<?php
/*
 * ZenMagick - Extensions for zen-cart
 * Copyright (C) 2006-2007 ZenMagick
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
 * $Id$
 */
?>
<?php

    error_reporting(E_ALL^E_NOTICE);
    ini_set("display_errors", true);
    ini_set("log_errors", true); 
    @ini_set("register_globals", 0);

    $_zm_1und1_error_logfile = dirname(__FILE__)."/error_log_1and1.php";
    if (file_exists($_zm_1und1_error_logfile)) {
        //include($_zm_1und1_error_logfile);
    }

    // ZenMagick bootstrap
    $_zm_bin_file = dirname(__FILE__)."/core.php";
    if (!IS_ADMIN_FLAG && file_exists($_zm_bin_file)) {
        require($_zm_bin_file);

        // configure core loader
        $zm_loader =& new ZMLoader('coreLoader');
    } else {
        $_zm_bin_dir = dirname(__FILE__)."/core/";
        require($_zm_bin_dir."bootstrap.php");
        require($_zm_bin_dir."settings/settings.php");
        require($_zm_bin_dir."settings/zenmagick.php");
        require($_zm_bin_dir."ZMLoader.php");
        require($_zm_bin_dir."ZMService.php");
        require($_zm_bin_dir."service/ZMThemes.php");
        require($_zm_bin_dir."rp/ZMUrlMapper.php");

        // configure core loader
        $zm_loader =& new ZMLoader('coreLoader');
        $zm_loader->addPath($_zm_bin_dir);
        // need to do this in global namespace
        foreach ($zm_loader->getStatic() as $static) {
            require_once($static);
        }
    }

    // use loader for all class loading from here?
    $zm_runtime = $zm_loader->create("ZMRuntime");

    // here the loader should take over...
    if (!defined('ZM_SINGLE_CORE')) {
        $includes = zm_find_includes($_zm_bin_dir, true);
        foreach ($includes as $include) {
            // exclude some stuff that gets loaded by the loader
            if ((false === strpos($include, '/controller/')
                && false === strpos($include, '/model/')
                && false === strpos($include, '/admin/')
                && false === strpos($include, '/settings/'))
                || (false !== strpos($include, '/admin/') && zm_setting('isAdmin'))) {
                require_once($include);
            }
        }
    }
    $zm_request = new ZMRequest();
    $zm_layout = new ZMLayout();

    // set up main class instances (aka the ZenMagick API)
    $zm_products = new ZMProducts();
    $zm_reviews = new ZMReviews();
    $zm_categories = new ZMCategories();
    $zm_features = new ZMFeatures();
    $zm_manufacturers = new ZMManufacturers();
    $zm_accounts = new ZMAccounts();
    $zm_currencies = new ZMCurrencies();
    $zm_addresses = new ZMAddresses();
    $zm_countries = new ZMCountries();
    $zm_orders = new ZMOrders();
    $zm_cart = new ZMShoppingCart();
    $zm_messages = new ZMMessages();
    $zm_pages = new ZMEZPages();
    $zm_coupons = new ZMCoupons();
    $zm_banners = new ZMBanners();
    $zm_languages = new ZMLanguages();
    $zm_music = new ZMMusic();
    $zm_mediaManager = new ZMMediaManager();
    $zm_validator = new ZMValidator();

    $zm_account = $zm_request->getAccount();

    // event proxy to simplify event subscription
    $zm_events = new ZMEvents();

    // these can be replaced by themes; will be reinitializes durin theme switching
    $zm_crumbtrail = $zm_loader->create('Crumbtrail');
    $zm_meta = $zm_loader->create('MetaTags');

    // global settings
    $_zm_local = $zm_runtime->getZMRootPath()."local.php";
    if (file_exists($_zm_local)) {
        include($_zm_local);
    }

    // set up *before* theme is resolved...
    $zm_urlMapper = new ZMUrlMapper();

    // load 
    if (zm_setting('isEnableZenMagick')) {
        $zm_theme = zm_resolve_theme(zm_setting('isEnableThemeDefaults') ? ZM_DEFAULT_THEME : $zm_runtime->getThemeId());
    } else {
        $zm_theme = $zm_runtime->getTheme();
    }
    $zm_themeInfo = $zm_theme->getThemeInfo();

    require(DIR_FS_CATALOG.'zenmagick/zc_fixes.php');

    // handle page caching
    if (zm_setting('isEnableZenMagick') && zm_setting('isPageCacheEnabled')) {
        $pageCache = $zm_runtime->getPageCache();
        if ($pageCache->isCacheable() && $contents = $pageCache->get()) {
            echo $contents;
            if (zm_setting('isDisplayTimerStats')) {
                $_zm_db = $zm_runtime->getDB();
                echo '<!-- stats: ' . round($_zm_db->queryTime(), 4) . ' sec. for ' . $_zm_db->queryCount() . ' queries; ';
                echo 'page: ' . zm_get_elapsed_time() . ' sec. -->';
            }
            require('includes/application_bottom.php');
            exit;
        }
    }

    if (zm_setting('isEnableZenMagick') && !zm_setting('isAdmin')) { ob_start(); }

    // upset plugins :)
    $zm_plugins =& new ZMPlugins();
    $pluginLoader =& new ZMLoader("pluginLoader");
    foreach ($zm_plugins->getPluginsForType('request') as $plugin) {
        if ($plugin->isInstalled() && $plugin->isEnabled()) {
            $plugin->init();
            $pluginLoader->addPath($plugin->getPluginDir());
            $pluginId = $plugin->getId();
            $$pluginId = $plugin;
        }
    }
    // plugins prevail over defaults, but not themes
    $rootLoader = zm_get_root_loader();
    $rootLoader->setParent($pluginLoader);

    // if GET or enabled && POST request set, fake directory to allow ZenMagick to handle the request and save time
    if (/*'GET' == $_SERVER['REQUEST_METHOD'] || **/
        ('POST' == $_SERVER['REQUEST_METHOD'] && zm_is_in_array($zm_request->getPageName(), zm_setting('postRequestEnabledList')))) {
        $code_page_directory = 'zenmagick';
    }

    $text = "
* abc
** xx1
### zzzz
### aahh
** xx2
* def

# foo
# bar

; foo : bar
; foo : bar
";
echo _replaceList($text);
	function _replaceList( &$text ) {
		//create <######>..</######> pseudo tags for string replacement, level 3 down to 1
		$text = preg_replace( '/\n([\*#;]{3,3})(.*?)\n(?![#\*;]{3,3})/si', "\n<\\1\\1l>\\1\\2</\\1\\1l>\n", $text );
		$text = preg_replace( '/\n([\*#;]{2,2})(?![\*#];)(.*?)\n(?!([#\*;]{2,3}|<[#\*;]{6,6}l>))/si', "\n<\\1\\1\\1l>\\1\\2</\\1\\1\\1l>\n", $text );
		$text = preg_replace( '/\n([\*#;])(?![\*#;])(.*?)\n(?!([#\*;]{1,3}|<[#\*;]{6,6}l>))/si', "\n<\\1\\1\\1\\1\\1\\1l>\\1\\2</\\1\\1\\1\\1\\1\\1l>\n", $text );
		//convert pseudo tags into HTML list tags
		$text = str_replace( array('######l>', '******l>', ';;;;;;l>'), array('ol>', 'ul>', 'dl>'), $text );
		//create list item tags <li> and <dt>,<dd>
		//$text = preg_replace( '/(\n<[uo]l>|\n)[\*#]{1,3} /si', '\1<li>', $text );
		$text = preg_replace( '/(\n<[uo]l>|\n)[\*#]{1,3}\s*([^\n<]*)/si', '\1<li>\2</li>', $text );
		$text = preg_replace( '/(\n<dl>|\n)[;]{1,3}\s*(\[{0,2})(.+?)(\]{0,2})\s*:\s*([^\n<]*)/si', '\1<dt><a name="\3">\2\3\4</a></dt><dd>\5</dd>', $text );
        return $text;
	}

?>
