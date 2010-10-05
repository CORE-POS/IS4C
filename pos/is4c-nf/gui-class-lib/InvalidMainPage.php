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

/* InvalidMainPage
 *
 * Combines InvalidPage and MainFramePage (hoo-ray
 * for no multiple inheritence)
 *
 * Headers and footers are incorporated as in
 * MainFramePage
 *
 * body_content() will call either valid_body()
 * or invalid_body() as in InvalidPage.
 *
 * Once again, do not override body_content()
 * unless you know what you're doing
 */

if (!class_exists("MainFramePage.php")) include_once($_SESSION["INCLUDE_PATH"]."/gui-class-lib/MainFramePage.php");

class InvalidMainPage extends MainFramePage {
	var $INVALID;

	function InvalidMainPage($h=1,$f=1){
		if (isset($_GET["invalid"]))
			$this->INVALID = True;
		else
			$this->INVALID = False;

		$this->header = $h;
		$this->footer = $f;

		$this->print_page();

	}

	function valid_body(){

	}

	function invalid_body(){

	}

	function body_content(){
		if ($this->INVALID)
			$this->invalid_body();
		else
			$this->valid_body();
	}	
}

?>
