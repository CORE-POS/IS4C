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
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('NewSpecialOrdersPage')) {
    include(dirname(__FILE__) . '/NewSpecialOrdersPage.php');
}

class OldSpecialOrdersPage extends NewSpecialOrdersPage
{
    protected $must_authenticate = true;
    protected $header = 'Old Special Orders';
    protected $title = 'Old Special Orders';
    public $description = '[Old Special Orders] lists all archived special orders';
    public $page_set = 'Special Orders';

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

        try {
            $filter_status = $this->form->f1;
            $filter_buyer = $this->form->f2;
            $filter_supplier = $this->form->f3;
        } catch (Exception $ex) {
            $filter_status='';
            $filter_buyer='';
            $filter_supplier='';
        }

        $ret = '';
        if ($this->card_no) {
            $ret .= sprintf('(<a href="%s?f1=%s&f2=%s&f3=%s">Back to All Owners</a>)<br />',
                    $_SERVER['PHP_SELF'], $filter_status, $filter_buyer, $filter_supplier);
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
        $assignments = $this->getSuperDepartments($dbc);

        /**
          Lookup list of vendors for filtering purposes
          These are vendors mentioned in an order which
          may not overlap perfectly with the actual
          vendors table
        */
        $suppliers = $this->getOrderSuppliers($dbc, false);

        /**
          Filter the inital query by
          status
        */
        $filterstring = '1=1 ';
        $filterargs = array();
        if ($filter_status !== '') {
            $filter_status = (int)$filter_status;
            $filterstring .= ' AND statusFlag=?';
            $filterargs[] = $filter_status;
        }

        $ret .= '<a href="index.php">Main Menu</a>';
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= sprintf('<a href="NewSpecialOrdersPage.php%s">Current Orders</a>',
            ($this->card_no ? '?card_no='.$this->card_no :'')
        );
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
        $ret .= "Old Orders";
        $ret .= '<p />';

        $ret .= '<div class="form-inline">';
        $ret .= "<b>Status</b>: ";
        $ret .= '<select id="f_1" class="form-control input-sm" onchange="refilter();">';
        $ret .= '<option value="">All</option>';
        foreach ($status as $k=>$v) {
            $ret .= sprintf("<option %s value=\"%d\">%s</option>",
                ($k===$filter_status?'selected':''),$k,$v);
        }
        $ret .= '</select>';

        $ret .= '&nbsp;';

        $ret .= '<b>Buyer</b>: <select id="f_2" class="form-control input-sm" onchange="refilter();">';
        $ret .= '<option value="">All</option>';
        foreach ($assignments as $k=>$v) {
            $ret .= sprintf("<option %s value=\"%d\">%s</option>",
                ($k==$filter_buyer?'selected':''),$k,$v);
        }
        $ret .= sprintf('<option %s value="2%%2C8">Meat+Cool</option>',($filter_buyer=="2,8"?'selected':''));
        $ret .= '</select>';

        $ret .= '&nbsp;';

        $ret .= '<b>Supplier</b>: <select id="f_3" class="form-control input-sm" onchange="refilter();">';
        foreach ($suppliers as $v) {
            $ret .= sprintf("<option %s>%s</option>",
                ($v===$filter_supplier?'selected':''),$v);
        }
        $ret .= '</select>';
        $ret .= '</div>';
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
                AND ".$dbc->monthdiff($dbc->now(),'min(datetime)')." >= ((?-1)*2)
                AND ".$dbc->monthdiff($dbc->now(),'min(datetime)')." < (?*2) ";
            $filterargs[] = $page;
            $filterargs[] = $page; // again
        }
        $lookupQ .= " ORDER BY MIN(datetime) DESC";
        $lookupP = $dbc->prepare($lookupQ);
        $lookupR = $dbc->execute($lookupP,$filterargs);

        /**
          Capture all the order records in $orders
          For now assume they are all valid
        */
        $orders = array();
        $valid_ids = array();
        while ($row = $dbc->fetchRow($lookupR)) {
            $orders[] = $row;
            $valid_ids[$row['order_id']] = true;
        }

        /**
          Apply filters two and three
          Look up order IDs that match the filters
          These matching IDs will be compared to the
          IDs in $orders to get the final list
        */
        if ($filter_buyer !== '' || $filter_supplier !== '') {
            $valid_ids = $this->filterBuyerSupplier($dbc, $filter_buyer, $filter_supplier, 'CompleteSpecialOrder');
        }

        /**
          Turn the list of valid order IDs into
          query parameters. Next step is to look
          up line items in the each order to list
          all items and vendors on the order summary 
          row
        */
        list($items, $suppliers) = $this->getTextForOrders($dbc, array_keys($valid_ids), 'CompleteSpecialOrder');
        $items = $this->limitTextSize($items, 'exp', 10);
        $suppliers = $this->limitTextSize($suppliers, 'sup', 10);

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

            $ret .= sprintf('<tr class="%s"><td><a href="OrderReviewPage.php?orderID=%d&k=%s">%s</a></td>
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
            $url = filter_input(INPUT_SERVER, 'REQUEST_URI');
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

    var loc = '<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>?f1='+f1+'&f2='+f2+'&f3='+f3;
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
    
