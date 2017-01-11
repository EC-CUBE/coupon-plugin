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
use Eccube\Common\Constant;
use Eccube\Entity\Order;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponOrder;
use Plugin\Coupon\Util\Version;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * Class EventLegacy.
 */
class EventLegacy
{
    /** @var \Eccube\Application */
    private $app;

    /**
     * @var string 非会員用セッションキー
     */
    private $sessionKey = 'eccube.front.shopping.nonmember';

    /**
     * position for insert in twig file.
     *
     * @var string
     */
    const COUPON_TAG = '<!--# coupon-plugin-tag #-->';

    /**
     * EventLegacy constructor.
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
     * @param FilterResponseEvent $event
     */
    public function onRenderShoppingBefore(FilterResponseEvent $event)
    {
        log_info('Coupon trigger onRenderShoppingBefore start');
        $response = $event->getResponse();
        // 受注データを取得
        $Order = $this->getOrder();
        if (is_null($Order)) {
            return;
        }
        // クーポン関連項目を追加する
        $response->setContent($this->getHtmlShopping($response, $Order));
        $event->setResponse($response);
        log_info('Coupon trigger onRenderShoppingBefore end');
    }

    /**
     * クーポンが利用されていないかチェック.
     */
    public function onControllerShoppingConfirmBefore()
    {
        log_info('Coupon trigger onControllerShoppingConfirmBefore start');
        $cartService = $this->app['eccube.service.cart'];
        $preOrderId = $cartService->getPreOrderId();
        if (is_null($preOrderId)) {
            return;
        }

        $repository = $this->app['coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        $CouponOrder = $repository->findOneBy(array(
            'pre_order_id' => $preOrderId,
        ));

        if (!$CouponOrder) {
            return;
        }

        if ($this->app->isGranted('ROLE_USER')) {
            $Customer = $this->app->user();
        } else {
            $Customer = $this->app['eccube.service.shopping']->getNonMember($this->sessionKey);
        }

        //check if coupon valid or not
        $Coupon = $this->app['coupon.repository.coupon']->findActiveCoupon($CouponOrder->getCouponCd());
        if (is_null($Coupon)) {
            $this->app->addError($this->app->trans('front.plugin.coupon.shopping.notexists'), 'front.request');
            // 既に存在している
            header('Location: '.$this->app->url('shopping'));
            exit;
        }
        $Order = $this->app['eccube.repository.order']->find($CouponOrder->getOrderId());
        // Validation coupon again
        $validationMsg = $this->app['eccube.plugin.coupon.service.coupon']->couponValidation($Coupon->getCouponCd(), $Coupon, $Order, $Customer);
        if (!is_null($validationMsg)) {
            $this->app->addError($validationMsg, 'front.request');
            // 既に存在している
            header('Location: '.$this->app->url('shopping'));
            exit;
        }
        $CouponOrder->setCouponName($Coupon->getCouponName());
        $repository->save($CouponOrder);
        log_info('Coupon trigger onControllerShoppingConfirmBefore end');
    }

    /**
     * 注文クーポン情報に受注日付を登録する.
     */
    public function onControllerShoppingCompleteBefore()
    {
        log_info('Coupon trigger onControllerShoppingCompleteBefore start');
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
        if (!$CouponOrder) {
            return;
        }
        // 更新対象データ
        $CouponOrder->setOrderDate($Order->getOrderDate());
        $repository->save($CouponOrder);
        $Coupon = $this->app['coupon.repository.coupon']->findActiveCoupon($CouponOrder->getCouponCd());
        // クーポンの発行枚数を減らす(マイナスになっても無視する)
        $Coupon->setCouponUseTime($Coupon->getCouponUseTime() - 1);
        $this->app['orm.em']->persist($Coupon);
        $this->app['orm.em']->flush($Coupon);
        log_info('Coupon trigger onControllerShoppingCompleteBefore end');
    }

    /**
     * [order/{id}/edit]表示の時のEvent Fork.
     * クーポン関連項目を追加する.
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderAdminOrderEditBefore(FilterResponseEvent $event)
    {
        log_info('Coupon trigger onRenderAdminOrderEditAfter start');
        $request = $event->getRequest();
        $response = $event->getResponse();
        // 受注IDを取得する
        $orderId = $request->get('id');
        if (is_null($orderId)) {
            return;
        }
        // クーポン受注情報を取得する
        $repCouponOrder = $this->app['coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        $CouponOrder = $repCouponOrder->findOneBy(array(
            'order_id' => $orderId,
        ));
        if (is_null($CouponOrder)) {
            return;
        }
        // 編集画面にクーポン表示を追加する
        $this->getHtmlOrderEdit($response, $CouponOrder);
        log_info('Coupon trigger onRenderAdminOrderEditAfter end');
    }

    /**
     * Hook point add coupon information to mypage history.
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderMypageHistoryBefore(FilterResponseEvent $event)
    {
        log_info('Coupon trigger onRenderMypageHistoryBefore start');
        try {
            // 受注データを取得
            $request = $event->getRequest();
            $response = $event->getResponse();
            // 受注IDを取得する
            $orderId = $request->get('id');
            if (is_null($orderId)) {
                return;
            }
            // クーポン受注情報を取得する
            $repCouponOrder = $this->app['coupon.repository.coupon_order'];
            // クーポン受注情報を取得する
            $CouponOrder = $repCouponOrder->findOneBy(array(
                'order_id' => $orderId,
            ));
            if (is_null($CouponOrder)) {
                return;
            }
            // クーポン受注情報からクーポン情報を取得する
            $twig = $this->app->renderView('Coupon/Resource/template/default/mypage_history_coupon.twig', array(
                'coupon_cd' => $CouponOrder->getCouponCd(),
                'coupon_name' => $CouponOrder->getCouponName(),
            ));
            $crawler = new Crawler($response->getContent());
            $html = $this->getHtml($crawler);
            if (strpos($html, self::COUPON_TAG)) {
                log_info('Render coupont with ', array('COUPON_TAG' => self::COUPON_TAG));
                $search = self::COUPON_TAG;
                $replace = $search.$twig;
                $html = str_replace($search, $replace, $html);
            } else {
                // このタグを前後に分割し、間に項目を入れ込む
                $beforeHtml = $crawler->filter('#confirm_main')->last()->html();
                $pos = strrpos($beforeHtml, '<h2 class="heading02">');
                if ($pos !== false) {
                    $oldHtml = substr($beforeHtml, 0, $pos);
                    $afterHtml = substr($beforeHtml, $pos);
                    $newHtml = $oldHtml.$twig.$afterHtml;
                    $beforeHtml = html_entity_decode($beforeHtml, ENT_NOQUOTES, 'UTF-8');
                    $html = str_replace($beforeHtml, $newHtml, $html);
                }
            }
            $response->setContent($html);
            $event->setResponse($response);
        } catch (\InvalidArgumentException $e) {
            log_info($e->getMessage());
        }
        log_info('Coupon trigger onRenderMypageHistoryBefore end');
    }

    /**
     * 配送先や支払い方法変更時の合計金額と値引きの差額チェック
     * v3.0.8までで使用.
     */
    public function onControllerRestoreDiscountAfter()
    {
        log_info('Coupon trigger onControllerRestoreDiscountAfter start');
        // 受注データを取得
        $Order = $this->getOrder();
        if (!$Order) {
            return;
        }
        $this->restoreDiscount($Order);
        log_info('Coupon trigger onControllerRestoreDiscountAfter end');
    }

    /**
     * for order change status.
     */
    public function onControllerOrderDeleteAfter()
    {
        log_info('Coupon trigger onControllerOrderDeleteAfter start');
        $Order = null;
        $app = $this->app;
        $id = $app['request']->get('id');
        $Order = $app['eccube.repository.order']->find($id);
        if (is_null($Order)) {
            return;
        }
        $repository = $this->app['coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        $CouponOrder = $repository->findOneBy(array(
            'order_id' => $Order->getId(),
        ));
        if (is_null($CouponOrder)) {
            return;
        }
        $orderDate = $CouponOrder->getOrderDate();
        $orderStatusFlg = $CouponOrder->getOrderChangeStatus();
        if ($orderStatusFlg == Constant::DISABLED && $orderDate) {
            // 更新対象データ
            $CouponOrder->setOrderDate(null);
            $repository->save($CouponOrder);
            $Coupon = $this->app['coupon.repository.coupon']->find($CouponOrder->getCouponId());
            if (!is_null($Coupon)) {
                // クーポンの発行枚数を上がる
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
        log_info('Coupon trigger onControllerOrderDeleteAfter end');
    }

    /**
     * for order delete.
     */
    public function onControllerOrderEditAfter()
    {
        log_info('Coupon trigger onControllerOrderEditAfter start');
        $Order = null;
        $app = $this->app;
        $id = $app['request']->get('id');
        $Order = $app['eccube.repository.order']->find($id);
        if (is_null($Order)) {
            return;
        }
        $repository = $this->app['coupon.repository.coupon_order'];
        // クーポン受注情報を取得する
        $CouponOrder = $repository->findOneBy(array(
            'order_id' => $Order->getId(),
        ));
        if (is_null($CouponOrder)) {
            return;
        }
        $status = $Order->getOrderStatus()->getId();
        $orderStatusFlg = $CouponOrder->getOrderChangeStatus();
        if ($status == $app['config']['order_cancel'] || $status == $app['config']['order_processing']) {
            if ($orderStatusFlg != Constant::ENABLED) {
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
        log_info('Coupon trigger onControllerOrderEditAfter end');
    }

    // =========================================================
    // クラス内メソッド
    // =========================================================

    /**
     * 受注情報編集画面にクーポン情報を追加する.
     *
     * @param Response $response
     * @param CouponOrder $CouponOrder
     */
    private function getHtmlOrderEdit(Response $response, CouponOrder $CouponOrder)
    {
        $source = $response->getContent();
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">'.$source);
        $dom->encoding = 'UTF-8';

        $Elements = $dom->getElementsByTagName('*');
        $parentNode = null;
        $operationNode = null;

        // for new version (> 3.0.4)
        $parentNodeValue = 'col-md-12';
        $operationNodeValue = 'row btn_area';
        // for old version (<= 3.0.4)
        if (version_compare(Constant::VERSION, '3.0.4', '<=')) {
            $parentNodeValue = 'col-md-9';
            $operationNodeValue = 'row hidden-xs hidden-sm';
        }

        for ($i = 0; $i < $Elements->length; ++$i) {
            if (@$Elements->item($i)->attributes->getNamedItem('class')->nodeValue == $parentNodeValue) {
                // 親ノードを保持する
                $parentNode = $Elements->item($i);
            } elseif (@$Elements->item($i)->attributes->getNamedItem('class')->nodeValue == $operationNodeValue) {
                // 操作部ノードを保持する
                $operationNode = $Elements->item($i);
            }
        }

        // 親ノード、操作部（登録ボタン、戻るリンク）ノードが取得できた場合のみクーポン情報を表示する
        if (!is_null($parentNode) && !is_null($operationNode)) {
            // 追加するクーポン情報のHTMLを取得する.
            $insert = $this->app->renderView('Coupon/Resource/template/admin/order_edit_coupon.twig', array(
                'coupon_cd' => $CouponOrder->getCouponCd(),
                'coupon_name' => $CouponOrder->getCouponName(),
            ));
            $template = $dom->createDocumentFragment();
            $template->appendXML($insert);
            // ChildNodeの途中には追加ができないため、一旦操作部を削除する
            // その後、クーポン情報、操作部の順にappendする
            // Insert coupon template before operationNode
            $parentNode->insertBefore($template, $operationNode);
            $response->setContent($dom->saveHTML());
        }
    }

    /**
     * ご注文内容のご確認画面のHTMLを取得し、関連項目を書き込む
     * お支払方法の下に下記の項目を追加する.(id=confirm_main )
     * ・クーポンコードボタン
     * 送料のの下に下記の項目を追加する.(class=total_box total_amountの上)
     * ・値引き表示.
     *
     * @param Response $response
     * @param Order    $Order
     *
     * @return mixed|string
     */
    private function getHtmlShopping(Response $response, Order $Order)
    {
        // HTMLを取得し、DOM化
        $crawler = new Crawler($response->getContent());
        $html = $this->getHtml($crawler);

        try {
            // クーポンが未入力でクーポン情報が存在すればクーポン情報を削除
            $CouponOrder = $this->app['eccube.plugin.coupon.service.coupon']->getCouponOrder($Order->getPreOrderId());

            $parts = $this->app->renderView('Coupon/Resource/template/default/coupon_shopping_item.twig', array(
                'CouponOrder' => $CouponOrder,
            ));

            if (strpos($html, self::COUPON_TAG)) {
                log_info('Render coupont with ', array('COUPON_TAG' => self::COUPON_TAG));
                $search = self::COUPON_TAG;
                $replace = $search.$parts;
                $html = str_replace($search, $replace, $html);
            } else {
                // このタグを前後に分割し、間に項目を入れ込む
                $beforeHtml = $crawler->filter('#confirm_main')->last()->html();
                $pos = strrpos($beforeHtml, '<h2 class="heading02">');
                if ($pos !== false) {
                    $oldHtml = substr($beforeHtml, 0, $pos);
                    $afterHtml = substr($beforeHtml, $pos);
                    $newHtml = $oldHtml.$parts.$afterHtml;
                    $beforeHtml = html_entity_decode($beforeHtml, ENT_NOQUOTES, 'UTF-8');
                    $html = str_replace($beforeHtml, $newHtml, $html);
                }
            }

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
                    $parts = $this->app->renderView('Coupon/Resource/template/default/old_discount_shopping_item.twig', array(
                        'Order' => $Order,
                    ));
                    $form = $crawler->filter('#confirm_side .total_box')->last()->html();
                    $pos = strrpos($form, '</dl>');
                    if ($pos !== false) {
                        $oldHtml = substr($form, 0, $pos);
                        $newHtml = $oldHtml.$parts;
                        $html = str_replace($form, $newHtml, $html);
                    }
                }
            }
        } catch (\InvalidArgumentException $e) {
            // no-op
        }

        return $html;
    }

    /**
     * 受注データを取得.
     *
     * @return null|object
     */
    private function getOrder()
    {
        // 受注データを取得
        $preOrderId = $this->app['eccube.service.cart']->getPreOrderId();
        $Order = $this->app['eccube.repository.order']->findOneBy(
            array(
                'pre_order_id' => $preOrderId,
                'OrderStatus' => $this->app['config']['order_processing'],
            ));

        return $Order;
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

    /**
     * 解析用HTMLを取得.
     *
     * @param Crawler $crawler
     *
     * @return string
     */
    private function getHtml(Crawler $crawler)
    {
        $html = '';
        /** @var \DOMElement $domElement */
        foreach ($crawler as $domElement) {
            $domElement->ownerDocument->formatOutput = true;
            $html .= $domElement->ownerDocument->saveHTML();
        }

        return html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }
}
