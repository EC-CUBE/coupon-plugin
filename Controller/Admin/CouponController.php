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

namespace Plugin\Coupon4\Controller\Admin;

use Eccube\Common\Constant;
use Eccube\Form\Type\Admin\SearchProductType;
use Plugin\Coupon4\Entity\Coupon;
use Plugin\Coupon4\Entity\CouponDetail;
use Plugin\Coupon4\Form\Type\CouponSearchCategoryType;
use Plugin\Coupon4\Form\Type\CouponType;
use Plugin\Coupon4\Repository\CouponDetailRepository;
use Plugin\Coupon4\Repository\CouponRepository;
use Plugin\Coupon4\Service\CouponService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Controller\AbstractController;

/**
 * Class CouponController
 */
class CouponController extends AbstractController
{
    /**
     * @var CouponRepository
     */
    private $couponRepository;

    /**
     * @var CouponService
     */
    private $couponService;

    /**
     * @var CouponDetailRepository
     */
    private $couponDetailRepository;

    /**
     * CouponController constructor.
     *
     * @param CouponRepository $couponRepository
     * @param CouponService $couponService
     * @param CouponDetailRepository $couponDetailRepository
     */
    public function __construct(CouponRepository $couponRepository, CouponService $couponService, CouponDetailRepository $couponDetailRepository)
    {
        $this->couponRepository = $couponRepository;
        $this->couponService = $couponService;
        $this->couponDetailRepository = $couponDetailRepository;
    }

    /**
     * @param Request $request
     *
     * @return array
     * @Route("/%eccube_admin_route%/plugin/coupon", name="plugin_coupon_list")
     * @Template("@Coupon4/admin/index.twig")
     */
    public function index(Request $request)
    {
        $coupons = $this->couponRepository->findBy(
            ['visible' => true],
            ['id' => 'DESC']
        );

        return [
            'Coupons' => $coupons,
        ];
    }

    /**
     * クーポンの新規作成/編集確定.
     *
     * @param Request $request
     * @param int     $id
     *
     * @return RedirectResponse|Response
     * @Route("/%eccube_admin_route%/plugin/coupon/new", name="plugin_coupon_new", requirements={"id" = "\d+"})
     * @Route("/%eccube_admin_route%/plugin/coupon/{id}/edit", name="plugin_coupon_edit", requirements={"id" = "\d+"})
     */
    public function edit(Request $request, $id = null)
    {
        $Coupon = null;
        if (!$id) {
            // 新規登録
            $Coupon = new Coupon();
            $Coupon->setEnableFlag(Constant::ENABLED);
            $Coupon->setVisible(true);
        } else {
            // 更新
            $Coupon = $this->couponRepository->find($id);
            if (!$Coupon) {
                $this->addError('plugin_coupon.admin.notfound', 'admin');

                return $this->redirectToRoute('plugin_coupon_list');
            }
        }

        $form = $this->formFactory->createBuilder(CouponType::class, $Coupon)->getForm();
        // クーポンコードの発行
        if (!$id) {
            $form->get('coupon_cd')->setData($this->couponService->generateCouponCd());
        }
        $details = [];
        $CouponDetails = $Coupon->getCouponDetails();
        foreach ($CouponDetails as $CouponDetail) {
            $details[] = clone $CouponDetail;
            $CouponDetail->getCategoryFullName();
        }
        $form->get('CouponDetails')->setData($details);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Plugin\Coupon4\Entity\Coupon $Coupon */
            $Coupon = $form->getData();
            $oldReleaseNumber = $request->get('coupon_release_old');
            if (is_null($Coupon->getCouponUseTime())) {
                $Coupon->setCouponUseTime($Coupon->getCouponRelease());
            } else {
                if ($Coupon->getCouponRelease() != $oldReleaseNumber) {
                    $Coupon->setCouponUseTime($Coupon->getCouponRelease());
                }
            }

            $CouponDetails = $this->couponDetailRepository->findBy([
                'Coupon' => $Coupon,
            ]);
            foreach ($CouponDetails as $CouponDetail) {
                $Coupon->removeCouponDetail($CouponDetail);
                $this->entityManager->remove($CouponDetail);
                $this->entityManager->flush($CouponDetail);
            }
            $CouponDetails = $form->get('CouponDetails')->getData();
            /** @var CouponDetail $CouponDetail */
            foreach ($CouponDetails as $CouponDetail) {
                $CouponDetail->setCoupon($Coupon);
                $CouponDetail->setCouponType($Coupon->getCouponType());
                $CouponDetail->setVisible(false);
                $Coupon->addCouponDetail($CouponDetail);
                $this->entityManager->persist($CouponDetail);
            }
            $this->entityManager->persist($Coupon);
            $this->entityManager->flush($Coupon);
            // 成功時のメッセージを登録する
            $this->addSuccess('plugin_coupon.admin.regist.success', 'admin');

            return $this->redirectToRoute('plugin_coupon_list');
        }

        return $this->renderRegistView([
            'form' => $form->createView(),
            'id' => $id,
        ]);
    }

    /**
     * クーポンの有効/無効化.
     *
     * @param Request $request
     * @param Coupon  $Coupon
     *
     * @return RedirectResponse
     * @Route("/%eccube_admin_route%/plugin/coupon/{id}/enable", name="plugin_coupon_enable", requirements={"id" = "\d+"}, methods={"put"})
     * @ParamConverter("Coupon")
     */
    public function enable(Request $request, Coupon $Coupon)
    {
        $this->isTokenValid();
        $this->couponRepository->enableCoupon($Coupon);
        $this->addSuccess('plugin_coupon.admin.enable.success', 'admin');
        log_info('Change status a coupon with ', ['ID' => $Coupon->getId()]);

        return $this->redirectToRoute('plugin_coupon_list');
    }

    /**
     * クーポンの削除.
     *
     * @param Request $request
     * @param Coupon  $Coupon
     *
     * @return RedirectResponse
     * @Route("/%eccube_admin_route%/plugin/coupon/{id}/delete", name="plugin_coupon_delete", requirements={"id" = "\d+"}, methods={"delete"})
     * @ParamConverter("Coupon")
     */
    public function delete(Request $request, Coupon $Coupon)
    {
        $this->isTokenValid();
        $this->couponRepository->deleteCoupon($Coupon);
        $this->addSuccess('plugin_coupon.admin.delete.success', 'admin');
        log_info('Delete a coupon with ', ['ID' => $Coupon->getId()]);

        return $this->redirectToRoute('plugin_coupon_list');
    }

    /**
     * 編集画面用のrender.
     *
     * @param array       $parameters
     *
     * @return Response
     */
    protected function renderRegistView($parameters = [])
    {
        // 商品検索フォーム
        $searchProductModalForm = $this->formFactory->createBuilder(SearchProductType::class)->getForm();
        // カテゴリ検索フォーム
        $searchCategoryModalForm = $this->formFactory->createBuilder(CouponSearchCategoryType::class)->getForm();
        $viewParameters = [
            'searchProductModalForm' => $searchProductModalForm->createView(),
            'searchCategoryModalForm' => $searchCategoryModalForm->createView(),
        ];
        $viewParameters += $parameters;

        return $this->render('@Coupon4/admin/regist.twig', $viewParameters);
    }
}
