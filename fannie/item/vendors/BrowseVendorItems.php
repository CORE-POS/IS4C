<?php
/*******************************************************************************

    Copyright 2009 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BrowseVendorItems extends FanniePage {
    protected $title = "Fannie : Browse Vendor Catalog";
    protected $header = "Browse Vendor Catalog";
    protected $window_dressing = False;

    public $description = '[Vendor Items] lists items in the vendor\'s catalog. Must be
    accessed via the Vendor Editor.';

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

    private function getCategoryBrands($vid,$did){
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
        $p = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($p,$args);
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

    private function showCategoryItems($vid,$did,$brand,$ds=-999){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $defaultSuper = $ds;

        $depts = "";
        $p = $dbc->prepare_statement("SELECT dept_no,dept_name 
                                      FROM departments AS d
                                        LEFT JOIN MasterSuperDepts AS s ON d.dept_no=s.dept_ID
                                      ORDER BY 
                                          CASE WHEN s.superID=? THEN 0 ELSE 1 END,
                                          dept_no");
        $rp = $dbc->exec_statement($p, array($defaultSuper));
        while($rw = $dbc->fetch_row($rp))
            $depts .= "<option value=$rw[0]>$rw[0] $rw[1]</option>";

        $query = "SELECT v.upc,v.brand,v.description,v.size,
            v.cost as cost,
            CASE WHEN d.margin IS NULL THEN 0 ELSE d.margin END as margin,
            CASE WHEN p.upc IS NULL THEN 0 ELSE 1 END as inPOS,
            s.srp
            FROM vendorItems AS v LEFT JOIN products AS p
            ON v.upc=p.upc LEFT JOIN vendorDepartments AS d
            ON d.deptID=v.vendorDept
            LEFT JOIN vendorSRPs AS s 
            ON v.upc=s.upc AND v.vendorID=s.vendorID
            WHERE v.vendorID=? AND v.brand=?";
        $args = array($vid,$brand);
        if ($did != 'All'){
            $query .= ' AND vendorDept=? ';
            $args[] = $did;
        }
        $query .= "ORDER BY v.upc";
        
        $ret = "<table cellspacing=0 cellpadding=4 border=1>";
        $ret .= "<tr><th>UPC</th><th>Brand</th><th>Description</th>";
        $ret .= "<th>Size</th><th>Cost</th><th colspan=3>&nbsp;</th></tr>";
        $p = $dbc->prepare_statement($query);
        $result = $dbc->exec_statement($p,$args);
        while($row = $dbc->fetch_row($result)){
            if ($row['inPOS'] == 1){
                $ret .= sprintf("<tr style=\"background:#ffffcc;\">
                    <td>%s</td><td>%s</td><td>%s</td>
                    <td>%s</td><td>\$%.2f</td><td colspan=3>&nbsp;
                    </td></tr>",$row['upc'],$row['brand'],
                    $row['description'],$row['size'],$row['cost']);
            }
            else {
                $srp = !empty($row['srp']) ? $row['srp'] : $this->getSRP($row['cost'],$row['margin']);
                $ret .= sprintf("<tr id=row%s><td>%s</td><td>%s</td><td>%s</td>
                    <td>%s</td><td>\$%.2f</td><td>
                    <input type=text size=5 value=%.2f id=price%s />
                    </td><td><select id=\"dept%s\">%s</select></td>
                    <td id=button%s>
                    <input type=submit value=\"Add to POS\"
                    onclick=\"addToPos('%s');\" /></td></tr>",$row['upc'],
                    $row['upc'],$row['brand'],$row['description'],
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

        $p = $dbc->prepare_statement("SELECT i.*,v.vendorName FROM vendorItems AS i
            LEFT JOIN vendors AS v ON v.vendorID=i.vendorID
            WHERE i.vendorID=? AND upc=?");
        $vinfo = $dbc->exec_statement($p, array($vid,$upc));
        $vinfo = $dbc->fetch_row($vinfo);
        $p = $dbc->prepare_statement("SELECT * FROM departments WHERE dept_no=?");
        $dinfo = $dbc->exec_statement($p,array($dept));
        $dinfo = $dbc->fetch_row($dinfo);
        
        $model = new ProductsModel($dbc);
        $model->upc(BarcodeLib::padUPC($upc));
        $model->description($vinfo['description']);
        $model->normal_price($price);
        $model->department($dept);
        $model->tax($dinfo['dept_tax']);
        $model->foodstamp($dinfo['dept_fs']);
        $model->cost($vinfo['cost']);
        $model->save();

        $xInsQ = $dbc->prepare_statement("INSERT INTO prodExtra (upc,distributor,manufacturer,cost,margin,variable_pricing,location,
                case_quantity,case_cost,case_info) VALUES
                (?,?,?,?,0.00,0,'','',0.00,'')");
        $args = array($upc,$vinfo['brand'],
                $vinfo['vendorName'],$vinfo['cost']);
        $dbc->exec_statement($xInsQ,$args);

        if ($tags !== -1) {
            $model = new ShelftagsModel($dbc);
            $model->id($tags);
            $model->upc($upc);
            $model->normal_price($price);
            $model->description($vinfo['description']);
            $model->brand($vinfo['brand']);
            $model->vendor($vinfo['vendorName']);
            $model->sku($vinfo['sku']);
            $model->size($vinfo['size']);
            $model->units($vinfo['units']);
            $model->pricePerUnit(PriceLib::pricePerUnit($price, $vinfo['size']));
            $model->save();
        }

        echo "Item added";
    }

    private function getSRP($cost,$margin){
        $srp = sprintf("%.2f",$cost/(1-$margin));
        while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" &&
               substr($srp,strlen($srp)-1,strlen($srp)) != "9")
            $srp += 0.01;
        return $srp;
    }

    function body_content(){
        global $FANNIE_OP_DB, $FANNIE_URL;
        $vid = FormLib::get_form_value('vid');
        if ($vid === ''){
            return "<i>Error: no vendor selected</i>";
        }

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $cats = "";
        $p = $dbc->prepare_statement("SELECT i.vendorDept, d.name 
                                      FROM vendorItems AS i
                                        LEFT JOIN vendorDepartments AS d
                                        ON i.vendorID=d.vendorID AND i.vendorDept=d.deptID
                                      WHERE i.vendorID=?
                                      GROUP BY i.vendorDept, d.name
                                      ORDER BY i.vendorDept");
        $rp = $dbc->exec_statement($p,array($vid));
        while($rw = $dbc->fetch_row($rp))
            $cats .= "<option value=$rw[0]>$rw[0] $rw[1]</option>";

        if ($cats =="") $cats = "<option value=\"\">Select a department...</option><option>All</option>";
        else $cats = "<option value=\"\">Select a department...</option>".$cats;

        ob_start();
        ?>
        <div id="categorydiv">
        <select id=categoryselect onchange="catchange();">
        <?php echo $cats ?>
        </select>
        &nbsp;&nbsp;&nbsp;
        <select id=brandselect onchange="brandchange();">
        <option>Select a department first...</option>
        </select>
        &nbsp;&nbsp;&nbsp;
        <select id="shelftags">
        <option value="-1">Shelf Tag Page</option>
        <?php
        $pages = $dbc->query('SELECT superID, super_name FROM MasterSuperDepts GROUP BY superID, super_name ORDER BY superID');
        while($row = $dbc->fetch_row($pages)) {
            printf('<option value="%d">%s</option>', $row['superID'], $row['super_name']);
        }
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
        <input type="hidden" id="vendorID" value="<?php echo $vid; ?>" />
        <input type="hidden" id="urlpath" value="<?php echo $FANNIE_URL; ?>" />
        <?php
        
        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
        $this->add_script('browse.js');

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

?>
