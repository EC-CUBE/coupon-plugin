<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon42;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\OrderRepository;
use Plugin\Coupon42\Entity\Coupon;
use Plugin\Coupon42\Repository\CouponOrderRepository;
use Plugin\Coupon42\Repository\CouponRepository;
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
            'Mypage/history.twig' => 'onRenderMypageHistory',
            '@admin/Order/edit.twig' => 'onRenderAdminOrderEdit',
        ];
    }

    /**
     * @param TemplateEvent $event
     */
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

        if (strpos($event->getView(), 'index.twig') !== false) {
            $event->addSnippet('@Coupon42/default/coupon_shopping_item.twig');
        } else {
            $event->addSnippet('@Coupon42/default/coupon_shopping_item_confirm.twig');
        }
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
        $event->addSnippet('@Coupon42/default/mypage_history_coupon.twig');
        log_info('Coupon trigger onRenderMypageHistory finish');
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
        // set parameter for twig files
        $parameters['coupon_cd'] = $CouponOrder->getCouponCd();
        $parameters['coupon_name'] = $CouponOrder->getCouponName();
        $parameters['coupon_change_status'] = $CouponOrder->getOrderChangeStatus();
        $event->setParameters($parameters);

        // add twig
        $event->addSnippet('@Coupon42/admin/order_edit_coupon.twig');

        log_info('Coupon trigger onRenderAdminOrderEdit finish');
    }
}
