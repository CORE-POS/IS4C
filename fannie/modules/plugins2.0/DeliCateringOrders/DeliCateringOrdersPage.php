<?php 
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class DeliCateringOrdersPage extends FannieRESTfulPage 
{

    protected $header = 'Deli Catering Orders';
    protected $title = 'Deli Catering Orders';
    protected $results = array();
    protected $orders = array();
    protected $must_authenticate = true;
    protected $auth_classes = array('delicatering');
    public $description = '[Deli Catering Orders] is a tool to take new Deli Catering Orders.';

    function preprocess(){
        
        $this->__routes[] = 'get<id><confirm>';
        $this->__routes[] = 'get<id>';
        $this->__routes[] = 'get<review>';
        $this->__routes[] = 'get<complete>';
        return parent::preprocess();
    }
    
    public function css_content()
    {
        return '
                table td,th {
                    border-top: none !important;
                }
                .descbox {
                    height: 200px;
                    width: 550px;
                    position: relative;
                    padding: 5px;
                    
                }
                .longdescbox {
                    height: 200px;
                    width: 650px;
                    position: relative;
                    padding: 5px;
                    
                }
                .longerdescbox {
                    height: 200px;
                    width: 850px;
                    position: relative;
                    padding: 5px;
                }
                .enchiladadescbox {
                    height: 200px;
                    width: 770px;
                    position: relative;
                    padding: 5px;
                }
                .title {
                    font-size: 20px;
                    
                    position: relative;
                    text-align: left;
                }
                .desc {
                    font-size: 16px;
                    position: relative;
                    float: left;
                    width: 400px;
                    padding: 10px;
                    
                }
                .quantbox {
                    height: 100px;
                    width: 280;
                    position: relative;
                    float: right;
                    padding: 10px;
                    text-align: right;
                    
                }
                p {
                    
                    font-size: 18px;
                }
                .form-qty {
                    width: 90px;
                }
                .form-up-btn {
                    height: 12px;
                    width: 20px;
                    background-image: url(src/up.png);   
                }
                .form-down-btn {
                    height: 12px;
                    width: 20px;
                    background-image: url(src/down.png);   
                }
                fieldset {
                    border: 1px solid black;
                }
                .menu {
                    background-color: lightgrey;
                    padding: 20px;
                }
                .grey {
                    color: grey;
                }
                .noborder {
                    border: 0;
                }
                .options {
                    position: relative; 
                    float: left; 
                    width: 210px; 
                    height: 125px; 
                }
                .smpanel {
                    width: 850px;
                }
                
        ';
    }
    
    public function get_complete_view()
    {
        $ret = "";
        $order_num = $_GET['order_num'];
        
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $prep = $dbc->prepare("
            UPDATE DeliCateringCustomerInfo
            SET status = 1 
            WHERE order_num = ?
        ");
        $result = $dbc->execute($prep, $order_num);
        $ret .= '<h2>Order # ' . $order_num . '</h2>';
        
        $ret .= '
            <form method="get">
                <!-- <input type="hidden" name="id"> -->
                <input type="submit" class="btn btn-default" value="Back to Orders">
            </form>
        ';
        if ($dbc->error()) {
            $ret .= $dbc->error(). "<br>";
        } else {
            $ret .= '<div class="alert alert-success" align="center">Order Marked as Completed</div>';
        }
        
        return $ret;
    }
    
    public function get_review_view()
    {
        $ret = "";
        $ret .= '<a class="btn btn-default" href="
            http://key/git/fannie/modules/plugins2.0/DeliCateringOrders/DeliCateringOrdersPage.php">
            Back to View Orders</a>';
        
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $order_num = $_GET['review'];
        
        $prep = $dbc->prepare("
            SELECT 
                order_num,
                name,
                email,
                phone,
                alt_phone,
                order_date,
                status,
                notes,
                card_no,
                owner
            FROM DeliCateringCustomerInfo
            WHERE order_num = ?
        ");
        $ret .= '<div class="container">';
        $ret .= '<table class="table">';
        $result = $dbc->execute($prep, $order_num);
        $ret .= '<h2>Order # ' . $order_num . '</h2>';
        $owner = 0;
        while ($row = $dbc->fetch_row($result)) {
            $ret .= '<tr><td width="150px" ><b>Date Order Placed:</b></td><td>' . substr($row['order_date'],0,16) . '</td>';
            $ret .= '<tr><td ><b>Owner Number:</b></td><td>' . $row['card_no'] . '</td>';
            $ret .= '<tr><td ><b>Customer Name:</b></td><td>' . $row['name'] . '</td>';
            $ret .= '<tr><td ><b>Phone Number:</b></td><td>' . $row['phone'] . '</td>';
            $ret .= '<tr><td ><b>Alternate Phone:</b></td><td>' . $row['alt_phone'] . '</td>';
            $ret .= '<tr><td ><b>Email Address:</b></td><td>' . $row['email'] . '</td>';
            $ret .= '<tr><td ><b>Notes / Special Instructions:</b></td><td>' . $row['notes'] . '</td>';
            if ($row['owner'] == 1) $owner = 1;
        }
        $ret .= '</div>';
        $ret .= '</table>';
        
        $curTotal = 0;
        $taxTotal = 0;
        $prep = $dbc->prepare("
            SELECT 
                dco.upc,
                dco.qty,
                dci.name,
                dci.price,
                dco.op1,
                dco.op2,
                dco.op3,
                dco.op4,
                dco.op5,
                dco.op6,
                dco.op7,
                dco.op8
            FROM DeliCateringOrders AS dco
                LEFT JOIN DeliCateringItems AS dci ON dci.upc=dco.upc
            WHERE dco.order_num = ?
        ");
        $result = $dbc->execute($prep, $order_num);
        $ret .= '<h2>Products</h2>';
        $ret .= '<table class="table">';
        $ret .= '<th>Product</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th>';
        while($row = $dbc->fetch_row($result)){
                $curTotal += $row['price'] * $row['qty'];
                $ret .= '<tr><td>' . $row['name'] . '</td><td>' .  $row['qty'] . '</td><td>$' . $row['price'] . 
                    '</td><td>' . $curTotal . '</tr>';
                    
                if ($row['op1']) $ret .= '<tr><td>~~' . $row['op1'] . '</td></tr>';
                if ($row['op2']) $ret .= '<tr><td>~~' . $row['op2'] . '</td></tr>';
                if ($row['op3']) $ret .= '<tr><td>~~' . $row['op3'] . '</td></tr>';
                if ($row['op4']) $ret .= '<tr><td>~~' . $row['op4'] . '</td></tr>';
                if ($row['op5']) $ret .= '<tr><td>~~' . $row['op5'] . '</td></tr>';
                if ($row['op6']) $ret .= '<tr><td>~~' . $row['op6'] . '</td></tr>';
                if ($row['op7']) $ret .= '<tr><td>~~' . $row['op7'] . '</td></tr>';
                if ($row['op8']) $ret .= '<tr><td>~~' . $row['op8'] . '</td></tr>';
              
            }
        //$ret .= '<tr><td></td><td></td><td><b>Final Subtotal:</b></td><td>' . $curTotal . '</tr>';
        //  IF total exceeds 150 and user IS an owner - then 10% discount applies here
        if ($curTotal > 149.99 && $owner == 1) {
            //Add IF IS OWNER
            $ret .= '<tr><td></td><td></td><td><b>(10%) Owner Discount:</b></td><td>' . 
                sprintf("-%0.2f",$curTotal * .10);
                $curTotal = $curTotal - ($curTotal * .10);
        }
        //  IF total exceeds 150, calculate the 25% deposit here
        $taxTotal = 0.10625 * $curTotal;
        $ret .= '<tr><td></td><td></td><td><b>Total with Tax:</b></td><td>$' . sprintf("%0.2f",($curTotal + $taxTotal)) . '</tr>';
        
        if ($curTotal > 149.99) {
            $deposit = $curTotal * 0.25;
            $ret .= '<tr><td></td><td></td><td><b>(25%) Required Deposit :</b></td><td>$' . sprintf("%0.2f",$deposit) . '</tr>'   ;
        }
        
        $ret .= '</table>';
        $ret .= '
            <br><br>
            <div>
                <form method="get">
                    <input type="hidden" name="order_num" value="' . $order_num . '">
                    <input type="submit" class="btn btn-danger" name="complete" value="Mark Order as Completed"
                        onclick="return confirm(\'Are you sure you want to remove order from list of active orders?\');">
                </form>
            </div><br>
        ';
        
        return $ret;
    }
    
    public function get_id_confirm_view()
    {
        
        $ret = '';
        $data = array();
        $opts = 1;
        
        if ($_GET['name']) $data['name'] = $_GET['name'];
        if ($_GET['card_no']) $data['card_no'] = $_GET['card_no'];
        if ($_GET['phone']) $data['phone'] = $_GET['phone'];
        if ($_GET['alt_phone']) $data['alt_phone'] = $_GET['alt_phone'];
        if ($_GET['email']) $data['email'] = $_GET['email'];
        if ($_GET['member']) $data['member'] = $_GET['member'];
        if ($_GET['notes']) $data['notes'] = $_GET['notes'];
        
        foreach ($_GET as $key => $value) {
            if ($value > 0 && substr($key,0,2) === 'id') {
                $thisUPC = substr($key,2,3);
                $data[$key][$thisUPC] = $value;
            } elseif ($value === 'LG') {
                $thisUPC = substr($key,2,3) + 1;
                $data[$key][$thisUPC] = 1;
            } elseif ($value === 'SM') {
                $thisUPC = substr($key,2,3);
                $data[$key][$thisUPC] = 1;    
            } elseif (substr($key,0,2) === 'op') {
                $thisUPC = substr($key,2,3);
                $thisID = 'id' . $thisUPC;
                if($_GET[$thisID] === 'LG') {
                    $thisUPC++;
                    $data[$key][$thisUPC][$opts] = $value;
                } else {
                    $data[$key][$thisUPC][$opts] = $value;
                }
            }
        }
             
        $date = date('Y-m-d h:i:s');
        $notes = $_GET['notes'];
        
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $order_num = 0;
        $query = $dbc->prepare("
            SELECT max(order_num) AS num
            FROM DeliCateringOrders
        ");
        $result = $dbc->execute($query);
        $row = $dbc->fetch_row($result);
        $anum = $row['num'];
        
        $query = $dbc->prepare("
            SELECT max(order_num) AS num
            FROM DeliCateringCustomerInfo
        ");
        $result = $dbc->execute($query);
        $row = $dbc->fetch_row($result);
        $bnum = $row['num'];
        
        if ($anum > $bnum) {
            $this->session->order_num = $anum + 1;
        } else {
            $this->session->order_num = $bnum + 1;
        }
        
        if ($row['num'] == NULL) $this->session->order_num = 1;
        $order_num = $this->session->order_num;
        $ret .= 'Order number: ' . $this->session->order_num . '<br>';
        
        $args = array();
        $args2 = array();
        $args3 = array();
        $op = 0;
        $iop = 1;
        $i = 0;
        $lastUPC = 0;
        
        foreach ($data as $fullUPC => $array) {
            foreach ($array as $UPC => $qty) {
                if (!is_array($qty) && $qty > 0) {
                    $args = array($UPC, $qty, $order_num);
                    $query = $dbc->prepare("
                        INSERT INTO DeliCateringOrders (upc, qty, order_num) 
                        VALUES (
                            ?,
                            ?,
                            ?
                        )
                    ");
                    $result = $dbc->execute($query, $args);
                } elseif (is_array($qty)) {
                    foreach ($qty as $qtyB => $option) {
                        $args3 = array($option, $order_num, $UPC);
                        
                        $op = 'op' . $iop;
                        $curUPC = $UPC;
                        if ($lastUPC == 0) $lastUPC = $UPC;
                        if ($iop != 1) {
                            if ($curUPC == $lastUPC) {
                                $iop++;
                            } else {
                                $iop = 1;
                            }    
                        } else {
                            $iop++;
                        }
                         
                        $query = $dbc->prepare("
                            UPDATE DeliCateringOrders 
                            SET {$op} = ? 
                            WHERE order_num = ?
                                AND upc = ?
                        ");
                        $result = $dbc->execute($query, $args3);
                        $lastUPC = $UPC;
                    }
                }
            }
        }
        $args2 = array($data['name'], $data['card_no'], $data['phone'], $data['alt_phone'], 
            $data['email'], $order_num, $date, "0", $data['notes'], $data['member']);
        $query = $dbc->prepare("
                    INSERT INTO DeliCateringCustomerInfo (name, card_no, phone, alt_phone, 
                        email, order_num, order_date, status, notes, owner) 
                    VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");
        $dbc->execute($query, $args2);
        
        if ($dbc->error()) {
            echo '
            <div class="alert alert-danger" align="center"><p>Something Went Wrong. This Order Was Not Placed.</p>
                <p class="text-warning">ERROR: ' . $dbc->error() . '</p>
            </div>';
        } else {
            $ret .= '<div class="alert alert-success" align="center">The Order Has Been Placed</div>';
        }
        
        $ret .= '<a class="btn btn-default" href="
            http://key/git/fannie/modules/plugins2.0/DeliCateringOrders/DeliCateringOrdersPage.php">
            Back to View Orders</a>';
            
        return $ret;
    }
    
    public function get_id_view()
    {
        $ret = '';
        $ret .= '

  <!-- Nav tabs -->
  <form method="get" name="orderform" id="orderform">
  <ul class="nav nav-tabs" role="tablist">
    <li role="menu" class="active"><a href="#customer" aria-controls="customer" role="tab" data-toggle="tab">Customer Info</a></li>
    <li role="menu"><a href="#appetizers" aria-controls="appetizers" role="tab" data-toggle="tab">Appetizers</a></li>
    <li role="menu"><a href="#fruit" aria-controls="fruit" role="tab" data-toggle="tab">Fruit & Veggie Trays</a></li>
    <li role="menu"><a href="#sandwiches" aria-controls="sandwiches" role="tab" data-toggle="tab">Sandwiches</a></li>
    <li role="menu"><a href="#entrees" aria-controls="entrees" role="tab" data-toggle="tab">Entrees</a></li>
    <li role="menu"><a href="#baked" aria-controls="baked" role="tab" data-toggle="tab">Baked Goods</a></li>
    <li role="menu"><a href="#checkout" aria-controls="checkout" role="tab" data-toggle="tab">Checkout</a></li>
    <li>&nbsp;&nbsp;&nbsp;&nbsp;</li>
    <li><input type="submit" class="btn btn-warning" value="Update Order"></li>
  </ul>
  
  <div align="right"><br>
    <input type="hidden" name="id">
    
  </div>
  
      <!-- Tab panes -->
      <div class="tab-content">
      
        <!-- Customer Info -->
        <div role="tabpanel" class="tab-pane active" id="customer">
<div class="container">            
<div class="panel panel-default smpanel"><div class="panel-heading" ><b>Customer Info</b><br><br>
            
                        <ul style="list-style-type:circle">
                            <li>WFC Owners receive a 10% discount on pre-order 
                                Deli prepared foods catering orders totalling more than $150.</li>
                            <li>ALL catering orderss (Owner and non-Owner) exceeding $150 require a 25% non-refundable deposit at the time of order.</li>
                            <li>Seven day notice is required for all catering orders exceeding $500.</li>
                            <li>24 hour notice is required for all other catering orders.</li>
                        </ul>
                    </div>                
            <div class="form-inline" >
                    
                <div class="container">
                
                    <table class="table">
                    <tr><td>
                    <input type="radio" class="form-control" name="member" value="0"';
                    if ($_GET['member'] < 1) $ret .= ' checked> Non-Owner<br>';
                        else $ret .= '> Non-Owner</td>';
                        
                    $ret .= '
                    <td>
                    <input type="radio" class="form-control" name="member" value="1"';
                    if ($_GET['member'] > 0) $ret .= ' checked> Owner<br><br>';
                        else $ret .= '> Owner</td>';
                              
                    
                    $ret .= '
                    <tr><td width="150px">Owner Number</td><td>
                    <input type="input" class="form-control" style="width: 75px" name="card_no" id="card_no" ';
                    if ($_GET['card_no'] > 0) $ret .= 'value="' . $_GET['card_no'] . '" '; 
                              $ret .= 'onChange="autoFill();" required></td>
                    
                    <tr><td width="150px">Name</td><td>
                    <input type="input" class="form-control" name="name" id="name" ';
                    if ($_GET['name']) $ret .= 'value="' . $_GET['name'] . '" '; 
                              $ret .= ' required></td>
                     
                     
                    <tr><td width="150px">Phone Number</td><td>
                    <input type="input" class="form-control" name="phone" id="phone" ';
                    if ($_GET['phone']) $ret .= 'value="' . $_GET['phone'] . '" '; 
                              $ret .= ' required></td>
                    
                    <tr><td width="150px">Alternate Phone</td><td>
                    <input type="input" class="form-control" name="alt_phone" id="alt_phone" ';
                    if ($_GET['alt_phone']) $ret .= 'value="' . $_GET['alt_phone'] . '" '; 
                              $ret .= '></td>
         
                    <tr><td width="150px">Email Address</td><td>
                    <input type="input" class="form-control" style="width: 250px" name="email" id="email" ';
                    if ($_GET['email']) $ret .= 'value="' . $_GET['email'] . '" '; 
                              $ret .= '></td>
                              
                </table>
                </div>
            </div>
        </div>
</div>
</div>
        
        <!-- APPETIZERS -->
        <div role="tabpanel" class="tab-pane" id="appetizers" >
<div class="container">            
<div class="panel panel-default smpanel"><div class="panel-heading" align="center"><b>Appetizers</b></div>

                
                    <div class=\'descbox\'>
                        <div class=\'title\'>
                            Gourmet Cheese Platter
                        </div>
                        <div class=\'desc\'>
                            An assortment of artisan cheeses for every palate. Creamy 
                            imported Brie, aged Gouda, sharp Irish cheddar, gorgonzola, 
                            fresh chevre, and applewood smoked cheddar. Served with 
                            seasonal fruit and sliced baguette.
                        </div>
                        <div class=\'quantbox\'>
                          <strong>$54.99</strong><br><i>Serves 8 - 10</i><br>
                              <input type=\'number\' name=\'id101\' id=\'qty101\' class=\'form-control form-control form-qty\' min=\'0\'';
                              if ($_GET['id101'] > 0) $ret .= 'value="' . $_GET['id101'] . '" '; 
                              $ret .= '><br>
                        </div>
                    </div>

                    <div class=\'descbox\'>
                        <div class=\'title\'>
                            <br>Gourmet Cheese & Charcuterie Platter
                        </div>
                        <div class=\'desc\'>
                            The same variety of delicious cheeses, but with the 
                            addition of Lake Superior smoked trout, duck liver 
                            pate with cognac, and thinly sliced Olli salami.
                        </div>
                        
                        <div class=\'quantbox\'>
                          <strong>$99.99</strong><br><i>Serves 16 - 20</i><br>
                            <input type=\'number\' name=\'id102\' id=\'qty102\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id102'] > 0) $ret .= 'value="' . $_GET['id102'] . '" '; 
                              $ret .= '><br>
                        </div> 
                    </div>
                    
                    <div class=\'longdescbox\'>
                        <div class=\'title\'>
                            Spanikopita Platter
                        </div>
                        <div class=\'desc\'>
                            Flaky phyllo dough wrapped around a savory 
                            combination of spinach, onions, feta and herbs. 
                            Served with a tangy tzatziki sauce for dipping.
                        </div>
                        <div class=\'quantbox\'>
                        <strong>$64.99</strong><br>Large<br><i>Serves 16 - 20</i><br>
                            <input type=\'number\' name=\'id104\' id=\'qty104\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id104'] > 0) $ret .= 'value="' . $_GET['id104'] . '" '; 
                              $ret .= '><br>
                        </div>
                        <div class=\'quantbox\'>
                          <strong>$34.99</strong><br>Small<br><i>Serves 8 - 10</i><br>
                            <input type=\'number\' name=\'id103\' id=\'qty103\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id103'] > 0) $ret .= 'value="' . $_GET['id103'] . '" '; 
                              $ret .= '><br>
                        </div>
                    </div>
                    
                    <div class=\'longdescbox\'>
                        <div class=\'title\'>
                            Caprese Skewers
                        </div>
                        <div class=\'desc\'>
                            Fresh mozzarella layered with grape tomatoes and basil, 
                            accompanied by a balsamic vinegar reduction.
                        </div>
                        <div class=\'quantbox\'>
                        <strong>$64.99</strong><br>Large<br><i>Serves 16 - 20</i><br>
                            <input type=\'number\' name=\'id106\' id=\'qty106\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id106'] > 0) $ret .= 'value="' . $_GET['id106'] . '" '; 
                              $ret .= '><br>
                        </div>
                        <div class=\'quantbox\'>
                          <strong>$34.99</strong><br>Small<br><i>Serves 8 - 10</i><br>
                            <input type=\'number\' name=\'id105\' id=\'qty105\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id105'] > 0) $ret .= 'value="' . $_GET['id105'] . '" '; 
                              $ret .= '><br>
                        </div>
                    </div>
                    
                    <div class=\'longdescbox\'>
                        <div class=\'title\'>
                            Shrimp Skewers with Thai Dipping Sauce
                        </div>
                        <div class=\'desc\'>
                            Delicate white shrimp in a lemongrass ginger marinade, 
                            grilled, and served with a Thai peanut dipping sauce.
                        </div><div class=\'quantbox\'>
                        <strong>$74.99</strong><br>Large<br><i>Serves 16 - 20</i><br>
                            <input type=\'number\' name=\'id108\' id=\'qty108\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id108'] > 0) $ret .= 'value="' . $_GET['id108'] . '" '; 
                              $ret .= '><br>
                        </div>
                        <div class=\'quantbox\'>
                          <strong>$39.99</strong><br>Small<br><i>Serves 8 - 10</i><br>
                            <input type=\'number\' name=\'id107\' id=\'qty107\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id107'] > 0) $ret .= 'value="' . $_GET['id107'] . '" '; 
                              $ret .= '><br>
                        </div>
                    </div>
                    
                    <div class=\'longdescbox\'>
                        <div class=\'title\'>
                            Antipasti Platter
                        </div>
                        <div class=\'desc\'>
                            Proscuitto, salami, fresh mozzarella, assorted olives, 
                            marinated mushrooms and artichokes, pepperoncini, olive 
                            tapenade. Served with crostini.
                        </div>
                        <div class=\'quantbox\'>
                        <strong>$74.99</strong><br>Large<br><i>Serves 16 - 20</i><br>
                            <input type=\'number\' name=\'id110\' id=\'qty110\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id110'] > 0) $ret .= 'value="' . $_GET['id110'] . '" '; 
                              $ret .= '><br>
                        </div>
                        <div class=\'quantbox\'>
                          <strong>$39.99</strong><br>Small<br><i>Serves 8 - 10</i><br>
                            <input type=\'number\' name=\'id109\' id=\'qty109\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id109'] > 0) $ret .= 'value="' . $_GET['id109'] . '" '; 
                              $ret .= '><br>
                        </div>
                    </div>
                    
                    <div class=\'longdescbox\'>
                        <div class=\'title\'>
                            Middle Eastern Tray
                        </div>
                        <div class=\'desc\'>
                            Hummus, red bell pepper, cucumbers, feta, olives, 
                            tabbouleh, Evening In Morrocco, dolmas, and pita.
                        </div>
                        <div class=\'quantbox\'>
                        <strong>$74.99</strong><br>Large<br><i>Serves 16 - 20</i><br>
                            <input type=\'number\' name=\'id112\' id=\'qty112\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id112'] > 0) $ret .= 'value="' . $_GET['id112'] . '" '; 
                              $ret .= '><br>
                        </div>
                        <div class=\'quantbox\'>
                          <strong>$39.99</strong><br>Small<br><i>Serves 8 - 10</i><br>
                            <input type=\'number\' name=\'id111\' id=\'qty111\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id111'] > 0) $ret .= 'value="' . $_GET['id111'] . '" '; 
                              $ret .= '><br>
                        </div>
                    </div>
                    
                    <div class=\'descbox\'>
                        <div class=\'title\'>
                            Baked Brie en Croute
                        </div>
                        <div class=\'desc\'>
                            Saint Rocco Brie filled with date apricot preserves 
                            and wrapped in buttery puff pastry, baked to golden 
                            perfection. Served with crackers.
                        </div>
                        
                        <div class=\'quantbox\'>
                          <strong>$19.99</strong><br><i>Serves 6 -  8</i><br>
                            <input type=\'number\' name=\'id113\' id=\'qty113\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id113'] > 0) $ret .= 'value="' . $_GET['id113'] . '" '; 
                              $ret .= '><br>
                        </div> 
                    </div>
                    
                    
        </div>
</div>
</div>
        
        <!-- Fruit & Veggie Trays -->
        <div role="tabpanel" class="tab-pane" id="fruit">
        
<div class="container">            
<div class="panel panel-default smpanel"><div class="panel-heading" align="center"><b>Fruit And Veggie Trays</b></div>
        
                <h2></h2>
                
                    <div class=\'longdescbox\'>
                        <div class=\'title\'>
                            Vegetable Tray
                        </div>
                        <div class=\'desc\'>
                        Each tray includes a variety of seasonal vegetables that
                        are hand-selected for maximum freshness and flavor and beautifully
                        arranged by the experts in our Produce department.

                        </div>
                        <div class=\'quantbox\'>
                        <strong>$69.95</strong><br>Large<br><i>18" Round<br>Serves 12 - 15</i><br>
                            <input type=\'number\' name=\'id202\' id=\'qty202\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id202'] > 0) $ret .= 'value="' . $_GET['id202'] . '" '; 
                              $ret .= '><br>
                        </div>
                        <div class=\'quantbox\'>
                          <strong>$39.95</strong><br>Small<br><i>12" Round<br>Serves 8 - 12</i><br>
                            <input type=\'number\' name=\'id201\' id=\'qty201\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id201'] > 0) $ret .= 'value="' . $_GET['id201'] . '" '; 
                              $ret .= '><br>
                        </div>
                    </div>
                    
                    <div class=\'longdescbox\'>
                        <div class=\'title\'>
                            Fruit Tray
                        </div>
                        <div class=\'desc\'>
                        Each tray includes a variety of seasonal fruit that
                        are hand-selected for maximum freshness and flavor and beautifully
                        arranged by the experts in our Produce department.
                        </div>
                        <div class=\'quantbox\'>
                        <strong>$69.95</strong><br>Large<br><i>18" Round<br>Serves 12 - 15</i><br>
                            <input type=\'number\' name=\'id204\' id=\'qty204\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id204'] > 0) $ret .= 'value="' . $_GET['id204'] . '" '; 
                              $ret .= '><br>
                        </div>
                        <div class=\'quantbox\'>
                          <strong>$39.95</strong><br>Small<br><i>12" Round<br>Serves 8 - 12</i><br>
                            <input type=\'number\' name=\'id203\' id=\'qty203\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id203'] > 0) $ret .= 'value="' . $_GET['id203'] . '" '; 
                              $ret .= '><br>
                        </div>
                    </div>
                    
                    <div class=\'longdescbox\'>
                        <div class=\'title\'>
                            Fruit & Vegetable Tray
                        </div>
                        <div class=\'desc\'>
                        Each tray includes a variety of seasonal fruit & vegetables that
                        are hand-selected for maximum freshness and flavor and beautifully
                        arranged by the experts in our Produce department.
                        </div>
                        <div class=\'quantbox\'>
                        <strong>$69.95</strong><br>Large<br><i>18" Round<br>Serves 12 - 15</i><br>
                            <input type=\'number\' name=\'id206\' id=\'qty206\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id206'] > 0) $ret .= 'value="' . $_GET['id206'] . '" '; 
                              $ret .= '><br>
                        </div>
                        <div class=\'quantbox\'>
                          <strong>$39.95</strong><br>Small<br><i>12" Round<br>Serves 8 - 12</i><br>
                            <input type=\'number\' name=\'id205\' id=\'qty205\' class=\'form-control form-qty\' min=\'0\'';
                            if ($_GET['id205'] > 0) $ret .= 'value="' . $_GET['id205'] . '" '; 
                              $ret .= '><br>
                        </div>
                    </div>
                    
                   
        </div>
</div>
</div>

        
        <!-- Sandwiches -->
        <div role="tabpanel" class="tab-pane" id="sandwiches">
            
<div class="container">            
<div class="panel panel-default smpanel"><div class="panel-heading" align="center"><b>Fog City Deli Sandwich Platters</b><br><br><i>Choose up to 2 options per platter. No 
                    substitutions, please.</i></div>                
                
            <div class=\'longerdescbox\'>
                <div class=\'title\'>
                    Croissant Platter
                </div>
                <div class=\'desc\'>
                    Flaky croissants, served with fresh spinach 
                    and sprouts and filled with your choice of our 
                    own Fog City Chicken Salad, Fog City Egg Salad, 
                    or Black Forest ham and Swiss with Dijon.
                </div>
                <div class=\'quantbox\'>
                <strong>$69.99</strong><br>Large<br><i>Serves 12</i><br>
                    <input type="radio" class="form-control" name="id301" value="LG"';
                if ($_GET['id301'] == "LG") $ret .= ' checked><br><br>';
                    else $ret .= '><br><br>';
        $ret .= '
                </div>
                <div class=\'quantbox\'>
                  <strong>$34.99</strong><br>Small<br><i>Serves 6</i><br>
                    <input type="radio" class="form-control" name="id301" id="qty301" value="SM"';
                    if ($_GET['id301'] == "SM") $ret .= ' checked><br>';
                        else $ret .= '><br>';
        $ret .= '
                </div>
           
                <div class="options">
                    <input type="checkbox" value="Chicken Salad" name =\'op3011\'';
                    if ($_GET['op3011'] == 'Chicken Salad') $ret .= 'checked="checked" ';
                      $ret .= '> Chicken Salad<br>
                    <input type="checkbox" value="Egg Salad" name =\'op3012\'';
                    if ($_GET['op3012'] == 'Egg Salad') $ret .= 'checked="checked" ';
                      $ret .= '> Egg Salad<br>
                    <input type="checkbox" value="Black Forest Ham" name =\'op3013\'';
                    if ($_GET['op3013'] == 'Black Forest Ham') $ret .= 'checked="checked" ';
                      $ret .= '> Black Forest Ham<br>
                    <input type="checkbox" value="Swiss & Dijon" name =\'op3014\'';
                    if ($_GET['op3014'] == 'Swiss & Dijon') $ret .= 'checked="checked" ';
                      $ret .= '> Swiss & Dijon<br>
                </div>
            </div>
            
            <div class=\'longerdescbox\'>
                <div class=\'title\'>
                    Sandwich Wrap
                </div>
                <div class=\'desc\'>
                    Your choice of buffalo chicken, hummus veggie, 
                    smoked turkey chipotle, BLT, maple chicken wild 
                    rice, Greek veggie, or veggie deluxe.
                </div>
                <div class=\'quantbox\'>
                <strong>$69.99</strong><br>Large<br><i>Serves 12</i><br>
                    <input type="radio" class="form-control" name="id303" value="LG"';
                if ($_GET['id303'] == "LG") $ret .= ' checked><br><br>';
                    else $ret .= '><br><br>';
        $ret .= '
                </div>
                <div class=\'quantbox\'>
                  <strong>$34.99</strong><br>Small<br><i>Serves 6</i><br>
                    <input type="radio" class="form-control" name="id303" id="qty303" value="SM"';
                    if ($_GET['id303'] == "SM") $ret .= ' checked><br>';
                        else $ret .= '><br>';
        $ret .= '
                </div>
           
                <div class="options">
                     <input type="checkbox"  value="Buffalo Chicken" name =\'op3031\'';
                    if ($_GET['op3031'] == 'Buffalo Chicken') $ret .= 'checked="checked" ';
                      $ret .= '> Buffalo Chicken<br>   
                    <input type="checkbox" value="Hummus Veggie" name =\'op3032\'';
                    if ($_GET['op3032'] == 'Hummus Veggie') $ret .= 'checked="checked" ';
                      $ret .= '> Hummus Veggie<br>
                    <input type="checkbox" value="Smoked Turkey" name =\'op3033\'';
                    if ($_GET['op3033'] == 'Smoked Turkey') $ret .= 'checked="checked" ';
                      $ret .= '> Smoked Turkey<br>
                    <input type="checkbox" value="B.L.T." name =\'op3034\'';
                    if ($_GET['op3034'] == 'B.L.T.') $ret .= 'checked="checked" ';
                      $ret .= '> B.L.T.<br>
                    <input type="checkbox" value="Maple Chicken Wild Rice" name =\'op3035\'';
                    if ($_GET['op3035'] == 'Maple Chicken Wild Rice') $ret .= 'checked="checked" ';
                      $ret .= '> Maple Chicken Wild Rice<br>
                    <input type="checkbox" value="Greek Veggie" name =\'op3036\'';
                    if ($_GET['op3036'] == 'Greek Veggie') $ret .= 'checked="checked" ';
                      $ret .= '> Greek Veggie<br>
                    <input type="checkbox" value="Veggie Delux" name =\'op3037\'';
                    if ($_GET['op3037'] == 'Veggie Delux') $ret .= 'checked="checked" ';
                      $ret .= '> Veggie Delux<br>
                </div>
            </div>
                
            <div class=\'longerdescbox\'>
                <div class=\'title\'>
                   Traditional Sandwich Platter
                </div>
                <div class=\'desc\'>
                    Ham and Swiss on marble rye, smoked turkey 
                    and cheddar on sourdough, roast beef and 
                    cheddar on whole wheat. Mayo and Dijon mustard 
                    on the side.
                </div>
                <div class=\'quantbox\'>
                <strong>$69.99</strong><br>Large<br><i>Serves 12</i><br>
                    <input type="radio" class="form-control" name="id305" value="LG"';
                if ($_GET['id305'] == "LG") $ret .= ' checked><br><br>';
                    else $ret .= '><br><br>';
        $ret .= '
                </div>
                <div class=\'quantbox\'>
                  <strong>$34.99</strong><br>Small<br><i>Serves 6</i><br>
                    <input type="radio" class="form-control" name="id305" id="qty305" value="SM"';
                    if ($_GET['id305'] == "SM") $ret .= ' checked><br>';
                        else $ret .= '><br>';
        $ret .= '
                </div>
           
                <div class="options">
                     <input type="checkbox" value="Ham & Swiss on Marble Rye" name =\'op3051\'';
                    if ($_GET['op3051'] == 'Ham & Swiss on Marble Rye') $ret .= 'checked="checked" ';
                      $ret .= '> Ham & Swiss on Marble Rye<br>   
                    <input type="checkbox" value="Smoked Turkey & Cheddar on Sourdough" name =\'op3052\'';
                    if ($_GET['op3052'] == 'Smoked Turkey & Cheddar on Sourdough') $ret .= 'checked="checked" ';
                      $ret .= '> Smoked Turkey & Cheddar on Sourdough<br>
                    <input type="checkbox" value="Roast Beef & Cheddar on Whole Wheat" name =\'op3053\'';
                    if ($_GET['op3053'] == 'Roast Beef & Cheddar on Whole Wheat') $ret .= 'checked="checked" ';
                      $ret .= '> Roast Beef & Cheddar on Whole Wheat<br>
                </div>
             </div>             
</div>            
</div>

<div class="container">
<div class="panel panel-default smpanel"><div class="panel-heading" align="center"><b>Build-Your-Own Sandwich Platters</b><br><br><i>A variety of sandwich toppings, including vegetables and condiments. Assorted bread, rolls and wraps. Vegan and wheat free available upon request.</i></div>     
             
             <div class=\'longerdescbox\'>
                <div class=\'title\'>
                    B.Y.O. Meat & Cheese
                </div>
                <div class=\'desc\'>
                    Black Forest ham, smoked turkey, roast beef, sliced 
                    Swiss, sharp cheddar, and provolone, tomato, lettuce 
                    and onion. Served with Lou Pistou olives.
                </div>
                <div class=\'quantbox\'>
                <strong>$69.99</strong><br>Large<br><i>Serves 16 - 20</i><br>
                    <input type="radio" class="form-control" name="id307" value="LG"';
                if ($_GET['id307'] == "LG") $ret .= ' checked><br><br>';
                    else $ret .= '><br><br>';
        $ret .= '
                </div>
                <div class=\'quantbox\'>
                  <strong>$34.99</strong><br>Small<br><i>Serves 8 - 10</i><br>
                    <input type="radio" class="form-control" name="id307" id="qty307" value="SM"';
                    if ($_GET['id307'] == "SM") $ret .= ' checked><br>';
                        else $ret .= '><br>';
        $ret .= '
                </div>
           
                <div class="options">
                    <input type="checkbox" value="Black Forest Ham" name =\'op3071\'';
                    if ($_GET['op3071'] == 'Black Forest Ham') $ret .= 'checked="checked" ';
                      $ret .= '> Black Forest Ham<br>   
                    <input type="checkbox" value="Smoked Turkey" name =\'op3072\'';
                    if ($_GET['op3072'] == 'Smoked Turkey') $ret .= 'checked="checked" ';
                      $ret .= '> Smoked Turkey<br>
                    <input type="checkbox" value="Roast Beef" name =\'op3073\'';
                    if ($_GET['op3073'] == 'Roast Beef') $ret .= 'checked="checked" ';
                      $ret .= '> Roast Beef<br>
                    <input type="checkbox" value="Sliced Swiss" name =\'op3074\'';
                    if ($_GET['op3074'] == 'Sliced Swiss') $ret .= 'checked="checked" ';
                      $ret .= '> Sliced Swiss<br>   
                    <input type="checkbox" value="Sharp Cheddar" name =\'op3075\'';
                    if ($_GET['op3075'] == 'Sharp Cheddar') $ret .= 'checked="checked" ';
                      $ret .= '> Sharp Cheddar<br>
                    <input type="checkbox" value="Provolone" name =\'op3076\'';
                    if ($_GET['op3076'] == 'Provolone') $ret .= 'checked="checked" ';
                      $ret .= '> Provolone<br>
                    <input type="checkbox" value="Tomato" name =\'op3077\'';
                    if ($_GET['op3077'] == 'Tomato') $ret .= 'checked="checked" ';
                      $ret .= '> Tomato<br>   
                    <input type="checkbox" value="Lettuce" name =\'op3078\'';
                    if ($_GET['op3078'] == 'Lettuce') $ret .= 'checked="checked" ';
                      $ret .= '> Lettuce<br>
                    <input type="checkbox" value="Onion" name =\'op3079\'';
                    if ($_GET['op3079'] == 'Onion') $ret .= 'checked="checked" ';
                      $ret .= '> Onion<br>
                </div>
             </div>
             
             <br><br><br><br>
             
             <div class=\'longerdescbox\'>
                <div class=\'title\'>
                    B.Y.O. Veggies & Cheese
                </div>
                <div class=\'desc\'>
                    Sliced Swiss, sharp cheddar, feta, cucumber, shredded carrot, 
                    falafel, shredded beets, hummus.
                </div>
                <div class=\'quantbox\'>
                <strong>$59.99</strong><br>Large<br><i>Serves 16 - 20</i><br>
                    <input type="radio" class="form-control" name="id309" value="LG"';
                if ($_GET['id309'] == "LG") $ret .= ' checked><br><br>';
                    else $ret .= '><br><br>';
        $ret .= '
                </div>
                <div class=\'quantbox\'>
                  <strong>$29.99</strong><br>Small<br><i>Serves 8 - 10</i><br>
                    <input type="radio" class="form-control" name="id309" id="qty309" value="SM"';
                    if ($_GET['id309'] == "SM") $ret .= ' checked><br>';
                        else $ret .= '><br>';
        $ret .= '
                </div>
           
                <div class="options">
                    <input type="checkbox" value="Sliced Swiss" name =\'op3091\'';
                    if ($_GET['op3091'] == 'Sliced Swiss') $ret .= 'checked="checked" ';
                      $ret .= '> Sliced Swiss<br>   
                    <input type="checkbox" value="Sharp Cheddar" name =\'op3092\'';
                    if ($_GET['op3092'] == 'Sharp Cheddar') $ret .= 'checked="checked" ';
                      $ret .= '> Sharp Cheddar<br>
                    <input type="checkbox" value="Feta" name =\'op3093\'';
                    if ($_GET['op3093'] == 'Feta') $ret .= 'checked="checked" ';
                      $ret .= '> Feta<br>
                    <input type="checkbox" value="Cucumber" name =\'op3094\'';
                    if ($_GET['op3094'] == 'Cucumber') $ret .= 'checked="checked" ';
                      $ret .= '> Cucumber<br>   
                    <input type="checkbox" value="Shredded Carrot" name =\'op3095\'';
                    if ($_GET['op3095'] == 'Shredded Carrot') $ret .= 'checked="checked" ';
                      $ret .= '> Shredded Carrot<br>
                    <input type="checkbox" value="Falafel" name =\'op3096\'';
                    if ($_GET['op3096'] == 'Falafel') $ret .= 'checked="checked" ';
                      $ret .= '> Falafel<br>
                    <input type="checkbox" value="Shredded Beets" name =\'op3097\'';
                    if ($_GET['op3097'] == 'Shredded Beets') $ret .= 'checked="checked" ';
                      $ret .= '> Shredded Beets<br>   
                    <input type="checkbox" value="Hummus" name =\'op3098\'';
                    if ($_GET['op3098'] == 'Hummus') $ret .= 'checked="checked" ';
                      $ret .= '> Hummus<br>
                    <input type="checkbox" value="Onion" name =\'op3099\'';
                    if ($_GET['op3099'] == 'Onion') $ret .= 'checked="checked" ';
                      $ret .= '> Onion<br>
                </div>
             </div>
             
             <br><br><br><br>
             
            <div class=\'longerdescbox\'>
                    <div class=\'title\'>
                        Boxed Lunches
                    </div>
                    <div class=\'desc\'>
                        Your choice of ham and Swiss on rye, smoked turkey 
                        or roast beef with cheddar on sourdough with fresh 
                        fruit, chocolate chip cookie and a bag of Kettle 
                        Chips and a pickle. Minimum order of 6, please.
                    </div>
                    
                    <div class=\'quantbox\'>
                      <strong>$9.99</strong><br><i>Serves 1</i><br>
                        <input type=\'number\' name=\'id311\' id=\'qty311\' class=\'form-control form-qty\' min=\'0\'';
                        if ($_GET['id311'] > 0) $ret .= 'value="' . $_GET['id311'] . '" '; 
                          $ret .= '><br>
                    </div> 
                
                <div class="options">
                    <input type="checkbox" value="Ham and Swiss on Rye" name =\'op3111\'';
                    if ($_GET['op3111'] == 'Ham and Swiss on Rye') $ret .= 'checked="checked" ';
                      $ret .= '> Ham and Swiss on Rye<br>   
                    <input type="checkbox" value="Smoked Turkey with Cheddar on Sourdough" name =\'op3112\'';
                    if ($_GET['op3112'] == 'Smoked Turkey with Cheddar on Sourdough') $ret .= 'checked="checked" ';
                      $ret .= '> Smoked Turkey with Cheddar on Sourdough<br>
                    <input type="checkbox" value="Roast Beef with Cheddar on Sourdough" name =\'op3113\'';
                    if ($_GET['op3113'] == 'Roast Beef with Cheddar on Sourdough') $ret .= 'checked="checked" ';
                      $ret .= '> Roast Beef with Cheddar on Sourdough<br>
                </div>
            </div>
            
            <br><br><br><br>
</div>            
</div>
             
        </div>
        
        <!-- Entrees -->
        <div role="tabpanel" class="tab-pane" id="entrees">
<div class="container">            
<div class="panel panel-default smpanel"><div class="panel-heading" align="center"><b>Entrees</b><br><br>
    <i>All Entrees available hot or cold. Please specify when ordering. <br>Vegan and made
    without wheat available upon request.</i></div>                


                
            <div class=\'descbox\'>
                <div class=\'title\'>
                    Lasagna
                </div>
                <div class=\'desc\'>
                    Traditional lasagna layered with pasta, marinara sauce, 
                    Italian sausage, mozzarella, provolone, and Parmesan.
                </div>
                <div class=\'quantbox\'>
                  <strong>$39.99</strong><br><i>Serves 8 - 10</i><br>
                    <input type=\'number\' name=\'id401\' id=\'qty401\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id401'] > 0) $ret .= 'value="' . $_GET['id401'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
            
            <div class=\'descbox\'>
                <div class=\'title\'>
                    Veggie Lasagna
                </div>
                <div class=\'desc\'>
                    Pasta layered with zucchini, peppers, mushrooms, onions, 
                    spinach, and fresh herbs in a marinara sauce with mozzarella, 
                    provolone, and Parmesan.
                </div>
                <div class=\'quantbox\'>
                  <strong>$34.99</strong><br><i>Serves 8 - 10</i><br>
                    <input type=\'number\' name=\'id402\' id=\'qty402\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id402'] > 0) $ret .= 'value="' . $_GET['id402'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
            
            <div class=\'enchiladadescbox\'>
                <div class=\'title\'>
                    Classic Enchiladas
                </div>
                <div class=\'desc\'>
                    Corn tortillas filled with cheese, onion, olives, tomatoes 
                    and spinach. Topped with a smoky chipotle sauce and 
                    smothered in Monterey Jack and cheddar.
                </div>
                <div class=\'quantbox\'>
                  <strong>$44.99</strong><br><i>With<br>Chicken</i><br>
                    <input type=\'number\' name=\'id404\' id=\'qty404\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id404'] > 0) $ret .= 'value="' . $_GET['id404'] . '" '; 
                      $ret .= '><br>
                </div> 
                <div class=\'quantbox\'>
                  <strong>$44.99</strong><br><i>With<br> Ground Beef</i><br>
                    <input type=\'number\' name=\'id405\' id=\'qty405\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id405'] > 0) $ret .= 'value="' . $_GET['id405'] . '" '; 
                      $ret .= '><br>
                </div>
                <div class=\'quantbox\'>
                  <strong>$39.99</strong><br><i>Original<br>Serves 8 - 10</i><br>
                    <input type=\'number\' name=\'id403\' id=\'qty403\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id403'] > 0) $ret .= 'value="' . $_GET['id403'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
    
            
            <div class=\'longdescbox\'>
                <div class=\'title\'>
                    Wild Rice Hotdish
                </div>
                <div class=\'desc\'>
                    A Minnesota classic, made with celery, onion and mushrooms. 
                    Want to add chicken? You betcha! Vegan option available.
                </div>
                <div class=\'quantbox\'>
                <strong>$44.99</strong><br><i>With<br>Chicken</i><br>
                    <input type=\'number\' name=\'id406\' id=\'qty406\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id406'] > 0) $ret .= 'value="' . $_GET['id406'] . '" '; 
                      $ret .= '><br>
                </div>
                <div class=\'quantbox\'>
                  <strong>$39.99</strong><br><i>Original<br>Serves 8 - 10</i><br>
                    <input type=\'number\' name=\'id407\' id=\'qty407\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id407'] > 0) $ret .= 'value="' . $_GET['id407'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
        </div>
</div>        
</div>        
        
        <!-- BAKED GOODS -->
        <div role="tabpanel" class="tab-pane" id="baked">
<div class="container">            
<div class="panel panel-default smpanel"><div class="panel-heading" align="center"><b>Baked Goods</b></div>                
            
            <div class=\'descbox\'>
                <div class=\'title\'>
                    Carrot Cake
                </div>
                <div class=\'desc\'>
                    A delicious made from scratch favorite with 
                    succulent cream cheese frosting. Vegan also 
                    available.
                </div>
                <div class=\'quantbox\'>
                  <strong>$35.99</strong><br><i>9 x 13"<br>Serves 12</i><br>
                    <input type=\'number\' name=\'id501\' id=\'qty501\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id501'] > 0) $ret .= 'value="' . $_GET['id501'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
            
            <div class=\'descbox\'>
                <div class=\'title\'>
                    Vegan Chocolate Cake
                </div>
                <div class=\'desc\'>
                    Our version of the traditional chocolate 
                    cake coated with delicious ganache and all vegan!
                </div>
                <div class=\'quantbox\'>
                  <strong>$29.99</strong><br><i>9 x 13"<br>Serves 12</i><br>
                    <input type=\'number\' name=\'id502\' id=\'qty502\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id502'] > 0) $ret .= 'value="' . $_GET['id502'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
            
            <div class=\'descbox\'>
                <div class=\'title\'>
                    Flourless Chocolate Cake
                </div>
                <div class=\'desc\'>
                    A rich, decadent chocolate cake made without wheat.
                </div>
                <div class=\'quantbox\'>
                  <strong>$35.99</strong><br><i>9" round<br>Serves 12 - 14</i><br>
                    <input type=\'number\' name=\'id503\' id=\'qty503\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id503'] > 0) $ret .= 'value="' . $_GET['id503'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
            
            <div class=\'descbox\'>
                <div class=\'title\'>
                    Cheesecake
                </div>
                <div class=\'desc\'>
                    Your choice of strawberry, blueberry, blackberry, 
                    raspberry, vanilla, lemon or lime.
                </div>
                <div class=\'quantbox\'>
                  <strong>$39.99</strong><br><i>9" round<br>Serves 12 - 14</i><br>
                    <input type=\'number\' name=\'id504\' id=\'qty504\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id504'] > 0) $ret .= 'value="' . $_GET['id504'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
            
            <div class=\'descbox\'>
                <div class=\'title\'>
                    Cookie Trays
                </div>
                <div class=\'desc\'>
                    An assortment of 18 cookies. Ask our expert 
                    bakers for today\'s selection.
                </div>
                <div class=\'quantbox\'>
                  <strong>$18.99</strong><br><i>9" round<br>Serves 12 - 16</i><br>
                    <input type=\'number\' name=\'id505\' id=\'qty505\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id505'] > 0) $ret .= 'value="' . $_GET['id505'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
            
            <div class=\'longdescbox\'>
                <div class=\'title\'>
                    St. Paul Bagelry Assortment
                </div>
                <div class=\'desc\'>
                    Served with flavored cream cheese. Ask our deli 
                    staff for today\'s selection.
                </div>
                <div class=\'quantbox\'>
                  <strong>$28.99</strong><br><i>16 bagels</i><br>
                    <input type=\'number\' name=\'id507\' id=\'qty507\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id507'] > 0) $ret .= 'value="' . $_GET['id507'] . '" '; 
                      $ret .= '><br>
                </div> 
                <div class=\'quantbox\'>
                <strong>$14.99</strong><br><i>8 bagels</i><br>
                    <input type=\'number\' name=\'id506\' id=\'qty506\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id506'] > 0) $ret .= 'value="' . $_GET['id506'] . '" '; 
                      $ret .= '><br>
                </div>
            </div>
            
            <div class=\'descbox\'>
                <div class=\'title\'>
                    Mini Muffin Tray
                </div>
                <div class=\'desc\'>
                    An assortment of our made fresh daily 
                    muffins, but in bite size form
                </div>
                <div class=\'quantbox\'>
                  <strong>$23.99</strong><br><i>36 count</i><br>
                    <input type=\'number\' name=\'id508\' id=\'qty508\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id508'] > 0) $ret .= 'value="' . $_GET['id508'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
            
            <div class=\'descbox\'>
                <div class=\'title\'>
                    Coffee To Go
                </div>
                <div class=\'desc\'>
                    Ask our deli staff for today\'s selection.
                </div>
                <div class=\'quantbox\'>
                  <strong>$15.99</strong><br><i>96 oz.<br>Serves 12</i><br>
                    <input type=\'number\' name=\'id509\' id=\'qty509\' class=\'form-control form-qty\' min=\'0\'';
                    if ($_GET['id509'] > 0) $ret .= 'value="' . $_GET['id509'] . '" '; 
                      $ret .= '><br>
                </div> 
            </div>
            
        </div>
</div>
</div>
        
        <!-- Checkout Order -->
        <div role="tabpanel" class="tab-pane" id="checkout">
            <div class="form-inline">
        ';
        
        if (!$_GET['card_no']) $ret .= '
            <div class="alert alert-danger" align="center">
                Click <i>Update Order</i> to review order.<br>
                <i>Customer Information must be filled out.</i> 
            </div>
        ';
        $ret .= '<div class="container">';        

        $data = array();
        $opts = 1;
        
        if ($_GET['name']) $data['name'] = $_GET['name'];
        if ($_GET['card_no']) $data['card_no'] = $_GET['card_no'];
        if ($_GET['phone']) $data['phone'] = $_GET['phone'];
        if ($_GET['alt_phone']) $data['alt_phone'] = $_GET['alt_phone'];
        if ($_GET['email']) $data['email'] = $_GET['email'];
        if ($_GET['member']) $data['member'] = $_GET['member'];
        
        foreach ($_GET as $key => $value) {
            if ($value > 0 && substr($key,0,2) === 'id') {
                $thisUPC = substr($key,2,3);
                $data[$key][$thisUPC] = $value;
            } elseif ($value === 'LG') {
                $thisUPC = substr($key,2,3) + 1;
                $data[$key][$thisUPC] = 1;
            } elseif ($value === 'SM') {
                $thisUPC = substr($key,2,3);
                $data[$key][$thisUPC] = 1;    
            } elseif (substr($key,0,2) === 'op') {
                $thisUPC = substr($key,2,3);
                $thisID = 'id' . $thisUPC;
                if($_GET[$thisID] === 'LG') {
                    $thisUPC++;
                    $data[$key][$thisUPC][$opts] = $value;
                } else {
                    $data[$key][$thisUPC][$opts] = $value;
                }
            }
        }
             
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $ret .= '<br><br><h3>Review Order</h3><br>';
        
        
        $ret .= '<table class="table">';
        $ret .= '<tr><td width="150 px"><b>Owner Number:</b></td><td>' . $data['card_no'] . '</td>';
        $ret .= '<tr><td ><b>Customer Name:</b></td><td>' . $data['name'] . '</td>';
        $ret .= '<tr><td ><b>Phone Number:</b></td><td>' . $data['phone'] . '</td>';
        $ret .= '<tr><td ><b>Alternate Phone:</b></td><td>' . $data['alt_phone'] . '</td>';
        $ret .= '<tr><td ><b>Email Address:</b></td><td>' . $data['email'] . '</td>';
        $ret .= '</table>';
        
        $ret .= '<table class="table">';
        $ret .= '<th>Product</th><th>Quantity</th><th>Unit Price</th><th>Subtotal</th>';
        $curTotal = 0;
        $taxTotal = 0;
        foreach ($data as $fullUPC => $array) {
            foreach ($array as $UPC => $qty) {
                if (!is_array($qty) && $qty > 0) {
                    //THIS I MY QUERY FOR UPC => PRICE
                    $query = $dbc->prepare("
                        SELECT 
                            name, 
                            price
                        FROM DeliCateringItems
                        WHERE upc = {$UPC}
                        ;
                        ");
                    $result = $dbc->execute($query);
                    while($row = $dbc->fetch_row($result)){
                        $curTotal += $row['price'] * $qty;
                        $ret .= '<tr><td>' . $row['name'] . '</td><td>' .  $qty . '</td><td>$' . $row['price'] . 
                            '</td><td>' . $curTotal . '</tr>';  
                    }
                } elseif (is_array($qty)) {
                    foreach ($qty as $qtyB => $option) {
                        $ret .= '<tr><td> ~~' . $option . ' </td></tr>';
                    }
                }
            }
        }
        
        
        
        //$ret .= '<tr><td></td><td></td><td><b>Final Subtotal:</b></td><td>' . $curTotal . '</tr>';
        //  IF total exceeds 150 and user IS an owner - then 10% discount applies here
        if ($curTotal > 149.99 && $_GET['member'] == 1) {
            //Add IF IS OWNER
            $ret .= '<tr><td></td><td></td><td><b>(10%) Owner Discount:</b></td><td>' . 
                sprintf("-%0.2f",$curTotal * .10);
                $curTotal = $curTotal - ($curTotal * .10);
        }
        //  IF total exceeds 150, calculate the 25% deposit here
        $taxTotal = 0.10625 * $curTotal;
        $ret .= '<tr><td></td><td></td><td><b>Total with Tax:</b></td><td>$' . sprintf("%0.2f",($curTotal + $taxTotal)) . '</tr>';
        
        if ($curTotal > 149.99) {
            $deposit = $curTotal * 0.25;
            $ret .= '<tr><td></td><td></td><td><b>(25%) Required Deposit:</b></td><td>$' . sprintf("%0.2f",$deposit) . '</tr>'   ;
        }
        
        $ret .= '</table>';
        $ret .= '
            </div></div>
            <div class="container">
            <br><b>Comments / Special Instructions</b>
            <br><textarea class="form-control" name="notes" form="orderform">';
            if ($_GET['notes']) $ret .= $_GET['notes']; 
                              $ret .= '</textarea><br><br>
            <input type="submit" class="btn btn-info" name="confirm" id="confirm" value="Place This Order"><br><br>
        ';
                
        $ret .='
        </div>
      </div>
    </form>
        ';
        
        return $ret;
    }
    
    public function get_view()
    {
        
        $ret = "";
        
        ?>
        <br>
        <form method="get" class="form-inline">
            <input type="submit" class="form-control" name="id" value="Create New Order"><br>   
        </form><br>
        
        <?php
        
        $dbc = FannieDB::get($this->config->get('OP_DB'));
                
        $query = $dbc->prepare("
                SELECT 
                    order_num,
                    name,
                    email,
                    phone,
                    alt_phone, 
                    order_date,
                    status,
                    card_no
                FROM DeliCateringCustomerInfo 
                WHERE status = 0
            ;
            ");
        $result = $dbc->execute($query);
        $ret .= "<table class='table'>";
        $ret .= "
            <thead><b>Active Orders</b></thead>
            <th>Order Date</th>
            <th>Owner #</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Order #</th>
            <th>Status</th>
            <th>View Order</th>
        ";
        $ret .= '<form method="get">';
        while($row = $dbc->fetch_row($result)){
            $ret .= '<tr><td>' . substr($row['order_date'], 0, 11) . '</td>';
            $ret .= '<td>' . $row['card_no'] . '</td>';
            $ret .= '<td>' . $row['name'] . '</td>';
            $ret .= '<td>' . $row['phone'] . '</td>';
            $ret .= '<td>' . $row['email'] . '</td>';
            $ret .= '<td>' . $row['order_num'] . '</td>';
            $ret .= '<td>new order</td>';
            $ret .= '<td><input type="submit" name="review" value="' . $row['order_num'] . '"  class="btn btn-default"></td>';
            
        }      
        $ret .= "</table>";
        
        return $ret;
    }
    
    public function javascriptContent()
    {
        ob_start();
        ?>
function autoFill()
{
    var x = document.getElementById("orderform");
    var card_no = x.elements[4].value;
    $.ajax({
        type: 'get',
        url: 'DeliCateringAjax.php',
        dataType: 'json',
        data: 'card_no='+card_no,
        error: function(xhr, status, error)
        { 
            alert('error:' + status + ':' + error + ':' + xhr.responseText) 
        },
        success: function(response)
        {
        }
    })
    .done(function(data){
        if (data.name) {
            $('#name').val(data.name);
        }
        if (data.phone) {
            $('#phone').val(data.phone);
        }
        if (data.altPhone) {
            $('#altPhone').val(data.altPhone);
        }
        if (data.email) {
            $('#email').val(data.email);
        }
    })
}
        <?php
        return ob_get_clean();
    }
   
}

FannieDispatch::conditionalExec();
