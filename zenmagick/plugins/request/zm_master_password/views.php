<?php
/*
 * ZenMagick - Extensions for zen-cart
 * Copyright (C) 2006,2007 ZenMagick
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
 *
 * @version $Id: views.php 426 2007-10-25 23:01:11Z DerManoMann $
 */
?>
<?php

    /**
     * Master password admin.
     *
     * @package org.zenmagick.plugins.zm_master_password
     */
    function &zm_master_password_admin() {
    global $zm_request, $zm_master_password;

        if ('POST' == $zm_request->getMethod()) {
            $values = $zm_request->getParameter('configuration', array());
            $masterPassword = $values['MASTERPASSWORD'];
            // allow to reset to blank
            if (!zm_is_empty($masterPassword)) {
                $masterPassword = zm_encrypt_password($masterPassword);
            }
            $zm_master_password->set('masterPassword', $masterPassword);
            zm_redirect(zm_plugin_admin_url());
        }

        //TODO: custom form; either single field to set or old, new, confirm to change
        $pluginPage = zm_simple_config_form($zm_master_password, 'zm_master_password_admin', 'Set Master Password', false);
        $contents = str_replace('type="text"', 'type="password"', $pluginPage->getContents());
        $pluginPage->setContents($contents);

        return $pluginPage;
    }

?>
