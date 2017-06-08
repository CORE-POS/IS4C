<?php

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\Drawers;
use COREPOS\pos\lib\Franking;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;
use COREPOS\pos\lib\ReceiptBuilding\Messages\StoreCreditIssuedReceiptMessage;
use COREPOS\pos\lib\ReceiptBuilding\Messages\GenericSigSlipMessage;
use COREPOS\pos\lib\ReceiptBuilding\Messages\GCBalanceReceiptMessage;
use COREPOS\pos\lib\ReceiptBuilding\Tag\DefaultReceiptTag;
use COREPOS\pos\lib\ReceiptBuilding\Format\TotalReceiptFormat;
use COREPOS\pos\lib\ReceiptBuilding\Format\TenderReceiptFormat;
use COREPOS\pos\lib\ReceiptBuilding\Format\OtherReceiptFormat;
use COREPOS\pos\lib\ReceiptBuilding\Format\DefaultReceiptFormat;
use COREPOS\pos\lib\ReceiptBuilding\Format\ItemReceiptFormat;
use COREPOS\pos\lib\ReceiptBuilding\Format\TwoLineItemReceiptFormat;
use COREPOS\pos\lib\ReceiptBuilding\CustMessages\WfcEquityMessage;
use COREPOS\pos\lib\ReceiptBuilding\CustMessages\CustomerReceiptMessage;
use COREPOS\pos\lib\ReceiptBuilding\HtmlEmail\DefaultHtmlEmail;
use COREPOS\pos\lib\PrintHandlers\PrintHandler;

/**
 * @backupGlobals disabled
 */
class ReceiptTest extends PHPUnit_Framework_TestCase
{
    /**
      Check methods for getting available PreParser and Parser modules
    */
    public function testMessages()
    {
        $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\Messages\\ReceiptMessage', true);
        $ph = new PrintHandler();

        foreach($mods as $message_class) {
            $db = Database::tDataConnect();
            $obj = new $message_class();
            $obj->setPrintHandler($ph);

            $selectStr = $obj->select_condition();
            $this->assertInternalType('string', $selectStr);

            $queryResult = $db->query('SELECT '.$selectStr.' FROM localtemptrans');
            $this->assertNotEquals(false, $queryResult, $selectStr . ' creates a failing query, '. $db->error());

            $msg = $obj->message(1, '1-1-1', false);
            $this->assertInternalType('string', $msg);

            $this->assertInternalType('boolean', $obj->paper_only);
            $this->assertInternalType('string', $obj->standalone_receipt_type);

            $receipt = $obj->standalone_receipt('1-1-1', false);
            $this->assertInternalType('string', $receipt);
        }

        $m = new StoreCreditIssuedReceiptMessage();
        $this->assertNotEquals(0, strlen($m->message(1, '1-1-1', true)));

        $m = new GenericSigSlipMessage();
        $this->assertNotEquals(0, strlen($m->message(1, '1-1-1', true)));

        $m = new GCBalanceReceiptMessage();
        CoreLocal::set('paycard_response', array('Balance' => 5));
        $this->assertNotEquals(0, strlen($m->standalone_receipt('1-1-1')));
        CoreLocal::set('paycard_response', '');
    }

    public function testTags()
    {
        $rowset = array(
            0 => array('trans_type'=>'T', 'department'=>0),
        );
        $tag = new DefaultReceiptTag();
        
        $out = $tag->tag($rowset);
        $this->assertEquals('Tender', $out[0]['tag']);

        $rowset[0]['department'] = 1;
        $out = $tag->tag($rowset);
        $this->assertEquals('Item', $out[0]['tag']);

        $rowset[0]['trans_type'] = 'I';
        $out = $tag->tag($rowset);
        $this->assertEquals('Item', $out[0]['tag']);

        $rowset[0]['trans_type'] = 'D';
        $out = $tag->tag($rowset);
        $this->assertEquals('Item', $out[0]['tag']);

        $rowset[0]['trans_type'] = 'H';
        $out = $tag->tag($rowset);
        $this->assertEquals('Other', $out[0]['tag']);

        $rowset[0]['trans_type'] = '0';
        $out = $tag->tag($rowset);
        $this->assertEquals('Other', $out[0]['tag']);

        $rowset[0]['trans_type'] = 'Default Tag';
        $out = $tag->tag($rowset);
        $this->assertEquals('Total', $out[0]['tag']);
    }

    public function testSavings()
    {
        foreach (array('COREPOS\\pos\\lib\\ReceiptBuilding\\Savings\\DefaultReceiptSavings', 'COREPOS\\pos\\lib\\ReceiptBuilding\\Savings\\SeparateReceiptSavings') as $class) {
            $obj = new $class();
            $this->assertEquals('', $obj->savingsMessage('1-1-1'));
        }
    }

    public function testFormatters()
    {
        $item = array('upc'=>'TOTAL', 'total'=>'-1.00');
        $f = new TotalReceiptFormat();
        $this->assertEquals(str_repeat(' ', 39) . 'TOTAL   -1.00', $f->format($item));
        $item['upc'] = 'SUBTOTAL';
        $this->assertEquals(str_repeat(' ', 36) . 'SUBTOTAL   -1.00', $f->format($item));
        $item['upc'] = 'TAX';
        $this->assertEquals(str_repeat(' ', 41) . 'TAX   -1.00', $f->format($item));
        $item['percentDiscount'] = 10;
        $item['upc'] = 'DISCOUNT';
        $this->assertEquals('** 10% Discount Applied **' . str_repeat(' ', 18) . '   -1.00', $f->format($item));

        $f = new TenderReceiptFormat();
        $item['description'] = 'Cash';
        $this->assertEquals(str_repeat(' ', 40) . 'Cash    1.00', $f->format($item));

        $f = new OtherReceiptFormat();
        $item['trans_type'] = '0';
        $item['description'] = '** FOO';
        $this->assertEquals(' = foo', $f->format($item));
        $item['trans_type'] = 'H';
        $this->assertEquals('** FOO', $f->format($item));
        $item['trans_type'] = 'Z';
        $this->assertEquals('', $f->format($item));

        $f = new DefaultReceiptFormat();
        $this->assertEquals('', $f->format($item));

        $f = new ItemReceiptFormat();
        $item['trans_type'] = 'D';
        $item['description'] = 'OPEN';
        $item['total'] = '1.00';
        $item['trans_status'] = 'V';
        $this->assertEquals('OPEN' . str_repeat(' ', 40) . '    1.00  VD', $f->format($item));
        $item['trans_status'] = 'R';
        $this->assertEquals('OPEN' . str_repeat(' ', 40) . '    1.00  RF', $f->format($item));
        $item['trans_status'] = '';
        $item['tax'] = 1;
        $item['foodstamp'] = 1;
        $this->assertEquals('OPEN' . str_repeat(' ', 40) . '    1.00  TF', $f->format($item));
        $item['trans_type'] = 'I';
        $item['trans_status'] = 'D';
        $item['description'] = '** FOO';
        $this->assertEquals(' > foo< ', $f->format($item));
        $item['description'] = 'ITEM';
        $item['trans_status'] = 'M';
        $this->assertEquals('ITEM' . str_repeat(' ', 26) . 'Owner Special     1.00    ', $f->format($item));
        $item['trans_status'] = '';
        $item['numflag'] = 'SO';
        $item['charflag'] = 'SO';
        $this->assertEquals('ITEM' . str_repeat(' ', 40) . '    1.00  TF', $f->format($item));
        $item['charflag'] = '';
        $item['scale'] = 1;
        $item['quantity'] = 1;
        $item['unitPrice'] = 1;
        $this->assertEquals('ITEM' . str_repeat(' ', 26) . '1.00 @ 1.00   ' . '    1.00  TF', $f->format($item));
        $item['scale'] = 0;
        $item['quantity'] = 2;
        $item['ItemQtty'] = 2;
        $this->assertEquals('ITEM' . str_repeat(' ', 26) . '2 @ 0.50' . str_repeat(' ', 6) . '    1.00  TF', $f->format($item));
        $item['quantity'] = 0;
        $item['ItemQtty'] = 0;
        $item['matched'] = 1;
        $this->assertEquals('ITEM' . str_repeat(' ', 26) . 'w/ vol adj' .str_repeat(' ', 4) . '    1.00  TF', $f->format($item));

        $two = new TwoLineItemReceiptFormat();
        $this->assertNotEquals(false, strstr($two->format($item), "\n")); 
    }

    public function testCustMessages()
    {
        $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\CustMessages\\CustomerReceiptMessage');

        foreach($mods as $class) {
            $obj = new $class();

            $output = $obj->message('SAMPLE INPUT STRING');
            if (is_array($output)) {
                $this->assertArrayHasKey('print', $output);
                $this->assertArrayHasKey('any', $output);
            } else {
                $this->assertInternalType('string', $output);
            }
        }

        $mod = new WfcEquityMessage();
        $str = str_repeat('-', 13) . '100.00 == line2';
        CoreLocal::set('equityNoticeAmt', 100.00);
        $out = $mod->message($str);
        $this->assertEquals(str_repeat(' ', 17) . "EQUITY BALANCE DUE \$0.00\n" . str_repeat(' ', 23) . "PAID IN FULL\n", $out);
        CoreLocal::set('equityNoticeAmt', 0);

        $mod = new CustomerReceiptMessage();
        $this->assertEquals('foo', $mod->message('foo'));
    }

    public function testDataFetch()
    {
        $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\DataFetch\\DefaultReceiptDataFetch');
        $dbc = Database::tDataConnect();

        foreach($mods as $message_class) {
            $obj = new $message_class();

            $queryResult = $obj->fetch($dbc);
            $this->assertNotEquals(false, $queryResult);
            $queryResult = $obj->fetch($dbc, 1, 1, 1);
            $this->assertNotEquals(false, $queryResult);
        }
    }

    /** simulated fetch data */
    private $test_records = array(
        array('upc'=>'TAX','description'=>'TAX','trans_type'=>'A','trans_subtype'=>'','trans_status'=>'','total'=>0,'percentDiscount'=>5,
              'charflag'=>'','scale'=>0,'quantity'=>0,'unitPrice'=>0,'ItemQtty'=>0,'matched'=>0,'numflag'=>0,'tax'=>0,'foodstamp'=>0,
              'department'=>0,'subdept_name','','trans_id'=>6),
        array('upc'=>'DISCOUNT','description'=>'DISCOUNT','trans_type'=>'S','trans_subtype'=>'','trans_status'=>'','total'=>-0.05,'percentDiscount'=>5,
              'charflag'=>'','scale'=>0,'quantity'=>0,'unitPrice'=>0,'ItemQtty'=>0,'matched'=>0,'numflag'=>0,'tax'=>0,'foodstamp'=>0,
              'department'=>0,'subdept_name','','trans_id'=>5),
        array('upc'=>'','description'=>'Change','trans_type'=>'T','trans_subtype'=>'CA','trans_status'=>'','total'=>0.05,'percentDiscount'=>5,
              'charflag'=>'','scale'=>0,'quantity'=>0,'unitPrice'=>0,'ItemQtty'=>0,'matched'=>0,'numflag'=>0,'tax'=>0,'foodstamp'=>0,
              'department'=>0,'subdept_name','','trans_id'=>4),
        array('upc'=>'','description'=>'Cash','trans_type'=>'T','trans_subtype'=>'CA','trans_status'=>'','total'=>-1.00,'percentDiscount'=>5,
              'charflag'=>'','scale'=>0,'quantity'=>0,'unitPrice'=>0,'ItemQtty'=>0,'matched'=>0,'numflag'=>0,'tax'=>0,'foodstamp'=>0,
              'department'=>0,'subdept_name','','trans_id'=>3),
        array('upc'=>'','description'=>'Subtotal 1.00, Tax 0.00','trans_type'=>'C','trans_subtype'=>'','trans_status'=>'','total'=>0,'percentDiscount'=>5,
              'charflag'=>'','scale'=>0,'quantity'=>0,'unitPrice'=>0,'ItemQtty'=>0,'matched'=>0,'numflag'=>0,'tax'=>0,'foodstamp'=>0,
              'department'=>0,'subdept_name','','trans_id'=>2),
        array('upc'=>'0000000004011','description'=>'PBANANA','trans_type'=>'I','trans_subtype'=>'','trans_status'=>'','total'=>1.00,'percentDiscount'=>5,
              'charflag'=>'','scale'=>1,'quantity'=>1,'unitPrice'=>1.000,'ItemQtty'=>1,'matched'=>0,'numflag'=>0,'tax'=>0,'foodstamp'=>1,
              'department'=>0,'subdept_name','','trans_id'=>1),
    );

    /** accumulate records for later tests */
    private $record_sets = array();

    public function testFilter()
    {
        $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\DefaultReceiptFilter');

        foreach($mods as $filter_class) {
            $obj = new $filter_class();
    
            $dbc = new COREPOS\common\sql\TestManager('127.0.0.1', 'mysqli', 'foo', 'bar', 'baz');
            $dbc->setTestData($this->test_records);

            $resultset = $obj->filter($dbc, $this->test_records);
            $this->assertInternalType('array', $resultset);

            foreach($resultset as $result) {
                $this->assertInternalType('array', $result);
                $this->assertArrayHasKey('upc', $result);
                $this->assertArrayHasKey('trans_type', $result);
            }

            $this->record_sets[] = $resultset;
        }
    }

    public function testSort()
    {
        $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\Sort\\DefaultReceiptSort');

        if (empty($this->record_sets)) {
            $this->record_sets[] = $this->test_records;
        }

        $sorted_sets = array();

        foreach($mods as $sort_class) {
            $obj = new $sort_class();
            
            foreach($this->record_sets as $set) {
                $sorted = $obj->sort($set);
                $this->assertInternalType('array', $set);

                foreach($set as $result) {
                    $this->assertInternalType('array', $result);
                    $this->assertArrayHasKey('upc', $result);
                    $this->assertArrayHasKey('trans_type', $result);
                }

                $sorted_sets[] = $sorted;
            }
        }

        $this->record_sets = $sorted_sets;
    }

    public function testTag()
    {
        $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\Tag\\DefaultReceiptTag');

        if (empty($this->record_sets)) {
            $this->record_sets[] = $this->test_records;
        }

        $tagged_sets = array();

        foreach($mods as $tag_class) {
            $obj = new $tag_class();

            foreach($this->record_sets as $set) {
                $tagged = $obj->tag($set);
                $this->assertInternalType('array', $set);

                foreach($tagged as $result) {
                    $this->assertInternalType('array', $result);
                    $this->assertArrayHasKey('upc', $result);
                    $this->assertArrayHasKey('trans_type', $result);
                    $this->assertArrayHasKey('tag', $result);
                }

                $tagged_sets[] = $tagged;
            }
        }

        $this->record_sets = $tagged_sets;
    }

    public function testFormat()
    {
        $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\DefaultReceiptFormat');

        if (empty($this->record_sets)) {
            $this->record_sets[] = $this->test_records;
        }

        foreach($mods as $format_class) {
            $obj = new $format_class();

            $this->assertInternalType('boolean', $obj->is_bold);

            foreach($this->record_sets as $set) {
                foreach($set as $line) {
                   $output = $obj->format($line); 

                   $this->assertInternalType('string', $output);
                }
            }
        }
    }

    public function testHtml()
    {
        $obj = new DefaultHtmlEmail();
        $this->assertEquals('', $obj->receiptHeader());
        $this->assertEquals('', $obj->receiptFooter());
    }

    public function testLib()
    {
        CoreLocal::set('dualDrawerMode', 1, true);
        $drawers = new Drawers(new WrappedStorage(), Database::pDataConnect());
        $drawers->free(1);
        $drawers->free(2);
        $this->assertEquals(0, $drawers->current());
        $emp = CoreLocal::get('CashierNo');
        CoreLocal::set('CashierNo', 1);
        $this->assertEquals(true, $drawers->assign(1, 2));
        $this->assertEquals(2, $drawers->current());
        $drawers->kick();
        $drawers->free(2);
        CoreLocal::set('CashierNo', $emp);

        $this->assertNotEquals(0, strlen(ReceiptLib::printChargeFooterStore(time(), '1-1-1')));
        $this->assertNotEquals(0, strlen(ReceiptLib::printCabCoupon(time(), '1-1-1')));
        $frank = new Franking(new WrappedStorage());
        $frank->frank(1);
        $frank->frankgiftcert(1);
        $frank->frankstock(1);
        $frank->frankclassreg(1);
        $this->assertEquals(chr(27).chr(33).chr(5), ReceiptLib::normalFont());
        $this->assertEquals(chr(27).chr(33).chr(9), ReceiptLib::boldFont());
        ReceiptLib::bold();
        ReceiptLib::unbold();
        CoreLocal::set('receiptHeaderCount', 5);
        CoreLocal::set('receiptHeader1', 'foo');
        CoreLocal::set('receiptHeader2', 'WfcLogo2014.bmp');
        CoreLocal::set('receiptHeader3', 'WfcLogo2014.bmp');
        CoreLocal::set('receiptHeader4', 'nv123');
        CoreLocal::set('receiptHeader5', 'bar');
        CoreLocal::set('receiptFooterCount', 1);
        CoreLocal::set('receiptFooter1', 'foo');
        $this->assertNotEquals(0, strlen(ReceiptLib::printReceiptHeader(date('Y-m-d H:i:s'), '1-1-1')));
        $this->assertNotEquals(0, strlen(ReceiptLib::receiptFromBuilders(false, '1-1-1')));

        $col1 = array('a', 'b', 'c');
        $col2 = array('a', 'b');
        $this->assertNotEquals(0, strlen(ReceiptLib::twoColumns($col1, $col2)));
        $this->assertNotEquals(0, strlen(ReceiptLib::twoColumns($col2, $col1)));

        $this->assertEquals(array('any'=>'','print'=>''), ReceiptLib::memReceiptMessages(1));
        $this->assertEquals('COREPOS\pos\lib\PrintHandlers\EmailPrintHandler', ReceiptLib::emailReceiptMod());

        $port = CoreLocal::get('printerPort');
        $temp_file = tempnam(sys_get_temp_dir(), 'ppp');
        CoreLocal::set('printerPort', $temp_file);
        CoreLocal::set('print', 1);
        ReceiptLib::writeLine('foo');
        CoreLocal::set('print', 0);
        CoreLocal::set('printerPort', $port);
        unlink($temp_file);
    }

    public function testTenderReport()
    {
        $mods = AutoLoader::listModules('COREPOS\\pos\\lib\\ReceiptBuilding\\TenderReports\\TenderReport', true);
        foreach ($mods as $mod) {
            $this->assertInternalType('string', $mod::get(new WrappedStorage()));
        }
    }
}

