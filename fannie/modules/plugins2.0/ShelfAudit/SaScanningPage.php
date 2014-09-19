<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op
    Based on example code from Wedge Community Co-op

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

include(dirname(__FILE__).'/../../../config.php');
include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');

/**
  @class SaScanningPage
*/
class SaScanningPage extends FanniePage {

    protected $window_dressing = False;

    public $page_set = 'Plugin :: Shelf Audit';
    public $description = '[Alt. Scanning] is an older interface for entering quantities
    on hand';

    private $status='';
    private $section=0;

    function preprocess(){
        global $FANNIE_PLUGIN_SETTINGS;
        $this->status = 'waiting - no input';

        /**
          Store session in browser section.
        */
        if (ini_get('session.auto_start')==0 && !headers_sent() && php_sapi_name() != 'cli') {
            @session_start();
        }
        if (!isset($_SESSION['SaPluginSection']))
            $_SESSION['SaPluginSection'] = 0;
        $this->section = $_SESSION['SaPluginSection'];

        $dbc = FannieDB::get($FANNIE_PLUGIN_SETTINGS['ShelfAuditDB']);
        if (!is_object($dbc) || $dbc->connections[$FANNIE_PLUGIN_SETTINGS['ShelfAuditDB']] === False){
            $this->status = 'bad - cannot connect';
            return True;
        }

        if (FormLib::get_form_value('sflag') == 1){
            $query = $dbc->prepare_statement('SELECT MAX(section) AS new_section FROM sa_inventory');
            $result = $dbc->exec_statement($query);
            $section = 0;
            if ($dbc->num_rows($result) > 0)
                $section = array_pop($dbc->fetch_row($result));

            $this->section = $section + 1;
            $_SESSION['SaPluginSection'] = $section + 1;
            $this->status = 'good - section changed';
        } 
        else if (FormLib::get_form_value('minput') !== ''){
            $upc = FormLib::get_form_value('minput');
            if (FormLib::get_form_value('isbnflag')=='1'){
                $upc=BarcodeLib::padUPC(substr($_GET['minput'],0,12));
            } else {
                $upc=BarcodeLib::padUPC(substr($_GET['minput'],0,11));
            }
                
            /* Short tag rules */
            if (strcmp('0000000',substr($upc,0,7))==0) {
                switch ($upc[12]) {
                case '0':
                    $upc='00'.substr($upc,6,3).'00000'.substr($upc,10,3);
                    break;
                case '1':
                    $upc='00'.substr($upc,6,3).'10000'.substr($upc,10,3);
                    break;
                case '2':
                    $upc='00'.substr($upc,6,3).'20000'.substr($upc,10,3);
                    break;
                case '3':
                    $upc='00'.substr($upc,6,4).'00000'.substr($upc,10,2);
                    break;
                case '4':
                    $upc='00'.substr($upc,6,5).'00000'.substr($upc,11,1);
                    break;
                default:
                    $upc='00'.substr($upc,6,6).'0000'.substr($upc,12,1);
                    break;
                }
            }

            /*
             * Strip the z from qinput. Quick hack version
             */
            $qty = FormLib::get_form_value('qinput');
            $qty = rtrim($qty,'z');
            $args = array($upc);
            $stmt = $dbc->prepare_statement('INSERT INTO sa_inventory 
                    (id,datetime,upc,clear,quantity,section)
                    VALUES (NULL,'.$dbc->now().',?,0,?,?)');
                    
            if ($qty != '' && ctype_digit($qty)){
                $args[] = $qty;
                $args[] = $this->section;
            } else if ($qty != '') {
                $split=strpos($qty,'s');
                $quantity=substr($qty,0,$split);
                $section=substr($qty,$split+1);
                $args[] = $quantity;
                $args[] = $section;
            } else {
                $args[] = 1;
                $args[] = $this->section;
            }

            $result = $dbc->exec_statement($stmt, $args);
            if ($result) { $this->status = 'good - scan entered:'.$upc.'';  
            }   else { $this->status = 'bad - strange scan:'.$query; }
        }

        return True;
    }

    function body_content(){
        ob_start();
        ?>
<html>
    <body onload="readinput();">
        <center>
            <form name="mForm" id="mid" action="SaScanningPage.php" method="get">
                <input name="minput" type="text" value=""/>
                <input name="isbnflag" type="hidden" value=""/>
                <input name="qinput" type="text" value="1"/>
                <input type="submit" value="enter"/>
            </form>
            <form name="sForm" id="sid" action="SaScanningPage.php" method="get">
                <input name="sflag" type="hidden" value="1"/>
                <input type="submit" value="new section"/>
                <br />
                <i>on section (<?php echo $this->section; ?>)</i>
            </form>
            <div>scan or type upc</div>
            <div>status: <?php echo($this->status); ?></div>
            <!-- other group not in example. ignoring for now. (Andy 29Mar2013)
            <div><strong>Using Group 1</strong></div>
            <div style="font-size: x-small; padding-top: .5em;"><a href="../hbc2">Switch to Group 2</a></div>
            -->
        </center>
    </body>
</html>
        <?php
        return ob_get_clean();
    }

    function javascript_content(){
        ob_start();
        ?>
function waitforz() {
    if (document.forms[0]) {
        var qinputvalue = document.forms[0].qinput.value;

        if (qinputvalue.charAt(qinputvalue.length - 1) == 'z') {
            document.forms[0].submit();
        }   else {
            t=setTimeout("waitforz()",1000);
        }
    }
}
        
function readinput() {
    if (document.forms[0]) {
        var inputvalue = document.forms[0].minput.value;
                
        if (inputvalue.length == 12) {
            document.forms[0].qinput.value="";
            document.forms[0].qinput.focus();
            waitforz();
        } else if (inputvalue.length == 13) {
            document.forms[0].isbnflag.value="1";
            document.forms[0].qinput.value="";
            document.forms[0].qinput.focus();
            waitforz();
        } else {
            document.forms[0].minput.focus();
            t=setTimeout("readinput()",1000);
        }
    } else {
    }
}
        <?php
        return ob_get_clean();
    }
}

FannieDispatch::conditionalExec(false);

