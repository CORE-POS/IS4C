<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class AuthReport extends FannieReportPage 
{
    public $discoverable = false;

    protected $title = "Fannie : Auth Report";
    protected $header = "Auth Report";
    protected $report_headers = array('Username', 'Name', 'User ID', 'Authorization', 'Via Group', 'Notes');
    protected $required_fields = array();

    public function fetch_report_data()
    {
        $dbc = $this->connection;

        $users = $dbc->query('SELECT uid, name, real_name FROM Users order by name');
        $authP = $dbc->prepare('
            SELECT v.auth_class,
                v.notes,
                \'n/a\' AS name
            FROM userKnownPrivs AS v 
                INNER JOIN userPrivs as p ON v.auth_class=p.auth_class
            WHERE p.uid=?

            UNION ALL

            SELECT v.auth_class,
                v.notes,
                u.name
            FROM userKnownPrivs AS v 
                INNER JOIN userGroupPrivs AS g ON g.auth=v.auth_class
                INNER JOIN userGroups AS u ON u.gid=g.gid
            WHERE u.username=?
 
            ORDER BY auth_class
        ');
        $data = array();
        while ($row = $dbc->fetchRow($users)) {
            $authR = $dbc->execute($authP, array($row['uid'], $row['name']));
            while ($authW = $dbc->fetchRow($authR)) {
                $data[] = array(
                    $row['name'],
                    $row['real_name'],
                    $row['uid'],
                    $authW['auth_class'],
                    $authW['name'],
                    $authW['notes'],
                );
            }
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

