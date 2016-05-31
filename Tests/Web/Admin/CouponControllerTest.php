<?php
/**
 * Created by PhpStorm.
 * User: lqdung
 * Date: 5/23/2016
 * Time: 4:21 PM
 */

namespace Plugin\Coupon\Tests\Web\Admin;


use Eccube\Common\Constant;

class CouponControllerTest extends CouponCommon
{
    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_list'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = 'クーポン管理クーポン内容設定';
        $this->actual = $crawler->filter('h1.page-header')->text();

        $this->verify();
    }

    public function testCreateRender()
    {
        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_new'));
        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->expected = 'クーポン情報';
        $this->actual = $crawler->filter('.container-fluid .box-title')->text();

        $this->verify();
    }

    public function testEditRender_IdIncorrect()
    {
        $this->client->request('GET', $this->app->url('admin_coupon_edit', array('id' => 999999)));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_coupon_list')));
    }

    public function testEditRender()
    {
        $Coupon = $this->createCouponDetail();
        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_edit', array('id' => $Coupon->getId())));

        $this->assertTrue($this->client->getResponse()->isSuccessful());

        $this->actual = true;
        $this->expected = 'クーポン情報';
        $this->actual = $crawler->filter('.container-fluid .box-title')->text();

        $this->verify();
    }

    public function testCommit_Render()
    {
        $Coupon = $this->createCouponDetail();
        $this->client->request('GET', $this->app->url('admin_coupon_commit', array('id'=> $Coupon->getId())));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    public function testCommit_Create()
    {
        $fake = $this->getFaker();
        $couponCd = $this->app['eccube.plugin.coupon.service.coupon']->generateCouponCd();
        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_new'));
        $form = $this->getForm($crawler, $couponCd);
        $current = new \DateTime();
        $form['admin_coupon[coupon_name]'] = $fake->word;
        $form['admin_coupon[coupon_type]'] = 1;
        $form['admin_coupon[discount_type]'] = 1;
        $form['admin_coupon[discount_price]'] = 10;
        $form['admin_coupon[coupon_use_time]'] = 10;
        $form['admin_coupon[available_from_date]'] = $current->setDate(2016,1,1)->format('Y-m-d');
        $form['admin_coupon[available_to_date]'] = $current->format('Y-m-d');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_coupon_list')));
    }

    public function testCommit_Edit_IdIncorrect()
    {
        $this->client->request('POST', $this->app->url('admin_coupon_commit', array('id'=> 999999)));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_coupon_list')));
    }

    public function testCommit_Edit()
    {
        $Coupon = $this->createCouponDetail();
        $couponCd = $this->app['eccube.plugin.coupon.service.coupon']->generateCouponCd();
        $crawler = $this->client->request('GET', $this->app->url('admin_coupon_edit', array('id'=> $Coupon->getId())));
        $form = $this->getForm($crawler, $couponCd);

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_coupon_list')));

        $this->expected = $couponCd;
        $this->actual = $Coupon->getCouponCd();
        $this->verify();
    }

    public function testEnable()
    {
        $Coupon = $this->createCouponDetail();
        $status_old = $Coupon->getEnableFlag();
        $this->client->request('GET', $this->app->url('admin_coupon_enable', array('id'=> $Coupon->getId())));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_coupon_list')));

        $this->actual = $Coupon->getEnableFlag();
        $this->expected = ($status_old == 0) ? 1 : 0;
        $this->verify();
    }

    public function testEnable_IdIncorrect()
    {
        $this->client->request('GET', $this->app->url('admin_coupon_enable', array('id'=> 999999)));

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_coupon_list')));
    }

    public function testDelete()
    {
        $Coupon = $this->createCouponDetail();
        $form =  array(
            '_token' => 'dummy',
        );
        $this->client->request('POST',
            $this->app->url('admin_coupon_delete', array('id'=> $Coupon->getId())),
            array('admin_coupon_search' => $form)
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_coupon_list')));

        $this->actual = $Coupon->getDelFlg();
        $this->expected = Constant::ENABLED;
        $this->verify();

        foreach ($Coupon->getCouponDetails() as $detail) {
            $this->actual = $detail->getDelFlg();
            $this->verify();
        }
    }

    public function testDelete_IdIncorrect()
    {
        $Coupon = $this->createCouponDetail();
        $form =  array(
            '_token' => 'dummy',
        );
        $this->client->request('POST',
            $this->app->url('admin_coupon_delete', array('id'=> 999999)),
            array('admin_coupon_search' => $form)
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_coupon_list')));

        $this->actual = $Coupon->getDelFlg();
        $this->expected = Constant::DISABLED;
        $this->verify();
    }
}