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
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Eccube\Event\RenderEvent;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form as Error;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Validator\Constraints as Assert;

class CouponLegacy
{

    /** @var \Eccube\Application */
    private $app;

    /**
     * @var string 非会員用セッションキー
     */
    private $sessionKey = 'eccube.front.shopping.nonmember';

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * クーポン関連項目を追加する
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderShoppingBefore(FilterResponseEvent $event)
    {

        $request = $event->getRequest();
        $response = $event->getResponse();

        // 受注データを取得
        $Order = $this->getOrder();
        if (is_null($Order)) {
            return;
        }

        // クーポン関連項目を追加する
        $response->setContent($this->getHtmlShopping($request, $response, $Order));

        $event->setResponse($response);

    }

    /**
     * クーポンが利用されていないかチェック
     *
     */
    public function onControllerShoppingConfirmBefore()
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
     * 注文クーポン情報に受注日付を登録する.
     *
     */
    public function onControllerShoppingCompleteBefore()
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

        if (!$CouponOrder) {
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
     * [order/{id}/edit]表示の時のEvent Fock.
     * クーポン関連項目を追加する
     *
     * @param FilterResponseEvent $event
     */
    public function onRenderAdminOrderEditAfter(FilterResponseEvent $event)
    {
        $app = &$this->app;

        $request = $event->getRequest();
        $response = $event->getResponse();

        // 受注IDを取得する
        $orderId = $request->get('id');
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

        // 編集画面にクーポン表示を追加する
        $this->getHtmlOrderEdit($request, $response, $Coupon);
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

        // 受注データを取得
        $Order = $this->getOrder();
        if (!$Order) {
            return;
        }

        $this->restoreDiscount($Order);
    }


    /**
     * 配送先や支払い方法変更時の合計金額と値引きの差額チェック
     * v3.0.9以降で使用
     *
     * @param EventArgs $event
     */
    public function onRestoreDiscount(EventArgs $event)
    {

        if ($event->hasArgument('Order')) {
            $Order = $event->getArgument('Order');
        } else {
            $Shipping = $event->getArgument('Shipping');
            $Order = $Shipping->getOrder();
        }

        $this->restoreDiscount($Order);

    }

    // =========================================================
    // クラス内メソッド
    // =========================================================

    /**
     * 受注情報編集画面にクーポン情報を追加する
     *
     * @param Request $request
     * @param Response $response
     * @param $Coupon
     */
    private function getHtmlOrderEdit(Request $request, Response $response, $Coupon)
    {
        $source = $response->getContent();
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">'.$source);
        $dom->encoding = "UTF-8";

        /** @var DOMNodeList */
        $Elements = $dom->getElementsByTagName("*");
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
        for ($i = 0; $i < $Elements->length; $i++) {
            if (@$Elements->item($i)->attributes->getNamedItem('class')->nodeValue == $parentNodeValue) {
                // 親ノードを保持する
                $parentNode = $Elements->item($i);
            } else if (@$Elements->item($i)->attributes->getNamedItem('class')->nodeValue == $operationNodeValue) {
                // 操作部ノードを保持する
                $operationNode = $Elements->item($i);
            }
        }

        // 親ノード、操作部（登録ボタン、戻るリンク）ノードが取得できた場合のみクーポン情報を表示する
        if (!is_null($parentNode) && !is_null($operationNode)) {

            // 追加するクーポン情報のHTMLを取得する.
            $insert = $this->app->renderView('Coupon/View/admin/order_edit_coupon.twig', array(
                'form' => $Coupon
            ));

            $template = $dom->createDocumentFragment();
            $template->appendXML($insert);


            // ChildNodeの途中には追加ができないため、一旦操作部を削除する
            // その後、クーポン情報、操作部の順にappendする

            // 操作部のノードを削除
            $parentNode->removeChild($operationNode);

            // クーポン情報のノードを追加
            $parentNode->appendChild($template);

            // 操作部のノードを削除
            $parentNode->appendChild($operationNode);

            $response->setContent($dom->saveHTML());
        }

    }


    /**
     * ご注文内容のご確認画面のHTMLを取得し、関連項目を書き込む
     * お支払方法の下に下記の項目を追加する.(id=confirm_main )
     * ・クーポンコードボタン
     * 送料のの下に下記の項目を追加する.(class=total_box total_amountの上)
     * ・値引き表示
     *
     * @param Request $request
     * @param Response $response
     * @param Order $Order
     * @return mixed|string
     */
    private function getHtmlShopping(Request $request, Response $response, Order $Order)
    {

        // HTMLを取得し、DOM化
        $crawler = new Crawler($response->getContent());
        $html = $this->getHtml($crawler);

        try {

            // クーポンが未入力でクーポン情報が存在すればクーポン情報を削除
            $CouponOrder = $this->app['eccube.plugin.coupon.service.coupon']->getCouponOrder($Order->getPreOrderId());

            $parts = $this->app->renderView('Coupon/View/coupon_shopping_item.twig', array(
                'CouponOrder' => $CouponOrder,
            ));

            // このタグを前後に分割し、間に項目を入れ込む
            $beforeHtml = $crawler->filter('#confirm_main')->last()->html();
            $pos = strrpos($beforeHtml, '<h2 class="heading02">');
            if ($pos !== false) {
                $oldHtml = substr($beforeHtml, 0, $pos);
                $afterHtml = substr($beforeHtml, $pos);
                $newHtml = $oldHtml.$parts.$afterHtml;
                $html = str_replace($beforeHtml, $newHtml, $html);
            }

            if (!version_compare('3.0.10', Constant::VERSION, '<=')) {
                // 値引き項目を表示

                if ($CouponOrder) {
                    $total = $Order->getTotal() - $CouponOrder->getDiscount();
                    $Order->setTotal($total);
                    $Order->setPaymentTotal($total);

                    // 合計、値引きを再計算し、dtb_orderを更新する
                    $this->app['orm.em']->flush($Order);

                    // このタグを前後に分割し、間に項目を入れ込む
                    // 元の合計金額は書き込み済みのため再度書き込みを行う
                    $parts = $this->app->renderView('Coupon/View/discount_shopping_item.twig', array(
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

    /**
     * 合計金額がマイナスになっていた場合、値引き処理を元に戻す
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

                $this->app['orm.em']->flush($Order);

                $this->app->addError($this->app->trans('front.plugin.coupon.shopping.use.minus'), 'front.request');
            }
        }

    }


    /**
     * 解析用HTMLを取得
     *
     * @param Crawler $crawler
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
