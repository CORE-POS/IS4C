<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../../../../config.php');
include(__DIR__ . '/../../../../classlib2.0/FannieAPI.php');

class AuditMeat extends FannieReportPage 
{
    protected $required_fields = array();
    protected $report_headers = array('UPC', 'Brand', 'Description', 'Text', 'Last Sold');
    protected $sort_direction = 1;
    protected $sort_column = 4;

    function fetch_report_data()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $query = "
            SELECT p.upc,
                p.brand,
                p.description,
                s.text AS expandedText,
                MAX(p.last_sold) AS last_sold
            FROM products AS p
                INNER JOIN scaleItems AS s ON s.plu=p.upc
                INNER JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            WHERE m.superID=8
            GROUP BY p.upc,
                p.brand,
                p.description,
                s.text";
        $res = $dbc->query($query);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['expandedText'],
                ($row['last_sold'] ? $row['last_sold'] : ''),
            );
        }

        return $data;
    }
    
    /**
      Sum the quantity and total columns
    */
    function calculate_footers($data){
    }

    function form_content(){
    }
}

FannieDispatch::conditionalExec();

