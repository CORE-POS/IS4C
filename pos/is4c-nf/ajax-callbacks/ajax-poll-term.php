<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

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

 This is related to the Ingenico i6550 driver.
 It's never been used in production and likely
 doesn't work at this point, but may be helpful
 as a starting point if that driver needs to
 be resurrected.
*/

ini_set('display_errors','Off');
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

/** $termDriver is a subclass of ScaleDriverWrapper 
    the SigCapture setting is no longer in use though.
*/
$termDriver = $CORE_LOCAL->get("SigCapture");
$td = 0;
if ($termDriver != "") 
	$td = new $termDriver();

if (is_object($td)){
	$res = $td->poll("poke");
}

?>
