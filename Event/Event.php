<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Event;

use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponOrder;
use Plugin\Coupon\Util\Version;
use Eccube\Event\TemplateEvent;
use Eccube\Common\Constant;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

/**
 * Class Event.
 */
class Event
{
    /**
     * @var Application
     */
    private $app;

    /**
     * position for insert in twig file.
     *
     * @var string
     */
    const COUPON_TAG = '<!--# coupon-plugin-tag #-->';

    /**
     * @var string 非会員用セッションキー
     */
    private $sessionKey = 'eccube.front.shopping.nonmember';

    /**
     * Event constructor.
     *
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * クーポン関連項目を追加する.
     *
     * @param TemplateEvent $event
     */
    public function onRenderShoppingIndex(TemplateEvent $event)
    {
        log_info('Coupon trigger onRenderShoppingIndex start');
        $app = $this->app;
        $parameters = $event->getParameters();
        if (is_null($parameters['Order'])) {
            return;
        }

        // 登録がない、レンダリングをしない
        $Order = $parameters['Order'];
        // クーポンが未入力でクーポン情報が存在すればクーポン情報を削除
        $CouponOrder = $this->app['eccube.plugin.coupon.service.coupon']->getCouponOrder($Order->getPreOrderId());
        // twigコードを挿入
        $snipet = $app['twig']->getLoader()->getSource('Coupon/Resource/template/default/coupon_shopping_item.twig');
        $source = $event->getSource();
        //find coupon mark
        if (strpos($source, self::COUPON_TAG)) {
            log_info('Render coupon with ', array('COUPON_TAG' => self::COUPON_TAG));
            $search = self::COUPON_TAG;
            $replace = $snipet.$search;
        } else {
            $search = '<h2 class="heading02">お問い合わせ欄</h2>';
            $replace = $snipet.$search;
        }
        $source = str_replace($search, $replace, $source);
        //add discount(値引き) layout for version < 3.0.10
        if (!Version::isSupportDisplayDiscount()) {
            // 値引き項目を表示
            if ($CouponOrder) {
                $total = $Order->getTotal() - $CouponOrder->getDiscount();
                $Order->setTotal($total);
                $Order->setPaymentTotal($total);
                // 合計、値引きを再計算し、dtb_orderを更新する
                $this->app['orm.em']->persist($Order);
                $this->app['orm.em']->flush($Order);
                // このタグを前後に分割し、間に項目を入れ込む
                // 元の合計金額は書き込み済みのため再度書き込みを行う
                $snipet = $app['twig']->getLoader()->getSource('Coupon/Resource/template/default/discount_shopping_item.twig');
                $search = '<div id="summary_box__result" class="total_amount">';
                $replace = $search.$snipet;
                $source = str_replace($search, $replace, $source);
                $parameters['Order'] = $Order;
            }
        }
        $event->setSource($source);
        //set parameter for twig files
        $parameters['CouponOrder'] = $CouponOrder;
        $event->setParameters($parameters);
        log_info('Coupon trigger onRenderShoppingIndex finish');
    }

    /**
     * クーポンが利用されていないかチェック.
     */
    public function onShoppingConfirmInit(EventArgs $event)
    {
        $app = $this->app;
        $builder = $event->getArgument('builder');

        // フォームイベントでバリデーションを実行する
        // 問題がある場合にはエラーを追加することで、バリデーションが失敗するようにして、購入確定処理に進まないようにする
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($app) {
            log_info('Coupon trigger onShoppingConfirmInit start');
            $cartService = $app['eccube.service.cart'];
            $preOrderId = $cartService->getPreOrderId();
            if (is_null($preOrderId)) {
                return;
            }

            $repository = $app['coupon.repository.coupon_order'];
            // クーポン受注情報を取得する
            $CouponOrder = $repository->findOneBy(array(
                'pre_order_id' => $preOrderId,
            ));

            if (!$CouponOrder) {
                return;
            }

            if ($app->isGranted('ROLE_USER')) {
                $Customer = $app->user();
            } else {
                $Customer = $app['eccube.service.shopping']->getNonMember($this->sessionKey);
            }
            //check if coupon valid or not
            $Coupon = $app['coupon.repository.coupon']->findActiveCoupon($CouponOrder->getCouponCd());
            if (is_null($Coupon)) {
                $errorMessage = $app->trans('front.plugin.coupon.shopping.notexists');
                $app->addError($errorMessage, 'front.request');
                $event->getForm()->addError(new FormError($errorMessage));

                return;
            }
            $Order = $app['eccube.repository.order']->find($CouponOrder->getOrderId());
            // Validation coupon again
            $validationMsg = $app['eccube.plugin.coupon.service.coupon']->couponValidation($Coupon->getCouponCd(), $Coupon, $Order, $Customer);
            if (!is_null($validationMsg)) {
                $app->addError($validationMsg, 'front.request');
                $event->getForm()->addError(new FormError($validationMsg));

                return;
            }
            $CouponOrder->setCouponName($Coupon->getCouponName());
            $repository->save($CouponOrder);
            log_info('Coupon trigger onShoppingConfirmInit end');
        });
    }

    /**
     * 注文クーポン情報に受注日付を登録する.
     */
    public function onRenderShoppingComplete()
    {
        log_info('Coupon trigger onRenderShoppingComplete start');
        $orderId = $this->app['session']->get('eccube.front.shopping.order.id');
        if (is_null($orderId)) {
            return;
        }
        $Order = $this->app['eccube.repository.order']->find($orderId);
        if (is_null($Order)) {
            return;
        }
        $repository = $this->app['coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        $CouponOrder = $repository->findOneBy(array(
            'order_id' => $orderId,
        ));
        if (is_null($CouponOrder)) {
            return;
        }
        $Coupon = $this->app['coupon.repository.coupon']->findActiveCoupon($CouponOrder->getCouponCd());
        // 更新対象データ
        $CouponOrder->setCouponName($Coupon->getCouponName());
        $CouponOrder->setOrderDate($Order->getOrderDate());
        $repository->save($CouponOrder);

        // クーポンの発行枚数を減らす(マイナスになっても無視する)
        $Coupon->setCouponUseTime($Coupon->getCouponUseTime() - 1);
        $this->app['orm.em']->persist($Coupon);
        $this->app['orm.em']->flush($Coupon);

        log_info('Coupon trigger onRenderShoppingComplete start');
    }

    /**
     * [order/{id}/edit]表示の時のEvent Fork.
     * クーポン関連項目を追加する.
     *
     * @param TemplateEvent $event
     */
    public function onRenderAdminOrderEdit(TemplateEvent $event)
    {
        log_info('Coupon trigger onRenderAdminOrderEdit start');
        $app = $this->app;
        $parameters = $event->getParameters();
        if (is_null($parameters['Order'])) {
            return;
        }
        $Order = $parameters['Order'];
        // クーポン受注情報を取得する
        $repCouponOrder = $this->app['coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        $CouponOrder = $repCouponOrder->findOneBy(array('order_id' => $Order->getID()));
        if (is_null($CouponOrder)) {
            return;
        }
        // twigコードを挿入
        $snipet = $app['twig']->getLoader()->getSource('Coupon/Resource/template/admin/order_edit_coupon.twig');
        $source = $event->getSource();
        //find coupon mark
        $search = '<div id="detail__insert_button" class="row btn_area">';
        $replace = $snipet.$search;
        $source = str_replace($search, $replace, $source);
        $event->setSource($source);
        //set parameter for twig files
        $parameters['coupon_cd'] = $CouponOrder->getCouponCd();
        $parameters['coupon_name'] = $CouponOrder->getCouponName();
        $event->setParameters($parameters);
        log_info('Coupon trigger onRenderAdminOrderEdit finish');
    }

    /**
     * 配送先や支払い方法変更時の合計金額と値引きの差額チェック
     * v3.0.9以降で使用.
     *
     * @param EventArgs $event
     */
    public function onRestoreDiscount(EventArgs $event)
    {
        log_info('Coupon trigger onRestoreDiscount start');
        if ($event->hasArgument('Order')) {
            $Order = $event->getArgument('Order');
        } else {
            $Shipping = $event->getArgument('Shipping');
            $Order = $Shipping->getOrder();
        }
        $this->restoreDiscount($Order);
        log_info('Coupon trigger onRestoreDiscount end');
    }

    /**
     * Hook point add coupon information to mypage history.
     *
     * @param TemplateEvent $event
     */
    public function onRenderMypageHistory(TemplateEvent $event)
    {
        log_info('Coupon trigger onRenderMypageHistory start');
        $app = $this->app;
        $parameters = $event->getParameters();
        if (is_null($parameters['Order'])) {
            return;
        }
        $Order = $parameters['Order'];
        // クーポン受注情報を取得する
        $repCouponOrder = $this->app['coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        $CouponOrder = $repCouponOrder->findOneBy(array(
            'order_id' => $Order->getId(),
        ));
        if (is_null($CouponOrder)) {
            return;
        }
        // twigコードを挿入
        $snipet = $app['twig']->getLoader()->getSource('Coupon/Resource/template/default/mypage_history_coupon.twig');
        $source = $event->getSource();
        if (strpos($source, self::COUPON_TAG)) {
            log_info('Render coupon with ', array('COUPON_TAG' => self::COUPON_TAG));
            $search = self::COUPON_TAG;
            $replace = $snipet.$search;
        } else {
            $search = '<h2 class="heading02">お問い合わせ</h2>';
            $replace = $snipet.$search;
        }
        //find coupon mark
        $source = str_replace($search, $replace, $source);
        $event->setSource($source);
        //set parameter for twig files
        $parameters['coupon_cd'] = $CouponOrder->getCouponCd();
        $parameters['coupon_name'] = $CouponOrder->getCouponName();
        $event->setParameters($parameters);
        log_info('Coupon trigger onRenderMypageHistory finish');
    }

    /**
     * Hook point send mail.
     *
     * @param EventArgs $event
     */
    public function onSendOrderMail(EventArgs $event)
    {
        log_info('Coupon trigger onSendOrderMail start');
        $repository = $this->app['coupon.repository.coupon_order'];
        $CouponOrder = null;
        $Coupon = null;
        $message = null;
        if ($event->hasArgument('Order')) {
            $Order = $event->getArgument('Order');
            // クーポン受注情報を取得する
            $CouponOrder = $repository->findOneBy(array(
                'order_id' => $Order->getId(),
            ));
            if (is_null($CouponOrder)) {
                return;
            }
            // クーポン受注情報からクーポン情報を取得する
            $repCoupon = $this->app['coupon.repository.coupon'];
            /* @var $Coupon Coupon */
            $Coupon = $repCoupon->find($CouponOrder->getCouponId());
            if (is_null($Coupon)) {
                return;
            }
        }

        if ($event->hasArgument('message')) {
            $message = $event->getArgument('message');
        }

        if (!is_null($CouponOrder)) {
            // メールボディ取得
            $body = $message->getBody();
            // 情報置換用のキーを取得
            $search = array();
            preg_match_all('/合　計.*\\n/u', $body, $search);
            // メール本文置換
            $snippet = PHP_EOL;
            $snippet .= PHP_EOL;
            $snippet .= '***********************************************'.PHP_EOL;
            $snippet .= '　クーポン情報                                 '.PHP_EOL;
            $snippet .= '***********************************************'.PHP_EOL;
            $snippet .= PHP_EOL;
            $snippet .= 'クーポンコード: '.$CouponOrder->getCouponCd().' '.$CouponOrder->getCouponName();
            $snippet .= PHP_EOL;
            $replace = $search[0][0].$snippet;
            $body = preg_replace('/'.$search[0][0].'/u', $replace, $body);
            // メッセージにメールボディをセット
            $message->setBody($body);
        }
        log_info('Coupon trigger onSendOrderMail finish');
    }

    /**
     * Hook point order edit completed.
     *
     * @param EventArgs $event
     */
    public function onOrderEditComplete(EventArgs $event)
    {
        log_info('Coupon trigger onOrderEditComplete start');
        $Order = null;
        $app = $this->app;
        $delFlg = false;
        //for edit event
        if ($event->hasArgument('TargetOrder')) {
            /* @var Order $Order */
            $Order = $event->getArgument('TargetOrder');
            if (is_null($Order)) {
                return;
            }
        }
        //for delete event
        if ($event->hasArgument('Order')) {
            /* @var Order $Order */
            $Order = $event->getArgument('Order');
            if (is_null($Order)) {
                return;
            }
            $delFlg = true;
        }
        $repository = $this->app['coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        /* @var CouponOrder $CouponOrder */
        $CouponOrder = $repository->findOneBy(array(
            'order_id' => $Order->getId(),
        ));
        if (is_null($CouponOrder)) {
            return;
        }
        $status = $Order->getOrderStatus()->getId();
        $orderStatusFlg = $CouponOrder->getOrderChangeStatus();
        if ($status == $app['config']['order_cancel'] || $status == $app['config']['order_processing'] || $delFlg) {
            if ($orderStatusFlg != Constant::ENABLED) {
                /* @var Coupon $Coupon */
                $Coupon = $this->app['coupon.repository.coupon']->find($CouponOrder->getCouponId());
                // クーポンの発行枚数を上がる
                if (!is_null($Coupon)) {
                    // 更新対象データ
                    $CouponOrder->setOrderDate(null);
                    $CouponOrder->setOrderChangeStatus(Constant::ENABLED);
                    $repository->save($CouponOrder);
                    $couponUseTime = $Coupon->getCouponUseTime() + 1;
                    $couponRelease = $Coupon->getCouponRelease();
                    if ($couponUseTime <= $couponRelease) {
                        $Coupon->setCouponUseTime($couponUseTime);
                    } else {
                        $Coupon->setCouponUseTime($couponRelease);
                    }
                    $this->app['orm.em']->persist($Coupon);
                    $this->app['orm.em']->flush($Coupon);
                }
            }
        }

        if ($status != $app['config']['order_cancel'] && $status != $app['config']['order_processing']) {
            if ($orderStatusFlg == Constant::ENABLED) {
                $Coupon = $this->app['coupon.repository.coupon']->find($CouponOrder->getCouponId());
                // クーポンの発行枚数を上がる
                if (!is_null($Coupon)) {
                    // 更新対象データ
                    $now = new \DateTime();
                    $CouponOrder->setOrderDate($now);
                    $CouponOrder->setOrderChangeStatus(Constant::DISABLED);
                    $repository->save($CouponOrder);
                    $Coupon->setCouponUseTime($Coupon->getCouponUseTime() - 1);
                    $this->app['orm.em']->persist($Coupon);
                    $this->app['orm.em']->flush($Coupon);
                }
            }
        }
        log_info('Coupon trigger onOrderEditComplete end');
    }

    /**
     * 合計金額がマイナスになっていた場合、値引き処理を元に戻す.
     *
     * @param Order $Order
     */
    private function restoreDiscount(Order $Order)
    {
        // クーポンが未入力でクーポン情報が存在すればクーポン情報を削除
        $CouponOrder = $this->app['eccube.plugin.coupon.service.coupon']->getCouponOrder($Order->getPreOrderId());
        if ($CouponOrder) {
            $total = $Order->getSubtotal() + $Order->getCharge() + $Order->getDeliveryFeeTotal();
            // 合計金額
            $totalAmount = $total - $Order->getDiscount();
            if ($totalAmount < 0) {
                // 合計金額がマイナスのため、金額を値引き前に戻す
                $this->app['orm.em']->remove($CouponOrder);
                $this->app['orm.em']->flush($CouponOrder);
                $discount = $Order->getDiscount() - $CouponOrder->getDiscount();
                $Order->setDiscount($discount);
                $total = $total - $discount;
                $Order->setTotal($total);
                $Order->setPaymentTotal($total);
                $this->app['orm.em']->persist($Order);
                $this->app['orm.em']->flush($Order);
                $this->app->addError($this->app->trans('front.plugin.coupon.shopping.use.minus'), 'front.request');
            }
        }
    }
}
