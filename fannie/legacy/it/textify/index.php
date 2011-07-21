<?php

header('Content-type: image/png');

$w = 2825; $h = 4570;
$im = imagecreatetruecolor($w, $h);

$white = imagecolorallocate($im, 255, 255, 255);
imagefilledrectangle($im, 0, 0, $w, $h, $white);

$fp = fopen('pa','r');

$color = imagecolorallocate($im,0,0,0);

$x = 2; $y=4;
$drop = 8;

while (!feof($fp)){
	$str = fread($fp,1280);
	$dims = imagettftext($im,3,0,$x,$y,$color,'Monaco.ttf',$str);
	$y += $drop;
}

imagepng($im);

?>
