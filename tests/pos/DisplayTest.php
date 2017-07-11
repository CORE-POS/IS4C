<?php

use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\PrehLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\Tenders\TenderModule;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
use COREPOS\pos\lib\Notifiers\NumPad;

/**
 * @backupGlobals disabled
 */
class DisplayTest extends PHPUnit_Framework_TestCase
{

    public function testScreenDisplay()
    {
        lttLib::clear();
        $session = new WrappedStorage();
        $u = new COREPOS\pos\parser\parse\UPC($session);
        $u->check('666');
        $u->parse('666');

        $records = DisplayLib::screenDisplay(0, 1);
        $item = array(
            'description' => 'EXTRA BAG',
            'comment' => '',
            'total' => 0.05,
            'status' => 'T',
            'lineColor' => '004080',
            'discounttype' => 0,
            'trans_type' => 'I',
            'trans_status' => '',
            'voided' => 0,
            'trans_id' => 1
        );
        $spec = array(1 => $item);
        $this->assertEquals(count($records), count($spec));
        $view = $this->getViewVersion(0, 2);
        foreach (array_keys($records) as $i) {
            $this->assertArrayHasKey($i, $records);
            $this->assertArrayHasKey($i, $spec);
            $this->compareArrays($records[$i], $spec[$i]);
            $this->compareArrays($records[$i], $view[$i]);
        }

        $t = new TenderModule('CA', 1.00);
        $t->add();
        $tender = array(
            'description' => '',
            'comment' => 'Cash',
            'total' => -1.00,
            'status' => '',
            'lineColor' => '800000',
            'discounttype' => 0,
            'trans_type' => 'T',
            'trans_status' => '',
            'voided' => 0,
            'trans_id' => 2,
        );
        $spec[2] = $tender;
        $records = DisplayLib::screenDisplay(0, 2);
        $view = $this->getViewVersion(0, 2);
        $this->assertEquals(count($records), count($spec));
        foreach (array_keys($records) as $i) {
            $this->assertArrayHasKey($i, $records);
            $this->assertArrayHasKey($i, $spec);
            $this->compareArrays($records[$i], $spec[$i]);
            $this->compareArrays($records[$i], $view[$i]);
        }

        CoreLocal::set('memberID', 1);
        CoreLocal::set('isMember', 1);
        CoreLocal::set('percentDiscount', 10);
        CoreLocal::set('memType', 1);
        PrehLib::ttl();

        $notify = array(
            'description' => '** 10% Discount Applied **',
            'comment' => '',
            'total' => '', 
            'status' => '',
            'lineColor' => '408080',
            'discounttype' => 0,
            'trans_type' => '0',
            'trans_status' => 'D',
            'voided' => 4,
            'trans_id' => 3
        );
        $discount = array(
            'description' => '',
            'comment' => 'Discount',
            'total' => 0.00, 
            'status' => '',
            'lineColor' => '408080',
            'discounttype' => 0,
            'trans_type' => 'C',
            'trans_status' => 'D',
            'voided' => 5,
            'trans_id' => 4
        );
        $subtotal = array(
            'description' => 'Subtotal -0.95, Tax0.00 #1',
            'comment' => 'Total ',
            'total' => -0.95, 
            'status' => '',
            'lineColor' => '000000',
            'discounttype' => 0,
            'trans_type' => 'C',
            'trans_status' => 'D',
            'voided' => 3,
            'trans_id' => 5
        );
        $spec[3] = $notify;
        $spec[4] = $discount;
        $spec[5] = $subtotal;

        $records = DisplayLib::screenDisplay(0, 5);
        $view = $this->getViewVersion(0, 5);
        $this->assertEquals(count($records), count($spec));
        foreach (array_keys($records) as $i) {
            $this->assertArrayHasKey($i, $records);
            $this->assertArrayHasKey($i, $spec);
            $this->compareArrays($records[$i], $spec[$i]);
            $this->compareArrays($records[$i], $view[$i]);
        }

        CoreLocal::set('quantity', 2);
        CoreLocal::set('multiple', 1);
        $u = new COREPOS\pos\parser\parse\UPC($session);
        $u->check('4627');
        $u->parse('4627');
        $item = array(
            'description' => 'PKALE',
            'comment' => '2 @ 1.99',
            'total' => 3.98,
            'status' => 'F',
            'lineColor' => '408080',
            'discounttype' => 1,
            'trans_type' => 'I',
            'trans_status' => '',
            'voided' => 0,
            'trans_id' => 6,
        );
        $notice = array(
            'description' => '** YOU SAVED $0.60 **',
            'comment' => '',
            'total' => '',
            'status' => '',
            'lineColor' => '408080',
            'discounttype' => 0,
            'trans_type' => 'I',
            'trans_status' => 'D',
            'voided' => 2,
            'trans_id' => 7
        );
        $spec[6] = $item;
        $spec[7] = $notice;

        $records = DisplayLib::screenDisplay(0, 7);
        $view = $this->getViewVersion(0, 7);
        $this->assertEquals(count($records), count($spec));
        foreach (array_keys($records) as $i) {
            $this->assertArrayHasKey($i, $records);
            $this->assertArrayHasKey($i, $spec);
            $this->compareArrays($records[$i], $spec[$i]);
            $this->compareArrays($records[$i], $view[$i]);
        }

        CoreLocal::set('quantity', 0);
        CoreLocal::set('multiple', 0);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd($session);
        $v->check('VD');
        $v->parse('VD');
        $void = array(
            'description' => 'EXTRA BAG',
            'comment' => '',
            'total' => -0.05,
            'status' => 'VD',
            'lineColor' => '800000',
            'discounttype' => 0,
            'trans_type' => 'I',
            'trans_status' => 'V',
            'voided' => 1,
            'trans_id' => 8
        );
        $spec[8] = $void;
        $spec[1]['voided'] = 1;

        $records = DisplayLib::screenDisplay(0, 8);
        $view = $this->getViewVersion(0, 8);
        $this->assertEquals(count($records), count($spec));
        foreach (array_keys($records) as $i) {
            $this->assertArrayHasKey($i, $records);
            $this->assertArrayHasKey($i, $spec);
            $this->compareArrays($records[$i], $spec[$i]);
            $this->compareArrays($records[$i], $view[$i]);
        }

        TransRecord::addFsTaxExempt();
        $fs = array(
            'description' => '',
            'comment' => 'FS Tax Exempt',
            'total' => 0.00, 
            'status' => '',
            'lineColor' => '800000',
            'discounttype' => 0,
            'trans_type' => 'C',
            'trans_status' => 'D',
            'voided' => 17,
            'trans_id' => 9
        );
        $spec[9] = $fs;

        $records = DisplayLib::screenDisplay(0, 9);
        $view = $this->getViewVersion(0, 9);
        $this->assertEquals(count($records), count($spec));
        foreach (array_keys($records) as $i) {
            $this->assertArrayHasKey($i, $records);
            $this->assertArrayHasKey($i, $spec);
            $this->compareArrays($records[$i], $spec[$i]);
            $this->compareArrays($records[$i], $view[$i]);
            $this->compareArrays($spec[$i], $view[$i]);
        }

        CoreState::memberReset();
        lttLib::clear();
    }

    private function compareArrays($one, $two)
    {
        $this->assertInternalType('array', $one);
        $this->assertInternalType('array', $two);
        foreach ($one as $key => $val) {
            $this->assertArrayHasKey($key, $two, 'Missing key ' . $key);
            $this->assertEquals($val, $two[$key], 'Value differs for ' . $key);
        }
    }

    private function getViewVersion($min, $max)
    {
        $db = Database::tDataConnect();
        $r = $db->query('
            SELECT * 
            FROM screendisplay 
            WHERE trans_id BETWEEN ' . $min . ' AND ' . $max);
        $ret = array();
        while ($w = $db->fetch_row($r)) {
            foreach (array_keys($w) as $k) {
                if (is_int($k)) {
                    unset($w[$k]);
                }
            }
            $ret[$w['trans_id']] = $w;
        }

        return $ret;
    }

    public function testNumPad()
    {
        $np = new NumPad();
        CoreLocal::set('touchscreen', true);
        $this->assertNotEquals(0, strlen($np->draw()));
        CoreLocal::set('touchscreen', false);
        $this->assertEquals('', $np->draw());
    }
}
