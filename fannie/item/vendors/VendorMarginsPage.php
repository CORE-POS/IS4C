<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class VendorMarginsPage extends FannieRESTfulPage 
{
    protected $header = 'Vendor Specific Margins';
    protected $title = 'Vendor Specific Margins';
    public $description = '[Vendor Specific Margins] show and set margins for POS departments on a per-vendor basis.';

    protected function post_id_handler()
    {
        $model = new VendorSpecificMarginsModel($this->connection);
        $vendor = FormLib::get('vendorID');
        $margins = FormLib::get('margin');
        for ($i=0; $i<count($this->id); $i++) {
            $model->deptID($this->id[$i]);
            $model->vendorID($vendor);
            $model->margin(isset($margins[$i]) ? $margins[$i]/100.00 : 0);
            $model->save();
        }

        return '?id=' . $vendor;
    }

    protected function get_id_view()
    {
        $prep = $this->connection->prepare('
            SELECT d.dept_name,
                v.margin,
                d.dept_no AS deptID
            FROM departments AS d
                LEFT JOIN VendorSpecificMargins AS v ON v.deptID=d.dept_no AND v.vendorID=?
            ORDER BY d.dept_no
        ');
        $res = $this->connection->execute($prep, array($this->id));
        $ret = '<form method="post" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '">';
        $ret .= '<table class="table table-bordered table-striped">
                <tr><th>Dept#</th><th>Name</th><th>Margin</th></tr>';
        while ($row = $this->connection->fetchRow($res)) {
            $ret .= sprintf('<tr>
                <td>%d<input type="hidden" name="id[]" value="%d" /></td>
                <td>%s</td>
                <td>
                    <div class="input-group">
                    <input type="text" class="form-control" name="margin[]" value="%.2f" />
                    <span class="input-group-addon">%%</span>
                    </div>
                </td>
                </tr>',
                $row['deptID'],
                $row['deptID'],
                $row['dept_name'],
                100*$row['margin']
            );
        }
        $ret .= '</table>
            <input type="hidden" name="vendorID" value="' . $this->id . '" />
            <p><button type="submit" class="btn btn-default btn-core">Save</button></p>
            </form>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }

    public function helpContent()
    {
        return '<p>Set margin targets that are specific to both a vendor and a POS department. 
A value of zero means there is no target for this vendor & POS department combination.</p>
<p>These are the highest priority margin targets ahead of both general margin targets for
POS departments and margin targets for vendor-specific subcategories.</p>';
    }
}

FannieDispatch::conditionalExec();

