<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ScaleItemsReport extends FannieReportPage 
{
    public $description = '[Scale Items] lists items with Hobart scale information';
    public $report_set = 'Service Scales';
    public $themed = true;

    protected $report_headers = array('UPC', 'Description', 'Weight', 'Tare', 'Shelf Life',
                                      'Net Wt', 'Label Text');
    protected $title = "Fannie : Scale Items Report";
    protected $header = "Scale Items Report";

    protected $required_fields = array('submit');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new ScaleItemsModel($dbc);

        $query = "SELECT s.plu,
                    CASE WHEN s.itemdesc = '' THEN p.description ELSE s.itemdesc END as itemdesc,
                    CASE WHEN s.weight = 0 THEN 'Random' ELSE 'Fixed' END as weight,
                    s.tare,
                    s.shelflife,
                    s.netWeight,
                    s.text
                  FROM scaleItems AS s
                    INNER JOIN products AS p ON s.plu=p.upc
                  WHERE 1=1";
        $args = array();
        $dept1 = FormLib::get('dept1', 0);
        $dept2 = FormLib::get('dept2', 0);
        if ($dept1 != 0 || $dept2 != 0) {
            if ($dept1 == 0) {
                $dept1 = $dept2;
            } else if ($dept2 == 0) {
                $dept2 = $dept1;
            }
            if ($dept1 > $dept2) {
                $tmp = $dept2;
                $dept2 = $dept1;
                $dept1 = $tmp;
            }
            $query .= ' AND p.department BETWEEN ? AND ? ';
            $args[] = $dept1;
            $args[] = $dept2;
        }
        $search = FormLib::get('search', '');
        if ($search !== '') {
            $query .= ' AND (s.itemdesc LIKE ? OR s.text LIKE ? OR p.description LIKE ?) ';
            $args[] = '%' . $search . '%';
            $args[] = '%' . $search . '%';
            $args[] = '%' . $search . '%';
        }
        if ($this->config->get('STORE_ID')) {
            $query .= ' AND p.store_id=? ';
            $args[] = $this->config->get('STORE_ID');
        }
        $query .= ' ORDER BY s.plu';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $data = array();
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['plu'],
            $row['itemdesc'],
            $row['weight'],
            $row['tare'],
            $row['shelflife'],
            $row['netWeight'],
            $row['text'],
        );
    }

    public function form_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $model = new DepartmentsModel($dbc);
        $dlist = array();
        foreach($model->find('dept_no') as $dept) {
            $dlist[$dept->dept_no()] = $dept->dept_name();
        }
        $ret = '<form method="get" action="ScaleItemsReport.php">
            <div class="panel panel-default">
                <div class="panel-heading">Filters</div>
                <div class="panel-body">
                    <div class="form-group form-inline">
                        <label>Search Text</label>
                        <input type="text" name="search" value="" 
                            class="form-control" placeholder="optional" />
                    </div>
                    <div class="form-group form-inline">';
        $ret .= '<label>Dept Start</label> 
            <input type="text" size="3" name="dept1" id="dept1" class="form-control" />';
        $ret .= '<select onchange="$(\'#dept1\').val(this.value);" class="form-control">';
        foreach($dlist as $id => $label) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $id, $id, $label);
        }
        $ret .= '</select>';
        $ret .= '</div>
                    <div class="form-group form-inline">';
        $ret .= '<label>Dept End</label>: 
            <input type="text" size="3" name="dept2" id="dept2" class="form-control" />';
        $ret .= '<select onchange="$(\'#dept2\').val(this.value);" class="form-control">';
        foreach($dlist as $id => $label) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $id, $id, $label);
        }
        $ret .= '</select>';
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '<p>
            <button type="submit" name="submit" value="1" 
                class="btn btn-default">Get Report</button>
            </p>
            </form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            List service scale item information stored in POS, 
            optionally limited to a range of departments. 
            The search text option will search within both
            descriptions and longer label text.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('plu'=>'21234000000', 'itemdesc'=>'test', 'weight'=>0,
            'tare'=>0.01, 'shelflife'=>5, 'netWeight'=>0, 'text'=>'test');
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

