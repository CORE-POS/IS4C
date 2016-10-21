<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
class SoReceivingReport extends FanniePage 
{
    protected $title = "Fannie :: Special Order Receiving";
    protected $header = "Special Order Receiving";
    public $description = '[Receiving Report] lists information about active special orders by vendor';
    public $page_set = 'Special Orders';

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
function refilter(f){
    var o = $('#orderSetting').val();
    var s = $('#sS').val();
    location = "receivingReport.php?f="+f+"&s="+s+"&order="+o;
}
function resort(o){
    var f= $('#sF').val();
    location = "SoReceivingReport.php?f="+f+"&order="+o;
}
JAVASCRIPT;
    }

    public function bodyContent()
    {
        ob_start();
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('TRANS_DB'));
        $status = array(
            ""=> "Any",
            0 => "New",
            2 => "Pending",
            4 => "Placed"
        );

        $order = FormLib::get('order', 'mixMatch');
        $filter = FormLib::get('f', 4);
        $supp = FormLib::get('s');
        if ($filter !== '') {
            $filter = (int)$filter;
        }

        echo '<div class="form-group form-inline">';
        echo '<select id="sF" class="form-control" onchange="refilter($(this).val());">';
        foreach ($status as $k=>$v) {
            printf('<option value="%s" %s>%s</option>',
                $k,($k===$filter?'selected':''),$v);
        }
        echo '</select>';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

        $suppliers = array('');
        $prep = $dbc->prepare("SELECT mixMatch FROM PendingSpecialOrder WHERE trans_type='I'
            GROUP BY mixMatch ORDER BY mixMatch");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $suppliers[] = $row[0];
        }
        echo '<select id="sS" class="form-control" onchange="refilter($(\'#sF\').val());">';
        echo '<option value="">Supplier...</option>';
        foreach ($suppliers as $s) {
            printf('<option %s>%s</option>',
                ($s==$supp?'selected':''),$s);
        }
        echo '</select></div>';
        printf('<input type="hidden" id="orderSetting" value="%s" />',$order);

        $where = "p.trans_type = 'I'";
        $args = array();
        if (!empty($filter)){
            $where .= " AND s.statusFlag=? ";
            $args[] = ((int)$filter);
        }
        if (!empty($supp)){
            $where .= " AND mixMatch=? ";
            $args[] = $supp;
        }

        $orderQ = "SELECT upc,description,ItemQtty,mixMatch,subStatus
            FROM PendingSpecialOrder AS p
            LEFT JOIN SpecialOrders as s
            ON p.order_id=s.specialOrderID
            WHERE $where
            ORDER BY mixMatch, upc";
        $orderP = $dbc->prepare($orderQ);
        $orderR = $dbc->execute($orderP, $args);
        echo '<table class="table table-bordered table-striped tablesorter tablesorter-core">';
        echo '<thead><tr>';
        echo '<th>UPC</th>';
        echo '<th>Description</th>';
        echo '<th># Cases</th>';
        echo '<th>Supplier</th>';
        echo '<th>Status Updated</th>';
        echo '</tr></thead><tbody>';
        while ($row = $dbc->fetchRow($orderR)){
            printf('<tr><td>%s</td><td>%s</td><td>%d</td><td>%s</td><td>%s</td></tr>',
                $row['upc'],$row['description'],$row['ItemQtty'],$row['mixMatch'],
                ($row['subStatus']==0?'Unknown':date('m/d/Y',$row['subStatus'])));
        }
        echo '</tbody></table>';
        $this->addScript($this->config->get('URL') . 'src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addOnloadCommand("\$('.tablesorter').tablesorter();\n");

        return ob_get_clean();
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->javascriptContent()));
        $phpunit->assertNotEquals(0, strlen($this->bodyContent()));
    }
}

FannieDispatch::conditionalExec();

