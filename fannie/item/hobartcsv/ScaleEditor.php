<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Community Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ScaleEditor extends FannieRESTfulPage
{
    protected $header = 'Manage Service Scales';
    protected $title = 'Manage Service Scales';

    public $description = '[Scale Editor] defines available service scales (Hobart).';
    public $themed = true;

    public function preprocess()
    {
        $this->__routes[] = 'get<new>'; 

        return parent::preprocess();
    }

    public function get_new_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $scale = new ServiceScalesModel($dbc);

        $scale->description('NEW SCALE');
        $scale->save();

        header('Location: ScaleEditor.php');

        return false;
    }

    public function post_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $scale = new ServiceScalesModel($dbc);

        if (!is_array($this->id)) {
            echo _('Error: invalid input');
            return false;
        }

        $descriptions = FormLib::get('description', array());
        $hosts = FormLib::get('host', array());
        $types = FormLib::get('type', array());
        $dept = FormLib::get('scaleDept', array());
        $super = FormLib::get('super', array());
        $ep_st = FormLib::get('store-no', array());
        $ep_dept = FormLib::get('dept-no', array());
        $ep_addr = FormLib::get('address-no', array());

        for ($i=0; $i<count($this->id); $i++) {
            $scale->reset();
            $scale->serviceScaleID($this->id[$i]);
            if (isset($descriptions[$i])) {
                $scale->description($descriptions[$i]);
            }
            if (isset($hosts[$i])) {
                $scale->host($hosts[$i]);
            }
            if (isset($types[$i])) {
                $scale->scaleType($types[$i]);
            }
            if (isset($dept[$i])) {
                $scale->scaleDeptName($dept[$i]);
            }
            if (isset($super[$i])) {
                if ($super[$i] === '') {
                    $super[$i] = null;
                }
                $scale->superID($super[$i]);
            }
            if (isset($ep_st[$i])) {
                $scale->epStoreNo($ep_st[$i]);
            }
            if (isset($ep_dept[$i])) {
                $scale->epDeptNo($ep_dept[$i]);
            }
            if (isset($ep_addr[$i])) {
                $scale->epScaleAddress($ep_addr[$i]);
            }
            $scale->save();
        }

        header('Location: ScaleEditor.php');

        return false;
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $scales = new ServiceScalesModel($dbc);

        $supers = array();
        $result = $dbc->query('SELECT superID, super_name FROM superDeptNames ORDER BY super_name');
        while ($row = $dbc->fetch_row($result)) {
            $supers[$row['superID']] = $row['super_name'];
        }

        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">';
        
        $ret .= '<table class="table">
            <tr><th>Description</th><th>Host</th><th>Type</th><th>Scale Dept.</th><th>POS Super Dept</th>
            <th>Store # (EP)</th><th>Dept # (EP)</th><th>Address # (EP)</th>
            </tr>';
        foreach ($scales->find('description') as $scale) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="id[]" value="%d" />
                            <td><input type="text" name="description[]" 
                                class="form-control" value="%s" /></td>
                            <td><input type="text" name="host[]" 
                                class="form-control" value="%s" /></td>
                            <td><input type="text" name="type[]" 
                                class="form-control" value="%s" /></td>
                            <td><input type="text" name="scaleDept[]" 
                                class="form-control" value="%s" /></td>
                                ',
                            $scale->serviceScaleID(),
                            $scale->description(),
                            $scale->host(),
                            $scale->scaleType(),
                            $scale->scaleDeptName()
            );
            $ret .= '<td><select name="super[]" class="form-control">
                        <option value="">None</option>';
            foreach ($supers as $id => $name) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                        ($scale->superID() !== null && $scale->superID() == $id ? 'selected' : ''),
                        $id, $name);
            }
            $ret .= '</select></td>';
            $ret .= sprintf('
                        <td><input type="text" name="store-no[]"
                            class="form-control" value="%d" /></td>
                        <td><input type="text" name="dept-no[]"
                            class="form-control" value="%d" /></td>
                        <td><input type="text" name="address-no[]"
                            class="form-control" value="%d" /></td>',
                        $scale->epStoreNo(),
                        $scale->epDeptNo(),
                        $scale->epScaleAddress()
            );
            $ret .= '</tr>';
        }

        $ret .= '</table>';
        $ret .= '<p><button type="submit" class="btn btn-default">Save Changes</button>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="button" class="btn btn-default"
                    onclick="location=\'ScaleEditor.php?new=true\';return false;">Add Scale</button>
                 </p>';
        $ret .= '</form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Define the name, type, and network location of service scales.
            Currently only Hobart Quantums are supported. Data Gate Weigh
            still has to be configured separately with similar information.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

