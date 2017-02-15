<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\ReceiptBuilding\Messages\ReceiptMessage;

class AccessProgramReceipt extends ReceiptMessage 
{

    public $standalone_receipt_type = 'accessSignupSlip';

    public function select_condition()
    {
        return "SUM(CASE WHEN upc='ACCESS' THEN quantity ELSE 0 END)";
    }

    public function message($val, $ref, $reprint=false)
    {
        if ($val == 0) {
            return '';
        }
        $msg = _('WFC is offering to its Owners an Access Discount for a 10% discount on all eligible items.  The Access Discount is available only for Owners who register annually at Customer Service by presenting proof of participation (e.g., a current card or current award letter) in an applicable program.');
        $msg = wordwrap($msg, 50, "\n");
        $only = ReceiptLib::bold() . 'only' . ReceiptLib::unbold();
        $annually = ReceiptLib::bold() . 'annually' . ReceiptLib::unbold();
        $msg = str_replace('only', $only, $msg);
        $msg = str_replace('annually', $annually, $msg);

        return "\n" . $msg . "\n";
    }

    public function standalone_receipt($ref, $reprint=false)
    {
        list($emp, $reg, $trans) = explode('-', $ref, 3);

        $ret = 'Date of Application: ' . date('M d, Y') . "\n";
        $ret .= 'Owner Name: ' . CoreLocal::get('fname')
                . ' ' . CoreLocal::get('lname')
                . ', Owner No.: ' . CoreLocal::get('memberID') . "\n";

        $ret .= "\n";
        $ret .= ReceiptLib::centerString(str_repeat('_', 30)) . "\n";
        $ret .= ReceiptLib::centerString('Owner Signature') . "\n";

        if (CoreLocal::get('standalone') == 0) {
            $ret .= $this->memAddress();
        }

        $ret .= "\n";
        $ret .= ReceiptLib::centerString(str_repeat('_', 30)) . "\n";
        $ret .= ReceiptLib::centerString('Employee Signature') . "\n";

        $ret .= "\n";
        $ret .= ReceiptLib::centerString(str_repeat('_', 30)) . "\n";
        $ret .= ReceiptLib::centerString('Manager Signature') . "\n";

        $dbc = Database::tDataConnect();
        $query = sprintf('SELECT MAX(numflag) FROM localtemptrans
                        WHERE upc=\'ACCESS\'
                            AND emp_no=%d
                            AND register_no=%d
                            AND trans_no=%d',
                            $emp, $reg, $trans);
        if ($reprint) {
            $query = str_replace('localtemptrans', 'localtranstoday', $query);
        }
        $result = $dbc->query($query);
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            if ($row[0] != 0) {
                $ret .= 'Program No: ' . $row[0] . "\n";
            }
        }

        return $ret;
    }

    private function memAddress()
    {
        $db_name = CoreLocal::get('ServerOpDB');
        $ret = '';
        $dbc = Database::mDataConnect();
        $query = 'SELECT street, zip, phone, email_1, email_2
                  FROM ' . $db_name . $dbc->sep() . 'meminfo
                  WHERE card_no = ' . ((int)CoreLocal::get('memberID'));
        $result = $dbc->query($query);
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            $ret .= _('Owner Address: ') . str_replace("\n", ", ", $row['street']) . "\n";
            $ret .= _('Zip Code: ') . $row['zip'] . "\n";
            $ret .= _('Owner Phone(s): ') . $row['phone'] . ' ' . $row['email_2'] . "\n";
            $ret .= _('Owner Email: ') . $row['email_1'] . "\n";
        }

        return $ret;
    }
}

