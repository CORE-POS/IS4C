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

class MemberPreferences extends FannieRESTfulPage
{
    protected $header = "Customer Preferences";
    protected $title = "Fannie :: Customer Preferences";
    public $themed = true;

    public function get_view()
    {
        return '<div class="alert alert-danger">Error - no member specified</div>';
    }

    public function get_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $cardno = $this->id;
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
                $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Saved Settings');\n");
            }
        }

        return true;
    }

    public function get_id_view()
    {
        if ($this->id == 0) {
            return get_view();
        }
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $cardno = $this->id;
        $ret = sprintf('<h3>Account #%d</h3>',$cardno);
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
        $ret .= sprintf('<input type="hidden" value="%d" name="id" />',$cardno);
        $ret .= '<div id="alert-area"></div>';

        $prefQ = $dbc->prepare_statement("SELECT a.pref_key,
            CASE WHEN c.pref_value IS NULL THEN a.pref_default_value ELSE c.pref_value END
            AS current_value,
            a.pref_description
            FROM custAvailablePrefs AS a
            LEFT JOIN custPreferences AS c
            ON a.pref_key=c.pref_key AND c.card_no=?
            ORDER BY a.pref_key");
        $prefR = $dbc->exec_statement($prefQ,array($cardno));
        $ret .= '<table class="table">';
        $ret .= '<tr><th>Setting</th><th>Value</th></tr>'; 
        while ($prefW = $dbc->fetch_row($prefR)) {
            $ret .= sprintf('<tr><td>%s</td>
                <td><input type="text" class="form-control" name="pref_v[]" value="%s" /></td>
                </tr><input type="hidden" name="pref_k[]" value="%s" />',
                $prefW['pref_description'],
                $prefW['current_value'],
                $prefW['pref_key']
            );
        }
        $ret .=  '</table>';
        $ret .= '<p><button type="submit" name="savebtn" class="btn btn-default">Save</button></p>';
        $ret .= '</form>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

