<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

class MemTypeMovementReport extends FannieReportPage 
{
    protected $report_cache = 'none';
    public $themed = true;
    protected $header = 'Movement by Customer Type';
    protected $title = 'Movement by Customer Type';
    public $description = '[Member Type Movement] shows sales by members with a given type or types.';
    public $report_set = 'Movement Reports';

    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('UPC', 'Description', 'Dept', 'Qty', '$ Total');
    protected $sort_column = 4;
    protected $sort_direction = 1;

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $query_parts = FormLib::standardItemFromWhere();

        $query = '
            SELECT t.upc,
                t.description,
                t.department,
                d.dept_name,
                ' . DTrans::sumQuantity('t') . ' AS qty,
                SUM(t.total) AS total '
            . $query_parts['query']
            . ' AND t.memType=?
            GROUP BY t.upc,
                t.description,
                t.department,
                d.dept_name
            ORDER BY t.upc';
        $args = $query_parts['args'];
        $args[] = FormLib::get('memtype');

        $data = array();
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($res)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['upc'],
            $row['description'],
            $row['department'] . ' ' . $row['dept_name'],
            sprintf('%.2f', $row['qty']),
            sprintf('%.2f', $row['total']),
        );
    }

    public function form_content()
    {
        $ret = '<form method="get">';
        $ret .= '<div class="row">';
        $ret .= FormLib::standardItemFields();
        $ret .= FormLib::standardDateFields();
        $ret .= '</div>';
        $ret .= '<div class="row">';
        $ret .= '<div class="form-group col-sm-5 form-inline">';
        $ret .= '<label>Customer Type</label> ';
        $ret .= '<select name="memtype" class="form-control">';
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $types = new MemtypeModel($dbc);
        foreach ($types->find() as $t) {
            $ret .= sprintf('<option value="%d">%s</option>', $t->memtype(), $t->memDesc());
        }
        $ret .= '</select>';
        $ret .= ' <button type="submit" class="btn btn-default">Submit</button>';
        $ret .= '</div>';
        $ret .= '</div>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            List sales by a specific member/customer type
            or types. This is used primarily to track
            activity by specialized internal customer accounts.
            For example, a special type of account may be
            used when transfer goods between departments
            or taking goods of the shelf for the store\'s
            own use.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('upc'=>'4011', 'description'=>'test',
            'department'=>1, 'dept_name'=>'test', 'qty'=>1, 'total'=>1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();
