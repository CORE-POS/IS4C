<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

use COREPOS\pos\lib\FormLib;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class AjaxEndorse extends AjaxCallback
{
    protected $encoding = 'plain';

    public function ajax($input=array())
    {
        $endorseType = FormLib::get('type', '');
        $amount = FormLib::get('amount', '');
        if (strlen($endorseType) > 0) {

            // close session so if printer hangs
            // this script won't lock the session file
            if (session_id() != '')
                session_write_close();

            switch ($endorseType) {

                case "check":
                    ReceiptLib::frank($amount);
                    break;

                case "giftcert":
                    ReceiptLib::frankgiftcert($amount);
                    break;

                case "stock":
                    ReceiptLib::frankstock($amount);
                    break;

                case "classreg":
                    ReceiptLib::frankclassreg();
                    break;

                default:
                    break;
            }
        }

        return 'Done';
    }
}

AjaxEndorse::run();

