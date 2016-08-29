<?php

use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TotalActions\TotalAction;

class InactiveMemTotalAction extends TotalAction
{
    public function apply()
    {
        if (CoreLocal::get('memberID') && in_array(CoreLocal::get('memberID'), CoreLocal::get('InactiveMemList')) && CoreLocal::get('InactiveMemApproval')) {
            return MiscLib::baseURL() . 'gui-modules/adminlogin.php?class=InactiveMemTotalAction';
        } else {
            return true;
        }
    }

    public static $adminLoginMsg = 'Approval Required';
    public static $adminLoginLevel = 30;

    public static function adminLoginInit()
    {
        $msg = 'Approval Required - #' . CoreLocal::get('memberID')
            . '<br />'
            . CoreLocal::get('fname') . ' ' . CoreLocal::get('lname')
            . '<br />'
            . CoreLocal::get('memMsg'); 
        self::$adminLoginMsg = $msg;
    }

    public static function adminLoginCallback($success)
    {
        if ($success) {
            CoreLocal::set('InactiveMemList', array());

            return true;
        } else {
            CoreLocal::set('InactiveMemList', array());
            COREPOS\pos\lib\MemberLib::clear();

            return false;
        }
    }
}

