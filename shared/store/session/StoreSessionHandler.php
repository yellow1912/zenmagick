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
namespace zenmagick\apps\store\session;

use ZMRuntime;
use zenmagick\http\session\SessionHandler;

/**
 * Simple session handler interface.
 *
 * @author DerManoMann
 */
class StoreSessionHandler implements SessionHandler {
    private $expiryTime_ = 1440;


    /**
     * {@inheritDoc}
     */
    public function open($path, $name) {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function read($id) {
        $sql = "SELECT value
                FROM %table.sessions%
                WHERE sesskey = :sesskey
                AND expiry > :expiry";
        if (null !== ($result = ZMRuntime::getDatabase()->querySingle($sql, array('sesskey' => $id, 'expiry' => time()), 'sessions'))) {
            return $result['value'];
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function write($id, $data) {
        // check for existing row
        $sql = "SELECT value
                FROM %table.sessions%
                WHERE sesskey = :sesskey";
        if (null !== ($result = ZMRuntime::getDatabase()->querySingle($sql, array('sesskey' => $id), 'sessions'))) {
            // update
            $sql = "UPDATE %table.sessions%
                    SET expiry = :expiry, value = :value
                    WHERE sesskey = :sesskey";
        } else {
            // create
            $sql = "INSERT INTO %table.sessions%
                    VALUES (:sesskey, :expiry, :value)";
        }

        $args = array('sesskey' => $id, 'value' => $data, 'expiry' => time() + $this->expiryTime_);
        return ZMRuntime::getDatabase()->updateObj($sql, $args, 'sessions');
    }

    /**
     * {@inheritDoc}
     */
    public function destroy($id) {
        $sql = "DELETE FROM %table.sessions% WHERE sesskey = :sesskey";
        return ZMRuntime::getDatabase()->updateObj($sql, array('sesskey' => $id), 'sessions');
    }

    /**
     * {@inheritDoc}
     */
    public function gc($lifetime) {
        $sql = "DELETE FROM %table.sessions% where expiry < :expiry";
        return ZMRuntime::getDatabase()->updateObj($sql, array('expiry' => time()), 'sessions');
    }

    /**
     * {@inheritDoc}
     */
    public function close() {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function setExpiryTime($expiryTime) {
        $this->expiryTime_ = $expiryTime;
    }

}
