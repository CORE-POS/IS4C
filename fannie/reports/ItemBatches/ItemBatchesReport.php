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

class ItemBatchesReport extends FannieReportPage 
{
    public $description = '[Item Batch History] shows all the sale batches an item has been in.';
    public $themed = true;
    public $report_set = 'Batches';

    protected $title = "Fannie : Item Batch History";
    protected $header = "Item Batch History";

    protected $report_headers = array('Start Date', 'End Date', 'Batch Name', 'Batch Type', 'Sale Price');
    protected $required_fields = array('upc');
    protected $sort_direction = 1;

    public function report_description_content()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $prod = new ProductsModel($dbc);
        $prod->upc(BarcodeLib::padUPC($this->form->upc));
        $prod->load();
        $ret = array('Batch History For ' . $prod->upc() . ' ' . $prod->description());

        return $ret;
    }

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        $upc = $this->form->upc;
        $upc = BarcodeLib::padUPC($upc);

        $query = '
            SELECT b.batchName,
                b.startDate,
                b.endDate,
                t.typeDesc,
                l.salePrice,
                b.batchID
            FROM batchList AS l
                INNER JOIN batches AS b ON b.batchID=l.batchID
                LEFT JOIN batchType AS t ON b.batchType=t.batchTypeID
            WHERE b.discountType <> 0
                AND l.upc=?
            ORDER BY b.startDate
        ';
        $prep = $dbc->prepare($query);
        $args = array($upc);
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
            $row['startDate'],
            $row['endDate'],
            sprintf('<a href="%s/batches/newbatch/EditBatchPage.php?id=%d" target="_batch%d">%s</a>',
                $this->config->get('URL'),
                $row['batchID'],
                $row['batchID'],
                $row['batchName']
            ),
            $row['typeDesc'],
            $row['salePrice'],
        );
    }

    public function calculate_footers($data)
    {
        return array();
    }

    public function form_content()
    {
        $this->addScript('../../item/autocomplete.js');
        $this->add_onload_command("bindAutoComplete('#upc','../../ws/', 'item');\n");
        $this->add_onload_command('$(\'#upc\').focus();');
        return '
            <form action="' . $_SERVER['PHP_SELF'] . '" method="get">
            <div class="form-group form-inline">
                <label>UPC</label> 
                <input type=text name=upc id=upc class="form-control" />
                <button type=submit class="btn btn-default">Get Report</button>
            </div>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            Lists all sale batches
            containing a particular item.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $data = array('batchID'=>1, 'batchName'=>'test', 'startDate'=>'2000-01-01',
            'endDate'=>'2000-01-02', 'typeDesc'=>'test', 'salePrice'=>1.99);
        $phpunit->assertInternalType('array', $this->rowToRecord($data));
    }
}

FannieDispatch::conditionalExec();

