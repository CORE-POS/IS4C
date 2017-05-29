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
    *                       in body_content() using tempMessage.

*/

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class memlist extends NoInputCorePage 
{
    private $tempMessage = '';

    private $results = array();
    private $submitted = false;

    private $noticeCache = array();
    private $noticeStatement = null;

    private function getInput()
    {
        $entered = false;
        if ($this->form->tryGet('idSearch', false) !== false) {
            $entered = $this->form->idSearch;
        } elseif ($this->form->tryGet('search', false) !== false) {
            $entered = $this->form->search;
        }
        if ($entered === false) {
            return false;
        }

        if (substr($entered, -2) == "ID") {
            $entered = substr($entered, 0, strlen($entered) - 2);
        }

        return strtoupper(str_replace("'", '', $entered));
    }

    private function runSearch($entered)
    {
        $lookups = AutoLoader::ListModules('COREPOS\\pos\\lib\\MemberLookup', True);
        $results = array();
        foreach ($lookups as $class) {
            if (!class_exists($class)) continue;
            $obj = new $class();

            if (is_numeric($entered) && !$obj->handle_numbers()) {
                continue;
            } elseif (!is_numeric($entered) && !$obj->handle_text()) {
                continue;
            } elseif (is_numeric($entered)) {
                $chk = $obj->lookup_by_number($entered);
                $results = $this->checkResults($chk, $results);
            } elseif (!is_numeric($entered)) {
                $chk = $obj->lookup_by_text($entered);
                $results = $this->checkResults($chk, $results);
            }
        }

        return $results;
    }

    private function checkResults($chk, $results)
    {
        if ($chk['url'] !== false) {
            $this->change_page($chk['url']);
            throw new Exception('page change requested');
        }
        foreach ($chk['results'] as $key=>$val) {
            $results[$key] = $val;
        }

        return $results;
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
                $this->results = $this->runSearch($entered);
            } catch (Exception $ex) {
                return false;
            }

            if (count($this->results) == 1 && ($this->session->get('verifyName') == 0 || $entered == $this->session->get('defaultNonMem'))) {
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
            if ($memberID == $this->session->get('defaultNonMem')) {
                $personNum = 1;
            }
            COREPOS\pos\lib\MemberLib::setMember($memberID, $personNum);

            if ($this->session->get('store') == "WEFC_Toronto") {
                $errorMsg = $this->wefcCardCheck($memberID);
                if ($errorMsg !== true) {
                    $this->tempMessage = $errorMsg;

                    return true;
                }
            }

            // don't bother with unpaid balance check if there is no balance
            $url = $this->page_url."gui-modules/pos2.php";
            if ($memberID != $this->session->get("defaultNonMem") && $this->session->get('balance') > 0) {
                $unpaid = COREPOS\pos\lib\MemberLib::checkUnpaidAR($memberID);
                if ($unpaid) {
                    $url = $this->page_url."gui-modules/UnpaidAR.php";
                }
            }
            $this->change_page($url);

            return false;
        }

        return true;

    } // END preprocess() FUNCTION

    /**
      Check for a registered callback that runs when
      a given member number is applied
    */
    private function getCallbackAction($cardNo)
    {
        $dbc = Database::pDataConnect();
        if ($this->session->get('NoCompat') != 1 && !$dbc->tableExists('CustomerNotifications')) {
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
        $res = $dbc->getRow($prep, array($cardNo));
        if ($res === false || !class_exists($res['modifierModule']) || !is_subclass_of($res['modifierModule'], 'COREPOS\\pos\\lib\\TotalActions\\MemTotalAction')) {
            return false;
        }

        $class = $res['modifierModule'];
        $obj = new $class();
        $obj->setMember($cardNo);
        $obj->setMessage($res['message']);

        return $obj;
    }

    // WEFC_Toronto: If a Member Card # was entered when the choice from the list was made,
    // add the memberCards record.
    private function wefcCardCheck($cardNo)
    {
        $dba = Database::pDataConnect();
        if ($this->form->tryGet('memberCard') !== '') {
            $memberCard = $this->form->memberCard;
            if (!is_numeric($memberCard) || strlen($memberCard) > 5 || $memberCard == 0) {
                return "Bad Member Card# format >{$memberCard}<";
            }
            $upc = sprintf("00401229%05d", $memberCard);
            // Check that it isn't already there, perhaps for someone else.
            $memQ = "SELECT card_no FROM memberCards where card_no = {$cardNo}";
            $mResult = $dba->query($memQ);
            $mNumRows = $dba->num_rows($mResult);
            if ($mNumRows > 0) {
                return "{$cardNo} is already associated with another Member Card";
            }
            $memQ = "INSERT INTO memberCards (card_no, upc) VALUES ({$cardNo}, '$upc')";
            $mResult = $dba->query($memQ);
            if ( !$mResult ) {
                return "Linking membership to Member Card failed.";
            }
        }

        return true;
    }

    function head_content()
    {
        if (count($this->results) > 0) {
            $this->add_onload_command("selectSubmit('#reginput', '#selectform', '#filter-div')\n");
        } else {
            $this->default_parsewrapper_js('reginput','selectform');
        }
        $this->add_onload_command("\$('#reginput').focus();\n");
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <?php
    } // END head() FUNCTION

    private function searchDialog($message)
    {
        ob_start();
        echo "
        <div class=\"colored centeredDisplay rounded\">
            <span class=\"larger\">";
        if ($message !== '') {
            echo $message . '<br />';
        } elseif (!$this->submitted) {
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
                " . _('Cancel [enter]') . "
            </button>
        </div>";

        return ob_get_clean();
    }

    private function noticeStatement($dbc)
    {
        if ($this->noticeStatement !== null) {
            return $this->noticeStatement;
        }

        $this->noticeStatement = false;
        if ($this->session->get('NoCompat') == 1 || $dbc->tableExists('CustomerNotifications')) {
            $this->noticeStatement = $dbc->prepare('
                SELECT message
                FROM CustomerNotifications
                WHERE cardNo=?
                    AND type=\'memlist\'
                ORDER BY message');
        }

        return $this->noticeStatement;
    }

    private function getNotification($cardNo)
    {
        if (isset($this->noticeCache[$cardNo])) {
            return $this->noticeCache[$cardNo];
        }

        $dbc = Database::pDataConnect();
        $noticeP = $this->noticeStatement($dbc);
        if ($noticeP === false) {
            return '';
        }
        $noticeR = $dbc->execute($noticeP, array($cardNo)); 
        $notice = '';
        while ($row = $dbc->fetchRow($noticeR)) {
            $notice .= ' ' . $row['message'];
        }
        $this->noticeCache[$cardNo] = $notice;

        return $notice;
    }

    private function listDisplay()
    {
        ob_start();
        echo "<div class=\"listbox\">"
            ."<select name=\"search\" size=\"15\" "
            .' style="min-height: 200px; min-width: 220px; max-width: 390px;" '
            ."onblur=\"\$('#reginput').focus();\" ondblclick=\"document.forms['selectform'].submit();\" 
            id=\"reginput\">";

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
            list($id, $pn) = explode('::', $optval, 2);
            $label .= $this->getNotification($id);
            echo '>'.$label.'</option>';
        }
        echo "</select>"
            . '<div id="filter-div"></div>'
            . "</div><!-- /.listbox -->";
        if ($this->session->get('touchscreen')) {
            echo '<div class="listbox listboxText">'
                . DisplayLib::touchScreenScrollButtons()
                . '</div>';
        }
        echo "<div class=\"listboxText coloredText centerOffset\">"
            . _("use arrow keys to navigate")
            . '<p><button type="submit" class="pos-button wide-button coloredArea">'
            . _('OK') . ' <span class="smaller">' . _('[enter]') . '</span>
                </button></p>'
            . '<p><button type="submit" class="pos-button wide-button errorColoredArea"
                onclick="$(\'#search\').append($(\'<option>\').val(\'\'));$(\'#search\').val(\'\');">'
            . _('Cancel') . ' <span class="smaller">' . _('[clear]') . '</span>
                </button></p>'
            ."</div><!-- /.listboxText coloredText .centerOffset -->"
            ."<div class=\"clear\"></div>";

        return ob_get_clean();
    }

    function body_content()
    {
        echo "<div class=\"baseHeight\">"
            ."<form id=\"selectform\" method=\"post\" action=\""
            .filter_input(INPUT_SERVER, 'PHP_SELF') . "\">";

        /* for no results or a problem found in preprocess, just throw up a re-do
         * otherwise, put results in a select box
         */
        if ($this->tempMessage !== '' || count($this->results) < 1) {
            echo $this->searchDialog($this->tempMessage);
        } else {
            echo $this->listDisplay();
        }
        echo "</form></div>";
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        ob_start();
        $this->head_content();
        $this->body_content();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
        $phpunit->assertNotEquals(0, strlen($this->listDisplay()));
        $nt1 = $this->getNotification(1);
        $nt2 = $this->getNotification(1);
        $phpunit->assertEquals($nt1, $nt2);
        $phpunit->assertInternalType('array', $this->runSearch(1));
        $phpunit->assertInternalType('array', $this->runSearch('joe'));

        $this->getCallbackAction(1);
    }

// /class memlist
}

AutoLoader::dispatch();

