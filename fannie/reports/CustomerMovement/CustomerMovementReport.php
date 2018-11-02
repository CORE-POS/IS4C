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

include(dirname(__FILE__) . '/../../config.php');
include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');

class CustomerMovementReport extends FannieReportPage 
{
    public $description = '[Customer Movement Report] lists movement data for a particular customer\'s purchases';
    public $report_set = 'Membership';
    public $themed = true;

    protected $title = "Fannie : Customer Movement Report";
    protected $header = "Customer Movement Report";
    protected $required_fields = array('date1', 'date2');
    protected $report_headers = array('UPC', 'Brand', 'Description', 'Qty', '$');

    function report_description_content()
    {
        return array('Customer #' . FormLib::get('cardno'));
    }

    function fetch_report_data()
    {
        $setup = FormLib::standardItemFromWhere();

        $query = "SELECT t.upc, p.brand, p.description,
            SUM(t.total) AS ttl,
            " . DTrans::sumQuantity('t') . " AS qty
            {$setup['query']} 
                AND t.card_no=?
            GROuP BY t.upc, p.brand, p.description";
        $prep = $this->connection->prepare($query);
        $setup['args'][] = FormLib::get('cardno');
        $res = $this->connection->execute($prep, $setup['args']);
        $data = array();
        while ($row = $this->connection->fetchRow($res)) {
            $data[] = array(
                $row['upc'],
                $row['brand'],
                $row['description'],
                sprintf('%.2f', $row['qty']),
                sprintf('%.2f', $row['ttl']),
            );
        }

        return $data;
    }

    function calculate_footers($data)
    {
        $sums = array(0, 0);
        foreach ($data as $d) {
            $sums[0] += $d[3];
            $sums[1] += $d[4];
        }

        return array('Total', null, null, $sums[0], $sums[1]);
    }

    function form_content()
    {
        $depts = FormLib::standardDepartmentFields('super-dept', 'departments', 'dept-start', 'dept-end');
        $dates = FormLib::standardDateFields();
        $stores = FormLib::storePicker();
        return <<<HTML
<form method="get">
    <div class="col-sm-5 form-horizontal">
        {$depts}
        <div class="form-group">
            <label class="col-sm-4 control-label">Customer #</label>
            <div class="col-sm-8">
                <input type="text" name="cardno" class="form-control" required />
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-4 control-label">Store</label>
            <div class="col-sm-8">
                {$stores['html']}
            </div>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default btn-core">Run Report</button>
        </div>
    </div>
    {$dates}
</form>
HTML;
    }

    public function helpContent()
    {
        return '<p>
            Lists information about members by their zip code.
            The default Join Date option will only show the number
            of members from each zip code who joined the co-op in
            the given date range.
            </p>
            <p>
            If the CoreWarehouse plugin is available, the report
            can also show total purchase information per zip code
            for all members who shopped in the given date range.
            </p>';
    }

}

FannieDispatch::conditionalExec();

