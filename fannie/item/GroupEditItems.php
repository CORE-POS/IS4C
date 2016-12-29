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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class GroupEditItems extends FanniePage
{
    protected $header = 'Edit Search Results';
    protected $title = 'Edit Search Results';

    public $description = '[Edit Search Results] takes a set of advanced search items and allows
    editing some fields on all items simultaneously. Must be accessed via Advanced Search.';
    public $themed = true;

    private $upcs = array();
    private $save_results = array();


    
    
    function body_content()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $ret = '';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $ret .= $this->dynamic_form($dbc);
        
        return $ret;
        
    }
    
    private function dynamic_form($dbc)
    {
        
        $ret = '';
        //$upc = FormLib::get('upc');
        
        $inputs = array('upc','description','brand','cost','price','department','size','tax','foodstamp','scale','wicable','inUse',
            'sign_description','sign_brand','nutrition_facts','sign_count');
        $curInputs = array();
        foreach ($_POST as $k => $v) {
            //${$k} = FormLib::get(''.$k.'');
            $curInputs[] = $k;
        }
        if ($_POST['upcs']) {
            $upcs = $_POST['upcs'];
            $plus = array();
            $chunks = explode("\r\n", $upcs);
            foreach ($chunks as $key => $str) {
                $plus[] = str_pad($str,13,'0',STR_PAD_LEFT);
            }
        }
        
        /*  For Testing
        foreach ($curInputs as $v) {
            $ret .= $v . '<br>';
        }
        */
        
        $lgPipe = '<span style="color: lightgrey"> | </span>';
        
        $ret .= '
            <form class="form-inline" method="post">
                <div class="panel panel-default" style="">
                    <div class="panel-heading">Select Columns to View/Edit</div>
                    <div style="padding: 5px;">
                        <textarea class="form-control" rows="1" name="upcs" 
                            placeholder="Paste UPCs to Edit">';
        if ($_POST['upcs']) {
            foreach ($plus as $upc) {
                $ret .= $upc . "\r\n";
            }
        }
        $ret.='         </textarea><br /><br />
        ';
        $i = 1;
        /*
        foreach ($inputs as $input) {
            $ret .= '   <div style="height: 50px;padding: 5px; border-radius: 5px; float: left"><input type="checkbox" class="form-control" value="1" name="'.$input.'" ';
            if ($_POST[$input]) $ret .= ' checked ';
            $ret .= '> '.ucwords($input).'</input></div>';
            if ($i != count($inputs)) {
                //$ret .= $lgPipe;
            }
            $i++;
        }
        */
        
        /*
        <div class="'.$curClass.'" style="float: left; width: 90px; " id="sel-col-'.$i.'">
                <select class="form-control" name="column'.$i.'">
                    <option>none</option>
                </select>
            </div>
        */
        
        $ret .= '<input type="hidden" name="cols" id="cols" value=1>
            Colums <br />';
        for ($i=0; $i<11; $i++) {
            $curClass = '';
            if ($i > 0) $curClass = "collapse";
            $ret .= '
            <div class="'.$curClass.'" style="float: left; width: 185px; " id="sel-col-'.$i.'">
                <select class="form-control" name="column'.$i.'">
                    ';
            $j=0;
            foreach ($inputs as $input) {
                $ret .= '<option value='.$i.'>'.ucwords($input).'</option>';
                $j++;
            }
            $ret .= '
                </select>
            </div>
            ';
        }
        $ret .= '<button class="btn btn-default btn-xs" href="" onclick="showNextBtn(); return false;">+</a></button><br />';
        

        
        $ret .= '
                        <br />
                        <div class="input-group input-group-sm">
                            <span class="input-group-addon">Store(s) to Update</span>
                            <select name="storesToUpdate" class="form-control">
                                <option value="0" ';
        if ($_POST['storesToUpdate'] == 0) $ret .= ' selected ';
        $ret .= '>Both</option>
                                <option value="1" ';
        if ($_POST['storesToUpdate'] == 1) $ret .= ' selected ';
        $ret .= '>Hillside Only</option>
                                <option value="2" ';
        if ($_POST['storesToUpdate'] == 2) $ret .= ' selected ';
        $ret .= '>Denfeld Only</option>
                            </select>
                        </div>
                        <button id="clear" onClick="window.location.href=window.location.href; return false;" class="btn btn-default">Clear Settings</button>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form> 
        ';
        
        list($in_clause,$args) = $dbc->safeInClause($plus);
        $query_string = '
            SELECT 
                p.upc,
                p.description,
                p.brand,
                p.cost,
                p.normal_price AS price,
                u.description AS sign_description,
                u.brand AS sign_brand,
                u.nutritionFacts AS nutrition_facts,
                u.signCount AS sign_count,
                p.department, p.size, p.tax, p.foodstamp, p.scale, p.wicable, p.inUse
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
            WHERE p.upc IN ('.$in_clause.')
            GROUP BY p.upc';
        $prepare = $dbc->prepare($query_string);
        $result = $dbc->execute($prepare,$args); 
        
        $ret .= '<table class="table table-striped table-bordered ">';
        $headers = array();
        
        $ret .= '<table class="table table-default">';
        $ret .= '<form name="update" method="post">';
        $ret .= '<thead>';
        foreach ($curInputs as $v) {
            if ($v != 'upcs' && $v != 'storesToUpdate') {
                $ret .= '<th>'.ucwords($v).'</th>';
            } 
        }
        $ret .= '</thead>';
        while ($row = $dbc->fetch_row($result)) {
            
            $ret .= '<tr>';
            
            /*
            foreach ($curInputs as $v) {
                $ret .= '<td><input type="text" name="'.$v.'[]" class="'.$v.'Field form-control input-sm" value="' . $row[$v] . '"></td>';
            }
            */
            
            foreach ($curInputs as $v) {
                if ($v == 'upc') {
                    $ret .= '<td><a href="ItemEditorPage.php?searchupc='.$row[$v].'" target="_BLANK">' . $row[$v] . '</a></td>
                        <input type="hidden" class="upcInput" name="upc[]" value="'.$row[$v].'" />';
                } elseif ($v =='upcs' || $v == 'storesToUpdate') {
                    //  Don't create row for list of upcs or stores to update.
                } else {
                    $ret .= '<td><input type="text" name="'.$v.'[]" class="'.$v.'Field form-control input-sm" value="' . $row[$v] . '"></td>';
                }
            }
            
            $ret .= '</tr>';
            
            /*
            $ret .= sprintf('<tr>
                            <td>
                                <a href="ItemEditorPage.php?searchupc=%s" target="_edit%s">%s</a>
                                <input type="hidden" class="upcInput" name="upc[]" value="%s" />
                            </td>
                            <td>%s</td>
                            <td><input type="text" name="brand[]" class="brandField form-control input-sm" value="%s" /></td>
                            <td><input type="text" name="vendor[]" class="vendorField form-control input-sm" value="%s" /></td>
                            <td><select name="dept[]" class="deptSelect form-control input-sm">%s</select></td>
                            <td><select name="tax[]" class="taxSelect form-control input-sm">%s</select></td>
                            <td><input type="checkbox" name="fs[]" class="fsCheckBox" value="%s" %s /></td>
                            <td><input type="checkbox" name="scale[]" class="scaleCheckBox" value="%s" %s /></td>
                            <td><select class="form-control input-sm discSelect" name="discount[]">%s</select></td>
                            <td><select name="local[]" class="localSelect form-control input-sm">%s</select></td>
                            <td><input type="checkbox" name="inUse[]" class="inUseCheckBox" value="%s" %s /></td>
                            </tr>',
                            $row['upc'], $row['upc'], $row['upc'],
                            $row['upc'],
                            $row['description'],
                            $row['manufacturer'],
                            $row['distributor'],
                            $deptOpts,
                            $taxOpts,
                            $row['upc'], ($row['foodstamp'] == 1 ? 'checked' : ''),
                            $row['upc'], ($row['scale'] == 1 ? 'checked' : ''),
                            $this->discountOpts($row['discount'], $row['line_item_discountable']),
                            $localOpts,
                            $row['upc'], ($row['inUse'] == 1 ? 'checked' : '')
            );
        }
        $ret .= '</table>';

        $ret .= '<p>';
        $ret .= '<button type="submit" name="save" class="btn btn-default" value="1">Save Changes</button>';
        $ret .= '</form>';
        */
        }
        if ($dbc->error()) $ret .= '<span class="text-danger">' . $dbc->error() . '</span>';;
        $ret .= '<tr><input type="hidden" name="update" value="1">
            <td><button type="submit" class="btn btn-danger">Save</button></td></tr>';
        $ret .= '</table></form>';

        return $ret;
    }

   

    function javascript_content()
    {
        ob_start();
        ?>
function toggleAll(elem, selector) {
    if (elem.checked) {
        $(selector).prop('checked', true);
    } else {
        $(selector).prop('checked', false);
    }
}
function updateAll(val, selector) {
    $(selector).val(val);
}
function showNextBtn()
{
    var x = document.getElementById('cols').value; 
    $('#sel-col-'+x).show(); 
    document.getElementById('cols').value++; 
}
        <?php
        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>
            
            </p>';
    }
    
   
}

FannieDispatch::conditionalExec();

