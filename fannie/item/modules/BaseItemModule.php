<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__).'/../../classlib2.0/FannieAPI.php');
}

class BaseItemModule extends ItemModule 
{

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $FANNIE_PRODUCT_MODULES = FannieConfig::config('PRODUCT_MODULES', array());
        $upc = BarcodeLib::padUPC($upc);

        $trimmed = ltrim($upc, '0');
        $barcode_type = '';
        if (strlen($trimmed) == '12') {
            // probably EAN-13 w/o check digi
            $barcode_type = 'EAN';
        } elseif (strlen($trimmed) == 11 && $trimmed[0] == '2') {
            // variable price UPC
            $barcode_type = 'Scale';
        } elseif (strlen($trimmed) <= 11 && strlen($trimmed) >= 6) {
            // probably UPC-A w/o check digit
            $barcode_type = 'UPC';
        } else {
            $barcode_type = 'PLU';
        }

        $ret = '<div id="BaseItemFieldset" class="panel panel-default">';

        $dbc = $this->db();
        $p = $dbc->prepare_statement('SELECT
                                        p.description,
                                        p.pricemethod,
                                        p.normal_price,
                                        p.cost,
                                        CASE 
                                            WHEN p.size IS NULL OR p.size=\'\' OR p.size=\'0\' AND v.size IS NOT NULL THEN v.size 
                                            ELSE p.size 
                                        END AS size,
                                        p.unitofmeasure,
                                        p.modified,
                                        p.special_price,
                                        p.end_date,
                                        p.subdept,
                                        p.department,
                                        p.tax,
                                        p.foodstamp,
                                        p.scale,
                                        p.qttyEnforced,
                                        p.discount,
                                        p.line_item_discountable,
                                        p.brand AS manufacturer,
                                        x.distributor,
                                        u.description as ldesc,
                                        p.default_vendor_id,
                                        v.units AS caseSize,
                                        v.sku,
                                        p.inUse,
                                        p.idEnforced,
                                        p.local,
                                        p.deposit
                                      FROM products AS p 
                                        LEFT JOIN prodExtra AS x ON p.upc=x.upc 
                                        LEFT JOIN productUser AS u ON p.upc=u.upc 
                                        LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id = v.vendorID
                                      WHERE p.upc=?');
        $r = $dbc->exec_statement($p,array($upc));
        $rowItem = array();
        $prevUPC = False;
        $nextUPC = False;
        $likeCode = False;
        if ($dbc->num_rows($r) > 0) {
            //existing item
            $rowItem = $dbc->fetch_row($r);

            /**
              Lookup default vendor & normalize
            */
            $product = new ProductsModel($dbc);
            $product->upc($upc);
            $product->load();
            $vendor = new VendorsModel($dbc);
            $vendor->vendorID($product->default_vendor_id());
            if ($vendor->load()) {
                $rowItem['distributor'] = $vendor->vendorName();
            }

            /* find previous and next items in department */
            $pnP = $dbc->prepare_statement('SELECT upc FROM products WHERE department=? ORDER BY upc');
            $pnR = $dbc->exec_statement($pnP,array($product->department()));
            $passed_it = False;
            while($pnW = $dbc->fetch_row($pnR)){
                if (!$passed_it && $upc != $pnW[0])
                    $prevUPC = $pnW[0];
                else if (!$passed_it && $upc == $pnW[0])
                    $passed_it = True;
                else if ($passed_it){
                    $nextUPC = $pnW[0];
                    break;      
                }
            }

            $lcP = $dbc->prepare_statement('SELECT likeCode FROM upcLike WHERE upc=?');
            $lcR = $dbc->exec_statement($lcP,array($upc));
            if ($dbc->num_rows($lcR) > 0) {
                $lcW = $dbc->fetch_row($lcR);
                $likeCode = $lcW['likeCode'];
            }
        } else {
            // default values for form fields
            $rowItem = array(
                'description' => '',
                'normal_price' => 0,
                'pricemethod' => 0,
                'size' => '',
                'unitofmeasure' => '',
                'modified' => '',
                'ledesc' => '',
                'manufacturer' => '',
                'distributor' => '',
                'default_vendor_id' => 0,
                'department' => 0,
                'subdept' => 0,
                'tax' => 0,
                'foodstamp' => 0,
                'scale' => 0,
                'qttyEnforced' => 0,
                'discount' => 1,
                'line_item_discountable' => 1,
                'caseSize' => '',
                'sku' => '',
                'inUse' => 1,
                'idEnforced' => 0,
                'local' => 0,
                'deposit' => 0,
            );

            /**
              Check for entries in the vendorItems table to prepopulate
              fields for the new item
            */
            $vendorP = "
                SELECT 
                    i.description,
                    i.brand as manufacturer,
                    i.cost,
                    v.vendorName as distributor,
                    d.margin,
                    i.vendorID,
                    s.srp,
                    i.size,
                    i.units,
                    i.sku,
                    i.vendorID as default_vendor_id
                FROM vendorItems AS i 
                    LEFT JOIN vendors AS v ON i.vendorID=v.vendorID
                    LEFT JOIN vendorDepartments AS d ON i.vendorDept=d.deptID
                    LEFT JOIN vendorSRPs AS s ON s.upc=i.upc AND s.vendorID=i.vendorID
                WHERE i.upc=?";
            $args = array($upc);
            $vID = FormLib::get_form_value('vid','');
            if ($vID !== ''){
                $vendorP .= ' AND i.vendorID=?';
                $args[] = $vID;
            }
            $vendorP = $dbc->prepare_statement($vendorP);
            $vendorR = $dbc->exec_statement($vendorP,$args);
            
            if ($dbc->num_rows($vendorR) > 0){
                $v = $dbc->fetch_row($vendorR);
                $ret .= "<br /><i>This product is in the ".$v['distributor']." catalog. Values have
                    been filled in where possible</i><br />";
                $rowItem['description'] = $v['description'];
                $rowItem['manufacturer'] = $v['manufacturer'];
                $rowItem['cost'] = $v['cost'];
                $rowItem['distributor'] = $v['distributor'];
                $rowItem['normal_price'] = $v['srp'];
                $rowItem['default_vendor_id'] = $v['vendorID'];
                $rowItem['size'] = $v['size'];
                $rowItem['caseSize'] = $v['units'];
                $rowItem['sku'] = $v['sku'];

                while($v = $dbc->fetch_row($vendorR)){
                    printf('This product is also in <a href="?searchupc=%s&vid=%d">%s</a><br />',
                        $upc,$v['vendorID'],$v['distributor']);
                }
            }

            /**
              Look for items with a similar UPC to guess what
              department this item goes in. If found, use 
              department settings to fill in some defaults
            */
            $rowItem['department'] = 0;
            $search = substr($upc,0,12);
            $searchP = $dbc->prepare_statement('SELECT department FROM products WHERE upc LIKE ?');
            while(strlen($search) >= 8){
                $searchR = $dbc->exec_statement($searchP,array($search.'%'));
                if ($dbc->num_rows($searchR) > 0){
                    $searchW = $dbc->fetch_row($searchR);
                    $rowItem['department'] = $searchW['department'];
                    $settingP = $dbc->prepare_statement('SELECT dept_tax,dept_fs,dept_discount
                                FROM departments WHERE dept_no=?');
                    $settingR = $dbc->exec_statement($settingP,array($rowItem['department']));
                    if ($dbc->num_rows($settingR) > 0){
                        $d = $dbc->fetch_row($settingR);
                        $rowItem['tax'] = $d['dept_tax'];
                        $rowItem['foodstamp'] = $d['dept_fs'];
                        $rowItem['discount'] = $d['dept_discount'];
                    }
                    break;
                }
                $search = substr($search,0,strlen($search)-1);
            }
        }

        $ret .= '
            <div class="panel-heading">
                <strong>UPC</strong>
                <span class="text-danger">';
        switch ($barcode_type) {
            case 'EAN':
            case 'UPC':
                $ret .= substr($upc, 0, 3) 
                    . '<a class="text-danger iframe fancyboxLink" href="../reports/ProductLine/ProductLineReport.php?prefix='
                    . substr($upc, 3, 5) . '" title="Product Line">'
                    . '<strong>' . substr($upc, 3, 5) . '</strong>'
                    . '</a>'
                    . substr($upc, 8);
                break;
            case 'Scale':
                $ret .= substr($upc, 0, 3)
                    . '<strong>' . substr($upc, 3, 4) . '</strong>'
                    . substr($upc, 7);
                break;
            case 'PLU':
                $trimmed = ltrim($upc, '0');
                $ret .= str_repeat('0', 13-strlen($trimmed))
                    . '<strong>' . $trimmed . '</strong>';
                break;
            default:
                $ret .= $upc;
        }
        $ret .= '</span>';
        $ret .= '<input type="hidden" id="upc" name="upc" value="' . $upc . '" />';
        if ($prevUPC) {
            $ret .= ' <a class="small" href="ItemEditorPage.php?searchupc=' . $prevUPC . '">Previous</a>';
        }
        if ($nextUPC) {
            $ret .= ' <a class="small" href="ItemEditorPage.php?searchupc=' . $nextUPC . '">Next</a>';
        }
        $ret .= ' <label style="color:darkmagenta;">Modified</label>
                <span style="color:darkmagenta;">'. $rowItem['modified'] . '</span>';
        $ret .= '</div>'; // end panel-heading

        $ret .= '<div class="panel-body">';

        if ($dbc->num_rows($r) == 0) {
            // new item
            $ret .= "<div class=\"alert alert-warning\">Item not found.  You are creating a new one.</div>";
        }

        // system for store-level records not refined yet; might go here
        $ret .= '<input type="hidden" name="store_id" value="0" />';
        $ret .= '<table class="table table-bordered">';

        $limit = 35 - strlen(isset($rowItem['description'])?$rowItem['description']:'');
        $ret .= 
            '<tr>
                <th>Description</th>
                <td colspan="5">
                    <div class="input-group" style="width:100%;">
                        <input type="text" maxlength="30" class="form-control"
                            name="descript" id="descript" value="' . $rowItem['description'] . '"
                            onkeyup="$(\'#dcounter\').html(35-(this.value.length));" />
                        <span id="dcounter" class="input-group-addon">' . $limit . '</span>
                    </div>
                </td>
                <th class="text-right">Cost</th>
                <td>
                    <div class="input-group">
                        <span class="input-group-addon">$</span>
                        <input type="text" id="cost" name="cost" class="form-control price-field"
                            value="' . sprintf('%.2f', $rowItem['cost']) . '" 
                            onkeydown="if (typeof nosubmit == \'function\') nosubmit(event);"
                            onkeyup="if (typeof nosubmit == \'function\') nosubmit(event);" 
                            onchange="$(\'.default_vendor_cost\').val(this.value);"
                        />
                    </div>
                </td>
                <th class="text-right">Price</th>
                <td>
                    <div class="input-group">
                        <span class="input-group-addon">$</span>
                        <input type="text" id="price" name="price" class="form-control price-field"
                            value="' . sprintf('%.2f', $rowItem['normal_price']) . '" />
                    </div>
                </td>
            </tr>';

        // no need to display this field twice
        if (!isset($FANNIE_PRODUCT_MODULES['ProdUserModule'])) {
            $ret .= '
                <tr>
                    <th>Long Desc.</th>
                    <td colspan="3">
                    <input type="text" size="60" name="puser_description"
                        value="' . $rowItem['ldesc'] . '" class="form-control" />
                    </td>
                </tr>';
        }

        $ret .= '
            <tr>
                <th class="text-right">Brand</th>
                <td colspan="5">
                    <input type="text" name="manufacturer" class="form-control input-sm"
                        value="' . $rowItem['manufacturer'] . '" id="brand-field" />
                </td>';
        /**
          Check products.default_vendor_id to see if it is a 
          valid reference to the vendors table
        */
        $normalizedVendorID = false;
        if (isset($rowItem['default_vendor_id']) && $rowItem['default_vendor_id'] > 0) {
            $normalizedVendor = new VendorsModel($dbc);
            $normalizedVendor->vendorID($rowItem['default_vendor_id']);
            if ($normalizedVendor->load()) {
                $normalizedVendorID = $normalizedVendor->vendorID();
            }
        }
        /**
          Use a <select> box if the current vendor corresponds to a valid
          entry OR if no vendor entry exists. Only allow free text
          if it's already in place
        */
        $ret .= ' <th class="text-right">Vendor</th> ';
        if ($normalizedVendorID || empty($rowItem['distributor'])) {
            $ret .= '<td colspan="3" class="form-inline"><select name="distributor" class="chosen-select form-control"
                        id="vendor_field" onchange="vendorChanged();">';
            $ret .= '<option value="0">Select a vendor</option>';
            $vendors = new VendorsModel($dbc);
            foreach ($vendors->find('vendorName') as $v) {
                $ret .= sprintf('<option %s>%s</option>',
                            ($v->vendorID() == $normalizedVendorID ? 'selected' : ''),
                            $v->vendorName());
            }
            $ret .= '</select>';
        } else {
            $ret .= "<td colspan=\"3\"><input type=text name=distributor size=8 value=\""
                .(isset($rowItem['distributor'])?$rowItem['distributor']:"")
                ."\" id=\"vendor_field\" class=\"form-control\" />";
        }
        $ret .= ' <button type="button" id="newVendorButton"
                    class="btn btn-default"><span class="glyphicon glyphicon-plus"></span></button>';
        $ret .= '</td></tr>'; // end row

        $ret .= '<div id="newVendorDialog" title="Create new Vendor" class="collapse">';
        $ret .= '<fieldset>';
        $ret .= '<label for="newVendorName">Vendor Name</label>';
        $ret .= '<input type="text" name="newVendorName" id="newVendorName" class="form-control" />';
        $ret .= '</fieldset>';
        $ret .= '</div>';

        if (isset($rowItem['special_price']) && $rowItem['special_price'] <> 0){
            /* show sale info */
            $batchP = $dbc->prepare_statement("
                SELECT b.batchName, 
                    b.batchID 
                FROM batches AS b 
                    LEFT JOIN batchList as l on b.batchID=l.batchID 
                WHERE '" . date('Y-m-d') . "' BETWEEN b.startDate AND b.endDate 
                    AND (l.upc=? OR l.upc=?)"
            );
            $batchR = $dbc->exec_statement($batchP,array($upc,'LC'.$likeCode));
            $batch = array('batchID'=>0, 'batchName'=>"Unknown");
            if ($dbc->num_rows($batchR) > 0) {
                $batch = $dbc->fetch_row($batchR);
            }

            $ret .= '<td class="alert-success" colspan="8">';
            $ret .= sprintf("<strong>Sale Price:</strong>
                %.2f (<em>Batch: <a href=\"%sbatches/newbatch/BatchManagementTool.php?startAt=%d\">%s</a></em>)",
                $rowItem['special_price'], FannieConfig::config('URL'), $batch['batchID'], $batch['batchName']);
            list($date,$time) = explode(' ',$rowItem['end_date']);
            $ret .= "<strong>End Date:</strong>
                    $date 
                    (<a href=\"EndItemSale.php?id=$upc\">Unsale Now</a>)";
            $ret .= '</td>';
        }

        $depts = array();
        $subs = array();
        $p = $dbc->prepare_statement('
            SELECT dept_no,
                dept_name,
                subdept_no,
                subdept_name,
                s.dept_ID,
                m.superID
            FROM departments AS d
                LEFT JOIN subdepts AS s ON d.dept_no=s.dept_ID
                LEFT JOIN MasterSuperDepts AS m ON d.dept_no=m.dept_ID
            ORDER BY d.dept_no, s.subdept_name');
        $r = $dbc->exec_statement($p);
        $superID = '';
        while ($w = $dbc->fetch_row($r)) {
            if (!isset($depts[$w['dept_no']])) $depts[$w['dept_no']] = $w['dept_name'];
            if ($w['dept_no'] == $rowItem['department']) {
                $superID = $w['superID'];
            }
            if ($w['subdept_no'] == '') continue;
            if (!isset($subs[$w['dept_ID']]))
                $subs[$w['dept_ID']] = '';
            $subs[$w['dept_ID']] .= sprintf('<option %s value="%d">%d %s</option>',
                    ($w['subdept_no'] == $rowItem['subdept'] ? 'selected':''),
                    $w['subdept_no'],$w['subdept_no'],$w['subdept_name']);
        }

        $ret .= '<tr>
                <th class="text-right">Dept</th>
                <td colspan="7" class="form-inline">
                <select id="super-dept" class="form-control chosen-select" onchange="chainSuper(this.value);">';
        $names = new SuperDeptNamesModel($dbc);
        foreach ($names->find('superID') as $obj) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                    $obj->superID() == $superID ? 'selected' : '',
                    $obj->superID(), $obj->super_name());
        }
        $ret .= '</select>
                <select name="department" id="department" 
                    class="form-control chosen-select" onchange="chainSelects(this.value);">';
        foreach ($depts as $id => $name){
            $ret .= sprintf('<option %s value="%d">%d %s</option>',
                    ($id == $rowItem['department'] ? 'selected':''),
                    $id,$id,$name);
        }
        $ret .= '</select>';
        $jsVendorID = $rowItem['default_vendor_id'] > 0 ? $rowItem['default_vendor_id'] : 'no-vendor';
        $ret .= '<select name="subdept" id="subdept" class="form-control chosen-select">';
        $ret .= isset($subs[$rowItem['department']]) ? $subs[$rowItem['department']] : '<option value="0">None</option>';
        $ret .= '</select>';
        $ret .= '</td>
                <th class="small text-right">SKU</th>
                <td colspan="2">
                    <input type="text" name="vendorSKU" class="form-control input-sm"
                        value="' . $rowItem['sku'] . '" 
                        onchange="$(\'#vsku' . $jsVendorID . '\').val(this.value);" 
                        ' . ($jsVendorID == 'no-vendor' ? 'disabled' : '') . '
                        id="product-sku-field" />
                </td>
                </tr>';

        $taxQ = $dbc->prepare_statement('SELECT id,description FROM taxrates ORDER BY id');
        $taxR = $dbc->exec_statement($taxQ);
        $rates = array();
        while ($taxW = $dbc->fetch_row($taxR)) {
            array_push($rates,array($taxW[0],$taxW[1]));
        }
        array_push($rates,array("0","NoTax"));
        $ret .= '<tr>
            <th class="small text-right">Tax</th>
            <td>
            <select name="tax" id="tax" class="form-control input-sm">';
        foreach($rates as $r){
            $ret .= sprintf('<option %s value="%d">%s</option>',
                (isset($rowItem['tax'])&&$rowItem['tax']==$r[0]?'selected':''),
                $r[0],$r[1]);
        }
        $ret .= '</select></td>';

        $ret .= '<td colspan="4" class="small">
                <label>FS
                <input type="checkbox" value="1" name="FS" id="FS"
                    ' . ($rowItem['foodstamp'] == 1 ? 'checked' : '') . ' />
                </label>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <label>Scale
                <input type="checkbox" value="1" name="Scale" id="scale-checkbox"
                    ' . ($rowItem['scale'] == 1 ? 'checked' : '') . ' />
                </label>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <label>QtyFrc
                <input type="checkbox" value="1" name="QtyFrc" id="qty-checkbox"
                    ' . ($rowItem['qttyEnforced'] == 1 ? 'checked' : '') . ' />
                </label>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <label>InUse
                <input type="checkbox" value="1" name="prod-in-use" id="in-use-checkbox"
                    ' . ($rowItem['inUse'] == 1 ? 'checked' : '') . ' 
                    onchange="$(\'#extra-in-use-checkbox\').prop(\'checked\', $(this).prop(\'checked\'));" />
                </label>
                </td>
                <th class="small text-right">Discount</th>
                <td class="col-sm-1">
                <select id="discount-select" name="discount" class="form-control input-sm">';
        $disc_opts = array(
            0 => 'No',
            1 => 'Yes',
            2 => 'Trans Only',
            3 => 'Line Only',
        );
        if ($rowItem['discount'] == 1 && $rowItem['line_item_discountable'] == 1) {
            $rowItem['discount'] = 1;
        } elseif ($rowItem['discount'] == 1 && $rowItem['line_item_discountable'] == 0) {
            $rowItem['discount'] = 2;
        } elseif ($rowItem['discount'] == 0 && $rowItem['line_item_discountable'] == 1) {
            $rowItem['discount'] = 3;
        } 
        foreach ($disc_opts as $id => $val) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                        ($id == $rowItem['discount'] ? 'selected' : ''),
                        $id, $val);
        }
        $ret .= '</select></td>
                <th class="small text-right">Deposit</th>
                <td colspan="2">
                    <input type="text" name="deposit-upc" class="form-control input-sm"
                        value="' . ($rowItem['deposit'] != 0 ? $rowItem['deposit'] : '') . '" 
                        placeholder="Deposit Item PLU/UPC"
                        onchange="$(\'#deposit\').val(this.value);"
                        id="deposit-upc" />
                </td>
                </tr>';

        $ret .= '
            <tr>
                <th class="small text-right">Pack Size</th>
                <td class="col-sm-1">
                    <input type="text" name="size" class="form-control input-sm"
                        value="' . $rowItem['size'] . '" 
                        onchange="$(\'#vsize' . $jsVendorID . '\').val(this.value);" 
                        id="product-pack-size" />
                </td>
                <th class="small">Case Size</th>
                <td class="col-sm-1">
                    <input type="text" name="caseSize" class="form-control input-sm"
                        value="' . $rowItem['caseSize'] . '" 
                        onchange="$(\'#vunits' . $jsVendorID . '\').val(this.value);" 
                        ' . ($jsVendorID == 'no-vendor' ? 'disabled' : '') . '
                        id="product-case-size" />
                </td>
                <th class="small">Unit of measure</th>
                <td class="col-sm-1">
                    <input type="text" name="unitm" class="form-control input-sm"
                        value="' . $rowItem['unitofmeasure'] . '" />
                </td>
                <th class="small">Age Req</th>
                <td class="col-sm-1">
                    <select name="id-enforced" id="id-enforced" class="form-control input-sm"
                        onchange="$(\'#idReq\').val(this.value);">';
        $ages = array('n/a'=>0, 18=>18, 21=>21);
        foreach($ages as $label => $age) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                            ($age == $rowItem['idEnforced'] ? 'selected' : ''),
                            $age, $label);
        }
        $ret .= '</select>
                </td>
                <th class="small text-right">Local</th>
                <td>
                    <select name="prod-local" id="prod-local" class="form-control input-sm"
                        onchange="$(\'#local-origin-id\').val(this.value);">';
        $local_opts = array(0=>'No');
        $p = $dbc->prepare_statement('SELECT originID,shortName FROM originName WHERE local=1 ORDER BY originID');
        $r = $dbc->exec_statement($p);
        while ($w = $dbc->fetch_row($r)) {
            $local_opts[$w['originID']] = $w['shortName'];  
        }
        if (count($local_opts) == 1) {
            $local_opts[1] = 'Yes'; // generic local if no origins defined
        }
        foreach($local_opts as $id => $val) {
            $ret .= sprintf('<option value="%d" %s>%s</option>',
                $id, ($id == $rowItem['local']?'selected':''), $val);
        }
        $ret .= '</select>
                </td>
                </tr>
            </div>';
        $ret .= '</table>';

        $ret .= '</div>'; // end panel-body
        $ret .= '</div>'; // end panel

        return $ret;
    }

    public function getFormJavascript($upc)
    {
        $FANNIE_URL = FannieConfig::config('URL');
        $dbc = $this->db();
        $prod = new ProductsModel($dbc);
        $prod->upc($upc);
        $prod->load();

        $p = $dbc->prepare_statement('SELECT dept_no,dept_name,subdept_no,subdept_name,dept_ID 
                FROM departments AS d
                LEFT JOIN subdepts AS s ON d.dept_no=s.dept_ID
                ORDER BY d.dept_no, s.subdept_name');
        $r = $dbc->exec_statement($p);
        $subs = array();
        while($w = $dbc->fetch_row($r)){
            if ($w['subdept_no'] == '') continue;
            if (!isset($subs[$w['dept_ID']]))
                $subs[$w['dept_ID']] = '';
            $subs[$w['dept_ID']] .= sprintf('<option %s value="%d">%d %s</option>',
                    ($w['subdept_no'] == $prod->subdept() ? 'selected':''),
                    $w['subdept_no'],$w['subdept_no'],$w['subdept_name']);
        }

        $json = count($subs) == 0 ? '{}' : json_encode($subs);
        ob_start();
        ?>
        function chainSuper(val) {
            var req = {
                jsonrpc: '2.0',
                method: '\\COREPOS\\Fannie\\API\\webservices\\FannieDeptLookup',
                id: new Date().getTime(),
                params: {
                    'type' : 'children',
                    'superID' : val
                }
            };
            $.ajax({
                url: '<?php echo $FANNIE_URL; ?>ws/',
                type: 'post',
                data: JSON.stringify(req),
                dataType: 'json',
                contentType: 'application/json',
                success: function(resp) {
                    if (resp.result) {
                        $('#department').empty();
                        for (var i=0; i<resp.result.length; i++) {
                            var opt = $('<option>').val(resp.result[i]['id'])
                                .html(resp.result[i]['id'] + ' ' + resp.result[i]['name']);
                            $('#department').append(opt);
                        }
                        $('#department').trigger('chosen:updated');
                        chainSelects($('#department').val());
                    }
                }
            });
        }
        function chainSelects(val){
            var lookupTable = <?php echo $json; ?>;
            if (val in lookupTable) {
                $('#subdept').html(lookupTable[val]);
                $('#subdept').trigger('chosen:updated');
            } else {
                $('#subdept').html('<option value=0>None</option>');
            }
            $.ajax({
                url: '<?php echo $FANNIE_URL; ?>item/modules/BaseItemModule.php',
                data: 'dept_defaults='+val,
                dataType: 'json',
                cache: false,
                success: function(data){
                    if (data.tax)
                        $('#tax').val(data.tax);
                    if (data.fs)
                        $('#FS').prop('checked',true);
                    else{
                        $('#FS').prop('checked', false);
                    }
                    if (data.nodisc) {
                        $('#discount-select').val(0);
                    } else {
                        $('#discount-select').val(1);
                    }
                }

            });
        }
        function vendorChanged()
        {
            var newVal = $('#vendor_field').val();
            $.ajax({
                url: '<?php echo $FANNIE_URL; ?>item/modules/BaseItemModule.php',
                data: 'vendorChanged='+newVal,
                dataType: 'json',
                cache: false,
                success: function(resp) {
                    if (!resp.error) {
                        $('#local-origin-id').val(resp.localID);
                        $('#product-case-size').prop('disabled', false);
                        $('#product-sku-field').prop('disabled', false);
                    } else {
                        $('#product-case-size').prop('disabled', true);
                        $('#product-sku-field').prop('disabled', true);
                    }
                }
            });
        }
        function addVendorDialog()
        {
            var v_dialog = $('#newVendorDialog').dialog({
                autoOpen: false,
                height: 300,
                width: 300,
                modal: true,
                buttons: {
                    "Create Vendor" : addVendorCallback,
                    "Cancel" : function() {
                        v_dialog.dialog("close");
                    }
                },
                close: function() {
                    $('#newVendorDialog :input').each(function(){
                        $(this).val('');
                    });
                    $('#newVendorAlert').html('');
                }
            });

            $('#newVendorDialog :input').keyup(function(e) {
                if (e.which == 13) {
                    addVendorCallback();
                }
            });

            $('#newVendorButton').click(function(e){
                e.preventDefault();
                v_dialog.dialog("open"); 
            });

            function addVendorCallback()
            {
                var data = 'action=addVendor';
                data += '&' + $('#newVendorDialog :input').serialize();
                $.ajax({
                    url: '<?php echo $FANNIE_URL; ?>item/modules/BaseItemModule.php',
                    data: data,
                    dataType: 'json',
                    error: function() {
                        $('#newVendorAlert').html('Communication error');
                    },
                    success: function(resp){
                        if (resp.vendorID) {
                            v_dialog.dialog("close");
                            var v_field = $('#vendor_field');
                            if (v_field.hasClass('chosen-select')) {
                                var newopt = $('<option/>').attr('id', resp.vendorID).html(resp.vendorName);
                                v_field.append(newopt);
                            }
                            $('#vendor_field').val(resp.vendorName);
                            if (v_field.hasClass('chosen-select')) {
                                v_field.trigger('chosen:updated');
                            }
                        } else if (resp.error) {
                            $('#newVendorAlert').html(resp.error);
                        } else {
                            $('#newVendorAlert').html('Invalid response');
                        }
                    }
                });
            }

        }
        <?php

        return ob_get_clean();
    }

    function SaveFormData($upc)
    {
        $FANNIE_PRODUCT_MODULES = FannieConfig::config('PRODUCT_MODULES', array());
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        if (!$model->load()) {
            // fully init new record
            $model->special_price(0);
            $model->specialpricemethod(0);
            $model->specialquantity(0);
            $model->specialgroupprice(0);
            $model->advertised(0);
            $model->tareweight(0);
            $model->start_date('');
            $model->end_date('');
            $model->discounttype(0);
            $model->wicable(0);
            $model->scaleprice(0);
            $model->inUse(1);
        }
        $model->tax(FormLib::get_form_value('tax',0));
        $model->foodstamp(FormLib::get_form_value('FS',0));
        $model->scale(FormLib::get_form_value('Scale',0));
        $model->qttyEnforced(FormLib::get_form_value('QtyFrc',0));
        $discount_setting = FormLib::get('discount', 1);
        switch ($discount_setting) {
            case 0:
                $model->discount(0);
                $model->line_item_discountable(0);
                break;
            case 1:
                $model->discount(1);
                $model->line_item_discountable(1);
                break;
            case 2:
                $model->discount(1);
                $model->line_item_discountable(0);
                break;
            case 3:
                $model->discount(0);
                $model->line_item_discountable(1);
                break;
        }
        $model->normal_price(FormLib::get_form_value('price',0.00));
        $model->cost(FormLib::get('cost', 0.00));
        $model->description(str_replace("'", '', FormLib::get_form_value('descript','')));
        $model->brand(str_replace("'", '', FormLib::get('manufacturer', '')));
        $model->pricemethod(0);
        $model->groupprice(0.00);
        $model->quantity(0);
        $model->department(FormLib::get_form_value('department',0));
        $model->size(FormLib::get_form_value('size',''));
        $model->modified(date('Y-m-d H:i:s'));
        $model->unitofmeasure(FormLib::get_form_value('unitm',''));
        $model->subdept(FormLib::get_form_value('subdept',0));

        /* turn on volume pricing if specified, but don't
           alter pricemethod if it's already non-zero */
        $doVol = FormLib::get_form_value('doVolume',False);
        $vprice = FormLib::get_form_value('vol_price','');
        $vqty = FormLib::get_form_value('vol_qtty','');
        if ($doVol !== false && is_numeric($vprice) && is_numeric($vqty)) {
            $model->pricemethod(FormLib::get_form_value('pricemethod',0));
            if ($model->pricemethod() == 0) {
                $model->pricemethod(2);
            }
            $model->groupprice($vprice);
            $model->quantity($vqty);
        }

        // lookup vendorID by name
        $vendorID = 0;
        $vendor = new VendorsModel($dbc);
        $vendor->vendorName(FormLib::get('distributor'));
        foreach($vendor->find('vendorID') as $obj) {
            $vendorID = $obj->vendorID();
            break;
        }
        $model->default_vendor_id($vendorID);
        $model->inUse(FormLib::get('prod-in-use',0));
        $model->idEnforced(FormLib::get('id-enforced', 0));
        $model->local(FormLib::get('prod-local', 0));
        $deposit = FormLib::get('deposit-upc', 0);
        if ($deposit == '') {
            $deposit = 0;
        }
        $model->deposit($deposit);

        $model->save();

        /**
          If a vendor is selected, intialize
          a vendorItems record
        */
        if ($vendorID != 0) {
            $vitem = new VendorItemsModel($dbc);
            $vitem->vendorID($vendorID);
            $vitem->upc($upc);
            $sku = FormLib::get('vendorSKU');
            if (empty($sku)) {
                $sku = $upc;
            }
            $vitem->sku($sku);
            $vitem->size($model->size());
            $vitem->description($model->description());
            $vitem->brand($model->brand());
            $vitem->units(FormLib::get('caseSize', 1));
            $vitem->cost($model->cost());
            $vitem->save();
        }


        if ($dbc->table_exists('prodExtra')){
            $arr = array();
            $arr['manufacturer'] = $dbc->escape(str_replace("'",'',FormLib::get_form_value('manufacturer')));
            $arr['distributor'] = $dbc->escape(str_replace("'",'',FormLib::get_form_value('distributor')));
            $arr['location'] = 0;
            $extra = new ProdExtraModel($dbc);
            $extra->upc($upc);
            if (!$extra->load()) {
                $extra->variable_pricing(0);
                $extra->margin(0);
                $extra->case_quantity('');
                $extra->case_cost(0.00);
                $extra->case_info('');
            }
            $extra->manufacturer(str_replace("'",'',FormLib::get('manufacturer')));
            $extra->distributor(str_replace("'",'',FormLib::get('distributor')));
            $extra->save();
        }

        if (!isset($FANNIE_PRODUCT_MODULES['ProdUserModule'])) {
            if ($dbc->table_exists('productUser')){
                $ldesc = FormLib::get_form_value('puser_description');
                $model = new ProductUserModel($dbc);
                $model->upc($upc);
                $model->description($ldesc);
                $model->save();
            }
        }
    }

    function AjaxCallback()
    {
        $db = $this->db();
        $json = array();
        if (FormLib::get('action') == 'addVendor') {
            $name = FormLib::get('newVendorName');
            if (empty($name)) {
                $json['error'] = 'Name is required';
            } else {
                $vendor = new VendorsModel($db);
                $vendor->vendorName($name);
                if (count($vendor->find()) > 0) {
                    $json['error'] = 'Vendor "' . $name . '" already exists';
                } else {
                    $max = $db->query('SELECT MAX(vendorID) AS max
                                       FROM vendors');
                    $newID = 1;
                    if ($max && $maxW = $db->fetch_row($max)) {
                        $newID = ((int)$maxW['max']) + 1;
                    }
                    $vendor->vendorID($newID);
                    $vendor->save();
                    $json['vendorID'] = $newID;
                    $json['vendorName'] = $name;
                }
            }
        } elseif (FormLib::get('dept_defaults') !== '') {
            $json = array('tax'=>0,'fs'=>False,'nodisc'=>False);
            $dept = FormLib::get_form_value('dept_defaults','');
            $p = $db->prepare_statement('SELECT dept_tax,dept_fs,dept_discount
                    FROM departments WHERE dept_no=?');
            $r = $db->exec_statement($p,array($dept));
            if ($db->num_rows($r)) {
                $w = $db->fetch_row($r);
                $json['tax'] = $w['dept_tax'];
                if ($w['dept_fs'] == 1) $json['fs'] = True;
                if ($w['dept_discount'] == 0) $json['nodisc'] = True;
            }
        } elseif (FormLib::get('vendorChanged') !== '') {
            $v = new VendorsModel($db);
            $v->vendorName(FormLib::get('vendorChanged'));
            $matches = $v->find();
            $json = array('error'=>false);
            if (count($matches) == 1) {
                $json['localID'] = $matches[0]->localOriginID();
                $json['vendorID'] = $matches[0]->vendorID();
            } else {
                $json['error'] = true;
            }
        }

        echo json_encode($json);
    }

    function summaryRows($upc)
    {
        $dbc = $this->db();

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        if ($model->load()) {
            $row1 = '<th>UPC</th>
                <td><a href="ItemEditorPage.php?searchupc=' . $upc . '">' . $upc . '</td>
                <td>
                    <a class="iframe fancyboxLink" href="addShelfTag.php?upc='.$upc.'" title="Create Shelf Tag">Shelf Tag</a>
                </td>';
            $row2 = '<th>Description</th><td>' . $model->description() . '</td>
                     <th>Price</th><td>$' . $model->normal_price() . '</td>';

            return array($row1, $row2);
        } else {
            return array('<td colspan="4">Error saving. <a href="ItemEditorPage.php?searchupc=' . $upc . '">Try Again</a>?</td>');
        }
    }
}

/**
  This form does some fancy tricks via AJAX calls. This block
  ensures the AJAX functionality only runs when the script
  is accessed via the browser and not when it's included in
  another PHP script.
*/
if (basename($_SERVER['SCRIPT_NAME']) == basename(__FILE__)){
    $obj = new BaseItemModule();
    $obj->AjaxCallback();   
}

?>
