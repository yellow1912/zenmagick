<?php
/*
 * ZenMagick - Smart e-commerce
 * Copyright (C) 2006-2012 zenmagick.org
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
namespace zenmagick\apps\store\storefront\controller;

use zenmagick\base\Runtime;
use zenmagick\apps\store\model\coupons\Coupon;

/**
 * Request controller for gv send page.
 *
 * @author DerManoMann <mano@zenmagick.org>
 */
class GvSendController extends \ZMController {

    /**
     * {@inheritDoc}
     */
    public function processGet($request) {
        return $this->findView(null, array('currentAccount' => $request->getAccount()));
    }

    /**
     * {@inheritDoc}
     */
    public function processPost($request) {
        $gvReceiver = $this->getFormData($request);

        // back from confirmation to edit or not valid
        if (null != $request->getParameter('edit')) {
            return $this->findView();
        }

        $data = array();
        $data['currentAccount'] = $request->getAccount();
        // to fake the email content display
        $coupon = new Coupon();
        $coupon->setCode( _zm('THE_COUPON_CODE'));
        $data['currentCoupon'] = $coupon;

        return $this->findView('success', $data);
    }

}
