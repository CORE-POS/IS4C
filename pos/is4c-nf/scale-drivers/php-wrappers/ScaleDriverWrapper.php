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
*/

$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

class ScaleDriverWrapper {

	function SavePortConfiguration($portName){}
	
	/* absPath is the top of IS4C, should have
	   trailing slash
	*/
	function SaveDirectoryConfiguration($absPath){}

	/* reads available scale and scanner input
	   Function should print a JSON object with two fields:
		'scale' is an HTML string to display current scale weight/status
		'scans' is an array of UPCs
	   Use scaledisplaymsg() to generate scale HTML. This ensures
	   appropriate weight-related session variables are
	   updated.
	*/
	function ReadFromScale(){}

	/* send output to the scale. Possible inputs
	   (not case sensitive):
		1. goodBeep
		2. errorBeep
		3. twoPairs
		4. rePoll
		5. wakeup
	*/
	function WriteToScale($str){}

}

?>
