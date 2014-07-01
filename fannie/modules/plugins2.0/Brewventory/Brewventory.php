<?php
/*******************************************************************************

    Copyright 2012 Andy Theuninck

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

include(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
if (!class_exists('FannieInventory'))
    include(dirname(__FILE__).'/FannieInventory.php');

/**
  @class Brewventory
*/
class Brewventory extends FannieInventory {

    public $required = False;

    public $description = "
    Module for managing homebrew ingredients
    ";

    protected $header = 'BrewVentory';
    protected $title = 'BrewVentory';
    protected $window_dressing = False;

    protected $mode = 'menu';
    private $msgs = "";

    function init(){
        global $FANNIE_URL;
        $this->add_script($FANNIE_URL.'src/javascript/jquery.js');
        $this->add_script($FANNIE_URL.'src/javascript/jquery-ui.js');
        $this->add_css_file($FANNIE_URL."src/javascript/jquery-ui.css");

        ob_start();
        vprintf('
            <a href="%s">Home</a>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a href="%s&mode=view">View</a>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a href="%s&mode=receive">Add</a>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a href="%s&mode=sale">Use</a>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a href="%s&mode=adjust">Adjust</a>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <a href="%s&mode=import">Import</a>
            &nbsp;&nbsp;&nbsp;&nbsp;
            ',array_fill(0,6,'Brewventory.php'));
        return ob_get_clean();
    }

    function form_tag(){
        return '<form action="Brewventory.php">';
    }
    
    function module_url(){
        return 'Brewventory.php';
    }

    function adjust(){
        ob_start();
        echo $this->init();
        if (!empty($this->msgs)){
            echo '<blockquote><i>'.$this->msgs.'</i></blockquote>';
            $this->msgs = "";
        }
        echo $this->form_tag();
        ?>
        <p>
        <b>Ingredient</b>: <input type="text" id="upc" name="upc" />
        </p>
        <input type="submit" name="adjust_submit" value="Enter Adjustment" />
        </form>
        <?php
        $this->add_onload_command(
            sprintf("\$('#upc').autocomplete({source:'%s&LookUp=1'});",
                $this->module_url())
        );
        $this->add_onload_command("\$('#upc').focus();");

        echo '<p />';
        printf('<a href="%s&mode=menu">Home</a>',$this->module_url());
        return ob_get_clean();
    }

    function adjust_confirm(){
        global $FANNIE_OP_DB;
        $ret = $this->init();
        $ret .= $this->form_tag();

        $upc = array_pop(array_keys($this->msgs));
        $amt = $this->msgs[$upc];   

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $q = $dbc->prepare_statement("SELECT description FROM productUser WHERE upc=?");
        $r = $dbc->exec_statement($q,array($upc));
        $w = $dbc->fetch_row($r);
        $dbc->close();

        $ret .= '<h3>'.$w['description'].'</h3>';
        $ret .= sprintf('<input type="hidden" name="upc" value="%s" />',$upc);
        
        $ret .= '<p><b>Listed stock</b>: ';
        $pts = $this->kg_to_lb_oz($amt);
        $ret .= sprintf("%d lbs %.2f ozs",$pts['lbs'],$pts['ozs']);
        $ret .= '</p>';
        $ret .= sprintf('<input type="hidden" name="amt" value="%s" />',$amt);

        $ret .= '<p><b>Actual stock</b>: ';
        $ret .= '<input type="text" name="lbs" size="3" />lbs ';
        $ret .= '<input type="text" name="ozs" size="3" />ozs ';
        $ret .= '</p>';
        
        $ret .= '<input type="submit" name="adjust_confirm_submit" value="Confirm Adjustment" />';
        $ret .= '</form>';
    
        return $ret;
    }

    function import(){
        ob_start();
        echo $this->init();
        echo str_replace('form ', 'form enctype="multipart/form-data" ',$this->form_tag());
        ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
        BeerXML file: <input type="file" name="import_xml_file" />
        <input type="submit" name="import_submit" value="Import Data" />
        </form>
        <?php
        return ob_get_clean();
    }

    function sale(){
        ob_start();
        echo $this->init();
        echo str_replace('form ', 'form enctype="multipart/form-data" ',$this->form_tag());
        ?>
        <h3>Use Single Ingredient</h3>
        <p>
        <b>Ingredient</b>: <input type="text" id="upc" name="upc" />
        </p>
        <p>
        <b>lbs</b>: <input type="text" name="lbs" size="3" value="0" />
        <b>ozs</b>: <input type="text" name="ozs" size="3" value="0" />
        </p>
        <input type="submit" name="sale_submit" value="Use Ingredient" />
        <hr />
        <h3>Upload Recipe</h3>
        <input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
        BeerXML file: <input type="file" name="import_xml_file" />
        <input type="submit" name="sale_recipe_submit" value="Upload" />
        </form>
        <?php
        $this->add_onload_command(
            sprintf("\$('#upc').autocomplete({source:'%s&LookUp=1'});",
                $this->module_url())
        );
        return ob_get_clean();
    }

    function sale_confirm(){
        if (!is_array($this->msgs)){
            return "<b>Error</b>: no item(s) specified.";
        }

        $qty = $this->get_stock(array_keys($this->msgs));
        $name = $this->get_names(array_keys($this->msgs));

        $ret = $this->init();
        $ret .= $this->form_tag('post');
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>ID</th><th>Name</th><th>Amount</th><th>Decrease Inventory</th></tr>';
        foreach($this->msgs as $upc => $info){
            $start = '<tr>';
            $line = '<td>'.$upc.'</td>';
            $line .= sprintf('<input type="hidden" name="upc[]" value="%s" />',$upc);
            $line .= '<td>'.(isset($name[$upc])?$name[$upc]:$info['name']).'</td>';
            $line .= sprintf('<input type="hidden" name="amt[]" value="%f" />',$info['qty']);
            if (isset($info['yeast']))
                $line .= sprintf('<td>%d</td>',$info['qty']);
            else {
                $weight = $this->kg_to_lb_oz($info['qty']);
                $line .= sprintf('<td>%d lbs %.2f ozs</td>',$weight['lbs'],$weight['ozs']);
            }
            $line .= '<td><select name="decrement[]">';
            if (!isset($name[$upc])){
                $line .= '<option value="0">No. Ingredient unknown</option>';
                $start = '<tr style="color:red;">';
            }
            else if (!isset($qty[$upc]) || $qty[$upc] <= 0){
                $line .= '<option value="0">No. Ingredient not in stock</option>';
                $start = '<tr style="color:blue;">';
            }
            else if ($info['qty'] <= $qty[$upc]){
                $line .= '<option value="1">Yes. Ingredient in stock</option>';
                $line .= '<option value="0">No. Skip this ingredient.</option>';
                $start = '<tr style="color:green;">';
            }
            else {
                $line .= '<option value="2">Yes. Stop at zero.</option>';
                $line .= '<option value="1">Yes. Continue to negative amount.</option>';
                $line .= '<option value="0">No. Skip this ingredient.</option>';
                $start = '<tr style="color:green;">';
            }
            $line .= '</select></td>';
            $line .= '</tr>';
            $ret .= $start.$line;
        }
        $ret .= '</table>';
        $ret .= '<input type="submit" name="submit_sale_confirm" value="Confirm" />';
        $ret .= '</form>';
        return $ret;    
    }

    function get_stock($upcs=False){
        global $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);

        $where = "";
        $args = array();
        $ret = array();
        if ($upcs && is_array($upcs) && count($upcs)>0){
            $where = "WHERE d.upc IN (";
            foreach($upcs as $upc){
                $where .= '?,';
                $args[] = $upc;
            }
            $where = rtrim($where,",").")";
        }

        $upcQ = $dbc->prepare_statement("SELECT d.upc,
            SUM(d.quantity)
             - SUM(CASE WHEN s.quantity IS NULL THEN 0 ELSE s.quantity END) 
             - SUM(CASE WHEN a.diff IS NULL THEN 0 ELSE a.diff END) as stock
            FROM InvDeliveryArchive AS d
            LEFT JOIN InvSalesArchive AS s ON d.upc=s.upc
            LEFT JOIN InvAdjustments AS a ON d.upc=a.upc
            $where
            GROUP BY d.upc");
        $upcR = $dbc->exec_statement($upcQ,$args);
        while($upcW = $dbc->fetch_row($upcR)){
            $ret[$upcW['upc']] = $upcW['stock'];
        }
    
        $dbc->close();
        return $ret;
    }

    function get_names($upcs){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = array();
        $args = array();
        if ($upcs && is_array($upcs) && count($upcs)>0){
            $where = "WHERE upc IN (";
            foreach($upcs as $upc){
                $where .= '?,';
                $args[] = $upc;
            }
            $where = rtrim($where,",").")";
        }
        $q = $dbc->prepare_statement("SELECT upc,description FROM productUser ".$where);
        $r = $dbc->exec_statement($q,$args);
        while($w = $dbc->fetch_row($r)){
            $ret[$w['upc']] = $w['description'];    
        }
        $dbc->close();
        return $ret;
    }

    function view(){
        global $FANNIE_OP_DB;
        $qty = $this->get_stock();
        $upcs = "(";
        $args = array();
        foreach($qty as $upc=>$amt){
            $upcs .= '?,';
            $args[] = $upc;
        }
        $upcs = rtrim($upcs,",").")";

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $query = $dbc->prepare_statement("SELECT p.upc,p.mixmatchcode,u.*,x.*
            FROM products AS p LEFT JOIN
            productUser AS u ON p.upc=u.upc
            LEFT JOIN prodExtra AS x ON p.upc=x.upc
            WHERE p.mixmatchcode IN ('brewmisc','hops','malts','yeasts')
            AND p.upc IN $upcs ORDER BY u.description");
        $result = $dbc->exec_statement($query,$args);

        $rows = array('hops'=>array(),'brewmisc'=>array(),'malts'=>array(),'yeasts'=>array());
        while($row = $dbc->fetch_row($result))
            $rows[$row['mixmatchcode']][] = $row;
        $dbc->close();

        ob_start();
        echo $this->init();
        ?>

        <h3>Hops</h3>
        <table cellspacing="0" cellpadding="4" border="1">
        <tr>
            <th>Name</th>
            <th>Brand</th>      
            <th>Origin</th>
            <th>Type</th>       
            <th>Alpha</th>
            <th>Beta</th>
            <th>HSI</th>
            <th colspan="2">Current Supply</th>
        </tr>
        <?php
        foreach($rows['hops'] as $hops){
            $ttl = $qty[$hops['upc']] * 2.20462262;
            $lbs = floor($ttl);
            $ozs = ($ttl - $lbs) * 16;
            printf('<tr><td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%.2f%%</td>
                <td>%.2f%%</td>
                <td>%.2f%%</td>
                <td>%d lb</td>
                <td>%.2f oz</td></tr>',
                $hops['description'],
                (!empty($hops['brand'])?$hops['brand']:'&nbsp;'),
                (!empty($hops['distributor'])?$hops['distributor']:'&nbsp;'),
                (!empty($hops['sizing'])?$hops['sizing']:'&nbsp;'),
                (!empty($hops['cost'])?$hops['cost']:'&nbsp;'),
                (!empty($hops['margin'])?$hops['margin']:'&nbsp;'),
                (!empty($hops['case_cost'])?$hops['case_cost']:'&nbsp;'),
                $lbs, $ozs
            );
        }
        ?>
        </table>

        <h3>Fermentables</h3>
        <table cellspacing="0" cellpadding="4" border="1">
        <tr>
            <th>Name</th>
            <th>Brand</th>      
            <th>Origin</th>
            <th>SRM</th>        
            <th colspan="2">Current Supply</th>
        </tr>
        <?php
        foreach($rows['malts'] as $malts){
            $ttl = $qty[$malts['upc']] * 2.20462262;
            $lbs = floor($ttl);
            $ozs = ($ttl - $lbs) * 16;
            if (sprintf('%.2f',$ozs)=="16.00"){
                $ozs = 0; $lbs++;
            }
            printf('<tr><td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%.1f</td>
                <td>%d lb</td>
                <td>%.2f oz</td></tr>',
                $malts['description'],
                (!empty($malts['brand'])?$malts['brand']:'&nbsp;'),
                (!empty($malts['distributor'])?$malts['distributor']:'&nbsp;'),
                (!empty($malts['cost'])?$malts['cost']:'&nbsp;'),
                $lbs, $ozs
            );
        }
        ?>
        </table>

        <h3>Yeasts</h3>
        <table cellspacing="0" cellpadding="4" border="1">
        <tr>
            <th>Name</th>
            <th>Brand</th>      
            <th>Type</th>       
            <th>Form</th>       
            <th>Current Supply</th>
        </tr>
        <?php
        foreach($rows['yeasts'] as $yeasts){
            $ttl = round($qty[$yeasts['upc']] * 2.20462262);
            printf('<tr><td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%d</td></tr>',
                $yeasts['description']
                .(!empty($yeasts['case_info'])?' ('.$yeasts['case_info'].')':''),
                (!empty($yeasts['brand'])?$yeasts['brand']:'&nbsp;'),
                (!empty($yeasts['distributor'])?$yeasts['distributor']:'&nbsp;'),
                (!empty($yeasts['sizing'])?$hops['sizing']:'&nbsp;'),
                $ttl
            );
        }
        ?>
        </table>

        <h3>Misc.</h3>
        <table cellspacing="0" cellpadding="4" border="1">
        <tr>
            <th>Name</th>
            <th>Brand</th>      
            <th>Type</th>
            <th colspan="2">Current Supply</th>
        </tr>
        <?php
        foreach($rows['brewmisc'] as $misc){
            $ttl = $qty[$misc['upc']] * 2.20462262;
            $lbs = floor($ttl);
            $ozs = ($ttl - $lbs) * 16;
            if (sprintf('%.2f',$ozs)=="16.00"){
                $ozs = 0; $lbs++;
            }
            printf('<tr><td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%d lb</td>
                <td>%.2f oz</td></tr>',
                $misc['description'],
                (!empty($misc['brand'])?$misc['brand']:'&nbsp;'),
                (!empty($misc['sizing'])?$misc['sizing']:'&nbsp;'),
                $lbs, $ozs
            );
        }
        ?>
        </table>

        <?php
        echo '<p />';
        printf('<a href="%s&mode=menu">Home</a>',$this->module_url());
        return ob_get_clean();
    }

    function receive(){
        ob_start();
        echo $this->init();
        if (!empty($this->msgs)){
            echo '<blockquote><i>'.$this->msgs.'</i></blockquote>';
            $this->msgs = "";
        }
        echo $this->form_tag();
        ?>
        <p>
        <b>Ingredient</b>: <input type="text" id="upc" name="upc" />
        </p>
        <p>
        <b>lbs</b>: <input type="text" name="lbs" size="3" value="0" />
        <b>ozs</b>: <input type="text" name="ozs" size="3" value="0" />
        </p>
        <input type="submit" name="receive_submit" value="Add to Stock" />
        </form>
        <?php
        $this->add_onload_command(
            sprintf("\$('#upc').autocomplete({source:'%s&LookUp=1'});",
                $this->module_url())
        );
        $this->add_onload_command("\$('#upc').focus();");

        echo '<p />';
        printf('<a href="%s&mode=menu">Home</a>',$this->module_url());
        return ob_get_clean();
    }

    function menu(){
        $ret = '<h3>BrewVentory</h3>';
        $ret .= '<ul>';
        $ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
                $this->module_url(),'view','View Current Inventory');
        $ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
                $this->module_url(),'receive','Add Purchases');
        $ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
                $this->module_url(),'sale','Use Ingredients');
        $ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
                $this->module_url(),'adjust','Enter Adjustments');
        $ret .= sprintf('<li><a href="%s&mode=%s">%s</a>',
                $this->module_url(),'import','Import Ingredient Definitions');
        $ret .= '</ul>';
        return $ret;
    }

    function kg_to_lb_oz($kgs){
        $ttl = $kgs * 2.20462262;
        $lbs = floor($ttl);
        $ozs = ($ttl - $lbs) * 16;
        if (sprintf('%.2f',$ozs)=='16.00'){
            $lbs += 1;
            $ozs -= 16.00;
            if ($ozs < 0) $ozs=0.0;
        }
        return array('lbs'=>$lbs,'ozs'=>$ozs);
    }
    
    function lb_oz_to_kg($lbs,$ozs){
        $ttl = $lbs + ($ozs/16.0);
        $kgs = $ttl * 0.45359237;
        return $kgs;
    }

    function preprocess(){
        global $FANNIE_TRANS_DB, $FANNIE_OP_DB;
        $this->mode = FormLib::get_form_value('mode','menu');

        /**
          Begin form callbacks
        */

        /**
          Callback for import() display function
        */
        if (isset($_REQUEST['import_submit'])){
            $tmpfile = $_FILES['import_xml_file']['tmp_name'];
            $filename = tempnam(sys_get_temp_dir(),'brewvenImport');
            move_uploaded_file($tmpfile, $filename);

            $bxml = new BeerXMLParser($filename);
            $data = $bxml->get_data();
        
            $dbc = FannieDB::get($FANNIE_OP_DB);
            foreach($data['Hops'] as $h)
                echo $this->add_hops($dbc, $h)."<br />";

            foreach($data['Fermentables'] as $f)
                echo $this->add_malt($dbc, $f)."<br />";

            foreach($data['Yeast'] as $y)
                echo $this->add_yeast($dbc, $y)."<br />";

            foreach($data['Misc'] as $m)
                echo $this->add_misc($dbc, $m)."<br />";

            echo '<p />';
            printf('<a href="%s&mode=menu">Home</a>',$this->module_url());
            unlink($filename);
            return False;
        }

        /**
          Callback #1 for sale() display function
          Process uploaded file
        */
        if (isset($_REQUEST['sale_recipe_submit'])){
            $tmpfile = $_FILES['import_xml_file']['tmp_name'];
            $filename = tempnam(sys_get_temp_dir(),'brewvenImport');
            move_uploaded_file($tmpfile, $filename);

            $bxml = new BeerXMLParser($filename);
            $data = $bxml->get_data();

            $this->msgs = array();
        
            foreach($data['Hops'] as $h){
                $upc = $this->hash('hops',$h);
                if (!isset($this->msgs[$upc]))
                    $this->msgs[$upc] = array('qty'=>0.0,'name'=>$h['name']);
                $this->msgs[$upc]['qty'] += (isset($h['amount'])?$h['amount']:0.0);
            }

            foreach($data['Fermentables'] as $f){
                $upc = $this->hash('malt',$f);
                if (!isset($this->msgs[$upc]))
                    $this->msgs[$upc] = array('qty'=>0.0,'name'=>$f['name']);
                $this->msgs[$upc]['qty'] += (isset($f['amount'])?$f['amount']:0.0);
            }

            foreach($data['Yeast'] as $y){
                $upc = $this->hash('yeast',$y);
                if (!isset($this->msgs[$upc]))
                    $this->msgs[$upc] = array('qty'=>0.0,'name'=>$y['name']);
                $this->msgs[$upc]['qty'] += 1;
                $this->msgs[$upc]['yeast'] = True;
            }

            foreach($data['Misc'] as $m){
                $upc = $this->hash('misc',$m);
                if (!isset($this->msgs[$upc]))
                    $this->msgs[$upc] = array('qty'=>0.0,'name'=>$m['name']);
                $this->msgs[$upc]['qty'] += (isset($m['amount'])?$m['amount']:0.0);
            }

            $this->mode = "sale_confirm";

            unlink($filename);
            return True;
        }

        /**
          Callback #2 for sale() display function
          Process single ingredient
        */
        if (isset($_REQUEST['sale_submit'])){
            $upc = FormLib::get_form_value('upc','');
            $lbs = FormLib::get_form_value('lbs',0.0);
            $ozs = FormLib::get_form_value('ozs',0.0);
            $kgs = $this->lb_oz_to_kg($lbs,$ozs);
            
            $this->msgs = array($upc=>array('qty'=>$kgs,'name'=>''));
            $this->mode = "sale_confirm";
            return True;
        }

        /**
          Callback for sale_confirm() display function
          Process single ingredient
        */
        if (isset($_REQUEST['submit_sale_confirm'])){
            $upcs = FormLib::get_form_value('upc',array()); 
            $amts = FormLib::get_form_value('amt',array());
            $confirms = FormLib::get_form_value('decrement',array());

            $dbc = FannieDB::get($FANNIE_TRANS_DB);
            $p = $dbc->prepare_statement("INSERT INTO InvSalesArchive (inv_date, upc, quantity, price)
                VALUES (".$dbc->now().", ?, ?, 0.0)");
            for($i=0;$i<count($confirms);$i++){
                if ($confirms[$i] == 0) continue; // skip

                $upc = $upcs[$i];
                $amt = $amts[$i];
                if ($confirms[$i] == 2){ // stop at zero
                    $stock = $this->get_stock(array($upc));
                    if (isset($stock[$upc])) $amt = $stock[$upc];
                    else continue; // couldn't find current stock   
                }

                $r = $dbc->exec_statement($p, array($upc,$amt));
                $dbc->close();
            }

            $this->mode = 'view';
            return True;
        }

        /**
          Callback for receive() display function
        */
        if (isset($_REQUEST['receive_submit'])){
            $upc = FormLib::get_form_value('upc','');
            $lbs = FormLib::get_form_value('lbs',0.0);
            $ozs = FormLib::get_form_value('ozs',0.0);
            $ttl = $lbs + ($ozs/16.0);

            $kgs = $ttl * 0.45359237;

            $dbc = FannieDB::get($FANNIE_OP_DB);
            $query = $dbc->prepare_statement("SELECT description FROM productUser
                WHERE upc=?");
            $result = $dbc->exec_statement($query,array($upc));
            if ($dbc->num_rows($result)==0){
                $this->msgs = "Product not found";
            }
            else {
                $item = array_pop($dbc->fetch_row($result));
                $dbc->close();
                $dbc = FannieDB::get($FANNIE_TRANS_DB);
                $insQ = $dbc->prepare_statement("INSERT INTO InvDeliveryArchive
                    (inv_date, upc, vendor_id, quantity, price)
                    VALUES (".$dbc->now().", ?, 0, ?, 0.00)");
                $add = $dbc->exec_statement($insQ,array($upc,$kgs));
                if ($add)
                    $this->msgs = "Added $item to inventory";
                else
                    $this->msgs = "Error adding product: ".$item;
            }
            $dbc->close();

            $this->mode = 'receive';
            return True;
        }

        /**
          Callback for adjust_confirm() display function
        */
        if (isset($_REQUEST['adjust_confirm_submit'])){
            $upc = FormLib::get_form_value('upc','');
            $lbs = FormLib::get_form_value('lbs',0.0);
            $ozs = FormLib::get_form_value('ozs',0.0);
            $amt = FormLib::get_form_value('amt',0.0);

            $newamt = $this->lb_oz_to_kg($lbs,$ozs);
            $diff = $amt - $newamt;

            $dbc = FannieDB::get($FANNIE_TRANS_DB);
            $q = $dbc->prepare_statement("INSERT INTO InvAdjustments (inv_date,upc,diff)
                VALUES(".$dbc->now().", ?, ?)");
            $r = $dbc->exec_statement($q,array($upc,$diff));
            $dbc->close();

            $this->msgs = 'Adjustment saved';
            $this->mode = 'adjust';
            
            return True;
        }

        /**
          Callback for adjust() display function
        */
        if (isset($_REQUEST['adjust_submit'])){
            $upc = FormLib::get_form_value('upc','');

            $current = $this->get_stock(array($upc));
            if(!isset($current[$upc])){
                $this->msgs = 'Item not in stock';
                $this->mode = 'adjust';
            }
            else {
                $this->msgs = $current;
                $this->mode = 'adjust_confirm';
            }

            return True;
        }

        /**
          jQuery autocomplete callback
        */
        if (isset($_REQUEST['LookUp']) && isset($_REQUEST['term'])){
            $dbc = FannieDB::get($FANNIE_OP_DB);
            $query = $dbc->prepare_statement("SELECT p.upc,u.description,u.brand,u.sizing,
                    x.distributor FROM products AS p
                    INNER JOIN productUser as u ON p.upc=u.upc
                    INNER JOIN prodExtra AS x ON p.upc=x.upc
                    WHERE p.mixmatchcode IN ('hops','malts','yeasts','brewmisc')
                    AND u.description LIKE ?");

            $json = "[";
            $result = $dbc->exec_statement($query,array('%'.$_REQUEST['term'].'%'));
            while($row = $dbc->fetch_row($result)){
                $json .= "{ \"label\": \"".$row['description'];

                if (!empty($row['brand']) || !empty($row['sizing']) || !empty($row['distributor']))
                    $json .= " ("; 
                if (!empty($row['brand']))
                    $json .= $row['brand'].", ";
                if (!empty($row['distributor']))
                    $json .= $row['distributor'].", ";
                if (!empty($row['sizing']))
                    $json .= $row['sizing'].", ";
                $json = rtrim($json,", ");
                if (!empty($row['brand']) || !empty($row['sizing']) || !empty($row['distributor']))
                    $json .= ")"; 

                $json .= "\", \"value\": \"".$row['upc']."\"},";
            }
            $json = rtrim($json,",");
            $json .= "]";

            header("Content-type: application/json");
            echo $json;
            return False;
        }

        /**
          End form callbacks
        */
    

        return True;
    }

    /**
      Hasing function for pseudo-UPCs
      @param $type ingredient type
      @param $fields BeerXML array
      @return unique(ish) 13 character hash
    */
    private function hash($type, $fields){
        $hash = $fields['name'];
        switch(strtolower($type)){
        case 'malt':
        case 'malts':
            $hash .= (isset($fields['supplier'])?$fields['supplier']:'');
            $hash .= (isset($fields['origin'])?$fields['origin']:'');
            break;
        case 'misc':
        case 'brewmisc':
            $hash .= (isset($fields['supplier'])?$fields['supplier']:'');
            $hash .= (isset($fields['type'])?$fields['type']:'');
            break;
        case 'yeast':
        case 'yeasts':
            $hash .= (isset($fields['laboratory'])?$fields['laboratory']:'');
            $hash .= (isset($fields['product_id'])?$fields['product_id']:'');
            break;
        case 'hop':
        case 'hops':
            $hash .= (isset($fields['origin'])?$fields['origin']:'');
            $hash .= (isset($fields['form'])?$fields['form']:'');
            break;
        }
        return substr(md5($hash),0,13);
    }

    /**
      Add malts to product database
      @param $dbc SQLManager object
      @param $malt_info array of BeerXML fields
      @return string describing result
    */
    private function add_malt($dbc, $malt_info){
        $good_desc = $malt_info['name'];
        $short_desc = substr($malt_info['name'],0,30);
        $upc = $this->hash('malt', $malt_info);

        $q = $dbc->prepare_statement("SELECT upc FROM products WHERE upc=?");
        $r = $dbc->exec_statement($q,array($upc));
        if ($dbc->num_rows($r) > 0)
            return "<i>Omitting malt: $good_desc (already exists)</i>";

        $userQ = $dbc->prepare_statement("INSERT INTO productUser
            (upc, description, brand, sizing, photo,
            long_text, enableOnline) VALUES
            (?, ?, ?, '', '', ?, 0)");

        $delP = $dbc->prepare_statement("DELETE FROM productUser WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($userQ,array(
            $upc, $good_desc,
            (isset($malt_info['supplier'])?$malt_info['supplier']:''),
            (isset($malt_info['notes'])?$malt_info['notes']:'')
        ));

        $xtraQ = $dbc->prepare_statement("INSERT INTO prodExtra (upc, distributor, 
            manufacturer, cost, margin, variable_pricing, location,
            case_quantity, case_cost, case_info) VALUES
            (?, ?, '', ?, 0, 0, '', '', 0, '')");

        $delP = $dbc->prepare_statement("DELETE FROM prodExtra WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($xtraQ,array(
            $upc,
            (isset($malt_info['origin'])?$malt_info['origin']:''),
            (isset($malt_info['color'])?$malt_info['color']:0)
        ));

        $prodQ = $dbc->prepare_statement("INSERT INTO products (upc, description, modified, mixmatchcode) VALUES
                (?, ?, ".$dbc->now().", 'malts')");

        $delP = $dbc->prepare_statement("DELETE FROM products WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($prodQ,array($upc,$short_desc));

        return "Imported malt: $good_desc";
    }

    /**
      Add misc ingredients to product database
      @param $dbc SQLManager object
      @param $misc_info array of BeerXML fields
      @return string describing result
    */
    private function add_misc($dbc, $misc_info){
        $good_desc = $misc_info['name'];
        $short_desc = substr($misc_info['name'],0,30);
        $upc = $this->hash('misc', $misc_info);

        $q = $dbc->prepare_statement("SELECT upc FROM products WHERE upc=?");
        $r = $dbc->exec_statement($q,array($upc));
        if ($dbc->num_rows($r) > 0)
            return "<i>Omitting misc: $good_desc (already exists)</i>";

        $userQ = $dbc->prepare_statement("INSERT INTO productUser
            (upc, description, brand, sizing, photo,
            long_text, enableOnline) VALUES
            (?, ?, ?, ?, '', ?, 0)");

        $delP = $dbc->prepare_statement("DELETE FROM productUser WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($userQ,array(
            $upc, $good_desc,
            (isset($misc_info['supplier'])?$misc_info['supplier']:''),
            (isset($misc_info['type'])?$misc_info['type']:''),
            (isset($misc_info['notes'])?$misc_info['notes']:'')
        ));

        $xtraQ = $dbc->prepare_statement("INSERT INTO prodExtra (upc, distributor, 
            manufacturer, cost, margin, variable_pricing, location,
            case_quantity, case_cost, case_info) VALUES
            (?, '', '', 0, 0, 0, '', '', 0, '')");

        $delP = $dbc->prepare_statement("DELETE FROM prodExtra WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($xtraQ,array($upc));

        $prodQ = $dbc->prepare_statement("INSERT INTO products (upc, description, modified, mixmatchcode) VALUES
                (?, ?, ".$dbc->now().", 'brewmisc')");

        $delP = $dbc->prepare_statement("DELETE FROM products WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($prodQ,array($upc,$short_desc));

        return "Imported misc: $good_desc";
    }

    /**
      Add yeast to product database
      @param $dbc SQLManager object
      @param $yeast_info array of BeerXML fields
      @return string describing result
    */
    private function add_yeast($dbc, $yeast_info){
        $good_desc = $yeast_info['name'];
        $short_desc = substr($yeast_info['name'],0,30);
        $upc = $this->hash('yeast', $yeast_info);

        $q = $dbc->prepare_statement("SELECT upc FROM products WHERE upc=?");
        $r = $dbc->exec_statement($q,array($upc));
        if ($dbc->num_rows($r) > 0)
            return "<i>Omitting yeast: $good_desc (already exists)</i>";

        $userQ = $dbc->prepare_statement("INSERT INTO productUser
            (upc, description, brand, sizing, photo,
            long_text, enableOnline) VALUES
            (?, ?, ?, ?, '', ?, 0)");
        $delP = $dbc->prepare_statement("DELETE FROM productUser WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($userQ,array(
            $upc, $good_desc,
            (isset($yeast_info['laboratory'])?$yeast_info['laboratory']:''),
            (isset($yeast_info['form'])?$yeast_info['form']:''),
            (isset($yeast_info['notes'])?$yeast_info['notes']:'')
        ));

        $xtraQ = $dbc->prepare_statement("INSERT INTO prodExtra (upc, distributor, 
            manufacturer, cost, margin, variable_pricing, location,
            case_quantity, case_cost, case_info) VALUES
            (?, ?, '', 0, 0, 0, '', '', 0, ?)");
        $delP = $dbc->prepare_statement("DELETE FROM prodExtra WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($xtraQ,array(
            $upc,
            (isset($yeast_info['type'])?$yeast_info['type']:''),
            (isset($yeast_info['product_id'])?$yeast_info['product_id']:'')
        ));

        $prodQ = $dbc->prepare_statement("INSERT INTO products (upc, description, modified, mixmatchcode) VALUES
                (?, ?, ".$dbc->now().", 'yeasts')");

        $delP = $dbc->prepare_statement("DELETE FROM products WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($prodQ,array($upc,$short_desc));

        return "Imported yeast: $good_desc";
    }

    /**
      Add hops to product database
      @param $dbc SQLManager object
      @param $hop_info array of BeerXML fields
      @return string describing result
    */
    private function add_hops($dbc, $hop_info){
        $good_desc = $hop_info['name'];
        $short_desc = substr($hop_info['name'],0,30);
        $upc = $this->hash('hops', $hop_info);

        $q = $dbc->prepare_statement("SELECT upc FROM products WHERE upc=?");
        $r = $dbc->exec_statement($q,array($upc));
        if ($dbc->num_rows($r) > 0)
            return "<i>Omitting hops: $good_desc (already exists)</i>";
        
        $userQ = $dbc->prepare_statement("INSERT INTO productUser
            (upc, description, brand, sizing, photo,
            long_text, enableOnline) VALUES
            (?, ?, ?, ?, '', ?, 0)");
        $delP = $dbc->prepare_statement("DELETE FROM prodExtra WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($userQ,array(
            $upc,$good_desc,
            (isset($hop_info['supplier'])?$hop_info['supplier']:''),
            (isset($hop_info['form'])?$hop_info['form']:''),
            (isset($hop_info['notes'])?$hop_info['notes']:'')
        ));

        $xtraQ = $dbc->prepare_statement("INSERT INTO prodExtra (upc, distributor, 
            manufacturer, cost, margin, variable_pricing, location,
            case_quantity, case_cost, case_info) VALUES
            (?, ?, '', ?, ?, 0, '', '', ?, '')");
        $delP = $dbc->prepare_statement("DELETE FROM prodExtra WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($xtraQ,array(
            $upc,
            (isset($hop_info['origin'])?$hop_info['origin']:''),
            (isset($hop_info['alpha'])?$hop_info['alpha']:0),
            (isset($hop_info['beta'])?$hop_info['beta']:0),
            (isset($hop_info['hsi'])?$hop_info['hsi']:0)
        ));

        $prodQ = $dbc->prepare_statement("INSERT INTO products (upc, description, modified, mixmatchcode) VALUES
                (?, ?, ".$dbc->now().", 'hops')");

        $delP = $dbc->prepare_statement("DELETE FROM products WHERE upc=?");
        $dbc->exec_statement($delP,array($upc));
        $dbc->exec_statement($prodQ,array($upc,$short_desc));

        return "Imported hops: $good_desc";
    }

    function css_content(){
        global $FANNIE_URL;
        ob_start();
        ?>
        a { color:blue; }
        body {
            background-image: url(<?php echo $FANNIE_URL.'modules/plugins/Brewventory/bg.jpg'; ?>);
            background-position: right top;
            background-repeat: no-repeat;
        }
        <?php
        return ob_get_clean();

    }
}

/**
  @class BeerXMLParser
  Class to read BeerXML files
*/
class BeerXMLParser {
    
    private $data = array('Hops'=>array(),
            'Fermentables'=>array(),
            'Yeast'=>array(),
            'Misc'=>array()
    );

    private $hop;
    private $ferm;
    private $yeast;
    private $misc;
    private $outer_element = "";
    private $current_element = array();

    public function BeerXMLParser($filename){
        $file = file_get_contents($filename);
        if (!$file) $this->data = False;
        else {
            // beersmith bad data correction
                        $file = str_replace(chr(0x01),"",$file);

            $xml_parser = xml_parser_create();
            xml_set_object($xml_parser,$this);
            xml_set_element_handler($xml_parser, "startElement", "endElement");
            xml_set_character_data_handler($xml_parser, "charData");
            xml_parse($xml_parser, $file, True);
            xml_parser_free($xml_parser);
        }
    }

    public function get_data(){
        return $this->data;
    }

    private function startElement($parser,$name,$attrs){
        switch(strtolower($name)){
        case 'hop':
            $this->outer_element = "hop";
            $this->hop = array();
            break;
        case 'fermentable':
            $this->outer_element = "fermentable";
            $this->ferm = array();
            break;
        case 'yeast':
            $this->outer_element = "yeast";
            $this->yeast = array();
            break;
        case 'misc':
            $this->outer_element = "misc";
            $this->misc = array();
            break;
        }
        array_unshift($this->current_element,strtolower($name));
    }

    private function endElement($parser,$name){
        switch(strtolower($name)){
        case 'hop':
            $this->data['Hops'][] = $this->hop;
            break;
        case 'fermentable':
            $this->data['Fermentables'][] = $this->ferm;
            break;
        case 'yeast':
            $this->data['Yeast'][] = $this->yeast;
            break;
        case 'misc':
            $this->data['Misc'][] = $this->misc;
            break;
        }
        array_shift($this->current_element);
    }

    private function charData($parser,$data){
        switch($this->outer_element){
        case 'hop':
            if (!isset($this->hop[$this->current_element[0]]))
                $this->hop[$this->current_element[0]] = "";
            $this->hop[$this->current_element[0]] .= $data;
            break;
        case 'fermentable':
            if (!isset($this->ferm[$this->current_element[0]]))
                $this->ferm[$this->current_element[0]] = "";
            $this->ferm[$this->current_element[0]] .= $data;
            break;
        case 'yeast':
            if (!isset($this->yeast[$this->current_element[0]]))
                $this->yeast[$this->current_element[0]] = "";
            $this->yeast[$this->current_element[0]] .= $data;
            break;
        case 'misc':
            if (!isset($this->misc[$this->current_element[0]]))
                $this->misc[$this->current_element[0]] = "";
            $this->misc[$this->current_element[0]] .= $data;
            break;
        }
    }

}

FannieDispatch::conditionalExec(false);

?>
