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
        $msg = _('WFC is offering to its Owners an Access Discount for a once a month 10% discount on one total purchase when an Owner chooses to apply that discount.  The Access Discount is a use it or lose it once a month benefit only for Owners who register annually at Customer Service by presenting proof of participation (e.g., a current card or current award letter) in an applicable program.');
        $msg = wordwrap($msg, 50, "\n");
        $only = ReceiptLib::bold() . 'only' . ReceiptLib::unbold();
        $annually = ReceiptLib::bold() . 'annually' . ReceiptLib::unbold();
        $msg = str_replace('only', $only, $msg);
        $msg = str_replace('annually', $annually, $msg);

        return "\n" . $msg . "\n";
    }

    public function standalone_receipt($ref, $reprint=false)
    {
        global $CORE_LOCAL;

        list($emp, $reg, $trans) = explode('-', $ref, 3);

        $ret = 'Date of Application: ' . date('M d, Y') . "\n";
        $ret .= 'Owner Name: ' . $CORE_LOCAL->get('fname')
                . ' ' . $CORE_LOCAL->get('lname')
                . ', Owner No.: ' . $CORE_LOCAL->get('memberID') . "\n";

        $ret .= "\n";
        $ret .= ReceiptLib::centerString(str_repeat('_', 30)) . "\n";
        $ret .= ReceiptLib::centerString('Owner Signature') . "\n";

        if ($CORE_LOCAL->get('standalone') == 0) {
            $db_name = $CORE_LOCAL->get('ServerOpDB');
            $db = Database::mDataConnect();
            $query = 'SELECT street, zip, phone, email_1, email_2
                      FROM ' . $db_name . $db->sep() . 'meminfo
                      WHERE card_no = ' . ((int)$CORE_LOCAL->get('memberID'));
            $result = $db->query($query);
            if ($db->num_rows($result) > 0) {
                $row = $db->fetch_row($result);
                $ret .= _('Owner Address: ') . str_replace("\n", ", ", $row['street']) . "\n";
                $ret .= _('Zip Code: ') . $row['zip'] . "\n";
                $ret .= _('Owner Phone(s): ') . $row['phone'] . ' ' . $row['email_2'] . "\n";
                $ret .= _('Owner Email: ') . $row['email_1'] . "\n";
            }
        }

        $ret .= "\n";
        $ret .= ReceiptLib::centerString(str_repeat('_', 30)) . "\n";
        $ret .= ReceiptLib::centerString('Employee Signature') . "\n";

        $ret .= "\n";
        $ret .= ReceiptLib::centerString(str_repeat('_', 30)) . "\n";
        $ret .= ReceiptLib::centerString('Manager Signature') . "\n";

        $db = Database::tDataConnect();
        $query = sprintf('SELECT MAX(numflag) FROM localtemptrans
                        WHERE upc=\'ACCESS\'
                            AND emp_no=%d
                            AND register_no=%d
                            AND trans_no=%d',
                            $emp, $reg, $trans);
        if ($reprint) {
            $query = str_replace('localtemptrans', 'localtranstoday', $query);
        }
        $result = $db->query($query);
        if ($db->num_rows($result) > 0) {
            $row = $db->fetch_row($result);
            if ($row[0] != 0) {
                $ret .= 'Program No: ' . $row[0] . "\n";
            }
        }

        return $ret;
    }
}

