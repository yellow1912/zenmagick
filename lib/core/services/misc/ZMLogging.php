<?php
/*
 * ZenMagick - Another PHP framework.
 * Copyright (C) 2006-2010 zenmagick.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
?>
<?php


/**
 * ZenMagick logging service.
 *
 * <p>Degraded to a compatibility/convenience wrapper around<code>\zenmagick\base\logging\Logging</code>.</p>
 *
 * @author DerManoMann
 * @package org.zenmagick.core.services.misc
 */
class ZMLogging extends ZMObject {
    /** Log level: Disabled. */
    const NONE = \zenmagick\base\logging\Logging::NONE;
    /** Log level: Error. */
    const ERROR = \zenmagick\base\logging\Logging::ERROR;
    /** Log level: Warning. */
    const WARN = \zenmagick\base\logging\Logging::WARN;
    /** Log level: Info. */
    const INFO = \zenmagick\base\logging\Logging::INFO;
    /** Log level: Debug. */
    const DEBUG = \zenmagick\base\logging\Logging::DEBUG;
    /** Log level: Trace. */
    const TRACE = \zenmagick\base\logging\Logging::TRACE;
    /** Log level: ALL. */
    const ALL = \zenmagick\base\logging\Logging::ALL;


    /**
     * Get instance.
     */
    public static function instance() {
        return ZMRuntime::singleton('ZMLogging');
    }


    /**
     * Log info.
     *
     * @param string msg The message to log.
     */
    public function info($msg) {
        \zenmagick\base\logging\Logging::instance()->log($msg, self::INFO);
    }

    /**
     * Log warning.
     *
     * @param string msg The message to log.
     */
    public function warn($msg) {
        \zenmagick\base\logging\Logging::instance()->log($msg, self::WARN);
    }

    /**
     * Log error.
     *
     * @param string msg The message to log.
     */
    public function error($msg) {
        \zenmagick\base\logging\Logging::instance()->log($msg, self::ERROR);
    }

    /**
     * Log debug.
     *
     * @param string msg The message to log.
     */
    public function debug($msg) {
        \zenmagick\base\logging\Logging::instance()->log($msg, self::DEBUG);
    }

    /**
     * Simple logging function.
     *
     * <p>Messages will either be appended to the webserver's error log or, if a custom
     * error handler is installed, trigger a <em>E_USER_NOTICE</em> error.</p>
     *
     * @param string msg The message to log.
     * @param int level Optional level; default: <code>ZMLogging::INFO</code>.
     */
    public function log($msg, $level=self::INFO) {
        \zenmagick\base\logging\Logging::instance()->log($msg, $level);
    }

    /**
     * Simple dump function.
     *
     * @param mixed obj The object to dump.
     * @param string msg An optional message.
     * @param int level Optional level; default: <code>ZMLogging::DEBUG</code>.
     */
    public function dump($obj, $msg=null, $level=self::DEBUG) {
        \zenmagick\base\logging\Logging::instance()->dump($obj, $msg, $level);
    }

    /**
     * Create a simple stack trace.
     *
     * @param mixed msg An optional string or array.
     * @param int level Optional level; default: <code>ZMLogging::DEBUG</code>.
     */
    public function trace($msg=null, $level=self::DEBUG) {
        \zenmagick\base\logging\Logging::instance()->trace($msg, $level);
    }

    /**
     * A callback function that can be overriden to implement custom logging.
     *
     * @param string line The pre-fromatted log line.
     * @param array info All available log information.
     */
    public function logError($line, $info) {
        \zenmagick\base\logging\Logging::instance()->logError($line, $info);
    }

}
