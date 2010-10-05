<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

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
$IS4C_PATH = isset($IS4C_PATH)?$IS4C_PATH:"";
if (empty($IS4C_PATH)){ while(!file_exists($IS4C_PATH."is4c.css")) $IS4C_PATH .= "../"; }

if (!isset($IS4C_LOCAL))
	include($IS4C_PATH.'lib/LocalStorage/conf.php');
if (!function_exists('scaledisplaymsg'))
	include($IS4C_PATH.'lib/drawscreen.php');
if (!function_exists('array_to_json'))
	include($IS4C_PATH.'lib/array_to_json.php');

$readfile = $IS4C_PATH.'NewMagellan/scanner-scale';
$i = 0;

$scale_display = "";
$scans = array();
if (file_exists($readfile.".data") && !file_exists($readfile.".lock")){

	$fp = fopen($readfile.".lock","w");
	fclose($fp);

	$data = file_get_contents($readfile.".data");

	unlink($readfile.".data");
	unlink($readfile.".lock");

	foreach(explode("\n",$data) as $line){
		$line = rtrim($line,"\r"); // in case OS adds it
		if (empty($line)) continue;
		if ($line[0] == 'S'){
			$scale_display = scaledisplaymsg($line);
		}
		else {
			$scans[] = $line;
		}
	}
}

$output = array();
if (!empty($scale_display)) $output['scale'] = $scale_display;
if (!empty($scans)) $output['scans'] = $scans;

if (!empty($output))
	echo array_to_json($output);
else
	echo "{}";

?>
