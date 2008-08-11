<?php
/*
 * ZenMagick - Extensions for zen-cart
 * Copyright (C) 2006-2008 ZenMagick
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
 * Build the skelton of a new theme.
 *
 * @author mano
 * @package org.zenmagick.admin
 * @version $Id$
 */
class ZMThemeBuilder extends ZMObject {
    var $name_;
    var $inheritDefaults_;
    var $messages_;
    var $fsLog_;


    /**
     * Create new instance.
     */
    function __construct() {
        parent::__construct();

        $this->name_ = '';
        $this->inheritDefaults_ = true;
        $this->messages_ = array();
        $this->fsLog_ = array();
    }

    /**
     * Destruct instance.
     */
    function __destruct() {
        parent::__destruct();
    }


    /**
     * Get messages.
     *
     * @return array List of text messages.
     */
    function getMessages() {
        return $this->messages_;
    }


    /**
     * Set the name.
     *
     * @param string name The name.
     */
    function setName($name) { $this->name_ = $name; }

    /**
     * Get the name.
     *
     * @return string The name.
     */
    function getName() { return $this->name_; }

    /**
     * Set the inherit defaults flag.
     *
     * @param boolean inheritDefaults The value.
     */
    function setInheritDefaults($inheritDefaults) { $this->inheritDefaults_ = $inheritDefaults; }

    /**
     * Get the inherit defaults flag.
     *
     * @return boolean The value.
     */
    function getInheritDefaults() { return $this->inheritDefaults_; }

    /**
     * Build a theme.
     *
     * @return boolean <code>true</code> if successful, <code>false</code> if not.
     */
    function build() {
        if (empty($this->name_)) {
            array_push($this->messages_, 'Invalid theme name "' . $this->name_ . '".');
            return false;
        }

        if (!$this->_createFolder()) {
            return false;
        }

        if (!$this->_createThemeInfoClass()) {
            return false;
        }

        if (!$this->_createInheritDefaultsSetting()) {
            return false;
        }

        // try to set group/owner
        clearstatcache();
        $owner = fileowner(ZMRuntime::getZMRootPath().'init.php');
        $group = filegroup(ZMRuntime::getZMRootPath().'init.php');
        foreach (array_reverse($this->fsLog_) as $file) {
            @chgrp($file, $group);
            @chown($file, $owner);
        }

        array_push($this->messages_, 'Successfully created new theme "' . $this->name_ . '".');
        return true;
    }

    /**
     * Get the themes basedir.
     *
     * @return string The theme base directory.
     */
    function getBaseDir() {
        return ZMRuntime::getThemesDir() . $this->name_ . '/';
    }

    /**
     * Create all required folder.
     *
     * @return boolean <code>true</code> if successful, <code>false</code> if not.
     */
    function _createFolder() {
        $themeDir = $this->getBaseDir();
        if (file_exists($themeDir)) {
            array_push($this->messages_, 'Theme "' . $this->name_ . '" already exists!');
            return false;
        }

        // try base dir
        ZMTools::mkdir($themeDir, 755);
        $this->fsLog_[] = $themeDir;
        if (!file_exists($themeDir)) {
            array_push($this->messages_, 'Could not create theme dir "' . $themeDir . '".');
            return false;
        }

        // do the common ones
        ZMTools::mkdir($themeDir.ZM_THEME_CONTENT_DIR, 755);
        $this->fsLog_[] = $themeDir.ZM_THEME_CONTENT_DIR;
        ZMTools::mkdir($themeDir.ZM_THEME_EXTRA_DIR, 755);
        $this->fsLog_[] = $themeDir.ZM_THEME_EXTRA_DIR;
        ZMTools::mkdir($themeDir.ZM_THEME_CONTENT_DIR.'views/', 755);
        $this->fsLog_[] = $themeDir.ZM_THEME_CONTENT_DIR.'views/';
        ZMTools::mkdir($themeDir.ZM_THEME_BOXES_DIR, 755);
        $this->fsLog_[] = $themeDir.ZM_THEME_BOXES_DIR;
        ZMTools::mkdir($themeDir.ZM_THEME_LANG_DIR, 755);
        $this->fsLog_[] = $themeDir.ZM_THEME_LANG_DIR;

        return true;
    }

    /**
     * Create theme info class.
     *
     * @return boolean <code>true</code> if successful, <code>false</code> if not.
     */
    function _createThemeInfoClass() {
        $infoName = $this->name_ . ' ThemeInfo';
        $infoClass = ZMLoader::makeClassname($infoName);

        $infoClassFile = $this->getBaseDir() . $infoClass .  '.php';

        if (!$handle = fopen($infoClassFile, 'ab')) {
            array_push($this->messages, 'could not open theme info class for writing ' . $infoClassFile);
            return false;
        }

        $contents = '<?php

// theme info class generated by ZMThemeBuilder; edit at own risk
class '.$infoClass.' extends ZMThemeInfo {

    // c\'tor
    function __construct() {
        parent::__construct();

        $this->setName("'.$this->name_.'");
        $this->setVersion("0.1");
        $this->setAuthor("ZenMagick");
        $this->setDescription("'.$this->name_.' theme; generated by ZMThemeBuilder.");
    }
    
}

?>
';

        if (false === fwrite($handle, $contents)) {
            array_push($this->errors_, 'could not write to file ' . $infoClassFile);
            return;
        }
  
        fclose($handle);

        $this->fsLog_[] = $infoClassFile;

        return true;
    }

    /**
     * Handle inherit defaults setting.
     *
     * @return boolean <code>true</code> if successful, <code>false</code> if not.
     */
    function _createInheritDefaultsSetting() {
        if ($this->inheritDefaults_) {
            // nothing to do
            return true;
        }

        $themeDir = $this->getBaseDir();
        $localFile = $themeDir.ZM_THEME_EXTRA_DIR . 'local.php';

        if (!$handle = fopen($localFile, 'ab')) {
            array_push($this->messages, 'could not open theme local.php file for writing ' . $localFile);
            return false;
        }

        $contents = '<?php

    ZMSettings::set(\'isEnableThemeDefaults\', false);

?>
';

        if (false === fwrite($handle, $contents)) {
            array_push($this->errors_, 'could not write to file ' . $localFileinfoClassFile);
            return;
        }
  
        fclose($handle);

        $this->fsLog_[] = $localFile;

        return true;
    }

}

?>
