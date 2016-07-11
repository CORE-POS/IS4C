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
        $tender = "AMT: ".MiscLib::truncate2($amount)."  CHANGE: ".MiscLib::truncate2(\CoreLocal::get("change"));
        $output = self::center_check($ref)."\n"
            .self::center_check($date)."\n"
            .self::center_check(\CoreLocal::get("ckEndorse1"))."\n"
            .self::center_check(\CoreLocal::get("ckEndorse2"))."\n"
            .self::center_check(\CoreLocal::get("ckEndorse3"))."\n"
            .self::center_check(\CoreLocal::get("ckEndorse4"))."\n"
            .self::center_check($tender)."\n";

        self::endorse($output);
    }

    static public function frankgiftcert($amount) 
    {
        $ref = trim(\CoreLocal::get("CashierNo"))."-".trim(\CoreLocal::get("laneno"))."-".trim(\CoreLocal::get("transno"));
        $time_now = strftime("%m/%d/%y", time());                // apbw 3/10/05 "%D" didn't work - Franking patch
        $next_year_stamp = mktime(0,0,0,date("m"), date("d"), date("Y")+1);
        $next_year = strftime("%m/%d/%y", $next_year_stamp);        // apbw 3/10/05 "%D" didn't work - Franking patch
        // lines 200-207 edited 03/24/05 apbw Wedge Printer Swap Patch
        $output = "";
        $output .= str_repeat("\n", 6);
        $output .= "ref: " .$ref. "\n";
        $output .= str_repeat(" ", 5).$time_now;
        $output .= str_repeat(" ", 12).$next_year;
        $output .= str_repeat("\n", 3);
        $output .= str_repeat(" ", 75);
        $output .= "$".MiscLib::truncate2($amount);
        self::endorse($output); 
    }

    static public function frankstock($amount) 
    {
        $time_now = strftime("%m/%d/%y", time());        // apbw 3/10/05 "%D" didn't work - Franking patch
        $ref = trim(\CoreLocal::get("CashierNo"))."-".trim(\CoreLocal::get("laneno"))."-".trim(\CoreLocal::get("transno"));
        $output  = "";
        $output .= str_repeat("\n", 40);    // 03/24/05 apbw Wedge Printer Swap Patch
        if (\CoreLocal::get("equityAmt")){
            $output = "Equity Payment ref: ".$ref."   ".$time_now; // WFC 
            \CoreLocal::set("equityAmt","");
            \CoreLocal::set("LastEquityReference",$ref);
        } else {
            $output .= "Stock Payment $".$amount." ref: ".$ref."   ".$time_now; // apbw 3/24/05 Wedge Printer Swap Patch
        }

        self::endorse($output);
    }

    static public function frankclassreg() 
    {
        $ref = trim(\CoreLocal::get("CashierNo"))."-".trim(\CoreLocal::get("laneno"))."-".trim(\CoreLocal::get("transno"));
        $time_now = strftime("%m/%d/%y", time());        // apbw 3/10/05 "%D" didn't work - Franking patch
        $output  = "";        
        $output .= str_repeat("\n", 11);        // apbw 3/24/05 Wedge Printer Swap Patch
        $output .= str_repeat(" ", 5);        // apbw 3/24/05 Wedge Printer Swap Patch
        $output .= "Validated: ".$time_now."  ref: ".$ref;     // apbw 3/24/05 Wedge Printer Swap Patch

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

    static private function center_check($text) 
    {
        return ReceiptLib::center($text, 60);                // apbw 03/24/05 Wedge printer swap patch
    }

}

