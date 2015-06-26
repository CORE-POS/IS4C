<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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
    include(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}

class NewSpecialOrdersPage extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    public $themed = true;
    protected $header = 'Manage Special Orders';
    protected $title = 'Manage Special Orders';

    private $card_no = false;

    public function get_handler()
    {
        /**
          Set up optional per-member filtering
        */
        if (is_numeric(FormLib::get('card_no'))) {
            $this->header = 'Special Orders for Owner #' . FormLib::get('card_no');
            $this->card_no = FormLib::get('card_no');
        }

        return true;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();

        $cachepath = sys_get_temp_dir()."/ordercache/";

        if (!is_dir($cachepath)) {
            mkdir($cachepath);
        }
        $key = dechex(str_replace(" ","",str_replace(".","",microtime())));
        $prints = array();
        $username = FannieAuth::checkLogin();
        if (file_exists("{$cachepath}{$username}.prints"))
            $prints = unserialize(file_get_contents("{$cachepath}{$username}.prints"));
        else {
            $fp = fopen("{$cachepath}{$username}.prints",'w');
            fwrite($fp,serialize($prints));
            fclose($fp);
        }

        $f1 = FormLib::get('f1');
        $f2 = FormLib::get('f2');
        $f3 = FormLib::get('f3');

        $ret = '';
        if ($this->card_no) {
            $ret .= sprintf('(<a href="%s?f1=%s&f2=%s&f3=%s&order=%s">Back to All Owners</a>)<br />',
                    $_SERVER['PHP_SELF'], $f1, $f2, $f3, FormLib::get('order'));
        }

        $status = array(
            0 => "New, No Call",
            3 => "New, Call",
            1 => "Called/waiting",
            2 => "Pending",
            4 => "Placed",
            5 => "Arrived"
        );

        /**
          Lookup list of super departments
          for filtering purposes
        */
        $assignments = array();
        $q = $dbc->prepare("
            SELECT superID,
                super_name 
            FROM MasterSuperDepts
            WHERE superID > 0
            GROUP BY superID,
                super_name 
            ORDER BY superID");
        $r = $dbc->exec_statement($q);
        while ($w = $dbc->fetch_row($r)) {
            $assignments[$w['superID']] = $w['super_name'];
        }
        unset($assignments[0]); 

        /**
          Lookup list of vendors for filtering purposes
          These are vendors mentioned in an order which
          may not overlap perfectly with the actual
          vendors table
        */
        $suppliers = array('');
        $q = $dbc->prepare("
            SELECT mixMatch 
            FROM {$TRANS}PendingSpecialOrder 
            WHERE trans_type='I'
            GROUP BY mixMatch 
            ORDER BY mixMatch");
        $r = $dbc->exec_statement($q);
        while ($w = $dbc->fetch_row($r)) {
            $suppliers[] = $w['mixMatch'];
        }

        $filterstring = "";
        $filterargs = array();
        if ($f1 !== ''){
            $f1 = (int)$f1;
            $filterstring .= ' AND statusFlag=?';
            $filterargs[] = $f1;
        }

        $ret .= '<a href="index.php">Main Menu</a>';
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "Current Orders";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= sprintf('<a href="OldSpecialOrdersPage.php%s">Old Orders</a>',
            ($this->card_no ? '?card_no='.$this->card_no :'')
        );
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= '<input type="checkbox" id="acbx" onclick="$(\'tr.arrived\').each(function(){$(this).toggle();});" />';
        $ret .= '<label for="acbx">Hide Printed</label>';
        $ret .= '<p />';

        $ret .= '<div class="form-inline">';
        $ret .= "<b>Status</b>: ";
        $ret .= '<select id="f_1" class="form-control input-sm" onchange="refilter();">';
        $ret .= '<option value="">All</option>';
        foreach ($status as $k=>$v) {
            $ret .= sprintf("<option %s value=\"%d\">%s</option>",
                ($k===$f1?'selected':''),$k,$v);
        }
        $ret .= '</select>';

        $ret .= '&nbsp;';

        $ret .= '<b>Buyer</b>: <select id="f_2" class="form-control input-sm" onchange="refilter();">';
        $ret .= '<option value="">All</option>';
        foreach ($assignments as $k=>$v) {
            $ret .= sprintf("<option %s value=\"%d\">%s</option>",
                ($k==$f2?'selected':''),$k,$v);
        }
        $ret .= sprintf('<option %s value="2%%2C8">Meat+Cool</option>',($f2=="2,8"?'selected':''));
        $ret .= '</select>';

        $ret .= '&nbsp;';

        $ret .= '<b>Supplier</b>: <select id="f_3" class="form-control input-sm" onchange="refilter();">';
        foreach ($suppliers as $v) {
            $ret .= sprintf("<option %s>%s</option>",
                ($v===$f3?'selected':''),$v);
        }
        $ret .= '</select>';
        $ret .= '</div>';

        /**
          Also filter by member number if applicable
        */
        if ($this->card_no) {
            $filterstring .= " AND p.card_no=?";
            $filterargs[] = $this->card_no;
            $ret .= sprintf('<input type="hidden" id="cardno" value="%d" />',$this->card_no);
        }

        $q = "SELECT min(datetime) as orderDate,p.order_id,sum(total) as value,
            count(*)-1 as items,
            o.statusFlag AS status_flag,
            o.subStatus AS sub_status,
            CASE WHEN MAX(p.card_no)=0 THEN MAX(o.lastName) ELSE MAX(c.LastName) END as name,
            MIN(CASE WHEN trans_type='I' THEN charflag ELSE 'ZZZZ' END) as charflag,
            MAX(p.card_no) AS card_no
            FROM {$TRANS}PendingSpecialOrder as p
                LEFT JOIN custdata AS c ON c.CardNo=p.card_no AND personNum=p.voided
                LEFT JOIN {$TRANS}SpecialOrders AS o ON p.order_id=o.specialOrderID
            WHERE 1=1 $filterstring
            GROUP BY p.order_id,statusFlag,subStatus
            HAVING 
                count(*) > 1 OR
                SUM(CASE WHEN o.notes LIKE '' THEN 0 ELSE 1 END) > 0
            ORDER BY MIN(datetime)";
        $p = $dbc->prepare($q);
        $r = $dbc->execute($p, $filterargs);

        $orders = array();
        $valid_ids = array();
        while ($w = $dbc->fetch_row($r)) {
            $orders[] = $w;
            $valid_ids[$w['order_id']] = true;
        }

        if ($f2 !== '' || $f3 !== '') {
            $filter = "";
            $args = array();
            if ($f2 !== '') {
                $filter .= "AND (m.superID IN (?) OR o.noteSuperID IN (?))";
                $args = array($f2,$f2);
            }
            if ($f3 !== '') {
                $filter .= "AND p.mixMatch=?";
                $args[] = $f3;
            }
            $q = "SELECT p.order_id FROM {$TRANS}PendingSpecialOrder AS p
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                LEFT JOIN {$TRANS}SpecialOrders AS o ON p.order_id=o.specialOrderID
                WHERE 1=1 $filter
                GROUP BY p.order_id";
            $p = $dbc->prepare($q);
            $r = $dbc->execute($p, $args);
            $valid_ids = array();
            while ($w = $dbc->fetch_row($r)) {
                $valid_ids[$w['order_id']] = true;
            }

            /**
              This may be redundant. Notes tagged by super
              department should be captured in the previous
              query. 
            */
            if ($f2 !== '' && $f3 === '') {
                $q2 = $dbc->prepare_statement("
                    SELECT o.specialOrderID 
                    FROM {$TRANS}SpecialOrders AS o
                    WHERE o.noteSuperID IN (?)
                    GROUP BY o.specialOrderID");
                $r2 = $dbc->exec_statement($q2, array($f2));
                while ($w2 = $dbc->fetch_row($r2)) {
                    $valid_ids[$w2['specialOrderID']] = true;
                }
            }
        }

        /**
          Turn the list of valid order IDs into
          query parameters. Next step is to look
          up line items in the each order to list
          all items and vendors on the order summary 
          row
        */
        $oids = "(";
        $oargs = array();
        foreach ($valid_ids as $id=>$nonsense) {
            $oids .= "?,";
            $oargs[] = $id;
        }
        $oids = rtrim($oids,",").")";
        if (empty($oargs)) {
            $oids = '(?)';
            $oargs = array(-1);
            // avoid invalid query
        }

        $itemsQ = $dbc->prepare_statement("
            SELECT order_id,
                description,
                mixMatch 
            FROM {$TRANS}PendingSpecialOrder 
            WHERE order_id IN $oids
                AND trans_id > 0");
        $itemsR = $dbc->exec_statement($itemsQ, $oargs);

        $items = array();
        $suppliers = array();
        while ($itemsW = $dbc->fetch_row($itemsR)) {
            if (!isset($items[$itemsW['order_id']]))
                $items[$itemsW['order_id']] = $itemsW['description'];
            else
                $items[$itemsW['order_id']] .= "; ".$itemsW['description'];
            if (!empty($itemsW['mixMatch'])){
                if (!isset($suppliers[$itemsW['order_id']]))
                    $suppliers[$itemsW['order_id']] = $itemsW['mixMatch'];
                else
                    $suppliers[$itemsW['order_id']] .= "; ".$itemsW['mixMatch'];
            }
        }
        $lenLimit = 10;
        foreach($items as $id=>$desc){
            if (strlen($desc) <= $lenLimit) continue;

            $min = substr($desc,0,$lenLimit);
            $rest = substr($desc,$lenLimit);
            
            $desc = sprintf('%s<span id="exp%d" style="display:none;">%s</span>
                    <a href="" onclick="$(\'#exp%d\').toggle();return false;">+</a>',
                    $min,$id,$rest,$id);
            $items[$id] = $desc;
        }
        $lenLimit = 10;
        foreach($suppliers as $id=>$desc){
            if (strlen($desc) <= $lenLimit) continue;

            $min = substr($desc,0,$lenLimit);
            $rest = substr($desc,$lenLimit);
            
            $desc = sprintf('%s<span id="sup%d" style="display:none;">%s</span>
                    <a href="" onclick="$(\'#sup%d\').toggle();return false;">+</a>',
                    $min,$id,$rest,$id);
            $suppliers[$id] = $desc;
        }

        $ret .= '<p />';

        $ret .= '<form id="pdfform" action="tagpdf.php" method="get">';
        $ret .= sprintf('<table class="table table-bordered table-striped tablesorter tablesorter-core">
                    <thead>
                    <tr>
                    <th>Order Date</th>
                    <th>Name</th>
                    <th>Desc</th>
                    <th>Supplier</th>
                    <th>Items</th>
                    <th>$</th>
                    <th>Status</th>
                    <th>Printed</th>',
                    base64_encode("min(datetime)"),
                    base64_encode("CASE WHEN MAX(p.card_no)=0 THEN MAX(o.lastName) ELSE MAX(c.LastName) END"),
                    base64_encode("sum(total)"),
                    base64_encode("count(*)-1"),
                    base64_encode("statusFlag")
        );
        $ret .= sprintf('<td><img src="%s" alt="Print" 
                onclick="$(\'#pdfform\').submit();" /></td>',
                $this->config->get('URL').'src/img/buttons/action_print.gif');
        $ret .= '</tr></thead><tbody>';
        $fp = fopen($cachepath.$key,"w");
        foreach ($orders as $w) {
            $id = $w['order_id'];
            if (!isset($valid_ids[$id])) continue;


            $ret .= '<tr class="' . ($w['charflag'] == 'P' ? 'arrived' : 'notarrived') . '">';

            list($date, $time) = explode(' ', $w['orderDate'], 2);
            $ret .= sprintf('<td><a href="view.php?orderID=%d&k=%s">%s</a></td>',
                            $id, $key, $date);

            $ret .= sprintf('<td><a href="" onclick="applyMemNum(%d); return false;">%s</a></td>',
                            $w['card_no'], $w['name']);

            $ret .= '<td class="small">' . (isset($items[$id]) ? $items[$id] : '&nbsp;') . '</td>';
            $ret .= '<td class="small">' . (isset($suppliers[$id]) ? $suppliers[$id] : '&nbsp;') . '</td>';

            $ret .= sprintf('<td>%d</td>', $w['items']);
            $ret .= sprintf('<td>%.2f</td>', $w['value']);

            $ret .= '<td class="form-inline">
                <select id="s_status" class="form-control input-sm" onchange="updateStatus('.$w['order_id'].',$(this).val());">';
            foreach($status as $k=>$v){
                $ret .= sprintf('<option %s value="%d">%s</option>',
                ($w['status_flag']==$k?'selected':''),
                $k,$v);
            }
            $ret .= "</select> <span id=\"statusdate{$id}\">".($w['sub_status']==0?'No Date':date('m/d/Y',$w['sub_status']))."</span></td>";
            $ret .= "<td align=center>".($w['charflag']=='P'?'Yes':'No')."</td>";

            $ret .= sprintf('<td><input type="checkbox" %s name="oids[]" value="%d" 
                            onclick="togglePrint(\'%s\',%d);" /></td>',
                    (isset($prints[$id])?'checked':''),
                    $id,$username,$id);
            $ret .= '</tr>';

            fwrite($fp,$w['order_id']."\n");
        }
        fclose($fp);
        $ret .= "</tbody></table>";

        $this->add_script('../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->add_onload_command("\$('.tablesorter').tablesorter();");
        
        return $ret;
    }

    public function javascriptContent()
    {
        return <<<JAVASCRIPT
function refilter(){
    var f1 = $('#f_1').val();
    var f2 = $('#f_2').val();
    var f3 = $('#f_3').val();

    var loc = '?f1='+f1+'&f2='+f2+'&f3='+f3;
    if ($('#cardno').length!=0)
        loc += '&card_no='+$('#cardno').val();
    
    location = loc;
}
function applyMemNum(n){
    if ($('#cardno').length==0) 
        $('body').append('<input type="hidden" id="cardno" />');
    $('#cardno').val(n);
    refilter();
}
function updateStatus(oid,val){
    $.ajax({
    url: 'ajax-calls.php',
    type: 'post',
    data: 'action=UpdateStatus&orderID='+oid+'&val='+val,
    cache: false,
    success: function(resp){
        $('#statusdate'+oid).html(resp);    
    }
    });
}
function togglePrint(username,oid){
    $.ajax({
    url: 'ajax-calls.php',
    type: 'post',
    data: 'action=UpdatePrint&orderID='+oid+'&user='+username,
    cache: false,
    success: function(resp){}
    });
}
JAVASCRIPT;
    }
}

FannieDispatch::conditionalExec();

