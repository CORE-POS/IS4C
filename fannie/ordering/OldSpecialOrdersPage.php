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
include('../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class OldSpecialOrdersPage extends FannieRESTfulPage
{
    protected $must_authenticate = true;
    public $themed = true;
    protected $header = 'Old Special Orders';
    protected $title = 'Old Special Orders';

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
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $TRANS = $FANNIE_TRANS_DB . $dbc->sep();

        $f1 = FormLib::get('f1');
        $f2 = FormLib::get('f2');
        $f3 = FormLib::get('f3');

        $ret = '';
        if ($this->card_no) {
            $ret .= sprintf('(<a href="%s?f1=%s&f2=%s&f3=%s">Back to All Owners</a>)<br />',
                    $_SERVER['PHP_SELF'], $f1, $f2, $f3);
        }

        $status = array(
            0 => "New",
            3 => "New, Call",
            1 => "Called/waiting",
            2 => "Pending",
            4 => "Placed",
            5 => "Arrived",
            7 => "Completed",
            8 => "Canceled",
            9 => "Inquiry"
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

        /**
          Lookup list of vendors for filtering purposes
          These are vendors mentioned in an order which
          may not overlap perfectly with the actual
          vendors table
        */
        $suppliers = array('');
        $q = $dbc->prepare("
            SELECT mixMatch 
            FROM {$TRANS}CompleteSpecialOrder 
            WHERE trans_type='I'
            GROUP BY mixMatch 
            ORDER BY mixMatch");
        $r = $dbc->exec_statement($q);
        while ($w = $dbc->fetch_row($r)) {
            $suppliers[] = $w['mixMatch'];
        }

        /**
          Filter the inital query by
          status
        */
        $filterstring = '1=1 ';
        $filterargs = array();
        if ($f1 !== '') {
            $f1 = (int)$f1;
            $filterstring .= ' AND statusFlag=?';
            $filterargs[] = $f1;
        }

        $ret .= '<a href="index.php">Main Menu</a>';
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= sprintf('<a href="clearinghouse.php%s">Current Orders</a>',
            ($this->card_no ? '?card_no='.$this->card_no :'')
        );
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "Old Orders";
        $ret .= '<p />';

        $ret .= "<b>Status</b>: ";
        $ret .= '<select id="f_1" onchange="refilter();">';
        $ret .= '<option value="">All</option>';
        foreach ($status as $k=>$v) {
            $ret .= sprintf("<option %s value=\"%d\">%s</option>",
                ($k===$f1?'selected':''),$k,$v);
        }
        $ret .= '</select>';

        $ret .= '&nbsp;';

        $ret .= '<b>Buyer</b>: <select id="f_2" onchange="refilter();">';
        $ret .= '<option value="">All</option>';
        foreach ($assignments as $k=>$v) {
            $ret .= sprintf("<option %s value=\"%d\">%s</option>",
                ($k==$f2?'selected':''),$k,$v);
        }
        $ret .= sprintf('<option %s value="2%%2C8">Meat+Cool</option>',($f2=="2,8"?'selected':''));
        $ret .= '</select>';

        $ret .= '&nbsp;';

        $ret .= '<b>Supplier</b>: <select id="f_3" onchange="refilter();">';
        foreach ($suppliers as $v) {
            $ret .= sprintf("<option %s>%s</option>",
                ($v===$f3?'selected':''),$v);
        }
        $ret .= '</select>';
        $ret .= '<hr />';

        /**
          Also filter by member number if applicable
        */
        if ($this->card_no) {
            $filterstring .= " AND p.card_no=?";
            $filterargs[] = $this->card_no;
            $ret .= sprintf('<input type="hidden" id="cardno" value="%d" />',$_REQUEST['card_no']);
        }

        $page = (int)FormLib::get('page', 1);

        /**
          Get list of completed special orders filtered by
          status and optionally member number. If no member number
          specified, use paging with 3 months of orders per page
        */
        $lookupQ = "
            SELECT min(datetime) as orderDate,
                p.order_id,
                sum(total) as value,
                count(*)-1 as items,
                statusFlag AS status_flag,
                subStatus AS sub_status,
                CASE WHEN MAX(p.card_no)=0 THEN MAX(o.lastName) ELSE MAX(c.LastName) END as name,
                MIN(CASE WHEN trans_type='I' THEN charflag ELSE 'ZZZZ' END) as charflag,
                MAX(p.card_no) AS card_no
            FROM {$TRANS}CompleteSpecialOrder as p
                LEFT JOIN custdata AS c ON c.CardNo=p.card_no AND personNum=p.voided
                LEFT JOIN {$TRANS}SpecialOrders AS o ON p.order_id=o.specialOrderID
            WHERE $filterstring
            GROUP BY p.order_id,statusFlag,subStatus
            HAVING 
                (count(*) > 1 OR SUM(CASE WHEN o.notes LIKE '' THEN 0 ELSE 1 END) > 0)";
        if (!$this->card_no) {
            $lookupQ .= "
                AND ".$dbc->monthdiff($dbc->now(),'min(datetime)')." >= ((?-1)*3)
                AND ".$dbc->monthdiff($dbc->now(),'min(datetime)')." < (?*3) ";
            $filterargs[] = $page;
            $filterargs[] = $page; // again
        }
        $lookupQ .= " ORDER BY MIN(datetime) DESC";
        $p = $dbc->prepare($lookupQ);
        $r = $dbc->exec_statement($p,$filterargs);

        /**
          Capture all the order records in $orders
          For now assume they are all valid
        */
        $orders = array();
        $valid_ids = array();
        while ($w = $dbc->fetch_row($r)) {
            $orders[] = $w;
            $valid_ids[$w['order_id']] = true;
        }

        /**
          Apply filters two and three
          Look up order IDs that match the filters
          These matching IDs will be compared to the
          IDs in $orders to get the final list
        */
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
            /** 
                while the goal is to filter by super department
                and/or vendor, reapplying the member number or
                paging criteria keeps the result set more manageable
            */
            $q = "
                SELECT p.order_id 
                FROM {$TRANS}CompleteSpecialOrder AS p
                    LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                    LEFT JOIN {$TRANS}SpecialOrders AS o ON p.order_id=o.specialOrderID
                WHERE 1=1 $filter ";
            if ($this->card_no) {
                $q .= ' AND p.card_no=? ';
                $args[] = $this->card_no;
            } else {
                $q .= "
                    AND ".$dbc->monthdiff($dbc->now(),'min(datetime)')." >= ((?-1)*3)
                    AND ".$dbc->monthdiff($dbc->now(),'min(datetime)')." < (?*3) ";
                $filterargs[] = $page;
                $filterargs[] = $page;
            }
            $q .= " GROUP BY p.order_id";
            $p = $dbc->prepare($q);
            $r = $dbc->exec_statement($p,$args);
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
            FROM {$TRANS}CompleteSpecialOrder 
            WHERE order_id IN $oids
                AND trans_id > 0");
        $itemsR = $dbc->exec_statement($itemsQ, $oargs);
        $items = array();
        $suppliers = array();
        while ($itemsW = $dbc->fetch_row($itemsR)) {
            if (!isset($items[$itemsW['order_id']])) {
                $items[$itemsW['order_id']] = $itemsW['description'];
            } else {
                $items[$itemsW['order_id']] .= "; ".$itemsW['description'];
            }
            if (!empty($itemsW['mixMatch'])) {
                if (!isset($suppliers[$itemsW['order_id']])) {
                    $suppliers[$itemsW['order_id']] = $itemsW['mixMatch'];
                } else {
                    $suppliers[$itemsW['order_id']] .= "; ".$itemsW['mixMatch'];
                }
            }
        }

        /**
          Trim down how much of the item(s) summary is shown
          by default. With multiple items on one order this
          could get very, very long. The full item list is 
          shown on hover or when clicking the expand/+ link
        */
        $lenLimit = 10;
        foreach ($items as $id=>$desc) {
            if (strlen($desc) <= $lenLimit) {
                $items[$id] = '<td>' . $desc . '</td>';
                continue;
            }

            $min = substr($desc,0,$lenLimit);
            $rest = substr($desc,$lenLimit);
            
            $desc = sprintf('<td title="%s%s">
                        %s<span id="exp%d" style="display:none;">%s</span>
                        <a href="" onclick="$(\'#exp%d\').toggle();return false;">+</a>
                        </td>',
                        $min, $rest,
                        $min, $id, $rest,
                        $id);
            $items[$id] = $desc;
        }

        /**
          Do the same trimming for suppliers
        */
        $lenLimit = 10;
        foreach ($suppliers as $id=>$desc) {
            if (strlen($desc) <= $lenLimit) {
                $suppliers[$id] = '<td>' . $desc . '</td>';
                continue;
            }

            $min = substr($desc,0,$lenLimit);
            $rest = substr($desc,$lenLimit);
            
            $desc = sprintf('<td title="%s%s">
                    %s<span id="sup%d" style="display:none;">%s</span>
                    <a href="" onclick="$(\'#sup%d\').toggle();return false;">+</a>
                    </td>',
                    $min, $rest,
                    $min, $id, $rest,
                    $id);
            $suppliers[$id] = $desc;
        }

        $ret .= '<table class="table table-bordered tablesorter">
            <thead>
            <tr>
            <th>Order Date</th>
            <th>Name</th>
            <th>Desc</th>
            <th>Supplier</th>
            <th>Items</th>
            <th>$</th>
            <th>Status</th>
            </tr>
            </thead>
            <tbody>';
        $key = "";
        foreach ($orders as $w) {
            if (!isset($valid_ids[$w['order_id']])) continue;

            $ret .= sprintf('<tr class="%s"><td><a href="review.php?orderID=%d&k=%s">%s</a></td>
                <td><a href="" onclick="applyMemNum(%d);return false;">%s</a></td>
                %s
                %s
                <td align=center>%d</td>
                <td>%.2f</td>',
                ($w['charflag']=='P'?'arrived':'notarrived'),
                $w['order_id'],$key,
                date('Y-m-d', strtotime($w['orderDate'])),
                $w['card_no'],$w['name'],
                (isset($items[$w['order_id']])?$items[$w['order_id']]:'<td>&nbsp;</td>'),
                (isset($suppliers[$w['order_id']])?$suppliers[$w['order_id']]:'<td>&nbsp;</td>'),
                $w['items'],$w['value']);
            $ret .= '<td>';
            foreach ($status as $k=>$v) {
                if ($w['status_flag']==$k) $ret .= $v;
            }
            $ret .= " <span id=\"statusdate{$w['order_id']}\">".($w['sub_status']==0?'No Date':date('m/d/Y',$w['sub_status']))."</span></td></tr>";
        }
        $ret .= "</tbody></table>";

        /**
          Paging links if not using member number filter
        */
        if (!$this->card_no) {
            $url = $_SERVER['REQUEST_URI'];
            if (!strstr($url,"page=")) {
                if (substr($url,-4)==".php") {
                    $url .= "?page=".$page;
                } else {
                    $url .= "&page=".$page;
                }
            }
            if ($page > 1) {
                $prev = $page-1;
                $prev_url = preg_replace('/page=\d+/','page='.$prev,$url);
                $ret .= sprintf('<a href="%s">Previous</a>&nbsp;&nbsp;||&nbsp;&nbsp;',
                        $prev_url);
            }
            $next = $page+1;
            $next_url = preg_replace('/page=\d+/','page='.$next,$url);
            $ret .= sprintf('<a href="%s">Next</a>',$next_url);
        }

        $this->add_script('../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->add_onload_command("\$('.tablesorter').tablesorter();");

        return $ret;
    }

    public function javascriptContent()
    {
        ob_start();
        ?>
function refilter(){
    var f1 = $('#f_1').val();
    var f2 = $('#f_2').val();
    var f3 = $('#f_3').val();

    var loc = '<?php echo $_SERVER['PHP_SELF']; ?>?f1='+f1+'&f2='+f2+'&f3='+f3;
    if ($('#cardno').length!=0)
        loc += '&card_no='+$('#cardno').val();
    if ($('#orderSetting').length!=0)
        loc += '&order='+$('#orderSetting').val();
    
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
        <?php
        return ob_get_clean();
    }

    public function css_content()
    {
        return '
            table.tablesorter thead th {
                cursor: hand;
                cursor: pointer;
            }';
    }
}

FannieDispatch::conditionalExec();
    
?>
