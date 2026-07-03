<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon44;

use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Plugin\Coupon44\Entity\Coupon;
use Plugin\Coupon44\Repository\CouponOrderRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class Event.
 */
class Event implements EventSubscriberInterface
{
    /**
     * Event constructor.
     *
     * @param CouponOrderRepository $couponOrderRepository
     */
    public function __construct(private readonly CouponOrderRepository $couponOrderRepository)
    {
    }

    /**
     * Todo: admin.order.delete.complete has been deleted.
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
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
    public function index(TemplateEvent $event): void
    {
        $parameters = $event->getParameters();
        // 登録がない、レンダリングをしない
        /** @var Order $Order */
        $Order = $parameters['Order'];
        // クーポンが未入力でクーポン情報が存在すればクーポン情報を削除
        $CouponOrder = $this->couponOrderRepository->getCouponOrder($Order->getPreOrderId());
        $parameters['CouponOrder'] = $CouponOrder;
        $event->setParameters($parameters);

        if (str_contains((string) $event->getView(), 'index.twig')) {
            $event->addSnippet('@Coupon44/default/coupon_shopping_item.twig');
        } else {
            $event->addSnippet('@Coupon44/default/coupon_shopping_item_confirm.twig');
        }
    }

    /**
     * Hook point add coupon information to mypage history.
     *
     * @param TemplateEvent $event
     */
    public function onRenderMypageHistory(TemplateEvent $event): void
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
        $event->addSnippet('@Coupon44/default/mypage_history_coupon.twig');
        log_info('Coupon trigger onRenderMypageHistory finish');
    }

    /**
     * [order/{id}/edit]表示の時のEvent Fork.
     * クーポン関連項目を追加する.
     *
     * @param TemplateEvent $event
     */
    public function onRenderAdminOrderEdit(TemplateEvent $event): void
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
        $event->addSnippet('@Coupon44/admin/order_edit_coupon.twig');

        log_info('Coupon trigger onRenderAdminOrderEdit finish');
    }
}
