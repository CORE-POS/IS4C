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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class adminlist extends NoInputPage {

	private $security;
	function preprocess(){
		global $CORE_LOCAL;

		$me = $CORE_LOCAL->get('CashierNo');	
		$this->security = 0;
		$db = Database::pDataConnect();
		$chk = $db->prepare_statement('SELECT frontendsecurity FROM employees WHERE emp_no=?');
		$res = $db->exec_statement($chk, array($me));
		if ($db->num_rows($res) > 0){
			$row = $db->fetch_row($res);
			$this->security = $row['frontendsecurity'];
		}

		if (isset($_REQUEST['selectlist'])){
            if (!FormLib::validateToken()) {
                return false;
            }
			if (empty($_REQUEST['selectlist'])){
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
			elseif ($_REQUEST['selectlist'] == 'SUSPEND'){
				Database::getsubtotals();
				if ($CORE_LOCAL->get("LastID") == 0) {
					$CORE_LOCAL->set("boxMsg",_("no transaction in progress"));
					$this->change_page($this->page_url."gui-modules/boxMsg2.php");
					return False;
				}
				else {
					// ajax call to end transaction
					// and print receipt
					$ref = SuspendLib::suspendorder();
					$this->add_onload_command("\$.ajax({
						type:'post',
						url:'{$this->page_url}ajax-callbacks/ajax-end.php',
						cache: false,
						data: 'receiptType=suspended&ref={$ref}',
						dataType: 'json',
						success: function(data){
							\$.ajax({
							type:'post',
							url:'{$this->page_url}ajax-callbacks/ajax-transaction-sync.php',
							cache: false,
							success: function(data){
								location='{$this->page_url}gui-modules/pos2.php';
							},
							error: function(e1){
								location='{$this->page_url}gui-modules/pos2.php';
							}
							});
						},
						error: function(e1){
							location='{$this->page_url}gui-modules/pos2.php';
						}
						});");
					return True;
				}
			}
			else if ($_REQUEST['selectlist'] == 'RESUME'){
				Database::getsubtotals();
				if ($CORE_LOCAL->get("LastID") != 0) {
					$CORE_LOCAL->set("boxMsg",_("transaction in progress"));
					$this->change_page($this->page_url."gui-modules/boxMsg2.php");
				}
				elseif (SuspendLib::checksuspended() == 0) {
					$CORE_LOCAL->set("boxMsg",_("no suspended transaction"));
					$CORE_LOCAL->set("strRemembered","");
					$this->change_page($this->page_url."gui-modules/boxMsg2.php");
				}
				else {
					$this->change_page($this->page_url."gui-modules/suspendedlist.php");
				}
				return False;
			}
			else if ($_REQUEST['selectlist'] == 'TR'){
				TenderReport::printReport();
				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
			}
			else if ($_REQUEST['selectlist'] == 'OTR' && $this->security >= 30){
				$this->change_page($this->page_url.'gui-modules/requestInfo.php?class=AnyTenderReportRequest');
				return False;
			}
		}
		return True;
	}

	function head_content(){
		?>
		<script type="text/javascript" src="<?php echo $this->page_url; ?>js/ajax-parser.js"></script>
	        <script type="text/javascript" src="../js/selectSubmit.js"></script>
		<?php
	} // END head() FUNCTION

	function body_content() {
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="centeredDisplay colored">
			<span class="larger"><?php echo _("administrative tasks"); ?></span>
			<br />
		<form id="selectform" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
		<select name="selectlist" id="selectlist" onblur="$('#selectlist').focus();">
		<option value=''><?php echo _("Select a Task"); ?>
		<option value='SUSPEND'>1. <?php echo _("Suspend Transaction"); ?>
		<option value='RESUME'>2. <?php echo _("Resume Transaction"); ?>
        <?php if ($CORE_LOCAL->get('SecurityTR') != 30 || $this->security >= 30) { ?>
            <option value='TR'>3. <?php echo _("Tender Report"); ?>
		<?php } ?>
		<?php if ($this->security >= 30){ ?>
			<option value='OTR'>4. <?php echo _("Any Tender Report"); ?>
		<?php } ?>
		</select>
        <?php echo FormLib::tokenField(); ?>
		</form>
		<p>
		<span class="smaller"><?php
		echo _("use arrow keys to navigate");
		echo "<br />";
		echo _("enter to select");
		echo "<br />";
		echo _("clear to cancel");
		?></span>
		</p>
		</div>
		</div>
		<?php
		$this->add_onload_command("\$('#selectlist').focus();");
	        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
	} // END body_content() FUNCTION

}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new adminlist();
?>
