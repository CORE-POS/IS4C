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

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodePricingReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    protected $title = "Fannie : Like Code Pricing";
    protected $header = "Like Code Pricing";

    protected $required_fields = array();

    public $description = '[Like Code Pricing] lists current pricing for likecodes';
    public $report_set = 'Price Reports';
    protected $report_headers = array('LC#', 'Price', 'Like Code');
    protected $new_tablesorter = true;

    /**
      Lots of options on this report.
    */
    function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $res = $dbc->query("
            SELECT u.likeCode,
                l.likeCodeDesc,
                AVG(p.normal_price) AS price
            FROM upcLike AS u
                INNER JOIN likeCodes AS l ON l.likeCode=u.likeCode
                INNER JOIN products AS p ON p.upc=u.upc
            GROUP BY u.likeCode
            ORDER BY u.likeCode");
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['likeCode'],
                sprintf('%.2f', $row['price']),
                $row['likeCodeDesc'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '<!-- not needed -->';
    }
}

FannieDispatch::conditionalExec();

