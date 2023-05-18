<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class VhAccountPage extends FannieRESTfulPage
{
    protected $header = 'Volunteer Hours';
    protected $title = 'Volunteer Hours';
    public $description = '[Volunteer Hours Accounts] lists partipating accounts and their status.';
    protected $must_authenticate = true;
    protected $auth_classes = array('editmembers');

    public function preprocess()
    {
        $this->addRoute('get<add>');
        $this->addRoute('post<add>');

        return parent::preprocess();
    }

    public function post_add_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $custdata = new CustdataModel($dbc);
        $custdata->CardNo($this->add);
        $custdata->personNum(1);
        if ($custdata->load()) {
            $settings = $this->config->get('PLUGIN_SETTINGS');
            $dbc->selectDB($settings['VolunteerHoursDB']);
            $model = new VolunteerHoursAccountMapModel($dbc);
            $model->cardNo($this->add);
            if (count($model->find()) == 0) {
                $model->save();
            }

            return filter_input(INPUT_SERVER, 'PHP_SELF');
        } else {
            return true;
        }
    }

    public function post_add_view()
    {
        return '<div class="alert alert-danger">No such account</div>'
            . $this->get_add_view();
    }

    public function get_add_view()
    {
        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">
            <div class="form-group">
            <label>Account#</label>
            <input type="text" name="add" class="form-control">
            </div>
            <div class="form-group">
            <button type="submit" class="btn btn-default">Add Account</button>
            </div>
            </form>';
        $this->addOnloadCommand("\$('input:first').focus();\n");

        return $ret;
    }

    public function post_id_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['VolunteerHoursDB']);
        $active = FormLib::get('active', 1);
        $delete = FormLib::get('delete');
        if ($delete == 1) {
            $prep = $dbc->prepare('
                DELETE FROM VolunteerHoursAccountMap
                WHERE cardNo=?');
            $res = $dbc->execute($prep, array($this->id));

            return filter_input(INPUT_SERVER, 'PHP_SELF');
        } else {
            $prep = $dbc->prepare('
                UPDATE VolunteerHoursAccountMap
                SET active=?
                WHERE cardNo=?');
            $res = $dbc->execute($prep, array($active, $this->id));

            return '?id=' . $this->id;
        }
    }

    public function get_id_view()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['VolunteerHoursDB']);

        $query = '
            SELECT a.cardNo,
                c.FirstName,
                c.LastName,
                a.active
            FROM VolunteerHoursAccountMap AS a
                LEFT JOIN ' . $this->config->get('OP_DB') . $dbc->sep() . 'custdata AS c ON c.CardNo=a.cardNo AND c.personNum=1
            WHERE a.cardNo=?';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($this->id));
        if ($res === false || $dbc->numRows($res) == 0) {
            return '<div class="alert alert-danger">Invalid Account</div>';
        }

        $row = $dbc->fetchRow($res);

        $ret = sprintf('<h3>%d %s, %s</h3>', $this->id, $row['LastName'], $row['FirstName']);
        $ret .= '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />';
        $opts = array('Inactive', 'Active');
        $ret .= '<div class="form-group">
            <label>Volunteer Status</label>
            <select name="active" class="form-control">';
        foreach ($opts as $key => $val) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($key == $row['active'] ? 'selected' : ''),
                $key, $val);
        }
        $ret .= '</select>
            </div>';

        $historyP = $dbc->prepare('
            SELECT tdate,
                hoursWorked,
                uid,
                hoursRedeemed,
                transNum
            FROM VolunteerHoursActivity
            WHERE cardNo=?
            ORDER BY tdate DESC');
        $historyR = $dbc->execute($historyP, array($this->id));
        if ($dbc->numRows($historyR) == 0) {
            $ret .= '<div class="form-group">
                <label><input type="checkbox" name="delete" value="1" /> Delete Volunteer Account</label>
                </div>';
        }
        $ret .= '<div class="form-group">
            <button type="submit" class="btn btn-default btn-core">Save Account</button>
            <a href="?active=1" class="btn btn-default btn-reset">Home</a>
            </div>
            </form>';

        $ret .= '<table class="table table-bordered">
            <thead>
            <tr>
                <th>Date</th>
                <th>Hours Worked</th>
                <th>Hours Redeemed</th>
            </tr>
            </thead>
            <tbody>';
        while ($row = $dbc->fetchRow($historyR)) {
            $ret .= sprintf('<tr>
                <td>%s</td>
                <td>%.2f</td>
                <td>%.2f</td>
                </tr>',
                $row['tdate'],
                $row['hoursWorked'],
                $row['hoursRedeemed']
            );
        }
        $ret .= '</tbody></table>';

        return $ret;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['VolunteerHoursDB']);
        $active = FormLib::get('active', 1);

        $ret = '<div class="form-group form-inline">
            Showing <select class="form-control input-sm" 
            onchange="location=\'?active=\'+this.value;">';
        $opts = array('Inactive', 'Active');
        foreach ($opts as $key => $val) {
            $ret .= sprintf('<option %s value="%d">%s</option>',
                ($key == $active ? 'selected' : ''),
                $key, $val);
        }
        $ret .= '</select> volunteers</div>';

        $query = '
            SELECT a.cardNo,
                c.FirstName,
                c.LastName,
                SUM(hoursWorked) as ttlWorked,
                SUM(hoursRedeemed) as ttlRedeemed
            FROM VolunteerHoursAccountMap AS a
                LEFT JOIN VolunteerHoursActivity AS v ON a.cardNo=v.cardNo
                LEFT JOIN ' . $this->config->get('OP_DB') . $dbc->sep() . 'custdata AS c ON c.CardNo=a.cardNo AND c.personNum=1
            WHERE a.active=?
            GROUP BY a.cardNo,
                c.FirstName,
                c.LastName
            ORDER BY c.LastName,
                c.FirstName';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($active));

        $ret .= '<table class="table table-bordered">
            <thead>
            <tr>
                <th>Account#</th>
                <th>Name</th>
                <th>Hours Worked</th>
                <th>Hours Redeemed</th>
                <th>Current Balance</th>
                <td>&nbsp;</td>
            </tr>
            </thead>
            <tbody>';
        while ($row = $dbc->fetchRow($res)) {
            $ret .= sprintf('<tr>
                <td>%d</td>
                <td>%s, %s</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td><a href="?id=%d" class="btn btn-default btn-xs">%s</a></tr>
                </tr>',
                $row['cardNo'],
                $row['LastName'], $row['FirstName'],
                $row['ttlWorked'],
                $row['ttlRedeemed'],
                $row['ttlWorked'] - $row['ttlRedeemed'],
                $row['cardNo'],
                \COREPOS\Fannie\API\lib\FannieUI::editIcon()
            );
        }
        $ret .= '</tbody></table>';

        $ret .= '<p>
            <a href="?add=add" class="btn btn-default">Add Account</a>
            <a href="VhEnterHoursPage.php" class="btn btn-default btn-reset">Enter Hours</a>
            </p>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

