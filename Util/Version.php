<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Coupon\Util;

use Eccube\Common\Constant;

/**
 * Class Version.
 * Util to check version.
 */
class Version
{
    /**
     * Check version to support get instance function. (monolog, new style, ...).
     *
     * @return bool
     */
    public static function isSupportGetInstanceFunction()
    {
        return version_compare(Constant::VERSION, '3.0.9', '>=');
    }

    /**
     * Check version to support new log function.
     *
     * @return bool
     */
    public static function isSupportLogFunction()
    {
        return version_compare(Constant::VERSION, '3.0.12', '>=');
    }

    /**
     * Check support in version Ec cube.
     *
     * @param string $version
     * @param string $operation
     *
     * @return bool
     */
    public static function isSupportNewHookPoint($version = '3.0.9', $operation = '>=')
    {
        return version_compare(Constant::VERSION, $version, $operation);
    }

    /**
     * Check support in version Ec cube.
     *
     * @param string $version
     * @param string $operation
     *
     * @return bool
     */
    public static function isSupportDisplayDiscount($version = '3.0.10', $operation = '>=')
    {
        return version_compare(Constant::VERSION, $version, $operation);
    }
}
