<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

namespace COREPOS\pos\lib\adminlogin;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\ReceiptBuilding\TenderReports\TenderReport;
use \CoreLocal;

/**
  @class AnyTenderReportRequest
  requestInfo callback for printing
  tender reports given an arbitrary
  employee number.
*/
class AnyTenderReportRequest 
{

    static public $requestInfoHeader = 'print tender report';

    static public $requestInfoMsg = 'enter cashier number';

    static public function requestInfoCallback($info)
    {
        if ($info === '' || strtoupper($info) == 'CL') {
            // clear/blank => go back to adminlist
            return MiscLib::base_url.'gui-modules/adminlist.php';
        } else if (!is_numeric($info)) {
            // other non-number is invalid input
            return false;
        } else {
            // change employee setting to desired,
            // print report, change back
            $my_emp_no = CoreLocal::get('CashierNo');
            CoreLocal::set('CashierNo', $info);    
            TenderReport::printReport();
            CoreLocal::set('CashierNo', $my_emp_no);
            return true;
        }
    }
}

