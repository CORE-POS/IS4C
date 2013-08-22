<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

/** @class NoInputPage

    This class automatically adds the header and
    and footer, but the header does not contain
    an input form.

    Normally pages using this class will define
    their own form in body_content().
 */

class NoInputPage extends BasicPage {

	function print_page(){
		$my_url = $this->page_url;
		?>
		<!DOCTYPE html>
		<html>
		<?php
		echo "<head>";
		// 18Aug12 EL Add content/charset.
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n";
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"{$my_url}css/pos.css\">";
		// include store css file if it exists
		if (file_exists(dirname(__FILE__).'/../store.css')){
			echo "<link rel=\"stylesheet\" type=\"text/css\"
			    href=\"{$my_url}/store.css\">";
		}
		echo "<script type=\"text/javascript\"
			src=\"{$my_url}js/jquery.js\"></script>";
		$this->head_content();
		echo "</head>";
		echo '<body class="'.$this->body_class.'">';
		echo "<div id=\"boundingBox\">";
		$this->noinput_header();
		echo DisplayLib::printheaderb();
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
		print "</html>";
	}

}

?>
