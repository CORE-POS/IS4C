<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/* PaycardProcessPage
 *
 * Adds some javascript functions that are useful
 * for submitting a paycard request via ajax
 *
 * $this->action should be used to prevent form
 * re-submission after a request is sent
 *
 */

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!class_exists("BasicPage")) include($IS4C_PATH.'gui-class-lib/BasicPage.php');
if (!function_exists('printfooter')) include($IS4C_PATH.'lib/drawscreen.php');

class PaycardProcessPage extends BasicPage {

	var $onload_commands;
	var $action;

	function PaycardProcessPage(){
		$this->action = "";
		parent::BasicPage();
	}

	function paycard_jscript_functions(){
		global $IS4C_PATH;
		?>
		<script type="text/javascript">
		function paycard_submitWrapper(){
			$.ajax({url: '<?php echo $IS4C_PATH; ?>ajax-callbacks/ajax-paycard-auth.php',
				cache: false,
				type: 'post',
				dataType: 'json',
				success: function(data){
					if (data.receipt){
						$.ajax({url: '<?php echo $IS4C_PATH; ?>ajax-callbacks/ajax-end.php',
							cache: false,
							type: 'post',
							data: 'receiptType='+data.receipt,
							success: function(data){}
						});
					}
					location = data.main_frame;
				}
			});
			paycard_processingDisplay();
			return false;
		}
		function paycard_processingDisplay(){
			var content = $('div.baseHeight').html();
			if (content.length >= 23)
				content = 'Waiting for response.';
			else
				content += '.';
			$('div.baseHeight').html(content);
			setTimeout('paycard_processingDisplay()',1000);
		}
		</script>
		<?php
	}

	function print_page(){
		global $IS4C_PATH;
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html>
		<?php
		echo "<head>";
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"{$IS4C_PATH}is4c.css\">";
		echo "<script type=\"text/javascript\"
			src=\"{$IS4C_PATH}js/jquery.js\"></script>";
		$this->paycard_jscript_functions();
		$this->head_content();
		echo "</head>";
		echo "<body>";
		echo "<div id=\"boundingBox\">";
		$this->input_header($this->action);
		$this->body_content();	
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";
		echo "</div>";
		$this->scale_box();
		$this->scanner_scale_polling(false);
		echo "</body>";
		if (!empty($this->onload_commands)){
			echo "<script type=\"text/javascript\">\n";
			echo "\$(document).ready(function(){\n";
			echo $this->onload_commands;
			echo "});\n";
			echo "</script>\n";
		}
		echo "</html>";
	}

}

?>
