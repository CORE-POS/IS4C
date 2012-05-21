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

ini_set('display_errors','1');

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class boxMsg2 extends BasicPage {

	function head_content(){
		?>
		<script type="text/javascript">
		function submitWrapper(){
			var str = $('#reginput').val();
			$.ajax({
				url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-decision.php',
				type: 'get',
				data: 'input='+str,
				dataType: 'json',
				cache: false,
				success: function(data){
					if (data.endorse){
						$.ajax({
							url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-endorse.php',
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
		$this->input_header("onsubmit=\"return submitWrapper();\"");
		?>
		<div class="baseHeight">

		<?php
		echo DisplayLib::boxMsg($CORE_LOCAL->get("boxMsg"));
		echo "</div>";
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
		$CORE_LOCAL->set("boxMsg",'');
		$CORE_LOCAL->set("msgrepeat",2);
		if ($CORE_LOCAL->get("warned") == 0)
		MiscLib::errorBeep();
	} // END body_content() FUNCTION
}

new boxMsg2();

?>
