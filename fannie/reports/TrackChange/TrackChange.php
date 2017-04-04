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

class TrackChange extends FannieReportPage 
{
    public $description = '[Track Product Change] Tracks Changes Made to Products';
    public $report_set = 'Operational Data';
    public $themed = true;

    protected $report_headers = array('Description', 'Price', 'Sale Price', 'Cost', 'Dept', 'Tax', 'FS', 'Scale', 'Modified', 'Modified By');
    protected $sort_direction = 1;
    protected $title = "Fannie : Track Product Change Report";
    protected $header = "Track Product Change";
    protected $required_fields = array('upc');

    public function fetch_report_data()
    {        
        try {
            $upc = $this->form->upc;
        } catch (Exception $ex) {
            return array();
        }
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $query = "SELECT pu.description,
                pu.salePrice,
                pu.price,
                pu.cost,
                pu.dept,
                pu.tax,
                pu.fs,
                pu.scale,
                pu.modified,
                u.name, 
                u.real_name,
                u.uid,
                pu.upc
            FROM prodUpdate as pu
            LEFT JOIN Users as u on u.uid=pu.user
            WHERE pu.upc='{$upc}'
            GROUP BY pu.modified;";
        $result = $dbc->query($query);
        $summary_desc = '';
        $desc = array();
        while ($row = $dbc->fetch_row($result)) {
            $desc[] = $row['description'];
            $salePrice[] = $row['salePrice'];
            $price[] = $row['price'];
            $cost[] = $row['cost'];
            $dept[] = $row['dept'];
            $tax[] = $row['tax'];
            $fs[] = $row['fs'];
            $scale[] = $row['scale'];
            $modified[] = $row['modified'];
            $name[] = $row['name'];
            $realName[] = $row['real_name'];
            $uid[] = $row['uid'];
            if ($summary_desc === '') {
                $summary_desc = $row['description'];
            }
        }     
        echo "Changes made to " . $upc . " <B>" . $summary_desc . '</B><br />';
        
        $item = array( array() );
        for ($i=0; $i<count($desc); $i++) {
            if ($cost[$i] != $cost[$i-1]
                || $salePrice[$i] != $salePrice[$i-1]
                || $cost[$i] != $cost[$i-1]
                || $tax[$i] != $tax[$i-1]
                || $fs[$i] != $fs[$i-1]
                || $scale[$i] != $scale[$i-1]
                || $desc[$i] != $desc[$i-1]
                
            ) {
                $item[$i][0] = $desc[$i];
                $item[$i][1] = $price[$i];
                $item[$i][2] = $salePrice[$i];
                $item[$i][3] = $cost[$i];
                $item[$i][4] = $dept[$i];
                $item[$i][5] = $tax[$i];
                $item[$i][6] = $fs[$i];
                $item[$i][7] = $scale[$i];
                $item[$i][8] = $modified[$i];
                if ($realName[$i] == NULL) {
                    $item[$i][9] = "<i>unknown - " . $uid[$i] . "</i>";
                } else {
                    $item[$i][10] = $realName[$i];
                }
            }
        }
        return $item;
    }

    public function form_content()
    {
        $this->add_onload_command('$(\'#startdate\').focus()');
        return '<form method="get" action="TrackChange.php" id="form1">
            <div class="form-group">
            <label>Enter UPC of item to track</label>
            <form method="get"><p>UPC: 
                <input type="text" name="upc" class="form-control">
                </p>
                <input type="submit" value="Get Report" class="btn btn-default">
                </p>
            </form>
            </div>';
    }

    public function helpContent()
    {
        return '<p>
            Search for all changes made to a product by UPC. 
            </p>';
    }

}

FannieDispatch::conditionalExec();

