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
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class IdEnforcedReport extends FannieReportPage 
{
    public $description = '[ID Enforced Report] lists sales for items designated as "age restricted"';

    protected $title = "Fannie : ID Enforced Item Movement Report";
    protected $header = "ID Enforced Movement Report";
    public $report_set = 'Movement Reports';
    protected $report_headers = array('UPC','Brand','Description','Dept#','Dept Name', 'Qty','$', 'Age Requirement', 'Contains THC');
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
        if ($super !== '' && $super > -1) {
            $superTable = 'superdepts';
            $args[] = $super;
        }

        /**
          I'm using {{placeholders}}
          to build the basic query, then replacing those
          pieces depending on date range options
        */
        $query = "SELECT
                    d.upc,
                    p.brand,
                    p.description,
                    d.department,
                    e.dept_name,
                    " . DTrans::sumQuantity('d') . " AS qty,
                    SUM(d.total) AS total,
                    p.idEnforced AS 'Age Req',
                    CASE WHEN INSTR(p.description, 'THC') != 0 THEN 'Yes' ELSE 'No' END AS THC
                  FROM {$dlog} AS d
                    LEFT JOIN departments AS e ON d.department=e.dept_no
                    LEFT JOIN {$superTable} AS m ON d.department=m.dept_ID
                    " . DTrans::joinProducts('d') . " 
                  WHERE trans_type IN ('I')
                    AND tdate BETWEEN ? AND ?
                    AND " . DTrans::isStoreID($store, 'd') . "
                    AND p.idEnforced > 0 
                  GROUP BY
                    d.upc,
                    p.brand,
                    d.description,
                    d.department,
                    e.dept_name,
                    p.idEnforced";
        
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
            $row['Age Req'],
            $row['THC']
        );
    }
    
    public function form_content()
    {
        $dates = FormLib::standardDateFields();
        $stores = FormLib::storePicker();

        return <<<HTML
<form method="get" action="IdEnforcedReport.php">
    <div class="form-group">
        $dates
    </div>
    <div class="form-group">
        {$stores['html']}
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-default btn-core">Submit</button>
    <button type="reset" class="btn btn-default btn-reset"
        onclick="$('#super-id').val('').trigger('change');">Start Over</button>
    </div>
</form>
HTML;
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

