<?php
/*
 * ZenMagick - Extensions for zen-cart
 * Copyright (C) 2006-2010 zenmagick.org
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
 * Test salemaker service.
 *
 * @package org.zenmagick.plugins.unitTests.tests
 * @author DerManoMann
 * @version $Id$
 */
class TestZMSalemaker extends ZMTestCase {

    /**
     * Test getSaleDiscountTypeInfo.
     */
    public function testGetSaleDiscountTypeInfo() {
        foreach (ZMProducts::instance()->getAllProducts(false) as $product) {
            $productId = $product->getId();
            $info = ZMSalemaker::instance()->getSaleDiscountTypeInfo($productId);
            $type = zen_get_products_sale_discount_type($productId);
            $amount = zen_get_products_sale_discount_type($productId, false, 'amount');
            if (!$this->assertEqual(array('type'=>$type, 'amount'=>$amount), $info)) {
                echo $productId . $product->getName();
                break;
            }
        }
    }

}
