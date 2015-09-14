<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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


/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 21Jan2013 Eric Lee table upcLike need database specified: core_op.upcLike

*/

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class RecentSalesReport extends FannieReportPage
{
    public $description = '[Recent Sales] lists sales for an item in recent days/weeks/months.';
    public $themed = true;

    protected $header = 'Recent Sales';
    protected $title = 'Fannie : Recent Sales';

    protected $report_headers = array('', 'Qty', '$');
    protected $report_cache = 'none';
    protected $sortable = false;
    protected $no_sort_but_style = true;

    private $upc;
    private $lc;

    public function preprocess() {
        // custom: one of the fields is required but not both
        $this->upc = BarcodeLib::padUPC(FormLib::get('upc'));
        $this->lc = FormLib::get('likecode');
        if ($this->upc != '0000000000000' || $this->lc !== '') {
            if ($this->lc !== '') {
                $this->report_headers[0] = 'Like Code #'.$this->lc;
                $this->required_fields = array('likecode');
            } else {
                $this->report_headers[0] = 'UPC #' . $this->upc;
                $this->required_fields = array('upc');
            }
            parent::preprocess();
        }

        return true;
    }

    public function report_description_content()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $prod = new ProductsModel($dbc);
        $prod->upc(BarcodeLib::padUPC(FormLib::get('upc')));
        $prod->load();
        $ret = array('Recent Sales For ' . $prod->upc() . ' ' . $prod->description() . '<br />');
        if ($this->report_format == 'html') {
            $ret[] = sprintf('<a href="../ItemLastQuarter/ItemLastQuarterReport.php?upc=%s">Weekly Sales Details</a> | ', $prod->upc());
            $ret[] = sprintf('<a href="../ItemOrderHistory/ItemOrderHistoryReport.php?upc=%s">Recent Order History</a>', $prod->upc());
        }

        return $ret;
    }

    protected function defaultDescriptionContent($datefields=array())
    {
        return array(); // override
    }

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $dates = array();
        $stamp = strtotime('yesterday');
        $dates['Yesterday'] = array(date("Y-m-d",$stamp), date('Y-m-d', $stamp));
        $stamp = mktime(0,0,0,date("n",$stamp),date("j",$stamp)-1,date("Y",$stamp));
        $dates['2 Days Ago'] = array(date("Y-m-d",$stamp), date('Y-m-d', $stamp));
        $stamp = mktime(0,0,0,date("n",$stamp),date("j",$stamp)-2,date("Y",$stamp));
        $dates['3 Days Ago'] = array(date("Y-m-d",$stamp), date('Y-m-d', $stamp));

        $dates['This Week'] = array(date("Y-m-d",strtotime("monday this week")),
                date("Y-m-d",strtotime("sunday this week")));
        $dates['Last Week'] = array(date("Y-m-d",strtotime("monday last week")),
                date("Y-m-d",strtotime("sunday last week")));

        $dates['This Month'] = array(date("Y-m-01"),date("Y-m-t"));
        $stamp = mktime(0,0,0,date("n")-1,1,date("Y"));
        $dates['Last Month'] = array(date("Y-m-01",$stamp),date("Y-m-t",$stamp));

        $dlog = DTransactionsModel::selectDlog($dates['Last Month'][0], $dates['Yesterday'][0]);

        $where = 'd.upc = ?';
        $baseArgs = array($this->upc);
        if ($this->lc !== '') {
            $where = 'u.likeCode = ?';
            $baseArgs = array($this->lc);
        }

        $q = "SELECT SUM(CASE WHEN trans_status='M' THEN 0 ELSE quantity END) as qty,
            SUM(total) as ttl
            FROM $dlog as d ";
        if ($this->lc !== '') {
            $q .= ' LEFT JOIN upcLike AS u ON d.upc=u.upc ';
        }
        $q .= "WHERE $where
            AND tdate < " . $dbc->curdate() . "
            AND tdate BETWEEN ? AND ?";
        $p = $dbc->prepare_statement($q);
        
        $data = array();
        foreach($dates as $label => $span) {
            $args = array($baseArgs[0], $span[0].' 00:00:00', $span[1].' 23:59:59');
            $r = $dbc->exec_statement($p, $args);

            $row = array('qty'=>0, 'ttl'=>0);
            if ($dbc->num_rows($r) > 0) {
                $row = $dbc->fetch_row($r);
            }

            $record = array($label, sprintf('%.2f',$row['qty']), sprintf('%.2f', $row['ttl']));
            $data[] = $record;
        }

        return $data;
    }

    public function form_content() 
    {
        $this->add_onload_command('$(\'#upc-field\').focus();');
        return '<form action="RecentSalesReport.php" method="get">
                <div class="form-group form-inline">
                <label>UPC</label>
                <input type="text" name="upc" id="upc-field" class="form-control" />
                </div>
                <p>
                <button type="submit" class="btn btn-default">Submit</button>
                </p>
                </form>';
    }

    public function helpContent()
    {
        return '<p>
            List sales for an item on:
            <ul>
                <li>Each of the last three days</li>
                <li>The current and previous week</li>
                <li>The current and previous month</li>
            </ul>
            This report is rarely used directly. More often
            it is integrated into other tools to provide
            a quick snapshot of sale information.
            </p>';
    }
}

FannieDispatch::conditionalExec();

?>
