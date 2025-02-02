<?php
/*
 * ZenMagick - Another PHP framework.
 * Copyright (C) 2006-2012 zenmagick.org
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
namespace zenmagick\http\session\validation;

use ZMRequest;
use zenmagick\base\ZMObject;
use zenmagick\http\session\Session;
use zenmagick\http\session\SessionValidator;

/**
 * IP validator.
 *
 * @author DerManoMann <mano@zenmagick.org>
 */
class IPSessionValidator extends ZMObject implements SessionValidator {
    /**
     * Name of the user agent session key.
     */
    const SESSION_IP_KEY = 'ip';

    private $enabled = false;

    /**
     * Enable/disable validation.
     *
     * @param boolean state The new state.
     */
    public function setEnabled($state) {
        $this->enabled = $state;
    }

    /**
     * {@inheritDoc}
     */
    public function isValidSession(ZMRequest $request, Session $session) {
        $valid = true;
        if ($this->enabled) {
            $ip = $request->getClientIp();
            if (null == ($sessionIP = $session->getValue(self::SESSION_IP_KEY, self::SESSION_VALIDATOR_NAMESPACE))) {
                $session->setValue(self::SESSION_IP_KEY, $ip, self::SESSION_VALIDATOR_NAMESPACE);
            } else {
                $valid = $ip == $sessionIP;
            }
        }
        return $valid;
    }

}
