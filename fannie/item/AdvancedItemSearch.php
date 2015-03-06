<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class AdvancedItemSearch extends FannieRESTfulPage
{
    protected $header = 'Advanced Search';
    protected $title = 'Advanced Search';

    public $description = '[Advanced Search] is a tool to look up items with lots of search options.';
    public $themed = true;

    function preprocess()
    {
        $this->__routes[] = 'get<search>';
        $this->__routes[] = 'post<search>';
        $this->__routes[] = 'post<upc>';
        $this->__routes[] = 'get<init>';
        return parent::preprocess();
    }

    // failover on ajax call
    // if javascript breaks somewhere and the form
    // winds up submitted, at least display the results
    private $post_results = '';
    function post_upc_handler()
    {
        ob_start();
        $this->get_search_handler();
        $this->post_results = ob_get_clean();

        return true;
    }

    // failover on ajax call
    function post_upc_view()
    {
        return $this->get_view() . $this->post_results;
    }

    function post_search_handler()
    {
        return $this->get_search_handler();
    }

    function get_search_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        /**
          Step 1:
          Get a preliminary item set by querying products table
        */

        $from = 'products AS p 
                LEFT JOIN departments AS d ON p.department=d.dept_no
                LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID';
        $where = '1=1';
        $args = array();

        $upc = FormLib::get('upc');
        if ($upc !== '') {
            if (strstr($upc, '*')) {
                $upc = str_replace('*', '%', $upc);
                $where .= ' AND p.upc LIKE ? ';
                $args[] = $upc;
            } elseif (substr(BarcodeLib::padUPC($upc), 0, 8) == '00499999') {
                $couponID = (int)substr(BarcodeLib::padUPC($upc), 8);
                $from .= ' LEFT JOIN houseCouponItems AS h ON p.upc=h.upc ';
                $where .= ' AND h.coupID=? ';
                $args[] = $couponID;
            } else {
                $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);
                $where .= ' AND p.upc = ? ';
                $args[] = $upc;
            }
        }

        $desc = FormLib::get('description');
        if ($desc !== '') {
            $where .= ' AND p.description LIKE ? ';
            $args[] = '%' . $desc . '%';
        }

        $brand = FormLib::get('brand');
        if ($brand !== '') {
            if (is_numeric($brand)) {
                $where .= ' AND p.upc LIKE ? ';
                $args[] = '%' . $brand . '%';
            } else {
                $where .= ' AND (p.brand LIKE ? OR x.manufacturer LIKE ? OR v.brand LIKE ?) ';
                $args[] = '%' . $brand . '%';
                $args[] = '%' . $brand . '%';
                $args[] = '%' . $brand . '%';
                if (!strstr($from, 'prodExtra')) {
                    $from .= ' LEFT JOIN prodExtra AS x ON p.upc=x.upc ';
                }
                if (!strstr($from, 'vendorItems')) {
                    $from .= ' LEFT JOIN vendorItems AS v ON p.upc=v.upc ';
                }
            }
        }

        $superID = FormLib::get('superID');
        if ($superID !== '') {
            /**
              Unroll superdepartment into a list of department
              numbers so products.department index can be utilizied
            */
            $superP = $dbc->prepare('
                SELECT dept_ID
                FROM superdepts
                WHERE superID=?'
            );
            $superR = $dbc->execute($superP, array($superID));
            if ($superR && $dbc->numRows($superR) > 0) {
                $where .= ' AND p.department IN (';
                while ($superW = $dbc->fetch_row($superR)) {
                    $where .= '?,';
                    $args[] = $superW['dept_ID'];
                }
                $where = substr($where, 0, strlen($where)-1) . ') ';
            }
        }

        $dept1 = FormLib::get('deptStart');
        $dept2 = FormLib::get('deptEnd');
        if ($dept1 !== '' || $dept2 !== '') {
            // work with just one department field set
            if ($dept1 === '') {
                $dept1 = $dept2;
            } else if ($dept2 === '') {
                $dept2 = $dept1;
            }
            // swap order if needed
            if ($dept2 < $dept1) {
                $tmp = $dept1;
                $dept1 = $dept2;
                $dept2 = $tmp;
            }
            $where .= ' AND p.department BETWEEN ? AND ? ';
            $args[] = $dept1;
            $args[] = $dept2;
        }

        $hobart = FormLib::get('serviceScale');
        if ($hobart !== '') {
            $from .= ' INNER JOIN scaleItems AS h ON h.plu=p.upc ';
            $where = str_replace('p.modified', 'h.modified', $where);
        }


        $modDate = FormLib::get('modDate');
        if ($modDate !== '') {
            switch(FormLib::get('modOp')) {
            case 'Before':
                $where .= ' AND p.modified < ? ';
                $args[] = $modDate . ' 00:00:00';
                if ($hobart !== '') {
                    $where = str_replace('p.modified', '(p.modified', $where)
                        . ' OR h.modified < ?) ';
                    $args[] = $modDate . ' 00:00:00';
                }
                break;
            case 'After':
                $where .= ' AND p.modified > ? ';
                $args[] = $modDate . ' 23:59:59';
                if ($hobart !== '') {
                    $where = str_replace('p.modified', '(p.modified', $where)
                        . ' OR h.modified > ?) ';
                    $args[] = $modDate . ' 23:59:59';
                }
                break;
            case 'On':
            default:
                $where .= ' AND p.modified BETWEEN ? AND ? ';
                $args[] = $modDate . ' 00:00:00';
                $args[] = $modDate . ' 23:59:59';
                if ($hobart !== '') {
                    $where = str_replace('p.modified', '(p.modified', $where)
                        . ' OR h.modified BETWEEN ? AND ?) ';
                    $args[] = $modDate . ' 00:00:00';
                    $args[] = $modDate . ' 23:59:59';
                }
                break;
            }
        }

        $vendorID = FormLib::get('vendor');
        if ($vendorID !== '') {
            $where .= ' AND (v.vendorID=? or p.default_vendor_id=?)';
            $args[] = $vendorID;
            $args[] = $vendorID;
            if (!strstr($from, 'vendorItems')) {
                $from .= ' LEFT JOIN vendorItems AS v ON p.upc=v.upc ';
            }

            if (FormLib::get('vendorSale')) {
                $where .= ' AND v.saleCost <> 0 ';
                $where .= ' AND p.default_vendor_id=? ';
                $where .= ' AND p.default_vendor_id=v.vendorID ';
                $args[] = $vendorID;
            }
        }

        $tax = FormLib::get('tax');
        if ($tax !== '') {
            $where .= ' AND p.tax=? ';
            $args[] = $tax;
        }

        $local = FormLib::get('local');
        if ($local !== '') {
            $where .= ' AND p.local=? ';
            $args[] = $local;
        }

        $fs = FormLib::get('fs');
        if ($fs !== '') {
            $where .= ' AND p.foodstamp=? ';
            $args[] = $fs;
        }

		$inUse = FormLib::get('in_use');
		if ($inUse !== '') {
			$where .= ' AND p.inUse=? ';
			$args[] = $inUse;
		}

        $discount = FormLib::get('discountable');
        if ($discount !== '') {
            $where .= ' AND p.discount=? ';
            $args[] = $discount;
        }

        $signinfo = FormLib::get('signinfo');
        if ($signinfo !== '') {
            if (!strstr($from, 'productUser')) {
                $from .= ' LEFT JOIN productUser AS s ON p.upc=s.upc ';
            }
            if ($signinfo == '1') {
                $where .= " AND s.brand IS NOT NULL 
                    AND s.description IS NOT NULL
                    AND s.brand <> ''
                    AND s.description <> '' ";
            } else {
                $where .= " AND (s.brand IS NULL 
                    OR s.description IS NULL
                    OR s.brand = ''
                    OR s.description = '') ";
            }
        }

        $origin = FormLib::get('originID', 0);
        if ($origin != 0) {
            $from .= ' INNER JOIN ProductOriginsMap AS g ON p.upc=g.upc ';
            $where .= ' AND (p.current_origin_id=? OR g.originID=?) ';
            $args[] = $origin;
            $args[] = $origin;
        }

        $lc = FormLib::get('likeCode');
        if ($lc !== '') {
            if (!strstr($from, 'upcLike')) {
                $from .= ' LEFT JOIN upcLike AS u ON p.upc=u.upc ';
            }
            if ($lc == 'ANY') {
                $where .= ' AND u.upc IS NOT NULL ';
            } else if ($lc == 'NONE') {
                $where .= ' AND u.upc IS NULL ';
            } else {
                $where .= ' AND u.likeCode=? ';
                $args[] = $lc;
            }
        }

        if ($where == '1=1') {
            echo 'Too many results';
            return false;
        }

        $query = 'SELECT p.upc, p.description, m.super_name, p.department, d.dept_name,
                 p.normal_price, p.special_price,
                 CASE WHEN p.discounttype > 0 THEN \'X\' ELSE \'-\' END as onSale,
                 0 as selected
                 FROM ' . $from . ' WHERE ' . $where;
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $items = array();
        while($row = $dbc->fetch_row($result)) {
            $items[$row['upc']] = $row;
        }

        /**
          Step two:
          Filter results based on sale-related
          parameters
        */
        $sale = FormLib::get('onsale');
        if ($sale !== '') {

            $where = '1=1';
            $args = array();

            $saletype = FormLib::get('saletype');
            if ($saletype !== '') {
                $where .= ' AND b.batchType = ? ';
                $args[] = $saletype;
            }

            $all = FormLib::get('sale_all', 0);
            $prev = FormLib::get('sale_past', 0);
            $now = FormLib::get('sale_current', 0);
            $next = FormLib::get('sale_upcoming', 0);
            // all=1 or all three times = 1 means no date filter
            if ($all == 0 && ($prev == 0 || $now == 0 || $next == 0)) {
                // all permutations where one of the times is zero
                if ($prev == 1 && $now == 1) {
                    $where .= ' AND b.endDate <= ' . $dbc->curdate();
                } else if ($prev == 1 && $next == 1) {
                    $where .= ' AND (b.endDate < ' . $dbc->curdate() . ' OR b.startDate > ' . $dbc->curdate() . ') ';
                } else if ($prev == 1) {
                    $where .= ' AND b.endDate < ' . $dbc->curdate();
                } else if ($now == 1 && $next == 1) {
                    $where .= ' AND b.endDate >= ' . $dbc->curdate();
                } else if ($now == 1) {
                    $where .= ' AND b.endDate >= ' . $dbc->curdate() . ' AND b.startDate <= ' . $dbc->curdate();
                } else if ($next == 1) {
                    $where .= ' AND b.startDate > ' .$dbc->curdate();
                }
            }

            $query = 'SELECT l.upc FROM batchList AS l INNER JOIN batches AS b
                        ON b.batchID=l.batchID WHERE ' . $where . ' 
                        GROUP BY l.upc';
            $prep = $dbc->prepare($query);
            $result = $dbc->execute($prep, $args);
            $saleUPCs = array();
            while($row = $dbc->fetch_row($result)) {
                $saleUPCs[] = $row['upc'];
            }

            if ($sale == 0) {
                // only items that are not selected sales
                foreach($saleUPCs as $s_upc) {
                    if (isset($items[$s_upc])) {
                        unset($items[$s_upc]);
                    }
                }
            } else {
                // noly items that are in selected sales
                // collect items in both sets
                $valid = array();
                foreach($saleUPCs as $s_upc) {
                    if (isset($items[$s_upc])) {
                        $valid[$s_upc] = $items[$s_upc];
                    }
                }
                $items = $valid;
            }
        }

        /**
          Filter by movement
        */
        $movementFilter = FormLib::get('soldOp');
        if ($movementFilter !== '') {
            $movementStart = date('Y-m-d', mktime(0, 0, 0, date('n'), date('j')-$movementFilter-1, date('Y')));
            $movementEnd = date('Y-m-d', strtotime('yesterday'));
            $dlog = DTransactionsModel::selectDlog($movementStart, $movementEnd);

            $args = array($movementStart.' 00:00:00', $movementEnd.' 23:59:59');
            $in = '';
            foreach($items as $upc => $info) {
                $in .= '?,';
                $args[] = $upc;
            }
            $in = substr($in, 0, strlen($in)-1);

            $query = "SELECT t.upc
                      FROM $dlog AS t
                      WHERE tdate BETWEEN ? AND ?
                        AND t.upc IN ($in)
                      GROUP BY t.upc";
            $prep = $dbc->prepare($query);
            $result = $dbc->execute($prep, $args);
            $valid = array();
            while($row = $dbc->fetch_row($result)) {
                if (isset($items[$row['upc']])) {
                    $valid[$row['upc']] = $items[$row['upc']];
                }
            }
            $items = $valid;
        }

        $savedItems = FormLib::get('u', array());
        if (is_array($savedItems) && count($savedItems) > 0) {
            $savedQ = 'SELECT p.upc, p.description, m.super_name, p.department, d.dept_name,
                        p.normal_price, p.special_price,
                        CASE WHEN p.discounttype > 0 THEN \'X\' ELSE \'-\' END as onSale,
                        1 as selected
                       FROM products AS p 
                        LEFT JOIN departments AS d ON p.department=d.dept_no
                        LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                       WHERE p.upc IN (';
            foreach ($savedItems as $item) {
                $savedQ .= '?,';
            }
            $savedQ = substr($savedQ, 0, strlen($savedQ)-1);
            $savedQ .= ')';

            $savedP = $dbc->prepare($savedQ);
            $savedR = $dbc->execute($savedP, $savedItems);
            while ($savedW = $dbc->fetch_row($savedR)) {
                if (isset($items[$savedW['upc']])) {
                    $items[$savedW['upc']]['selected'] = 1;
                } else {
                    $items[$savedW['upc']] = $savedW;
                }
            }
        }

        if (count($items) > 2500) {
            echo 'Too many results';
            return false;
        }

        $dataStr = http_build_query($_GET);
        echo 'Found ' . count($items) . ' items';
        echo '&nbsp;&nbsp;&nbsp;&nbsp;';
        echo '<a href="AdvancedItemSearch.php?init=' . base64_encode($dataStr) . '">Permalink for this Search</a>';
        echo $this->streamOutput($items);

        return false;
    }

    public function get_init_handler()
    {
        $vars = base64_decode($this->init);
        parse_str($vars, $data);
        foreach ($data as $field_name => $field_val) {
            $this->add_onload_command('$(\'#searchform :input[name="' . $field_name . '"]\').val(\'' . $field_val . '\');' . "\n");
        }
        $this->add_onload_command('getResults();' . "\n");

        return true;
    }

    private function streamOutput($data) 
    {
        $ret = '';
        $ret .= '<table class="table search-table">';
        $ret .= '<thead><tr>
                <th><input type="checkbox" onchange="toggleAll(this, \'.upcCheckBox\');" /></th>
                <th>UPC</th><th>Desc</th><th>Super</th><th>Dept</th>
                <th>Retail</th><th>On Sale</th><th>Sale</th>
                </tr></thead><tbody>';
        foreach($data as $upc => $record) {
            $ret .= sprintf('<tr>
                            <td><input type="checkbox" name="u[]" class="upcCheckBox" value="%s" %s /></td>
                            <td><a href="ItemEditorPage.php?searchupc=%s" target="_advs%s">%s</a></td>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%d %s</td>
                            <td>$%.2f</td>
                            <td>%s</td>
                            <td>$%.2f</td>
                            </tr>', 
                            $upc, ($record['selected'] == 1 ? 'checked' : ''),
                            $upc, $upc, $upc,
                            $record['description'],
                            $record['super_name'],
                            $record['department'], $record['dept_name'],
                            $record['normal_price'],
                            $record['onSale'],
                            $record['special_price']
            );
        }
        $ret .= '</tbody></table>';

        return $ret;
    }

    function javascript_content()
    {
        ob_start();
        ?>
function getResults() {
    var dstr = $('#searchform').serialize();
    $('.upcCheckBox:checked').each(function(){
        dstr += '&u[]='+$(this).val();
    });

    $('.progress').show();
    $('#resultArea').html('');
    $.ajax({
        url: 'AdvancedItemSearch.php',
        type: 'get',
        data: 'search=1&' + dstr,
        success: function(data) {
            $('.progress').hide();
            $('#resultArea').html(data);
            $('.search-table').tablesorter({headers: { 0: { sorter:false } } });
        }
    });
}
function toggleAll(elem, selector) {
    if (elem.checked) {
        $(selector).prop('checked', true);
    } else {
        $(selector).prop('checked', false);
    }
}
// helper: add all selected upc values to hidden form
// as hidden input tags. the idea is to submit UPCs
// to the handling page via POST because the amount of
// data might not fit in the query string. the hidden 
// form also opens in a new tab/window so search
// results are not lost
function getItems() {
    $('#actionForm').empty();
    var ret = false;
    $('.upcCheckBox:checked').each(function(){
        $('#actionForm').append('<input type="hidden" name="u[]" value="' + $(this).val() + '" />');
        ret = true;
    });
    return ret;
}
function goToBatch() {
    if (getItems()) {
        $('#actionForm').attr('action', '../batches/BatchFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToEdit() {
    if (getItems()) {
        $('#actionForm').attr('action', 'EditItemsFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToList() {
    if (getItems()) {
        $('#actionForm').attr('action', 'ProductListPage.php');
        $('#actionForm').append('<input type="hidden" name="supertype" id="supertype-field" value="upc" />');
        $('#actionForm').submit();
    }
}
function goToSigns() {
    if (getItems()) {
        $('#actionForm').attr('action', '../admin/labels/SignFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToMargins() {
    if (getItems()) {
        $('#actionForm').attr('action', 'MarginToolFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToSync() {
    if (getItems()) {
        $('#actionForm').attr('action', 'hobartcsv/SyncFromSearch.php');
        $('#actionForm').submit();
    }
}
function goToReport() {
    if (getItems()) {
        $('#actionForm').attr('action', $('#reportURL').val());
        $('#actionForm').submit();
    }
}
function formReset()
{
    $('#vendorSale').attr('disabled', 'disabled');
    $('.saleField').attr('disabled', 'disabled');
}
function chainSuper(superID)
{
    if (superID === '') {
        superID = -1;
    }
    var req = {
        jsonrpc: '2.0',
        method: '\\COREPOS\\Fannie\\API\\webservices\\FannieDeptLookup',
        id: new Date().getTime(),
        params: {
            'type' : 'children',
            'superID' : superID
        }
    };
    $.ajax({
        url: '../ws/',
        type: 'post',
        data: JSON.stringify(req),
        dataType: 'json',
        contentType: 'application/json',
        success: function(resp) {
            if (resp.result) {
                $('#dept-start').empty().append('<option value="">Select Start...</option>');
                $('#dept-end').empty().append('<option value="">Select End...</option>');
                for (var i=0; i<resp.result.length; i++) {
                    var opt = $('<option>').val(resp.result[i]['id'])
                        .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                    $('#dept-start').append(opt.clone());
                    $('#dept-end').append(opt);
                }
            }
        }
    });
}
        <?php
        return ob_get_clean();
    }

    public function get_init_view()
    {
        return $this->get_view();
    }

    public function css_content()
    {
        return '
                .search-table thead th {
                    cursor: hand;
                    cursor: pointer;
                }
            ';
    }

    function get_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $ret = '<div class="col-sm-10">';

        $ret .= '<form method="post" id="searchform" onsubmit="getResults(); return false;" onreset="formReset();">';

        $ret .= '<table class="table table-bordered">';
        $ret .= '<tr>';

        $ret .= '<td>
            <label class="control-label">UPC</label>
            </td>
            <td>
                <input type="text" name="upc" class="form-control input-sm" 
                    placeholder="UPC or PLU" />
            </td>';

        $ret .= '<td>
            <label class="control-label">Descript.</label>
            </td>
            <td>
            <input type="text" name="description" class="form-control input-sm" 
                placeholder="Item Description" />
            </td>';

        $ret .= '<td>
            <label class="control-label">Brand</label>
            </td>
            <td>
            <input type="text" name="brand" class="form-control input-sm" 
                placeholder="Brand Name" id="brand-field" />
            </td>';

        $ret .= '</tr><tr>';

        $ret .= '<td>
            <label class="control-label small">Super Dept</label>
            </td>
            <td>
            <select name="superID" class="form-control input-sm" onchange="chainSuper(this.value);" >
                <option value="">Select Super...</option>';
        $supers = $dbc->query('SELECT superID, super_name FROM superDeptNames order by superID');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['superID'], $row['super_name']);
        }
        $ret .= '</select></td>';

        $ret .= '<td>
            <label class="control-label small">Dept Start</label>
            </td>
            <td>
            <select name="deptStart" id="dept-start" class="form-control input-sm">
                <option value="">Select Start...</option>';
        $supers = $dbc->query('SELECT dept_no, dept_name FROM departments order by dept_no');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $row['dept_no'], $row['dept_no'], $row['dept_name']);
        }
        $ret .= '</select></td>';

        $ret .= '<td>
            <label class="control-label small">Dept End</label>
            </td>
            <td>
            <select name="deptEnd" id="dept-end" class="form-control input-sm">
                <option value="">Select End...</option>';
        $supers = $dbc->query('SELECT dept_no, dept_name FROM departments order by dept_no');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $row['dept_no'], $row['dept_no'], $row['dept_name']);
        }
        $ret .= '</select></td>';

        $ret .= '</tr><tr>'; // end row

        $ret .= '<td>
            <select class="form-control input-sm" name="modOp">
                <option>Modified On</option>
                <option>Modified Before</option>
                <option>Modified After</option>
            </select>
            </td>
            <td>
            <input type="text" name="modDate" id="modDate" class="form-control input-sm date-field" 
                    placeholder="Modified date" />
           </td>';

        $ret .= '<td>
                <label class="control-label small">Movement</label>
                </td>
                <td>
                <select name="soldOp" class="form-control input-sm"><option value="">n/a</option><option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option><option value="90">Last 90 days</option></select>
                </td>';

        $ret .= '<td>
                    <label class="control-label">Vendor</label>
                 </td>
                 <td>
                    <select name="vendor" class="form-control input-sm"
                    onchange="if(this.value===\'\') $(\'#vendorSale\').attr(\'disabled\',\'disabled\'); else $(\'#vendorSale\').removeAttr(\'disabled\');" >
                    <option value="">Any</option>';
        $vendors = $dbc->query('SELECT vendorID, vendorName FROM vendors ORDER BY vendorName');
        while ($row = $dbc->fetch_row($vendors)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['vendorID'], $row['vendorName']);
        }
        $ret .= '</select></td>';

        $ret .= '</tr><tr>'; // end row

        $ret .= '<td>
            <label class="control-label">Origin</label>
            </td>';
        $ret .= '<td>
            <select name="originID" class="form-control input-sm"><option value="0">Any Origin</option>';
        $origins = $dbc->query('SELECT originID, shortName FROM origins WHERE local=0 ORDER BY shortName');
        while($row = $dbc->fetch_row($origins)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['originID'], $row['shortName']);
        }
        $ret .= '</select></td>';

        $ret .= '<td>
                <label class="control-label">Likecode</label> 
                </td>';
        $ret .= '<td>
            <select name="likeCode" class="form-control input-sm"><option value="">Choose Like Code</option>
                <option value="ANY">In Any Likecode</option>
                <option value="NONE">Not in a Likecode</option>';
        $lcs = $dbc->query('SELECT likeCode, likeCodeDesc FROM likeCodes ORDER BY likeCode');
        while($row = $dbc->fetch_row($lcs)) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $row['likeCode'], $row['likeCode'], $row['likeCodeDesc']);
        }
        $ret .= '</select></td>';

        $ret .= '<td colspan="2">
            <label class="small" for="vendorSale">
            On Vendor Sale
            <input type="checkbox" id="vendorSale" name="vendorSale" class="checkbox-inline" disabled />
            </label>';
        $ret .= ' | 
                <label class="small" for="in_use">
                InUse
				<input type="checkbox" name="in_use" id="in_use" value="1" checked class="checkbox-inline" />
                </label>
                </td>'; 

        $ret .= '</tr><tr>';

        $ret .= '<td colspan="2" class="form-inline">
            <label class="control-label">Tax</label>
            <select name="tax" class="form-control input-sm"><option value="">Any</option><option value="0">NoTax</option>';
        $taxes = $dbc->query('SELECT id, description FROM taxrates');
        while($row = $dbc->fetch_row($taxes)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['id'], $row['description']);
        }
        $ret .= '</select>';

        $ret .= '&nbsp;&nbsp;
            <label class="control-label">FS</label>
            <select name="fs" class="form-control input-sm">
            <option value="">Any</option><option value="1">Yes</option><option value="0">No</option></select>
            </td>';

        $ret .= '<td colspan="2" class="form-inline">
            <label class="control-label">Local</label>
            <select name="local" class="form-control input-sm"><option value="">Any</option><option value="0">No</option>';
        $origins = $dbc->query('SELECT originID, shortName FROM originName WHERE local=1');
        while($row = $dbc->fetch_row($origins)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['originID'], $row['shortName']);
        }
        $ret .= '</select> ';

        $ret .= '&nbsp;&nbsp;
            <label class="control-label">%Disc</label>
            <select name="discountable" class="form-control input-sm">
            <option value="">Any</option><option value="1">Yes</option><option value="0">No</option></select>
            </td>';

        $ret .= '<td colspan="2" class="form-inline">
            <label class="small" for="serviceScale">
            Service Scale
            <input type="checkbox" id="serviceScale" name="serviceScale" class="checkbox-inline" />
            </label>';

        $ret .= '&nbsp;&nbsp;
            <label class="control-label small">Sign Info</label>
            <select name="signinfo" class="form-control input-sm">
            <option value="">Any</option><option value="1">Yes</option><option value="0">No</option>
            </select>';

        $ret .= '</td>'; // end row

        $ret .= '</tr><tr>';

        $ret .= '<td colspan="6" class="form-inline">';

        $ret .= '
                <label class="control-label small">In Sale Batch</label>
                <select name="onsale" class="form-control input-sm"
                    onchange="if(this.value===\'\') $(\'.saleField\').attr(\'disabled\',\'disabled\'); else $(\'.saleField\').removeAttr(\'disabled\');" >
                    <option value="">Any</option>';
        $ret .= '<option value="1">Yes</option><option value="0">No</option>';
        $ret .= '</select>';

        $ret .= '&nbsp;&nbsp;
            <label class="control-label small">Sale Type</label>
            <select disabled class="saleField form-control input-sm" name="saletype">
            <option value="">Any Sale Type</option>';
        $vendors = $dbc->query('SELECT batchTypeID, typeDesc FROM batchType WHERE discType <> 0');
        while($row = $dbc->fetch_row($vendors)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['batchTypeID'], $row['typeDesc']);
        }
        $ret .= '</select>';

        $ret .= '&nbsp;&nbsp;
                <label class="small">
                All Sales
                <input type="checkbox" disabled class="saleField checkbox-inline" name="sale_all" id="sale_all" value="1" /> 
                </label> | ';
        $ret .= '<label class="small">
                Past Sales
                <input type="checkbox" disabled class="saleField checkbox-inline" name="sale_past" id="sale_past" value="1" /> 
                </label> | ';
        $ret .= '<label class="small">
                Current Sales
                <input type="checkbox" disabled class="saleField checkbox-inline" name="sale_current" id="sale_current" value="1" /> 
                </label> | ';
        $ret .= '<label class="small">
                Upcoming Sales
                <input type="checkbox" disabled class="saleField checkbox-inline" name="sale_upcoming" id="sale_upcoming" value="1" /> 
                </label>';
        $ret .= '</td>'; 

        $ret .= '</tr></table>';
        
        $ret .= '<button type="submit" class="btn btn-default">Find Items</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="reset" class="btn btn-default">Clear Settings</button>';
        $ret .= '</form>';

        $ret .= '<hr />';
        $ret .= '
            <div class="progress collapse">
                <div class="progress-bar progress-bar-striped active"  role="progressbar" 
                    aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
                    <span class="sr-only">Searching</span>
                </div>
            </div>';
        $ret .= '<div id="resultArea"></div>';

        $ret .= '</div>'; // end col-sm-10

        $ret .= '<div class="col-sm-2">';
        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">Selected Items</div>
            <div class="panel-body">';
        $ret .= '<p><button type="submit" class="btn btn-default btn-xs" 
            onclick="goToBatch();">Price or Sale Batch</button></p>';
        $ret .= '<p><button type="submit" class="btn btn-default btn-xs" 
            onclick="goToEdit();">Group Edit Items</button></p>';
        $ret .= '<p><button type="submit" class="btn btn-default btn-xs" 
            onclick="goToList();">Product List Tool</button></p>';
        $ret .= '<p><button class="btn btn-default btn-xs" type="submit" 
            onclick="goToSigns();">Tags/Signs</button></p>';
        $ret .= '<p><button class="btn btn-default btn-xs" type="submit" 
            onclick="goToMargins();">Margins</button></p>';
        $ret .= '<p><button class="btn btn-default btn-xs" type="submit" 
            onclick="goToSync();">Scale Sync</button></p>';
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">Report on Items</div>
            <div class="panel-body">';
        $ret .= '<select id="reportURL" class="form-control input-sm">';
        $ret .= sprintf('<option value="%sreports/DepartmentMovement/SmartMovementReport.php?date1=%s&date2=%s&lookup-type=u">
                        Movement</option>', $FANNIE_URL, date('Y-m-d'), date('Y-m-d'));
        $ret .= sprintf('<option value="%sreports/from-search/PercentageOfSales/PercentageOfSalesReport.php">
                        %% of Sales</option>', $FANNIE_URL);
        $ret .= '</select> ';
        $ret .= '<p><button class="btn btn-default btn-xs" type="submit" 
            onclick="goToReport();">Get Report</button></p>';
        $ret .= '</div>';
        $ret .= '</div>';
        $ret .= '<form method="post" id="actionForm" target="__advs_act"></form>';
        $ret .= '</div>';

        $this->add_script('autocomplete.js');
        $this->add_onload_command("bindAutoComplete('#brand-field', '../ws/', 'brand');\n");
        $this->addScript('../src/javascript/tablesorter/jquery.tablesorter.js');
        
        return $ret;
    }

}

FannieDispatch::conditionalExec();

