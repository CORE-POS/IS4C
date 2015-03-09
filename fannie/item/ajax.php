<?php
require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}

if (isset($_REQUEST['action'])){
    switch($_REQUEST['action']){
    case 'margin':
        MarginFS($_REQUEST['upc'],$_REQUEST['cost'],$_REQUEST['dept']);
        break;
    case 'likecode':
        GetLikeCodeItems($_REQUEST['lc']);
        break;
    }
}

function GetLikecodeItems($lc){
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $ret = "<table border=0 bgcolor=\"#FFFFCC\">";
    if (is_numeric($lc)){
        $prep = $dbc->prepare_statement("SELECT p.upc,p.description FROM
            products AS p INNER JOIN upcLike AS u ON
            p.upc=u.upc WHERE u.likeCode=?
            ORDER BY p.upc");
        $res = $dbc->exec_statement($prep, array($lc));
        while($row = $dbc->fetch_row($res)){
            $ret .= sprintf("<tr><td><a href=itemMaint.php?upc=%s>%s</a></td>
                    <td>%s</td></tr>",$row[0],$row[0],$row[1]);
        }
    }
    $ret .= "</table>";
    
    echo $ret;
}

function MarginFS($upc,$cost,$deptID)
{
    global $FANNIE_OP_DB;
    $dbc = FannieDB::get($FANNIE_OP_DB);

    $price = 'None';
    $prod = new ProductsModel($dbc);
    $prod->upc($upc);
    if ($prod->load()) {
        $price = $prod->normal_price();
    }

    $dm = 'Unknown';
    $dept = new DepartmentsModel($dbc);
    $dept->dept_no($deptID);
    if ($dept->load()) {
        $dm = $dept->margin();
    }

    if ((empty($dm) || $dm == 'Unknown') && $dbc->tableExists('deptMargin')) {
        $prep = $dbc->prepare_statement("SELECT margin FROM deptMargin WHERE dept_ID=?");
        $dm = $dbc->exec_statement($prep, array($deptID));
        if ($dbc->num_rows($dm) > 0) {
            $row = $dbc->fetch_row($dm);
            $dm = $dm['margin'];
        }
    }

    $ret = "Desired margin on this department is ";
    if ($dm == "Unknown") $ret .= $dm;
    else $ret .= sprintf("%.2f%%",$dm*100);
    $ret .= "<br />";
    
    $actual = 0;
    if ($price != 0)
        $actual = ($price-$cost)/$price;
    if (($actual > $dm && is_numeric($dm)) || !is_numeric($dm) ){
        $ret .= sprintf("<span style=\"color:green;\">Current margin on this item is %.2f%%<br />",
            $actual*100);
    } else if (!is_numeric($price)) {
        $ret .= "<span style=\"color:green;\">No price has been saved for this item<br />";
    } else {
        $ret .= sprintf("<span style=\"color:red;\">Current margin on this item is %.2f%%</span><br />",
            $actual*100);
        $srp = getSRP($cost,$dm);
        $ret .= sprintf("Suggested price: \$%.2f ",$srp);
        $ret .= sprintf("(<a href=\"\" onclick=\"setPrice(%.2f); return false;\">Use this price</a>)",$srp);
    }

    echo $ret;
}

function getSRP($cost,$margin){
    $srp = sprintf("%.2f",$cost/(1-$margin));
    while (substr($srp,strlen($srp)-1,strlen($srp)) != "5" &&
           substr($srp,strlen($srp)-1,strlen($srp)) != "9")
        $srp += 0.01;
    return $srp;
}


?>
