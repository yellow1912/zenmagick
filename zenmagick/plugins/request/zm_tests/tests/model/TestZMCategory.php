<?php

/**
 * Test category.
 *
 * @package org.zenmagick.plugins.zm_tests.tests
 * @author DerManoMann
 * @version $Id$
 */
class TestZMCategory extends ZMTestCase {

    /**
     * Test childIds
     */
    public function testChildIds() {
        $expect = array(3, 10, 13, 12, 15, 11, 14);
        $category = ZMCategories::instance()->getCategoryForId(3);
        $ids = $category->getChildIds();
        $this->assertEqual(count($expect), count($ids));
        foreach ($expect as $id) {
            $this->assertTrue(in_array($id, $ids));
        }
    }

    /**
     * Test childIds excluding category.
     */
    public function testChildIdsExclude() {
        $expect = array(10, 13, 12, 15, 11, 14);
        $category = ZMCategories::instance()->getCategoryForId(3);
        $ids = $category->getChildIds(false);
        $this->assertEqual(count($expect), count($ids));
        foreach ($expect as $id) {
            $this->assertTrue(in_array($id, $ids));
        }
    }

}

?>
