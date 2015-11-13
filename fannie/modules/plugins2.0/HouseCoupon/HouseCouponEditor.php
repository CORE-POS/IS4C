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
class HouseCouponEditor extends FanniePage 
{

    public $description = "[Module] for managing in store coupons";
    public $themed = true;

    protected $must_authenticate = true;
    protected $auth_classes = array('tenders');

    protected $header = "Fannie :: House Coupons";
    protected $title = "House Coupons";

    private $display_function;
    private $coupon_id;

    public function preprocess()
    {
        $this->display_function = 'listHouseCoupons';

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

        } elseif (FormLib::get_form_value('edit_id','') !== '') {
            $this->coupon_id = (int)FormLib::get_form_value('edit_id',0);
            $this->display_function = 'editCoupon';
        } elseif (FormLib::get_form_value('new_coupon_submit') !== '') {
            $dbc = FannieDB::get($this->config->get('OP_DB'));

            $maxQ = $dbc->prepare_statement("SELECT max(coupID) from houseCoupons");
            $maxR = $dbc->execute($maxQ);
            $max = 0;
            if ($maxR && $dbc->numRows($maxR)) {
                $maxW = $dbc->fetchRow($maxR);
                $max = $maxW[0];
            }
            $this->coupon_id = $max+1;
            
            $insQ = $dbc->prepare_statement("INSERT INTO houseCoupons (coupID) values (?)");
            $dbc->exec_statement($insQ,array($this->coupon_id));

            $this->display_function='editCoupon';

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

            $this->display_function = 'editCoupon';

            if (FormLib::get_form_value('submit_delete_upc') !== '' || FormLib::get_form_value('submit_delete_dept') !== '') {
                /**
                  Delete UPCs and departments
                */
                $query = $dbc->prepare_statement("DELETE FROM houseCouponItems
                    WHERE upc=? AND coupID=?");
                foreach (FormLib::get_form_value('del',array()) as $upc) {
                    $dbc->exec_statement($query,array($upc,$this->coupon_id));
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

    private function listHouseCoupons()
    {
        $FANNIE_URL = $this->config->get('URL');

        $this->add_script($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.js?v=1');
        $this->add_css_file($FANNIE_URL . 'src/javascript/fancybox/jquery.fancybox-1.3.4.css');
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $ret = '<form action="HouseCouponEditor.php" method="get">';
        $ret .= '<p>';
        $ret .= '<button type="submit" name="new_coupon_submit" 
            class="btn btn-default" value="New Coupon">New Coupon</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="button" class="fancybox-btn btn btn-default"
            href="explainify.html">Explanation of Settings</button>';
        $this->add_onload_command('$(\'.fancybox-btn\').fancybox();');
        $ret .= '</p>';
        $ret .= '</form>';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>ID</th><th>Name</th><th>Value</th><th>Expires</th></tr>';
        $model = new HouseCouponsModel($dbc);
        foreach($model->find('coupID') as $obj) {
            if (strstr($obj->endDate(), ' ')) {
                $tmp = explode(' ', $obj->endDate());
                $obj->endDate($tmp[0]);
            }
            $report_dates = array(
                date('Y-m-d', strtotime($obj->startDate())),
                date('Y-m-d', strtotime($obj->endDate())),
            );
            /**
              If coupon period is more than 45 days, use the current month
              as a reporting period
            */
            if (strtotime($report_dates[1]) - strtotime($report_dates[0]) > (86400 * 45)) {
                $report_dates = array(date('Y-m-01'), date('Y-m-t'));
            }
            $ret .= sprintf('<tr><td>#%d <a href="HouseCouponEditor.php?edit_id=%d">Edit</a></td>
                    <td>%s</td><td>%.2f%s</td><td>%s</td>
                    <td>
                        <a href="%sws/barcode-pdf/?upc=%s&name=%s"
                        class="btn btn-default">Print Barcode</a>
                        <a href="%sreports/ProductMovement/ProductMovementModular.php?upc=%s&date1=%s&date2=%s"
                        class="btn btn-default">Usage Report</a>
                        <a href="%smodules/plugins2.0/CoreWarehouse/reports/CWCouponReport.php?coupon-id=%d&date1=%s&date2=%s"
                        class="btn btn-default %s">Member Baskets</a>
                    </tr>',
                    $obj->coupID(),$obj->coupID(),$obj->description(),
                    $obj->discountValue(), $obj->discountType(), $obj->endDate(),
                    $FANNIE_URL,
                    ('499999' . str_pad($obj->coupID(), 5, '0', STR_PAD_LEFT)),
                    urlencode($obj->description()),
                    $FANNIE_URL,
                    ('499999' . str_pad($obj->coupID(), 5, '0', STR_PAD_LEFT)),
                    $report_dates[0],
                    $report_dates[1],
                    $FANNIE_URL,
                    $obj->coupID(),
                    $report_dates[0],
                    $report_dates[1],
                    (\COREPOS\Fannie\API\FanniePlugin::isEnabled('CoreWarehouse') ? '' : 'collapse')
                );
        }
        $ret .= '</table>';
        
        $dbc->close();

        return $ret;
    }

    private function editCoupon()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        
        $depts = array();
        $query = $dbc->prepare_statement("SELECT dept_no,dept_name FROM departments ORDER BY dept_no");
        $result = $dbc->exec_statement($query);
        while($row = $dbc->fetch_row($result)){
            $depts[$row[0]] = $row[1];
        }

        $cid = $this->coupon_id;
        $model = new HouseCouponsModel($dbc);
        $model->coupID($cid);
        $model->load();

        $starts = $model->startDate();
        if (strstr($starts,' ')) {
            list($starts, $time) = explode(' ',$starts, 2);
        }
        $expires = $model->endDate();
        if (strstr($expires,' ')) {
            list($expires, $time) = explode(' ',$expires, 2);
        }
        $limit = $model->limit();
        $mem = $model->memberOnly();
        $dType = $model->discountType();
        $dVal = $model->discountValue();
        $mType = $model->minType();
        $mVal = $model->minValue();
        $dept = $model->department();
        $description = $model->description();
        $auto = $model->auto();

        $ret = '<form class="form-horizontal" action="HouseCouponEditor.php" method="post">';
        $ret .= '<input type="hidden" name="cid" value="'.$cid.'" />';

        $ret .= sprintf('
            <div class="row">
                <div class="col-sm-1 text-right">Coupon ID#</div>
                <div class="col-sm-3 text-left">%s</div>
                <div class="col-sm-1 text-right">UPC</div>
                <div class="col-sm-3 text-left">
                    <a href="%sws/barcode-pdf/?upc=%s&name=%s">%s</a>
                </div>
            </div>
            <div class="row">
                <label class="col-sm-1 control-label">Label</label>
                <div class="col-sm-3"><input type=text name=description value="%s" class="form-control" /></div>
                <label class="col-sm-1 control-label">Limit</label>
                <div class="col-sm-3"><input type=text name=limit class="form-control" value="%s" /></div>
            </div>
            <div class="row">
                <label class="col-sm-1 control-label">Begins</label>
                <div class="col-sm-3">
                    <input type=text name=starts value="%s" 
                        id="starts" class="form-control date-field" />
                </div>
                <label class="col-sm-1 control-label">Expires</label>
                <div class="col-sm-3">
                    <input type=text name=expires value="%s" 
                        id="expires" class="form-control date-field" />
                </div>
            </div>
            <div class="row">
                <label class="col-sm-2">Member Only
                <input type=checkbox name=memberonly id=memberonly value="1" %s />
                </label>
                <label class="col-sm-2">Auto-apply
                <input type=checkbox name=autoapply id=autoapply value="1" %s />
                </label>
                <label class="col-sm-1 control-label">Department</label>
                <div class="col-sm-3"><select class="form-control" name=dept>',
            $cid,
            $this->config->get('URL'),
            "00499999".str_pad($cid,5,'0',STR_PAD_LEFT),
            urlencode($description),
            "00499999".str_pad($cid,5,'0',STR_PAD_LEFT),
            $description,
            $limit,
            $starts, $expires,
            ($mem==1?'checked':''),
            ($auto==1?'checked':'') 
        );
        foreach($depts as $k=>$v){
            $ret .= "<option value=\"$k\"";
            if ($k == $dept) $ret .= " selected";
            $ret .= ">$k $v</option>";
        }
        $ret .= "</select></div>
            </div>";

        $mts = array(
            'Q'=>'Quantity (at least)',
            'Q+'=>'Quantity (more than)',
            'C'=>'Department (at least qty)',
            'C+'=>'Department (more than qty)',
            'D'=>'Department (at least $)',
            'D+'=>'Department (more than $)',
            'M'=>'Mixed (Item+Item)',
            'MX'=>'Mixed (Department+Item)',
            '$'=>'Total (at least $)',
            '$+'=>'Total (more than $)',
            ''=>'No minimum'
        );
        $ret .= '<div class="row">
            <label class="col-sm-1 control-label">Minimum Type</label>
            <div class="col-sm-3">
            <select class="form-control" name=mtype>';
        foreach ($mts as $k=>$v) {
            $ret .= "<option value=\"$k\"";
            if ($k == $mType) $ret .= " selected";
            $ret .= ">$v</option>";
        }
        $ret .= "</select></div>
            <label class=\"col-sm-1 control-label\">Minimum value</label>
            <div class=\"col-sm-3\"><input class=\"form-control\" type=text name=mval value=\"$mVal\"
             /></div>
             </div>";

        $dts = array('Q'=>'Quantity Discount',
            'P'=>'Set Price Discount',
            'FI'=>'Scaling Discount (Item)',
            'FD'=>'Scaling Discount (Department)',
            'MD'=>'Capped Discount (Department)',
            'F'=>'Flat Discount',
            'PI'=>'Per-Item Discount',
            'BG'=>'BOGO (Buy one get one)',
            '%'=>'Percent Discount (End of transaction)',
            '%B' => 'Percent Discount (Coupon discount OR member discount)',
            '%D'=>'Percent Discount (Department)',
            'PD'=>'Percent Discount (Anytime)',
            'AD'=>'All Discount (Department)',
        );
        $ret .= '<div class="row">
            <label class="col-sm-1 control-label">Discount Type</label>
            <div class="col-sm-3">
            <select class="form-control" name=dtype>';
        foreach ($dts as $k=>$v) {
            $ret .= "<option value=\"$k\"";
            if ($k == $dType) $ret .= " selected";
            $ret .= ">$v</option>";
        }
        $ret .= "</select></div>
            <label class=\"col-sm-1 control-label\">Discount value</label>
            <div class=\"col-sm-3\"><input type=text name=dval value=\"$dVal\"
            class=\"form-control\" /></div>
            </div>";

        $ret .= "<br /><button type=submit name=submit_save value=Save class=\"btn btn-default btn-core\">
            Save Settings</button>";
        $ret .= ' <button type="button" value="Back" class="btn btn-default btn-reset" 
            onclick="location=\'HouseCouponEditor.php\';return false;">Back to List of Coupons</button>';

        $ret .= "<hr />";
        $ret .= '<div class="container-fluid">';
        $ret .= '<div class="form-group form-inline" id="add-item-form">';
        if ($mType == "Q" || $mType == "Q+" || $mType == "M" || $mType == 'MX') {
            $ret .= '<label class="control-label">Add UPC</label>
                <input type=text class="form-control add-item-field" name=new_upc /> ';
        } 
        if ($mType == "D" || $mType == "D+" || $mType == 'C' || $mType == 'C+' || $dType == '%D' || $mType == 'MX') {
            $ret .= '
                <label class="control-label">Add Dept</label>
                <select class="form-control add-item-field" name=new_dept>
                <option value="">Select...</option>';
            foreach ($depts as $k=>$v) {
                $ret .= "<option value=\"$k\"";
                $ret .= ">$k $v</option> ";
            }   
            $ret .= "</select> ";
        }
        if ($mType != 'MX') {
            $ret .= '<select class="form-control"name=newtype><option>BOTH</option><option>QUALIFIER</option>
                    <option>DISCOUNT</option></select>';
        }
        $ret .= ' 
                <input type="hidden" name="id" value="' . $cid . '" />
                <button type="button" class="btn btn-default"
                    onclick="addItemToCoupon(); return false;">Add</button>';
        $ret .= '</div>';
        $ret .= '</div>';

        $ret .= "<table class=\"table\" id=\"coupon-item-table\">";
        $ret .= '<tr><td colspan="4">Items</tr>';
        $ret .= $this->couponItemTable($cid);
        $ret .= '</table>';
        $ret .= "<p><button type=submit name=submit_delete_dept value=\"1\"
            class=\"btn btn-default\">Delete Selected Departments</button></p>";

        return $ret;
    }

    public function javascriptContent()
    {
        ob_start();
        ?>
        function addItemToCoupon()
        {
            var dataStr = $('#add-item-form :input').serialize();
            dataStr += '&ajax-add=1';
            $.ajax({
                type: 'post',
                data: dataStr,
                success: function(resp) {
                    $('#coupon-item-table').html(resp);
                    $('.add-item-field').val('');
                }
            });
        }
        <?php
        return ob_get_clean();
    }

    private function couponItemTable($id)
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $hc = new HouseCouponsModel($dbc);
        $hc->coupID($id);
        $hc->load();
        $query = '
            SELECT h.upc,
                COALESCE(p.description, \'Unknown item\') AS description,
                h.type
            FROM houseCouponItems AS h
                ' . DTrans::joinProducts('h') . '
            WHERE h.coupID=?';
        if ($hc->minType() == 'MX') {
            $query = "
                SELECT h.upc,
                    CASE WHEN h.type='QUALIFIER' THEN d.dept_name ELSE p.description END as description,
                    h.type
                FROM houseCouponItems AS h
                    LEFT JOIN products AS p ON p.upc=h.upc AND h.type='DISCOUNT'
                    LEFT JOIN departments AS d ON h.upc=d.dept_no AND h.type='QUALIFIER'
                WHERE h.coupID=?";
        } elseif ($hc->minType() == "D" || $hc->minType() == "D+" || $hc->minType() == 'C' || $hc->minType() == 'C+' || $hc->discountType() == '%D') {
            $query = '
                SELECT h.upc,
                    COALESCE(d.dept_name, \'Unknown department\') AS description,
                    h.type
                FROM houseCouponItems AS h
                    LEFT JOIN departments AS d ON d.dept_no=h.upc
                WHERE h.coupID=?';
        }
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($id));
        $ret = '';
        while ($w = $dbc->fetch_row($result)) {
            $ret .= sprintf('<tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td><input type="checkbox" name="del[]" value="%s" /></td>
                </tr>',
                $w['upc'],
                $w['description'],
                $w['type'],
                $w['upc']);
        }

        return $ret;
    }

    public function helpContent()
    {
        $help = file_get_contents(dirname(__FILE__) . '/explainify.html');
        $extract = preg_match('/<body>(.*)<\/body>/ms', $help, $matches);
        if ($extract) {
            return $matches[1];
        } else {
            return $help;
        }
    }
}

FannieDispatch::conditionalExec();

