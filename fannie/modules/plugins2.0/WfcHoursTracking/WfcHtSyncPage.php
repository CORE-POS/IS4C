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
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
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

    private function checkLocalAccounts($EXCLUDE_EMAILS)
    {
        $ldap = ldap_connect('ldaps://email.wholefoods.coop:636');
        $bind = ldap_bind($ldap);
        $search = ldap_search($ldap, 'ou=People,dc=wholefoods,dc=coop', 'gidnumber=100');
        $info = ldap_get_entries($ldap, $search);
        $accounts = array();
        for ($i=0; $i<$info['count']; $i++) {
            $entry = $info[$i];
            $shortname = $entry['uid'][0];
            $uid = $entry['uidnumber'][0];
            $name = $entry['cn'][0];
            $accounts[$uid] = array($shortname, $name);
        }
        $new_accounts = array();
        $dbc = WfcHtLib::hours_dbconnect();
        $chkQ = $dbc->prepare("SELECT name FROM employees WHERE name=? AND deleted=0");
        $insQ = $dbc->prepare("INSERT INTO employees VALUES (?,?,NULL,0,8,NULL,0)");
        $nextP = $dbc->prepare('SELECT MAX(empID) FROM employees');
        $next = $dbc->getValue($nextP) + 1;
        echo $next;
        foreach ($accounts as $uid => $names) {
            $shortname = $names[0];
            if (isset($EXCLUDE_EMAILS[$shortname])) {
                continue;
            }
            $name = $names[1];
            list($first, $last) = explode(' ', $name, 2);
            $name = $last . ', ' . $first;

            // create entry in hours database
            $chkR = $dbc->execute($chkQ, array($name));
            if ($dbc->numRows($chkR) == 0) {
                $new_accounts[$uid] = $shortname;
                $dbc->execute($insQ, array($next, $name));
                echo "Add ADP entry for $name<br />";
                $next++;
            }
        }

        return $new_accounts;
    }

    public function body_content()
    {
        ob_start();

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
            'jkresha'=>True,
            'wpulford'=>true,
            'receivingl'=>true,
            'receivingr'=>true,
            'denfeldcsc'=>true,
        );
        $new_accounts = $this->checkLocalAccounts($EXCLUDE_EMAILS);

        echo '<p />
            <a href="WfcHtMenuPage.php">Main Menu</a>';

        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec();

