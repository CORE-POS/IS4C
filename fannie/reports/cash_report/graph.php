<?php

/*
draws a bargraph of data in png form in filename
ARGS:
$data is an array of data to be graphed
$labels is an array of labels for the x-axis
  (should be the same size as data)
$filename is the file to write the png to
  (must be owned by www)
$scale is the vertical scaling  (i.e.,size of one unit)
  (defaults to 10)
$r,$g,$b is the fill color
  (defaults to red)
*/
function graph($data, $labels, $filename, $scale=10,$r=255,$g=0,$b=0){
  $left = 22;
  $bottom = 20;
  $top = 5;
  $col_width = 10;
  $row_height = $scale;
  $width = count($data)*$col_width;
  
  /* create an image and paint it white */
  $height = ceil(max($data))*$row_height;
  $im = imagecreatetruecolor($width+$left+2,$height+$bottom+$top);
  $white = imagecolorallocate($im,255,255,255);
  imagefill($im,0,0,$white);

  /* get black */
  $black = imagecolorallocate($im,0,0,0);

  /* begin drawing the grid */

  /* top and bottom */
  imageline($im,$left,$top,$width+$left,$top,$black);
  imageline($im,$left,$height+$top,$width+$left,$height+$top,$black);

  /* draw column markers and periodic labels */
  for ($i = 0; $i < $width / $col_width; $i++){
    imageline($im,$col_width*$i+$left,$top,$col_width*$i+$left,$height+$top,$black);
    if ($i % 7 == 0){
      imageline($im,$col_width*$i+$left+$col_width/2,$height+$top,
                $col_width*$i+$left+$col_width/2,$height+$top+$bottom/2-1,$black);
      imagestring($im,1,$col_width*$i+$left-20,$height+$top+$bottom/2,
                  $labels[$i],$black);
    }
  }
  /* last column marker */
  imageline($im,$width+$left,$top,$width+$left,$height+$top,$black);

  /* draw row markers and periodic labels */
  $period = ceil(30 / $row_height); // as scale grows, place markers more often
  for ($i = $height/$row_height-1; $i >= 0; $i--){
    if ($i % $period == 0){
      imageline($im,$left/2,$i*$row_height+$top,$left+$width,$i*$row_height+$top,$black);
      imagestring($im,1,0,$i*$row_height-4+$top,$height/$row_height-$i,$black);
    }
    else {
      imageline($im,$left,$i*$row_height+$top,$left+$width,$i*$row_height+$top,$black);
    }
  }

  /* fill in data */
  $fill = imagecolorallocate($im,$r,$g,$b);
  for ($i = 0; $i < count($data); $i++){
    $temp_h = $data[$i] * $row_height;
    imagefilledrectangle($im,$i*$col_width+$left+3,$height-$temp_h+$top,
                         $i*$col_width+$left+7,$height-1+$top,$fill);

  }

  /* save the image */
  imagepng($im,$filename);

  imagedestroy($im);

  return $width+$left+2;
}


function linegraph($data, $labels, $filename, $scale=-1,$thickness=1){
  $left = 46;
  $bottom = 20;
  $top = 5;
  $col_width = 20;
  $row_height = 10;
  $width = (count($data)-1)*$col_width;
  
  /* create an image and paint it white */
  $max = 0;
  $min = 999999;
  foreach($data as $d){
    if (max($d) > $max)
      $max = max($d);
    if (abs_min($d) < $min)
      $min = abs_min($d);
  }
  $range = $max - $min;
  /* auto scaling, seems to work for now */
  if ($scale == -1)
    $scale = .3/($range / 100);
  $height = ceil($max*$row_height*$scale);
  $offset = floor($min*$row_height*$scale);
  $im = imagecreatetruecolor($width+$left+2,$height+$bottom+$top-$offset);
  $white = imagecolorallocate($im,255,255,255);
  imagefill($im,0,0,$white);

  /* get black */
  $black = imagecolorallocate($im,0,0,0);

  /* begin drawing the grid */

  /* top and bottom */
  imageline($im,$left,$top,$width+$left,$top,$black);
  imageline($im,$left,$height+$top-$offset,$width+$left,$height+$top-$offset,$black);

  /* draw column markers and periodic labels */
  for ($i = 0; $i < $width / $col_width; $i++){
    imageline($im,$col_width*$i+$left,$top,$col_width*$i+$left,$height+$top-$offset,$black);
    if ($i % 7 == 0){
      imageline($im,$col_width*$i+$left,$height+$top-$offset,
                $col_width*$i+$left,$height+$top-$offset+$bottom/2-1,$black);
      imagestring($im,1,$col_width*$i+$left-20,$height+$top-$offset+$bottom/2,
                  $labels[$i],$black);
    }
  }
  /* last column marker */
  imageline($im,$width+$left,$top,$width+$left,$height+$top-$offset,$black);

  /* draw row markers and periodic labels */
  $period = ceil(30 / $row_height); // as scale grows, place markers more often
  for ($i = ($height-$offset)/$row_height-1; $i >= 0; $i--){
    if ($i % $period == 0){
      imageline($im,$left-6,$i*$row_height+$top,$left+$width,$i*$row_height+$top,$black);
      imagestring($im,1,0,$i*$row_height-4+$top,round($height/$row_height/$scale-$i/$scale,2),$black);
    }
    else {
      imageline($im,$left,$i*$row_height+$top,$left+$width,$i*$row_height+$top,$black);
    }
  }

  $colors = array(
                array(255,0,0),
                array(0,0,255),
                array(0,255,0)
                 );
  /* map the data */
  imagesetthickness($im,$thickness);
  $prevs = array_fill(0,count($data[0]),-1);
  $backtrack = array_fill(0,count($data[0]),-1);
  for ($i = 0; $i < count($data); $i++){
    for($j = 0; $j < count($data[$i]); $j++){
      if ($data[$i][$j] != -1){
        $fill = imagecolorallocate($im,$colors[$j][0],$colors[$j][1],$colors[$j][2]);
        imagefilledellipse($im,$i*$col_width+$left,$height-($data[$i][$j]*$scale*$row_height)+$top,4*sqrt($thickness),4*sqrt($thickness),$fill);
        if ($prevs[$j] != -1){
          imageline($im,$i*$col_width+$left,$height-($data[$i][$j]*$scale*$row_height)+$top,
                    ($i-$backtrack[$j])*$col_width+$left,$height-($prevs[$j]*$scale*$row_height)+$top,$fill);
        }
      }
      if ($data[$i][$j] != -1){
        $prevs[$j] = $data[$i][$j];
        $backtrack[$j] = 1;
      }
      else{ 
        $backtrack[$j]++;
      }
    }
  }

  /* save the image */
  imagepng($im,$filename);

  imagedestroy($im);

  return $width+$left+2;
}

/* custom minimum function to ignore negative values */
function abs_min($a){
  $min = 999999;
  foreach($a as $i){
    if ($i < $min && $i >= 0)
      $min = $i;
  }
  return $min;
}

?>
