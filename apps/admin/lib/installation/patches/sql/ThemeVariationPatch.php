<?php
/*
 * ZenMagick - Smart e-commerce
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
namespace zenmagick\apps\store\admin\installation\patches\sql;

use zenmagick\base\Runtime;
use zenmagick\apps\store\admin\installation\patches\SQLPatch;


/**
 * Patch to create the theme variation column.
 *
 * @author DerManoMann <mano@zenmagick.org>
 */
class ThemeVariationPatch extends SQLPatch {
    var $sqlFiles_ = array(
        "/shared/etc/sql/mysql/theme_chaining_install.sql"
    );
    var $sqlUndoFiles_ = array(
        "/shared/etc/sql/mysql/theme_chaining_uninstall.sql"
    );


    /**
     * Create new instance.
     */
    public function __construct() {
        parent::__construct('sqlThemeVariation');
        $this->label_ = 'Create additional column for theme variation selection';
    }


    /**
     * Checks if this patch can still be applied.
     *
     * @return boolean <code>true</code> if this patch can still be applied.
     */
    function isOpen() {
        $meta = \ZMRuntime::getDatabase()->getMetaData('template_select');
        return !array_key_exists('variation_dir', $meta);
    }

    /**
     * Execute this patch.
     *
     * @param boolean force If set to <code>true</code> it will force patching even if
     *  disabled as per settings.
     * @return boolean <code>true</code> if patching was successful, <code>false</code> if not.
     */
    function patch($force=false) {
        $baseDir = Runtime::getInstallationPath();
        // do only interactive
        if ($force) {
            $status = true;
            foreach ($this->sqlFiles_ as $file) {
                $sql = file($baseDir.$file);
                $status |= $this->_runSQL($sql);
            }
            return $status;
        }

        return true;
    }

    /**
     * Revert the patch.
     *
     * @return boolean <code>true</code> if patching was successful, <code>false</code> if not.
     */
    function undo() {
        if ($this->isOpen()) {
            return true;
        }

        $baseDir = Runtime::getInstallationPath();
        $status = true;
        foreach ($this->sqlUndoFiles_ as $file) {
            $sql = file($baseDir.$file);
            $status |= $this->_runSQL($sql);
        }
        return $status;
    }

}
