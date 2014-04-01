<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardTransListPage extends NoInputPage 
{

	function preprocess(){
		global $CORE_LOCAL;
		// check for posts before drawing anything, so we can redirect
        if (isset($_REQUEST['selectlist'])) {
            $id = $_REQUEST['selectlist'];

            if ($id == 'CL' || $id == '') {
                $this->change_page($this->page_url."gui-modules/pos2.php");

                return false;
            } else {
                $this->change_page('PaycardTransLookupPage.php?id=' . $id . '&mode=lookup');

                return false;
            }
		} // post?
		return True;
	}

	function body_content(){
		global $CORE_LOCAL;
        $local = array();
        $other = array();
        $db = Database::tDataConnect();
        $localQ = 'SELECT amount, PAN, refNum FROM efsnetRequest GROUP BY amount, PAN, refNum';
        $localR = $db->query($localQ);
        while($w = $db->fetch_row($localR)) {
            $local['_l' . $w['refNum']] = '(CURRENT)' . $w['PAN'] . ' : ' . sprintf('%.2f', $w['amount']);
        }
        if ($CORE_LOCAL->get('standalone') == 0) {

            $emp = $CORE_LOCAL->get('CashierNo');
            $db = Database::pDataConnect();
            $empQ = 'SELECT frontendsecurity FROM employees WHERE emp_no=' . ((int)$emp);
            $empR = $db->query($empQ);
            $supervisor = false;
            if ($db->num_rows($empR) > 0) {
                $empW = $db->fetch_row($empR);
                if ($empW['frontendsecurity'] >= 30) {
                    $supervisor = true;
                }
            }

            $db = Database::mDataConnect();
            $otherQ = 'SELECT MIN(datetime) as dt, amount, PAN, refNum,
                        cashierNo, laneNo, transNo
                        FROM efsnetRequest 
                        WHERE date=' . date('Ymd');
            if (!$supervisor) {
                $otherQ .= ' AND laneNo=' . ((int)$CORE_LOCAL->get('laneno')) . '
                           AND cashierNo=' . ((int)$CORE_LOCAL->get('CashierNo'));
            }
            $otherQ .= ' GROUP BY amount, PAN, refNum
                        ORDER BY datetime DESC';
            $otherR = $db->query($otherQ);
            while($w = $db->fetch_row($otherR)) {
                $other[$w['refNum']] = $w['dt'] . ' : ' 
                                        . $w['cashierNo'] . '-' . $w['laneNo'] . '-' . $w['transNo'] . ' : ' 
                                        . sprintf('%.2f', $w['amount']);
            }
        }
		?>
		<div class="baseHeight">
        <div class="listbox">
        <form name="selectform" method="post" id="selectform" 
            action="<?php echo $_SERVER['PHP_SELF']; ?>" >
        <select name="selectlist" size="10" id="selectlist"
            onblur="$('#selectlist').focus()" >
		<?php
        $selected = 'selected';
        foreach($local as $id => $label) {
            printf('<option %s value="%s">%s</option>',
                    $selected, $id, $label);
            $selected = '';
        }
        foreach($other as $id => $label) {
            printf('<option %s value="%s">%s</option>',
                    $selected, $id, $label);
            $selected = '';
        }
        if (count($local) == 0 && count($other) == 0) {
            echo '<option value="" selected>No transactions found</option>';
        }
		?>
        </select>
        </form>
        </div>
        <div class="listboxText coloredText centerOffset">
        <?php echo _("use arrow keys to navigate"); ?><br />
        <?php echo _("enter to reprint receipt"); ?><br />
        <?php echo _("clear to cancel"); ?>
        </div>
        <div class="clear"></div>
		</div>
		<?php
        $this->add_onload_command("\$('#selectlist').keypress(processkeypress);\n");
        $this->add_onload_command("\$('#selectlist').focus();\n");
	}

    function head_content()
    {
        ?>
        <script type="text/javascript" >
        var prevKey = -1;
        var prevPrevKey = -1;
        function processkeypress(e) {
            var jsKey;
            if (e.keyCode) // IE
                jsKey = e.keyCode;
            else if(e.which) // Netscape/Firefox/Opera
                jsKey = e.which;
            if (jsKey==13) {
                if ( (prevPrevKey == 99 || prevPrevKey == 67) &&
                (prevKey == 108 || prevKey == 76) ){ //CL<enter>
                    $('#selectlist option:selected').val('');
                }
                $('#selectform').submit();
            }
            prevPrevKey = prevKey;
            prevKey = jsKey;
        }
        </script> 
        <?php
    }
}

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	new PaycardTransListPage();
}

