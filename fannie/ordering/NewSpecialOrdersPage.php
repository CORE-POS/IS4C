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
    protected $header = 'Manage Special Orders';
    protected $title = 'Manage Special Orders';
    public $description = '[New Special Orders] lists all currently active special orders';
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

    protected function getPrintInfo($username)
    {
        $prints = array();
        $cachepath = $this->getCachePath();
        if (file_exists("{$cachepath}{$username}.prints")) {
            $prints = unserialize(file_get_contents("{$cachepath}{$username}.prints"));
        } else {
            $fptr = fopen("{$cachepath}{$username}.prints",'w');
            fwrite($fptr,serialize($prints));
            fclose($fptr);
        }

        return $prints;
    }

    protected function getCachePath()
    {
        $cachepath = sys_get_temp_dir()."/ordercache/";

        if (!is_dir($cachepath)) {
            mkdir($cachepath);
        }

        return $cachepath;
    }

    protected function getSuperDepartments($dbc)
    {
        /**
          Lookup list of super departments
          for filtering purposes
        */
        $assignments = array();
        $prep = $dbc->prepare("
            SELECT superID,
                super_name 
            FROM MasterSuperDepts
            WHERE superID > 0
            GROUP BY superID,
                super_name 
            ORDER BY superID");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $assignments[$row['superID']] = $row['super_name'];
        }
        unset($assignments[0]); 

        return $assignments;
    }

    protected function getOrderSuppliers($dbc, $new=true)
    {
        $suppliers = array('');
        $table = $new ? 'PendingSpecialOrder' : 'CompleteSpecialOrder';
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();
        $prep = $dbc->prepare("
            SELECT mixMatch 
            FROM {$TRANS}{$table}
            WHERE trans_type='I'
            GROUP BY mixMatch 
            ORDER BY mixMatch");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $suppliers[] = $row['mixMatch'];
        }

        return $suppliers;
    }

    protected function filterBuyerSupplier($dbc, $buyer, $supplier, $table='PendingSpecialOrder')
    {
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();
        $valid_ids = array();
        $filter = "";
        $args = array();
        if ($buyer !== '') {
            $filter .= "AND (m.superID IN (?) OR o.noteSuperID IN (?))";
            $args = array($buyer,$buyer);
        }
        if ($supplier !== '') {
            $filter .= "AND p.mixMatch=?";
            $args[] = $supplier;
        }
        $bothQ = "SELECT p.order_id FROM {$TRANS}{$table} AS p
            LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
            LEFT JOIN {$TRANS}SpecialOrders AS o ON p.order_id=o.specialOrderID
            WHERE 1=1 $filter
            GROUP BY p.order_id
            ORDER BY p.order_id DESC";
        $bothP = $dbc->prepare($bothQ);
        $bothR = $dbc->execute($bothP, $args);
        while ($row = $dbc->fetchRow($bothR)) {
            $valid_ids[$row['order_id']] = true;
            if (count($valid_ids) > 1000) {
                break;
            }
        }

        /**
          This may be redundant. Notes tagged by super
          department should be captured in the previous
          query. 
        */
        if ($buyer !== '' && $supplier === '') {
            $noteP = $dbc->prepare("
                SELECT o.specialOrderID 
                FROM {$TRANS}SpecialOrders AS o
                WHERE o.noteSuperID IN (?)
                GROUP BY o.specialOrderID
                ORDER BY o.specialOrderID DESC");
            $noteR = $dbc->execute($noteP, array($buyer));
            while ($row = $dbc->fetchRow($noteR)) {
                $valid_ids[$row['specialOrderID']] = true;
                if (count($valid_ids) > 1000) {
                    break;
                }
            }
        }

        return $valid_ids;
    }

    protected function getTextForOrders($dbc, $ids, $table='PendingSpecialOrder')
    {
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();
        list($oids, $oargs) = $dbc->safeInClause($ids);

        $itemsQ = $dbc->prepare("
            SELECT order_id,
                description,
                mixMatch 
            FROM {$TRANS}{$table} 
            WHERE order_id IN ($oids)
                AND trans_id > 0");
        $itemsR = $dbc->execute($itemsQ, $oargs);

        $items = array();
        $suppliers = array();
        while ($itemsW = $dbc->fetchRow($itemsR)) {
            $items = $this->appendByID($items, $itemsW['order_id'], $itemsW['description']);
            if (!empty($itemsW['mixMatch'])) {
                $suppliers = $this->appendByID($suppliers, $itemsW['order_id'], $itemsW['mixMatch']);
            }
        }

        return array($items, $suppliers);
    }

    private function appendByID($arr, $id, $text)
    {
        if (!isset($arr[$id])) {
            $arr[$id] = $text;
        } else {
            $arr[$id] .= '; ' . $text;
        }

        return $arr;
    }

    protected function limitTextSize($items, $prefix, $lenLimit)
    {
        foreach ($items as $id=>$desc) {
            if (strlen(trim($desc)) <= $lenLimit) {
                $items[$id] = '<td class="small">' . $desc . '</td>';
            }

            $min = substr($desc,0,$lenLimit);
            $rest = substr($desc,$lenLimit);
            
            $desc = sprintf('<td class="small" title="%s%s">
                    %s<span id="%s%d" style="display:none;">%s</span>
                    <a href="" onclick="$(\'#%s%d\').toggle();return false;">+</a>
                    </td>',
                    $min, $rest, $min, $prefix, $id, $rest, $prefix, $id);
            $items[$id] = $desc;
        }

        return $items;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $TRANS = $this->config->get('TRANS_DB') . $dbc->sep();

        $key = dechex(str_replace(" ","",str_replace(".","",microtime())));
        $username = FannieAuth::checkLogin();
        $prints = $this->getPrintInfo($username);
        $cachepath = $this->getCachePath();

        try {
            $filter_status = $this->form->f1;
            $filter_buyer = $this->form->f2;
            $filter_supplier = $this->form->f3;
            $filter_store = $this->form->f4;
        } catch (Exception $ex) {
            $filter_status='';
            $filter_buyer='';
            $filter_supplier='';
            $filter_store = COREPOS\Fannie\API\lib\Store::getIdByIp();
        }

        $ret = '';
        if ($this->card_no) {
            $ret .= sprintf('(<a href="%s?f1=%s&f2=%s&f3=%s&order=%s">Back to All Owners</a>)<br />',
                    filter_input(INPUT_SERVER, 'PHP_SELF'), $filter_status, $filter_buyer, $filter_supplier, FormLib::get('order'));
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
        $assignments = $this->getSuperDepartments($dbc);

        /**
          Lookup list of vendors for filtering purposes
          These are vendors mentioned in an order which
          may not overlap perfectly with the actual
          vendors table
        */
        $suppliers = $this->getOrderSuppliers($dbc);
        $stores = FormLib::storePicker('f4');
        $stores['html'] = str_replace(
            '<select',
            '<select id="f_4" onchange="refilter();"',
            $stores['html']
        );
        $stores['html'] = str_replace('form-control', 'form-control input-sm', $stores['html']);

        $filterstring = "";
        $filterargs = array();
        if ($filter_status !== ''){
            $filter_status = (int)$filter_status;
            $filterstring .= ' AND statusFlag=?';
            $filterargs[] = $filter_status;
        }
        if ($filter_store) {
            $filterstring .= ' AND o.storeID=? ';
            $filterargs[] = $filter_store;
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

        $ret .= '&nbsp;';

        $ret .= '<b>Store</b>: ' . $stores['html'];

        $ret .= '</div>';

        /**
          Also filter by member number if applicable
        */
        if ($this->card_no) {
            $filterstring .= " AND p.card_no=?";
            $filterargs[] = $this->card_no;
            $ret .= sprintf('<input type="hidden" id="cardno" value="%d" />',$this->card_no);
        }

        $soQ = "SELECT min(datetime) as orderDate,p.order_id,sum(total) as value,
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
        $soP = $dbc->prepare($soQ);
        $soR = $dbc->execute($soP, $filterargs);

        $orders = array();
        $valid_ids = array();
        while ($row = $dbc->fetch_row($soR)) {
            $orders[] = $row;
            $valid_ids[$row['order_id']] = true;
        }

        if ($filter_buyer !== '' || $filter_supplier !== '') {
            $valid_ids = $this->filterBuyerSupplier($dbc, $filter_buyer, $filter_supplier);
        }

        /**
          Turn the list of valid order IDs into
          query parameters. Next step is to look
          up line items in the each order to list
          all items and vendors on the order summary 
          row
        */
        list($items, $suppliers) = $this->getTextForOrders($dbc, array_keys($valid_ids));
        $items = $this->limitTextSize($items, 'exp', 10);
        $suppliers = $this->limitTextSize($suppliers, 'sup', 10);

        $ret .= '<p />';

        $ret .= '<form id="pdfform" action="SpecialOrderTags.php" method="get">';
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
        $fptr = fopen($cachepath.$key,"w");
        foreach ($orders as $w) {
            $oid = $w['order_id'];
            if (!isset($valid_ids[$oid])) continue;


            $ret .= '<tr class="' . ($w['charflag'] == 'P' ? 'arrived' : 'notarrived') . '">';

            list($date, $time) = explode(' ', $w['orderDate'], 2);
            $ret .= sprintf('<td><a href="OrderViewPage.php?orderID=%d&k=%s">%s</a></td>',
                            $oid, $key, $date);

            $ret .= sprintf('<td><a href="" onclick="applyMemNum(%d); return false;">%s</a></td>',
                            $w['card_no'], $w['name']);

            $ret .= (isset($items[$oid]) ? $items[$oid] : '<td>&nbsp;</td>');
            $ret .= (isset($suppliers[$oid]) ? $suppliers[$oid] : '<td>&nbsp;</td>');

            $ret .= sprintf('<td>%d</td>', $w['items']);
            $ret .= sprintf('<td>%.2f</td>', $w['value']);

            $ret .= '<td class="form-inline">
                <select id="s_status" class="form-control input-sm" onchange="updateStatus('.$w['order_id'].',$(this).val());">';
            foreach($status as $k=>$v){
                $ret .= sprintf('<option %s value="%d">%s</option>',
                ($w['status_flag']==$k?'selected':''),
                $k,$v);
            }
            $ret .= "</select> <span id=\"statusdate{$oid}\">".($w['sub_status']==0?'No Date':date('m/d/Y',$w['sub_status']))."</span></td>";
            $ret .= "<td align=center>".($w['charflag']=='P'?'Yes':'No')."</td>";

            $ret .= sprintf('<td><input type="checkbox" %s name="oids[]" value="%d" 
                            onclick="togglePrint(\'%s\',%d);" /></td>',
                    (isset($prints[$oid])?'checked':''),
                    $oid,$username,$oid);
            $ret .= '</tr>';

            fwrite($fptr,$w['order_id']."\n");
        }
        fclose($fptr);
        $ret .= "</tbody></table>";
        $ret .= '<p>
            <button type="submit" class="btn btn-default">Print Selected</button>
            </p>
            </form>';

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
    var f4 = $('#f_4').val();

    var loc = '?f1='+f1+'&f2='+f2+'&f3='+f3+'&f4='+f4;
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
        url: 'OrderAjax.php',
        type: 'post',
        data: 'id='+oid+'&status='+val,
        dataType: 'json',
        cache: false
    }).done(function(resp){
        $('#statusdate'+oid).html(resp.tdate);
        if (resp.sentEmail) {
            alert('Emailed Arrival Notification');
        }
    });
}
function togglePrint(username,oid){
    $.ajax({
        url: 'OrderViewPage.php',
        type: 'post',
        data: 'orderID='+oid+'&togglePrint=1',
        cache: false
    });
}
JAVASCRIPT;
    }

    public function unitTest($phpunit)
    {
        $this->connection->throwOnFailure(true);
        $phpunit->assertEquals(true, $this->get_handler());
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->javascriptContent()));
        $form = new COREPOS\common\mvc\ValueContainer();
        $form->f1 = 1;
        $form->f2 = 1;
        $form->f3 = 'ACME';
        $this->setForm($form);
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $form->f3 = '';
        $this->setForm($form);
        $phpunit->assertNotEquals(0, strlen($this->get_view()));

        $items = array(1=>'foobar');
        $long = $this->limitTextSize($items, 'foo', 10);
        $short = $this->limitTextSize($items, 'foo', 3);
        $phpunit->assertNotEquals($long, $short);

        $arr = $this->appendByID(array(), 1, 'foo');
        $arr = $this->appendByID($arr, 1, 'foo');
        $phpunit->assertEquals('foo; foo', $arr[1]);
    }
}

FannieDispatch::conditionalExec();

