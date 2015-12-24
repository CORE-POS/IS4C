<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

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

include(dirname(__FILE__). '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BatchFromSearch extends FannieRESTfulPage
{

    protected $header = 'Create Batch From Search Results';
    protected $title = 'Create Batch From Search Results';

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;

    public $description = '[Batch From Search] takes a set of advanced search results and
    creates a sale or price change batch. Must be accessed via Advanced Search.';
    public $has_unit_tests = true;

    private $upcs = array();

    function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       $this->__routes[] = 'post<createBatch>';
       $this->__routes[] = 'post<redoSRPs>';
       return parent::preprocess();
    }

    function post_createBatch_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $type = $this->form->batchType;
        $name = $this->form->batchName;
        $startdate = $this->form->startDate;
        $enddate = $this->form->endDate;
        $owner = $this->form->batchOwner;
        $priority = 0;

        $upcs = $this->form->upc;
        $prices = $this->form->price;

        $btype = new BatchTypeModel($dbc);
        $btype->batchTypeID($type);
        if (!$btype->load()) {
            echo 'Invalid Batch Type ' . $type;
            return false;
        }
        $discounttype = $btype->discType();

        // make sure item data is present before creating batch
        if (!is_array($upcs) || !is_array($prices) || count($upcs) != count($prices) || count($upcs) == 0) {
            echo 'Invalid item data';
            return false;
        }

        $batch = new BatchesModel($dbc);
        $batch->startDate($startdate);
        $batch->endDate($enddate);
        $batch->batchName($name);
        $batch->batchType($type);
        $batch->discountType($discounttype);
        $batch->priority($priority);
        $batch->owner($owner);
        $batchID = $batch->save();

        if ($this->config->get('STORE_MODE') === 'HQ') {
            StoreBatchMapModel::initBatch($batchID);
        }

        if ($dbc->tableExists('batchowner')) {
            $insQ = $dbc->prepare("insert batchowner values (?,?)");
            $insR = $dbc->execute($insQ,array($batchID,$owner));
        }

        $this->itemsToBatch($batchID, $dbc, $upcs, $prices);

        /**
          If tags were requested and it's price change batch, make them
          Lookup vendor info for each item then add a shelftag record
        */
        $tagset = $this->form->tagset;
        if ($discounttype == 0 && $tagset !== '') {
            $this->itemsToTags($tagset, $dbc, $upcs, $prices);
        }

        return 'Location: newbatch/BatchManagementTool.php?startAt=' . $batchID;
    }

    private function itemsToBatch($batchID, $dbc, $upcs, $prices)
    {
        // add items to batch
        for($i=0; $i<count($upcs); $i++) {
            $upc = $upcs[$i];
            $price = isset($prices[$i]) ? $prices[$i] : 0.00;
            $list = new BatchListModel($dbc);
            $list->upc(BarcodeLib::padUPC($upc));
            $list->batchID($batchID);
            $list->salePrice($price);
            $list->groupSalePrice($price);
            $list->active(0);
            $list->pricemethod(0);
            $list->quantity(0);
            $list->save();
        }
    }

    private function itemsToTags($tagset, $dbc, $upcs, $prices)
    {
        $tag = new ShelftagsModel($dbc);
        $product = new ProductsModel($dbc);
        for($i=0; $i<count($upcs);$i++) {
            $upc = $upcs[$i];
            $price = isset($prices[$i]) ? $prices[$i] : 0.00;
            $product->upc($upc);
            $info = $product->getTagData($price);
            $tag->id($tagset);
            $tag->upc($upc);
            $tag->description($info['description']);
            $tag->normal_price($price);
            $tag->brand($info['brand']);
            $tag->sku($info['sku']);
            $tag->size($info['size']);
            $tag->units($info['units']);
            $tag->vendor($info['vendor']);
            $tag->pricePerUnit($info['pricePerUnit']);
            $tag->save();
        }
    }

    function post_redoSRPs_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $upcs = $this->form->upc;
        $vendorID = $this->form->preferredVendor;

        for ($i=0; $i<count($upcs); $i++) {
            $upcs[$i] = BarcodeLib::padUPC($upcs[$i]);
        }
        list($in_sql, $args) = $dbc->safeInClause($upcs);

        $query = '
            SELECT p.upc,
                CASE WHEN v.srp IS NULL THEN 0 ELSE v.srp END as newSRP
            FROM products AS p
                LEFT JOIN vendorItems AS v ON p.upc=v.upc 
            WHERE p.upc IN (' . $in_sql . ')
            ORDER BY p.upc,
                CASE WHEN v.vendorID=? THEN -999 ELSE v.vendorID END';
        $prep = $dbc->prepare($query);
        $args[] = $vendorID;
        $result = $dbc->execute($prep, $args);

        $prevUPC = 'notUPC';
        $results = array();
        while ($row = $dbc->fetch_row($result)) {
            if ($row['upc'] == $prevUPC) {
                continue;
            }
            $results[] = array(
                'upc' => $row['upc'],
                'srp' => $row['newSRP'],
            );
            $prevUPC = $row['upc'];
        }

        echo json_encode($results);

        return false;
    }

    function post_u_handler()
    {
        if (!is_array($this->u)) {
            $this->u = array($this->u);
        }
        foreach($this->u as $postdata) {
            if (is_numeric($postdata)) {
                $this->upcs[] = BarcodeLib::padUPC($postdata);
            }
        }

        if (empty($this->upcs)) {
            echo '<div class="alert alert-danger">Error: no valid data</div>';
            return false;
        } else {
            return true;
        }
    }

    function post_u_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $this->addScript('from-search.js');
        $ret = '<form action="BatchFromSearch.php" method="post">';

        $ret .= '<div class="form-group form-inline">';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $types = $dbc->query('SELECT batchTypeID, typeDesc, discType FROM batchType');
        $discTypes = array();
        $ret .= '<select name="batchType" id="batchType" class="form-control"
            onchange="discountTypeFixup()">';
            while($row = $dbc->fetch_row($types)) {
                $ret .= sprintf('<option value="%d">%s</option>',
                            $row['batchTypeID'], $row['typeDesc']
            );
            $discTypes[] = $row;
        }
        $ret .= '</select>';
        foreach($discTypes as $row) {
            $ret .= sprintf('<input type="hidden" id="discType%d" value="%d" />',
                            $row['batchTypeID'], $row['discType']
            );
        }

        $name = FannieAuth::checkLogin();
        $ret .= '
                <label>Name</label>: ';
        $ret .= '<input type="text" class="form-control" name="batchName" value="'
                . ($name ? $name : 'Batch') . ' '
                . date('M j')
                . '" />';

        $ret .= '
                <label>Start</label>: <input type="text" class="form-control date-field" id="startDate" value="'
                . date('Y-m-d') . '" name="startDate" />
                ';

        $ret .= '
                <label>End</label>: <input type="text" class="form-control date-field" id="endDate" value="'
                . date('Y-m-d') . '" name="endDate" />
                </div>';

        $owners = $dbc->query('SELECT super_name FROM MasterSuperDepts GROUP BY super_name ORDER BY super_name');
        $ret .= '<div class="form-group form-inline">
            <label>Owner</label>: <select name="batchOwner" class="form-control" id="batchOwner"><option value=""></option>';
        while($row = $dbc->fetch_row($owners)) {
            $ret .= '<option>' . $row['super_name'] . '</option>';
        }
        $ret .= '<option>IT</option></select>
                <button type="submit" name="createBatch" value="1"
                    class="btn btn-default">Create Batch</button>
                </div>';

        $ret .= '<hr />';

        list($in_sql, $args) = $dbc->safeInClause($this->upcs);
        $query = 'SELECT p.upc, p.description, p.normal_price, m.superID,
                MAX(CASE WHEN v.srp IS NULL THEN 0.00 ELSE v.srp END) as srp
                FROM products AS p
                    LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
                    LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                WHERE p.upc IN ( ' . $in_sql . ')
                GROUP BY p.upc, p.description, p.normal_price, m.superID
                ORDER BY p.upc';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);

        $ret .= '<div id="saleTools" class="form-group form-inline">';
        $ret .= '<label>Markdown</label>
                <div class="input-group">
                    <input type="text" id="mdPercent" class="form-control" value="10" onchange="markDown(this.value);" />
                    <span class="input-group-addon">%</span>
                </div>
                <button type="submit" class="btn btn-default" onclick="markDown($(\'#mdPercent\').val()); return false">Go</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<label>or</label>
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    <input type="text" id="mdDollar" class="form-control" value="0.00" onchange="discount(this.value);" />
                </div>
                <button type="submit" class="btn btn-default" onclick="discount($(\'#mdDollar\').val()); return false">Go</button>';
        $ret .= '</div>';

        $ret .= '<div id="priceChangeTools" class="form-group form-inline">';
        $ret .= '<button type="submit" class="btn btn-default" onclick="useSRPs(); return false;">Use Vendor SRPs</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<select name="preferredVendor" class="form-control" onchange="reCalcSRPs();">
            <option value="0">Auto Choose Vendor</option>';
        $vendors = new VendorsModel($dbc);
        foreach ($vendors->find('vendorName') as $vendor) {
            $ret .= sprintf('<option value="%d">%s</option>',
                        $vendor->vendorID(), $vendor->vendorName());
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<label>Markup</label>
                <div class="input-group">
                    <input type="text" id="muPercent" class="form-control" value="10" onchange="markUp(this.value);" />
                    <span class="input-group-addon">%</span>
                </div>
                <button type="submit" class="btn btn-default" onclick="markUp($(\'#muPercent\').val()); return false">Go</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<label>Tags</label> <select name="tagset" class="form-control" id="tagset"><option value="">No Tags</option>';
        $queues = new ShelfTagQueuesModel($dbc);
        $ret .= $queues->toOptions();
        $ret .= '</select>';
        $ret .= '</div>';

        $ret .= '<table class="table">';
        $ret .= '<tr><th>UPC</th><th>Description</th><th>Retail</th>
                <th id="newPriceHeader">Sale Price</th></tr>';
        $superDetect = array();
        while($row = $dbc->fetch_row($result)) {
            $ret .= sprintf('<tr class="batchItem">
                            <td><input type="hidden" name="upc[]" class="itemUPC" value="%s" />%s</td>
                            <td>%s</td>
                            <td>$%.2f<input type="hidden" class="currentPrice" value="%.2f" /></td>
                            <td><div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="text" name="price[]" class="itemPrice form-control" value="0.00" />
                                <input type="hidden" class="itemSRP" value="%.2f" />
                            </div>
                            </td>
                            </tr>',
                            $row['upc'], $row['upc'],
                            $row['description'],
                            $row['normal_price'], $row['normal_price'],
                            $row['srp']
            );

            if (!isset($superDetect[$row['superID']])) {
                $superDetect[$row['superID']] = 0;
            }
            $superDetect[$row['superID']]++;
        }
        $ret .= '</table>';

        $ret .= '</form>';

        // auto-detect likely owner & tag set by super department
        $tagPage = array_search(max($superDetect), $superDetect);
        if ($tagPage !== false) {
            $this->add_onload_command("\$('#tagset').val($tagPage);\n");
            $this->add_onload_command("\$('#batchOwner').val(\$('#tagset option:selected').text());\n");
        }
        // show sale or price change tools as appropriate
        $this->add_onload_command('discountTypeFixup();');
        // don't let enter key on these fields trigger form submission 
        $this->add_onload_command("\$('#mdPercent').bind('keypress', noEnter);\n");
        $this->add_onload_command("\$('#mdDollar').bind('keypress', noEnter);\n");
        $this->add_onload_command("\$('#muPercent').bind('keypress', noEnter);\n");

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            This tool creates a sale or price change batch
            for a set of advanced search results. The top
            fields control the batch type, name, dates, and
            owner. The middle section contains different tools
            for calculating prices depending on whether the
            batch type selected is a sale or a price change.
            <p>
            For sales, two markdown options are available. The
            percentage will create sale prices that are X% off
            from the regular retial price. The dollar amount will
            create sale prices that are $X.XX less than the
            regular retial price.
            </p>
            <p>
            Price changes include a larger list of options.
            The Use Vendor SRPs button will calculate new
            retail pricing based on vendor-specific margin
            rules. The Auto Choose Vendor option will simply
            use whichever vendor is assigned as the default
            for a given product. Choosing a specific vendor instead
            will use that vendor\'s pricing rules for all 
            products in the list. The markup percentage is
            an alternative to vendor-based pricing and will
            create new prices that are X% above current retail.
            New shelftags are allocated and the Tags dropdown
            controls which set they land in.
            </p>';
    }

    /**
      Create a one-item sale. Requires sample data
      for item, batch types
    */
    public function unitTest($phpunit)
    {
        $this->u = array('0001878777132'); //14.99
        $this->post_u_handler();
        $phpunit->assertEquals(1, count($this->upcs));
        $post = $this->post_u_view();
        $phpunit->assertNotEquals(0, strlen($post));

        $form = new \COREPOS\common\mvc\ValueContainer();
        $form->upc = $this->u;
        $form->preferredVendor = 0;
        $this->setForm($form);
        ob_start();
        $this->post_redoSRPs_handler();
        $json = ob_get_clean();
        $arr = json_decode($json, true);
        $phpunit->assertInternalType('array', $arr);
        $phpunit->assertEquals(1, count($arr));
        $phpunit->assertEquals($this->u[0], $arr[0]['upc']);
        $phpunit->assertEquals(0, $arr[0]['srp']);

        $form->startDate = date('Y-m-d');
        $form->endDate = date('Y-m-d');
        $form->batchName = 'Test BatchFromSearch';
        $form->batchType = 3; // price change batch means tags get created
        $form->batchOwner = 'IT';
        $form->price = array(1.99);
        $form->tagset = 1;
        $this->setForm($form);
        $this->post_createBatch_handler();

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $batch = new BatchesModel($dbc);
        $batch->batchName('Test BatchFromSearch');
        $phpunit->assertEquals(1, count($batch->find()));
        $sale = new BatchListModel($dbc);
        $sale->upc($this->u[0]);
        $sale->salePrice(1.99);
        $phpunit->assertEquals(1, count($sale->find()));

        $tag = new ShelftagsModel($dbc);
        $tag->id(1);
        $tag->upc($this->u[0]);
        $phpunit->assertEquals(true, $tag->load());
    }
}

FannieDispatch::conditionalExec();
