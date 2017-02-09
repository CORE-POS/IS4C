<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

namespace COREPOS\pos\lib\ReceiptBuilding\TenderReports;
use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;

/**
  @class TenderReport
  Generate a tender report
*/
class TenderReport 
{

/**
  Write tender report to the printer
*/
static public function printReport($class=false){
    $session = new WrappedStorage();
    if ($class && !class_exists($class)) {
        $class = 'COREPOS\\pos\\lib\\ReceiptBuilding\\TenderReports\\' . $class;
    }
    $contents = $class === false ? self::get($session) : $class::get($session);
    ReceiptLib::writeLine($contents);
}

/** 
 Generate a tender report
 @return [string] receipt contents
 
 This method can be overriden by subclasses to create
 alternate tender reports. When called, this function
 will use whichever module is listed in the configuration
 setting "TenderReportMod". If nothing has been selected,
 the "DefaultTenderReport" module is used.
 */
static public function get($session)
{
    $trClass = $session->get("TenderReportMod");
    if ($trClass == '' || !class_exists($trClass)) {
        $trClass = 'COREPOS\\pos\\lib\\ReceiptBuilding\\TenderReports\\DefaultTenderReport';
    }
    return $trClass::get($session);
}

static public function timeStamp($time) {

    return strftime("%I:%M %p", strtotime($time));
}

static protected function standardLine($tdate, $lane, $trans, $amt)
{
    $timeStamp = self::timeStamp($tdate);
    $blank = self::standardBlank();
    $line = "  ".substr($timeStamp . $blank, 0, 13)
        .substr($lane . $blank, 0, 9)
        .substr($trans . $blank, 0, 8)
        .substr($blank . number_format("0", 2), -10)
        .substr($blank . number_format($amt, 2), -14)
        ."\n";

    return $line;
}

static protected function standardBlank()
{
    $blank = "             ";
    return $blank;
}

static protected function standardFieldNames()
{
    $blank = self::standardBlank();
    $fieldNames = "  ".substr("Time".$blank, 0, 13)
            .substr("Lane".$blank, 0, 9)
            .substr("Trans #".$blank, 0, 12)
            .substr("Change".$blank, 0, 14)
            .substr("Amount".$blank, 0, 14)."\n";

    return $fieldNames;
}

}

