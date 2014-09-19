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

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ScaleEditor extends FannieRESTfulPage
{
    protected $header = 'Manage Service Scales';
    protected $title = 'Manage Service Scales';

    public $description = '[Scale Editor] defines available service scales (Hobart).';

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

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">
            <tr><th>Description</th><th>Host</th><th>Type</th><th>Scale Dept.</th><th>POS Super Dept</th></tr>';
        foreach ($scales->find('description') as $scale) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="id[]" value="%d" />
                            <td><input type="text" name="description[]" value="%s" /></td>
                            <td><input type="text" name="host[]" size="10" value="%s" /></td>
                            <td><input type="text" name="type[]" size="10" value="%s" /></td>
                            <td><input type="text" name="scaleDept[]" size="10" value="%s" /></td>',
                            $scale->serviceScaleID(),
                            $scale->description(),
                            $scale->host(),
                            $scale->scaleType(),
                            $scale->scaleDeptName()
            );
            $ret .= '<td><select name="super[]"><option value="">None</option>';
            foreach ($supers as $id => $name) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                        ($scale->superID() !== null && $scale->superID() == $id ? 'selected' : ''),
                        $id, $name);
            }
            $ret .= '</select></td></tr>';
        }

        $ret .= '</table>';
        $ret .= '<br /><input type="submit" value="Save Changes" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="submit" value="Add Scale"
                    onclick="location=\'ScaleEditor.php?new=true\';return false;" />';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

