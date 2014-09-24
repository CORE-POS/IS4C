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

    protected $window_dressing = false;

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
                $where .= ' AND (x.manufacturer LIKE ? OR v.brand LIKE ?) ';
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
            if (!strstr($from, 'superdepts')) {
                $from .= ' LEFT JOIN superdepts AS s ON p.department = s.dept_ID ';
            }
            $where .= ' AND s.superID = ? ';
            $args[] = $superID;
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

        $modDate = FormLib::get('modDate');
        if ($modDate !== '') {
            switch(FormLib::get('modOp')) {
            case 'Before':
                $where .= ' AND p.modified < ? ';
                $args[] = $modDate . ' 00:00:00';
                break;
            case 'After':
                $where .= ' AND p.modified > ? ';
                $args[] = $modDate . ' 23:59:59';
                break;
            case 'On':
            default:
                $where .= ' AND p.modified BETWEEN ? AND ? ';
                $args[] = $modDate . ' 00:00:00';
                $args[] = $modDate . ' 23:59:59';
                break;
            }
        }

        $vendorID = FormLib::get('vendor');
        if ($vendorID !== '') {
            $where .= ' AND v.vendorID=? ';
            $args[] = $vendorID;
            if (!strstr($from, 'vendorItems')) {
                $from .= ' LEFT JOIN vendorItems AS v ON p.upc=v.upc ';
            }

            if (FormLib::get('vendorSale')) {
                $where .= ' AND v.saleCost <> 0 ';
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

        $hobart = FormLib::get('serviceScale');
        if ($hobart !== '') {
            $from .= ' INNER JOIN scaleItems AS h ON h.plu=p.upc ';
            $where = str_replace('p.modified', 'h.modified', $where);
        }

        if ($where == '1=1') {
            echo 'Too many results';
            return false;
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);
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

        $dataStr = http_build_query($_GET);
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

    private function streamOutput($data) {
        $ret = $dataStr;
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr>
                <th><input type="checkbox" onchange="toggleAll(this, \'.upcCheckBox\');" /></th>
                <th>UPC</th><th>Desc</th><th>Super</th><th>Dept</th>
                <th>Retail</th><th>On Sale</th><th>Sale</th>
                </tr>';
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
        $ret .= '</table>';

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

    $('#resultArea').html('Searching');
    $.ajax({
        url: 'AdvancedItemSearch.php',
        type: 'get',
        data: 'search=1&' + dstr,
        success: function(data) {
            $('#resultArea').html(data);
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
        <?php
        return ob_get_clean();
    }

    public function get_init_view()
    {
        return $this->get_view();
    }

    function get_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
        $this->add_script($FANNIE_URL.'src/javascript/jquery-ui.js');
        $this->add_css_file($FANNIE_URL.'src/style.css');
        $this->add_css_file($FANNIE_URL.'src/javascript/jquery-ui.css');

        $ret = '<!doctype html><html><head><title>Advanced Search</title></head><body>';
        $ret .= '<div style="float:left;">';

        $ret .= '<form method="post" id="searchform" onsubmit="getResults(); return false;" onreset="formReset();">';
        $ret .= '<table>';    

        $ret .= '<tr>';

        $ret .= '<th>UPC</th><td><input type="text" size="12" name="upc" /></td>';

        $ret .= '<th>Super Dept</th><td><select name="superID"><option value="">Select...</option>';
        $supers = $dbc->query('SELECT superID, super_name FROM superDeptNames order by superID');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['superID'], $row['super_name']);
        }
        $ret .= '</select></td>';

        $ret .= '<th>Modified</th>';
        $ret .= '<td><select name="modOp"><option>On</option><option>Before</option><option>After</option></select>';
        $ret .= '<td><input type="text" name="modDate" id="modDate" size="10" /></td>';
        $this->add_onload_command("\$('#modDate').datepicker();\n");

        $ret .= '</tr><tr>';

        $ret .= '<th>Brand</th><td><input type="text" size="12" name="brand" /></td>';

        $ret .= '<th>Dept Start</th><td><select name="deptStart"><option value="">Select...</option>';
        $supers = $dbc->query('SELECT dept_no, dept_name FROM departments order by dept_no');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $row['dept_no'], $row['dept_no'], $row['dept_name']);
        }
        $ret .= '</select></td>';

        $ret .= '<th>Movement</th>';
        $ret .= '<td colspan="2"><select name="soldOp"><option value="">n/a</option><option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option><option value="90">Last 90 days</option></select>';

        $ret .= '&nbsp;&nbsp;&nbsp;<label for="in_use">InUse</label>
				<input type="checkbox" class="saleField" name="in_use" id="in_use" value="1" /></td>'; 

        $ret .= '</tr><tr>';

        $ret .= '<th>Description</th><td><input type="text" size="12" name="description" /></td>';

        $ret .= '<th>Dept End</th><td><select name="deptEnd"><option value="">Select...</option>';
        $supers = $dbc->query('SELECT dept_no, dept_name FROM departments order by dept_no');
        while($row = $dbc->fetch_row($supers)) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $row['dept_no'], $row['dept_no'], $row['dept_name']);
        }
        $ret .= '</select></td>';
        
        $ret .= '<th>Vendor</th><td colspan="2"><select name="vendor"
                    onchange="if(this.value===\'\') $(\'#vendorSale\').attr(\'disabled\',\'disabled\'); else $(\'#vendorSale\').removeAttr(\'disabled\');" >
                    <option value="">Any</option>';
        $vendors = $dbc->query('SELECT vendorID, vendorName FROM vendors');
        while($row = $dbc->fetch_row($vendors)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['vendorID'], $row['vendorName']);
        }
        $ret .= '</select></td>';

        $ret .= '</tr><tr>';

        $ret .= '<th>Tax</th><td><select name="tax"><option value="">Any</option><option value="0">NoTax</option>';
        $taxes = $dbc->query('SELECT id, description FROM taxrates');
        while($row = $dbc->fetch_row($taxes)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['id'], $row['description']);
        }
        $ret .= '</select></td>';

        $ret .= '<th>Local</th><td colspan="3"><select name="local"><option value="">Any</option><option value="0">No</option>';
        $origins = $dbc->query('SELECT originID, shortName FROM originName WHERE local=1');
        while($row = $dbc->fetch_row($origins)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['originID'], $row['shortName']);
        }
        $ret .= '</select>';

        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>FS</b>: <select name="fs"><option value="">Any</option><option value="1">Yes</option><option value="0">No</option></select>';
        $ret .= '&nbsp;&nbsp;&nbsp;';
        $ret .= '<b>Discountable</b>: <select name="discountable"><option value="">Any</option><option value="1">Yes</option><option value="0">No</option></select>';

        $ret .= '</td>'; 

        $ret .= '<td><label for="vendorSale">On Vendor Special</label> <input type="checkbox" id="vendorSale" name="vendorSale" disabled /></td>';

        $ret .= '</tr><tr>';

        $ret .= '<th>Origin</th>';
        $ret .= '<td><select name="originID"><option value="0">n/a</option>';
        $origins = $dbc->query('SELECT originID, shortName FROM origins WHERE local=0 ORDER BY shortName');
        while($row = $dbc->fetch_row($origins)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['originID'], $row['shortName']);
        }
        $ret .= '</select>';
        $ret .= '</td>';

        $ret .= '<th>Likecode</th>';
        $ret .= '<td colspan="3"><select name="likeCode"><option value="">n/a</option>
                <option value="ANY">In Any Likecode</option>
                <option value="NONE">Not in a Likecode</option>';
        $lcs = $dbc->query('SELECT likeCode, likeCodeDesc FROM likeCodes ORDER BY likeCode');
        while($row = $dbc->fetch_row($lcs)) {
            $ret .= sprintf('<option value="%d">%d %s</option>', $row['likeCode'], $row['likeCode'], $row['likeCodeDesc']);
        }
        $ret .= '</select></td>';

        $ret .= '<td><label for="serviceScale">Service Scale</label> <input type="checkbox" id="serviceScale" name="serviceScale" /></td>';

        $ret .= '</tr><tr>';

        $ret .= '<th>In Sale Batch</th><td><select name="onsale"
                    onchange="if(this.value===\'\') $(\'.saleField\').attr(\'disabled\',\'disabled\'); else $(\'.saleField\').removeAttr(\'disabled\');" >
                    <option value="">Any</option>';
        $ret .= '<option value="1">Yes</option><option value="0">No</option>';
        $ret .= '</td>';

        $ret .= '<th>Sale Type</th><td><select disabled class="saleField" name="saletype"><option value="">Any</option>';
        $vendors = $dbc->query('SELECT batchTypeID, typeDesc FROM batchType WHERE discType <> 0');
        while($row = $dbc->fetch_row($vendors)) {
            $ret .= sprintf('<option value="%d">%s</option>', $row['batchTypeID'], $row['typeDesc']);
        }
        $ret .= '</select></td>';

        $ret .= '<td colspan="3">';
        $ret .= '<input type="checkbox" disabled class="saleField" name="sale_all" id="sale_all" value="1" /> 
                <label for="sale_all">All Sales</label> ';
        $ret .= '<input type="checkbox" disabled class="saleField" name="sale_past" id="sale_past" value="1" /> 
                <label for="sale_past">Past</label> ';
        $ret .= '<input type="checkbox" disabled class="saleField" name="sale_current" id="sale_current" value="1" /> 
                <label for="sale_current">Current</label> ';
        $ret .= '<input type="checkbox" disabled class="saleField" name="sale_upcoming" id="sale_upcoming" value="1" /> 
                <label for="sale_upcoming">Upcoming</label> ';
        
        $ret .= '</tr>';

        $ret .= '</table>';
        $ret .= '<input type="submit" value="Find Items" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="reset" value="Clear Settings" />';
        $ret .= '</form>';

        $ret .= '</div>';
        $ret .= '<div style="float:left;">';
        $ret .= '<fieldset><legend>Selected Items</legend>';
        $ret .= '<input type="submit" value="Create Price or Sale Batch" onclick="goToBatch();" />';
        $ret .= '<br />';
        $ret .= '<input style="margin-top:10px;" type="submit" value="Edit Items" onclick="goToEdit();" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input style="margin-top:10px;" type="submit" value="Tags/Signs" onclick="goToSigns();" />';
        $ret .= '<br />';
        $ret .= '<input style="margin-top:10px;" type="submit" value="Margins" onclick="goToMargins();" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input style="margin-top:10px;" type="submit" value="Scale Sync" onclick="goToSync();" />';
        $ret .= '</fieldset>';
        $ret .= '<fieldset><legend>Report on Items</legend>';
        $ret .= '<select id="reportURL">';
        $ret .= sprintf('<option value="%sreports/from-search/PercentageOfSales/PercentageOfSalesReport.php">
                        %% of Sales</option>', $FANNIE_URL);
        $ret .= '</select> ';
        $ret .= '<input style="margin-top:10px;" type="submit" value="Get Report" onclick="goToReport();" />';
        $ret .= '</fieldset>';
        $ret .= '<form method="post" id="actionForm" target="__advs_act"></form>';
        $ret .= '</div>';
        
        $ret .= '<div style="clear:left;"></div>';

        $ret .= '<hr />';

        $ret .= '<div id="resultArea"></div>';
        $ret .= '</body></html>';

        return $ret;
    }

}

FannieDispatch::conditionalExec();

