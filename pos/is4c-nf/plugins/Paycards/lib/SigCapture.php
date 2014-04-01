<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

/**
  @deprecated 11Mar14 Andy

  Related to Ingenico i6550 driver. Never finished.
  No references to this class should remain elsewhere
  in the codebase.
*/
class SigCapture {

static public function term_object(){
    return false;
    /**
	global $CORE_LOCAL;
	$termDriver = $CORE_LOCAL->get("SigCapture");
	$td = 0;
	if ($termDriver != "" && !class_exists($termDriver)){
		include(realpath(dirname(__FILE__).
			'/../scale-drivers/php-wrappers/'.$termDriver.'.php'));
		$td = new $termDriver();
	}
	if (is_object($td)) return $td;
	return False;
    */
}

}

?>
