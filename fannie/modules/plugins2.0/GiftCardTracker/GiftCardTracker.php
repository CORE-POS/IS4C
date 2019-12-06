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

include(__DIR__.'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('wfcuRegistryModel')) {
    include(__DIR__ . '/wfcuRegistryModel.php');
}

class GiftCardTracker extends FannieRESTfulPage
{
    public $description = "[Module] Gift Card Tracker";
    public $themed = true;

    protected $header = "Fannie :: Gift Card Tracker";
    protected $title = "Gift Card Tracker";

    public function preprocess()
    {
        $this->__routes[] = 'get<newgc>';
        $this->__routes[] = 'get<gcid><save>';

        return parent::preprocess();
    }

    public function get_gcid_save_handler()
    {
        $user = FannieAuth::getUID($this->current_user);
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $args = array($user);
        $prep = $dbc->prepare("SELECT name FROM Users WHERE uid = ?;");
        $res = $dbc->execute($prep, $args);
        $row = $dbc->fetchRow($res);
        $username = $row['name'];
        $firstName = FormLib::get('firstName');
        $lastName = FormLib::get('lastName');
        $phone = FormLib::get('phone');
        $cardNo = FormLib::get('cardNo');
        $amount = FormLib::get('amount');
        $gcid = FormLib::get('gcid');
        $addr1 = FormLib::get('addr1');
        $city = FormLib::get('city');
        $state = FormLib::get('state');
        $email = FormLib::get('email');
        $zip = FormLib::get('zip');
        $notes = FormLib::get('notes');
        $store = 0;
        $args = array($cardNo, $firstName, $lastName, $phone, $amount, $gcid, $username,
            $city, $state, $zip, $addr1, $store, $email, $notes);
        $prep = $dbc->prepare("
            INSERT INTO onlineGiftcard (cardNo, firstName, lastName,
                phone, amount, uniqid, employee, city, state, zip,
                addr1, store, email, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?);");
        $res = $dbc->execute($prep, $args);
        if ($er = $dbc->error()) {
            return header("location: ?save=failed&err=$er");
        } else {
            return header('location: ?save=success');
        }

        return header('location: ?error=somethingwentwrong');
    }

    public function get_newgc_view()
    {
        $json = FormLib::get('json');
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $json = (json_decode(base64_decode($json)));
        $owner = ($o = $json->owner) ? $o : 11;

        //$address = $json->address;
        if ($owner != 11) {
            $a = array($owner);
            $p = $dbc->prepare("SELECT addressFirstLine AS addr FROM CustomerAccounts WHERE cardNo = ?");
            $r = $dbc->execute($p, $a);
            $address = $dbc->fetchRow($r);
            $address = $address['addr'];
        } else {
            $address = 'n/a';
        }

        $name = $json->name;
        $addr1 = $json->addr1;
        $ph = $json->ph;
        $formattedPh = preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $ph);
        if (strpos($ph, '-') == false) {
            $ph = $formattedPh;
        }
        $amt = $json->amt;
        $sname = "This card should be mailed to: \n" . $json->sname;
        $city = $json->city;
        $state = $json->state;
        $zip = $json->zip;
        $notes = ($n = $json->notes) ? $n : "\nnone specified.";
        $email = $json->email;

        $gcid = FormLib::get('newgc');
        $args = array($gcid);
        $prep = $dbc->prepare("SELECT uniqid FROM onlineGiftcard WHERE uniqid = ?");
        $res = $dbc->execute($prep, $args);
        $usedid = $dbc->fetchRow($res);
        $used = ($usedid == false) ? "<div align='center' class='alert alert-success'>Creating gift card for email with code: $gcid</div>"
            : "<div align='center' class='alert alert-danger'>$gcid already logged as completed</div>";
        if ($usedid == true) {
            $this->addOnloadCommand("
                window.location.href = 'GiftCardTracker.php?save=cardlogged&uid=$gcid';");
        }

        return <<<HTML
$used
<div class="row">
<form>
    <div class="col-md-6">
        <label>Owner Number</label>
        <div class="form-group">
            <input type="text" class="form-control" name="cardNo" value="$owner"/>
        </div>
        <label>Card From</label>
        <div class="form-group">
            <input type="text" class="form-control" name="firstName" value="$name"/>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label>Phone Number</label>
                <div class="form-group">
                    <input type="text" class="form-control" name="phone" value="$ph"/>
                </div>
            </div>
            <div class="col-md-6">
                <label>Gift Card Amount</label>
                <div class="form-group">
                    <input type="text" class="form-control" name="amount" value="$amt"/>
                </div>
            </div>
        </div>
        <label>Email Address</label>
        <div class="form-group">
            <input type="text" class="form-control" name="email" value="$email"/>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-default">Log Gift Card</button>
        </div>
        <input type="hidden" name="save" value="true"/>
        <input type="hidden" name="gcid" value="$gcid"/>
    </div>
    <div class="col-md-6">
        <!--
        <label>Owner's Address</label>
        <div class="form-group">
            <input type="text" class="form-control" name="address" value="$address"/>
        </div>
        <div class="row">
            <div class="col-md-3">
                <label>City</label>
                <div class="form-group">
                    <input type="text" class="form-control" name="city" value="$city"/>
                </div>
            </div>
            <div class="col-md-3">
                <label>State</label>
                <div class="form-group">
                    <input type="text" class="form-control" name="state" value="$state"/>
                </div>
            </div>
            <div class="col-md-3">
                <label>ZIP</label>
                <div class="form-group">
                    <input type="text" class="form-control" name="zip" value="$zip"/>
                </div>
            </div>
        </div>
        -->
        <label>Notes</label>
        <div class="form-group">
            <textarea class="form-control" rows=10 name="notes" spellcheck="falsespellcheck="false""/>
$sname
$addr1
$city
\nAdditional Customer Notes: $notes
            </textarea>
        </div>
    </div>
</form>
</div>
HTML;
    }

    public function get_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $save = FormLib::get('save');
        $uid = FormLib::get('uid');
        $prep = $dbc->prepare("SELECT * FROM onlineGiftcard ORDER BY date DESC;");
        $res = $dbc->execute($prep);
        $table = "<table class='table table-bordered table-condensed'>
            <thead>
                <th>Owner #</th>
                <th>Purchaser(s)</th>
                <th>Phone Number</th>
                <th>Email Address</th>
                <th>Amount</th>
                <th>Transaction Code</th>
                <th>Completed</th>
                <th>Timestamp</th>
                <th>Notes</th>
            </thead><tbody>";
        $i = 0;
        while ($row = $dbc->fetchRow($res)) {
            $noteid = 'noteid'.$i;
            $date = substr($row['date'], 0, -3);
            $gcid = $row['uniqid'];
            $gc = ($gcid == $uid) ? "<span class='alert-danger'>$gcid</span>" : $gcid;
            $table .= "<tr>";
            $table .= "<td>{$row['cardNo']}</td>";
            $table .= "<td>{$row['firstName']}</td>";
            //$table .= "<td>{$row['lastName']}</td>";
            //$table .= "<td>{$row['addr1']}</td>";
            //$table .= "<td>{$row['city']}</td>";
            //$table .= "<td>{$row['state']}</td>";
            //$table .= "<td>{$row['zip']}</td>";
            $table .= "<td>{$row['phone']}</td>";
            $table .= "<td>{$row['email']}</td>";
            $table .= "<td>{$row['amount']}</td>";
            $table .= "<td>$gc</td>";
            $table .= "<td>{$row['employee']}</td>";
            $table .= "<td>$date</td>";
            $table .= "<td><span class='clickie' data-toggle='collapse' data-target='#noteid$i'
                title='{$row['notes']}'>...<span><span class='collapse'
                id='noteid$i'>{$row['notes']}</span></td>";
            $table .= "</tr>";
            $i++;
        }
        $table .= "</tbody></table>";
        $alert = "";
        if ($save == 'success') {
            $alert = "<div align='center' class='alert alert-success'>Save Successful</div>";
        } elseif ($save == 'failed') {
            $alert = "<div align='center' class='alert alert-danger'>Save Failed</div>";
        } elseif ($save == 'cardlogged') {
            $alert = "<div align='center' class='alert alert-danger'>This Gift Card
                had already been logged as completed</div>";
        }

        return <<<HTML
<div class="row">
    <div class="col-md-12">
        $alert
        <h2>Completed Gift Cards</h2>
        <div class="table-responsive">$table</div>
    </div>
</div>

HTML;
    }

    public function css_content()
    {
        return <<<HTML
span.clickie {
    cursor: pointer;
}
HTML;
    }

}
FannieDispatch::conditionalExec();
