<?php
/*
 * This file is part of the Coupon plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Plugin\Coupon\Util\Version;

if (Version::isSupportLogFunction()) {
    return;
}

if (function_exists('log_emergency') === false) {
    /**
     * Log emergency.
     * Urgent alert. System is unusable.
     *
     * @param string $message
     * @param array  $context
     */
    function log_emergency($message, array $context = array())
    {
        if (isset($GLOBALS['eccube_logger'])) {
            $GLOBALS['eccube_logger']->emergency($message, $context);
        }
    }
}

if (function_exists('log_alert') === false) {
    /**
     * Log alert.
     * Action must be taken immediately.
     *
     * Entire website down, database unavailable, etc. This should trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     */
    function log_alert($message, array $context = array())
    {
        if (isset($GLOBALS['eccube_logger'])) {
            $GLOBALS['eccube_logger']->alert($message, $context);
        }
    }
}

if (function_exists('log_critical') === false) {
    /**
     * Log critical.
     * Critical conditions.
     *
     * Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     */
    function log_critical($message, array $context = array())
    {
        if (isset($GLOBALS['eccube_logger'])) {
            $GLOBALS['eccube_logger']->critical($message, $context);
        }
    }
}

if (function_exists('log_error') === false) {
    /**
     * Log error.
     * Runtime errors that do not require immediate action but should typically be logged and monitored.
     *
     * Error content at the time of occurrence Exception.
     *
     * @param string $message
     * @param array  $context
     */
    function log_error($message, array $context = array())
    {
        if (isset($GLOBALS['eccube_logger'])) {
            $GLOBALS['eccube_logger']->error($message, $context);
        }
    }
}

if (function_exists('log_warning') === false) {
    /**
     * Log warning.
     * Exceptional occurrences that are not errors.
     *
     * Use of deprecated APIs, poor use of an API, undesirable things that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     */
    function log_warning($message, array $context = array())
    {
        if (isset($GLOBALS['eccube_logger'])) {
            $GLOBALS['eccube_logger']->warning($message, $context);
        }
    }
}

if (function_exists('log_notice') === false) {
    /**
     * Log notice.
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     */
    function log_notice($message, array $context = array())
    {
        if (isset($GLOBALS['eccube_logger'])) {
            $GLOBALS['eccube_logger']->notice($message, $context);
        }
    }
}

if (function_exists('log_info') === false) {
    /**
     * Log info.
     * Interesting events.
     *
     * Logging to confirm the operation was performed.
     *
     * @param string $message
     * @param array  $context
     */
    function log_info($message, array $context = array())
    {
        if (isset($GLOBALS['eccube_logger'])) {
            $GLOBALS['eccube_logger']->info($message, $context);
        }
    }
}

if (function_exists('log_debug') === false) {
    /**
     * Log debug.
     * Detailed debug information.
     *
     * HTTP communication log.
     * Log you want to output at the time of development
     *
     * @param string $message
     * @param array  $context
     */
    function log_debug($message, array $context = array())
    {
        if (isset($GLOBALS['eccube_logger'])) {
            $GLOBALS['eccube_logger']->debug($message, $context);
        }
    }
}

if (function_exists('eccube_log_init') === false) {
    /**
     * Init log function for ec-cube <= 3.0.8.
     *
     * @param object $app
     */
    function eccube_log_init($app)
    {
        if (isset($GLOBALS['eccube_logger'])) {
            return;
        }

        $GLOBALS['eccube_logger'] = $app['monolog'];

        $app['eccube.monolog.factory'] = $app->protect(function ($config) use ($app) {
            return $app['monolog'];
        });
    }
}

// 3.0.9以上の場合は初期化処理を行う.
if (method_exists('Eccube\Application', 'getInstance') === true) {
    $app = \Eccube\Application::getInstance();
    eccube_log_init($app);
}
