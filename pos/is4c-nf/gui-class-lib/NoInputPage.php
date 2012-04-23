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

$CORE_PATH = isset($CORE_PATH)?$CORE_PATH:"";
if (empty($CORE_PATH)){ while(!file_exists($CORE_PATH."pos.css")) $CORE_PATH .= "../"; }

if (!class_exists('BasicPage')) include($CORE_PATH.'gui-class-lib/BasicPage.php');
if (!function_exists('printfooter')) include($CORE_PATH.'lib/drawscreen.php');

class NoInputPage extends BasicPage {

	function print_page(){
		global $CORE_PATH;
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html>
		<?php
		echo "<head>";
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"{$CORE_PATH}pos.css\">";
		echo "<script type=\"text/javascript\"
			src=\"{$CORE_PATH}js/jquery.js\"></script>";
		$this->head_content();
		echo "</head>";
		echo "<body>";
		echo "<div id=\"boundingBox\">";
		$this->noinput_header();
		echo printheaderb();
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
		print "</html>";
	}

}

?>
