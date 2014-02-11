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

include('../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class MarginToolFromSearch extends FannieRESTfulPage
{
    protected $header = 'Margin Search Results';
    protected $title = 'Margin Search Results';

    protected $window_dressing = false;

    protected $upcs = array();
    private $save_results = array();
    private $depts = array();
    private $superdepts = array();

    public function readinessCheck()
    {
        global $FANNIE_ARCHIVE_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_ARCHIVE_DB);
        if (!$dbc->tableExists('productSummaryLastQuarter')) {
            $this->error_text = _("You are missing an important table") . " ($FANNIE_ARCHIVE_DB.productSummaryLastQuarter). ";
            $this->error_text .= " Visit the <a href=\"{$FANNIE_URL}install\">Install Page</a> to create it.";
            return false;
        } else {
            $testQ = 'SELECT upc FROM productSummaryLastQuarter';
            $testQ = $dbc->addSelectLimit($testQ, 1);
            $testR = $dbc->query($testQ);
            if ($dbc->num_rows($testR) == 0) {
                $this->error_text = _('The product sales summary is missing. Run the Summarize Product Sales task.');
                return false;
            }
        }

        return true;
    }

    function preprocess()
    {
       $this->__routes[] = 'post<u>'; 
       $this->__routes[] = 'get<upc><deptID><newprice>';
       $this->__routes[] = 'get<upc><superID><newprice>';
       $this->__routes[] = 'post<upcs><deptID><newprices>';
       $this->__routes[] = 'post<upcs><superID><newprices>';
       return parent::preprocess();
    }

    // ajax callback: recalculate department margin
    // using new price for the given upc
    function get_upc_deptID_newprice_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $query = "SELECT SUM(p.cost) AS totalCost,
                    SUM(CASE WHEN upc=? THEN ? ELSE normal_price END) as totalPrice
                  FROM products AS p
                  WHERE department=?
                    AND cost <> 0";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($this->upc, $this->newprice, $this->deptID));
        echo $dbc->error();
        if ($dbc->num_rows($result) == 0) {
            echo "0";
        } else {
            $row = $dbc->fetch_row($result);
            echo sprintf('%.4f', ($row['totalPrice'] - $row['totalCost']) / $row['totalPrice'] * 100);
        }

        return false;
    }

    // ajax calllback: recalculate department margin
    // this plural version takes JSON arrays of UPCs and 
    // new prices. Recalculating the all applicable items 
    // seems easier then keeping track of every edit
    function post_upcs_deptID_newprices_handler()
    {
        global $FANNIE_OP_DB;
        $this->upcs = json_decode($this->upcs);
        $this->newprices = json_decode($this->newprices);
        // validate input arrays
        if (!is_array($this->upcs) || !is_array($this->newprices) || count($this->upcs) != count($this->newprices) || count($this->upcs) == 0) {
            echo '0';
            return false;
        }

        $priceCalc = "SUM(CASE";
        $args = array();
        for($i=0; $i<count($this->upcs); $i++) {
            $priceCalc .= ' WHEN upc=? THEN ? ';
            $args[] = $this->upcs[$i];
            $args[] = $this->newprices[$i];
        }
        $priceCalc .= ' ELSE normal_price END)';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $query = "SELECT SUM(p.cost) AS totalCost,
                    {$priceCalc} as totalPrice
                  FROM products AS p
                  WHERE department=?
                    AND cost <> 0";
        $args[] = $this->deptID;
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
        echo $dbc->error();
        if ($dbc->num_rows($result) == 0) {
            echo "0";
        } else {
            $row = $dbc->fetch_row($result);
            echo sprintf('%.4f', ($row['totalPrice'] - $row['totalCost']) / $row['totalPrice'] * 100);
        }

        return false;
    }

    // ajax callback: recalculate superdept margin
    // using new price for the given upc
    function get_upc_superID_newprice_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $query = "SELECT SUM(p.cost) AS totalCost,
                    SUM(CASE WHEN upc=? THEN ? ELSE normal_price END) as totalPrice
                  FROM products AS p
                  INNER JOIN MasterSuperDepts AS m ON p.department=m.superID
                  WHERE m.superID=?
                    AND cost <> 0";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($this->upc, $this->newprice, $this->superID));
        echo $dbc->error();
        if ($dbc->num_rows($result) == 0) {
            echo "0";
        } else {
            $row = $dbc->fetch_row($result);
            echo sprintf('%.4f', ($row['totalPrice'] - $row['totalCost']) / $row['totalPrice'] * 100);
        }

        return false;
    }

    // ajax calllback: recalculate superdept margin
    // this plural version takes JSON arrays of UPCs and 
    // new prices. Recalculating the all applicable items 
    // seems easier then keeping track of every edit
    function post_upcs_superID_newprices_handler()
    {
        global $FANNIE_OP_DB;
        $this->upcs = json_decode($this->upcs);
        $this->newprices = json_decode($this->newprices);
        // validate input arrays
        if (!is_array($this->upcs) || !is_array($this->newprices) || count($this->upcs) != count($this->newprices) || count($this->upcs) == 0) {
            echo '0';
            return false;
        }

        $priceCalc = "SUM(CASE";
        $args = array();
        for($i=0; $i<count($this->upcs); $i++) {
            $priceCalc .= ' WHEN upc=? THEN ? ';
            $args[] = $this->upcs[$i];
            $args[] = $this->newprices[$i];
        }
        $priceCalc .= ' ELSE normal_price END)';

        $dbc = FannieDB::get($FANNIE_OP_DB);
        $query = "SELECT SUM(p.cost) AS totalCost,
                    {$priceCalc} as totalPrice
                  FROM products AS p
                  INNER JOIN MasterSuperDepts AS m ON p.department=m.superID
                  WHERE m.superID=?
                    AND cost <> 0";
        $args[] = $this->superID;
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $args);
        echo $dbc->error();
        if ($dbc->num_rows($result) == 0) {
            echo "0";
        } else {
            $row = $dbc->fetch_row($result);
            echo sprintf('%.4f', ($row['totalPrice'] - $row['totalCost']) / $row['totalPrice'] * 100);
        }

        return false;
    }

    function post_u_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        if (!is_array($this->u)) {
            $this->u = array($this->u);
        }
        foreach($this->u as $postdata) {
            if (is_numeric($postdata)) {
                $this->upcs[] = BarcodeLib::padUPC($postdata);
            }
        }

        // get applicable department(s)
        $info = $this->arrayToParams($this->upcs);
        $deptQ = "SELECT department
                  FROM products 
                  WHERE upc in ({$info['in']})
                  GROUP BY department";
        $deptP = $dbc->prepare($deptQ);
        $deptR = $dbc->execute($deptP, $info['args']);
        while($deptW = $dbc->fetch_row($deptR)) {
            $this->depts[] = $deptW['department'];
        }

        if (empty($this->upcs)) {
            echo 'Error: no valid data';
            return false;
        } else {
            return true;
        }
    }

    function post_u_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL, $FANNIE_ARCHIVE_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $this->add_script($FANNIE_URL.'/src/jquery/jquery.js');
        $this->add_script($FANNIE_URL.'src/jquery/jquery.tablesorter.js');
        $this->add_css_file($FANNIE_URL.'/src/style.css');
        $this->add_css_file($FANNIE_URL.'src/jquery/themes/blue/style.css');
        $ret = '';

        // list super depts & starting margins
        $info = $this->arrayToParams($this->depts);
        $superQ = "SELECT m.superID, super_name,
                    SUM(cost) AS totalCost,
                    SUM(CASE WHEN p.cost = 0 THEN 0 ELSE p.normal_price END) as totalPrice
                   FROM MasterSuperDepts AS m
                   LEFT JOIN products AS p ON m.dept_ID=p.department
                   WHERE m.dept_ID IN ({$info['in']})
                   GROUP BY m.superID, super_name";
        $superP = $dbc->prepare($superQ);
        $superR = $dbc->execute($superP, $info['args']);
        $ret .= '<div style="position: fixed; top: 1em; right: 1em;">';
        $ret .= '<fieldset><legend>Overall Margins</legend>';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        while($superW = $dbc->fetch_row($superR)) {
            $this->upc = 'n/a';
            $this->newprice = 0;
            $this->superID = $superW['superID'];
            // use ajax callback to calculate margin for full superdept
            ob_start();
            $this->get_upc_superID_newprice_handler();
            $margin = ob_get_clean();
            $ret .= sprintf('<tr><td>%d %s</td><td id="smargin%d">%.4f%%</td></tr>',
                            $superW['superID'], $superW['super_name'], $superW['superID'], $margin
            );
        }
        $ret .= '<tr><td colspan="2" style="height:8px;font-size:0;">&nbsp;</td></tr>';

        // list depts and starting margins
        $marginQ = "SELECT department, dept_name,
                      SUM(cost) AS totalCost,
                      SUM(normal_price) as totalPrice
                    FROM products AS p
                    LEFT JOIN departments AS d ON p.department=d.dept_no
                    WHERE department IN ({$info['in']})
                        AND cost <> 0
                    GROUP BY department, dept_name";
        $marginP = $dbc->prepare($marginQ);
        $marginR = $dbc->execute($marginP, $info['args']);
        while($marginW = $dbc->fetch_row($marginR)) {
            $ret .= sprintf('<tr><td>%d %s</td><td id="dmargin%d">%.4f%%</td></tr>',
                            $marginW['department'], $marginW['dept_name'], $marginW['department'],
                            (($marginW['totalPrice'] - $marginW['totalCost']) / $marginW['totalPrice']) * 100
            );
        }
        $ret .= '</table></fieldset></div>';

        // list the actual items
        $ret .= '<form onsubmit="return false;" method="post">';
        $ret .= '<table id="maintable" class="tablesorter" style="width:80%;"><thead>';
        $ret .= '<tr>
                <th>UPC</th>
                <th>Description</th>
                <th>% Store</th>
                <th>% Super</th>
                <th>% Dept</th>
                <th>Cost</th>
                <th>Current Price</th>
                <th>Margin</th>
                <th>New Price</th>
                </tr></thead><tbody>';

        $info = $this->arrayToParams($this->upcs);
        $query = 'SELECT p.upc, p.description, p.department, p.cost,
                    p.normal_price, m.superID, q.percentageStoreSales,
                    q.percentageSuperDeptSales, q.percentageDeptSales
                  FROM products AS p
                  LEFT JOIN MasterSuperDepts AS m ON p.department=m.dept_ID
                  LEFT JOIN ' . $FANNIE_ARCHIVE_DB . $dbc->sep() . 'productSummaryLastQuarter AS q
                    ON p.upc=q.upc
                  WHERE p.upc IN (' . $info['in'] . ')
                  ORDER BY p.upc';
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, $info['args']);
        while($row = $dbc->fetch_row($result)) {
            $ret .= sprintf('<tr>
                            <td>%s</td>
                            <td>%s</td>
                            <td>%.5f%%</td>
                            <td>%.4f%%</td>
                            <td>%.4f%%</td>
                            <td>%.2f</td>
                            <td>%.2f</td>
                            <td id="margin%s">%.4f%%</td>
                            <td class="dept%d super%d">
                                <input type="text" size="5" name="price[]" class="newprice"
                                value="%.2f" onchange="reCalc(\'%s\', this.value, %f, %d, %d);" />
                                <input type="hidden" name="upc[]" class="itemupc" value="%s" />
                            </td>
                            </tr>',
                            $row['upc'],
                            $row['description'],
                            $row['percentageStoreSales'] * 100,
                            $row['percentageSuperDeptSales'] * 100,
                            $row['percentageDeptSales'] * 100,
                            $row['cost'],
                            $row['normal_price'],
                            $row['upc'], (($row['normal_price'] - $row['cost']) / $row['normal_price']) * 100,
                            $row['department'], $row['superID'],
                            $row['normal_price'], $row['upc'], $row['cost'], $row['department'], $row['superID'],
                            $row['upc']
            );
        }
        $ret .= '</tbody></table>';

        $ret .= '<br />';
        $ret .= '<input type="submit" name="save" value="Save Changes" />';
        $ret .= '</form>';

        $this->add_onload_command("\$('#maintable').tablesorter({sortList: [[0,0]], widgets: ['zebra']});");

        return $ret;
    }

    function javascript_content()
    {
        ob_start();
        ?>
function reCalc(upc, price, cost, deptID, superID) {
    var newprice = Number(price);
    if (cost == 0 || isNaN(newprice)) {
        return false;
    }

    var itemMargin = (price - cost) / price * 100;
    itemMargin = Math.round(itemMargin * 10000) / 10000;
    $('#margin'+upc).html(itemMargin+"%");

    var pArray = Array();
    var uArray = Array();
    $('.dept'+deptID).each(function(){
        pArray.push($(this).find('.newprice').val());
        uArray.push($(this).find('.itemupc').val());
    });
    var prices = JSON.stringify(pArray);
    var upcs = JSON.stringify(uArray);
    console.log(prices);
    console.log(upcs);

    $.ajax({
        url: 'MarginToolFromSearch.php',
        type: 'post',
        data: 'upcs='+upcs+'&deptID='+deptID+'&newprices='+prices,
        success: function(resp) {
            $('#dmargin'+deptID).html(resp+"%");
        }
    });

    $.ajax({
        url: 'MarginToolFromSearch.php',
        type: 'post',
        data: 'upcs='+upcs+'&superID='+superID+'&newprices='+prices,
        success: function(resp) {
            $('#smargin'+superID).html(resp+"%");
        }
    });
}
        <?php
        return ob_get_clean();
    }

    private function arrayToParams($arr) {
        $str = '';
        $args = array();
        foreach($arr as $entry) {
            $str .= '?,';
            $args[] = $entry;
        }
        $str = substr($str, 0, strlen($str)-1);

        return array('in'=>$str, 'args'=>$args);
    }
}

FannieDispatch::conditionalExec();

