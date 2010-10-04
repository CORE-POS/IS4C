<?php

header("Content-type: image/png");
$size = $_GET['size'];
$width = 200;
$height = 17;

$image = imagecreate($width,$height);

$white = imagecolorallocate($image,255,255,255);
$black = imagecolorallocate($image,0,0,0);
$hotpink = imagecolorallocate($image,207,41,48);
$green = imagecolorallocate($image,51,204,0);
imagefilledrectangle($image,0,0,$width,$height,$white);

if($size > 60){
   imagefilledrectangle($image,0,1,$size,15,$green);
}elseif($size < 20){
   imagefilledrectangle($image,0,1,$size,15,$hotpink);
}else{
   imagefilledrectangle($image,0,1,$size,15,$black);
}
imagepng($image);

imagedestroy($image);

?>
