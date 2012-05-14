<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

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

/* this module is intended for re-use. Just set 
 * $CORE_LOCAL["adminRequest"] to the module you want loaded
 * upon successful admin authentication. To be on the safe side,
 * that module should then unset (or clear to "") the session
 * variable
 */

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class requestInfo extends NoInputPage {

	function head_content(){
		global $CORE_PATH;
		?>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			$.ajax({
				url: '<?php echo $CORE_PATH; ?>ajax-callbacks/ajax-decision.php',
				type: 'get',
				data: 'input='+str,
				dataType: 'json',
				cache: false,
				success: function(data){
					if (data.endorse){
						$.ajax({
							url: '<?php echo $CORE_PATH; ?>ajax-callbacks/ajax-endorse.php',
							type: 'get',
							cache: false,
							success: function(){
								location = data.dest_page;
							}
						});
					}
					else {
						location = data.dest_page;
					}
				}
			});
			return false;
		}
		</script>
		<?php
	}

	function body_content(){
		global $CORE_LOCAL;
		?>
		<div class="baseHeight">
		<div class="colored centeredDisplay">
		<span class="larger">
		<?php echo $CORE_LOCAL->get("requestType") ?>
		</span>
		<form name="form" method="post" autocomplete="off" onsubmit="return submitWrapper();">
		<input type="text" id="reginput" name='input' tabindex="0" onblur="$('#input').focus()" />
		</form>
		<p />
		<?php echo $CORE_LOCAL->get("requestMsg") ?>
		<p />
		</div>
		</div>

		<?php
		$this->add_onload_command("\$('#reginput').focus();");
		$CORE_LOCAL->set("scan","noScan");
	} // END true_body() FUNCTION

}

new requestInfo();

?>
