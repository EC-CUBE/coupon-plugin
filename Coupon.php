<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
     * @param FilterResponseEvent $event
     */
    public function onShoppingIndex(FilterResponseEvent $event)
    {
        $this->legacyEvent->onRenderShoppingBefore($event);
    }

    /**
     * New hook point support in version >= 3.0.9
     * Shopping complete before render
     */
    public function onShoppingCompleteInit()
    {
        $this->legacyEvent->onControllerShoppingCompleteBefore();
    }

    /**
     * New hook point support in version >= 3.0.9
     * Admin Order Edit page
     *
     * @param FilterResponseEvent $event
     */
    public function onAdminOrderEdit(FilterResponseEvent $event)
    {
        $this->legacyEvent->onRenderAdminOrderEditAfter($event);
    }

    /**
     * New hook point support in version >= 3.0.9
     * Shopping confirm on request
     */
    public function onShoppingConfirmInit()
    {
        $this->legacyEvent->onControllerShoppingConfirmBefore();
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
}
