<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\Constant;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\OrderRepository;
use Plugin\Coupon\Entity\Coupon;
use Plugin\Coupon\Entity\CouponOrder;
use Plugin\Coupon\Repository\CouponOrderRepository;
use Plugin\Coupon\Repository\CouponRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * Event constructor.
     *
     * @param CouponOrderRepository $couponOrderRepository
     * @param EntityManagerInterface $entityManager
     * @param CouponRepository $couponRepository
     * @param OrderRepository $orderRepository
     * @param \Twig_Environment $twig
     */
    public function __construct(CouponOrderRepository $couponOrderRepository, EntityManagerInterface $entityManager, CouponRepository $couponRepository, OrderRepository $orderRepository, \Twig_Environment $twig)
    {
        $this->couponOrderRepository = $couponOrderRepository;
        $this->entityManager = $entityManager;
        $this->couponRepository = $couponRepository;
        $this->orderRepository = $orderRepository;
        $this->twig = $twig;
    }

    /**
     * Todo: admin.order.delete.complete has been deleted.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'index',
            'Shopping/confirm.twig' => 'index',
            'Shopping/complete.twig' => 'complete',
            'Mypage/history.twig' => 'onRenderMypageHistory',
            'mail.order' => 'onSendOrderMail',
            'mail.admin.order' => 'onSendOrderMail',
            'Admin/@admin/Order/edit.twig' => 'onRenderAdminOrderEdit',
            'admin.order.edit.index.complete' => 'onOrderEditComplete',
            'admin.order.delete.complete' => 'onOrderEditComplete', // has been deleted
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
     * Hook point add coupon information to mypage history.
     *
     * @param TemplateEvent $event
     */
    public function onRenderMypageHistory(TemplateEvent $event)
    {
        log_info('Coupon trigger onRenderMypageHistory start');
        $parameters = $event->getParameters();
        if (is_null($parameters['Order'])) {
            return;
        }
        $Order = $parameters['Order'];
        // クーポン受注情報を取得する
        $CouponOrder = $this->couponOrderRepository->findOneBy([
            'order_id' => $Order->getId(),
        ]);
        if (is_null($CouponOrder)) {
            return;
        }

        // set parameter for twig files
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
        $CouponOrder = null;
        if ($event->hasArgument('Order')) {
            $Order = $event->getArgument('Order');
            // クーポン受注情報を取得する
            $CouponOrder = $this->couponOrderRepository->findOneBy([
                'order_id' => $Order->getId(),
            ]);
            if (is_null($CouponOrder)) {
                return;
            }
        }

        if ($CouponOrder) {
            $message = $event->getArgument('message');
            // メールボディ取得
            $body = $message->getBody();
            // 情報置換用のキーを取得
            $search = [];
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
     * [order/{id}/edit]表示の時のEvent Fork.
     * クーポン関連項目を追加する.
     *
     * @param TemplateEvent $event
     */
    public function onRenderAdminOrderEdit(TemplateEvent $event)
    {
        log_info('Coupon trigger onRenderAdminOrderEdit start');
        $parameters = $event->getParameters();
        if (is_null($parameters['Order'])) {
            return;
        }
        $Order = $parameters['Order'];
        // クーポン受注情報を取得する
        $CouponOrder = $this->couponOrderRepository->findOneBy(['order_id' => $Order->getId()]);
        if (is_null($CouponOrder)) {
            return;
        }
        // twigコードを挿入
        $snipet = $this->twig->getLoader()->getSourceContext('Coupon/Resource/template/admin/order_edit_coupon.twig')->getCode();
        $source = $event->getSource();
        preg_match_all('/(<div class="card rounded border-0 mb-4">)/i', $source, $matches, PREG_OFFSET_CAPTURE);
        $pos = $matches[0][count($matches[0]) - 1][1];
        $aboveSection = substr($source, 0, $pos);
        $belowSection = substr($source, $pos);
        $newSource = $aboveSection.$snipet.$belowSection;
        $event->setSource($newSource);
        // set parameter for twig files
        $parameters['coupon_cd'] = $CouponOrder->getCouponCd();
        $parameters['coupon_name'] = $CouponOrder->getCouponName();
        $event->setParameters($parameters);
        log_info('Coupon trigger onRenderAdminOrderEdit finish');
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
        // クーポン受注情報を取得する
        /* @var CouponOrder $CouponOrder */
        $CouponOrder = $this->couponOrderRepository->findOneBy([
            'order_id' => $Order->getId(),
        ]);
        if (is_null($CouponOrder)) {
            return;
        }
        $status = $Order->getOrderStatus()->getId();
        $orderStatusFlg = $CouponOrder->getOrderChangeStatus();
        if ($status == OrderStatus::CANCEL || $status == OrderStatus::PROCESSING || $delFlg) {
            if ($orderStatusFlg != Constant::ENABLED) {
                /* @var Coupon $Coupon */
                $Coupon = $this->couponRepository->find($CouponOrder->getCouponId());
                // クーポンの発行枚数を上がる
                if (!is_null($Coupon)) {
                    // 更新対象データ
                    $CouponOrder->setOrderDate(null);
                    $CouponOrder->setOrderChangeStatus(Constant::ENABLED);
                    $this->couponOrderRepository->save($CouponOrder);
                    $couponUseTime = $Coupon->getCouponUseTime() + 1;
                    $couponRelease = $Coupon->getCouponRelease();
                    if ($couponUseTime <= $couponRelease) {
                        $Coupon->setCouponUseTime($couponUseTime);
                    } else {
                        $Coupon->setCouponUseTime($couponRelease);
                    }
                    $this->entityManager->persist($Coupon);
                    $this->entityManager->flush($Coupon);
                }
            }
        }

        if ($status != OrderStatus::CANCEL && $status != OrderStatus::PROCESSING) {
            if ($orderStatusFlg == Constant::ENABLED) {
                $Coupon = $this->couponRepository->find($CouponOrder->getCouponId());
                // クーポンの発行枚数を上がる
                if (!is_null($Coupon)) {
                    // 更新対象データ
                    $now = new \DateTime();
                    $CouponOrder->setOrderDate($now);
                    $CouponOrder->setOrderChangeStatus(Constant::DISABLED);
                    $this->couponOrderRepository->save($CouponOrder);
                    $Coupon->setCouponUseTime($Coupon->getCouponUseTime() - 1);
                    $this->entityManager->persist($Coupon);
                    $this->entityManager->flush($Coupon);
                }
            }
        }
        log_info('Coupon trigger onOrderEditComplete end');
    }
}
