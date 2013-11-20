<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

/* wraps around an undone transaction to limit editing options
   CL cancels the attempt (wraps to input "CN")
   {Enter} finishes the transaction (wraps to input "0CA")
*/
class undo_confirm extends BasicPage {
	var $box_color;
	var $msg;

	function body_content(){
		global $CORE_LOCAL;
		echo $this->input_header();
		?>
		<div class="baseHeight">
		<?php 
			if (empty($this->msg))
				echo DisplayLib::lastpage(); 
			else {
				echo $this->msg;	
			}
		?>
		</div>
		<?php
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
		$this->add_onload_command("\$('#reginput').keyup(function(ev){
					switch(ev.keyCode){
					case 33:
						\$('#reginput').val('U11');
						\$('#formlocal').submit();
						break;
					case 38:
						\$('#reginput').val('U');
						\$('#formlocal').submit();
						break;
					case 34:
						\$('#reginput').val('D11');
						\$('#formlocal').submit();
						break;
					case 40:
						\$('#reginput').val('D');
						\$('#formlocal').submit();
						break;
					}
				});\n");
		$this->add_onload_command("undoInstructions();");
	}

	function head_content(){
		?>
		<script type="text/javascript">
		function undoInstructions(){
			var str = '<span style="padding:3px;background:#fff;"><b>[Enter] to accept, [Clear] to reject</b></span> ';
			var cur = $('.notices').html();
			$('.notices').html(str+cur);
		}
		</script>
		<?php
	}

	function preprocess(){
		global $CORE_LOCAL;
		$this->msg = "";
		if (isset($_REQUEST['reginput'])){
			switch(strtoupper($_REQUEST['reginput'])){
			case 'CL':
                // cancel the transaction instead
				$CORE_LOCAL->set("msgrepeat",1);
				$CORE_LOCAL->set("strRemembered","CN");

                /**
                  Unify emp_no & trans_no records in the
                  database. Logging records from authentication
                  may have different values. This step normalizes
                  the transaction. In this case I'm restoring
                  the logged in cashier's info immediately
                  and assigning the entire transaction to that
                  cashier. This is simpler than the case below
                  and since it's canceled it doesn't matter if
                  the tender records are assigned to the original
                  cashier or the current cashier.
                */
                Database::loadglobalvalues();
                $db = Database::tDataConnect();
                $emp_no = $CORE_LOCAL->get('CashierNo');
                $trans_no = $CORE_LOCAL->get('transno');
                $db->query('UPDATE localtemptrans SET
                            emp_no='.((int)$emp_no).',
                            trans_no='.((int)$trans_no).'
                            WHERE
                            emp_no<>'.((int)$emp_no).' OR
                            trans_no<>'.((int)$trans_no));

				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
				break;
			case '':
                // use zero cash to finish transaction
				$CORE_LOCAL->set("msgrepeat",1);
				$CORE_LOCAL->set("strRemembered","0CA");

                /**
                  Unify emp_no & trans_no records in the
                  database. Logging records from authentication
                  may have different values. This step
                  normalizes the transaction. When ajax-end.php
                  runs to close the transaction, the actual
                  logged in cashier's values will be restored
                  via Database::loadglobalvalues().
                */
                $db = Database::tDataConnect();
                $emp_no = $CORE_LOCAL->get('CashierNo');
                $trans_no = $CORE_LOCAL->get('transno');
                $db->query('UPDATE localtemptrans SET
                            emp_no='.((int)$emp_no).',
                            trans_no='.((int)$trans_no).'
                            WHERE
                            emp_no<>'.((int)$emp_no).' OR
                            trans_no<>'.((int)$trans_no));

				$this->change_page($this->page_url."gui-modules/pos2.php");
				return False;
				break;
			case 'U':
			case 'U11':
			case 'D':
			case 'D11':
				// just use the parser module here
				// for simplicity; all its really
				// doing is updating a couple session vars
				$si = new ScrollItems();
				$json = $si->parse($_REQUEST['reginput']);
				$this->msg = $json['output'];
				break;
			default:
				break;
			}
		}
		return True;
	}
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
	new undo_confirm();
