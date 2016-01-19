<?php 
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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
    public $description = '[Member Preferences] manages a set of per-member preference settings.';

    protected $must_authenticate = true;
    protected $auth_classes = array('editmembers');

    public function get_view()
    {
        return '<div class="alert alert-danger">Error - no member specified</div>';
    }

    public function get_id_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $cardno = $this->id;
        $notice = new CustomerNotificationsModel($dbc);
        if (FormLib::get('savebtn') !== '') {
            $pkey = FormLib::get('pref_k', array());
            $pval = FormLib::get('pref_v', array());
            if (is_array($pkey) && is_array($pval) && count($pkey)==count($pval)){
                $availModel = new CustAvailablePrefsModel($dbc);
                $prefModel = new CustPreferencesModel($dbc);
                for($i=0;$i<count($pkey);$i++) {
                    $availModel->pref_key($pkey[$i]);
                    $availModel->load();

                    $prefModel->pref_key($pkey[$i]);
                    $prefModel->card_no($cardno);
                    $prefModel->custAvailablePrefID($availModel->custAvailablePrefID());
                    $prefModel->pref_value($pval[$i]);
                    $prefModel->save();

                    if ($pkey[$i] === 'email_receipt') {
                        $this->setupNotification($cardno, $pval[$i], $notice);
                    }
                }
                $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Saved Settings');\n");
            }
        }

        return true;
    }

    private function setupNotification($cardno, $val, $notice)
    {
        $ret = true;
        if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
            $notice->reset();
            $notice->cardNo($cardno);
            $notice->source('email_receipt');
            $notice->type('memlist');
            $exists = $notice->find();
            if (count($exists) > 0) {
                $notice = array_pop($exists);
            }
            $notice->message('&#x2709;');
            $ret = $notice->save();
        } else {
            $notice->reset();
            $notice->cardNo($cardno);
            $notice->source('email_receipt');
            $notice->type('memlist');
            foreach ($notice->find() as $obj) {
                $ret = ($obj->delete() && $ret) ? true : false;
            }
        }

        return $ret;
    }

    public function get_id_view()
    {
        if ($this->id == 0) {
            return '<div class="alert alert-danger">No member specified</div>';
        }
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $cardno = $this->id;
        $ret = sprintf('<h3>Account #%d</h3>',$cardno);
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="get">';
        $ret .= sprintf('<input type="hidden" value="%d" name="id" />',$cardno);
        $ret .= '<div id="alert-area"></div>';

        $prefQ = $dbc->prepare("SELECT a.pref_key,
            CASE WHEN c.pref_value IS NULL THEN a.pref_default_value ELSE c.pref_value END
            AS current_value,
            a.pref_description
            FROM custAvailablePrefs AS a
            LEFT JOIN custPreferences AS c
            ON a.pref_key=c.pref_key AND c.card_no=?
            ORDER BY a.pref_key");
        $prefR = $dbc->execute($prefQ,array($cardno));
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
        $ret .= '<p><button type="submit" name="savebtn" value="1" class="btn btn-default">Save</button></p>';
        $ret .= '</form>';

        return $ret;
    }

    public function helpContent()
    {
        return '<p>
            Member preferences are an arbitrary, custom list
            of per-member configuration options. Stores can
            add new preferences as needed to assist in local
            operations. Individual members\' preference
            settings are managed on this page.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $this->id = 0;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
        $this->id = 1;
        $phpunit->assertEquals(true, $this->get_id_handler());
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }
}

FannieDispatch::conditionalExec();

