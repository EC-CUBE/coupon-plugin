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

use Eccube\Application;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Plugin\Coupon\Util\Version;

/**
 * Class Event.
 */
class Event
{
    /** @var \Eccube\Application */
    private $app;

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
        $this->app['eccube.plugin.coupon.event']->onRenderShoppingIndex($event);
    }

    /**
     * クーポンが利用されていないかチェック.
     */
    public function onShoppingConfirmInit(EventArgs $event)
    {
        $this->app['eccube.plugin.coupon.event']->onShoppingConfirmInit($event);
    }

    /**
     * 注文クーポン情報に受注日付を登録する.
     */
    public function onRenderShoppingComplete()
    {
        $this->app['eccube.plugin.coupon.event']->onRenderShoppingComplete();
    }

    /**
     * [order/{id}/edit]表示の時のEvent Fock.
     * クーポン関連項目を追加する.
     *
     * @param TemplateEvent $event
     */
    public function onRenderAdminOrderEdit(TemplateEvent $event)
    {
        $this->app['eccube.plugin.coupon.event']->onRenderAdminOrderEdit($event);
    }

    /**
     * 配送先や支払い方法変更時の合計金額と値引きの差額チェック
     * v3.0.9以降で使用.
     *
     * @param EventArgs $event
     */
    public function onRestoreDiscount(EventArgs $event)
    {
        $this->app['eccube.plugin.coupon.event']->onRestoreDiscount($event);
    }

    /**
     * Hook point add coupon information to mypage history.
     *
     * @param TemplateEvent $event
     */
    public function onRenderMypageHistory(TemplateEvent $event)
    {
        $this->app['eccube.plugin.coupon.event']->onRenderMypageHistory($event);
    }

    /**
     * Hook point send mail.
     *
     * @param EventArgs $event
     */
    public function onSendOrderMail(EventArgs $event)
    {
        $this->app['eccube.plugin.coupon.event']->onSendOrderMail($event);
    }

    /**
     * Hook point order edit completed.
     *
     * @param EventArgs $event
     */
    public function onOrderEditComplete(EventArgs $event)
    {
        $this->app['eccube.plugin.coupon.event']->onOrderEditComplete($event);
    }

    /**
     * クーポン関連項目を追加する.
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderShoppingBefore(FilterResponseEvent $event)
    {
        //current version >= 3.0.9
        if (Version::isSupportNewHookPoint()) {
            return;
        }
        $this->app['eccube.plugin.coupon.event.legacy']->onRenderShoppingBefore($event);
    }

    /**
     * Hook point add coupon information to mypage history.
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderMypageHistoryBefore(FilterResponseEvent $event)
    {
        //current version >= 3.0.9
        if (Version::isSupportNewHookPoint()) {
            return;
        }
        $this->app['eccube.plugin.coupon.event.legacy']->onRenderMypageHistoryBefore($event);
    }

    /**
     * クーポンが利用されていないかチェック.
     */
    public function onControllerShoppingConfirmBefore()
    {
        //current version >= 3.0.9
        if (Version::isSupportNewHookPoint()) {
            return;
        }
        $this->app['eccube.plugin.coupon.event.legacy']->onControllerShoppingConfirmBefore();
    }

    /**
     * 注文クーポン情報に受注日付を登録する.
     */
    public function onControllerShoppingCompleteBefore()
    {
        //current version >= 3.0.9
        if (Version::isSupportNewHookPoint()) {
            return;
        }
        $this->app['eccube.plugin.coupon.event.legacy']->onControllerShoppingCompleteBefore();
    }

    /**
     * [order/{id}/edit]表示の時のEvent Fock.
     * クーポン関連項目を追加する.
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderAdminOrderEditBefore(FilterResponseEvent $event)
    {
        //current version >= 3.0.9
        if (Version::isSupportNewHookPoint()) {
            return;
        }
        $this->app['eccube.plugin.coupon.event.legacy']->onRenderAdminOrderEditBefore($event);
    }

    /**
     * 配送先や支払い方法変更時の合計金額と値引きの差額チェック
     * v3.0.8までで使用.
     */
    public function onControllerRestoreDiscountAfter()
    {
        //current version >= 3.0.9
        if (Version::isSupportNewHookPoint()) {
            return;
        }
        $this->app['eccube.plugin.coupon.event.legacy']->onControllerRestoreDiscountAfter();
    }

    /**
     * for order change status.
     */
    public function onControllerOrderEditAfter()
    {
        //current version >= 3.0.9
        if (Version::isSupportNewHookPoint()) {
            return;
        }
        $this->app['eccube.plugin.coupon.event.legacy']->onControllerOrderEditAfter();
    }

    /**
     * for order delete.
     */
    public function onControllerOrderDeleteAfter()
    {
        //current version >= 3.0.9
        if (Version::isSupportNewHookPoint()) {
            return;
        }
        $this->app['eccube.plugin.coupon.event.legacy']->onControllerOrderDeleteAfter();
    }
}
