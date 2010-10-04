<?php
/*******************************************************************************

    Copyright 2007 Whole Foods Co-op

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

/* MainFramePage
 *
 * This subclass incorporates the headers and footers
 * from drawscreen.php automatically, since they're used
 * quite often. Internal header and footer variables
 * are maintained to determine which (if any) headers
 * and footers should be printed. By default, it uses
 * printheaderb() and printfooter
 */

if (!class_exists("BasicPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/BasicPage.php");
if (!function_exists("printheaderb")) include_once($_SERVER["DOCUMENT_ROOT"]."/lib/drawscreen.php");

class MainFramePage extends BasicPage {
	var $header;
	var $footer;

	function MainFramePage($h=1,$f=1){
		$this->header = $h;
		$this->footer = $f;

		$this->print_page();
	}

	function print_page(){
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
		<html>
		<?php	
		if ($this->preprocess()){
			echo "<head>";
		      	echo "<link rel=\"stylesheet\" type=\"text/css\"
		            href=\"/is4c.css\">";
			echo "<script type=\"text/javascript\"
				src=\"/js/jquery.js\"></script>";
			$this->head();
			echo "</head>";
			$this->body_tag();

			if ($this->header == 1)
				echo printheaderb();

			$this->body_content();	

			if ($this->footer == 1)
				printfooter();
			elseif ($this->footer == 2)
				printfooterb();

			print "</body>";
		}
		print "</html>";	
	}
}

?>
