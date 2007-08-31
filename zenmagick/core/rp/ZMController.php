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
 */
?>
<?php


/**
 * Request controller base class.
 *
 * @author mano
 * @package net.radebatz.zenmagick.rp
 * @version $Id$
 */
class ZMController extends ZMObject {
    var $id_;
    var $globals_;
    var $view_;


    /**
     * Default c'tor.
     */
    function ZMController() {
    global $zm_request;

        parent::__construct();

        $this->globals_ = array();
        // use as controller id
        $this->id_ = $zm_request->getPageName();

        foreach ($GLOBALS as $name => $instance) {
            if (zm_starts_with($name, "zm_")) {
                if (is_object($instance)) {
                    $this->exportGlobal($name, $GLOBALS[$name]);
                }
            }
        }
    }

    /**
     * Default c'tor.
     */
    function __construct() {
        $this->ZMController();
    }

    /**
     * Default d'tor.
     */
    function __destruct() {
        parent::__destruct();
    }


    /**
     * Checks if the current request can be handled by the controller or not.
     *
     * <p>This is a temp. method that wil be obsolete once all views are implemented
     * in the default theme (is that now??).</p>
     *
     * @return boolean <code>true</code> if the controller can handle the request, <code>false</code> if not.
     */
    function validateRequest() {
    global $zm_runtime;

        $theme = $zm_runtime->getTheme();

        return is_subclass_of($this, "ZMController") || $theme->isValidRequest();
    }


    /**
     * Process a HTTP request.
     *
     * <p>Supported request methods are <code>GET</code> and <code>POST</code>.</p>
     *
     * @return ZMView A <code>ZMView</code> instance or <code>null</code>.
     */
    function process() { 
    global $zm_sacsMapper, $zm_events;

        $zm_sacsMapper->ensureAuthorization($this->id_);

        $zm_events->fireEvent($this, ZM_EVENT_CONTROLLER_PROCESS_START);

        $view = null;
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $view = $this->processGet();
                break;
            case 'POST':
                $view = $this->processPost();
                break;
            default:
                die('Unsupported request method: ' . $_SERVER['REQUEST_METHOD']);
        }

        if (null != $view) {
            if (!$view->isValid()) {
                zm_log('Invalid view: '.$view->getName(), ZM_LOG_WARN);
                $view = $this->findView(zm_setting('missingPageId'));
            }
            $view->setController($this);
            $this->view_ =& $view;
        }

        return $view;
    }


    /**
     * Process a HTTP GET request.
     * 
     * @return ZMView A <code>ZMView</code> that handles presentation or <code>null</code>
     * if the controller generates the contents itself.
     */
    function processGet() {
    global $zm_crumbtrail;

        $zm_crumbtrail->addCrumb(zm_title(false));

        return $this->findView();
    }


    /**
     * Process a HTTP POST request.
     * 
     * @return ZMView A <code>ZMView</code> that handles presentation or <code>null</code>
     * if the controller generates the contents itself.
     */
    function processPost() { return $this->processGet(); }


    /**
     * Set the response content type.
     *
     * @param string type The content type.
     * @param string charset Optional charset; default is utf-8; <code>null</code> will omit the charset part.
     */
    function setContentType($type, $charset="utf-8") {
        $text = "Content-Type: " . $type;
        if (null != $charset) {
            $text .= "; charset=" . $charset;
        }
        header($text);
    }


    /**
     * Returns a <code>name => object</code> hash of variables that need to be exported
     * into the theme space.
     *
     * @return array An associative array of <code>name => object</code> for all variables
     *  that need to be exported into the theme space.
     */
    function getGlobals() {
        return $this->globals_;
    }


    /**
     * Export the given object under the given name.
     * <p>Controller may use this method to make objects available to the response template/view.</p>
     *
     * @param string name The name under which the object should be visible.
     * @param mixed instance An object.
     */
    function exportGlobal($name, &$instance) {
        if (null === $instance)
            return;
        $this->globals_[$name] =& $instance;
    }

    /**
     * Returns the named global.
     *
     * @param string name The object name.
     * @param mixed instance An object instance or <code>null</code>.
     */
    function &getGlobal($name) {
        return array_key_exists($name, $this->globals_) ? $this->globals_[$name] : null;
    }

    /**
     * Lookup the appropriate view for the given name.
     *
     * <p>This implementation might be changed later on to allow for view mapping to make the page
     * flow configurable and extract that knowlege from the controller into some sort of config
     * file or other piece of logic.</p>
     *
     * @param string id The controller id or <code>null</code> to return to the current page.
     * @param array parameter Optional map of name/value pairs to further configure the view; default is <code>null</code>.
     * @return ZMView The actual view to be used to render the response.
     */
    function &findView($id=null, $parameter=null) {
    global $zm_urlMapper;

        // page and controller name *must* be the same as the logic to 
        // build the controller name is based on that fact!
        $view =& $zm_urlMapper->findView($this->id_, $id, $parameter);
        return $view;
    }

    /**
     * Validate the current request using the given rule id.
     *
     * @param string id The <code>ZMRuleSet</code> id.
     * @param mixed req A (request) map, an object or <code>null</code> to default to <code>$_POST</code>.
     * @return boolean <code>true</code> if the validation was successful, <code>false</code> if not.
     */
    function validate($id, $req=null) {
    global $zm_validator, $zm_messages;

        if (null === $req) {
            $req = zm_sanitize($_POST);
        }

        if (!$zm_validator->hasRuleSet($id)) {
            return true;
        }

        $valid = $zm_validator->validate($req, $id);
        if (!$valid) {
            foreach ($zm_validator->getMessages() as $field => $fieldMessages) {
                foreach ($fieldMessages as $msg) {
                    $zm_messages->error($msg, $field);
                }
            }
        }

        return $valid;
    }

    /**
     * Get the current view.
     *
     * @return ZMView The view or <code>null</code>.
     */
    function &getView() {
        return $this->view_;
    }

    /**
     * Get the controller id.
     *
     * @return string The id (page name).
     */
    function getId() { return $this->id_; }

}

?>
