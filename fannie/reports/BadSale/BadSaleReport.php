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

class BadSaleReport extends FannieReportPage 
{
    public $description = '[Bad Sale] lists items in current or future sale batches that
    are on sale for more than their normal retail price.';
    public $themed = true;

    protected $report_headers = array('Batch', 'Item', 'Current', 'Retail', 'Sale');
    protected $title = "Fannie : Bad Sale Report";
    protected $header = "Bad Sale Report";

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $query = '
            SELECT b.batchID,
                b.batchName,
                l.upc,
                p.normal_price,
                l.salePrice,
                CASE WHEN b.startDate > ' . $dbc->curdate() . ' THEN 0 ELSE 1 END AS current
            FROM batches AS b
                INNER JOIN batchList AS l ON b.batchID=l.batchID
                INNER JOIN products AS p ON l.upc=p.upc
            WHERE b.endDate >= ' . $dbc->curdate() . '
                AND b.discounttype <> 0
                AND l.salePrice >= p.normal_price';
        $result = $dbc->query($query);
        $data = array();
        while ($w = $dbc->fetchRow($result)) {
            $record = array(
                '<a href="' 
                    . $this->config->get('URL') 
                    . 'batches/newbatch/EditBatchPage.php?id=' 
                    . $w['batchID'] 
                    . '">' 
                    . $w['batchName'] 
                    . '</a>',
                $w['upc'],
                ($w['current'] == 1 ? 'Yes' : 'No'),
                $w['normal_price'],
                $w['salePrice'],
            );
            $data[] = $record;
        }

        return $data;
    }

    public function helpContent()
    {
        return '<p>
            Items that are or will be on sale for more than their normal
            retail price are listed in this report. Click batches or items
            to view them, respectively.
            </p>';
    }

}

FannieDispatch::conditionalExec();

