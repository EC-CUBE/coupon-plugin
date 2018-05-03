<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Application;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\OrderRepository;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponOrder;
use Plugin\Coupon\Repository\CouponOrderRepository;
use Plugin\Coupon\Repository\CouponRepository;
use Plugin\Coupon\Service\CouponService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
//use Plugin\Coupon\Util\Version;

/**
 * Class Event.
 */
class Event implements EventSubscriberInterface
{
    /**
     * @var CouponOrderRepository
     */
    private $couponOrderRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * Event constructor.
     * @param CouponOrderRepository $couponOrderRepository
     * @param EntityManagerInterface $entityManager
     * @param CouponRepository $couponRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(CouponOrderRepository $couponOrderRepository, EntityManagerInterface $entityManager, CouponRepository $couponRepository, OrderRepository $orderRepository)
    {
        $this->couponOrderRepository = $couponOrderRepository;
        $this->entityManager = $entityManager;
        $this->couponRepository = $couponRepository;
        $this->orderRepository = $orderRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'index',
            'Shopping/confirm.twig' => 'index',
            'Shopping/complete.twig' => 'complete',
        ];
    }

    public function index(TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        // 登録がない、レンダリングをしない
        /** @var Order $Order */
        $Order = $parameters['Order'];
        // クーポンが未入力でクーポン情報が存在すればクーポン情報を削除
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($Order->getPreOrderId());

        $parameters['CouponOrder'] = $CouponOrder;
        $event->setParameters($parameters);
    }

    public function complete(TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        /** @var Order $Order */
        $Order = $this->orderRepository->find($parameters['orderId']);
        if (!$Order) {
            return;
        }

        /** @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($Order->getPreOrderId());
        if (!$CouponOrder) {
            return;
        }
        $CouponOrder->setOrderDate($Order->getOrderDate());
        $this->couponOrderRepository->save($CouponOrder);

        /** @var Coupon $Coupon */
        $Coupon = $this->couponRepository->findActiveCoupon($CouponOrder->getCouponCd());
        if (!$Coupon) {
            return;
        }
        $Coupon->setCouponUseTime($Coupon->getCouponUseTime() - 1);
        $this->entityManager->persist($Coupon);
        $this->entityManager->flush($Coupon);
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
}
