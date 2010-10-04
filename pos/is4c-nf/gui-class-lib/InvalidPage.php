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

/* InvalidPage
 *
 * This subclass maintains an internal INVALID flag
 * if the GET variable "invalid" is set, this flag
 * will be enabled.
 *
 * Two new methods are introduced:
 *   valid_body()
 *   invalid_body()
 * Both should be overriden
 *
 * body_content() will call whichever of the above
 * functions is appropriate. Do not override 
 * body_content() for this class (unless you know
 * what you're doing)
 */

if (!class_exists("BasicPage")) include_once($_SERVER["DOCUMENT_ROOT"]."/gui-class-lib/BasicPage.php");

class InvalidPage extends BasicPage {
	
	var $INVALID;

	function InvalidPage(){
		if (isset($_GET["invalid"]))
			$this->INVALID = True;
		else
			$this->INVALID = False;

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
