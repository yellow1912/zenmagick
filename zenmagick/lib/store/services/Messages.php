<?php
/*
 * ZenMagick - Extensions for zen-cart
 * Copyright (C) 2006-2009 ZenMagick
 *
 * Portions Copyright (c) 2003 The zen-cart developers
 * Portions Copyright (c) 2003 osCommerce
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
 * Messages to be displayed to the user.
 *
 * <p>Messages will be saved in the session if not delivered.</p>
 *
 * <p>All known zen cart query message types stored are supported.</p>
 *
 * @author DerManoMann
 * @package org.zenmagick.store.services
 * @version $Id$
 */
class Messages extends ZMMessages {

    /**
     * Create new instance.
     */
    function __construct() {
    global $messageStack;

        parent::__construct();
        $this->_loadMessageStack();
    }

    /**
     * Destruct instance.
     */
    function __destruct() {
        parent::__destruct();
    }


    /**
     * Load messages from zen-cart message stack.
     */
    function _loadMessageStack() {
    global $messageStack;

        $session = ZMRequest::instance()->getSession();

        // add messages generated by zen-cart so far
        if (isset($messageStack) && isset($messageStack->messages)) {
            foreach ($messageStack->messages as $zenMessage) {
                $pos = strpos($zenMessage['text'], "/>");
                $text = substr($zenMessage['text'], $pos+2);
                $this->add($text, 
                  (false === strpos($zenMessage['params'], 'Error') 
                    ? (false === strpos($zenMessage['params'], 'Success') ? ZMMessages::T_WARN : ZMMessages::T_MESSAGE) : ZMMessages::T_ERROR));
            }
        } else {
            // look for session messages
            $this->addAll($session->getMessages());
        }

        // also check for messages in the request...
        if (null != ($error = ZMRequest::instance()->getParameter('error_message'))) {
            $this->error($error);
        }
        if (null != ($error = ZMRequest::instance()->getParameter('credit_class_error'))) {
            $this->error($error);
        }
        if (null != ($info = ZMRequest::instance()->getParameter('info_message'))) {
            $this->info($info);
        }

        $session->clearMessages();
    }

}

?>
