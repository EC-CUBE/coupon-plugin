<?php
/**
 * Created by PhpStorm.
 * User: lqdung
 * Date: 6/1/2016
 * Time: 3:56 PM
 */

namespace Plugin\Coupon;

use Eccube\Common\Constant;
use Eccube\Event\RenderEvent;
use Symfony\Component\Form as Error;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Validator\Constraints as Assert;

class Coupon
{
    /**
     * @var \Eccube\Application
     */
    private $app;

    /**
     * v3.0.0 - 3.0.8 向けのイベントを処理するインスタンス
     *
     * @var CouponLegacy
     */
    private $legacyEvent;


    public function __construct($app)
    {
        $this->app = $app;
        $this->legacyEvent = new CouponLegacy($app);
    }

    /**
     * New hook point support in version >= 3.0.9
     * Shopping index render view
     * @param \Eccube\Event\TemplateEvent $event
     */
    public function onShoppingIndex(\Eccube\Event\TemplateEvent $event)
    {
        $parameters = $event->getParameters();

        // Order
        $Order = $this->getOrder();
        if (is_null($Order)) {
            return;
        }

        // Content
        $source = $event->getSource();
        // Position
        $search = '<div class="extra-form column">';
        // Template need addition (twig)
        $parts = $this->app['twig']->getLoader()->getSource('Coupon\View\coupon_shopping_item.twig');
        $replace = $parts.$search;
        $source = str_replace($search, $replace, $source);
        $event->setSource($source);

        // Coupon order service
        $CouponOrder = $this->app['eccube.plugin.coupon.service.coupon']->getCouponOrder($Order->getPreOrderId());
        $parameters['CouponOrder'] = $CouponOrder;
        $event->setParameters($parameters);
    }

    /**
     * New hook point support in version >= 3.0.9
     * Shopping complete before render
     *
     * @param \Eccube\Event\EventArgs $event
     */
    public function onShoppingConfirmComplete(\Eccube\Event\EventArgs $event)
    {
        $orderId = $this->app['session']->get('eccube.front.shopping.order.id');
        if (is_null($orderId)) {
            return;
        }

        $repository = $this->app['eccube.plugin.coupon.repository.coupon_order'];

        // クーポン受注情報を取得する
        $CouponOrder = $repository->findOneBy(array(
            'order_id' => $orderId
        ));
        if (is_null($CouponOrder)) {
            return;
        }
        // 更新対象データ

        $now = new \DateTime();

        $CouponOrder->setOrderDate($now);
        $CouponOrder->setUpdateDate($now);

        $repository->save($CouponOrder);

        $Coupon = $this->app['eccube.plugin.coupon.repository.coupon']->findActiveCoupon($CouponOrder->getCouponCd());
        // クーポンの発行枚数を減らす(マイナスになっても無視する)
        $Coupon->setCouponUseTime($Coupon->getCouponUseTime() - 1);
        $this->app['orm.em']->flush($Coupon);
    }

    /**
     * New hook point support in version >= 3.0.9
     * Admin Order Edit page
     *
     * @param \Eccube\Event\TemplateEvent $event
     */
    public function onAdminOrderEdit(\Eccube\Event\TemplateEvent $event)
    {
        $parameters = $event->getParameters();
        $orderId = $parameters['id'];
        if (is_null($orderId)) {
            return;
        }
        // クーポン受注情報を取得する
        $repCouponOrder = $this->app['eccube.plugin.coupon.repository.coupon_order'];

        // クーポン受注情報を取得する
        $CouponOrder = $repCouponOrder->findUseCouponByOrderId($orderId);
        if (is_null($CouponOrder)) {
            return;
        }

        // クーポン受注情報からクーポン情報を取得する
        $repCoupon = $this->app['eccube.plugin.coupon.repository.coupon'];
        $Coupon = $repCoupon->find($CouponOrder->getCouponId());
        if (is_null($Coupon)) {
            return;
        }

        // Content
        $source = $event->getSource();
        // Position
        $search = '<div id="detail__insert_button" class="row btn_area">';
        // Template need addition (html)
        $parts  = $this->app->renderView('Coupon/View/admin/order_edit_coupon.twig', array(
            'form' => $Coupon
        ));

        $replace = $parts.$search;
        $source  = str_replace($search, $replace, $source);
        $event->setSource($source);
    }

    public function onShoppingConfirmInit()
    {
        $cartService = $this->app['eccube.service.cart'];

        $preOrderId = $cartService->getPreOrderId();
        if (is_null($preOrderId)) {
            return;
        }

        $repository = $this->app['eccube.plugin.coupon.repository.coupon_order'];
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

        // クーポンが既に利用されているかチェック
        $couponUsedOrNot = $this->app['eccube.plugin.coupon.service.coupon']
            ->checkCouponUsedOrNotBefore($CouponOrder->getCouponCd(), $CouponOrder->getOrderId(), $Customer);

        if ($couponUsedOrNot) {
            $this->app->addError($this->app->trans('front.plugin.coupon.shopping.sameuser'), 'front.request');
            // 既に存在している
            header("Location: ".$this->app->url('shopping'));
            exit;
        }
    }

    /**
     * クーポン関連項目を追加する
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderShoppingBefore(FilterResponseEvent $event)
    {
        if ($this->supportNewHookPoint()) {
            return;
        }
        $this->legacyEvent->onRenderShoppingBefore($event);
    }

    /**
     * クーポンが利用されていないかチェック
     *
     */
    public function onControllerShoppingConfirmBefore()
    {
        if ($this->supportNewHookPoint()) {
            return;
        }
        $this->legacyEvent->onControllerShoppingConfirmBefore();
    }

    /**
     * 注文クーポン情報に受注日付を登録する.
     *
     */
    public function onControllerShoppingCompleteBefore()
    {
        if ($this->supportNewHookPoint()) {
            return;
        }
        $this->legacyEvent->onControllerShoppingCompleteBefore();
    }

    /**
     * [order/{id}/edit]表示の時のEvent Fock.
     * クーポン関連項目を追加する
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderAdminOrderEditAfter(FilterResponseEvent $event)
    {
        if ($this->supportNewHookPoint()) {
            return;
        }
        $this->legacyEvent->onRenderAdminOrderEditAfter($event);
    }


    /**
     * 配送先や支払い方法変更時の合計金額と値引きの差額チェック
     * v3.0.8までで使用
     *
     */
    public function onControllerRestoreDiscountAfter()
    {
        if ($this->supportNewHookPoint()) {
            return;
        }
        $this->legacyEvent->onControllerRestoreDiscountAfter();
    }

    /**
     * 配送先や支払い方法変更時の合計金額と値引きの差額チェック
     * v3.0.9以降で使用
     *
     * @param \Eccube\Event\EventArgs $event
     */
    public function onRestoreDiscount(\Eccube\Event\EventArgs $event)
    {
        $this->legacyEvent->onRestoreDiscount($event);
    }

    /**
     * v3.0.9以降のフックポイントに対応しているのか
     *
     * @return bool
     */
    private function supportNewHookPoint()
    {
        return version_compare('3.0.9', Constant::VERSION, '<=');
    }

    /**
     * 受注データを取得
     *
     * @return null|object
     */
    private function getOrder()
    {
        // 受注データを取得

        $preOrderId = $this->app['eccube.service.cart']->getPreOrderId();
        $Order = $this->app['eccube.repository.order']->findOneBy(array(
            'pre_order_id' => $preOrderId,
            'OrderStatus' => $this->app['config']['order_processing']
        ));

        return $Order;
    }
}