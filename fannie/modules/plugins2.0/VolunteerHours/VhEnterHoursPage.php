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

class VhEnterHoursPage extends FannieRESTfulPage
{
    protected $header = 'Volunteer Hours';
    protected $title = 'Volunteer Hours';
    public $description = '[Volunteer Hours Entry] adds volunteers\' hours worked.';
    protected $must_authenticate = true;
    protected $auth_classes = array('editmembers');

    public function preprocess()
    {
        $this->addRoute('get<summary>');

        return parent::preprocess();
    }

    public function post_id_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['VolunteerHoursDB']);

        $prep = $dbc->prepare('
            INSERT INTO VolunteerHoursActivity
                (tdate, cardNo, hoursWorked, hoursRedeemed, uid)
            VALUES
                (?, ?, ?, 0, ?)');

        $dates = FormLib::get('date');
        $hours = FormLib::get('hours');
        $entries = array();
        $uid = FannieAuth::getUID($this->current_user);
        for ($i=0; $i<count($this->id); $i++) {
            if (isset($dates[$i]) && isset($hours[$i]) && $hours[$i] != 0) {
                $added = $dbc->execute($prep, array(
                    $dates[$i],
                    $this->id[$i],
                    $hours[$i],
                    $uid,
                ));
                if ($added) {
                    $entries[] = $dbc->insertID();
                }
            }
        }

        if (count($entries) == 0) {
            return filter_input(INPUT_SERVER, 'PHP_SELF');
        } else {
            return '?summary=' . base64_encode(json_encode($entries));
        }
    }

    public function get_summary_view()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['VolunteerHoursDB']);
        $model = new VolunteerHoursActivityModel($dbc);

        $ids = json_decode(base64_decode($this->summary), true);
        $ret = '<table class="table table-bordered">
            <thead>
            <tr>
                <th>Account#</th>
                <th>Date</th>
                <th>Hours</th>
            </tr>
            </thead>
            <tbody>';
        foreach ($ids as $id) {
            $model->volunteerHoursActivityID($id);
            $model->load();
            $ret .= sprintf('<tr>
                <td>%d</td>
                <td>%s</td>
                <td>%.2f</td>
                </tr>',
                $model->cardNo(),
                $model->tdate(),
                $model->hoursWorked()
            );
        }
        $ret .= '</tbody></table>';

        $ret .= '<p>
            <a href="VhEnterHoursPage.php" class="btn btn-default">Enter More Hours</a>
            <a href="VhEnterHoursPage.php" class="btn btn-default btn-reset">View Accounts</a>
            </p>';

        return $ret;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['VolunteerHoursDB']);

        $query = '
            SELECT a.cardNo,
                c.FirstName,
                c.LastName,
                SUM(hoursWorked) - SUM(hoursRedeemed) AS balance,
                MAX(CASE WHEN hoursWorked > 0 THEN tdate ELSE 0 END) AS lastShift
            FROM VolunteerHoursAccountMap AS a
                LEFT JOIN VolunteerHoursActivity AS v ON a.cardNo=v.cardNo
                LEFT JOIN ' . $this->config->get('OP_DB') . $dbc->sep() . 'custdata AS c ON c.CardNo=a.cardNo AND c.personNum=1
            WHERE a.active=1
            GROUP BY a.cardNo,
                c.FirstName,
                c.LastName
            ORDER BY c.LastName,
                c.FirstName';
        $res = $dbc->query($query);

        $ret = '<form method="post">';
        $ret .= '<table class="table table-bordered">
            <thead>
            <tr>
                <th>Account#</th>
                <th>Name</th>
                <th>Current Hours Balance</th>
                <th>Last Shift Worked</th>
                <th>Post Date</th>
                <th>Post Hours</th>
            </tr>
            </thead>
            <tbody>';
        while ($row = $dbc->fetchRow($res)) {
            $ret .= sprintf('<tr>
                <td>%d<input type="hidden" name="id[]" value="%d" /></td>
                <td>%s, %s</td>
                <td>%.2f</td>
                <td>%s</td>
                <td><input type="text" class="form-control date-field" 
                    name="date[]" value="%s" /></td>
                <td><input type="text" class="form-control math-field price-field" 
                    name="hours[]" value="0" /></td>
                </tr>',
                $row['cardNo'], $row['cardNo'],
                $row['LastName'], $row['FirstName'],
                $row['balance'],
                $row['lastShift'] == 0 ? 'n/a' : $row['lastShift'],
                date('Y-m-d')
            );
        }
        $ret .= '</tbody></table>';

        $ret .= '<p>
            <button type="submit" class="btn btn-default btn-core">Post Entries</button>
            <a href="VhAccountPage.php" class="btn btn-default btn-reset">View Accounts</a>
            </p>
            </form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

