<?php

use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DeptLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\parser\PreParser;
use COREPOS\pos\parser\Parser;
use COREPOS\pos\parser\PostParser;
use COREPOS\pos\parser\ParseResult;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;

include(dirname(__FILE__).'/../../pos/is4c-nf/parser-class-lib/PreParser.php');
include(dirname(__FILE__).'/../../pos/is4c-nf/parser-class-lib/Parser.php');
if (!class_exists('lttLib')) {
    include(dirname(__FILE__) . '/lttLib.php');
}

/**
 * @backupGlobals disabled
 */
class ParsersTest extends PHPUnit_Framework_TestCase
{

    public function testResult()
    {
        $res = new ParseResult();
        $this->assertNotEquals(false, strstr(json_encode($res), 'main_frame'));
    }

    /**
      Check methods for getting available PreParser and Parser modules
    */
    public function testStatics()
    {
        $session = new WrappedStorage();
        $chain = PreParser::get_preparse_chain();
        $this->assertInternalType('array',$chain);
        $this->assertNotEmpty($chain);
        foreach($chain as $class){
            $instance = new $class($session);
            $this->assertInstanceOf('COREPOS\\pos\\parser\\PreParser',$instance);
            // just for coverage; not vital functionality
            $this->assertNotEquals(0, strlen($instance->doc()));
        }

        $chain = Parser::get_parse_chain();
        $this->assertInternalType('array',$chain);
        $this->assertNotEmpty($chain);
        foreach($chain as $class){
            $instance = new $class($session);
            $this->assertInstanceOf('COREPOS\\pos\\parser\\Parser',$instance);
            // just for coverage; not vital functionality
            $this->assertNotEquals(0, strlen($instance->doc()));
        }
    }

    public function testPreParsers()
    {
        /* set any needed session variables */
        CoreLocal::set('runningTotal',1.99);
        CoreLocal::set('mfcoupon',0);
        CoreLocal::set('itemPD',0);
        CoreLocal::set('multiple',0);
        CoreLocal::set('quantity',0);
        CoreLocal::set('refund',0);
        CoreLocal::set('toggletax',0);
        CoreLocal::set('togglefoodstamp',0);
        CoreLocal::set('toggleDiscountable',0);
        CoreLocal::set('nd',0);
        $session = new WrappedStorage();
    
        /* inputs and expected outputs */
        $input_output = array(
            'MANUALCC'    => '199CC',
            '5DI123'    => '123',
            '7PD123'    => '123',
            '2*4011'    => '4011',
            '3*100DP10'    => '100DP10',
            'text*blah'    => 'text*blah',
            'RF123'        => '123',
            '123RF'        => '123',
            'RF3*100DP10'    => '100DP10',
            'FN123'        => '123',
            'DN123'        => '123',
            '1TN123'    => '123',
            'invalid'    => 'invalid',
            '123NR'     => '123',
            'NR123'     => '123',
            'CMWHARF'   => 'CMWHARF',
            'RF123VD'   => 'RF123VD',
        );

        $chain = PreParser::get_preparse_chain();
        foreach($input_output as $input => $output){
            foreach($chain as $class){
                $obj = new $class($session);
                $chk = $obj->check($input);
                $this->assertInternalType('boolean',$chk);
                if ($chk){
                    $input = $obj->parse($input);
                }
            }
            $this->assertEquals($output, $input);
        }

        /* verify correct session values */
        $this->assertEquals(7, CoreLocal::get('itemPD'));
        $this->assertEquals(1, CoreLocal::get('multiple'));
        $this->assertEquals(3, CoreLocal::get('quantity'));
        $this->assertEquals(1, CoreLocal::get('refund'));
        $this->assertEquals(1, CoreLocal::get('toggletax'));
        $this->assertEquals(1, CoreLocal::get('togglefoodstamp'));
        $this->assertEquals(1, CoreLocal::get('toggleDiscountable'));

        CoreLocal::set('itemPD', 0);
        CoreLocal::set('multiple', 0);
        CoreLocal::set('quantity', 0);
        CoreLocal::set('refund', 0);
        CoreLocal::set('toggletax', 0);
        CoreLocal::set('togglefoodstamp', 0);
        CoreLocal::set('toggleDiscountable', 0);
    }

    function testCcMenu()
    {
        $plugins = CoreLocal::get('PluginList');
        CoreLocal::set('PluginList', array('Paycards'), true);
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\preparse\CCMenu($session);
        $this->assertEquals(true, $obj->check('CC'));
        $this->assertEquals('QM1', $obj->parse('CC'));
        CoreLocal::set('PluginList', $plugins);
    }

    function testMemStatusToggle()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\preparse\MemStatusToggle($session);
        $this->assertEquals(false, $obj->check('foo'));
        $this->assertEquals('foo', $obj->parse('foo'));
    }

    function testParsers()
    {
        /* inputs and expected outputs */
        $input_output = array(
        'WillNotMatchAnythingEver' => array(),
        );
        $session = new WrappedStorage();

        $chain = Parser::get_parse_chain();
        foreach($input_output as $input => $output){
            $actual = $output;
            foreach($chain as $class){
                $obj = new $class($session);
                $chk = $obj->check($input);
                $this->assertInternalType('boolean',$chk, $class . ' returns non-boolean');
                if ($chk){
                    $actual = $obj->parse($input);
                    break;
                }
                $this->assertInternalType('string', $obj->doc());
            }
            $this->assertEquals($output, $actual);
        }
    }

    function testWakeup()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\Wakeup($session);
        $this->assertEquals(true, $obj->check('WAKEUP'));

        $out = $obj->parse('WAKEUP');
        $this->assertEquals('wakeup', $out['udpmsg']);
    }

    function testToggleReceipt()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\ToggleReceipt($session);
        CoreLocal::set('receiptToggle', 0);
        $obj->parse('NR');
        $this->assertEquals(1, CoreLocal::get('receiptToggle'));
        $out = $obj->parse('NR');
        $this->assertEquals(0, CoreLocal::get('receiptToggle'));
        $this->assertEquals('/pos2.php', substr($out['main_frame'], -9));
    }

    function testTotals()
    {
        $session = new WrappedStorage();
        $t = new COREPOS\pos\parser\parse\Totals($session);

        $out = $t->parse('FNTL');
        $this->assertEquals('/fsTotalConfirm.php', substr($out['main_frame'], -19));
        $out = $t->parse('TETL');
        $this->assertEquals('-Totals', substr($out['main_frame'], -7));
        lttLib::clear();
        CoreLocal::set('percentDiscount', 10);
        CoreLocal::set('fsTaxExempt', 1);
        $out = $t->parse('FTTL');
        $this->assertNotEquals(0, strlen($out['output']));
        $this->assertEquals(true, $out['redraw_footer']);
        CoreLocal::set('percentDiscount', 0);
        CoreLocal::set('fsTaxExempt', 0);
        lttLib::clear();
        $out = $t->parse('TL');
        $this->assertEquals('/memlist.php', substr($out['main_frame'], -12));
        lttLib::clear();
        COREPOS\pos\lib\MemberLib::setMember(1, 1);
        $out = $t->parse('TL');
        $this->assertNotEquals(0, strlen($out['output']));
        $this->assertEquals(true, $out['redraw_footer']);
        lttLib::clear();
        $out = $t->parse('WICTL');
        $this->assertNotEquals(0, strlen($out['output']));
        $this->assertEquals(true, COREPOS\pos\parser\parse\Totals::requestInfoCallback('1234'));
        lttLib::clear();

        // just for coverage of omtr_ttl 
        $out = $t->parse('MTL');
        lttLib::clear();
    }

    function testTenderOut()
    {
        $session = new WrappedStorage();
        $to = new COREPOS\pos\parser\parse\TenderOut($session);
        $this->assertEquals(true, $to->check('TO'));

        CoreLocal::set('LastID', 0);
        $out = $to->parse('TO');
        $this->assertNotEquals(0, strlen($out['output']));

        lttLib::clear();
        $lib = new DeptLib($session);
        $lib->deptkey(10, 100);
        $out = $to->parse('TO');
        $this->assertNotEquals(0, strlen($out['output']));

        lttLib::clear();
        CoreLocal::set('amtdue', 0);
        $out = $to->parse('TO');
        $this->assertEquals(1, CoreLocal::get('End'));
        $this->assertEquals('full', $out['receipt']);
        CoreLocal::set('End', 0);
        $dbc = Database::tDataConnect();
        $dbc->query('TRUNCATE TABLE localtranstoday');
    }

    function testTenderKey()
    {
        $session = new WrappedStorage();
        $tk = new COREPOS\pos\parser\parse\TenderKey($session);
        $this->assertEquals(true, $tk->check('TT'));
        $out = $tk->parse('TT');
        $this->assertNotEquals(false, strstr($out['main_frame'], '/tenderlist.php'));

        $this->assertEquals(true, $tk->check('100TT'));
        $out = $tk->parse('100TT');
        $this->assertNotEquals(false, strstr($out['main_frame'], '/tenderlist.php'));
    }

    function testSigTermCommands()
    {
        $session = new WrappedStorage();
        $st = new COREPOS\pos\parser\parse\SigTermCommands($session);

        $this->assertEquals(true, $st->check('TERMAUTOENABLE'));
        $this->assertEquals('direct', CoreLocal::get('PaycardsStateChange'));
        $this->assertEquals(true, $st->check('TERMAUTODISABLE'));
        $this->assertEquals('coordinated', CoreLocal::get('PaycardsStateChange'));

        $this->assertEquals(true, $st->check('TERMMANUAL'));
        $this->assertEquals(true, CoreLocal::get('paycard_keyed'));

        $this->assertEquals(true, $st->check('PANCACHE:FAKE'));
        CoreLocal::set('PaycardsAllowEBT', 1);
        $this->assertEquals(true, $st->check('PANCACHE:FAKE'));
        $this->assertEquals('FAKE', CoreLocal::get('CachePanEncBlock'));

        $this->assertEquals(true, $st->check('PINCACHE:1234'));
        $this->assertEquals('1234', CoreLocal::get('CachePinEncBlock'));
        $this->assertEquals('ready', CoreLocal::get('ccTermState'));

        $this->assertEquals(true, $st->check('TERM:CREDIT'));
        $this->assertEquals('CREDIT', CoreLocal::get('CacheCardType'));
        $this->assertEquals('ready', CoreLocal::get('ccTermState'));
        $this->assertEquals(true, $st->check('TERM:DEBIT'));
        $this->assertEquals('DEBIT', CoreLocal::get('CacheCardType'));
        $this->assertEquals('pin', CoreLocal::get('ccTermState'));
        CoreLocal::set('PaycardsOfferCashBack', 1);
        $this->assertEquals(true, $st->check('TERM:DEBIT'));
        $this->assertEquals('DEBIT', CoreLocal::get('CacheCardType'));
        $this->assertEquals('cashback', CoreLocal::get('ccTermState'));
        CoreLocal::set('PaycardsOfferCashBack', 2);
        CoreLocal::set('isMember', 0);
        $this->assertEquals(true, $st->check('TERM:DEBIT'));
        $this->assertEquals('DEBIT', CoreLocal::get('CacheCardType'));
        $this->assertEquals('pin', CoreLocal::get('ccTermState'));
        CoreLocal::set('isMember', 1);
        $this->assertEquals(true, $st->check('TERM:DEBIT'));
        $this->assertEquals('DEBIT', CoreLocal::get('CacheCardType'));
        $this->assertEquals('cashback', CoreLocal::get('ccTermState'));
        CoreLocal::set('isMember', 0);
        CoreLocal::set('PaycardsOfferCashBack', '');
        $this->assertEquals(true, $st->check('TERM:EBTFOOD'));
        $this->assertEquals('EBTFOOD', CoreLocal::get('CacheCardType'));
        $this->assertEquals('pin', CoreLocal::get('ccTermState'));
        $this->assertEquals(true, $st->check('TERM:EBTCASH'));
        $this->assertEquals('EBTCASH', CoreLocal::get('CacheCardType'));
        $this->assertEquals('pin', CoreLocal::get('ccTermState'));
        CoreLocal::set('PaycardsOfferCashBack', 1);
        $this->assertEquals(true, $st->check('TERM:EBTCASH'));
        $this->assertEquals('EBTCASH', CoreLocal::get('CacheCardType'));
        $this->assertEquals('cashback', CoreLocal::get('ccTermState'));
        CoreLocal::set('PaycardsOfferCashBack', '');

        $this->assertEquals(true, $st->check('VAUTH:1234'));
        $this->assertEquals('1234', CoreLocal::get('paycard_voiceauthcode'));
        $this->assertEquals(true, $st->check('EBTAUTH:1234'));
        $this->assertEquals('1234', CoreLocal::get('ebt_authcode'));
        $this->assertEquals(true, $st->check('EBTV:1234'));
        $this->assertEquals('1234', CoreLocal::get('ebt_vnum'));

        $this->assertEquals(true, $st->check('TERMCB:99999'));
        $out = $st->parse('TERMCB:99999');
        $this->assertNotEquals(0, strlen($out['output']));
        $this->assertEquals(true, $st->check('TERMCB:0'));
        $out = $st->parse('TERMCB:0');
        $this->assertEquals(0, strlen($out['output']));

        $this->assertEquals(true, $st->check('CCFROMCACHE'));
        $out = $st->parse('CCFROMCACHE');
        $this->assertEquals('FAKE', $out['retry']);

        foreach (array('TERMRESET', 'TERMREBOOT', 'TERMCLEARALL') as $cmd) {
            CoreLocal::set('CachePanEncBlock', 'FAKE');
            CoreLocal::set('ccTermState', 'FOO');
            $this->assertEquals(true, $st->check($cmd));
            $this->assertEquals('', CoreLocal::get('CachePanEncBlock'));
            $this->assertEquals('swipe', CoreLocal::get('ccTermState'));
        }
    }

    function testReceiptCoupon()
    {
        $session = new WrappedStorage();
        $rc = new COREPOS\pos\parser\parse\ReceiptCoupon($session);
        $one = 'RC9901001'; // expire 2099-01-01
        $two = 'RC0001001'; // expire 2000-01-01
        $this->assertEquals(true, $rc->check($one));
        $this->assertEquals(true, $rc->check($two));
        $out = $rc->parse($one);
        $this->assertNotEquals(0, strlen($out['output']));
        $out = $rc->parse($two);
        $this->assertNotEquals(0, strlen($out['output']));
    }

    function testEndOfShift()
    {
        $session = new WrappedStorage();
        $e = new COREPOS\pos\parser\parse\EndOfShift($session);
        $this->assertEquals(true, $e->check('ES'));
        $out = $e->parse('ES');
        lttLib::clear();
        CoreState::transReset();
        $dbc = Database::tDataConnect();
        $dbc->query('TRUNCATE TABLE localtranstoday');
    }

    function testSteering()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\Steering($session);

        CoreLocal::set('LastID', 1);
        $obj->check('CAB');
        $out = $obj->parse('CAB');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('LastID', 0);
        $obj->check('CAB');
        $out = $obj->parse('CAB');
        $this->assertEquals('/cablist.php', substr($out['main_frame'], -12));

        $obj->check('PVASDF');
        $out = $obj->parse('PVASDF');
        $this->assertEquals('/productlist.php?search=ASDF', substr($out['main_frame'], -28));
        $obj->check('TESTPV');
        $out = $obj->parse('TESTPV');
        $this->assertEquals('/productlist.php?search=TEST', substr($out['main_frame'], -28));

        CoreLocal::set('LastID', 1);
        $obj->check('UNDO');
        $out = $obj->parse('UNDO');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('LastID', 0);
        $obj->check('UNDO');
        $out = $obj->parse('UNDO');
        $this->assertEquals('-UndoAdminLogin', substr($out['main_frame'], -15));

        $obj->check('SK');
        $out = $obj->parse('SK');
        $this->assertEquals('/DDDReason.php', substr($out['main_frame'], -14));
        $obj->check('DDD');
        $out = $obj->parse('DDD');
        $this->assertEquals('/DDDReason.php', substr($out['main_frame'], -14));

        CoreLocal::set('SecuritySR', 21, true);
        $obj->check('MG');
        $out = $obj->parse('MG');
        $this->assertEquals('-SusResAdminLogin', substr($out['main_frame'], -17));
        CoreLocal::set('SecuritySR', 0, true);
        $obj->check('MG');
        $out = $obj->parse('MG');
        $this->assertEquals('/adminlist.php', substr($out['main_frame'], -14));

        CoreLocal::set('LastID', 1);
        CoreLocal::set('receiptToggle', 0);
        $obj->check('RP');
        $out = $obj->parse('RP');
        $this->assertEquals(1, CoreLocal::get('receiptToggle'));
        $this->assertEquals('/pos2.php', substr($out['main_frame'], -9));
        $obj->check('RP');
        $out = $obj->parse('RP');
        $this->assertEquals(0, CoreLocal::get('receiptToggle'));
        $this->assertEquals('/pos2.php', substr($out['main_frame'], -9));
        CoreLocal::set('LastID', 0);
        $obj->check('RP');
        $out = $obj->parse('RP');
        $this->assertNotEquals(0, strlen($out['output']));

        $obj->check('ID');
        $out = $obj->parse('ID');
        $this->assertEquals('/memlist.php', substr($out['main_frame'], -12));

        $obj->check('DDM');
        $out = $obj->parse('DDM');
        $this->assertEquals('/drawerPage.php', substr($out['main_frame'], -15));

        CoreLocal::set('LastID', 1);
        $obj->check('NS');
        $out = $obj->parse('NS');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('LastID', 0);
        $obj->check('NS');
        $out = $obj->parse('NS');
        $this->assertEquals('/nslogin.php', substr($out['main_frame'], -12));

        $obj->check('GD');
        $out = $obj->parse('GD');
        $this->assertEquals('/giftcardlist.php', substr($out['main_frame'], -17));

        $obj->check('IC');
        $out = $obj->parse('IC');
        $this->assertEquals('/HouseCouponList.php', substr($out['main_frame'], -20));

        $obj->check('CN');
        $out = $obj->parse('CN');
        $this->assertEquals('/mgrlogin.php', substr($out['main_frame'], -13));

        $obj->check('PO');
        $out = $obj->parse('PO');
        $this->assertEquals('-PriceOverrideAdminLogin', substr($out['main_frame'], -24));

        CoreLocal::set('memType', 1);
        $obj->check('MSTG');
        $out = $obj->parse('MSTG');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('memType', 0);
        CoreLocal::set('SecuritySR', 21, true);
        $obj->check('MSTG');
        $out = $obj->parse('MSTG');
        $this->assertEquals('-MemStatusAdminLogin', substr($out['main_frame'], -20));
        CoreLocal::set('SecuritySR', 0, true);
        $obj->check('MSTG');
        $out = $obj->parse('MSTG');
        $this->assertNotEquals(0, strlen($out['output']));

        CoreLocal::set('LastID', 1);
        $obj->check('SS');
        $out = $obj->parse('SS');
        $this->assertNotEquals(0, strlen($out['output']));
        $obj->check('SO');
        $out = $obj->parse('SO');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('LastID', 0);
        CoreLocal::set('LoggedIn', 1);
        $obj->check('SS');
        $out = $obj->parse('SS');
        $this->assertEquals('login.php', substr($out['main_frame'], -9));
        $this->assertEquals(0, CoreLocal::get('LoggedIn'));
        CoreLocal::set('LastID', 0);
        CoreLocal::set('LoggedIn', 1);
        $obj->check('SO');
        $out = $obj->parse('SO');
        $this->assertEquals('login.php', substr($out['main_frame'], -9));
        $this->assertEquals(0, CoreLocal::get('LoggedIn'));
        // may have created log records when signing out
        lttLib::clear();
    }

    function testStackableDiscount()
    {
        $session = new WrappedStorage();
        $sd = new COREPOS\pos\parser\parse\StackableDiscount($session);
        $this->assertEquals(false, $sd->check('ZSD'));
        CoreLocal::set('tenderTotal', 1);
        $this->assertEquals(true, $sd->check('100SD'));
        $out = $sd->parse('100SD');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('tenderTotal', 0);
        $this->assertEquals(true, $sd->check('100SD'));
        $out = $sd->parse('100SD');
        $this->assertNotEquals(0, strlen($out['output']));
        $this->assertEquals(true, $sd->check('-1SD'));
        $out = $sd->parse('-1SD');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('percentDiscount', 5);
        $this->assertEquals(true, $sd->check('5SD'));
        $out = $sd->parse('5SD');
        $this->assertEquals(10, CoreLocal::get('percentDiscount'));
        CoreLocal::set('percentDiscount', 0);
        // may have subtotal'd
        lttLib::clear();
    }

    function testScrollItems()
    {
        $inputs = array('D', 'U', 'D5', 'U5');
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\ScrollItems($session);
        foreach ($inputs as $input) {
            $this->assertEquals(true, $obj->check($input));
            $out = $obj->parse($input);
            $this->assertNotEquals(0, strlen($out['output']));
        }
    }

    function testScaleInput()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\ScaleInput($session);
        $out = $obj->parse('S111234');
        $this->assertEquals(1, CoreLocal::get('scale'));
        $this->assertEquals(12.34, CoreLocal::get('weight'));
        $this->assertEquals('S111234', $out['scale']);

        $out = $obj->parse('S143');
        $this->assertEquals(0, CoreLocal::get('scale'));
        $this->assertEquals('S143', $out['scale']);
    }

    function testRepeatKey()
    {
        $session = new WrappedStorage();
        $rk = new COREPOS\pos\parser\parse\RepeatKey($session);
        $this->assertEquals(true, $rk->check('*'));
        $this->assertEquals(true, $rk->check('*2'));
        $dbc = Database::tDataConnect();
        $upc = new COREPOS\pos\parser\parse\UPC($session);
        lttLib::clear();
        $out = $rk->parse('*');
        $this->assertNotEquals(0, strlen($out['output']));
        $upc->parse('666');
        $out = $rk->parse('*');
        $this->assertNotEquals(0, strlen($out['output']));
        $query = 'SELECT * FROM localtemptrans WHERE upc=\'0000000000666\'';
        $res = $dbc->query($query);
        $this->assertEquals(2, $dbc->numRows($res));
        lttLib::clear();
        $upc->parse('666');
        $out = $rk->parse('*2');
        $this->assertNotEquals(0, strlen($out['output']));
        $query = 'SELECT * FROM localtemptrans WHERE upc=\'0000000000666\'';
        $res = $dbc->query($query);
        $this->assertEquals(2, $dbc->numRows($res));
        $prep = $dbc->prepare('SELECT SUM(quantity) FROM localtemptrans WHERE upc=\'0000000000666\'');
        $this->assertEquals(3, $dbc->getValue($prep));
        lttLib::clear();
    }

    function testRRR()
    {
        $session = new WrappedStorage();
        $r = new COREPOS\pos\parser\parse\RRR($session);
        $this->assertEquals(true, $r->check('RRR'));
        $this->assertEquals(true, $r->check('2*RRR'));
        CoreLocal::set('LastID', 0);
        $out = $r->parse('RRR');
        $this->assertNotEquals(0, strlen($out['output']));
        $out = $r->parse('2*RRR');
        $this->assertNotEquals(0, strlen($out['output']));
        lttLib::clear();
    }

    function testPartialReceipt()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\PartialReceipt($session);
        $out = $obj->parse('PP');
        $this->assertEquals('partial', $out['receipt']);
        $this->assertNotEquals(0, strlen($out['output']));
    }

    function testMemberID()
    {
        $session = new WrappedStorage();
        $m = new COREPOS\pos\parser\parse\MemberID($session);
        $this->assertEquals(true, $m->check('1ID'));
        $out = $m->parse('0ID');
        $this->assertNotEquals(0, strlen($out['output']));
        $this->assertEquals(true, $out['redraw_footer']);
        $this->assertEquals('0', CoreLocal::get('memberID'));
        CoreLocal::set('verifyName', 1);
        $out = $m->parse('1ID');
        $this->assertEquals('/memlist.php?idSearch=1', substr($out['main_frame'], -23));
        CoreLocal::set('memberID', 1);
        CoreLocal::set('defaultNonMem', 99999, true);
        CoreLocal::set('RestrictDefaultNonMem', 1, true);
        $out = $m->parse('99999ID');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreState::memberReset();
        $out = $m->parse('99999ID');
        $this->assertEquals('/memlist.php?idSearch=99999', substr($out['main_frame'], -27));
        CoreLocal::set('RestrictDefaultNonMem', 0);
        CoreState::memberReset();
    }

    function testLock()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\Lock($session);
        $out = $obj->parse('LOCK');
        $this->assertEquals('/login3.php', substr($out['main_frame'], -11));
    }

    function testDonationKey()
    {
        $session = new WrappedStorage();
        $d = new COREPOS\pos\parser\parse\DonationKey($session);
        CoreLocal::set('roundUpDept', 1, true);
        $this->assertEquals(true, $d->check('RU'));
        $this->assertEquals(true, $d->check('2RU'));
        $dbc = Database::tDataConnect();
        lttLib::clear();
        $out = $d->parse('RU');
        $prep = $dbc->prepare('SELECT SUM(total) FROM localtemptrans');
        $this->assertEquals(1, $dbc->getValue($prep));
        lttLib::clear();
        $out = $d->parse('200RU');
        $prep = $dbc->prepare('SELECT SUM(total) FROM localtemptrans');
        $this->assertEquals(2, $dbc->getValue($prep));
        CoreLocal::set('roundUpDept', '');
        $d->parse('RU');
        lttLib::clear();
    }

    function testDiscountApplied()
    {
        $session = new WrappedStorage();
        $sd = new COREPOS\pos\parser\parse\DiscountApplied($session);
        $this->assertEquals(false, $sd->check('ZDA'));
        CoreLocal::set('tenderTotal', 1);
        $this->assertEquals(true, $sd->check('100DA'));
        $out = $sd->parse('100DA');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('tenderTotal', 0);
        $this->assertEquals(true, $sd->check('100DA'));
        $out = $sd->parse('100DA');
        $this->assertNotEquals(0, strlen($out['output']));
        $this->assertEquals(true, $sd->check('-1DA'));
        $out = $sd->parse('-1DA');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('percentDiscount', 4);
        $this->assertEquals(true, $sd->check('5DA'));
        $out = $sd->parse('5DA');
        $this->assertEquals(5, CoreLocal::get('percentDiscount'));
        CoreLocal::set('percentDiscount', 0);
        // may have subtotal'd
        lttLib::clear();
    }

    function testDeptKey()
    {
        $session = new WrappedStorage();
        $d = new COREPOS\pos\parser\parse\DeptKey($session);
        CoreLocal::set('refund', 1);
        CoreLocal::set('SpecialDeptMap', false);
        $out = $d->parse('1.00DP');
        $this->assertNotEquals(false, strstr($out['main_frame'], '/deptlist.php'));
        $this->assertInternalType('array', CoreLocal::get('SpecialDeptMap'));
        CoreLocal::set('refundComment', '');
        CoreLocal::set('SecurityRefund', 21, true);
        $out = $d->parse('100DP10');
        $this->assertEquals('-RefundAdminLogin', substr($out['main_frame'], -17));
        CoreLocal::set('SecurityRefund', 0, true);
        $out = $d->parse('100DP10');
        $this->assertEquals('/refundComment.php', substr($out['main_frame'], -18));
        CoreLocal::set('refund', 0);
        CoreLocal::set('SpecialDeptMap', array(1 => array('AutoReprintDept')));
        $out = $d->parse('100DP10');
        $this->assertEquals(1, CoreLocal::get('autoReprint'));
        CoreLocal::set('SpecialDeptMap', false);
        CoreLocal::set('autoReprint', 0);
    }

    function testBalanceCheck()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\BalanceCheck($session);
        $this->assertEquals(true, $obj->check('BQ'));
        $out = $obj->parse('BQ');
        $this->assertNotEquals(0, strlen($out['output']));
    }

    function testComment()
    {
        $session = new WrappedStorage();
        $cm = new COREPOS\pos\parser\parse\Comment($session);
        lttLib::clear();
        $out = $cm->parse('CMTEST');
        $dbc = Database::tDataConnect();
        $prep = $dbc->prepare('SELECT description FROM localtemptrans');
        $this->assertEquals('TEST', $dbc->getValue($prep));
        $out = $cm->parse('CM');
        $this->assertEquals('/bigComment.php', substr($out['main_frame'], -15));
        lttLib::clear();
    }

    function testClear()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\Clear($session);
        $out = $obj->parse('CL');
        $this->assertEquals(0, CoreLocal::get('msgrepeat'));
        $this->assertEquals('', CoreLocal::get('strRemembered'));
        $this->assertEquals(0, CoreLocal::get('SNR'));
        $this->assertEquals(0, CoreLocal::get('refund'));
        $this->assertEquals('/pos2.php', substr($out['main_frame'], -9));
    }

    function testCheckKey()
    {
        $session = new WrappedStorage();
        $obj = new COREPOS\pos\parser\parse\CheckKey($session);
        $out = $obj->parse('100CQ');
        $this->assertNotEquals(false, strstr($out['main_frame'], '/checklist.php'));
    }

    function testAutoTare()
    {
        $session = new WrappedStorage();
        $tare = new COREPOS\pos\parser\parse\AutoTare($session);
        $this->assertEquals(true, $tare->check('TW'));
        $this->assertEquals(true, $tare->check('5TW'));
        CoreLocal::set('weight', 0);
        $out = $tare->parse('TW');
        $this->assertEquals(0.01, CoreLocal::get('tare'));
        $out = $tare->parse('100TW');
        $this->assertEquals(1, CoreLocal::get('tare'));
        $out = $tare->parse('10000TW');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('weight', 0.5);
        $out = $tare->parse('100TW');
        $this->assertNotEquals(0, strlen($out['output']));
        CoreLocal::set('weight', 0);
        CoreLocal::set('tare', 0);
        lttLib::clear();
    }

    function testItemsEntry()
    {
        $session = new WrappedStorage();
        CoreLocal::set('mfcoupon',0);
        CoreLocal::set('itemPD',0);
        CoreLocal::set('multiple',0);
        CoreLocal::set('quantity',0);
        CoreLocal::set('refund',0);
        CoreLocal::set('toggletax',0);
        CoreLocal::set('togglefoodstamp',0);
        CoreLocal::set('toggleDiscountable',0);
        CoreLocal::set('nd',0);
        CoreLocal::set('memType', 0);

        // test regular price item
        lttLib::clear();
        $u = new COREPOS\pos\parser\parse\UPC($session);
        $this->assertEquals(true, $u->check('666'));
        $json = $u->parse('666');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000666';
        $record['description'] = 'EXTRA BAG';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 13;
        $record['tax'] = 1;
        $record['quantity'] = 1;
        $record['unitPrice'] = 0.05;
        $record['total'] = 0.05;
        $record['regPrice'] = 0.05;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd($session);
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);

        // test quantity multiplier
        lttLib::clear();
        CoreLocal::set('quantity', 2);
        CoreLocal::set('multiple', 1);
        $u = new COREPOS\pos\parser\parse\UPC($session);
        $this->assertEquals(true, $u->check('666'));
        $json = $u->parse('666');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000666';
        $record['description'] = 'EXTRA BAG';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 13;
        $record['tax'] = 1;
        $record['quantity'] = 2;
        $record['unitPrice'] = 0.05;
        $record['total'] = 0.10;
        $record['regPrice'] = 0.05;
        $record['ItemQtty'] = 2;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd($session);
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);

        // test refund
        lttLib::clear();
        CoreLocal::set('quantity', 0);
        CoreLocal::set('multiple', 0);
        CoreLocal::set('refund', 1);
        CoreLocal::set('refundComment', 'TEST REFUND');
        $u = new COREPOS\pos\parser\parse\UPC($session);
        $this->assertEquals(true, $u->check('666'));
        $json = $u->parse('666');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000666';
        $record['description'] = 'EXTRA BAG';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['trans_status'] = 'R';
        $record['department'] = 13;
        $record['tax'] = 1;
        $record['quantity'] = -1;
        $record['unitPrice'] = 0.05;
        $record['total'] = -0.05;
        $record['regPrice'] = 0.05;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd($session);
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);

        // test sale item
        lttLib::clear();
        CoreLocal::set('refund', 0);
        CoreLocal::set('refundComment', '');
        $u = new COREPOS\pos\parser\parse\UPC($session);
        $this->assertEquals(true, $u->check('4627'));
        $json = $u->parse('4627');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000004627';
        $record['description'] = 'PKALE';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 513;
        $record['foodstamp'] = 1;
        $record['discounttype'] = 1;
        $record['discountable'] = 1;
        $record['quantity'] = 1;
        $record['cost'] = 1.30;
        $record['unitPrice'] = 1.99;
        $record['total'] = 1.99;
        $record['regPrice'] = 2.29;
        $record['discount'] = 0.30;
        $record['ItemQtty'] = 1;
        $record['mixMatch'] = '943';
        lttLib::verifyRecord(1, $record, $this);
        $drecord = lttLib::genericRecord();
        $drecord['description'] = '** YOU SAVED $0.30 **';
        $drecord['trans_type'] = 'I';
        $drecord['department'] = 513;
        $drecord['trans_status'] = 'D';
        $drecord['voided'] = 2;
        lttLib::verifyRecord(2, $drecord, $this);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd($session);
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record['total'] *= -1;
        $record['cost'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        $record['discount'] *= -1;
        lttLib::verifyRecord(3, $record, $this);

        // test member sale
        lttLib::clear();
        CoreLocal::set('isMember', 1);
        $u = new COREPOS\pos\parser\parse\UPC($session);
        $this->assertEquals(true, $u->check('0003049488122'));
        $json = $u->parse('0003049488122');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0003049488122';
        $record['description'] = 'MINERAL WATER';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 188;
        $record['foodstamp'] = 1;
        $record['discounttype'] = 2;
        $record['discountable'] = 1;
        $record['quantity'] = 1;
        $record['cost'] = 2.06;
        $record['unitPrice'] = 2.49;
        $record['total'] = 2.49;
        $record['regPrice'] = 3.15;
        $record['memDiscount'] = 0.66;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        $drecord = lttLib::genericRecord();
        $drecord['description'] = '** YOU SAVED $0.66 **';
        $drecord['trans_type'] = 'I';
        $drecord['department'] = 188;
        $drecord['trans_status'] = 'D';
        $drecord['voided'] = 2;
        lttLib::verifyRecord(2, $drecord, $this);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd($session);
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record['total'] *= -1;
        $record['cost'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        $record['memDiscount'] *= -1;
        lttLib::verifyRecord(3, $record, $this);

        // test member sale as non-member
        lttLib::clear();
        CoreLocal::set('isMember', 0);
        $u = new COREPOS\pos\parser\parse\UPC($session);
        $this->assertEquals(true, $u->check('0003049488122'));
        $json = $u->parse('0003049488122');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '0003049488122';
        $record['description'] = 'MINERAL WATER';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['department'] = 188;
        $record['foodstamp'] = 1;
        $record['discounttype'] = 2;
        $record['discountable'] = 1;
        $record['quantity'] = 1;
        $record['cost'] = 2.06;
        $record['unitPrice'] = 3.15;
        $record['total'] = 3.15;
        $record['regPrice'] = 3.15;
        $record['memDiscount'] = 0.66;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd($session);
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record['total'] *= -1;
        $record['cost'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        $record['memDiscount'] *= -1;
        lttLib::verifyRecord(2, $record, $this);
    }

    function testOpenRings()
    {
        $session = new WrappedStorage();
        lttLib::clear();
        $d = new COREPOS\pos\parser\parse\DeptKey($session);
        $this->assertEquals(true, $d->check('100DP10'));
        $json = $d->parse('100DP10');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '1DP1';
        $record['description'] = 'BBAKING';
        $record['trans_type'] = 'D';
        $record['department'] = 1;
        $record['quantity'] = 1;
        $record['foodstamp'] = 1;
        $record['unitPrice'] = 1.00;
        $record['total'] = 1.00;
        $record['regPrice'] = 1.00;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd($session);
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);

        lttLib::clear();
        CoreLocal::set('refund', 1);
        CoreLocal::set('refundComment', 'TEST REFUND');
        $d = new COREPOS\pos\parser\parse\DeptKey($session);
        $this->assertEquals(true, $d->check('100DP10'));
        $json = $d->parse('100DP10');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record = lttLib::genericRecord();
        $record['upc'] = '1DP1';
        $record['description'] = 'BBAKING';
        $record['trans_type'] = 'D';
        $record['trans_status'] = 'R';
        $record['department'] = 1;
        $record['quantity'] = -1;
        $record['foodstamp'] = 1;
        $record['unitPrice'] = 1.00;
        $record['total'] = -1.00;
        $record['regPrice'] = 1.00;
        $record['ItemQtty'] = 1;
        lttLib::verifyRecord(1, $record, $this);
        CoreLocal::set('currentid', 1);
        $v = new COREPOS\pos\parser\parse\VoidCmd($session);
        $this->assertEquals(true, $v->check('VD'));
        $json = $v->parse('VD');
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $json);
        $record['total'] *= -1;
        $record['quantity'] *= -1;
        $record['ItemQtty'] *= -1;
        $record['voided'] = 1;
        $record['trans_status'] = 'V';
        lttLib::verifyRecord(2, $record, $this);
    }

    /**
      Add a taxable, non-foodstampable item record,
      shift it, and verify
    */
    function testTaxFoodShift()
    {
        $session = new WrappedStorage();
        if (!class_exists('lttLib')) {
            include (dirname(__FILE__) . '/lttLib.php');
        }
        lttLib::clear();
        $upc = new COREPOS\pos\parser\parse\UPC($session);
        $upc->parse('0000000000111');
        $record = lttLib::genericRecord();
        $record['upc'] = '0000000000111';
        $record['description'] = 'WYNDMERE 5-8 DRAM BOTTLE';
        $record['trans_type'] = 'I';
        $record['trans_subtype'] = 'NA';
        $record['trans_status'] = '';
        $record['department'] = 103;
        $record['quantity'] = 1;
        $record['ItemQtty'] = 1;
        $record['unitPrice'] = 1.65;
        $record['total'] = 1.65;
        $record['regPrice'] = 1.65;
        $record['tax'] = 1;
        $record['discountable'] = 1;
        $record['mixMatch'] = '499';
        lttLib::verifyRecord(1, $record, $this);
        $tfs = new COREPOS\pos\parser\parse\TaxFoodShift($session);
        $tfs->parse('TFS');
        $record['tax'] = 0;
        $record['foodstamp'] = 1;
        lttLib::verifyRecord(1, $record, $this);

        lttLib::clear();
    }

    function testLineItemDiscount()
    {
        $session = new WrappedStorage();
        $ld = new COREPOS\pos\parser\parse\LineItemDiscount($session);
        $this->assertEquals(true, $ld->check('LD'));
        $ld->parse('LD');
        $upc = new COREPOS\pos\parser\parse\UPC($session);
        $upc->parse('111');
        $ld->parse('LD');
        TransRecord::addtender('tender', 'TT', 1);
        $ld->parse('LD');
        lttLib::clear();
    }

    function testDefaultTender()
    {
        $session = new WrappedStorage();
        $t = new COREPOS\pos\parser\parse\DefaultTender($session);
        $this->assertEquals(true, $t->check('123ZZ'));
        $this->assertEquals(true, $t->check('CA'));
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $t->parse('CA'));
        $d = new COREPOS\pos\parser\parse\DeptKey($session);
        $d->parse('100DP10'); // avoid ending transaction
        $this->assertInternalType('array', $t->parse('1CA'));
        lttLib::clear();
    }

    function testUPC()
    {
        $session = new WrappedStorage();
        $u = new COREPOS\pos\parser\parse\UPC($session);
        foreach (array(COREPOS\pos\parser\parse\UPC::SCANNED_PREFIX, COREPOS\pos\parser\parse\UPC::MACRO_PREFIX, COREPOS\pos\parser\parse\UPC::HID_PREFIX, COREPOS\pos\parser\parse\UPC::GS1_PREFIX) as $prefix) {
            $this->assertEquals(true, $u->check($prefix . '4011'));
        }
        $scaleUPC = '0XA0020121000199';
        $u->parse($scaleUPC);

        $weighUPC = '4011';
        // trigger wait-for-scale message
        $u->parse($weighUPC);
        CoreLocal::set('weight', 1);
        CoreLocal::set('tare', 1.05);
        // trigger invalid tare message
        $u->parse($weighUPC);
        // add weight item
        CoreLocal::set('tare', 0.05);
        $u->parse($weighUPC);
        CoreLocal::set('lastWeight', 1);
        $weighUPC = 'GS1~0010000000004011';
        // trigger same-last-weight and cover GS1 prefix removal
        $u->parse($weighUPC);
        CoreLocal::set('weight', 0);
        CoreLocal::set('lastWeight', 0);
        CoreLocal::set('tare', 0.00);

        $upce = array(
            '0991230' => '09900000123',
            '0991231' => '09910000123',
            '0991232' => '09920000123',
            '0999123' => '09990000012',
            '0999914' => '09999000001',
            '0999995' => '09999900005',
        );
        foreach ($upce as $e => $a) {
            $this->assertEquals($a, $u->expandUPCE($e));
        }

        $this->assertEquals(false, COREPOS\pos\parser\parse\UPC::requestInfoCallback('foo'));
        $this->assertNotEquals(false, COREPOS\pos\parser\parse\UPC::requestInfoCallback('20000101'));

        // cover item-not-found
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $u->parse('0041234512345'));
        CoreLocal::set('tare', 0.05);
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $u->parse('0XA0041234512345'));
        CoreLocal::set('tare', 0.00);

        lttLib::clear();
    }

    function testDriverStatus()
    {
        $session = new WrappedStorage();
        $ds = new COREPOS\pos\parser\parse\DriverStatus($session);
        $this->assertEquals(true, $ds->check('POS'));
        $this->assertInstanceOf('COREPOS\\pos\\parser\\ParseResult', $ds->parse('POS'));
    }

    // mostly for coverage's sake
    function testBaseClasses()
    {
        $session = new WrappedStorage();
        $parser = new Parser($session);
        $pre = new PreParser($session);
        $post = new PostParser();

        $pre->check('');
        $pre->parse('');
        $pre->doc();

        $this->assertEquals(array(), $post->parse(array()));
        $this->assertInternalType('array', PostParser::getPostParseChain());

        $parser->check('');
        $parser->parse('');
    }
}
