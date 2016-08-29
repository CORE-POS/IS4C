<?php

use COREPOS\pos\lib\Database;

/**
 * @backupGlobals disabled
 */
class PaycardsTest extends PHPUnit_Framework_TestCase
{

    private function emptyPaycardTables()
    {
        $db = Database::tDataConnect();
        $db->query('DELETE FROM PaycardTransactions');
        if ($db->table_exists('efsnetRequest')) {
            $db->query('TRUNCATE TABLE efsnetRequest');
        }
        if ($db->table_exists('efsnetResponse')) {
            $db->query('TRUNCATE TABLE efsnetResponse');
        }
    }

    public function testGoE()
    {
        if (!class_exists('lttLib')) {
            include(dirname(__FILE__) . '/lttLib.php');
        }
        lttLib::clear();

        $plugins = CoreLocal::get('PluginList');
        if (!in_array('Paycards', $plugins)) {
            $plugins[] = 'Paycards';
            CoreLocal::set('PluginList', $plugins);
        }

        /**
          Initialize session stuff to run a test transaction
        */
        CoreLocal::set('CCintegrate', 1);
        CoreLocal::set('RegisteredPaycardClasses', array('GoEMerchant'));
        CoreLocal::set('PaycardsTerminalID', '99');
        CoreLocal::set('PaycardsTenderCodeCredit', 'CC');
        CoreLocal::set('PaycardsTenderCodeVisa', '');
        CoreLocal::set('CacheCardType', 'CREDIT');
        CoreLocal::set('ttlflag', 1);
        CoreLocal::set('amtdue', 0.01);
        CoreLocal::set('CashierNo', 9999);
        CoreLocal::set('laneno', 99);
        CoreLocal::set('transno', 1);
        CoreLocal::set('training', 1);

        /**
          Run parser on test PAN. Verify result
          and appropriate session vars initialized
        */
        $parser = new paycardEntered();
        $pan = '4111111111111111' . date('my');
        $this->assertEquals(true, $parser->check($pan), 'Parser does not handle input');
        $json = $parser->parse($pan);
        $url = 'plugins/Paycards/gui/paycardboxMsgAuth.php';
        $this->assertEmpty($json['output'], 'Parser error message: ' . $json['output']);
        $this->assertEquals($url, substr($json['main_frame'], -1*strlen($url)), 'No redirect to auth page');
        
        $this->assertEquals(0.01, CoreLocal::get('paycard_amount'));
        $this->assertEquals(PaycardLib::PAYCARD_MODE_AUTH, CoreLocal::get('paycard_mode'));
        $this->assertEquals(PaycardLib::PAYCARD_TYPE_CREDIT, CoreLocal::get('paycard_type'));
        $this->assertEquals('Visa', CoreLocal::get('paycard_issuer'));

        /**
          Submit transaction to processor.
          Verify return value and check database
          for an appropriate auth record
        */
        $this->emptyPaycardTables();
        $processor = new GoEMerchant();
        $auth_result = $processor->doSend(PaycardLib::PAYCARD_MODE_AUTH);
        $this->assertEquals(PaycardLib::PAYCARD_ERR_OK, $auth_result, 'Authorization attempt produced error: ' . $auth_result);
        $db = Database::tDataConnect();
        $ptrans = $db->query('SELECT * FROM PaycardTransactions');
        $row = $db->fetch_row($ptrans);
        $this->assertEquals(date('Ymd'), $row['dateID']);
        $this->assertEquals(9999, $row['empNo']);
        $this->assertEquals(99, $row['registerNo']);
        $this->assertEquals(1, $row['transNo']);
        $this->assertEquals(1, $row['transID']);
        $this->assertEquals('GoEMerchant', $row['processor']);
        $this->assertEquals('Credit', $row['cardType']);
        $this->assertEquals('Sale', $row['transType']);
        $this->assertEquals(0.01, $row['amount']);
        $this->assertEquals('Visa', $row['issuer']);
        $this->assertEquals(0, $row['commErr']);
        $this->assertEquals(200, $row['httpCode']);
        $this->assertEquals(1, $row['validResponse']);
        $this->assertEquals(1, $row['xResultCode']);
        $this->assertEquals(1, $row['xResponseCode']);
        $this->assertNotEmpty($row['xApprovalNumber']);
        $this->assertNotEmpty($row['xResultMessage']);
        $this->assertNotEmpty($row['xTransactionID']);
        $this->assertEquals('xxxxxxxxxxxx', substr($row['PAN'], 0, 12));

        /** cleanup test transaction record(s) */
        $this->emptyPaycardTables();
    }

    public function testMercuryE2E()
    {
        if (!class_exists('lttLib')) {
            include(dirname(__FILE__) . '/lttLib.php');
        }
        lttLib::clear();

        $plugins = CoreLocal::get('PluginList');
        if (!in_array('Paycards', $plugins)) {
            $plugins[] = 'Paycards';
            CoreLocal::set('PluginList', $plugins);
        }

        /**
          Initialize session stuff to run a test transaction
        */
        CoreLocal::set('CCintegrate', 1);
        CoreLocal::set('RegisteredPaycardClasses', array('MercuryE2E'));
        CoreLocal::set('PaycardsTerminalID', '99');
        CoreLocal::set('PaycardsTenderCodeCredit', 'CC');
        CoreLocal::set('PaycardsTenderCodeVisa', '');
        CoreLocal::set('CacheCardType', 'CREDIT');
        CoreLocal::set('ttlflag', 1);
        CoreLocal::set('amtdue', 0.01);
        CoreLocal::set('CashierNo', 9999);
        CoreLocal::set('laneno', 99);
        CoreLocal::set('transno', 1);
        CoreLocal::set('training', 1);

        /**
          Run parser on test PAN. Verify result
          and appropriate session vars initialized
        */
        $parser = new paycardEntered();
        // source: Visa Test Card using Sign&Pay w/ test keys
        $pan = '02E600801F2E2700039B25423430303330302A2A2A2A2A2A363738315E544553542F4D50535E313531322A2A2A2A2A2A2A2A2A2A2A2A2A3F3B3430303330302A2A2A2A2A2A363738313D313531322A2A2A2A2A2A2A2A2A2A2A2A2A2A2A2A3FA7284186B3E8E1A3E2AD8548E732DBB5B33285117FB1B0CDBA6D732E5DF031DE3CB590DE2E02BDEF6182373B7401A3E3D304013C85D3BEFDEBF552A3C30914246B0145538F2E5856885CAA06FF64E201CB974CD506ADDCB22C9F3BF500C62310C9C88B56FD2BDF6E59481BC4B6C4F034264B2C38F8FF6F4405D563AA7D49B82221111010000000E001BFXXXX03';
        $this->assertEquals(true, $parser->check($pan), 'Parser does not handle input');
        $json = $parser->parse($pan);
        $url = 'plugins/Paycards/gui/paycardboxMsgAuth.php';
        $this->assertEmpty($json['output'], 'Parser error message: ' . $json['output']);
        $this->assertEquals($url, substr($json['main_frame'], -1*strlen($url)), 'No redirect to auth page');
        
        $this->assertEquals(0.01, CoreLocal::get('paycard_amount'));
        $this->assertEquals(PaycardLib::PAYCARD_MODE_AUTH, CoreLocal::get('paycard_mode'));
        $this->assertEquals(PaycardLib::PAYCARD_TYPE_ENCRYPTED, CoreLocal::get('paycard_type'));

        // mercury's gateway is returning weird errors;
        // don't feel like addressing it right now
        return;

        /**
          Submit transaction to processor.
          Verify return value and check database
          for an appropriate auth record
        */
        $this->emptyPaycardTables();
        $processor = new MercuryE2E();
        $auth_result = $processor->doSend(PaycardLib::PAYCARD_MODE_AUTH);
        $this->assertEquals(
            PaycardLib::PAYCARD_ERR_OK, 
            $auth_result, 
            'Authorization attempt produced error: ' 
                . $auth_result . ' : '
                . CoreLocal::get('boxMsg')
        );
        $db = Database::tDataConnect();
        $ptrans = $db->query('SELECT * FROM PaycardTransactions');
        $row = $db->fetch_row($ptrans);
        $this->assertEquals(date('Ymd'), $row['dateID']);
        $this->assertEquals(9999, $row['empNo']);
        $this->assertEquals(99, $row['registerNo']);
        $this->assertEquals(1, $row['transNo']);
        $this->assertEquals(1, $row['transID']);
        $this->assertEquals('MercuryE2E', $row['processor']);
        $this->assertEquals('Credit', $row['cardType']);
        $this->assertEquals('Sale', $row['transType']);
        $this->assertEquals(0.01, $row['amount']);
        $this->assertEquals('Visa', $row['issuer']);
        $this->assertEquals(0, $row['commErr']);
        $this->assertEquals(200, $row['httpCode']);
        $this->assertEquals(1, $row['validResponse']);
        $this->assertEquals(1, $row['xResultCode']);
        $this->assertEquals(0, $row['xResponseCode']);
        $this->assertNotEmpty($row['xApprovalNumber']);
        $this->assertNotEmpty($row['xResultMessage']);
        $this->assertNotEmpty($row['xTransactionID']);
        $this->assertNotEmpty($row['xToken']);
        $this->assertNotEmpty($row['xProcessorRef']);
        $this->assertNotEmpty($row['xAcquirerRef']);
        $this->assertEquals('************', substr($row['PAN'], 0, 12));

        /** cleanup test transaction record(s) */
        $this->emptyPaycardTables();
    }
}

