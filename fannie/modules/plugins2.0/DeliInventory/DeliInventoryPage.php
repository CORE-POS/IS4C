<?php

if (basename($_SERVER['PHP_SELF']) != basename(__FILE__)) {
    return;
}

include(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class DeliInventoryPage extends FanniePage
{

    protected $window_dressing = false;

    public function preprocess()
    {
        global $FANNIE_OP_DB;
        $sql = FannieDB::get($FANNIE_OP_DB);

/* ajax responses 
 * $out is the output sent back
 * by convention, the request name ($_GET['action'])
 * is prepended to all output so the javascript receiver
 * can handle responses differently as needed.
 * a backtick separates request name from data
 */
$out = '';
if (isset($_GET['action'])){
    // prepend request name & backtick
    $out = $_GET['action']."`";
    // switch on request name
    switch ($_GET['action']){
    case 'additem':
        $item = $_GET['item'];
        $orderno = $_GET['orderno'];
        $units = $this->strim($_GET['units']);
        $price = $_GET['price'];
        $price = preg_replace("/,/",".",$price);
        $size = $_GET['size'];
        $cases = $this->strim($_GET['cases']);
        $fraction = $this->strim($_GET['fraction']);
        $category = $this->strim($_GET['category']);
        $category = preg_replace("/_/"," ",$category);

        if (empty($price))
            $price = 0;
        if (empty($cases))
            $cases = 0;
        if (empty($fraction))
            $fraction = 0;
        
        $stocktotal = 0;
        if (!empty($units)){
            if ($units[strlen($units)-1] == '#' && 
                $fraction[strlen($fraction)-1] == '#' && $units != 0){
                $partial = substr($fraction,0,strlen($fraction)-1) / substr($units,0,strlen($units)-1);
                $stocktotal = $cases + $partial;        
            }
            else if ($units != 0){
                $partial = $fraction / $units;
                $stocktotal = $cases + $partial;
            }
        }
        $total = $stocktotal * $price;
            
        $model = new DeliInventoryCatModel($sql);
        $model->item($item);
        $model->orderno($orderno);
        $model->units($units);
        $model->cases($cases);
        $model->fraction($fraction);
        $model->totalstock($stocktotal);
        $model->price($price);
        $model->total($total);
        $model->size($size);
        $model->category($category);
        $model->save();
        
        $out .= $this->gettable();
        break;
    case 'saveitem':
        $id = $_GET["id"];
        $item = $this->strim($_GET['item']);
        $orderno = $this->strim($_GET['orderno']);
        $units = $this->strim($_GET['units']);
        $cases = $this->strim($_GET['cases']);
        $fraction = $this->strim($_GET['fraction']);
        $price = $this->strim($_GET['price']);
        $size = $this->strim($_GET['size']);

        if (empty($cases) || !is_numeric($cases))
            $cases = 0;
        if (empty($fraction))
            $fraction = 0;
        
        $stocktotal = 0;
        if (!empty($units)){
            if ($units[strlen($units)-1] == '#' && 
                $fraction[strlen($fraction)-1] == '#' && $units != 0){
                $partial = substr($fraction,0,strlen($fraction)-1) / substr($units,0,strlen($units)-1);
                $stocktotal = $cases + $partial;        
            }
            else if ($units != 0){
                $partial = $fraction / $units;
                $stocktotal = $cases + $partial;
            }
        }
        $total = $stocktotal * $price;
        
        $model = new DeliInventoryCatModel($sql);
        $model->id($id);
        $model->item($item);
        $model->orderno($orderno);
        $model->units($units);
        $model->cases($cases);
        $model->fraction($fraction);
        $model->totalstock($stocktotal);
        $model->price($price);
        $model->total($total);
        $model->size($size);
        $model->save();

        $ret = array(
            'stock'=>$stocktotal,
            'total'=>$total,
            'cat'=>str_replace(' ','_',$model->category())
        );

        // recalculate category total
        $cat = $model->category();
        $model->reset();
        $model->category($cat);
        $ret['grandTotal'] = 0.0;
        foreach($model->find() as $obj)
            $ret['grandTotal'] += $obj->total();
        
        echo json_encode($ret);
        $out = '';
        break;
    case 'refresh':
        $out .= $this->gettable();
        break;
    case 'deleteitem':
        $id = $_GET['id'];
        
        $model = new DeliInventoryCatModel($sql);
        $model->id($id);
        $cat = $model->category();
        $model->delete();
        $ret = array('delete_row'=>True);

        $model->reset();
        $model->category($cat);
        $remaining = $model->find();
        if (count($remaining) == 0)
            $ret['delete_category'] = str_replace(' ','_',$cat);
        
        echo json_encode($ret);
        $out = '';
        break;
    case 'printview':
        $category = $_GET['category'];
            
        $out = "";

        if (isset($_GET["excel"])){
            header("Content-Disposition: inline; filename=deliInventoryCat.xls");
            header("Content-Description: PHP3 Generated Data");
            header("Content-type: application/vnd.ms-excel; name='excel'");
        }
        else {
            $out .= "<a href={$_SERVER['PHP_SELF']}?action=printview&category=$category&excel=yes>Save to Excel</a><br />"; 
        }
        $out .= $this->gettable(true,$category);
        break;
    case 'saveCategory':
        $oldcat = preg_replace("/_/"," ",$_GET['oldcat']);
        $newcat = preg_replacE("/_/"," ",$_GET['newcat']);

        $update = $sql->prepare_statement('UPDATE deliInventoryCat SET category=? WHERE category=?');
        $sql->exec_statement($update, array($newcat, $oldcat));

        $out .= $this->gettable();
        break;
    case 'catList':
        $id = $_GET['id'];
        $cat = preg_replace("/_/"," ",$_GET['category']);
        
        $fetchQ = "select category from deliInventoryCat
               group by category order by category";
        $fetchR = $sql->query($fetchQ);
        
        $out .= "$id"."`";
        $out .= "<select onchange=\"saveCat($id);\" id=catSelect$id>";
        while ($fetchW = $sql->fetch_array($fetchR)){
            if ($fetchW[0] == $cat)
                $out .= "<option selected>$fetchW[0]</option>";
            else
                $out .= "<option>$fetchW[0]</option>";
        }
        $out .= "</select>";
        break;
    case 'changeCat':
        $id = $_GET['id'];
        $newcat = $_GET['newcat'];

        $model = new DeliInventoryCatModel($sql);
        $model->id($id);
        $model->category($newcat);
        $model->save();

        $model->reset();
        $model->category($newcat);
        $ret['grandTotal'] = 0.0;
        foreach($model->find() as $obj)
            $ret['grandTotal'] += $obj->total();
        
        echo json_encode($ret);
        break;
    case 'clearAll':
        $clearQ = "update deliInventoryCat set cases=0, fraction=0,
            totalstock=0, total=0";
        $clearR = $sql->query($clearQ);
        $out .= $this->gettable();
        break;
    }
    
    echo $out;
    return false;
} else {
    return true;
}

    }

    private function gettable($limit=false,$limitCat="ALL")
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $sql = FannieDB::get($FANNIE_OP_DB);
        $ret = "";
        $colors = array('#ffffcc','#ffffff');
        $c = 0;

        $b_drop = $FANNIE_URL.'src/img/buttons/b_drop.png';
        $b_edit = $FANNIE_URL.'src/img/buttons/b_edit.png';
        $ret .= '<input type="hidden" id="editbtn" value="'.$b_edit.'" />';

        $fetchQ = "select item,size,orderno,units,
               case when cases='0' then NULL else cases end as cases,
               case when fraction='0' then NULL else fraction end as fraction,
               case when totalstock='0' then NULL else totalstock end as totalstock,
               price,total,category,id
                   from deliInventoryCat
               WHERE 1=1 ";
        $args = array();
        if ($limit){
            $fetchQ .= ' AND category=? ';
            $args[] = $limitCat;
        }
        $fetchQ .= "order by category, item";
        $fetchP = $sql->prepare_statement($fetchQ); 
        $fetchR = $sql->exec_statement($fetchP, $args);

        $ret .= "<a href=\"\" onclick=\"saveAll();return false;\">Save all changes</a> | <a href=\"\" onclick=\"clearAll();return false;\">Clear all totals</a><br /><br />";

        $currentCat = "";
        $sum = 0.0;
        while ($fetchW = $sql->fetch_array($fetchR)){
            $catfixed = $currentCat;
            if ($fetchW['category'] != $currentCat){
                if ($currentCat != ""){
                    $ret .= '</tbody>';
                    $ret .= '<tfoot>';
                    $ret .= "<tr><th bgcolor=$colors[$c]>Grand Total</th>";
                    for ($i = 0; $i < 7; $i++)
                        $ret .= "<td bgcolor=$colors[$c]>&nbsp;</td>";  
                    $ret .= "<td id=\"ttl$catfixed\">$sum</td></tr>";
                    $ret .= '</tfoot>';
                    $ret .= "</table>";
                    if (!$limit)
                        $ret .= $this->inputBox($currentCat);
                    $ret .= "<hr />";
                    $ret .= '</div>';
                }
                $currentCat = $fetchW['category'];
                $catfixed = preg_replace("/ /","_",$currentCat);
                $ret .= '<div id="wholeCategory'.$catfixed.'">';
                $ret .= "<b><span id=category$catfixed>$currentCat</span></b>"; 
                $ret .= "<span id=renameTrigger$catfixed>";
                if (!$limit){
                    $ret .= " [<a href=\"\" onclick=\"renameCategory('$catfixed'); return false;\">Rename This Category</a>]";
                    $ret .= " [<a href=\"{$_SERVER['PHP_SELF']}?action=printview&category=$currentCat\">Print this Category</a>]";
                }
                $ret .= "</span>";
                $ret .= "<table id=\"catTable{$catfixed}\" cellspacing=0 cellpadding=3 border=1>";
                $ret .= '<thead>';
                $ret .= "<tr><th>Item</th><th>Size</th><th>Order #</th><th>Units/Case</th>";
                $ret .= "<th>Cases</th><th>#/Each</th><th>Total cases</th>";
                $ret .= "<th>Price/case</th><th>Total</th></tr>";
                $ret .= '</thead>';
                $ret .= '<tbody>';
                $c = 0;
                $sum = 0.0;
            }
            $ret .= "<tr id=\"itemRow{$fetchW['id']}\">";
            $ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col0 bgcolor=$colors[$c]>".$fetchW['item']."&nbsp;</td>";
            $ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col1 bgcolor=$colors[$c]>".$fetchW['size']."&nbsp;</td>";
            $ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col2 bgcolor=$colors[$c]>".$fetchW['orderno']."&nbsp;</td>";
            $ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" id=item".$fetchW['id']."col3 bgcolor=$colors[$c]>".$fetchW['units']."&nbsp;</td>";
            $ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" 
                    id=item".$fetchW['id']."col4 bgcolor=$colors[$c]>"
                    . ($fetchW['cases'] == 0 ? '&nbsp;' : sprintf('%.2f',$fetchW['cases']))
                    . "&nbsp;</td>";
            $ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" 
                    id=item".$fetchW['id']."col5 bgcolor=$colors[$c]>"
                    . ($fetchW['fraction'] == 0 ? '&nbsp;' : sprintf('%.2f',$fetchW['fraction']))
                    . "&nbsp;</td>";
            $ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" 
                    id=item".$fetchW['id']."col6 bgcolor=$colors[$c]>"
                    . ($fetchW['totalstock'] == 0 ? '&nbsp;' : sprintf('%.2f',$fetchW['totalstock']))
                    . "&nbsp;</td>";
            $ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" 
                    id=item".$fetchW['id']."col7 bgcolor=$colors[$c]>".sprintf('%.2f',$fetchW['price'])."&nbsp;</td>";
            $ret .= "<td onclick=\"edititem(".$fetchW['id'].",'$catfixed');\" 
                    id=item".$fetchW['id']."col8 bgcolor=$colors[$c]>".sprintf('%.2f',$fetchW['total'])."&nbsp;</td>";

            $sum += $fetchW['total'];       

            if (!$limit){
                $ret .= "<td id=edit".$fetchW['id']." bgcolor=$colors[$c]><a href=\"\" onclick=\"edititem(".$fetchW['id']."); return false;\" title=Edit><img src=\"$b_edit\" border=0 /></a></td>";
                $ret .= "<td id=changecat".$fetchW['id']." bgcolor=$colors[$c]><a href=\"\" onclick=\"catList(".$fetchW['id'].",'$catfixed'); return false;\">Category</a></td>";
                $ret .= "<td bgcolor=$colors[$c]><a href=\"\" onclick=\"deleteitem(".$fetchW['id']."); return false;\" title=Delete><img src=\"$b_drop\" border=0 /></a></td>";
            }

            $ret .= "</tr>";
            $c = ($c+1)%2;
        
        }
        $ret .= "<tr><th bgcolor=$colors[$c]>Grand Total</th>";
        for ($i = 0; $i < 7; $i++)
            $ret .= "<td bgcolor=$colors[$c]>&nbsp;</td>";  
        $ret .= "<td>$sum</td></tr>";
        $ret .= "</table>";
        if (!$limit)
            $ret .= $this->inputBox($currentCat);
    
        return $ret;
    }

    private function inputBox($category)
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $sql = FannieDB::get($FANNIE_OP_DB);
        $category = preg_replace("/ /","_",$category);
        $ret = "<form onsubmit=\"additem('$category'); return false;\" id=newform$category>";
        $ret .= "<table cellspacing=0 cellpadding=3 border=1>";
        $ret .= "<tr>";
        $ret .= "<th>Item</th><th>Size</th><th>Order #</th><th>Units/Case</th>";
        $ret .= "<th>Cases</t><th>#/Each</th><th>Price/case</th>";
        $ret .= "</tr>";
        $ret .= "<tr>";
        $ret .= "<td bgcolor=#cccccc><input type=text id=newitem$category maxlength=50 /></td>";
        $ret .= "<td bgcolor=#cccccc><input type=text id=newsize$category size=8 maxlength=20 /></td>";
        $ret .= "<td bgcolor=#cccccc><input type=text id=neworderno$category size=6 maxlength=15 /></td>";
        $ret .= "<td bgcolor=#cccccc><input type=text id=newunits$category size=7 maxlength=10 /></td>";
        $ret .= "<td bgcolor=#cccccc><input type=text id=newcases$category size=7 maxlength=10 /></td>";
        $ret .= "<td bgcolor=#cccccc><input type=text id=newfraction$category size=7 maxlength=10 /></td>";
        $ret .= "<td bgcolor=#cccccc><input type=text id=newprice$category size=7 /></td>";
        $ret .= "<td><input type=submit value=Add /></td>";
        $ret .= "</tr>";
        $ret .= "</table>";
        $ret .= "</form>";

        return $ret;
    }

    private function swap($id1,$id2)
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $sql = FannieDB::get($FANNIE_OP_DB);

        $prep = $sql->prepare_statement('UPDATE deliInventoryCat SET id=? WHERE id=?');

        $sql->exec_statement($prep, array(-1*$id2, $id2));
        $sql->exec_statement($prep, array($id2, $id1));
        $sql->exec_statement($prep, array($id1, -1*$id2));
    }

    // safari trim
    // also takes off ascii 160 chars
    private function strim($str)
    {
        return trim($str,chr(32).chr(9).chr(10).chr(11).chr(13).chr(0).chr(160).chr(194));
    }

    public function body_content()
    {
        global $FANNIE_URL;
        ob_start();
?>

<html>
<head><title>Inventory</title>
<script type="text/javascript" src="<?php echo $FANNIE_URL; ?>src/javascript/jquery.js"></script>
<script type="text/javascript" src="index.js"></script>
<link rel="stylesheet" type="text/css" href="index.css">
</head>
<body>
<div id=tablearea><?php echo $this->gettable() ?></div>
<div id=inputarea>
<hr />
<b>Add an item to a new category</b><br />
<form onsubmit="additem('__new__'); return false;" id=newform__new__>
    <table cellspacing=0 cellpadding=3 border=1>
    <tr>
    <th>Item</th><th>Size</th><th>Order #</th><th>Units/Case</th>
    <th>Cases</t><th>#/Each</th><th>Price/case</th><th>Category Name</th>
    </tr>
    <tr>
    <td bgcolor=#cccccc><input type=text id=newitem__new__ maxlength=50 /></td>
    <td bgcolor=#cccccc><input type=text id=newsize__new__ size=8 maxlength=20 /></td>
    <td bgcolor=#cccccc><input type=text id=neworderno__new__ size=6 maxlength=15 /></td>
    <td bgcolor=#cccccc><input type=text id=newunits__new__ size=7 maxlength=10 /></td>
    <td bgcolor=#cccccc><input type=text id=newcases__new__ size=7 maxlength=10 /></td>
    <td bgcolor=#cccccc><input type=text id=newfraction__new__ size=7 maxlength=10 /></td>
    <td bgcolor=#cccccc><input type=text id=newprice__new__ size=7 /></td>
    <td bgcolor=#cccccc><input type=text id=category__new__ maxlength=50 /></td>
    <td><input type=submit value=Add /></td>
    </tr>
    </table>
</form>
<br />
</div>
</body>
</html>

        <?php
        return ob_get_clean();
    }

}

FannieDispatch::conditionalExec();
