<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Community Co-op

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

class ShrinkEditor extends FannieRESTfulPage
{
    protected $header = 'Edit Entries';
    protected $title = 'Edit Entries';
    public $themed = true;
    public $description = '[Shrink Editor] can adjust or remove shrink entries for the current day.';

    public function post_id_handler()
    {
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));

        $qty = FormLib::get('qty', array());
        $reason = FormLib::get('reason', array());
        $loss = FormLib::get('loss', array());
        $successes = 0;
        for ($i=0; $i<count($this->id); $i++) {
            $args = array();
            $json = json_decode(base64_decode($this->id[$i]), true);
            $query = 'UPDATE dtransactions SET '; 
            if (isset($qty[$i])) {
                $args[] = $qty[$i];
                $args[] = $qty[$i];
                $args[] = $qty[$i];
                $query .= ' quantity=?, ItemQtty=?, total=unitPrice*?, ';
            }
            if (isset($reason[$i])) {
                $args[] = $reason[$i];
                $query .= ' numflag=?, ';
            }
            if (isset($loss[$i])) {
                $args[] = $loss[$i];
                $query .= ' charflag=?, ';
            }
            if (count($args) == 0) {
                $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'danger', 'Invalid data for: " . json_encode($json) . "');\n");
                continue;
            }
            // trim trailing space and comma
            $query = substr($query, 0, strlen($query)-2);
            $query .= '
                WHERE ' . $dbc->datediff('datetime', $dbc->now()) . ' = 0
                    AND emp_no=?
                    AND register_no=?
                    AND trans_no=?
                    AND trans_id=?
                    AND store_id=?';
            $args[] = $json['emp_no'];
            $args[] = $json['register_no'];
            $args[] = $json['trans_no'];
            $args[] = $json['trans_id'];
            $args[] = $json['store_id'];

            $prep = $dbc->prepare($query);
            $res = $dbc->execute($prep, $args);
            if ($res) {
                $successes++;
            }
        }
        if ($successes > 0) {
            $this->addOnloadCommand("showBootstrapAlert('#alert-area', 'success', 'Updated Entries');\n");
        }

        return true;
    }

    public function post_id_view()
    {
        return '<div id="alert-area"></div>' . $this->get_view();
    }

    public function get_view()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $model = new ShrinkReasonsModel($dbc);
        $reasons = array(0 => 'n/a');
        foreach ($model->find('description') as $obj) {
            $reasons[$obj->shrinkReasonID()] = $obj->description();
        }

        $dbc = FannieDB::get($this->config->get('TRANS_DB'));

        $query = '
            SELECT upc,
                description,
                quantity,
                unitPrice,
                total,
                emp_no,
                register_no,
                trans_no,
                trans_id,
                store_id,
                charflag,
                numflag
            FROM dtransactions
            WHERE trans_status=\'Z\'
                AND trans_type IN (\'I\', \'D\')
                AND emp_no <> 9999
                AND register_no <> 99
                AND ' . $dbc->datediff('datetime', $dbc->now()) . ' = 0';
        $result = $dbc->query($query);
        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>UPC</th><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th>
                 <th>Reason</th><th>Loss</th></tr>';
        while ($row = $dbc->fetch_row($result)) {
            $id = array(
                'emp_no' => $row['emp_no'],
                'register_no' => $row['register_no'],
                'trans_no' => $row['trans_no'],
                'trans_id' => $row['trans_id'],
                'store_id' => $row['store_id'],
            );
            $id = base64_encode(json_encode($id));
            $ret .= sprintf('<tr>
                        <td>%s<input type="hidden" name="id[]" value="%s" /></td>
                        <td>%s</td>
                        <td><input type="text" name="qty[]" value="%.2f" class="form-control" /></td>
                        <td>$%.2f</td>
                        <td>$%.2f</td>',
                        $row['upc'], $id,
                        $row['description'],
                        $row['quantity'],
                        $row['unitPrice'],
                        $row['total']);
            $ret .= '<td><select name="reason[]" class="form-control">';
            foreach ($reasons as $id => $label) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                        ($id == $row['numflag'] ? 'selected' : ''),
                        $id, $label);
            }
            $ret .= '</select></td>';
            $ret .= '<td>
                        <select name="loss[]" class="form-control">
                            <option value="L">Loss</option> 
                            <option value="C" ' . ($row['charflag'] == 'C' ? 'selected' : '') . '>Contribute</option>
                        </select>
                     </td>
                     </tr>';
        }
        $ret .= '</table>';
        $ret .= '<p>
            <button type="submit" class="btn btn-default">Update Quantities</button>
            |
            <a href="ShrinkTool.php" class="btn btn-default">Enter More Items</a>
            </p>';
        $ret .= '</form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Shrink entries are for loss tracking and may be made
            via the lane or office tools. Entries from the current
            day can be edited until the day closes. Adjusting
            quantity and reason is allowed.
            </p>
            <p>
            Loss vs Contribute may be WFC specific. From an inventory
            standpoint, the item is gone either way but if it
            can be donated ("contributed") to charity that may
            be relevant for tax accounting.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->post_id_view()));
    }
}

FannieDispatch::conditionalExec();

