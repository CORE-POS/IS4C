<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('wfcuRegistryModel')) {
    include_once($FANNIE_ROOT.'modules/plugins2.0/wfcu2/wfcuRegistryModel.php');
}

/**
  @class HouseCouponEditor
*/
class wfcu2 extends FanniePage 
{

    public $description = "[Module] for managing WFC-U Class Sign-In";
    public $themed = true;

    protected $must_authenticate = true;
    protected $auth_classes = array('tenders');

    protected $header = "Fannie :: WFC-U Class Registry";
    protected $title = "WFC Class Sign-in";

    private $display_function;
    private $coupon_id;
    private $plu;

    public function preprocess()
    {
        $this->display_function = 'listClasses';
        
        return true;
    }

    public  function body_content()
    {
        $func = $this->display_function;

        return $this->$func();
    }

    private function listClasses()
    {
        $FANNIE_URL = $this->config->get('URL');
        echo "<div id=\"line-div\"></div>";
        
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $query = $dbc->prepare("
            SELECT 
                pu.description, 
                p.upc,
                p.size
            FROM products AS p 
                LEFT JOIN productUser AS pu ON pu.upc=p.upc 
            WHERE p.description LIKE 'class -%' 
                    AND p.inUse=1
            ORDER BY pu.description DESC;
            ");
        $result = $dbc->execute($query);
        while($row = $dbc->fetch_row($result)){
            $className[] = substr($row['description'], 11, 100);
            $classUPC[] = substr($row['upc'], 5, 13);
            $classDate[] = substr($row['description'], 0, 8);
            $classSize[] = $row['size'];
        }
        
        $ret .= '<div class=\'container\'><form method=\'get\'><select class=\'form-control\' name=\'class_plu\'>';
        $ret .= '<option value=\'1\'>Choose a class...</option>';
        foreach ($className as $key => $name) {
            $ret .= '<option value=\'' . $key . '\'>' . $classDate[$key] . " :: " . $name . '</option>';
        }
        
        $ret .= '<input class=\'btn btn-default\' type=\'submit\' value=\'Open Class Registry\'>';
        $ret .= '</select></form></div>';
        
        $key = $_GET['class_plu'];
        $plu = $classUPC[$key];
        $this->plu = $classUPC[$key];
        
        //* Create table if it doesn't exist
        $p1 = $dbc->prepare("CREATE TABLE IF NOT EXISTS
            wfcuRegistry (
                id INT(6) PRIMARY KEY AUTO_INCREMENT,
                upc VARCHAR(13), 
                class VARCHAR(255), 
                first_name VARCHAR(30),
                last_name VARCHAR(30),
                phone VARCHAR(30),
                opt_phone VARCHAR(30),
                card_no INT(11),
                payment VARCHAR(30),
                refunded INT(1),
                modified DATETIME,
                store_id SMALLINT(6),
                start_time TIME,
                date_paid DATETIME,
                seat INT(50),
                seatType INT(5),
                details TEXT
            );   
        ");
        $r1 = $dbc->execute($p1);
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        }
        
        //* Populate Seats
        $pCheck = $dbc->prepare("
            SELECT count(seat)
            FROM wfcuRegistry
            WHERE upc = {$plu}
                AND seatType=1
        ;");
        $rCheck = $dbc->execute($pCheck);
        while ($row = $dbc->fetch_row($rCheck)) {
            $numSeats = $row['count(seat)'];
        }
        $pCheck = $dbc->prepare("
            SELECT size
            FROM products
            WHERE upc = {$plu}
        ;");
        $rCheck = $dbc->execute($pCheck);
        while ($row = $dbc->fetch_row($rCheck)) {
            $classSize = $row['size'];
        }
        
        $sAddSeat = "INSERT INTO wfcuRegistry (upc, seat, seatType) VALUES ";
        for ($i=$numSeats; $i<$classSize; $i++) {
                    $sAddSeat .= " ( " . $plu . ", " . ($i+1) . ", 1) ";
                    if (($i+1)<$classSize) {
                        $sAddSeat .= ", ";
                    }
        }
        if ($numSeats != $classSize) {
            $pAddSeat = $dbc->prepare("{$sAddSeat}");  
            $rAddSeat = $dbc->execute($pAddSeat);
        }
        
        $prep = $dbc->prepare("SELECT count(seat) FROM wfcuRegistry WHERE seatType=0 AND upc={$plu};");
        $resp = $dbc->execute($prep);
        while ($row = $dbc->fetch_row($resp)) {
            $waitSize = $row['count(seat)'];
        }
        if ($waitSize == 0 || !isset($waitSize)) {
            $prep = $dbc->prepare("INSERT INTO wfcuRegistry (upc, seat, seatType) VALUES ({$plu}, 1, 0);");
            $resp = $dbc->execute($prep);
        }
        
        $prep = $dbc->prepare("SELECT seat, first_name FROM wfcuRegistry WHERE seatType=0;");
        $resp = $dbc->execute($prep);
        while ($row = $dbc->fetch_row($resp)) {
            $name = $row['first_name'];
        }
        $nextSeat = ($waitSize + 1);
        if ($name) {
            $prep = $dbc->prepare("INSERT INTO wfcuRegistry (upc, seat, seatType) VALUES ({$plu}, {$nextSeat}, 0);");
            $resp = $dbc->execute($prep);
        }
        
        if ($key) {
            
            $ret .= "<h2 align=\"center\">" . $className[$key] . "</h2>";
            $ret .= "<h3 align=\"center\">" . $classDate[$key] . "</h3>";
            $ret .= "<h5 align=\"center\"> Plu for this class: " . $plu . "</h5>";
            $ret .= "<div id=\"line-div\"></div>";
            
            $items = new wfcuRegistryModel($dbc);
            $items->upc($this->plu);
            $items->seatType(1);
            
            $ret .= '<div id="alert-area"></div>
            <table class="table tablesorter">';
            $ret .= '<thead><tr><th>Class Registry  <th>
                <tr><th>Seat</th>
                <th>First</th>
                <th>Last</th>
                <th>Member #</th>
                <th>Phone Number</th>
                <th>Payment Type</th>
                <th>Additional Notes</th>
                </thead>';
            $ret .= '<tbody>';
            $ret .=  sprintf('<input type="hidden" class="upc" id="upc" name="upc" value="%d" />', $this->plu );
            
            foreach ($items->find() as $item) {
                $ret .= sprintf('<tr>
                    <td class="seat">%s</td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editFirst" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editLast" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editPhone" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editCard_no" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <select class="form-control input-sm editable" name="editPayment">
                                <option value="student has not paid">*unpaid*</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Gift Card">Gift Card</option>
                                <option value="Check">Check</option>
                                <option value="other">other</option>
                            </select>
                    <td><span class="collapse">%s</span>
                        <textarea class="form-control editable" name="editNotes" value="%s" rows="1" cols="30" /></textarea></td>
                    <td><button type="button" class="btn btn-default withdraw" onclick="withdraw(); return false;">Withdraw</button></td>
                    </tr>',
                    $item->seat(),
                    $item->first_name(),
                    $item->first_name(),
                    $item->last_name(),
                    $item->last_name(),
                    $item->card_no(),
                    $item->card_no(),
                    $item->phone(),
                    $item->phone(),
                    $item->payment(),
                    $item->payment(),
                    $item->first_opt_name()
                    
                );  
            }
            $ret .= '</tbody></table>';
            
            $items->reset();
            $items->upc($this->plu);
            $items->seatType(0);
            
            $ret .= '<div id="alert-area"></div>
            <table class="table tablesorter">';
            $ret .= '<thead><tr><th>Waiting List<th>
                <tr><th>Seat</th>
                <th>First</th>
                <th>Last</th>
                <th>Member #</th>
                <th>Phone Number</th>
                <th>Payment Type</th>
                <th>opt. Attendee</th>
                <th>opt. Attendee Phone</th></thead>';
            $ret .= '<tbody>';
            $ret .=  sprintf('<input type="hidden" class="upc" id="upc" name="upc" value="%d" />', $this->plu );
            foreach ($items->find() as $item) {
                $ret .= sprintf('<tr>
                    <td class="seat">%s</td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editFirst" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editLast" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editPhone" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editCard_no" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <select class="form-control input-sm editable" name="editPayment">
                                <option value="student has not paid">*unpaid*</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Gift Card">Gift Card</option>
                                <option value="Check">Check</option>
                                <option value="other">other</option>
                            </select>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editOptFirst" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editOptLast" value="%s" /></td>
                    <td><button type="button" class="btn btn-default" >Join Class</button></td>
                    </tr>',
                    $item->seat(),
                    $item->first_name(),
                    $item->first_name(),
                    $item->last_name(),
                    $item->last_name(),
                    $item->card_no(),
                    $item->card_no(),
                    $item->phone(),
                    $item->phone(),
                    $item->payment(),
                    $item->payment(),
                    $item->first_opt_name(),
                    $item->first_opt_name(),
                    $item->last_opt_name(),
                    $item->last_opt_name()
                );  
            }
            $ret .= '</tbody></table>';
            
            $items->reset();
            $items->upc($this->plu);
            $items->seatType(3);
            
            $ret .= '<div id="alert-area"></div>
            <table class="table tablesorter">';
            $ret .= '<thead><tr><th>Cancellations<th>
                <tr><th>Seat</th>
                <th>First</th>
                <th>Last</th>
                <th>Member #</th>
                <th>Phone Number</th>
                <th>Payment Type</th>
                <th>opt. Attendee</th>
                <th>opt. Attendee Phone</th></thead>';
            $ret .= '<tbody>';
            $ret .=  sprintf('<input type="hidden" class="upc" id="upc" name="upc" value="%d" />', $this->plu );
            foreach ($items->find() as $item) {
                $ret .= sprintf('<tr>
                    <td class="seat">%s</td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editFirst" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editLast" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editPhone" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editCard_no" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <select class="form-control input-sm editable" name="editPayment">
                                <option value="student has not paid">*unpaid*</option>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Gift Card">Gift Card</option>
                                <option value="Check">Check</option>
                                <option value="other">other</option>
                            </select>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editOptFirst" value="%s" /></td>
                    <td><span class="collapse">%s</span>
                        <input type="text" class="form-control input-sm editable" name="editOptLast" value="%s" /></td>
                    </tr>',
                    $item->seat(),
                    $item->first_name(),
                    $item->first_name(),
                    $item->last_name(),
                    $item->last_name(),
                    $item->card_no(),
                    $item->card_no(),
                    $item->phone(),
                    $item->phone(),
                    $item->payment(),
                    $item->payment(),
                    $item->first_opt_name(),
                    $item->first_opt_name(),
                    $item->last_opt_name(),
                    $item->last_opt_name()
                );  
            }
            $ret .= '</tbody></table>';
            
        }

        $this->add_onload_command('itemEditing();');
        $this->add_onload_command('withdraw();');
        $this->add_script('../../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addCssFile('../../src/javascript/tablesorter/themes/blue/style.css');
        $this->add_onload_command("\$('.tablesorter').tablesorter({sortList:[[0,0]], widgets:['zebra']});");
        
        $dbc->close();
        
        return $ret;
    }
    
    public function javascriptContent()
    {
        ob_start();
        ?>
function itemEditing()
{
    
    $('.editable').change(function(){
        var current_seat = $(this).closest('tr').find('.seat').html();
        $(this).prev('span.collapse').html($(this).val());
        $('.tablesorter').trigger('update');
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type: 'post',
            url: 'registryUpdate.php',
            dataType: 'json',
            data: 'upc='+$('#upc').val()+'&seat='+current_seat+'&field='+$(this).attr('name')+'&value='+$(this).val(),
            success: function(resp) {
                if (resp.error) {
                    showBootstrapAlert('#alert-area', 'danger', resp.error_msg);
                } else {
                    showBootstrapPopover(elem, orig, '');
                }
            }
        });
    });
}
function withdraw()
{
    $('.withdraw').change(function(){
        var current_seat = $(this).closest('tr').find('.seat').html();
        $.ajax({
            type: 'post',
            url: 'registryUpdate.php',
            dataType: 'json',
            data: 'upc='+$('#upc').val()+'&seat='+current_seat+'&field='+$(this).attr('name')+'&value='+$(this).val(),
            success: function(resp) {
                    if (resp.error) {
                        showBootstrapAlert('#alert-area', 'danger', resp.error_msg);
                    } else {
                        showBootstrapPopover(elem, orig, '');
                    }
                }
        });
    });    
}
        <?php
        return ob_get_clean();
    }
    
}

FannieDispatch::conditionalExec();

