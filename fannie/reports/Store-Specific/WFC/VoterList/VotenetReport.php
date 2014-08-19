<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

include('../../../../config.php');
include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

class VotenetReport extends FannieReportPage
{
    protected $report_headers = array('User Name', 'Password', 'First Name', 'Last Name', 'Email Address');

    public function fetch_report_data()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $q = "
            SELECT c.CardNo,
                c.FirstName,
                c.LastName,
                m.email_1
            FROM custdata AS c
                LEFT JOIn meminfo AS m ON c.CardNo=m.card_no
            WHERE c.personNum=1
                AND c.Type='PC'
                AND c.memType IN (1,3)
                AND c.LastName <> 'NEW MEMBER'
            ORDER BY c.CardNo
        ";
        $r = $dbc->query($q);
        $data = array();

        while ($w = $dbc->fetch_row($r)) {
            $record = array(
                str_pad($w['CardNo'], 5, '0', STR_PAD_LEFT),
                $w['FirstName'],
                $w['FirstName'],
                $w['LastName'],
                $w['email_1'],
            );
            if (!filter_var($record[4], FILTER_VALIDATE_EMAIL)) {
                $record[4] = 'noreply@wholefoods.coop';
            }
            $record[1] = strtolower($record[1]);
            $record[1] = trim($record[1]);
            $record[1] = str_replace('.', '', $record[1]);
            if (strpos($record[1], ' ') > 0) {
                $parts = explode(' ', $record[1]);
                $fixed = '';
                $long_part = false;
                for ($i=0; $i<count($parts); $i++) {
                    if ($i == count($parts)-1) {
                        // last piece; omit single
                        // letters to avoid middle initials
                        if (strlen($parts[$i]) > 1 || !$long_part) {
                            $fixed .= $parts[$i];
                        }
                    } else {
                        $fixed .= $parts[$i];
                        if (strlen($parts[$i]) > 1) {
                            $long_part = true;
                        }
                    }
                }
                $record[1] = $fixed;
            }
            $data[] = $record;
        }

        return $data;
    }
}

FannieDispatch::conditionalExec();

