<?php
/*******************************************************************************

    Copyright 2007,2010 Whole Foods Co-op

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

/* InputPage
 * 
 * Automatically add the header w/ input box
 */

if (!class_exists('BasicPage')) include($_SERVER['DOCUMENT_ROOT'].'/gui-class-lib/BasicPage.php');
if (!function_exists('printfooter')) include($_SERVER['DOCUMENT_ROOT'].'/lib/drawscreen.php');

class InputPage extends BasicPage {

	function print_page(){
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html>
		<?php
		echo "<head>";
		echo "<link rel=\"stylesheet\" type=\"text/css\"
		    href=\"/is4c.css\">";
		echo "<script type=\"text/javascript\"
			src=\"/js/jquery.js\"></script>";
		$this->head_content();
		echo "</head>";
		echo "<body>";
		$this->input_header();
		echo printheaderb();
		$this->body_content();	
		echo "<div id=\"footer\">";
		echo printfooter();
		echo "</div>";
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
