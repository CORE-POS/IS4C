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

/** @class PaycardProcessPage

    This class automatically includes the header and footer
    and also defines some useful javascript functions

    Normally the submit process looks like this:
     - Cashier presses enter, POST-ing to the page
     - In preprocess(), the included javascript function
       paycard_submitWrapper() is queued using
       BasicPage:add_onload_command()
     - The $action property get set to something "safe"
       like onsubmit="return false;" so that repeatedly
       pressing enter won't cause multiple submits 
 */

class PaycardProcessPage extends BasicPage {

	var $onload_commands;

	/**
	   The input form action. See BasicPage::input_header()
	   for format information
	*/
	var $action;

	function PaycardProcessPage(){
		$this->action = "";
		parent::BasicPage();
	}

	/**
	   Include some paycard submission javascript functions.
	   Automatically called during page print.
	*/
	function paycard_jscript_functions(){
		?>
		<script type="text/javascript">
		function paycard_submitWrapper(){
			$.ajax({url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-paycard-auth.php',
				cache: false,
				type: 'post',
				dataType: 'json',
				success: function(data){
					if (data.receipt){
						$.ajax({url: '<?php echo $this->page_url; ?>ajax-callbacks/ajax-end.php',
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
		$my_url = $this->page_url;
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<?php
		echo "<head>";
		// 18Aug12 EL Add content/charset.
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"{$my_url}pos.css\">";
		echo "<script type=\"text/javascript\"
			src=\"{$my_url}js/jquery.js\"></script>";
		$this->paycard_jscript_functions();
		$this->head_content();
		echo "</head>";
		echo "<body>";
		echo "<div id=\"boundingBox\">";
		$this->input_header($this->action);
		$this->body_content();	
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
		echo "</div>";
		$this->scale_box();
		$this->scanner_scale_polling(false);
		if (!empty($this->onload_commands)){
			echo "\n<script type=\"text/javascript\">\n";
			echo "\$(document).ready(function(){\n";
			echo $this->onload_commands;
			echo "});\n";
			echo "</script>\n";
		}
		// 18Aug12 EL Moved after ready-script.
		echo "</body>\n";
		echo "</html>";
	}

}

?>
