<?php

/**
 * Test order service.
 *
 * @package org.zenmagick.plugins.zm_tests.tests
 * @author DerManoMann
 * @version $Id$
 */
class TestZMOrders extends ZMTestCase {

    /**
     * Test create product.
     */
    public function testUpdateOrderStatus() {
        $order = ZMOrders::instance()->getOrderForId(1);
        if (null != $order) {
            $order->setOrderStatusId(4);
            ZMOrders::instance()->updateOrder($order);
            $order = ZMOrders::instance()->getOrderForId(1);
            $this->assertEqual(4, $order->getOrderStatusId());
            $this->assertEqual('Update', $order->getStatusName());
            $order->setOrderStatusId(2);
            ZMOrders::instance()->updateOrder($order);
            $order = ZMOrders::instance()->getOrderForId(1);
            $this->assertEqual(2, $order->getOrderStatusId());
            $this->assertEqual('Processing', $order->getStatusName());
        } else {
            $this->fail('test order not found');
        }
    }

    /**
     * Test get orders for status.
     */
    public function testGetOrdersForStatusId() {
        $orders = ZMOrders::instance()->getOrdersForStatusId(2);
        $this->assertNotNull($orders);
        $this->assertTrue(0 < count($orders));
    }

    /**
     * Test order account.
     */
    public function testGetAccount() {
        $order = ZMOrders::instance()->getOrderForId(1);
        if (null != $order) {
            $account = $order->getAccount();
            $this->assertNotNull($account);
            $this->assertNotNull($account->getLastName());
            $this->assertNotNull($account->getEmail());
        } else {
            $this->fail('test order not found');
        }
    }

    /**
     * Test change address.
     */
    public function testChangeAddress() {
        $order = ZMOrders::instance()->getOrderForId(1);
        $this->assertNotNull($order);

        if (null != $order) {
            $address = ZMLoader::make('Address');
            $address->setFirstName('foo');
            $address->setLastName('bar');
            $address->setCompanyName('dooh inc.');
            $address->setAddress('street 1');
            $address->setSuburb('sub');
            $address->setPostcode('12345');
            $address->setCity('Christchurch');
            $address->setState('Canterbury');
            $address->setCountryId(153);
            //address format is derived from country

            $order->setBillingAddress($address);

            $this->assertEqual('12345', $order->get('billing_postcode'));
        } else {
            $this->fail('test order not found');
        }

    }

    /**
     * Test downloads.
     */
    public function testDownloads() {
        $downloads = ZMOrders::instance()->getDownloadsForOrderId(62, array(1));
        foreach ($downloads as $dl) {
            echo $dl->getId().': isDownloadable:'.$dl->isDownloadable()."<BR>";
            echo $dl->getId().': isLimited:'.$dl->isLimited()."<BR>";
            echo $dl->getId().': getFileSize:'.$dl->getFileSize()."<BR>";
        }
    }

    /**
     * Test get order status history.
     */
    public function testGetOrderStatusHistory() {
        $order = ZMOrders::instance()->getOrderForId(1);
        $this->assertNotNull($order);

        if (null != $order) {
            $orderStatusHistory = $order->getOrderStatusHistory();
            $this->assertNotNull($orderStatusHistory);
            $this->assertTrue(is_array($orderStatusHistory));
            $this->assertEqual(1, count($orderStatusHistory));

            // check first entry
            $orderStatus = $orderStatusHistory[0];
            $this->assertEqual(1, $orderStatus->getId());
            $this->assertEqual(1, $orderStatus->getOrderId());
            $this->assertEqual('Pending', $orderStatus->getName());
            $this->assertEqual(true, $orderStatus->isCustomerNotified());
            $this->assertEqual(null, $orderStatus->getComment());
        } else {
            $this->fail('test order not found');
        }
    }

    /**
     * Test create order status history.
     */
    public function testCreateOrderStatusHistory() {
        $order = ZMOrders::instance()->getOrderForId(1);
        $this->assertNotNull($order);

        if (null != $order) {
            $orderStatusHistory = $order->getOrderStatusHistory();
            $this->assertNotNull($orderStatusHistory);
            $this->assertTrue(is_array($orderStatusHistory));
            $this->assertEqual(1, count($orderStatusHistory));

            $newOrderStatus = ZMLoader::make('OrderStatus');
            $newOrderStatus->setOrderId(1);
            $newOrderStatus->setOrderStatusId(2);
            $newOrderStatus = ZMOrders::instance()->createOrderStatusHistory($newOrderStatus);
            // check for new primary key
            $this->assertTrue(0 != $newOrderStatus->getId());

            $orderStatusHistory = $order->getOrderStatusHistory();
            $this->assertNotNull($orderStatusHistory);
            $this->assertTrue(is_array($orderStatusHistory));
            $this->assertEqual(2, count($orderStatusHistory));
            // check created entry
            $createdOrderStatus = $orderStatusHistory[1];
            // make sure this is set
            $this->assertEqual('Processing', $createdOrderStatus->getName());
            $this->assertNotNull($createdOrderStatus->getDateAdded());

            // clean up
            $sql = "DELETE FROM ".TABLE_ORDERS_STATUS_HISTORY." WHERE orders_status_history_id = :orderStatusHistoryId";
            ZMRuntime::getDatabase()->update($sql, array('orderStatusHistoryId' => $newOrderStatus->getId()), TABLE_ORDERS_STATUS_HISTORY);
        } else {
            $this->fail('test order not found');
        }
    }

    /**
     * Test get order totals.
     */
    public function testGetOrderTotals() {
        $order = ZMOrders::instance()->getOrderForId(1);
        $this->assertNotNull($order);

        if (null != $order) {
            $totals = $order->getOrderTotals();
            $this->assertNotNull($totals);
            $this->assertTrue(is_array($totals));
            $this->assertEqual(3, count($totals));
            
            // test total total
            $total = $totals['ot_total'];
            $this->assertEqual('Total:', $total->getName());
            $this->assertEqual('$52.49', $total->getValue());
            $this->assertEqual(52.49, $total->getAmount());
            $this->assertEqual('ot_total', $total->getType());
        } else {
            $this->fail('test order not found');
        }
    }

    /*
     * Test order items.
     */
    public function testOrderItems() {
        $order = ZMOrders::instance()->getOrderForId(109);
        $this->assertNotNull($order);

        if (null != $order) {
            $items = $order->getOrderItems();
            $this->assertEqual(1, count($items));
            $item = $items[0];
            $this->assertEqual(1, $item->getProductId());
            $this->assertEqual(1, $item->getQty());
            $this->assertNotNull($item->getTaxRate());
            $this->assertEqual(10.00, $item->getTaxRate()->getRate());
            $this->assertEqual(2, count($item->getAttributes()));
        } else {
            $this->fail('test order not found');
        }
    }

    /*
     * Test order item attributes.
     */
    public function testOrderItemAttributes() {
        $order = ZMOrders::instance()->getOrderForId(109);
        $this->assertNotNull($order);

        if (null != $order) {
            $items = $order->getOrderItems();
            $this->assertEqual(1, count($items));
            $item = $items[0];
            $attributes = $item->getAttributes();
            $this->assertEqual(2, count($attributes));

            if (2 == count($attributes)) {
                // expect productId 1 with model:premium and memory:16MB
                $attribute = $attributes[0];
                $this->assertEqual('Model', $attribute->getName());
                $values = $attribute->getValues();
                $this->assertEqual(1, count($values));
                $value = $values[0];
                $this->assertEqual('Premium', $value->getName());

                $attribute = $attributes[1];
                $this->assertEqual('Memory', $attribute->getName());
                $values = $attribute->getValues();
                $this->assertEqual(1, count($values));
                $value = $values[0];
                $this->assertEqual('16 mb', $value->getName());
            } else {
                $this->fail('missing attributes');
            }
        } else {
            $this->fail('test order not found');
        }
    }

}

?>
