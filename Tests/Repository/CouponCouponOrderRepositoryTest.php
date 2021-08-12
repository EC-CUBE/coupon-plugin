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

namespace Plugin\Coupon4\Tests\Repository;

use Eccube\Entity\Customer;
use Eccube\Entity\OrderItem;
use Eccube\Entity\TaxRule;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Tests\EccubeTestCase;
use Eccube\Util\StringUtil;
use Plugin\Coupon4\Entity\Coupon;
use Plugin\Coupon4\Entity\CouponDetail;
use Plugin\Coupon4\Entity\CouponOrder;
use Plugin\Coupon4\Repository\CouponOrderRepository;
use Plugin\Coupon4\Repository\CouponRepository;

/**
 * Class CouponCouponOrderRepositoryTest.
 */
class CouponCouponOrderRepositoryTest extends EccubeTestCase
{
    /**
     * @var Customer
     */
    private $Customer;

    /**
     * @var CouponOrderRepository
     */
    private $couponOrderRepository;

    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * @var TaxRuleRepository
     */
    private $taxRuleRepository;

    public function setUp()
    {
        parent::setUp();
        $this->Customer = $this->createCustomer('aaa@example.com');
        $this->couponOrderRepository = $this->entityManager->getRepository(CouponOrder::class);
        $this->couponRepository = $this->entityManager->getRepository(Coupon::class);
        $this->taxRuleRepository = $this->entityManager->getRepository(TaxRule::class);
//        $this->deleteAllRows(array(''));
    }

    /**
     * testSave.
     */
    public function testSave()
    {
        $Coupon = $this->getCoupon();
        $discount = 200;
        $preOrderId = sha1(StringUtil::random(32));
        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);
        $this->couponOrderRepository->save($CouponOrder);

        $CouponOrder1 = $this->couponOrderRepository->findOneBy(['pre_order_id' => $preOrderId]);

        $this->actual = $CouponOrder1->getDiscount();
        $this->expected = $discount;
        $this->verify();
    }

    /**
     * testFindUseCouponNonMember.
     */
    public function testFindUseCouponNonMember()
    {
        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(StringUtil::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);

        $CouponOrder->setEmail($this->Customer->getEmail());
        $CouponOrder->setOrderDate(new \DateTime());

        $this->couponOrderRepository->save($CouponOrder);

        $CouponOrder1 = $this->couponOrderRepository->findUseCoupon($Coupon->getCouponCd(), $this->Customer->getEmail());

        $this->actual = $CouponOrder1[0]->getDiscount();

        $this->expected = $discount;

        $this->verify();
    }

    public function testGetCouponOrder()
    {
        $Coupon = $this->getCoupon();

        $discount = 200;
        $preOrderId = sha1(StringUtil::random(32));

        $CouponOrder = $this->getCouponOrder($Coupon, $discount, $preOrderId);
        $this->couponOrderRepository->save($CouponOrder);

        $CouponOrder1 = $this->couponOrderRepository->getCouponOrder($preOrderId);

        $this->actual = $CouponOrder1->getDiscount();
        $this->expected = $discount;
        $this->verify();
    }

    /**
     * getCouponOrder.
     *
     * @param Coupon $Coupon
     * @param $discount
     * @param $preOrderId
     *
     * @return CouponOrder
     */
    private function getCouponOrder(Coupon $Coupon, $discount, $preOrderId)
    {
        $Order = $this->createOrder($this->Customer);

        $Order->setPreOrderId($preOrderId);

        $orderItem = new OrderItem();
        $TaxRule = $this->taxRuleRepository->getByRule();
        $orderItem
            ->setProductName('discount')
            ->setPrice((0 - $discount))
            ->setQuantity(1)
            ->setTaxRuleId($TaxRule->getId())
            ->setTaxRate($TaxRule->getTaxRate());
        $this->entityManager->persist($orderItem);
        $this->entityManager->flush($orderItem);
        $orderItem->setOrder($Order);
        $Order->addItem($orderItem);

        $CouponOrder = new CouponOrder();
        $CouponOrder->setVisible(false);
        $CouponOrder->setDiscount($discount);
        $CouponOrder->setUserId($this->Customer->getId());
        $CouponOrder->setCouponId($Coupon->getId());
        $CouponOrder->setOrderChangeStatus(false);
        $CouponOrder->setOrderId($Order->getId());
        $CouponOrder->setPreOrderId($Order->getPreOrderId());
        $CouponOrder->setCouponCd($Coupon->getCouponCd());
        $CouponOrder->setOrderItemId($orderItem->getId());

        return $CouponOrder;
    }

    /**
     * getCoupon.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    protected function getCoupon($couponType = 1)
    {
        $this->getTestData($couponType);

        /** @var Coupon $Coupon */
        $Coupon = $this->couponRepository->findOneBy(['coupon_cd' => 'aaaaaaaa']);

        $Product = $this->createProduct();
        $CouponDetail = new CouponDetail();

        $CouponDetail->setCoupon($Coupon);
        $CouponDetail->setCouponType($Coupon->getCouponType());
        $CouponDetail->setUpdateDate($Coupon->getUpdateDate());
        $CouponDetail->setCreateDate($Coupon->getCreateDate());
        $CouponDetail->setVisible(true);

        $Categories = $Product->getProductCategories();

        /** @var \Eccube\Entity\ProductCategory $Category */
        $ProductCategory = $Categories[0];

        $CouponDetail->setCategory($ProductCategory->getCategory());

        $CouponDetail->setProduct($Product);

        $Coupon->addCouponDetail($CouponDetail);

        return $Coupon;
    }

    /**
     * getTestData.
     *
     * @param int $couponType
     *
     * @return Coupon
     */
    protected function getTestData($couponType = 1)
    {
        $Coupon = new Coupon();

        $date1 = new \DateTime();
        $date2 = new \DateTime();

        $Coupon->setCouponCd('aaaaaaaa');
        $Coupon->setCouponType($couponType);
        $Coupon->setCouponName('クーポン');
        $Coupon->setDiscountType(1);
        $Coupon->setCouponRelease(100);
        $Coupon->setCouponUseTime(100);
        $Coupon->setDiscountPrice(100);
        $Coupon->setDiscountRate(10);
        $Coupon->setCouponLowerLimit(100);
        $Coupon->setCouponMember(0);
        $Coupon->setEnableFlag(1);
        $Coupon->setVisible(false);
        $d1 = $date1->setDate(2016, 1, 1);
        $Coupon->setAvailableFromDate($d1);
        $d2 = $date2->setDate(2040, 12, 31);
        $Coupon->setAvailableToDate($d2);

        // クーポン情報を登録する
        $this->entityManager->persist($Coupon);
        $this->entityManager->flush($Coupon);

        return $Coupon;
    }
}
