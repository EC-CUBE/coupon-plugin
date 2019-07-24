<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Repository;

use Doctrine\ORM\EntityRepository;
use Plugin\Coupon\Entity\CouponOrder;
use Doctrine\Orm\NoResultException;

/**
 * CouponOrderRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CouponOrderRepository extends EntityRepository
{
    /**
     * クーポン受注情報を保存する.
     *
     * @param CouponOrder $CouponOrder
     */
    public function save(CouponOrder $CouponOrder)
    {
        $em = $this->getEntityManager();
        $em->persist($CouponOrder);
        $em->flush($CouponOrder);
    }

    /**
     * 受注ID(order_id)から使用されたクーポン受注情報を取得する.
     *
     * @param string $orderId
     *
     * @return mixed|null
     *
     * @throws NoResultException
     */
    public function findUseCouponByOrderId($orderId)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c')
            ->andWhere('c.del_flg = 0')
            ->andWhere('c.order_date IS NOT NULL')
            ->andWhere('c.coupon_id IS NOT NULL')
            ->andWhere('c.order_id = :order_id')
            ->setParameter('order_id', $orderId);
        $query = $qb->getQuery();

        $result = null;
        try {
            $result = $query->getSingleResult();
        } catch (NoResultException $e) {
            $result = null;
        }

        return $result;
    }

    /**
     * 会員または非会員が既にクーポンを利用しているか検索
     * 会員の場合、会員IDで非会員の場合、メールアドレスで検索.
     *
     * @param string $couponCd
     * @param string $param
     *
     * @return array
     */
    public function findUseCoupon($couponCd, $param)
    {
        $userId = null;
        $email = null;

        if (is_numeric($param)) {
            $userId = $param;
        } else {
            $email = $param;
        }

        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.coupon_cd = :coupon_cd')
            ->andWhere('c.order_date IS NOT NULL')
            ->andWhere('c.user_id = :user_id OR c.email = :email')
            ->setParameter('coupon_cd', $couponCd)
            ->setParameter('user_id', $userId)
            ->setParameter('email', $email);
        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    /**
     * 会員または非会員が既にクーポンを利用しているか検索
     * 会員の場合、会員IDで非会員の場合、メールアドレスで検索.
     *
     * @param string $couponCd
     * @param string $orderId
     * @param string $param
     *
     * @return array
     */
    public function findUseCouponBefore($couponCd, $orderId, $param)
    {
        $userId = null;
        $email = null;

        if (is_numeric($param)) {
            $userId = $param;
        } else {
            $email = $param;
        }

        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.coupon_cd = :coupon_cd')
            ->andWhere('c.order_id != :order_id')
            ->andWhere('c.user_id = :user_id OR c.email = :email')
            ->setParameter('coupon_cd', $couponCd)
            ->setParameter('order_id', $orderId)
            ->setParameter('user_id', $userId)
            ->setParameter('email', $email);
        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    /**
     * クーポンの発行枚数を検索.
     *
     * @param string $couponCd
     *
     * @return int|mixed
     *
     * @throws NoResultException
     */
    public function countCouponByCd($couponCd)
    {
        $qb = $this->createQueryBuilder('c')
            ->select('count(c.coupon_cd)')
            ->andWhere('c.del_flg = 0')
            ->andWhere('c.coupon_cd = :coupon_cd')
            ->andWhere('c.order_date IS NOT NULL')
            ->setParameter('coupon_cd', $couponCd);

        $query = $qb->getQuery();
        try {
            $count = $query->getSingleResult();
        } catch (NoResultException $e) {
            $count = 0;
        }

        return $count;
    }
}
