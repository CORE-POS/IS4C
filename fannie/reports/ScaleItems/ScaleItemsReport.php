<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ScaleItemsReport extends FannieReportPage 
{
    public $description = '[Scale Items] lists all items sent to Hobart scales';
    public $report_set = '';

    protected $report_headers = array('UPC', 'Description', 'Weight', 'Tare', 'Shelf Life',
                                      'Net Wt', 'Label Text');
    protected $title = "Fannie : Scale Items Report";
    protected $header = "Scale Items Report";

    protected $required_fields = array('submit');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
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
        $query .= ' ORDER BY s.plu';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $data = array();
        while($row = $dbc->fetch_row($result)) {
            $record = array(
                $row['plu'],
                $row['itemdesc'],
                $row['weight'],
                $row['tare'],
                $row['shelflife'],
                $row['netWeight'],
                $row['text'],
            );
            $data[] = $record;
        }

        return $data;
    }

    public function form_content()
    {
        global $FANNIE_OP_DB;
        $model = new DepartmentsModel(FannieDB::get($FANNIE_OP_DB));
        $dlist = array();
        foreach($model->find('dept_no') as $dept) {
            $dlist[$dept->dept_no()] = $dept->dept_name();
        }
        $ret = '<form method="get" action="ScaleItemsReport.php">
            <fieldset><legend>Filters</legend>
            <b>Search Text</b> <input type="text" name="search" value="" placeholder="optional" />
            <br /><br />';
        $ret .= '<b>Dept Start</b>: <input type="text" size="3" name="dept1" id="dept1" />';
        $ret .= '<select onchange="$(\'#dept1\').val(this.value);">';
        foreach($dlist as $id => $label) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $id, $id, $label);
        }
        $ret .= '</select>';
        $ret .= '<br /><br />';
        $ret .= '<b>Dept End</b>: <input type="text" size="3" name="dept2" id="dept1" />';
        $ret .= '<select onchange="$(\'#dept2\').val(this.value);">';
        foreach($dlist as $id => $label) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $id, $id, $label);
        }
        $ret .= '</select>';
        $ret .= '</fieldset>';
        $ret .= '<br /><br />
            <input type="submit" name="submit" value="Get Report" />
            </form>';

        return $ret;
    }

}

FannieDispatch::conditionalExec();

