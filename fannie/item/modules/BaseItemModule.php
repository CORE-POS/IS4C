<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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

class BaseItemModule extends \COREPOS\Fannie\API\item\ItemModule 
{
    private function getBarcodeType($upc)
    {
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

        return $barcode_type;
    }

    private function getStores()
    {
        $store_model = new StoresModel($this->db());
        $store_model->hasOwnItems(1);
        $stores = array();
        foreach ($store_model->find('storeID') as $obj) {
            $stores[$obj->storeID()] = $obj;
        }
        if (count($stores) == 0) {
            $store_model->storeID(1);
            $store_model->description('DEFAULT STORE');
            $stores[1] = $store_model;
        }

        return $stores;
    }

    private function prevNextItem($upc, $department)
    {
        /* find previous and next items in department */
        $dbc = $this->db();
        $prevP = $dbc->prepare('SELECT upc FROM products WHERE department=? AND upc < ? ORDER BY upc DESC');
        $nextP = $dbc->prepare('SELECT upc FROM products WHERE department=? AND upc > ? ORDER BY upc');
        $prevUPC = $dbc->getValue($prevP, array($department, $upc));
        $nextUPC = $dbc->getValue($nextP, array($department, $upc));

        return array($prevUPC, $nextUPC);
    }

    private function getVendorName($vendorID)
    {
        $dbc = $this->db();
        $prep = $dbc->prepare('SELECT vendorName FROM vendors WHERE vendorID=?');
        $name = $dbc->getValue($prep, array($vendorID));

        return ($name === false) ? '' : $name;
    }

    /**
      Look for items with a similar UPC to guess what
      department this item goes in. If found, use 
      department settings to fill in some defaults
    */
    private function guessDepartment($upc)
    {
        $dbc = $this->db();
        $search = substr($upc,0,12);
        $searchP = $dbc->prepare('SELECT department FROM products WHERE upc LIKE ?');
        $department = 0;
        while (strlen($search) >= 8) {
            $searchR = $dbc->execute($searchP,array($search.'%'));
            if ($dbc->numRows($searchR) > 0) {
                $searchW = $dbc->fetchRow($searchR);
                $department = $searchW['department'];
                break;
            }
            $search = substr($search,0,strlen($search)-1);
        }

        return $department;
    }

    private function confidentDepartment($upc)
    {
        $brand_id = substr($upc, 0, 8);
        if ($brand_id === '00000000') {
            return true;
        }
        $dbc = $this->db();
        $chkP = $dbc->prepare('
            SELECT department 
            FROM products
            WHERE upc LIKE ?
                AND upc not like \'002%\'
            GROUP BY department
        ');
        $chkR = $dbc->execute($chkP, array($brand_id . '%'));

        return $dbc->numRows($chkR) === 1 ? true : false;
    }

    private function getExistingItem($upc)
    {
        $dbc = $this->db();
        $itemQ = '
            SELECT
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
                p.last_sold,
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
                n.vendorName AS distributor,
                u.description as ldesc,
                p.default_vendor_id,
                v.units AS caseSize,
                v.sku,
                p.inUse,
                p.idEnforced,
                p.local,
                p.deposit,
                p.discounttype,
                p.wicable,
                p.store_id,
                CASE WHEN c.upc IS NOT NULL THEN 1 ELSE 0 END AS inventoried,
                c.count AS lastCount,
                c.countDate,
                c.par AS invPar,
                i.onHand,
                0 AS isAlias
            FROM products AS p 
                LEFT JOIN productUser AS u ON p.upc=u.upc 
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id = v.vendorID
                LEFT JOIN vendors AS n ON p.default_vendor_id=n.vendorID
                LEFT JOIN InventoryCache AS i ON p.upc=i.upc AND p.store_id=i.storeID
                LEFT JOIN InventoryCounts AS c ON p.upc=c.upc AND p.store_id=c.storeID
            WHERE p.upc=?';
        $p_def = $dbc->tableDefinition('products');
        if (!isset($p_def['last_sold'])) {
            $itemQ = str_replace('p.last_sold', 'NULL as last_sold', $itemQ);
        }
        $itemP = $dbc->prepare($itemQ);
        $res = $dbc->execute($itemP,array($upc));
        if ($dbc->numRows($res) > 0) {
            $items = array();
            while ($row = $dbc->fetchRow($res)) {
                $items[$row['store_id']] = $row;
            }
            return $items;
        }

        return false;
    }

    private function getNewItem($upc)
    {
        $dbc = $this->db();
        // default values for form fields
        $rowItem = array(
            'description' => '',
            'normal_price' => 0,
            'pricemethod' => 0,
            'size' => '',
            'unitofmeasure' => '',
            'modified' => '',
            'ldesc' => '',
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
            'cost' => 0,
            'discounttype' => 0,
            'wicable' => 0,
            'inventoried' => 0,
            'isAlias' => 0,
        );
        $ret = '';

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
                i.srp,
                i.size,
                i.units,
                i.sku,
                i.vendorID as default_vendor_id
            FROM vendorItems AS i 
                LEFT JOIN vendors AS v ON i.vendorID=v.vendorID
                LEFT JOIN vendorDepartments AS d ON i.vendorDept=d.deptID AND d.vendorID=i.vendorID
            WHERE i.upc=?";
        $args = array($upc);
        $vID = FormLib::get('vid','');
        if ($vID !== ''){
            $vendorP .= ' AND i.vendorID=?';
            $args[] = $vID;
        }
        $vendorP .= ' ORDER BY i.vendorID';
        $vendorP = $dbc->prepare($vendorP);
        $vendorR = $dbc->execute($vendorP,$args);

        if ($dbc->numRows($vendorR) > 0){
            $vrow = $dbc->fetchRow($vendorR);
            $ret .= "<div><i>This product is in the ".$vrow['distributor']." catalog. Values have
                been filled in where possible</i></div>";
            $rowItem['description'] = $vrow['description'];
            $rowItem['manufacturer'] = $vrow['manufacturer'];
            $rowItem['cost'] = $vrow['cost'];
            $rowItem['distributor'] = $vrow['distributor'];
            $rowItem['normal_price'] = $vrow['srp'];
            $rowItem['default_vendor_id'] = $vrow['vendorID'];
            $rowItem['size'] = $vrow['size'];
            $rowItem['caseSize'] = $vrow['units'];
            $rowItem['sku'] = $vrow['sku'];

            while ($vrow = $dbc->fetchRow($vendorR)) {
                $ret .= sprintf('This product is also in <a href="?searchupc=%s&vid=%d">%s</a><br />',
                    $upc,$vrow['vendorID'],$vrow['distributor']);
            }
        }

        $rowItem['department'] = $this->guessDepartment($upc);
        /**
          If no match is found, pick the most
          commonly used department
        */
        if ($rowItem['department'] == 0) {
            $commonP = $dbc->prepare('
                SELECT department,
                    COUNT(*)
                FROM products
                GROUP BY department
                ORDER BY COUNT(*) DESC');
            $rowItem['department'] = $dbc->getValue($commonP);
        }
        /**
          Get defaults for chosen department
        */
        $dmodel = new DepartmentsModel($dbc);
        $dmodel->dept_no($rowItem['department']);
        if ($dmodel->load()) {
            $rowItem['tax'] = $dmodel->dept_tax();
            $rowItem['foodstamp'] = $dmodel->dept_fs();
            $rowItem['discount'] = $dmodel->dept_discount();
            $rowItem['line_item_discountable'] = $dmodel->line_item_discount();
            $rowItem['wicable'] = $dmodel->dept_wicable();
        }

        return array($rowItem, $ret);
    }

    private function highlightUPC($upc)
    {
        switch ($this->getBarcodeType($upc)) {
            case 'EAN':
            case 'UPC':
                 return substr($upc, 0, 3) 
                    . '<a class="text-danger iframe fancyboxLink" href="../reports/ProductLine/ProductLineReport.php?prefix='
                    . substr($upc, 3, 5) . '" title="Product Line">'
                    . '<strong>' . substr($upc, 3, 5) . '</strong>'
                    . '</a>'
                    . substr($upc, 8);
            case 'Scale':
                return substr($upc, 0, 3)
                    . '<strong>' . substr($upc, 3, 4) . '</strong>'
                    . substr($upc, 7);
            case 'PLU':
                $trimmed = ltrim($upc, '0');
                if (strlen($trimmed) < 13) {
                    return str_repeat('0', 13-strlen($trimmed))
                        . '<strong>' . $trimmed . '</strong>';
                }
        }
        
        return $upc; 
    }

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $FANNIE_PRODUCT_MODULES = FannieConfig::config('PRODUCT_MODULES', array());
        $upc = BarcodeLib::padUPC($upc);

        $ret = '<div id="BaseItemFieldset" class="panel panel-default">';

        $dbc = $this->db();

        $stores = $this->getStores();
        $items = array();
        $rowItem = array();
        $prevUPC = false;
        $nextUPC = false;
        $likeCode = false;
        $exists = $this->getExistingItem($upc);
        if ($exists) {
            $items = $exists;
            $rowItem = current($items);
            $new_item = false;

            $rowItem['distributor'] = $this->getVendorName($rowItem['default_vendor_id']);

            /* find previous and next items in department */
            list($prevUPC, $nextUPC) = $this->prevNextItem($upc, $rowItem['department']);

            $lcP = $dbc->prepare('SELECT likeCode FROM upcLike WHERE upc=?');
            $likeCode = $dbc->getValue($lcP,array($upc));

            if (FannieConfig::config('STORE_MODE') == 'HQ') {
                $default_id = array_keys($items);
                $default_id = $default_id[0];
                $default_item = $items[$default_id];
                foreach ($stores as $id => $info) {
                    if (!isset($items[$id])) {
                        $items[$id] = $default_item;
                    }
                }
            }
        } else {
            list($rowItem, $msg) = $this->getNewItem($upc);
            $ret .= $msg;
            $new_item = true;

            foreach ($stores as $id => $obj) {
                $items[$id] = $rowItem;
            }
        }

        $ret .= '<div class="panel-heading">';
        if ($prevUPC) {
            $ret .= ' <a class="btn btn-default btn-xs small" href="ItemEditorPage.php?searchupc=' . $prevUPC . '"
                title="Previous item in this department">
                <span class="glyphicon glyphicon-chevron-left"></span></a> ';
        }
        $ret .= '<strong>UPC</strong>
                <span class="text-danger">';
        $ret .= $this->highlightUPC($upc);
        $ret .= '</span>';
        $ret .= '<input type="hidden" id="upc" name="upc" value="' . $upc . '" />';
        if ($nextUPC) {
            $ret .= ' <a class="btn btn-default btn-xs small" href="ItemEditorPage.php?searchupc=' . $nextUPC . '"
                title="Next item in this department">
                <span class="glyphicon glyphicon-chevron-right"></span></a>';
        }
        $ret .= ' <label style="color:darkmagenta;">Modified</label>
                <span style="color:darkmagenta;">'. $rowItem['modified'] . '</span>';
        $ret .= ' | <label style="color:darkmagenta;">Last Sold</label>
                <span style="color:darkmagenta;">'. (empty($rowItem['last_sold']) ? 'n/a' : $rowItem['last_sold']) . '</span>';
        $ret .= '</div>'; // end panel-heading

        $ret .= '<div class="panel-body">';

        if ($new_item) {
            // new item
            $ret .= "<div class=\"alert alert-warning\">Item not found.  You are creating a new one.</div>";
            if (!$this->confidentDepartment($upc)) {
                $ret .= '<div class="alert alert-danger">Please double-check POS department auto selection</div>';
            }
        }

        $nav_tabs = '<ul id="store-tabs" class="nav nav-tabs small" role="tablist">';
        $ret .= '{{nav_tabs}}<div class="tab-content">';
        $netStore = COREPOS\Fannie\API\lib\Store::getIdByIp();
        foreach ($items as $store_id => $rowItem) {
            $active_tab = false;
            if (FannieConfig::config('STORE_MODE') !== 'HQ' || $netStore == $store_id || ($netStore == false && $store_id == FannieConfig::config('STORE_ID'))) {
                $active_tab = true;
            }
            $tabID = 'store-tab-' . $store_id;
            $store_description = 'n/a';
            if (isset($stores[$store_id])) {
                $store_description = $stores[$store_id]->description();
            }
            $nav_tabs .= '<li role="presentation" ' . ($active_tab ? 'class="active"' : '') . '>'
                . '<a href="#' . $tabID . '" aria-controls="' . $tabID . '" '
                . 'onclick="$(\'.tab-content .chosen-select:visible\').chosen();"'
                . 'role="tab" data-toggle="tab">'
                . $store_description . '</a></li>';
            $ret .= '<div role="tabpanel" class="tab-pane' . ($active_tab ? ' active' : '') . '"
                id="' . $tabID . '">';

            $ret .= '<input type="hidden" class="store-id" name="store_id[]" value="' . $store_id . '" />';
            $ret .= '<table class="table table-bordered">';

            $jsVendorID = $rowItem['default_vendor_id'] != 0 ? $rowItem['default_vendor_id'] : 'no-vendor';
            $vFieldsDisabled = $jsVendorID == 'no-vendor' || !$active_tab ? 'disabled' : '';
            $aliasDisabled = $rowItem['isAlias'] ? 'disabled' : '';
            $limit = 30 - strlen(isset($rowItem['description'])?$rowItem['description']:'');
            $cost = sprintf('%.3f', $rowItem['cost']);
            $price = sprintf('%.2f', $rowItem['normal_price']);
            $ret .= <<<HTML
<tr>
    <th class="text-right">Description</th>
    <td colspan="5">
        <div class="input-group" style="width:100%;">
            <input type="text" maxlength="30" class="form-control syncable-input" required
                name="descript[]" id="descript" value="{$rowItem['description']}"
                onkeyup="$(this).next().html(30-(this.value.length));" />
            <span class="input-group-addon">{$limit}</span>
        </div>
    </td>
    <th class="text-right">Cost</th>
    <td>
        <div class="input-group">
            <span class="input-group-addon">$</span>
            <input type="text" id="cost{$store_id}" name="cost[]" 
                class="form-control price-field cost-input syncable-input"
                value="{$cost}" data-store-id="{$store_id}" maxlength="6"
                onkeydown="if (typeof nosubmit == 'function') nosubmit(event);"
                onkeyup="if (typeof nosubmit == 'function') nosubmit(event);" 
                onchange="$('.default_vendor_cost').val(this.value);"
            />
        </div>
    </td>
    <th class="text-right">Price</th>
    <td>
        <div class="input-group">
            <span class="input-group-addon">$</span>
            <input type="text" id="price{$store_id}" name="price[]" 
                class="form-control price-field price-input syncable-input"
                data-store-id="{$store_id}" maxlength="6"
                required value="{$price}" />
        </div>
    </td>
</tr>
HTML;

            // no need to display this field twice
            if (!isset($FANNIE_PRODUCT_MODULES['ProdUserModule'])) {
                $ret .= '
                    <tr>
                        <th>Long Desc.</th>
                        <td colspan="5">
                        <input type="text" size="60" name="puser_description" maxlength="255"
                            ' . (!$active_tab ? ' disabled ' : '') . '
                            value="' . $rowItem['ldesc'] . '" class="form-control" />
                        </td>
                    </tr>';
            }

            $ret .= '
                <tr>
                    <th class="text-right">Brand</th>
                    <td colspan="5">
                        <input type="text" name="manufacturer[]" 
                            class="form-control input-sm brand-field syncable-input"
                            value="' . $rowItem['manufacturer'] . '" />
                    </td>';
            /**
              Check products.default_vendor_id to see if it is a 
              valid reference to the vendors table
            */
            $normalizedVendorID = false;
            if (isset($rowItem['default_vendor_id']) && $rowItem['default_vendor_id'] <> 0) {
                $normalizedVendorID = $rowItem['default_vendor_id'];
            }
            /**
              Use a <select> box if the current vendor corresponds to a valid
              entry OR if no vendor entry exists. Only allow free text
              if it's already in place
            */
            $ret .= ' <th class="text-right">Vendor</th> ';
            if ($normalizedVendorID || empty($rowItem['distributor'])) {
                $ret .= '<td colspan="3" class="form-inline"><select name="distributor[]" 
                            class="chosen-select form-control vendor_field syncable-input"
                            onchange="baseItem.vendorChanged(this.value);">';
                $ret .= '<option value="0">Select a vendor</option>';
                $vendR = $dbc->query('SELECT vendorID, vendorName FROM vendors WHERE inactive=0 ORDER BY vendorName');
                while ($vendW = $dbc->fetchRow($vendR)) {
                    $ret .= sprintf('<option %s>%s</option>',
                                ($vendW['vendorID'] == $normalizedVendorID ? 'selected' : ''),
                                $vendW['vendorName']);
                }
                $ret .= '</select>';
            } else {
                $ret .= "<td colspan=\"3\"><input type=text name=distributor[] size=8 value=\""
                    .(isset($rowItem['distributor'])?$rowItem['distributor']:"")
                    ."\" class=\"form-control vendor-field syncable-input\" />";
            }
            $ret .= ' <button type="button" 
                        title="Create new vendor"
                        class="btn btn-default btn-sm newVendorButton">
                        <span class="glyphicon glyphicon-plus"></span></button>';
            $ret .= '</td></tr>'; // end row

            if (isset($rowItem['discounttype']) && $rowItem['discounttype'] <> 0) {
                /* show sale info */
                if (FannieConfig::config('STORE_MODE') == 'HQ') {
                    $batchP = $dbc->prepare("
                        SELECT b.batchName, 
                            b.batchID 
                        FROM batches AS b 
                            LEFT JOIN batchList as l on b.batchID=l.batchID 
                            LEFT JOIN StoreBatchMap AS m ON b.batchID=m.batchID
                        WHERE '" . date('Y-m-d') . "' BETWEEN b.startDate AND b.endDate 
                            AND (l.upc=? OR l.upc=?)
                            AND m.storeID=?"
                    );
                    $batchR = $dbc->execute($batchP,array($upc,'LC'.$likeCode,$store_id));
                } else {
                    $batchP = $dbc->prepare("
                        SELECT b.batchName, 
                            b.batchID 
                        FROM batches AS b 
                            LEFT JOIN batchList as l on b.batchID=l.batchID 
                        WHERE '" . date('Y-m-d') . "' BETWEEN b.startDate AND b.endDate 
                            AND (l.upc=? OR l.upc=?)
                    ");
                    $batchR = $dbc->execute($batchP,array($upc,'LC'.$likeCode));
                }
                $batch = array('batchID'=>0, 'batchName'=>"Unknown");
                if ($dbc->num_rows($batchR) > 0) {
                    $batch = $dbc->fetch_row($batchR);
                }

                $ret .= '<td class="alert-success" colspan="8">';
                $ret .= sprintf("<strong>Sale Price:</strong>
                    %.2f (<em>Batch: <a href=\"%sbatches/newbatch/EditBatchPage.php?id=%d\">%s</a></em>)",
                    $rowItem['special_price'], FannieConfig::config('URL'), $batch['batchID'], $batch['batchName']);
                list($date,$time) = explode(' ',$rowItem['end_date']);
                $ret .= "<strong>End Date:</strong>
                        $date 
                        (<a href=\"EndItemSale.php?id=$upc\">Unsale Now</a>)";
                $ret .= '</td>';
            }

            $supers = array();
            $depts = array();
            $subs = array();
            $superID = '';
            $range_limit = FannieAuth::validateUserLimited('pricechange');
            list($superID, $supers, $depts, $subs) = $this->deptMaps($rowItem, $range_limit);

            $names = new SuperDeptNamesModel($dbc);
            $superQ = 'SELECT superID, super_name FROM superDeptNames';
            $superArgs = array();
            if (is_array($range_limit) && count($range_limit) == 2) {
                $superArgs = $range_limit;
                $superQ .= ' WHERE superID BETWEEN ? AND ? ';
            }
            $superQ .= ' ORDER BY superID';
            $superP = $dbc->prepare($superQ);
            $superR = $dbc->execute($superP, $superArgs);
            $superOpts = '';
            while ($superW = $dbc->fetchRow($superR)) {
                $superOpts .= sprintf('<option %s value="%d">%s</option>',
                        $superW['superID'] == $superID ? 'selected' : '',
                        $superW['superID'], $superW['super_name']);
            }

            $deptOpts = '';
            foreach ($depts as $id => $name){
                if (is_numeric($superID) && is_array($supers[$superID])) {
                    if (!in_array($id, $supers[$superID]) && $id != $rowItem['department']) {
                        continue;
                    }
                }
                $deptOpts .= sprintf('<option %s value="%d">%d %s</option>',
                        ($id == $rowItem['department'] ? 'selected':''),
                        $id,$id,$name);
            }

            $subOpts = isset($subs[$rowItem['department']]) ? $subs[$rowItem['department']] : '';
            // subdept zero is selected
            $subZero = $rowItem['subdept'] == 0 ? 'selected' : '';

            $taxQ = $dbc->prepare('SELECT id,description FROM taxrates ORDER BY id');
            $taxR = $dbc->execute($taxQ);
            $rates = array();
            while ($taxW = $dbc->fetch_row($taxR)) {
                array_push($rates,array($taxW[0],$taxW[1]));
            }
            array_push($rates,array("0","NoTax"));
            $rateOpts = '';
            foreach ($rates as $r) {
                $rateOpts .= sprintf('<option %s value="%d">%s</option>',
                    (isset($rowItem['tax'])&&$rowItem['tax']==$r[0]?'selected':''),
                    $r[0],$r[1]);
            }
            $fsCheck = $rowItem['foodstamp'] == 1 ? 'checked' : '';
            $scaleCheck = $rowItem['scale'] == 1 ? 'checked' : '';
            $qtyCheck = $rowItem['qttyEnforced'] == 1 ? 'checked' : '';
            $wicCheck = $rowItem['wicable'] == 1 ? 'checked' : '';
            $inUseCheck = $rowItem['inUse'] == 1 ? 'checked' : '';

            $disc_opts = array(
                0 => 'No',
                1 => 'Yes',
                2 => 'Trans Only',
                3 => 'Line Only',
            );
            $rowItem['discount'] = $this->mapDiscounts($rowItem['discount'], $rowItem['line_item_discountable']);
            $discountOpts = '';
            foreach ($disc_opts as $id => $val) {
                $discountOpts .= sprintf('<option %s value="%d">%s</option>',
                            ($id == $rowItem['discount'] ? 'selected' : ''),
                            $id, $val);
            }
            $deposit = ($rowItem['deposit'] != 0) ? $rowItem['deposit'] : '';

            $ageOpts = '';
            foreach (array('n/a'=>0, 18=>18, 21=>21) as $label=>$age) {
                $ageOpts .= sprintf('<option %s value="%d">%s</option>',
                                ($age == $rowItem['idEnforced'] ? 'selected' : ''),
                                $age, $label);
            }

            $local_opts = array(0=>'No');
            $origin = new OriginsModel($dbc);
            $local_opts = array_merge($local_opts, $origin->getLocalOrigins());
            if (count($local_opts) == 1) {
                $local_opts[1] = 'Yes'; // generic local if no origins defined
            }
            $localOpts = '';
            foreach($local_opts as $id => $val) {
                $localOpts .= sprintf('<option value="%d" %s>%s</option>',
                    $id, ($id == $rowItem['local']?'selected':''), $val);
            }

            $ret .= <<<HTML
<tr>
    <th class="text-right">Dept</th>
    <td colspan="7" class="form-inline">
        <select id="super-dept{$store_id}" name="super[]"
            class="form-control chosen-select syncable-input" 
            onchange="chainSuperDepartment('../ws/', this.value, {
                dept_start:'#department{$store_id}', 
                callback:function(){
                    \$('#department{$store_id}').trigger('chosen:updated');
                    baseItem.chainSubs({$store_id});
                    var opts = $('#department{$store_id}').html();
                    $('.chosen-dept').each(function(i, e) {
                        if (e.id != 'department{$store_id}') {
                            $(e).html(opts);
                            $(e).trigger('chosen:updated');
                            baseItem.chainSubs(e.id.substring(10));
                        }
                    });
                }
            });">
            {$superOpts}
        </select>
        <select name="department[]" id="department{$store_id}" 
            class="form-control chosen-select chosen-dept syncable-input"
            onchange="baseItem.chainSubs({$store_id});">
            {$deptOpts}
        </select>
        <select name="subdept[]" id="subdept{$store_id}" 
            class="form-control chosen-select syncable-input">
            <option {$subZero} value="0">None</option>
            {$subOpts}
        </select>
    </td>
    <th class="small text-right">SKU</th>
    <td colspan="2">
        <input type="text" name="vendorSKU" class="form-control input-sm"
            value="{$rowItem['sku']}" 
            onchange="$('#vsku{$jsVendorID}').val(this.value);" 
            {$vFieldsDisabled} {$aliasDisabled} id="product-sku-field" />
        <input type="hidden" name="isAlias" value="{$rowItem['isAlias']}" />
    </td>
</tr>
<tr>
    <th class="small text-right">Tax</th>
    <td>
    <select name="tax[]" id="tax{$store_id}" 
        class="form-control input-sm syncable-input">
        {$rateOpts}
    </select></td>
    <td colspan="4" class="small">
        <label>FS
        <input type="checkbox" value="{$store_id}" name="FS[]" id="FS{$store_id}"
            class="syncable-checkbox" {$fsCheck} />
        </label>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <label>Scale
        <input type="checkbox" value="{$store_id}" name="Scale[]" 
            class="scale-checkbox syncable-checkbox" {$scaleCheck} />
        </label>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <label>QtyFrc
        <input type="checkbox" value="{$store_id}" name="QtyFrc[]" 
            class="qty-checkbox syncable-checkbox" {$qtyCheck} />
        </label>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <label>WIC
        <input type="checkbox" value="{$store_id}" id="wic{$store_id}" name="prod-wicable[]" 
            class="prod-wicable-checkbox syncable-checkbox" {$wicCheck} />
        </label>
        &nbsp;&nbsp;&nbsp;&nbsp;
        <label>InUse
        <input type="checkbox" value="{$store_id}" name="prod-in-use[]" 
            class="in-use-checkbox" {$inUseCheck} 
            onchange="$('#extra-in-use-checkbox').prop('checked', $(this).prop('checked'));" />
        </label>
        </td>
        <th class="small text-right">Discount</th>
        <td class="col-sm-1">
            <select id="discount-select{$store_id}" name="discount[]" 
                class="form-control input-sm syncable-input">
                {$discountOpts}
            </select>
        </td>
        <th class="small text-right">Deposit</th>
        <td colspan="2">
            <input type="text" name="deposit-upc[]" class="form-control input-sm syncable-input"
                value="{$deposit}" placeholder="Deposit Item PLU/UPC"
                onchange="\$('#deposit').val(this.value);" />
        </td>
    </tr>
    <tr>
        <th class="small text-right">Case Size</th>
        <td class="col-sm-1">
            <input type="text" name="caseSize" 
                class="form-control input-sm product-case-size"
                value="{$rowItem['caseSize']}" 
                onchange="\$('#vunits{$jsVendorID}').val(this.value);" 
                {$vFieldsDisabled} {$aliasDisabled} />
        </td>
        <th class="small text-right">Pack Size</th>
        <td class="col-sm-1">
            <input type="text" name="size[]" 
                class="form-control input-sm product-pack-size syncable-input"
                value="{$rowItem['size']}" 
                onchange="\$('#vsize{$jsVendorID}').val(this.value);" />
        </td>
        <th class="small text-right">Unit of measure</th>
        <td class="col-sm-1">
            <input type="text" name="unitm[]" 
                class="form-control input-sm unit-of-measure syncable-input"
                value="{$rowItem['unitofmeasure']}" />
        </td>
        <th class="small text-right">Age Req</th>
        <td class="col-sm-1">
            <select name="id-enforced[]" class="form-control input-sm id-enforced syncable-input"
                onchange="\$('#idReq').val(this.value);">
                {$ageOpts}
            </select>
        </td>
        <th class="small text-right">Local</th>
        <td>
            <select name="prod-local[]" class="form-control input-sm prod-local syncable-input"
                onchange="\$('#local-origin-id').val(this.value);">
                {$localOpts}
            </select>
        </td>
    </tr>
HTML;
            if ($rowItem['inventoried']) {
                $ret .= sprintf('<tr>
                    <th class="small text-right">On Hand</th><td class="small">%d</td>
                    <th class="small text-right">Last Count</th><td colspan="2" class="small">%d on %s</td>
                    <th class="small text-right">Par</th><td class="small">%s</td>
                    <td colspan="3" class="small"><a href="inventory/InvCountPage.php?id=%s&store=%d">Adjust count/par</a></td>
                    </tr>',
                    $rowItem['onHand'],
                    $rowItem['lastCount'], $rowItem['countDate'],
                    $rowItem['invPar'],
                    $upc,
                    $store_id
                );
            }
            $ret .= '</table></div>';
            if (FannieConfig::config('STORE_MODE') != 'HQ') {
                break;
            }
        }
        $ret .= '</div>';
        // sync button will copy current tab values to all other store tabs
        if (!$new_item && FannieConfig::config('STORE_MODE') == 'HQ') {
            $nav_tabs .= '<li><label title="Apply update to all stores">
                <input type="checkbox" id="store-sync" ';
            $audited = FannieAuth::validateUserQuiet('audited_pricechange');
            $nav_tabs .= ($audited) ? 'disabled' : 'checked';
            $nav_tabs .= ' /> Sync</label></li>';
        }
        $nav_tabs .= '</ul>';
        // only show the store tabs in HQ mode
        if (FannieConfig::config('STORE_MODE') == 'HQ') {
            $ret = str_replace('{{nav_tabs}}', $nav_tabs, $ret);
        } else {
            $ret = str_replace('{{nav_tabs}}', '', $ret);
        }

        $ret .= <<<HTML
<div id="newVendorDialog" title="Create new Vendor" class="collapse">
    <fieldset>
        <label for="newVendorName">Vendor Name</label>
        <input type="text" name="newVendorName" id="newVendorName" class="form-control" />
    </fieldset>
</div>
</div> <!-- end panel-body -->
</div> <!-- end panel-->
HTML;

        return $ret;
    }

    public function getFormJavascript($upc)
    {
        return file_get_contents(__DIR__ . '/baseItem.js');
    }

    private function formNoEx($field, $default)
    {
        try {
            return $this->form->{$field};
        } catch (Exception $ex) {
            return $default;
        }
    }

    function SaveFormData($upc)
    {
        $FANNIE_PRODUCT_MODULES = FannieConfig::config('PRODUCT_MODULES', array());
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();

        $model = new ProductsModel($dbc);
        $model->upc($upc);
        $stores = $this->formNoEx('store_id', array());
        for ($i=0; $i<count($stores); $i++) {
            $model->store_id($stores[$i]);
            if (!$model->load()) {
                // fully init new record
                $model->pricemethod(0);
                $model->groupprice(0.00);
                $model->quantity(0);
                $model->special_price(0);
                $model->specialpricemethod(0);
                $model->specialquantity(0);
                $model->specialgroupprice(0);
                $model->advertised(0);
                $model->tareweight(0);
                $model->start_date('0000-00-00');
                $model->end_date('0000-00-00');
                $model->discounttype(0);
                $model->wicable(0);
                $model->scaleprice(0);
                $model->inUse(1);
                $model->created(date('Y-m-d H:i:s'));
            }

            $taxes = $this->formNoEx('tax', array());
            if (isset($taxes[$i])) {
                $model->tax($taxes[$i]);
            }
            $fs = $this->formNoEx('FS', array());
            $model->foodstamp(in_array($stores[$i], $fs) ? 1 : 0);
            $scale = $this->formNoEx('Scale', array());
            $model->scale(in_array($stores[$i], $scale) ? 1 : 0);
            $qtyFrc = $this->formNoEx('QtyFrc', array());
            $model->qttyEnforced(in_array($stores[$i], $qtyFrc) ? 1 : 0);
            $wic = FormLib::get('prod-wicable', array());
            $model->wicable(in_array($stores[$i], $wic) ? 1 : 0);
            $discount_setting = $this->formNoEx('discount', array());
            if (isset($discount_setting[$i])) {
                switch ($discount_setting[$i]) {
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
            }
            $price = $this->formNoEx('price', array());
            if (isset($price[$i])) {
                $model->normal_price($price[$i]);
            }
            $cost = $this->formNoEx('cost', array());
            if (isset($cost[$i])) {
                $model->cost($cost[$i]);
            }
            $desc = $this->formNoEx('descript', array());
            if (isset($desc[$i])) {
                $model->description($desc[$i]);
            }
            $brand = $this->formNoEx('manufacturer', array());
            if (isset($brand[$i])) {
                $model->brand($brand[$i]);
            }
            /**
            $model->pricemethod(0);
            $model->groupprice(0.00);
            $model->quantity(0);
            */
            $dept = $this->formNoEx('department', array());
            if (isset($dept[$i])) {
                $model->department($dept[$i]);
            }
            $size = $this->formNoEx('size', array());
            if (isset($size[$i])) {
                $model->size($size[$i]);
            }
            $model->modified(date('Y-m-d H:i:s'));
            $unit = FormLib::get('unitm');
            $unit = $this->formNoEx('unitm', array());
            if (isset($unit[$i])) {
                $model->unitofmeasure($unit[$i]);
            }
            $subdept = $this->formNoEx('subdept', array());
            if (isset($subdept[$i])) {
                $model->subdept($subdept[$i]);
            }

            // lookup vendorID by name
            $vendorID = 0;
            $v_input = $this->formNoEx('distributor', array());
            if (isset($v_input[$i])) {
                $vendorID = $this->getVendorID($v_input[$i]);
            }
            $model->default_vendor_id($vendorID);
            $inUse = FormLib::get('prod-in-use', array());
            $model->inUse(in_array($stores[$i], $inUse) ? 1 : 0);
            $idEnf = FormLib::get('id-enforced', array());
            if (isset($idEnf[$i])) {
                $model->idEnforced($idEnf[$i]);
            }
            $local = FormLib::get('prod-local');
            if (isset($local[$i])) {
                $model->local($local[$i]);
            }
            $deposit = FormLib::get('deposit-upc');
            if (isset($deposit[$i])) {
                if ($deposit[$i] == '') {
                    $deposit[$i] = 0;
                }
                $model->deposit($deposit[$i]);
            }
            $model->formatted_name($this->formatName($i));

            $model->save();
        }

        /**
          If a vendor is selected, intialize
          a vendorItems record
        */
        if ($vendorID != 0) {
            $this->saveVendorItem($model, $vendorID);
        }

        if ($dbc->tableExists('prodExtra')) {
            $this->saveProdExtra($model);
        }

        if (!isset($FANNIE_PRODUCT_MODULES['ProdUserModule']) && $dbc->tableExists('productUser')) {
            $this->saveProdUser($upc);
        }
    }

    private function getVendorID($name)
    {
        $dbc = $this->db();
        $vendor = new VendorsModel($dbc);
        $vendor->vendorName($name);
        foreach ($vendor->find('vendorID') as $obj) {
            return $obj->vendorID();
        }

        return 0;
    }

    private function formatName($index)
    {
        /* products.formatted_name is intended to be maintained automatically.
         * Get all enabled plugins and standard modules of the base.
         * Run plugins first, then standard modules.
         */
        $formatters = FannieAPI::ListModules('ProductNameFormatter');
        $fmt_name = "";
        $fn_params = array('index' => $index);
        foreach ($formatters as $formatter_name) {
            $formatter = new $formatter_name();
            $fmt_name = $formatter->compose($fn_params);
            if (isset($formatter->this_mod_only) &&
                $formatter->this_mod_only) {
                break;
            }
        }

        return $fmt_name;
    }

    private function saveProdUser($upc)
    {
        try {
            $dbc = $this->db();
            $model = new ProductUserModel($dbc);
            $model->upc($upc);
            $model->description($this->form->puser_description);
            return $model->save();
        } catch (Exception $ex) {
            return false;
        }
    }

    private function saveProdExtra($product)
    {
        $dbc = $this->db();
        $extra = new ProdExtraModel($dbc);
        $extra->upc($product->upc());
        if (!$extra->load()) {
            $extra->variable_pricing(0);
            $extra->margin(0);
            $extra->case_quantity('');
            $extra->case_cost(0.00);
            $extra->case_info('');
        }
        $extra->manufacturer($product->brand());
        $extra->cost($product->cost());
        try {
            $extra->distributor($this->form->distributor[0]);
        } catch (Exception $ex) {
            $extra->distributor('');
        }

        return $extra->save();
    }

    private function saveVendorItem($product, $vendorID)
    {
        $dbc = $this->db();
        $upc = $product->upc();
        /**
          If a vendor is selected, intialize
          a vendorItems record
        */
        $vitem = new VendorItemsModel($dbc);
        $vitem->vendorID($vendorID);
        $vitem->upc($upc);
        try {
            $sku = $this->form->vendorSKU;
            $caseSize = $this->form->caseSize;
            $alias = $this->form->isAlias;
            if ($alias) {
                return true;
            }
            if (!empty($sku) && $sku != $upc) {
                /**
                  If a SKU is provided, update any
                  old record that used the UPC as a
                  placeholder SKU.
                */
                $existsP = $dbc->prepare('
                    SELECT sku
                    FROM vendorItems
                    WHERE sku=?
                        AND upc=?
                        AND vendorID=?');
                $existsR = $dbc->execute($existsP, array($upc, $upc, $vendorID));
                if ($dbc->numRows($existsR) > 0 && $sku != $upc) {
                    $fixSkuP = $dbc->prepare('
                        UPDATE vendorItems
                        SET sku=?
                        WHERE sku=?
                            AND vendorID=?');
                    $dbc->execute($fixSkuP, array($sku, $upc, $vendorID));
                }
            } else {
                $sku = $upc;
            }
        } catch (Exception $ex) {
            $sku = $upc;
            $caseSize = 1;
        }
        $vitem->sku($sku);
        $vitem->size($product->size());
        $vitem->description($product->description());
        $vitem->brand($product->brand());
        $vitem->units($caseSize);
        $vitem->cost($product->cost());
        return $vitem->save();
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

    private function mapDiscounts($disc, $line)
    {
        if ($disc == 1 && $line == 1) {
            return 1;
        } elseif ($disc == 1 && $line == 0) {
            return 2;
        } elseif ($disc == 0 && $line == 1) {
            return 3;
        } 

        return $disc;
    }

    /**
      Build ID=>name mappings for department tiers
      $supers lists its departments
      $subs is indexed by parent department number
    */
    private function deptMaps($rowItem, $range_limit)
    {
        $supers = array();
        $depts = array();
        $subs = array();
        $superID = '';
        $dbc = $this->db();

        $deptQ = '
            SELECT dept_no,
                dept_name,
                subdept_no,
                subdept_name,
                s.dept_ID,
                MIN(m.superID) AS superID
            FROM departments AS d
                LEFT JOIN subdepts AS s ON d.dept_no=s.dept_ID
                LEFT JOIN superdepts AS m ON d.dept_no=m.dept_ID ';
        if (is_array($range_limit) && count($range_limit) == 2) {
            $deptQ .= ' WHERE m.superID BETWEEN ? AND ? ';
        } else {
            $range_limit = array();
        }
        $deptQ .= '
            GROUP BY d.dept_no,
                d.dept_name,
                s.subdept_no,
                s.subdept_name,
            s.dept_ID
            ORDER BY d.dept_no, s.subdept_name';
        $deptP = $dbc->prepare($deptQ);
        $deptR = $dbc->execute($deptP, $range_limit);
        while ($row = $dbc->fetchRow($deptR)) {
            if (!isset($depts[$row['dept_no']])) $depts[$row['dept_no']] = $row['dept_name'];
            if ($row['dept_no'] == $rowItem['department']) {
                $superID = $row['superID'];
            }
            if (!isset($supers[$row['superID']])) {
                $supers[$row['superID']] = array();
            }
            $supers[$row['superID']][] = $row['dept_no'];

            if ($row['subdept_no'] == '') {
                continue;
            }

            if (!isset($subs[$row['dept_ID']]))
                $subs[$row['dept_ID']] = '';
            $subs[$row['dept_ID']] .= sprintf('<option %s value="%d">%d %s</option>',
                    ($row['subdept_no'] == $rowItem['subdept'] ? 'selected':''),
                    $row['subdept_no'],$row['subdept_no'],$row['subdept_name']);
        }

        return array($superID, $supers, $depts, $subs);
    }
}

