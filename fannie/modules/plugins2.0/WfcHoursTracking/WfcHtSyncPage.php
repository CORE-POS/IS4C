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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('WfcHtLib')) {
    require(dirname(__FILE__).'/WfcHtLib.php');
}

class WfcHtSyncPage extends FanniePage
{
    protected $must_authenticate = true;
    protected $header = 'Sync';
    protected $title = 'Sync';

    public $page_set = 'Plugin :: WFC Hours Tracking';
    public $description = '[Sync Accounts] brings unix account in sync with web accounts.';

    public function body_content()
    {
        global $FANNIE_OP_DB;

        $USER_FILE = '/etc/passwd';
        $EXCLUDE_EMAILS = array(
            'root'=>True,
            'finance'=>True,
            'pop'=>True,
            'quickbooks'=>True,
            'testuser'=>True,
            'printer'=>True,
            'games'=>True,
            'csc'=>True,
            'ldap'=>True,
            'relfvin'=>True,
            'jkresha'=>True
        );

        $new_accounts = array();
        $ret = '';

        $db = WfcHtLib::hours_dbconnect();
        $fp = fopen($USER_FILE,'r');
        $chkQ = $db->prepare_statement("SELECT empID FROM employees WHERE empID=?");
        $insQ = $db->prepare_statement("INSERT INTO employees VALUES (?,?,NULL,0,8,NULL,0)");
        while( ($line = fgets($fp)) !== false ){
            // extract users with group 100 from unix passwd file
            $fields = explode(":",$line);
            $uid = $fields[2];
            $group = $fields[3];
            if ($group != "100") {
                continue;
            }

            $shortname = $fields[0];
            if (isset($EXCLUDE_EMAILS[$shortname])) {
                continue;
            }

            // reformat name as "last, first"
            $tmp = explode(" ",$fields[4]);
            $name = "";
            for($i=1;$i<count($tmp);$i++) {
                $name .= $tmp[$i]." ";
            }
            if (count($tmp) > 1) {
                $name = trim($name).", ";
            }
            if (count($tmp) == 0) {
                $name = $shortname;
            } else {
                $name .= $tmp[0];
            }

            // create entry in hours database
            $chkR = $db->exec_statement($chkQ, array($uid));
            if ($db->num_rows($chkR) == 0) {
                $new_accounts[$uid] = $shortname;
                $db->exec_statement($insQ, array($uid, $name));
                $ret .= "Added ADP entry for $name<br />";
            }
        }
        fclose($fp);

        /**
          Create corresponding POS user accounts
        */
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $chkQ = $dbc->prepare_statement("SELECT uid FROM Users WHERE uid=?");
        $insQ = $dbc->prepare_statement("INSERT INTO Users VALUES (?,'','',?,'','')");
        foreach($new_accounts as $uid => $uname){
            $uid = str_pad($uid,4,'0',STR_PAD_LEFT);
            $chkR = $dbc->exec_statement($chkQ, array($uid));
            if ($dbc->num_rows($chkR) == 0) {
                $dbc->exec_statement($insQ, array($uname, $uid));
                $ret .= "Added user account for $uname<br />";
            }
        }

        if (count($new_accounts) == 0){
            $ret .= '<i>No new employees found</i><br />';
        }

        $ret .= '<p />
            <a href="WfcHtMenuPage.php">Main Menu</a>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

