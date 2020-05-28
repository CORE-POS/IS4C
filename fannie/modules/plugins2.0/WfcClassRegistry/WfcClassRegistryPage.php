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

use COREPOS\Fannie\API\data\pipes\OutgoingEmail;

include(__DIR__.'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('wfcuRegistryModel')) {
    include(__DIR__ . '/wfcuRegistryModel.php');
}

class WfcClassRegistryPage extends FanniePage
{
    public $description = "[WFC-U Public Class Registry] Register customers for 
        public classes offered by WFC.";
    public $themed = true;
    public $plugin_settings = array();
    protected $must_authenticate = true;
    protected $header = "Fannie :: WFC-U Class Registry";
    protected $title = "WFC Class Sign-in";
    private $display_function;
    private $coupon_id;
    private $plu;

    public function preprocess()
    {
        $this->addScript('WfcClassRegistry.js?date=20180302');
        if (FormLib::get('notify', false) !== false) {
            $this->notify_handler();
        } elseif (FormLib::get('sellOut', false) !== false) {
            $this->sellOut_handler();
        } elseif (FormLib::get('newDate', false) !== false) {
            $this->newDate_handler();
        } elseif (FormLib::get('cancel', false) !== false) {
            $this->cancel_handler();
        }


        $this->display_function = 'listClasses';

        if (FormLib::get('sign_pay', false) !== false) {
            $this->display_function = 'sign_pay_view';
        }
        return true;
    }

    public function newDate_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $localDB = $dbc;
        include(__DIR__.'/../../../src/Credentials/OutsideDB.tunneled.php');
        $remoteDB = $dbc;
        $upc = BarcodeLib::padUpc(FormLib::get('upc'));
        $newDate = FormLib::get('newDate');

        $args = array($newDate,$upc);
        $prep = $dbc->prepare("UPDATE productExpires SET expires = ? WHERE upc = ?");
        $localDB->execute($prep,$args);
        $remoteDB->execute($prep,$args);

        return true;
    }

    public function notify_handler()
    {
        $upc = BarcodeLib::padUpc(FormLib::get('upc'));
        $class = FormLib::get('className');

        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $args = array($upc);
        $prep = $dbc->prepare("UPDATE productExpires SET notified = 1 WHERE upc = ?");
        $dbc->execute($prep,$args);

        $to = array();
        $to[] = 'brand@wholefoods.coop';
        $msg = "This class is almost full - ";
        $this->send($msg,$to);

        return true;
    }

    private function sellOut_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $localDB = $dbc;
        include(__DIR__.'/../../../src/Credentials/OutsideDB.tunneled.php');
        $remoteDB = $dbc;
        $upc = BarcodeLib::padUpc(FormLib::get('upc'));
        $class = FormLib::get('className');

        $args = array($upc);
        $prep = $dbc->prepare("UPDATE productUser SET soldOut = 1 WHERE upc = ?");
        $localDB->execute($prep,$args);
        $remoteDB->execute($prep,$args);

        $to = array();
        $to[] = $this->config->get('ADMIN_EMAIL');
        $to[] = 'brand@wholefoods.coop';
        $msg = "This class is full and has been removed from the online store.";
        $this->send($msg,$to);

        return true;
    }

    public function send($msg,$to)
    {
        $seats = FormLib::get('seats');
        $registered = FormLib::get('n');
        $upc = BarcodeLib::padUpc(FormLib::get('upc'));
        $className = FormLib::get('className');
        if (!OutgoingEmail::available()) {
            // can't send
            return false;
        }
        $mail = OutgoingEmail::get();
        $mail->From = 'automail@wholefoods.coop';
        $mail->FromName = 'WFC-U Class Registration Alerts';
        foreach ($to as $address) {
            $mail->addAddress($address);
        }
        $mail->Subject = 'WFC-U Class Registration Alert';
        $msg .= "\n";
        $msg .= "$registered/$seats seats have been filled.";
        $msg .= "\n";
        $msg .= "UPC for this class: $upc";
        $msg .= "\n";
        $msg .= "$className<br/>";
        $msg .= "\n";
        $mail->Body = strip_tags($msg);
        $ret = $mail->send();

        return $ret ? true : false;
    }

    public function css_content()
    {
        return '
            .w-xs {
                max-width: 60px;
            }
            table td,th {
                border-top: none !important;
            }
            .soldOut {
                color: tomato;
                text-shadow: 1px 0 0 rgba(255,165,0,0.3), 0 -1px 0 rgba(255,165,0,0.3), 0 1px 0 rgba(255,165,0,0.3), -1px 0 0 rgba(255,165,0,0.3);
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

        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $query = $dbc->prepare("
            SELECT
                pu.description,
                p.upc,
                p.size,
                pe.expires,
                pe.notified,
                pu.soldOut
            FROM products AS p
                LEFT JOIN productUser AS pu ON pu.upc=p.upc
                LEFT JOIN productExpires AS pe ON pe.upc=p.upc
            WHERE p.description LIKE 'class -%'
                    AND p.inUse=1
            GROUP BY pu.description
            ORDER BY substr(pu.description,9,2),pu.description ASC;
            ");
        $result = $dbc->execute($query);
        while($row = $dbc->fetch_row($result)){
            $className[] = substr($row['description'], 11, 100);
            $classUPC[] = substr($row['upc'], 5, 13);
            $classDate[] = substr($row['description'], 0, 10);
            $classSize[] = $row['size'];
            $classExp[] = $row['expires'];
            $notified[] = $row['notified'];
            $soldOut[] = $row['soldOut'];
        }

        $ret = '';
        $curPlu = FormLib::get('class_plu');
        if (is_numeric($curPlu) && isset($classUPC[$curPlu])) {
            $upc = BarcodeLib::padUPC($classUPC[$curPlu]);
            $ret .= '<input type="hidden" id="curUpc" value="'.$upc.'">';
            $ret .= '<input type="hidden" id="notified" value="'.$notified[$curPlu].'">';
            $ret .= '<input type="hidden" id="soldOut" value="'.$soldOut[$curPlu].'">';
            $ret .= '<input type="hidden" id="className" value="'.$className[$curPlu].'">';
            $ret .= '<input type="hidden" id="classExpires" value="'.$classExp[$curPlu].'">';
            $ret .= '<input type="hidden" id="maxSize" value="'.$classSize[$curPlu].'">';
            $newExpires = new DateTime($classExp[$curPlu]);
            $newExpires->modify('-2 days');
            $ret .= '<input type="hidden" id="newDate" value="'.$newExpires->format('Y-m-d h:i:s').'">';
        }
        $ret .= '<form method=\'get\' class=\'form-inline\' name=\'classSelector\'>
            <select class=\'form-control\' name=\'class_plu\' id=\'classSelector\' style=\'border: 2px solid #38ACEC;\'>';
        $ret .= '<option value=\'1\'>Choose a class...</option>';

        $date = date('m/d/y');
        $date = strtotime($date);

        foreach ($className as $key => $name) {
            $tempDate = substr($classExp[$key], 0, 7);
            if ($key == $curPlu) {
                $sel = 'selected';
            } else {
                $sel = '';
            }
            $expirationDate = strtotime($tempDate);
            if (FormLib::get('expired') === '') {
                $ret .= '<option value=\'' . $key . '\'' . $sel . '>' . $classDate[$key] . " :: " . $name . '</option>';
            } else {
                if ($date <= $expirationDate) {
                    $ret .= '<option value=\'' . $key . '\'' . $sel . '>' . $classDate[$key] . " :: " . $name . '</option>';
                }
            }
        }
        $ret .= '</select>';
        $ret .= '<span class="hidden-xs hidden-sm">&nbsp;&nbsp;</span>';
        $ret .= '<div style="padding: 5px;"><input type="checkbox" class="checkbox" name="expired" value="1" ';
        if ($expired = FormLib::get('expired')) {
            $ret .= 'checked="checked" ';
        }
        $ret .= ' ><i style="padding: 20;"> Don\'t show expired Classes</i> ';
        if (isset($soldOut[$curPlu]) && $soldOut[$curPlu] == 1) {
            $ret .= "<span class='soldOut' title='This class has been removed from online sign-up.'>CLASS IS SOLD OUT</span>";
        }
        $ret .= '</div>';
        $ret .= '</form>';
        $vNext = $curPlu+1;
        $vPrev = $curPlu - 1 > 0 ? $curPlu - 1 : 0;
        $ret .= '<form method=\'get\' class=\'form-inline\'>';
        if ($expired) {
            $ret .= '<input type="hidden" name="expired" value="1">';
        }
        $ret .= '<button type="submit" class="btn-default btn-xs" name="class_plu" value="'.$vPrev.'">Prev</button>&nbsp;';
        $ret .= '<button type="submit" class="btn-default btn-xs" name="class_plu" value="'.$vNext.'">Next</button>&nbsp;';
        $ret .= '</form>';
        $ret .= '</div>';

        $key = FormLib::get('class_plu');
        $plu = isset($classUPC[$key]) ? barcodeLib::padUpc($classUPC[$key]) : '';
        $this->plu = $plu;

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

        //* Insert IDs into Rows based on class size
        if($plu) {
            $plu = BarcodeLib::padUpc($plu);
            $pCheck = $dbc->prepare("
                SELECT COUNT(seat)
                FROM wfcuRegistry
                WHERE upc = ? 
                    AND seatType = 1
                    AND childSeat = 0;");
            $rCheck = $dbc->execute($pCheck, array($plu));
            $numSeats = $dbc->fetchRow($rCheck);
            $numSeats = $numSeats[0];

            $pCheck = $dbc->prepare("
                SELECT size
                FROM products
                WHERE upc = ?;");
            $rCheck = $dbc->execute($pCheck, array($plu));
            $classSize = $dbc->fetchRow($rCheck);
            $classSize = $classSize[0];

            if ($numSeats < $classSize) {
                for ($i=$numSeats; $i<$classSize; $i++) {
                    $aAddSeat = array($plu, $i+1, 1); 
                    $pAddSeat = $dbc->prepare("INSERT INTO wfcuRegistry (upc, seat, seatType)
                        VALUES (?, ?, ?);");
                    $dbc->execute($pAddSeat, $aAddSeat);
                    if (strpos(strtolower($className[$curPlu]), 'kid') !== false
                            || strpos(strtolower($className[$curPlu]), 'child') !== false) {
                        $aAddSeat = array($plu, $i+1, 1, 1); 
                        $pAddSeat = $dbc->prepare("INSERT INTO wfcuRegistry (upc, seat, seatType, childSeat)
                            VALUES (?, ?, ?, ?);");
                        $dbc->execute($pAddSeat, $aAddSeat);
                    }
                }
            }

            $prep = $dbc->prepare("SELECT count(id) FROM wfcuRegistry WHERE seatType=0 AND upc=?;");
            $resp = $dbc->execute($prep, array($plu));
            while ($row = $dbc->fetch_row($resp)) {
                $waitSize = $row['count(id)'];
            }
            if ($waitSize == 0 || !isset($waitSize)) {
                $prep = $dbc->prepare("INSERT INTO wfcuRegistry (upc, seat, seatType) VALUES (?, 1, 0);");
                $resp = $dbc->execute($prep, array($plu));
            }

            //  Create a new (blank) 'Waiting List' row if the previous row no longer NULL.
            $prep = $dbc->prepare("SELECT id, LENGTH(first_name) AS firstNameLength 
                FROM wfcuRegistry WHERE seatType=0 AND upc=?;");
            $resp = $dbc->execute($prep, array($plu));
            while ($row = $dbc->fetch_row($resp)) {
                $firstNameLength = $row['firstNameLength'];
                $id = $row['id'];
            }
            $prep = $dbc->prepare("SELECT id FROM wfcuRegistry WHERE upc = ?
                AND seatType = 0 AND LENGTH(first_name) = 0");
            $resp = $dbc->execute($prep, array($plu));
            $rows = $dbc->numRows($resp);

            if ($rows == 0) {
                $prep = $dbc->prepare("SELECT MAX(seat) AS max FROM wfcuRegistry WHERE upc = ? 
                    AND seatType = 0");
                $resp = $dbc->execute($prep, array($plu));
                $row = $dbc->fetchRow($resp);
                $max = $row['max'];
                $max += 1;
                $prep = $dbc->prepare("INSERT INTO wfcuRegistry (upc, seat, seatType) VALUES (?, ?, 0);");
                $resp = $dbc->execute($prep, array($plu, $max));
            }
        }

        if ($key > -1) {
            //* Class Roster
            $ret .= "<div style='float: left'><h3>" . (isset($className[$key]) ? $className[$key] : '') . "</h3></div>";
            $ret .= "<h4 align=\"center\">" . (isset($classDate[$key]) ? $classDate[$key] : '') . "</h4>";
            $ret .= "<h5 align='center'><a href='../../../item/ItemEditorPage.php?searchupc=" . $plu . "' target='_blank'>PLU: " . $plu . "</a></h5>";
            $ret .= "<div id=\"line-div\"></div>";

            $items = new wfcuRegistryModel($dbc);
            $items->upc($this->plu);
            $items->seatType(1);

            list($rows, $count) = $this->printItems($items, $curPlu);
            $ret .= '<div id="alert-area"></div>
            <h4>Class Registry</h4>
            <table class="table" name="ClassRegistry" id="table-roster">';
            $ret .= '<thead><tr></tr>
                <tr><th>Seat</th>
                <th>Mem #</th>
                <th>First</th>
                <th>Last</th>
                <th>Phone Number</th>
                <th>Email Address</th>
                <th>Payment Type</th>
                <th>Notes</th>
                </thead>';
            $ret .= '<tbody>';
            $ret .=  sprintf('<input type="hidden" class="upc" id="upc" name="upc" value="%d" />', $this->plu );
            $ret .= $rows;
            $ret .= '</tr></tbody></table>';

            $items->reset();
            $items->upc($this->plu);
            $items->seatType(0);

            list($rows, $count) = $this->printItems($items);
            //* Waiting List Roster
            $ret .= '<div id="alert-area"></div>
            <h4>Waiting List</h4>
            <table class="table" id="table-waiting">';
            $ret .= '<thead>
                <tr><th></th>
                <th>Mem #</th>
                <th>First</th>
                <th>Last</th>
                <th>Phone Number</th>
                <th>Email Address</th>
                <th>Payment Type</th>
                <th>Notes</th></thead>';
            $ret .= '<tbody>';
            $ret .=  sprintf('<input type="hidden" class="upc" id="upc" name="upc" value="%d" />', $this->plu );
            $ret .= $rows;
            $ret.= '<tr><td><button type="button" class="btn btn-default" onclick="window.location.reload();">Add Row</button></tr>';
            $ret .= '</tbody></table>';

            $items->reset();
            $items->upc($this->plu);
            $items->seatType(3);

            list($rows, $count) = $this->printItems($items);
            //* Class Cancellations
            $ret .= '<div id="alert-area"></div>
            <h4>Cancellations</h4>
            <table class="table" id="table-cancel">';
            $ret .= '<thead><tr>
                <tr><th></th>
                <th>Mem #</th>
                <th>First</th>
                <th>Last</th>
                <th>Phone Number</th>
                <th>Email Address</th>
                <th>Payment Type</th>
                <th>Refund Type</th>
                <th>Notes</th></thead>';
            $ret .= '<tbody>';
            $ret .=  sprintf('<input type="hidden" class="upc" id="upc" name="upc" value="%d" />', $this->plu );
            $ret .= $rows;
            $ret .= '</tbody></table></div>';
        }

        if (!is_array($classSize)) {
            $this->addOnloadCommand('itemEditing(' . $classSize . ');');
        }
        $this->addOnloadCommand('withdraw();');

        $dbc->close();

        return $ret;
    }

    private function cancel_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        //$class_plu = FormLib::get('class_plu');
        $model = new wfcuRegistryModel($dbc);
        $newRow = new wfcuRegistryModel($dbc);
        $child = new wfcuRegistryModel($dbc);
        $model->id(FormLib::get('id'));
        $model->load();
        $cols = array("id","upc","first_name","last_name","phone","card_no","payment","refund","modified","seat","seatType","details","amount","email","childseat");

        // set new row into cancelled table with data from row selected
        foreach ($cols as $col) {
            $newRow->{$col}($model->{$col}());
            if ($col == 'details') {
                $details = $model->{$col}();
            }
        }
        // if class has child seats, save child info to details
        $child->id(FormLib::get('id') + 1);
        $child->load();
        if ($child->childseat() == 1) {
            $newRow->details($details."\r\nchild=".$child->first_name().' '.$child->last_name().'; age='.$child->phone());
            $child->first_name(null);
            $child->last_name(null);
            $child->phone(null);
            $child->save();
        }

        $newRow->id(null);
        $newRow->seatType(3);
        $newRow->seat(null);
        $newRow->save();

        // unset data in original row so it can be used again 
        $cols = array("first_name","last_name","phone","card_no","payment","refund","email","details");
        foreach ($cols as $col) {
            $model->{$col}(null);
        };
        $model->save();

        return header('location: WfcClassRegistryPage.php?class_plu=16');
        
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
        return <<<JAVASCRIPT
$('input[type="checkbox"][name="expired"]').click(function(){
    //window.location.reload();
    document.forms['classSelector'].submit();
});
$('.cardno').change(function(){
    var ownerid = $(this).val();
    var seat = $(this).closest('tr').find('.seat').text();
    var tableID = $(this).closest('table').attr('id');
    if (ownerid != 11) {
        $.ajax ({
            url: 'registryUpdate.php',
            type: 'post',
            data: 'ownerid='+ownerid+'&custdata=1',
            dataType: 'json',
            success: function(resp)
            {
                var card_no = resp['card_no'];
                var data = ['first_name','last_name','street','city',
                    'state','zip','email_1','email_2','phone','address'];
                $('.seat').each(function(){
                    var curTable = $(this).closest('table').attr('id');
                    if ($(this).text() == seat && curTable == tableID) {
                        var editFirst = $(this).closest('tr').find('input[name="editFirst"]');
                        var editLast = $(this).closest('tr').find('input[name="editLast"]');
                        var editPhone = $(this).closest('tr').find('input[name="editPhone"]');
                        var editEmail = $(this).closest('tr').find('input[name="editEmail"]');
                        editFirst.val(resp.first_name);
                        editFirst.trigger('change');
                        editLast.val(resp.last_name);
                        editLast.trigger('change');
                        editPhone.val(resp.phone);
                        editPhone.trigger('change');
                        editEmail.val(resp.email_1);
                        editEmail.trigger('change');
                    }
                });
                $.each(data, function(k,v) {
                    // var value = resp[v];
                    // $('#'+v).val(value);
                });
            }
        });
    }
});
$('tr').each(function(){
    var maxSeat = parseInt($('#maxSize').val(), 10);
    var curSeat = parseInt($(this).find('.seat').text(), 10);
    //console.log(maxSeat+', '+curSeat);
    if (curSeat > maxSeat) {
        $(this).addClass('alert-warning');
    }
});
$('.btn-cancel').click(function(){
    c = confirm('Move customer to Cancellations?');
    if (c === false) {
        return false;
    }
    return true;
});
JAVASCRIPT;
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

    private function printItems($items, $curPlu=false, $withCancel=true)
    {
        $ret = '';
        $i = 0;
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $rowCount = 0;
        foreach ($items->find('seat') as $item) {
            $puModel = new ProductUserModel($dbc);
            $puModel->upc(BarcodeLib::padUpc($item->upc()));
            $obj = $puModel->find('narrow');
            $accessDiscount = ($obj[0]->narrow() == 1) ? true : false;
            $i+=1;
            $isChild = $item->childseat();
            $ageLabel = ($isChild) ? '<span style="position: absolute; margin-top: -18px"><i>Age</i></span>' : '';
            $firstNameLabel = ($isChild) ? '<span style="position: absolute; margin-top: -18px"><i>Child\'s first name</i></span>' : '';
            $lastNameLabel = ($isChild) ? '<span style="position: absolute; margin-top: -18px"><i>Child\'s last name</i></span>' : '';
            $disabled = ($isChild) ? 'hidden' : 'text';
            $ret .= sprintf('<tr>
                <td class="id collapse">%s</td>
                <td class="seat">%s</td>
                <td><span class="collapse">%s</span>
                    <input type="'.$disabled.'" class="form-control input-sm w-xs editable cardno" name="editCard_no" value="%s"/></td>
                <td><span class="collapse">%s</span>
                    %s
                    <input type="text" class="form-control input-sm editable first_name" name="editFirst" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    %s
                    <input type="text" class="form-control input-sm editable" name="editLast" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    %s
                    <input type="text" class="form-control input-sm editable" name="editPhone" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <input type="'.$disabled.'" class="form-control input-sm editable" name="editEmail" value="%s" /></td>
                <td><span class="collapse">%s</span>
                    <select class="form-control input-sm editable '.$disabled.'" name="editPayment">
                        <option value="student has not paid">*unpaid*</option>',
                $item->id(),
                $isChild ? '<i style="display: none;">child-seat('.$item->seat().')</i>' : $item->seat(),
                $item->card_no(),
                $item->card_no(),
                $item->first_name(),
                $firstNameLabel,
                $item->first_name(),
                $item->last_name(),
                $lastNameLabel,
                $item->last_name(),
                $item->phone(),
                $ageLabel,
                $item->phone(),
                $item->email(),
                $item->email(),
                htmlspecialchars($item->payment())
            );
            $paymentTypes = array();
            $paymentTypes = ($accessDiscount == true) ? array('Cash', 'Card', 'Gift Card', 'Check', 'ACCESS', 'Other') : array('Cash', 'Card', 'Gift Card', 'Check', 'Other');
            foreach ($paymentTypes as $tender) {
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

            if ($withCancel && $item->first_name() && $item->childseat() != 1 && $item->seatType() == 1) {
                $ret .= sprintf('
                    <td><a class="btn btn-default btn-cancel" href="?classplu=%d&id=%d&cancel=1&key=%d">Cancel</button></td>',
                    $curPlu,
                    $item->id(),
                    $item->upc()
                );
            }
            $rowCount++;
        }

        return array($ret, $rowCount);
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

