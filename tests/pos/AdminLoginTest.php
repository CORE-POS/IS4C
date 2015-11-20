<?php
/**
 * @backupGlobals disabled
 */
class AdminLoginTest extends PHPUnit_Framework_TestCase
{
    public function testGeneric()
    {
        $classes = array(
            'AgeApproveAdminLogin' => '&repeat=1',
            'DDDAdminLogin' => '/ddd.php',
            'LineItemDiscountAdminLogin' => '&repeat=1',
            'MemStatusAdminLogin' => '/boxMsg2.php',
            'PriceOverrideAdminLogin' => '/priceOverride.php',
            'RefundAdminLogin' => '/refundComment.php',
            'SusResAdminLogin' => '/adminlist.php',
            'UndoAdminLogin' => '/undo.php',
            'ManagerApproveTender' => '&repeat=1',
            'StoreTransferTender' => '&repeat=1',
        );

        foreach ($classes as $class => $url) {
            $this->assertEquals(false, $class::adminLoginCallback(false));
            $this->assertEquals($url, substr($class::adminLoginCallback(true), -1*strlen($url)));
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
        $this->assertEquals(true, Void::adminLoginCallback(true));
        $this->assertEquals(1, CoreLocal::get('voidOverride'));
        $this->assertEquals(1, CoreLocal::get('msgrepeat'));
        $this->assertEquals(false, Void::adminLoginCallback(false));
        $this->assertEquals(0, CoreLocal::get('voidOverride'));
        CoreLocal::set('msgrepeat', 0);
    }
}

