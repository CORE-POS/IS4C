<?php
/**
 * @backupGlobals disabled
 */
class AdminLoginTest extends PHPUnit_Framework_TestCase
{
    public function testGeneric()
    {
        $classes = array(
            'COREPOS\\pos\\lib\\adminlogin\\AgeApproveAdminLogin' => '&repeat=1',
            'COREPOS\\pos\\lib\\adminlogin\\DDDAdminLogin' => '/ddd.php',
            'COREPOS\\pos\\lib\\adminlogin\\LineItemDiscountAdminLogin' => '&repeat=1',
            'COREPOS\\pos\\lib\\adminlogin\\MemStatusAdminLogin' => '/boxMsg2.php',
            'COREPOS\\pos\\lib\\adminlogin\\PriceOverrideAdminLogin' => '/priceOverride.php',
            'COREPOS\\pos\\lib\\adminlogin\\RefundAdminLogin' => '/refundComment.php',
            'COREPOS\\pos\\lib\\adminlogin\\SusResAdminLogin' => '/adminlist.php',
            'COREPOS\\pos\\lib\\adminlogin\\UndoAdminLogin' => '/undo.php',
            'COREPOS\\pos\\lib\\Tenders\\ManagerApproveTender' => '&repeat=1',
            'COREPOS\\pos\\lib\\Tenders\\StoreTransferTender' => '&repeat=1',
        );

        foreach ($classes as $class => $url) {
            $this->assertEquals(false, $class::adminLoginCallback(false));
            $this->assertEquals($url, substr($class::adminLoginCallback(true), -1*strlen($url)));
            list($str, $level) = $class::messageAndLevel();
            $this->assertInternalType('string', $str);
            $this->assertEquals(true, is_numeric($level));
        }

        CoreLocal::set('isMember', 0);
        CoreLocal::set('memType', 0);
        CoreLocal::set('boxMsg', '');
        CoreLocal::set('boxMsgButtons', '');
        CoreLocal::set('refundComment', '');
        CoreLocal::set('cashierAgeOveride', '');
        CoreLocal::set('approvetender', 0);
        CoreLocal::set('transfertender', 0);
    }

    public function testVoidAdminLogin()
    {
        $this->assertEquals(true, COREPOS\pos\parser\parse\VoidCmd::adminLoginCallback(true));
        $this->assertEquals(1, CoreLocal::get('voidOverride'));
        $this->assertEquals(1, CoreLocal::get('msgrepeat'));
        $this->assertEquals(false, COREPOS\pos\parser\parse\VoidCmd::adminLoginCallback(false));
        $this->assertEquals(0, CoreLocal::get('voidOverride'));
        list($str, $level) = COREPOS\pos\parser\parse\VoidCmd::messageAndLevel();
        $this->assertInternalType('string', $str);
        $this->assertEquals(true, is_numeric($level));
        CoreLocal::set('msgrepeat', 0);
    }
}

