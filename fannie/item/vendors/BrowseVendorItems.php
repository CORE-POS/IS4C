<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BrowseVendorItems extends FanniePage 
{
    protected $title = "Fannie : Browse Vendor Catalog";
    protected $header = "Browse Vendor Catalog";

    public $description = '[Vendor Items] lists items in the vendor\'s catalog. Must be
    accessed via the Vendor Editor.';

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    function preprocess(){

        $ajax = FormLib::get_form_value('action');
        if ($ajax !== ''){
            $this->ajax_callbacks($ajax);
            return False;
        }       

        return True;
    }

    function ajax_callbacks($action){
        global $FANNIE_OP_DB;
        switch($action){
        case 'getCategoryBrands':
            $this->getCategoryBrands(FormLib::get_form_Value('vid'),FormLib::get_form_value('deptID'));
            break;
        case 'showCategoryItems':
            $ret = array();
            $ret['tags'] = $this->guessSuper(
                FormLib::get('vid'),
                FormLib::get('deptID'),
                FormLib::get('brand')
            );
            $ret['items'] = $this->showCategoryItems(
                FormLib::get_form_value('vid'),
                FormLib::get_form_value('deptID'),
                FormLib::get_form_value('brand'),
                $ret['tags']
            );
            echo json_encode($ret);
            break;
        case 'addPosItem':
            $this->addToPos(
                FormLib::get_form_value('upc'),
                FormLib::get_form_value('vid'),
                FormLib::get_form_value('price'),
                FormLib::get_form_value('dept'),
                FormLib::get('tags', -1)
            );
            break;
        default:
            echo 'bad request';
            break;
        }
    }

    private function getCategoryBrands($vid,$did)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $query = "SELECT brand FROM vendorItems AS v
            LEFT JOIN vendorDepartments AS d ON
            v.vendorDept=d.deptID WHERE v.vendorID=?";
        $args = array($vid);
        if($did != 'All'){
            $query .= ' AND vendorDept=? ';
            $args[] = $did;
        }
        $query .= "GROUP BY brand ORDER BY brand";
        $ret = "<option value=\"\">Select a brand...</option>";
        $p = $dbc->prepare($query);
        $result = $dbc->execute($p,$args);
        while($row=$dbc->fetch_row($result))
            $ret .= "<option>$row[0]</option>";

        echo $ret;
    }

    private function guessSuper($vid, $did, $brand)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $args = array($vid, $brand);
        $guess = 'SELECT s.superID
                  FROM products AS p
                    LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
                    INNER JOIN vendorItems AS v ON p.upc=v.upc
                  WHERE v.vendorID=?
                    AND s.superID IS NOT NULL
                    AND v.brand=? ';
        if ($did != 'All') {
            $guess .= ' AND v.vendorDept=? ';
            $args[] = $did;
        }
        $guess .= ' ORDER BY count(*) DESC';

        $prep = $dbc->prepare($guess);
        $result = $dbc->execute($prep, $args);
        $defaultSuper = -999;
        if ($dbc->num_rows($result) > 0) {
            $row = $dbc->fetch_row($result);
            if ($row['superID'] != '') {
                $defaultSuper = $row['superID'];
            }
        } 
        
        if ($defaultSuper == -999 && $did != 'All') {
            $guess = 'SELECT s.superID
                      FROM products AS p
                        LEFT JOIN MasterSuperDepts AS s ON p.department=s.dept_ID
                        INNER JOIN vendorItems AS v ON p.upc=v.upc
                      WHERE v.vendorID=?
                        AND v.vendorDept=?
                      ORDER BY count(*) DESC';
            $prep = $dbc->prepare($guess);
            $result = $dbc->execute($prep, array($vid, $did));
            if ($dbc->num_rows($result) > 0) {
                $row = $dbc->fetch_row($result);
                $defaultSuper = $row['superID'];
            }
        }

        return $defaultSuper;
    }

    private function showCategoryItems($vid,$did,$brand,$ds=-999)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $defaultSuper = $ds;

        $depts = "";
        $p = $dbc->prepare("SELECT dept_no,dept_name 
                                      FROM departments AS d
                                        LEFT JOIN MasterSuperDepts AS s ON d.dept_no=s.dept_ID
                                      ORDER BY 
                                          CASE WHEN s.superID=? THEN 0 ELSE 1 END,
                                          dept_no");
        $rp = $dbc->execute($p, array($defaultSuper));
        while($rw = $dbc->fetch_row($rp))
            $depts .= "<option value=$rw[0]>$rw[0] $rw[1]</option>";

        $query = "
            SELECT v.upc,
                v.brand,
                v.description,
                v.size,
                v.cost as cost,
                CASE WHEN d.margin IS NULL THEN 0 ELSE d.margin END as margin,
                v.srp
            FROM vendorItems AS v 
                LEFT JOIN vendorDepartments AS d ON d.deptID=v.vendorDept AND d.vendorID=v.vendorID
            WHERE v.vendorID=? 
                AND v.brand=? ";
        $args = array($vid,$brand);
        if ($did != 'All'){
            $query .= ' AND vendorDept=? ';
            $args[] = $did;
        }
        $query .= "ORDER BY v.upc";
        $posP = $dbc->prepare('
            SELECT upc, 
                normal_price, 
                department,
                d.dept_name
            FROM products AS p
                LEFT JOIN departments AS d ON p.department=d.dept_no
            WHERE upc=?');
        
        $ret = "<table class=\"table table-bordered\">";
        $ret .= "<tr><th>UPC</th><th>Brand</th><th>Description</th>";
        $ret .= "<th>Size</th><th>Cost</th><th>Price</th><th>Dept.</th><th>&nbsp;</th></tr>";
        $p = $dbc->prepare($query);
        $result = $dbc->execute($p,$args);
        while ($row = $dbc->fetch_row($result)) {
            $inPOS = $dbc->execute($posP, array($row['upc']));
            if ($inPOS && $dbc->numRows($inPOS) > 0) {
                $pos = $dbc->fetchRow($inPOS);
                $ret .= sprintf("<tr class=\"alert-success\">
                    <td><a href=\"../ItemEditorPage.php?searchupc=%s\">%s</a></td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>%s</td>
                    <td>\$%.2f</td>
                    <td>\$%.2f</td>
                    <td>%d %s</td>
                    <td>&nbsp;</td>
                    </tr>",
                    $row['upc'], $row['upc'],
                    $row['brand'],
                    $row['description'],
                    $row['size'],
                    $row['cost'],
                    $pos['normal_price'],
                    $pos['department'], $pos['dept_name']
                );
            } else {
                $srp = !empty($row['srp']) ? $row['srp'] : $this->getSRP($row['cost'],$row['margin']);
                $ret .= sprintf("<tr id=row%s>
                    <td><a href=\"../ItemEditorPage.php?searchupc=%s\">%s</a></td>
                    <td>%s</td><td>%s</td>
                    <td>%s</td><td>\$%.2f</td>
                    <td class=\"col-sm-1\">
                        <div class=\"input-group\">
                            <span class=\"input-group-addon\">$</span>
                            <input type=text size=5 value=%.2f id=price%s 
                                class=\"form-control price-field\" />
                        </div>
                    </td><td><select id=\"dept%s\" class=\"form-control\">%s</select></td>
                    <td id=button%s>
                    <button type=button value=\"Add to POS\" class=\"btn btn-default\"
                    onclick=\"addToPos('%s');\">Add to POS</button></td>
                    </tr>",
                    $row['upc'],
                    $row['upc'], $row['upc'],
                    $row['brand'],$row['description'],
                    $row['size'],$row['cost'],$srp,$row['upc'],
                    $row['upc'],$depts,$row['upc'],$row['upc']);
            }
        }
        $ret .= "</table>";

        return $ret;
    }

    private function addToPos($upc,$vid,$price,$dept,$tags=-1)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $p = $dbc->prepare("SELECT i.*,v.vendorName FROM vendorItems AS i
            LEFT JOIN vendors AS v ON v.vendorID=i.vendorID
            WHERE i.vendorID=? AND upc=?");
        $vinfo = $dbc->execute($p, array($vid,$upc));
        $vinfo = $dbc->fetch_row($vinfo);
        $p = $dbc->prepare("SELECT * FROM departments WHERE dept_no=?");
        $dinfo = $dbc->execute($p,array($dept));
        $dinfo = $dbc->fetch_row($dinfo);
        
        $model = new ProductsModel($dbc);
        $model->upc(BarcodeLib::padUPC($upc));
        $model->description($vinfo['description']);
        $model->normal_price($price);
        $model->department($dept);
        $model->tax($dinfo['dept_tax']);
        $model->foodstamp($dinfo['dept_fs']);
        $model->cost($vinfo['cost']);
        $model->default_vendor_id($vid);
        $model->brand($vinfo['brand']);
        $model->store_id(1);
        $model->save();

        $xInsQ = $dbc->prepare("INSERT INTO prodExtra (upc,manufacturer,distributor,cost,margin,variable_pricing,location,
                case_quantity,case_cost,case_info) VALUES
                (?,?,?,?,0.00,0,'','',0.00,'')");
        $args = array($upc,$vinfo['brand'],
                $vinfo['vendorName'],$vinfo['cost']);
        $dbc->execute($xInsQ,$args);

        if ($tags !== -1) {
            $tag = new ShelftagsModel($dbc);
            $tag->id($tags);
            $tag->upc($upc);
            $info = $model->getTagData();
            $tag->normal_price($info['normal_price']);
            $tag->description($info['description']);
            $tag->brand($info['brand']);
            $tag->vendor($info['vendor']);
            $tag->sku($info['sku']);
            $tag->size($info['size']);
            $tag->units($info['units']);
            $tag->pricePerUnit($info['pricePerUnit']);
            $tag->save();
        }

        echo "Item added";
    }

    private function getSRP($cost,$margin)
    {
        $srp = sprintf("%.2f",$cost/(1-$margin));
        while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" &&
               substr($srp,strlen($srp)-1,strlen($srp)) != "9")
            $srp += 0.01;
        return $srp;
    }

    function body_content()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $vid = FormLib::get_form_value('vid');
        if ($vid === ''){
            return "<i>Error: no vendor selected</i>";
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $cats = "";
        $p = $dbc->prepare("SELECT i.vendorDept, d.name 
                                      FROM vendorItems AS i
                                        LEFT JOIN vendorDepartments AS d
                                        ON i.vendorID=d.vendorID AND i.vendorDept=d.deptID
                                      WHERE i.vendorID=?
                                      GROUP BY i.vendorDept, d.name
                                      ORDER BY i.vendorDept");
        $rp = $dbc->execute($p,array($vid));
        while ($rw = $dbc->fetch_row($rp)) {
            if ($rw['vendorDept'] == 0 && empty($rw['name'])) {
                continue;
            }
            $cats .= "<option value=$rw[0]>$rw[0] $rw[1]</option>";
        }

        if ($cats =="") {
            $cats = "<option value=\"\">Select a subcategory...</option><option selected>All</option>";
            $this->addOnloadCommand('catchange()');
        } else {
            $cats = "<option value=\"\">Select a subcategory...</option>".$cats; 
        }

        ob_start();
        ?>
        <div id="categorydiv" class="form-inline">
        <select id=categoryselect onchange="catchange();" class="form-control">
        <?php echo $cats ?>
        </select>
        &nbsp;&nbsp;&nbsp;
        <select id=brandselect onchange="brandchange();" class="form-control">
        <option>Select a subcategory first...</option>
        </select>
        &nbsp;&nbsp;&nbsp;
        <select id="shelftags" class="form-control">
        <option value="-1">Shelf Tag Page</option>
        <?php
        $queues = new ShelfTagQueuesModel($dbc);
        echo $queues->toOptions();
        ?>
        </select>
        </div>
        <hr />
        <div id="contentarea">
        <?php if (isset($_REQUEST['did'])){
            echo showCategoryItems($vid,$_REQUEST['did']);
        }
        ?>
        </div>
        <div id="loading-bar" class="col-sm-6 collapse">
            <div class="progress">
                <div class="progress-bar progress-bar-striped active" style="width:100%;"
                    role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
                    <span class="sr-only">Loading</span>
                </div>
            </div>
        </div>
        <input type="hidden" id="vendorID" value="<?php echo $vid; ?>" />
        <input type="hidden" id="urlpath" value="<?php echo $FANNIE_URL; ?>" />
        <p><a href="VendorIndexPage.php?vid=<?php echo $vid; ?>" class="btn btn-default">Home</a></p>
        <?php
        
        $this->add_script('browse.js');

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>This tool is used to create POS products from
            entries in the vendor\'s catalog of items. Selecting
            a subcategory and brand first is necessary to keep the
            list of available items a manageable size.
            </p>
            <p>Green rows are already in POS as products. Other
            items can be added by entering an appropriate price
            and department. CORE will try to guess correct values
            but the default selections should still be verified.
            </p>
            <p>The third, rightmost dropdown box at the top controls
            where new shelf tags are created for the added products.
            Again CORE will try to guess the correct set.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
        $phpunit->assertEquals('1.99', $this->getSRP(1.96, 0));
        $phpunit->assertNotEquals(0, strlen($this->showCategoryItems(1,1,'test')));
        $guess = is_numeric($this->guessSuper(1, 1, 'test'));
        ob_start();
        $this->getCategoryBrands(1, 1);
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
    }
}

FannieDispatch::conditionalExec();

