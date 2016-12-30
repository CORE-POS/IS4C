<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\MiscLib;

/**
  @class Franking
*/
class Franking 
{
    static public function frank($amount) 
    {
        $date = strftime("%m/%d/%y %I:%M %p", time());
        $ref = trim(\CoreLocal::get("memberID"))." ".trim(\CoreLocal::get("CashierNo"))." ".trim(\CoreLocal::get("laneno"))." ".trim(\CoreLocal::get("transno"));
        $tender = _("AMT: ").MiscLib::truncate2($amount)._("  CHANGE: ").MiscLib::truncate2(\CoreLocal::get("change"));
        $output = self::centerCheck($ref)."\n"
            .self::centerCheck($date)."\n"
            .self::centerCheck(\CoreLocal::get("ckEndorse1"))."\n"
            .self::centerCheck(\CoreLocal::get("ckEndorse2"))."\n"
            .self::centerCheck(\CoreLocal::get("ckEndorse3"))."\n"
            .self::centerCheck(\CoreLocal::get("ckEndorse4"))."\n"
            .self::centerCheck($tender)."\n";

        self::endorse($output);
    }

    static public function frankgiftcert($amount) 
    {
        $ref = trim(\CoreLocal::get("CashierNo"))."-".trim(\CoreLocal::get("laneno"))."-".trim(\CoreLocal::get("transno"));
        $timeNow = strftime("%m/%d/%y", time());                // apbw 3/10/05 "%D" didn't work - Franking patch
        $nextYearStamp = mktime(0,0,0,date("m"), date("d"), date("Y")+1);
        $nextYear = strftime("%m/%d/%y", $nextYearStamp);        // apbw 3/10/05 "%D" didn't work - Franking patch
        // lines 200-207 edited 03/24/05 apbw Wedge Printer Swap Patch
        $output = "";
        $output .= str_repeat("\n", 6);
        $output .= _("ref: ") .$ref. "\n";
        $output .= str_repeat(" ", 5).$timeNow;
        $output .= str_repeat(" ", 12).$nextYear;
        $output .= str_repeat("\n", 3);
        $output .= str_repeat(" ", 75);
        $output .= "$".MiscLib::truncate2($amount);
        self::endorse($output); 
    }

    static public function frankstock($amount) 
    {
        $timeNow = strftime("%m/%d/%y", time());        // apbw 3/10/05 "%D" didn't work - Franking patch
        $ref = trim(\CoreLocal::get("CashierNo"))."-".trim(\CoreLocal::get("laneno"))."-".trim(\CoreLocal::get("transno"));
        $output  = "";
        $output .= str_repeat("\n", 40);    // 03/24/05 apbw Wedge Printer Swap Patch
        if (\CoreLocal::get("equityAmt")){
            $output = _("Equity Payment ref: ").$ref."   ".$timeNow; // WFC 
            \CoreLocal::set("equityAmt","");
            \CoreLocal::set("LastEquityReference",$ref);
        } else {
            $output .= _("Stock Payment $").$amount._(" ref: ").$ref."   ".$timeNow; // apbw 3/24/05 Wedge Printer Swap Patch
        }

        self::endorse($output);
    }

    static public function frankclassreg() 
    {
        $ref = trim(\CoreLocal::get("CashierNo"))."-".trim(\CoreLocal::get("laneno"))."-".trim(\CoreLocal::get("transno"));
        $timeNow = strftime("%m/%d/%y", time());        // apbw 3/10/05 "%D" didn't work - Franking patch
        $output  = "";        
        $output .= str_repeat("\n", 11);        // apbw 3/24/05 Wedge Printer Swap Patch
        $output .= str_repeat(" ", 5);        // apbw 3/24/05 Wedge Printer Swap Patch
        $output .= _("Validated: ").$timeNow._("  ref: ").$ref;     // apbw 3/24/05 Wedge Printer Swap Patch

        self::endorse($output);    
    }

    static public function endorse($text) 
    {
        ReceiptLib::writeLine(chr(27).chr(64).chr(27).chr(99).chr(48).chr(4)      
            // .chr(27).chr(33).chr(10)
            .$text
            .chr(27).chr(99).chr(48).chr(1)
            .chr(12)
            .chr(27).chr(33).chr(5));
    }

    static private function centerCheck($text) 
    {
        return ReceiptLib::center($text, 60);                // apbw 03/24/05 Wedge printer swap patch
    }

}

