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

/**
  @class TenderReport
  Generate a tender report
*/
class TenderReport extends LibraryClass {

/**
  Write tender report to the printer
*/
static public function printReport(){
	$contents = self::get();
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
static public function get()
{
	$trClass = CoreLocal::get("TenderReportMod");
	if ($trClass == '') $trClass = 'DefaultTenderReport';
	return $trClass::get();
}

static public function timeStamp($time) {

	return strftime("%I:%M %p", strtotime($time));
}

}

?>
