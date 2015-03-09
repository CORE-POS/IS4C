<?php

function writeitem($filename,$scaletype,$scaleip,$department,$writetype,
                   $plu,$itemdesc,$tare,$shelflife,$price,$bycount,$type,
                   $exception,$label,$graphics){
  $fp = fopen($filename,"w+");
  fputs($fp,"Record Type,Task Department,Task Destination,Task Destination Device,Task Destination Type\r\n");
  fputs($fp, "ExecuteOneTask,{$department},{$scaleip},{$scaletype},SCALE\r\n");
  
  if (!$graphics)
    fputs($fp,"Record Type,PLU Number,Item Description,Expanded Text Number,Reporting Class,Label Type 01,Tare 01,Shelf Life,Price,By Count,Item Type,Bar Code Number System,Bar Code,Force By Count\r\n");
  else
    fputs($fp,"Record Type,PLU Number,Item Description,Expanded Text Number,Reporting Class,Label Type 01,Tare 01,Shelf Life,Price,By Count,Item Type,Bar Code Number System,Bar Code,Force By Count,Graphic Number\r\n");

  if (is_array($plu)){
    for ($i = 0; $i < count($plu); $i++){
      if (($bycount[$i] === "on" or $bycount[$i] === 1) && $type[$i]=="Random Weight"){
    $type[$i] = "By Count";
    $bycount[$i] = 1;
      }
      else if ($type[$i] == "Fixed Weight"){
    $bycount[$i] = 1;
      }
      else {
    $bycount[$i] = 0;
      }
      $barcode = str_pad($plu[$i],5,"0",STR_PAD_RIGHT);

      if (!$graphics)
        fputs($fp,"{$writetype},{$plu[$i]},\"{$itemdesc[$i]}\",{$plu[$i]},999999,{$label[$i]},{$tare[$i]},{$shelflife[$i]},{$price[$i]},{$bycount[$i]},{$type[$i]},2,{$barcode},0\r\n");
      else
        fputs($fp,"{$writetype},{$plu[$i]},\"{$itemdesc[$i]}\",{$plu[$i]},999999,{$label[$i]},{$tare[$i]},{$shelflife[$i]},{$price[$i]},{$bycount[$i]},{$type[$i]},2,{$barcode},0,{$graphics[$i]}\r\n");
    }
  }
  else {
    if (($bycount === "on" or $bycount === 1) && $type=="Random Weight"){
      $bycount = 1;
      $type = "By Count";
    }
    else if ($type == "Fixed Weight"){
      $bycount = 1;
    }
    else {
      $bycount = 0;
    }
    $barcode = str_pad($plu,5,"0",STR_PAD_RIGHT);
    if (!$graphics) {
      fputs($fp,"{$writetype},{$plu},\"{$itemdesc}\",{$plu},999999,{$label},{$tare},{$shelflife},{$price},{$bycount},{$type},2,{$barcode},0\r\n");
    }
    else{
      fputs($fp,"{$writetype},{$plu},\"{$itemdesc}\",{$plu},999999,$label,{$tare},{$shelflife},{$price},{$bycount},{$type},2,{$barcode},0,{$graphics}\r\n");
    }
  }
  fclose($fp);
}

function writetext($filename,$scaletype,$scaleip,$department,$plu,$text){
  $fp = fopen($filename,"w+");
  fputs($fp,"Record Type,Task Department,Task Destination,Task Destination Device,Task Destination Type\r\n");
  fputs($fp,"ExecuteOneTask,{$department},{$scaleip},{$scaletype},SCALE\r\n");
  fputs($fp,"Record Type,Expanded Text Number,Expanded Text\r\n");

  if (is_array($plu)){
    // same as below, added to handle array data
    for ($i = 0; $i < count($plu); $i++){
      $text_lines = explode('\n',$text[$i]);
      $fixed_text = "";
      foreach($text_lines as $str){
    $fixed_text .= wordwrap($str,50,"\n") . "\n";
      }
      $text[$i] = preg_replace("/\\n/","<br />",$fixed_text);
      fputs($fp,"WriteOneExpandedText,{$plu[$i]},\"{$text[$i]}\"\r\n");
    }
  }
  else {
    // do word wrapping:
    // blow the string apart on newlines
    $text_lines = explode('\n',$text);
    $fixed_text = "";
    // wordwrap each section and glue 'em back together
    // (this maintains manual newlines by the person entering
    // data)
    foreach ($text_lines as $str){
    //echo $str;
      $fixed_text .= wordwrap($str,50,"\n") . "\n";
    }
    // change newlines to break tags so the scale
    // won't choke on them
    $text = preg_replace("/\\n/","<br />",$fixed_text);
    //echo $text."<br>";
    fputs($fp,"WriteOneExpandedText,{$plu},\"{$text}\"\r\n");
  }

  fclose($fp);
}

function delete_item($filename,$scaletype,$scaleip,$department,$plu){
  $fp = fopen($filename,"w+");
  fputs($fp,"Record Type,Task Department,Task Destination,Task Destination Device,Task Destination Type\r\n");
  fputs($fp,"ExecuteOneTask,{$department},{$scaleip},{$scaletype},SCALE\r\n");
  fputs($fp,"Record Type,PLU Number\r\n");
  if (is_array($plu)){
    foreach ($plu as $p){
      fputs($fp,"DeleteOneItem,$p\r\n");
    }
  }
  else
    fputs($fp,"DeleteOneItem,$plu\r\n");

  fclose($fp);

}

function delete_text($filename,$scaletype,$scaleip,$department,$plu){
  $fp = fopen($filename,"w+");
  fputs($fp,"Record Type,Task Department,Task Destination,Task Destination Device,Task Destination Type\r\n");
  fputs($fp,"ExecuteOneTask,{$department},{$scaleip},{$scaletype},SCALE\r\n");
  fputs($fp,"Record Type,Expanded Text Number\r\n");
  if (is_array($plu)){
    foreach ($plu as $p){
      fputs($fp,"DeleteOneExpandedText,$p\r\n");
    }
  }
  else
    fputs($fp,"DeleteOneExpandedText,$plu\r\n");

  fclose($fp);

}

