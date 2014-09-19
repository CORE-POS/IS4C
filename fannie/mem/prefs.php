<?php 
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op, Duluth, MN

    This file is part of Fannie.

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
include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$header = "Customer Preferences";
$page_title = "Fannie :: Customer Preferences";

$cardno = isset($_REQUEST['memID']) ? $_REQUEST['memID'] : 0;

include($FANNIE_ROOT.'src/header.html');

if ($cardno == 0){
    echo '<i>Error - no member specified</i>';
}
else {

    if (isset($_REQUEST['savebtn'])){
        $pk = isset($_REQUEST['pref_k']) ? $_REQUEST['pref_k'] : array();
        $pv = isset($_REQUEST['pref_v']) ? $_REQUEST['pref_v'] : array();
        if (is_array($pk) && is_array($pv) && count($pk)==count($pv)){
            $availModel = new CustAvailablePrefsModel($dbc);
            $prefModel = new CustPreferencesModel($dbc);
            for($i=0;$i<count($pk);$i++) {
                $availModel->pref_key($pk[$i]);
                $availModel->load();

                $prefModel->pref_key($pk[$i]);
                $prefModel->card_no($cardno);
                $prefModel->custAvailablePrefID($availModel->custAvailablePrefID());
                $prefModel->pref_value($pv[$i]);
                $prefModel->save();
            }
            echo '<div align="center"><i>Settings Saved</i></div>';
        }
    }

    printf('<h3>Account #%d</h3>',$cardno);
    echo '<form action="prefs.php" method="post">';
    printf('<input type="hidden" value="%d" name="memID" />',$cardno);

    $prefQ = $dbc->prepare_statement("SELECT a.pref_key,
        CASE WHEN c.pref_value IS NULL THEN a.pref_default_value ELSE c.pref_value END
        AS current_value,
        a.pref_description
        FROM custAvailablePrefs AS a
        LEFT JOIN custPreferences AS c
        ON a.pref_key=c.pref_key AND c.card_no=?
        ORDER BY a.pref_key");
    $prefR = $dbc->exec_statement($prefQ,array($cardno));
    echo '<table cellpadding="4" cellspacing="0" border="1">';
    echo '<tr><th>Setting</th><th>Value</th></tr>'; 
    while($prefW = $dbc->fetch_row($prefR)){
        printf('<tr><td>%s</td>
            <td><input type="text" name="pref_v[]" value="%s" /></td>
            </tr><input type="hidden" name="pref_k[]" value="%s" />',
            $prefW['pref_description'],
            $prefW['current_value'],
            $prefW['pref_key']
        );
    }
    echo '</table><br />';
    echo '<input type="submit" value="Save" name="savebtn" />';
    echo '</form>';
}

include($FANNIE_ROOT.'src/footer.html');

?>
