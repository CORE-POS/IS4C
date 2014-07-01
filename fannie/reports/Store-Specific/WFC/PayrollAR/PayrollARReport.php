<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class PayrollARReport extends FannieReportPage {

    function preprocess(){
        /**
          Change content function, turn off the menus,
          set up headers
        */
        $this->header = "Payroll AR Report";
        $this->content_function = "report_content";
        $this->has_menus(False);
        $this->report_headers = array('Owner#','First','Last','Limit','Deducted',
            'Household #1','Household #1','Household #2','Household #2',
            'Household #3','Household #3');
        
        /**
          Check if a non-html format has been requested
        */
        if (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'xls')
            $this->report_format = 'xls';
        elseif (isset($_REQUEST['excel']) && $_REQUEST['excel'] == 'csv')
            $this->report_format = 'csv';

        return True;
    }

    function fetch_report_data(){
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $query = $dbc->prepare_statement("SELECT c.CardNo,c.FirstName,c.LastName,c.ChargeLimit,
            CASE WHEN s.cardNo IS NULL THEN 'no' ELSE 'yes' END as autodeduct,
            x.FirstName,x.LastName,
            y.FirstName,y.LastName,
            z.FirstName,z.LastName
            FROM custdata AS c LEFT JOIN ".
            $FANNIE_TRANS_DB.$dbc->sep()."staffAR as s ON c.CardNo=s.cardNo
            LEFT JOIN custdata AS x ON c.CardNo=x.CardNo AND x.personNum=2
            LEFT JOIN custdata AS y ON c.CardNo=y.CardNo AND y.personNum=3
            LEFT JOIN custdata AS z ON c.CardNo=z.CardNo AND z.personNum=4
            WHERE c.personNum=1 AND c.memType in (3,9)
            AND c.LastName <> 'NEW STAFF'
            order by c.CardNo");
    
        /**
          Simple report
        
          Issue a query, build array of results
        */
        $result = $dbc->exec_statement($query);
        $ret = array();
        while ($row = $dbc->fetch_array($result)){
            $record = array();
            for($i=0;$i<$dbc->num_fields($result);$i++)
                $record[] = $row[$i];
            $ret[] = $record;
        }
        return $ret;
    }
    
    /**
      Sum the quantity and total columns
    */
    function calculate_footers($data){
    }

    function form_content(){
    }
}

FannieDispatch::conditionalExec(false);

?>
