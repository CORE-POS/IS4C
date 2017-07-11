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
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class BasicsList extends FannieReportPage {

    protected $required_fields = array();
    protected $report_headers = array('UPC', 'Brand', 'Description', 'Category', 'Pricing Rule', 'Hillside', 'Denfeld');

    function fetch_report_data()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $query = "
            SELECT p.upc,
                SUM(CASE WHEN store_id=1 AND inUse=1 THEN 1 ELSE 0 END) AS hillside,
                SUM(CASE WHEN store_id=2 AND inUse=1 THEN 1 ELSE 0 END) AS denfeld,
                CASE WHEN u.brand IS NULL OR u.brand='' THEN p.brand ELSE u.brand END AS brand,
                CASE WHEN u.description IS NULL OR u.description='' THEN p.description ELSE u.description END AS description,
                t.description AS ruleType,
                m.super_name,
                p.department
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                INNER JOIN PriceRules AS r ON p.price_rule_id=r.priceRuleID
                INNER JOIN PriceRuleTypes AS t ON r.priceRuleTypeID=t.priceRuleTypeID
            WHERE t.priceRuleTypeID IN (6,7,8)
            GROUP BY p.upc,
                brand,
                description,
                ruleType"; 
        $res = $dbc->query($query);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                $row['super_name'] . $row['department'],
                $row['ruleType'],
                $row['hillside'] ? 'Yes' : 'No',
                $row['denfeld'] ? 'Yes' : 'No',
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

