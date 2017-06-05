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

class MemberAccess extends FannieReportPage 
{
    public $description = '[Member Access Discount Report] Shows owners who have used EBT, WIC, and/or ACCESS';
    public $report_set = 'Membership';
    public $discoverable = false;

    protected $report_headers = array('Owner #', 'Tender Used', 'Has Access');
    protected $sort_direction = 1;
    protected $title = "Fannie : Member Access Report";
    protected $header = "Member Access Report";

    public function fetch_report_data()
    {        
        
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        
        $query = "SELECT card_no
            FROM dlog_90_view 
            WHERE card_no!=11 
                AND card_no!=9 
                AND card_no!=5700 
                AND card_no!=5500 
                AND card_no!=5603 
                AND trans_subtype='EF' 
            GROUP BY card_no
        ;";
        $result = $dbc->query($query);
        $item = array ( array() );
        while ($row = $dbc->fetch_row($result)) {
            $item[$row['card_no']][0] = $row['card_no'];
            $item[$row['card_no']][1] = "EBT";
            $item[$row['card_no']][2] = 0;
        }
        
        $query = "SELECT card_no 
            FROM dlog_90_view 
            WHERE card_no!=11 
                AND card_no!=9 
                AND card_no!=5700 
                AND card_no!=5500 
                AND card_no!=5603 
                AND trans_subtype='WT' 
            GROUP BY card_no
        ;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            if (isset($item[$row['card_no']][0])) {
                $item[$row['card_no']][1] .= "-WIC";
            } else {
                $item[$row['card_no']][0] = $row['card_no'];
                $item[$row['card_no']][1] = "WIC";
                $item[$row['card_no']][2] = 0;
            }
                
        }
        
        $query = "SELECT card_no 
            FROM dlog_90_view 
            WHERE card_no!=11 
                AND card_no!=9 
                AND card_no!=5700 
                AND card_no!=5500 
                AND card_no!=5603 
                AND description='ACCESS DISCOUNT' 
            GROUP BY card_no;
        ;";
        $result = $dbc->query($query);
        while ($row = $dbc->fetch_row($result)) {
            if (isset($item[$row['card_no']][0])) {
                $item[$row['card_no']][2] = 1;
            } else {
                $item[$row['card_no']][0] = $row['card_no'];
                $item[$row['card_no']][1] = "Member does not utilize EBT or WIC.";
                $item[$row['card_no']][2] = 1;
            }
        }
        
        return $item;
    }

    public function form_content()
    {
        return '<!-- not required -->';
    }

    public function helpContent()
    {
        return '<p>Show member numbers of accounts that 
        have used EBT or WIC as tender in the past 90 days
        and show if the member is utilizing the ACCESS discount.
        <li><em>Owner #</em> shows a row for each member who has
        used WIC, EBT, or ACCESS.</li>
        <li><em>Tender Used</em> show if the owner has used 
        WIC or EBT or both.</li>
        <li><em>Has Access</em> shows if the owner has utilized
        the ACCESS discount</li>
        </p>';
    }

}

FannieDispatch::conditionalExec();

