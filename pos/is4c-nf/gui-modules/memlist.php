<?php
/*******************************************************************************

   Copyright 2010 Whole Foods Co-op

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

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 24Oct2013 Eric Lee Defeated:
    *                    + A WEFC_Toronto-only textbox for collecting Member Card#
    *  5Oct2012 Eric Lee Added:
    *                    + A WEFC_Toronto-only chunk for collecting Member Card#
    *                    + A general facility for displaying an error encountered in preprocess()
    *                       in body_content() using temp_message.

*/

use COREPOS\pos\lib\FormLib;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class memlist extends NoInputCorePage 
{

    private $temp_result;
    private $temp_num_rows;
    private $entered;
    private $temp_message = '';

    private $results = array();
    private $submitted = false;

    private function getInput()
    {
        $entered = false;
        if (FormLib::get('idSearch', false) !== false) {
            $entered = FormLib::get('idSearch');
        } elseif (FormLib::get('search', false) !== false) {
            $entered = FormLib::get('search');
        } else {
            return false;
        }

        if (substr($entered, -2) == "ID") {
            $entered = substr($entered, 0, strlen($entered) - 2);
        }

        return str_replace("'", '', $entered);
    }

    private function runSearch($entered)
    {
        $lookups = AutoLoader::ListModules('MemberLookup', True);
        foreach ($lookups as $class) {
            if (!class_exists($class)) continue;
            $obj = new $class();

            if (is_numeric($entered) && !$obj->handle_numbers()) {
                continue;
            } else if (!is_numeric($entered) && !$obj->handle_text()) {
                continue;
            } else if (is_numeric($entered)) {
                $chk = $obj->lookup_by_number($entered);
                if ($chk['url'] !== false) {
                    $this->change_page($chk['url']);
                    throw new Exception('page change requested');
                }
                foreach($chk['results'] as $key=>$val) {
                    $this->results[$key] = $val;
                }
            } elseif (!is_numeric($entered)) {
                $chk = $obj->lookup_by_text($entered);
                if ($chk['url'] !== false) {
                    $this->change_page($chk['url']);
                    throw new Exception('page change requested');
                }
                foreach ($chk['results'] as $key=>$val) {
                    $this->results[$key] = $val;
                }
            }
        }
    }

    function preprocess()
    {
        $entered = $this->getInput();
        if ($entered === false) {
            return true;
        } elseif (!$entered || strlen($entered) < 1 || $entered == "CL") {
            $this->change_page($this->page_url."gui-modules/pos2.php");

            return false;
        }

        $personNum = false;
        $memberID = false;
        $this->submitted = true;
        if (strstr($entered, "::") !== false) {
            // User selected a :: delimited item from the list interface
            list($memberID, $personNum) = explode("::", $entered, 2);
        } else {
            // search for the member
            try {
                $this->runSearch($entered);
            } catch (Exception $ex) {
                return false;
            }

            if (count($this->results) == 1 && (CoreLocal::get('verifyName') == 0 || $entered == CoreLocal::get('defaultNonMem'))) {
                $members = array_keys($this->results);
                $match = $members[0];
                list($memberID, $personNum) = explode('::', $match, 2);
            }
        }


        // we have exactly one row and 
        // don't need to confirm any further
        if ($memberID !== false && $personNum !== false) {
            $callback = $this->getCallbackAction($memberID);
            if ($callback != false) {
                $callback->apply();
            }
            if ($memberID == CoreLocal::get('defaultNonMem')) {
                $personNum = 1;
            }
            PrehLib::setMember($memberID, $personNum);

            if (CoreLocal::get('store') == "WEFC_Toronto") {
                $error_msg = $this->wefcCardCheck($memberID);
                if ($error_msg !== true) {
                    $this->temp_message = $error_msg;

                    return true;
                }
            }

            // don't bother with unpaid balance check if there is no balance
            if ($memberID != CoreLocal::get("defaultNonMem") && CoreLocal::get('balance') > 0) {
                $unpaid = PrehLib::check_unpaid_ar($memberID);
                if ($unpaid) {
                    $this->change_page($this->page_url."gui-modules/UnpaidAR.php");
                } else {
                    $this->change_page($this->page_url."gui-modules/pos2.php");
                }
            } else {
                $this->change_page($this->page_url."gui-modules/pos2.php");
            }

            return false;
        }

        return true;

    } // END preprocess() FUNCTION

    /**
      Check for a registered callback that runs when
      a given member number is applied
    */
    private function getCallbackAction($card_no)
    {
        $dbc = Database::pDataConnect();
        if (!$dbc->tableExists('CustomerNotifications')) {
            echo 'no notifications';
            return false;
        }
        $prep = $dbc->prepare("
            SELECT message,
                modifierModule
            FROM CustomerNotifications
            WHERE type='callback'
                AND cardNo=?"
        );
        $res = $dbc->getRow($prep, array($card_no));
        if ($res === false || !class_exists($res['modifierModule']) || !is_subclass_of($res['modifierModule'], 'MemTotalAction')) {
            return false;
        }

        $class = $res['modifierModule'];
        $obj = new $class();
        $obj->setMember($card_no);
        $obj->setMessage($res['message']);

        return $obj;
    }

    // WEFC_Toronto: If a Member Card # was entered when the choice from the list was made,
    // add the memberCards record.
    private function wefcCardCheck($card_no)
    {
        $db_a = Database::pDataConnect();
        if (FormLib::get('memberCard') !== '') {
            $memberCard = FormLib::get('memberCard');
            if (!is_numeric($memberCard) || strlen($memberCard) > 5 || $memberCard == 0) {
                return "Bad Member Card# format >{$memberCard}<";
            } else {
                $upc = sprintf("00401229%05d", $memberCard);
                // Check that it isn't already there, perhaps for someone else.
                $mQ = "SELECT card_no FROM memberCards where card_no = {$card_no}";
                $mResult = $db_a->query($mQ);
                $mNumRows = $db_a->num_rows($mResult);
                if ($mNumRows > 0) {
                    return "{$card_no} is already associated with another Member Card";
                } else {
                    $mQ = "INSERT INTO memberCards (card_no, upc) VALUES ({$card_no}, '$upc')";
                    $mResult = $db_a->query($mQ);
                    if ( !$mResult ) {
                        return "Linking membership to Member Card failed.";
                    }
                }
            }
        }

        return true;
    }

    function head_content()
    {
        if (count($this->results) > 0) {
            $this->add_onload_command("selectSubmit('#search', '#selectform', '#filter-div')\n");
            $this->add_onload_command("\$('#search').focus();\n");
        } else {
            $this->default_parsewrapper_js('reginput','selectform');
            $this->add_onload_command("\$('#reginput').focus();\n");
        }
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <?php
    } // END head() FUNCTION

    function body_content()
    {
        $message = $this->temp_message;

        echo "<div class=\"baseHeight\">"
            ."<form id=\"selectform\" method=\"post\" action=\""
            .filter_input(INPUT_SERVER, 'PHP_SELF') . "\">";

        // First check for a problem found in preprocess.
        if ($message != "") {
            echo "
            <div class=\"colored centeredDisplay rounded\">
                <span class=\"larger\">
                    {$message} <br />" .
                    _("enter member number or name") . "
                </span>
                <br />
                <input type=\"text\" name=\"search\" size=\"15\"
                       onblur=\"\$('#reginput').focus();\" id=\"reginput\" />
                <br />press [enter] to cancel
            </div>";
        } else if (count($this->results) < 1) {
            /* for no results, just throw up a re-do
             * otherwise, put results in a select box
             */
            echo "
            <div class=\"colored centeredDisplay rounded\">
                <span class=\"larger\">";
            if (!$this->submitted) {
                echo _("member search")."<br />"._("enter member number or name");
            } else {
                echo _("no match found")."<br />"._("next search or member number");
            }
            echo "</span>
                <p>
                <input type=\"text\" name=\"search\" size=\"15\"
                       onblur=\"\$('#reginput').focus();\" id=\"reginput\" />
                </p>
                <button class=\"pos-button\" type=\"button\"
                    onclick=\"\$('#reginput').val('');\$('#selectform').submit();\">
                    Cancel [enter]
                </button>
            </div>";
        } else {
            echo "<div class=\"listbox\">"
                ."<select name=\"search\" size=\"15\" "
                .' style="min-height: 200px; min-width: 220px; max-width: 390px;" '
                ."onblur=\"\$('#search').focus();\" ondblclick=\"document.forms['selectform'].submit();\" id=\"search\">";

            $noticeP = false;
            $notice_cache = array();
            $dbc = Database::pDataConnect();
            if ($dbc->tableExists('CustomerNotifications')) {
                $noticeP = $dbc->prepare('
                    SELECT message
                    FROM CustomerNotifications
                    WHERE cardNo=?
                        AND type=\'memlist\'
                    ORDER BY message');
            }
            $selectFlag = 0;
            foreach ($this->results as $optval => $label) {
                echo '<option value="'.$optval.'"';
                if ($selectFlag == 0) {
                    echo ' selected';
                    $selectFlag = 1;
                }
                /**
                  If available, look up notifications designated
                  for this screen. Cache results in case the
                  same account appears more than once in the list.
                */
                if ($noticeP) {
                    list($id, $pn) = explode('::', $optval, 2);
                    if (isset($notice_cache[$id])) {
                        $label .= $notice_cache[$id];
                    } else {
                        $noticeR = $dbc->execute($noticeP, array($id)); 
                        $notice = '';
                        while ($row = $dbc->fetchRow($noticeR)) {
                            $notice .= ' ' . $row['message'];
                        }
                        $notice_cache[$id] = $notice;
                        $label .= $notice;
                    }
                }
                echo '>'.$label.'</option>';
            }
            echo "</select>"
                . '<div id="filter-div"></div>'
                . "</div><!-- /.listbox -->";
            if (CoreLocal::get('touchscreen')) {
                echo '<div class="listbox listboxText">'
                    . DisplayLib::touchScreenScrollButtons()
                    . '</div>';
            }
            echo "<div class=\"listboxText coloredText centerOffset\">"
                . _("use arrow keys to navigate")
                . '<p><button type="submit" class="pos-button wide-button coloredArea">
                    OK <span class="smaller">[enter]</span>
                    </button></p>'
                . '<p><button type="submit" class="pos-button wide-button errorColoredArea"
                    onclick="$(\'#search\').append($(\'<option>\').val(\'\'));$(\'#search\').val(\'\');">
                    Cancel <span class="smaller">[clear]</span>
                    </button></p>'
                ."</div><!-- /.listboxText coloredText .centerOffset -->"
                ."<div class=\"clear\"></div>";
        }
        echo "</form></div>";
    } // END body_content() FUNCTION

// /class memlist
}

AutoLoader::dispatch();

