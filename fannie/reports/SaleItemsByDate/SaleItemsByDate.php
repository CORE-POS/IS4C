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

use COREPOS\Fannie\API\item\ItemText;

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SaleItemsByDate extends FannieReportPage 
{
    public $description = '[Sale Items/Batch Start Date] lists all sales items with a given start date';
    public $report_set = 'Batches';

    protected $report_headers = array('Brand', 'Description', 'Size', 'Sale Price', 'UPC');
    protected $sort_direction = 1;
    protected $title = "Fannie : Sale Items by Start-Date Report";
    protected $header = "List Sale Items by Batch Date Report";
    protected $required_fields = array('startdate');

    public function report_description_content()
    {
        return array('Items from batches beginning on ');
    }

    public function fetch_report_data()
    {        
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        
        //procure batchIDs from 'batches'
        $query = "SELECT batchID, owner FROM batches WHERE " . $dbc->datediff('startDate', '?') . "=0";
        $args = array($this->form->startdate);
        if (FormLib::get('dept') != '') {
            $query .= ' AND owner=?';
            $args[] = FormLib::get('dept');
        }
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($query, $args);
        while ($row = $dbc->fetchRow($result)) {
            $batchID[] = $row['batchID'];
            $owner[] = $row['owner'];
        }     

        list($inStr, $args) = $dbc->safeInClause($batchID);
        $query = "SELECT l.upc,
                l.salePrice,
                " . ItemText::longBrandSQL() . ",
                " . ItemText::longDescriptionSQL() . ",
                p.size
            FROM batchList AS l
                " . DTrans::joinProducts('l') . "
                LEFT JOIN productUser AS u ON p.upc=u.upc
            WHERE l.batchID IN ({$inStr})";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($row = $dbc->fetchRow($res)) {
            $data[] = array(
                $row['brand'],
                $row['description'],
                $row['size'],
                $row['salePrice'],
                $row['upc'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        $this->add_onload_command('$(\'#startdate\').focus()');
        $res = $this->connection->query('SELECT owner FROM batches GROUP BY owner ORDER BY owner');
        $opts = '';
        while ($row = $this->connection->fetchRow($res)) {
            if ($row['owner']) {
                $opts .= '<option>' . $row['owner'] . '</option>';
            }
        }
        return '<form method="get" action="SaleItemsByDate.php" id="form1">
            <div class="form-group">
            <label>Enter Batch Start Date and Select a Department</label>
            <input type="text" name="startdate" value="" class="form-control date-field"
                required id="startdate" />
            </div>
            <div class="form-group">
            <select form="form1" name="dept" class="form-control">
                <option value="">All Departments</option>
                ' . $opts . '
            </select>
            </div>
            <p>
            <button type="submit" class="btn btn-default">Get Report</button>
            </p>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            View all Accounts Receivable (AR) activity for a given member.
            Enter the desired member number.
            </p>';
    }

}

FannieDispatch::conditionalExec();

