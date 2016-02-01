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

/**
  @class HouseCouponEditor
*/
class WfcClassSignUp extends FanniePage 
{

    public $description = "[Module] for managing WFC-U Class Sign-In";
    public $themed = true;

    protected $must_authenticate = true;
    protected $auth_classes = array('tenders');

    protected $header = "Fannie :: WFC-U Class Sign-In";
    protected $title = "WFC Class Sign-in";

    private $display_function;
    private $coupon_id;

    public function preprocess()
    {
        $this->display_function = 'listClasses';

        $msgs = array();
        if (FormLib::get('ajax-add') !== '') {
            $dbc = FannieDB::get($this->config->get('OP_DB'));
            $id = FormLib::get('id');
            $upc = FormLib::get('new_upc');
            $dept = FormLib::get('new_dept');
            $type = FormLib::get('newtype', 'BOTH');
            $hc = new HouseCouponsModel($dbc);
            $hc->coupID($id);
            $hc->load();

            // nothing to add
            if (empty($upc) && empty($dept)) {
                echo $this->couponItemTable($id);
                return false;
            }

            $item = new HouseCouponItemsModel($dbc);
            if ($hc->minType() == 'MX') {
                if (!empty($upc)) {
                    $item->reset();
                    $item->coupID($id);
                    $item->upc(BarcodeLib::padUPC($upc));
                    $item->type('DISCOUNT');
                    $item->save();
                }
                if (!empty($dept)) {
                    $item->reset();
                    $item->coupID($id);
                    $item->upc($dept);
                    $item->type('QUALIFIER');
                    $item->save();
                }
            } else {
                if (!empty($upc)) {
                    $item->reset();
                    $item->coupID($id);
                    $item->upc(BarcodeLib::padUPC($upc));
                    $item->type($type);
                    $item->save();
                }
                if (!empty($dept)) {
                    $item->reset();
                    $item->coupID($id);
                    $item->upc($dept);
                    $item->type($type);
                    $item->save();
                }
            }

            echo $this->couponItemTable($id);

            return false;
        } elseif (FormLib::get('u', '') !== '') {
            $this->display_function = 'addUPCs';
        } elseif (FormLib::get('add-to-coupon') !== '') {
            $hci = new HouseCouponItemsModel($this->connection);
            $hci->coupID(FormLib::get('add-to-coupon'));
            $hci->type(FormLib::get('add-to-as'));
            foreach (FormLib::get('add-to-upc') as $upc) {
                $upc = BarcodeLib::padUPC($upc);
                $hci->upc($upc);
                $hci->save();
            }
            header('Location: ' . filter_input(INPUT_SERVER, 'PHP_SELF') . '?edit_id=' . $hci->coupID());
            return false;
        } elseif (FormLib::get_form_value('edit_id','') !== '') {
            $this->coupon_id = (int)FormLib::get_form_value('edit_id',0);
            $this->display_function = 'editClass';
        } elseif (FormLib::get_form_value('new_coupon_submit') !== '') {
            $dbc = FannieDB::get($this->config->get('OP_DB'));

            $maxQ = $dbc->prepare("SELECT max(coupID) from houseCoupons");
            $maxR = $dbc->execute($maxQ);
            $max = 0;
            if ($maxR && $dbc->numRows($maxR)) {
                $maxW = $dbc->fetchRow($maxR);
                $max = $maxW[0];
            }
            $this->coupon_id = $max+1;
            
            $insQ = $dbc->prepare("INSERT INTO houseCoupons (coupID) values (?)");
            $dbc->execute($insQ,array($this->coupon_id));

            $this->display_function='editClass';

            $msgs[] = array('type'=>'success', 'text'=>'Created new coupon');
            
            $dbc->close();
        } elseif (FormLib::get_form_value('submit_save') !== '' 
          || FormLib::get_form_value('submit_add_upc') !== ''
          || FormLib::get_form_value('submit_delete_upc') !== '' 
          || FormLib::get_form_value('submit_add_dept') !== ''
          || FormLib::get_form_value('submit_delete_dept') !== '' ) {

            $dbc = FannieDB::get($this->config->get('OP_DB'));

            $this->coupon_id = FormLib::get_form_value('cid',0);
            $expires = FormLib::get_form_value('expires');
            if ($expires == '') {
                $expires = null;
            }
            $limit = FormLib::get_form_value('limit',1);
            $mem = FormLib::get_form_value('memberonly',0);
            $dept = FormLib::get_form_value('dept',0);
            $dtype = FormLib::get_form_value('dtype','Q');
            $dval = FormLib::get_form_value('dval',0);
            $mtype = FormLib::get_form_value('mtype','Q');
            $mval = FormLib::get_form_value('mval',0);
            $descript = FormLib::get_form_value('description',0);
            $auto = FormLib::get('autoapply', 0);
            $starts = FormLib::get('starts');
            if ($starts == '') {
                $starts = null;
            }

            $model = new HouseCouponsModel($dbc);
            $model->coupID($this->coupon_id);
            $model->startDate($starts);
            $model->endDate($expires);
            $model->limit($limit);
            $model->discountType($dtype);
            $model->discountValue($dval);
            $model->minType($mtype);
            $model->minValue($mval);
            $model->department($dept);
            $model->description($descript);
            $model->memberOnly($mem);
            $model->auto($auto);
            $model->save();

            $msgs[] = array('type'=>'success', 'text'=>'Updated coupon settings');

            $this->display_function = 'editClass';

            if (FormLib::get_form_value('submit_delete_upc') !== '' || FormLib::get_form_value('submit_delete_dept') !== '') {
                /**
                  Delete UPCs and departments
                */
                $query = $dbc->prepare("DELETE FROM houseCouponItems
                    WHERE upc=? AND coupID=?");
                foreach (FormLib::get_form_value('del',array()) as $upc) {
                    $dbc->execute($query,array($upc,$this->coupon_id));
                    $msgs[] = array('type'=>'success', 'text'=>'Deleted ' . $upc);
                }
            }

            foreach ($msgs as $msg) {
                $alert = '<div class="alert alert-' . $msg['type'] . '" role="alert">'
                    . '<button type="button" class="close" data-dismiss="alert">'
                    . '<span>&times;</span></button>'
                    . $msg['text'] . '</div>';
                $this->add_onload_command("\$('div.navbar-default').after('{$alert}');");
            }
        }

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
                p.upc ,
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
            $classUPC[] = $row['upc'];
            $classDate[] = substr($row['description'], 0, 8);
            $classSize[] = $row['size'];
        }
        
        echo $classUPC[0] . "<br>"; //delete me
        echo $date[0];
        
        $ret .= '<table class="table">';
        
        $ret .= '<tr><th>Date</th><th>Class</th><th>Seats</th>';
        
        foreach ($className as $key => $name) {
            $ret .= '<tr><td>' . $classDate[$key] . '</td>';
            $ret .= sprintf('<td><a href="WfcClassSignUp.php?edit_id=%d&classname=%s&date=%s">%s</a></td>',
                $classUPC[$key],
                $name,
                $classDate[$key],
                $name
            );
            
            $ret .= '<td>' . $classSize[$key] . '</td>';
            $ret .= '</tr>';
        }
        
        $ret .= '</table></form>';
        
        $dbc->close();
        
        return $ret;
    }

    private function editClass()
    {
        $FANNIE_URL = $this->config->get('URL');
        
        $dbc = $this->connection;
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $upc = $_GET['edit_id'];
        echo "UPC : " . $upc . "<br>";
        $class = $_GET['classname'];
        $date = $_GET['date'];
        
        echo "<h2 align=\"center\">" . $class . "</h2>";
        echo "<h3 align=\"center\">" . $date . "</h3>";
        echo "<div id=\"line-div\"></div>";
        
        
        
        $p1 = $dbc->prepare("CREATE TABLE IF NOT EXISTS
            wfcuRegistry (
                upc VARCHAR(13), 
                class VARCHAR(255), 
                first_name VARCHAR(30),
                last_name VARCHAR(30),
                first_opt_name VARCHAR(30),
                last_opt_name VARCHAR(30),
                phone VARCHAR(30),
                opt_phone VARCHAR(30),
                card_no INT(11),
                payment VARCHAR(30),
                refunded INT(1),
                modified DATETIME,
                store_id SMALLINT(6),
                start_time TIME,
                date_paid DATETIME,
                seat INT(50)
            );   
        ");
        $r1 = $dbc->execute($p1);
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        }
        
        $p2 = $dbc->prepare("
            SELECT upc,
                class,
                first_name,
                last_name,
                first_opt_name,
                last_opt_name,
                phone,
                opt_phone,
                card_no,
                payment,
                refunded,
                modified,
                store_id,
                start_time,
                seat
            FROM wfcuRegistry
            WHERE upc = ?
            ORDER BY seat
        ;");
        $r2 = $dbc->execute($p2, $upc);
        while ($row = $dbc->fetch_row($r2)) {
            $class = $row['class'];
            $first_name[] = $row['first_name'];
            $last_name[] = $row['last_name'];
            $first_opt_name[] = $row['first_opt_name'];
            $last_opt_name[] = $row['last_opt_name'];
            $phone[] = $row['phone'];
            $opt_phone[] = $row['opt_phone'];
            $card_no[] = $row['card_no'];
            $payment[] = $row['payment'];
            $refunded[] = $row['refunded'];
            $modified[] = $row['modified'];
            $store_id = $row['store_id'];
            $classDate = substr($row['class'], 0, 8);
            $start = $row['start_time'];
            $datePaid[] = $row['date_paid'];
            $seat[] = $row['seat'];
        }
        if (mysql_errno() > 0) {
            echo mysql_errno() . ": " . mysql_error(). "<br>";
        }
        
        echo "seat : ";
        var_dump($seat); 
        
        $this->addScript('class-signup.js');
        
        /* This was the form method I was working with...
        <form method=\"get\" 
                        onsubmit=\"saveStudent(form, first_name, last_name, card_no, 
                        phone, payment, opt_student, opt_phone)\"; return false;> */
        $ret .= "
            <div class=\"form-inline\" align=\"left\">
                <form method=\"get\">
                    <div class=\"form-group\">
                    <label>Sign-up New Student</label><br>
                        <input type=\"hidden\" name=\"edit_id\" value=\"" . $upc . "\">
                        <input type=\"hidden\" name=\"classname\" value=\"" . $class . "\">
                        <input type=\"hidden\" name=\"date\" value=\"" . $date  . "\">
                        <input type=\"text\" class=\"form-control\" name=\"first_name\" placeholder=\"First Name\">
                        <input type=\"text\" class=\"form-control\" name=\"last_name\" placeholder=\"Last Name\">
                        <input type=\"text\" class=\"form-control\" name=\"card_no\" placeholder=\"Member #\">
                        <input type=\"text\" class=\"form-control\" name=\"phone\" placeholder=\"Phone #\">
                        <select class=\"form-control\" name=\"payment\" placeholder=\"Payment Type\">
                            <option value=\"Cash\">Cash</option>
                            <option value=\"Card\">Card</option>
                            <option value=\"Gift Card\">Gift Card</option>
                            <option value=\"Check\">Check</option>
                            <option value=\"other\">other</option>
                            <option value=\"student has not paid\">*student has not paid*</option>
                        </select>
                        <button type=\"submit\" class=\"btn btn-default\">Add Student</button>
                    </div><br><br>
                    <div class=\"form-group\">
                    <label>Alternate Attendee Information : <i>if customer is buying class for someone else to attend</i></label><br>
                        <input type=\"text\" class=\"form-control\" name=\"opt_student\" placeholder=\"attendee name\">
                        <input type=\"text\" class=\"form-control\" name=\"opt_phone\" placeholder=\"attendee phone\">
                    </div>
                </form>
            </div><br><br>
        ";
        
        if ($_GET['first_name']) {
            $query = $dbc->prepare("INSERT INTO wfcuRegistry 
                (first_name) SET VALUES ( ? );
            ");
            $result = $dbc->execute($query, $_GET['first_name']);
        }
        
        $ret .= '<table class="table table-striped">';
        $ret .= '
            <tr><th>Seat</th>
            <th>First</th>
            <th>Last</th>
            <th>Member #</th>
            <th>Phone Number</th>
            <th>Payment Type</th>
            <th>opt. Attendee</th>
            <th>opt. Attendee Phone</th>
        ';

        foreach ($seat as $key => $seatNum) {
            $ret .= '<tr><td>' . $seatNum . '</td>';
            $ret .= '<td>' . $first_name[$key] . '</td>';
            $ret .= '<td>' . $last_name[$key] . '</td>';
            $ret .= '<td>' . $card_no[$key] . '</td>';
            $ret .= '<td>' . $phone[$key] . '</td>';
            $ret .= '<td>' . $first_opt_name[$key] . '</td>';
            $ret .= '<td>' . $last_opt_name[$key] . '</td>';
            $ret .= '<td>' . $opt_phone[$key] . '</tr>';
        }
        $ret .= '</table>';
        
        $dbc->close();
        
        $ret .= "<a href=\"http://localhost/IS4C/fannie/modules/plugins2.0/WfcClassSignUp/WfcClassSignUp.php\">Back to Class List</a>";
        
        return $ret;
    }

    


}

FannieDispatch::conditionalExec();

