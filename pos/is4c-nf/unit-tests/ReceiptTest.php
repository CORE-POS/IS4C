<?php

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
        $mods = AutoLoader::listModules('ReceiptMessage');
        $db = Database::tDataConnect();

        foreach($mods as $message_class) {
            $obj = new $message_class();

            $selectStr = $obj->select_condition();
            $this->assertInternalType('string', $selectStr);

            $queryResult = $db->query('SELECT '.$selectStr.' FROM localtemptrans');
            $this->assertNotEquals(false, $queryResult);

            $msg = $obj->message(1, '1-1-1', false);
            $this->assertInternalType('string', $msg);

            $this->assertInternalType('boolean', $obj->paper_only);
            $this->assertInternalType('string', $obj->standalone_receipt_type);

            $receipt = $obj->standalone_receipt('1-1-1', false);
            $this->assertInternalType('string', $receipt);
        }
	}

	public function testCustMessages()
    {
        $mods = AutoLoader::listModules('CustomerReceiptMessage');

        foreach($mods as $class) {
            $obj = new $class();

            $output = $obj->message('SAMPLE INPUT STRING');
            $this->assertInternalType('string', $output);
        }
    }

	public function testDataFetch()
    {
        $mods = AutoLoader::listModules('DefaultReceiptDataFetch');

        foreach($mods as $message_class) {
            $obj = new $message_class();

            $queryResult = $obj->fetch();
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
        $mods = AutoLoader::listModules('DefaultReceiptFilter');

        foreach($mods as $filter_class) {
            $obj = new $filter_class();

            $db = Database::tDataConnect();
            $db->setTestData($this->test_records);

            $resultset = $obj->filter($this->test_records);
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
        $mods = AutoLoader::listModules('DefaultReceiptSort');

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
        $mods = AutoLoader::listModules('DefaultReceiptTag');

        if (empty($this->record_sets)) {
            $this->record_sets[] = $this->test_records;
        }

        $tagged_sets = array();

        foreach($mods as $tag_class) {
            $obj = new $tag_class();

            foreach($this->record_sets as $set) {
                $tagged = $obj->tag($set);
                $this->assertInternalType('array', $set);

                foreach($set as $result) {
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
        $mods = AutoLoader::listModules('DefaultReceiptFormat');

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

}

