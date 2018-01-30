<?php

function parseitem($dowrite,$plu,$itemdesc,$tare,$shelflife,$price,$bycount,
                   $type,$exception,$text,$label,$graphics){
  global $FANNIE_SCALES;
  $CSV_dir = __DIR__ . '/../../item/hobartcsv/csvfiles';
  $DGW_dir = __DIR__ . '/../../item/hobartcsv/csv_output';
    if (!function_exists('writeitem')) {
        include('writecsv.php');
    }
  
  $session_key = '';
  for ($i = 0; $i < 20; $i++){
    $num = rand(97,122);
    $session_key = $session_key . chr($num);
  }
  
  $i = 0;
  foreach($FANNIE_SCALES as $f){
    writeitem("{$CSV_dir}/{$session_key}_wi_scale_{$i}.csv",$f['type'],
          $f['host'],$f['dept'],$dowrite,$plu,$itemdesc,$tare,
          $shelflife,$price,$bycount,$type,$exception,$label,$graphics);
    writetext("{$CSV_dir}/{$session_key}_et_scale_{$i}.csv",$f['type'],
          $f['host'],$f['dept'],$plu,$text);
    $i++;
  }
  
  copycsv($CSV_dir, $DGW_dir, $session_key . '_wi_scale_');
  copycsv($CSV_dir, $DGW_dir, $session_key . '_et_scale_');
}

function copycsv($src, $dest, $prefix)
{
  global $FANNIE_SCALES;
  for ($i = 0; $i < count($FANNIE_SCALES); $i++){
    copy($src."/".$prefix.$i.".csv",
        $dest."/".$prefix.$i.".csv");
    unlink($src."/".$prefix.$i.".csv");
  }
}

function deleteitem($plu){
  global $FANNIE_SCALES;
  $CSV_dir = __DIR__ . '/../../item/hobartcsv/csvfiles';
  $DGW_dir = __DIR__ . '/../../item/hobartcsv/csv_output';
    if (!function_exists('writeitem')) {
        include('writecsv.php');
    }
  
  $session_key = '';
  for ($i = 0; $i < 20; $i++){
    $num = rand(97,122);
    $session_key = $session_key . chr($num);
  }

  $i = 0;
  foreach($FANNIE_SCALES as $f){
      delete_item("{$CSV_dir}/{$session_key}_di_scale_{$i}.csv",$f['type'],
            $f['host'],$f['dept'],$plu);
      delete_text("{$CSV_dir}/{$session_key}_dt_scale_{$i}.csv",$f['type'],
            $f['host'],$f['dept'],$plu);
    $i++;
  }

  copycsv($CSV_dir, $DGW_dir, $session_key . '_di_scale_');
  copycsv($CSV_dir, $DGW_dir, $session_key . '_dt_scale_');
}

