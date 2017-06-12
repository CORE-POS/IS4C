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

class WfcClassRegistryPage extends FanniePage 
{
    public $description = "[Module] for managing WFC-U Class Sign-In";
    public $themed = true;

    protected $must_authenticate = true;

    protected $header = "Fannie :: WFC-U Class Registry";
    protected $title = "WFC Class Sign-in";

    private $display_function;
    private $coupon_id;
    private $plu;

    public function preprocess()
    {
        if (FormLib::get('process', false) !== false) {
            $this->credits_save_handler();
        }
        
        $this->display_function = 'listClasses';
        
        if (FormLib::get('cancel', false) !== false) {
            $this->display_function = 'cancel_vew';
        } elseif (FormLib::get('sign_pay', false) !== false) {
            $this->display_function = 'sign_pay_view';
        } elseif (FormLib::get('credits', false) !== false) {
            $this->display_function = 'credits_view';
        }
        
        return true;
    }
    
    private function credits_save_handler()
    {
        $credits = array();
        $credits = FormLib::get('processCredit');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $items = new wfcuRegistryModel($dbc);
        
        foreach ($credits as $id) {
            $items->id($id);
            $items->seatType(99);
            if ($save = $items->save()) {
                $resp = '<span class="text-success">saved!</span>';
            } else {
                $resp = '<span class="text-danger">There was an error saving.</span>';
            }
        }
        
        return $resp;
    }
    
    private function credits_view()
    {
        
        $ret = '';
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $ret .= '<div align="center"><h2>WFC-U Credits</h2><a href="?class_plu=0">Return to Registry</a></div>';
        $items = new wfcuRegistryModel($dbc);
        
        //  Create a new empty credit pending row if there is no empty row. 
        $prep = $dbc->prepare("SELECT id, first_name FROM wfcuRegistry WHERE seatType=4 AND upc='99999999';");
        $resp = $dbc->execute($prep);
        while ($row = $dbc->fetch_row($resp)) {
            $name = $row['first_name'];
            $id = $row['id'];
        }
        if ($dbc->error()) {
            $ret .= '<div class="alert alert-danger">'.$dbc->error().'</div>';
        }
        $prep = $dbc->prepare("SELECT max(id) as id FROM wfcuRegistry;");
        $resp = $dbc->execute($prep);
        while ($row = $dbc->fetch_row($resp)) {
            $maxID = $row['id'];
        }
        if ($dbc->error()) {
            $ret .= '<div class="alert alert-danger">'.$dbc->error().'</div>';
        }
        $nextId = ($maxID + 1);
        if (isset($name)) { 
            $prep = $dbc->prepare("INSERT INTO wfcuRegistry (upc, id, seatType) VALUES ('99999999', ?, 4);");
            $dbc->execute($prep,$nextId);
            if ($dbc->error()) {
                $ret .= '<div class="alert alert-danger">'.$dbc->error().'</div>';
            }
        }
        
        // Create the first row if there are no rows
        $prepA = $dbc->prepare("SELECT count(*) AS count FROM wfcuRegistry WHERE seatType=4;");
        $resA = $dbc->execute($prepA);
        while ($row = $dbc->fetchRow($resA)) {
            $rows = $row['count'];
        }
        if ($rows < 1) {
            $prepB = $dbc->prepare("INSERT INTO wfcuRegistry (upc, id, seatType) VALUES ('99999999', ?, 4);");
            $dbc->execute($prepB,$nextId);
            if ($dbc->error()) {
                $ret .= '<div class="alert alert-danger">'.$dbc->error().'</div>';
            }
        }
        
        
        $items->seatType(4);
        $ret .= '<div id="alert-area"></div><table class="table tablesorter">';
        $ret .= '<thead><label>Pending Credits</label></tr>
            <tr>
            <th style="width: 10px;"></th>
            <th style="width: 200px;">First</th>
            <th style="width: 200px;">Last</th>
            <th style="width: 100px;">Member #</th>
            <th style="width: 130px;">Phone Number</th>
            <th style="width: 80px;">Amount</th>
            <th>Notes</th>
            <th style="width: 20px;"></th>
            </thead>';
        $ret .= '<tbody>';        
        $ret .= '<form method="post"><input type="hidden" class="upc" id="upc" name="upc" value="99999999" /></form>';
        $ret .= $this->printCredits($items);
        $ret .= '</tbody></table>';
        
        $items->reset();
        $items->upc(99999999);
        $items->seatType(99);
        
        //  Processed Credits
        $ret .= '<br /><br />';
        $ret .= '<div id="alert-area"></div><table class="table tablesorter table-condensed table-stripped small">';
        $ret .= '<thead><label>Processed Credits</label></tr>
            <tr>
            <th style="width: 10px;"></th>
            <th style="width: 200px;">First</th>
            <th style="width: 200px;">Last</th>
            <th style="width: 100px;">Member #</th>
            <th style="width: 130px;">Phone Number</th>
            <th style="width: 80px;">Amount</th>
            <th>Notes</th>
            <th style="width: 200px;">Processed On</th>
            </thead>';
        $ret .= '<tbody>';      
        foreach ($items->find('modified') as $data) {
            $ret .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                '',
                $data->first_name(),
                $data->last_name(),
                $data->card_no(),
                $data->phone(),
                $data->amount(),
                $data->details(),
                $data->modified()
            );
        }
        $ret .= '</tbody></table>';
        
        $this->add_onload_command('itemEditing(' . $classSize . ');');
        //$this->add_onload_command('withdraw();');
        $this->add_script('../../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addCssFile('../../src/javascript/tablesorter/themes/blue/style.css');
        $this->add_onload_command("\$('.tablesorter').tablesorter({sortList:[[0,0]], widgets:['zebra']});");
        
        return $ret; 
    }
    
    public function css_content()
    {
        return '
            table td,th {
                border-top: none !important;
            }
       ';
    }

    public function body_content()
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
                p.size,
                pe.expires
            FROM products AS p 
                LEFT JOIN productUser AS pu ON pu.upc=p.upc 
                LEFT JOIN productExpires AS pe ON pe.upc=p.upc
            WHERE p.description LIKE 'class -%' 
                    AND p.inUse=1
            GROUP BY pu.description
            ORDER BY pu.description ASC;
            ");
        $result = $dbc->execute($query);
        while($row = $dbc->fetch_row($result)){
            $className[] = substr($row['description'], 11, 100);
            $classUPC[] = substr($row['upc'], 5, 13);
            $classDate[] = substr($row['description'], 0, 10);
            $classSize[] = $row['size'];
            $classExp[] = $row['expires'];
        }
        
        $ret = '<div class=\'container\'><form method=\'get\'><select class=\'form-control\' name=\'class_plu\'>';
        $ret .= '<option value=\'1\'>Choose a class...</option>';
        
        $date = date('m/d/y');
        $date = strtotime($date);
        
       
        foreach ($className as $key => $name) {
            $tempDate = substr($classExp[$key], 0, 7);
            $expirationDate = strtotime($tempDate);
            if (FormLib::get('expired') === '') {
                $ret .= '<option value=\'' . $key . '\'>' . $classDate[$key] . " :: " . $name . '</option>';
            } else {
                if ($date <= $expirationDate) {
                    $ret .= '<option value=\'' . $key . '\'>' . $classDate[$key] . " :: " . $name . '</option>';
                }
            }
        }
        $ret .= '</select>';
        $ret .= '
            <div>
                <div style="float: left">
                    <input class=\'btn btn-default\' type=\'submit\' value=\'Open Class Registry\'>
                </div>
                <div style="float: right">
                    <a class=\'btn btn-default \' href="?credits=1">View Credits</a>
                </div>
                
            </div>
        ';
        $ret .= '<br /><br /><input type="checkbox" class="checkbox" name="expired" value="1" ';
            if (FormLib::get('expired')) {
                $ret .= 'checked="checked" ';
            }
        $ret .= ' ><i>Don\'t show Expired Classes</i>';
        $ret .= '</form></div>';
        
        $key = FormLib::get('class_plu');
        $plu = $classUPC[$key];
        $this->plu = $classUPC[$key];
        
        //* Create table if it doesn't exist
        $prep = $dbc->prepare("CREATE TABLE IF NOT EXISTS
            wfcuRegistry (
                id INT(6) PRIMARY KEY AUTO_INCREMENT,
                upc VARCHAR(13), 
                first_name VARCHAR(30),
                last_name VARCHAR(30),
                phone VARCHAR(30),
                card_no INT(11),
                payment VARCHAR(30),
                refund VARCHAR(30),
                modified DATETIME,
                seat INT(50),
                seatType INT(5),
                details TEXT
            );   
        ");
        $res = $dbc->execute($prep);
        if (!$res) {
            echo $dbc->error() . '<br />';
        }
        
        if($plu) {
            //* Insert IDs into Rows based on class size
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
            
            $prep = $dbc->prepare("SELECT count(id) FROM wfcuRegistry WHERE seatType=0 AND upc={$plu};");
            $resp = $dbc->execute($prep);
            while ($row = $dbc->fetch_row($resp)) {
                $waitSize = $row['count(id)'];
            }
            if ($waitSize == 0 || !isset($waitSize)) {
                $prep = $dbc->prepare("INSERT INTO wfcuRegistry (upc, seat, seatType) VALUES ({$plu}, 1, 0);");
                $resp = $dbc->execute($prep);
            }
            
            //  Create a new (blank) 'Waiting List' row if the previous row no longer NULL. 
            $prep = $dbc->prepare("SELECT id, first_name FROM wfcuRegistry WHERE seatType=0 AND upc={$plu};");
            $resp = $dbc->execute($prep);
            while ($row = $dbc->fetch_row($resp)) {
                $name = $row['first_name'];
                $id = $row['id'];
            }
            $prep = $dbc->prepare("SELECT max(id) as id FROM wfcuRegistry;");
            $resp = $dbc->execute($prep);
            while ($row = $dbc->fetch_row($resp)) {
                $maxID = $row['id'];
            }
            $nextId = ($maxID + 1);
            if (isset($name)) { 
                $prep = $dbc->prepare("INSERT INTO wfcuRegistry (upc, id, seatType) VALUES ({$plu}, {$nextId}, 0);");
                $resp = $dbc->execute($prep);
            }
        
        }
        
        if ($key > -1) {
            
            
            //  Send email if class is almost filled and email hasn't been sent.
            $query = $dbc->prepare('
                SELECT count(seat) 
                FROM wfcuRegistry 
                WHERE upc = ?
                    AND first_name != "NULL" 
                    AND seatType = 1
            
            ');
            $result = $dbc->execute($query, $plu);
            $curClassSize = $dbc->fetch_row($result);
            if (!is_array($classSize) && $curClassSize[0] >= ($classSize - 3)) {
                $query = $dbc->prepare('
                    SELECT details 
                    FROM wfcuRegistry
                    WHERE upc = ?
                        AND details = "email_sent"
                ');
                $result = $dbc->execute($query, $plu);
                $row = $dbc->fetch_row($result);
                if (!isset($row)) {
                    $to = 'it@wholefoods.coop; brand@wholefoods.coop';
                    $sub = 'WFC-U Class Near Capacity';
                    $adhd = 'From: automail@wholefoods.coop';
                    $msg = 'WFC-U class ';
                    $msg .= $className[$key];
                    $msg .= ' is almost full.';
                    $msg .= "\n";
                    $msg .= 'IT - please mark class as Sold Out : 
                        <a href="http://192.168.1.2/git/fannie/item/ItemEditorPage.php?searchupc=' . $plu . '>' . $plu . '</a>';
                    $msg .= "\n";
                    mail($to,$sub,$msg,$adhd);
                    
                    $query = $dbc->prepare('
                        INSERT INTO wfcuRegistry (details, upc)
                        VALUES ("email_sent", ?)
                    ');
                    $dbc->execute($query, $plu);
                }
            }
            
            //* Class Roster
            $ret .= "<h2 align=\"center\">" . $className[$key] . "</h2>";
            $ret .= "<h3 align=\"center\">" . $classDate[$key] . "</h3>";
            //$ret .= "<h5 align=\"center\"> <i>Plu</i>: " . $plu . "</h5>";
            $ret .= "<h5 align='center'><a href='/git/fannie/item/ItemEditorPage.php?searchupc=" . $plu . "' target='_blank'>PLU: " . $plu . "</a></h5>";
            $ret .= "<div id=\"line-div\"></div>";
            
            $items = new wfcuRegistryModel($dbc);
            $items->upc($this->plu);
            $items->seatType(1);
            
            $ret .= '<div id="alert-area"></div>
            <table class="table tablesorter">';
            $ret .= '<thead><tr><th>Class Registry  </th></tr>
                <tr><th>Seat</th>
                <th>First</th>
                <th>Last</th>
                <th>Member #</th>
                <th>Phone Number</th>
                <th>Payment Type</th>
                <th>Notes</th>
                </thead>';
            $ret .= '<tbody>';
            $ret .=  sprintf('<input type="hidden" class="upc" id="upc" name="upc" value="%d" />', $this->plu );
            $ret .= $this->printItems($items);
            $ret .= '</tr></tbody></table>';
            
            $items->reset();
            $items->upc($this->plu);
            $items->seatType(0);
            
            //* Waiting List Roster
            $ret .= '<div id="alert-area"></div>
            <table class="table tablesorter">';
            $ret .= '<thead><tr><th>Waiting List<th>
                <tr><th></th>
                <th>First</th>
                <th>Last</th>
                <th>Member #</th>
                <th>Phone Number</th>
                <th>Payment Type</th>
                <th>Notes</th></thead>';
            $ret .= '<tbody>';
            $ret .=  sprintf('<input type="hidden" class="upc" id="upc" name="upc" value="%d" />', $this->plu );
            $ret .= $this->printItems($items);
            $ret.= '<tr><td><button type="button" class="btn btn-default" onclick="window.location.reload();">Add Row</button></tr>';
            $ret .= '</tbody></table>';
            
            $items->reset();
            $items->upc($this->plu);
            $items->seatType(3);
            
            //* Class Cancellations
            $ret .= '<div id="alert-area"></div>
            <table class="table tablesorter">';
            $ret .= '<thead><tr>
                <th>Cancellations</th>
                <tr><th></th>
                <th>First</th>
                <th>Last</th>
                <th>Member #</th>
                <th>Phone Number</th>
                <th>Payment Type</th>
                <th>Refund Type</th>
                <th>Notes</th></thead>';
            $ret .= '<tbody>';
            $ret .=  sprintf('<input type="hidden" class="upc" id="upc" name="upc" value="%d" />', $this->plu );
            $ret .= $this->printItems($items, false);
            $ret .= '</tbody></table>';
        }

        $this->add_onload_command('itemEditing(' . $classSize . ');');
        $this->add_onload_command('withdraw();');
        $this->add_script('../../src/javascript/tablesorter/jquery.tablesorter.js');
        $this->addCssFile('../../src/javascript/tablesorter/themes/blue/style.css');
        $this->add_onload_command("\$('.tablesorter').tablesorter({sortList:[[0,0]], widgets:['zebra']});");
        
        $dbc->close();
        
        return $ret;
    }
    
    private function cancel_vew()
    {
        $key = FormLib::get('key');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $info = new wfcuRegistryModel($dbc);
        $move = new wfcuRegistryModel($dbc);
        $info->upc(FormLib::get('class_plu'));
        $info->id(FormLib::get('id'));
        
        $ret .= '<p class="bg-success" align="center"> <b>';
        
        foreach ($info->find() as $info) {
            $ret .= $info->first_name() . " ";
            $ret .= $info->last_name() . " Owner#: ";
            $ret .= $info->card_no() . " </b> has been removed from the class registry</p>";
            
            $move->upc($info->upc());
            $move->first_name($info->first_name());
            $move->last_name($info->last_name());
            $move->card_no($info->card_no());
            $move->payment($info->payment());
            $move->phone($info->phone());
            $move->seatType(3);
            $saved = $move->save();
            $deleted = $info->delete();
        }
        $ret .= '<a class="btn btn-default" href=' . '?class_plu=' . $key . '>Return to Registry</a>';
        return $ret;
    }
    
    private function sign_pay_view()
    {
        $key = FormLib::get('key');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $locateEmptySeat = new wfcuRegistryModel($dbc);
        $locateEmptySeat->seatType(1);
        $locateEmptySeat->upc(FormLib::get('class_plu'));
        foreach ($locateEmptySeat->find() as $seat) {
            if (is_null($seat->first_name())) {
                $id = $seat->id();
                    continue;
            }
        }
        
        $info = new wfcuRegistryModel($dbc);
        $info->upc(FormLib::get('class_plu'));
        $info->seatType(0);
        $info->id(FormLib::get('id'));
        $move = new wfcuRegistryModel($dbc);
        
        if ($id) {
            foreach ($info->find() as $info) {
                $move->upc($info->upc());
                $move->first_name($info->first_name());
                $move->last_name($info->last_name());
                $move->card_no($info->card_no());
                $move->payment($info->payment());
                $move->phone($info->phone());
                $move->details($info->details());
                $move->id($id);
                $move->seatType(1);
                $saved = $move->save();
                $deleted = $info->delete();
            }
            $ret .= '<p class="bg-success" align="center"> <b>';
            $ret .= 'Student has been moved to Registry.</p>';
        } else {
            $ret .= '<p class="bg-danger" align="center"> <b>';
            $ret .= "There are no available seats in this class.</p>";
        }
        
        $ret .= '<a class="btn btn-default" href=' . '?class_plu=' . $key . '>Return to Registry</a>';
        return $ret;
    }
    
    public function javascriptContent()
    {
        ob_start();
        ?>
function itemEditing(size)
{
    
    $('.editable').change(function(){
        var current_seat = $(this).closest('tr').find('.id').html();
        $(this).prev('span.collapse').html($(this).val());
        $('.tablesorter').trigger('update');
        var elem = $(this);
        var orig = this.defaultValue;
        $.ajax({
            type: 'post',
            url: 'registryUpdate.php',
            dataType: 'json',
            data: 'upc='+$('#upc').val()+'&seat='+current_seat+'&field='+$(this).attr('name')+'&value='+$(this).val()+'&size='+size,
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
    
    public function helpContent()
    {
        return '<p>
            Sign students up for WFC-U classes.
            <ul>
                <li>Select a Class Registry to edit</li>
                <li>Enter new students who have paid to the Class Registry list</li>
                <li>Enter unpaid students information under Waiting List. <i>Students who 
                    are moved from the Waiting List to the Paid Registry will appear at the
                    bottom of the List.</i></li>
                <li>Students who have cancelled their seat in class will appear in Cancellation list</li>
            </ul>
            </p>';
    }

    private function printItems($items, $withCancel=true)
    {
        $ret = '';
        $i = 0;
        foreach ($items->find() as $item) {
            $i+=1;
            $ret .= sprintf('<tr>
                <td class="id collapse">%s</td>
                <td class="seat">%d</td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="editFirst" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="editLast" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="editCard_no" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="editPhone" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <select class="form-control input-sm editable" name="editPayment">
                        <option value="student has not paid">*unpaid*</option>',
                $item->id(),
                $i,
                $item->first_name(),
                $item->first_name(),
                $item->last_name(),
                $item->last_name(),
                $item->card_no(),
                $item->card_no(),
                $item->phone(),
                $item->phone(),
                htmlspecialchars($item->payment())
            );
            foreach (array('Cash', 'Card', 'Gift Card', 'Check', 'Other') as $tender) {
                $ret .= sprintf('<option %s value="%s">%s</option>',
                    ($tender == $item->payment() ? 'selected' : ''),
                    $tender, $tender);
            }
            $ret .= sprintf('
                        </select>
                <td><span class="collapse">%s</span>
                    <textarea class="form-control editable" name="editNotes" value="%s" rows="1" cols="30" />%s</textarea></td>',
                htmlspecialchars($item->payment()),
                $item->details(),
                $item->details()
            );  
            
            if ($withCancel && $item->first_name()) {
                $ret .= sprintf('
                    <td><a class="btn btn-default" href="?class_plu=%d&id=%d&cancel=1&key=%d">Cancel</button></td>',                
                    $item->upc(),
                    $item->id(),
                    $item->upc()
                );
            }
        }

        return $ret;
    }
    
    private function printCredits($items)
    {
        $ret = '';
        $ret .= '<form method="post" name="processCredits">';
        $i = 0;
        foreach ($items->find() as $item) {
            $i+=1;
            $ret .= sprintf('<tr>
                <td class="id collapse">%s</td>
                <td class="seat">%d</td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="editFirst" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="editLast" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="editCard_no" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="editPhone" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="text" class="form-control input-sm editable" name="editAmount" value="%s" /></td>
                    ',
                $item->id(),
                $i,
                $item->first_name(),
                $item->first_name(),
                $item->last_name(),
                $item->last_name(),
                $item->card_no(),
                $item->card_no(),
                $item->phone(),
                $item->phone(),
                $item->amount(),
                $item->amount()
                
            );
            
            $ret .= sprintf('
                <td><span class="collapse">%s</span>
                    <textarea class="form-control editable" name="editNotes" value="%s" rows="1" cols="30" style="height: 30px; "/>%s</textarea></td>
                <td><span class="collapse">%s</span>
                    <input type="checkbox" class="form-control" name="processCredit[]" value="%s" /></td>
                    ',
                htmlspecialchars($item->payment()),
                $item->details(),
                $item->details(),
                $item->id(),
                $item->id()
                
            );  
            
        }
        $ret .= '</tr><tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td><button type="submit" class="btn btn-warning btn-xs" name="process" value="1">Completed</button></td></tr>';
        $ret .= '<input type="hidden" name="credit" value="1">';
        $ret .= '</form>';

        return $ret;
    }
    
}

FannieDispatch::conditionalExec();

