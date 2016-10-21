<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

class LocalMovementReport extends FannieReportPage 
{
    public $description = '[Local Movement] lists sales for items designated as "local"';

    protected $title = "Fannie : Local Movement Report";
    protected $header = "Local Movement Report";
    public $report_set = 'Movement Reports';
    protected $report_headers = array('UPC','Brand','Description','Dept#','Dept Name', 'Qty','$', 'Local');
    protected $required_fields = array('date1', 'date2');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $FANNIE_TRANS_DB = $this->config->get('TRANS_DB');

        $args = array();
        try {
            $date1 = $this->form->date1;
            $date2 = $this->form->date2;
            $dlog = DTransactionsModel::selectDlog($date1, $date2);
            $args[] = $date1 . ' 00:00:00';
            $args[] = $date2 . ' 23:59:59';
        } catch (Exception $ex) {
            return array();
        }
        $store = FormLib::get('store', 0);
        $args[] = $store;

        $dept_where = '';
        $super = FormLib::get('buyer', '');
        $superTable = 'MasterSuperDepts';
        $depts = FormLib::get('departments', array());
        $dept1 = FormLib::get('deptStart', '');
        $dept2 = FormLib::get('deptEnd', '');
        $subs = FormLib::get('subdepts', array());
        if ($super !== '' && $super == -2) {
            $dept_where .= " AND m.superID<>0 ";
        } elseif ($super !== '' && $super > -1) {
            $superTable = 'superdepts';
            $dept_where .= " AND m.superID=? ";
            $args[] = $super;
        }
        if (!empty($depts)) {
            list($dIN, $args) = $dbc->safeInClause($depts, $args);
            $dept_where .= " AND d.department IN ({$dIN}) ";
        } elseif ($dept1 && $dept2) {
            $dept_where .= " AND d.department BETWEEN ? AND ? ";
            $args[] = $dept1;
            $args[] = $dept2;
        }
        if (!empty($subs)) {
            list($sIN, $args) = $dbc->safeInClause($subs, $args);
            $dept_where .= " AND p.subdept IN ({$sIN}) ";
        }

        /**
          I'm using {{placeholders}}
          to build the basic query, then replacing those
          pieces depending on date range options
        */
        $query = "SELECT
                    d.upc,
                    COALESCE(p.brand, '') AS brand,
                    d.description,
                    d.department,
                    e.dept_name,
                    " . DTrans::sumQuantity('d') . " AS qty,
                    SUM(d.total) AS total,
                    o.name AS local_name
                  FROM {$dlog} AS d
                    LEFT JOIN departments AS e ON d.department=e.dept_no
                    LEFT JOIN {$superTable} AS m ON d.department=m.dept_ID
                    LEFT JOIN origins AS o ON d.numflag=o.originID AND o.local=1
                    " . DTrans::joinProducts('d') . "
                  WHERE trans_type IN ('I')
                    AND tdate BETWEEN ? AND ?
                    AND " . DTrans::isStoreID($store, 'd') . "
                    AND d.numflag > 0
                    {$dept_where}
                  GROUP BY
                    d.upc,
                    d.description,
                    d.department,
                    e.dept_name";
        
        $data = array();
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
        while ($row = $dbc->fetchRow($result)) {
            $data[] = $this->rowToRecord($row);
        }

        return $data;
    }

    private function rowToRecord($row)
    {
        return array(
            $row['upc'],
            $row['brand'],
            $row['description'],
            $row['department'],
            $row['dept_name'],
            sprintf('%.2f', $row['qty']),
            sprintf('%.2f', $row['total']),
            $row['local_name'] ?: 'Yes',
        );
    }
    
    public function form_content()
    {
        $form = FormLib::dateAndDepartmentForm();

        return $form;
    }

    public function helpContent()
    {
        return '<p>
            List items marked as shrink for a given date range. In this
            context, shrink is tracking losses.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('upc'=>'4011',
            'brand'=>'b','description'=>'test', 'department'=>1, 'dept_name'=>'test',
            'qty'=>1, 'local_name'=>'yes', 'total'=>1);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

