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
 * List validation rules.
 *
 * <p>Validate against a list of allowed values.</p>
 *
 * @author DerManoMann
 * @package org.zenmagick.validation.rules
 * @version $Id$
 */
class ZMListRule extends ZMRule {
    private $values_;


    /**
     * Create new list rule.
     *
     * @param string name The field name.
     * @param mixed values The list of valid values as either a comma separated string or array.
     * @param string msg Optional message.
     */
    function __construct($name, $values, $msg=null) {
        parent::__construct($name, "%s is not valid.", $msg);
        $this->values_ = $values;
    }

    /**
     * Destruct instance.
     */
    function __destruct() {
        parent::__destruct();
    }


    /**
     * Validate the given request data.
     *
     * @param array req The request data.
     * @return boolean <code>true</code> if the regular expression does match.
     */
    function validate($req) {
        return empty($req[$this->getName()]) || ZMTools::inArray($req[$this->getName()], $this->values_);
    }


    /**
     * Create JS validation call.
     *
     * @return string Formatted JavaScript .
     */
    function toJSString() {
        $quoted = array();
        foreach ($this->values_ as $value) {
            $quoted[] = "'".addslashes($value)."'";
        }
        $js = "    new Array('list'";
        $js .= ",'".$this->getName()."'";
        $js .= ",'".addslashes($this->getErrorMsg())."'";
        $js .= ",new Array(".implode(',', $quoted).")";
        $js .= ")";
        return $js;
    }

}

?>
