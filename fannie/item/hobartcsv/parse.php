<?php

function parseitem($dowrite,$plu,$itemdesc,$tare,$shelflife,$price,$bycount,
                   $type,$exception,$text,$label,$graphics){
  global $FANNIE_ROOT, $FANNIE_SCALES;
  $CSV_dir = $FANNIE_ROOT.'item/hobartcsv/csvfiles';
  $DGW_dir = $FANNIE_ROOT.'item/hobartcsv/csv_output';
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
  
  for ($i = 0; $i < count($FANNIE_SCALES); $i++){
    copy($CSV_dir."/".$session_key."_wi_scale_".$i.".csv",
        $DGW_dir."/".$session_key."_wi_scale_".$i.".csv");
    unlink($CSV_dir."/".$session_key."_wi_scale_".$i.".csv");
    copy($CSV_dir."/".$session_key."_et_scale_".$i.".csv",
    $DGW_dir."/".$session_key."_et_scale_".$i.".csv");
    unlink($CSV_dir."/".$session_key."_et_scale_".$i.".csv");
  }
}

function deleteitem($plu){
  global $FANNIE_ROOT, $FANNIE_SCALES;
  $CSV_dir = $FANNIE_ROOT.'item/hobartcsv/csvfiles';
  $DGW_dir = $FANNIE_ROOT.'item/hobartcsv/csv_output';
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

  for ($i = 0; $i < count($FANNIE_SCALES); $i++){
    copy($CSV_dir."/".$session_key."_di_scale_".$i.".csv",
         $DGW_dir."/".$session_key."_di_scale_".$i.".csv");
    unlink($CSV_dir."/".$session_key."_di_scale_".$i.".csv");
    copy($CSV_dir."/".$session_key."_dt_scale_".$i.".csv",
         $DGW_dir."/".$session_key."_dt_scale_".$i.".csv");
    unlink($CSV_dir."/".$session_key."_dt_scale_".$i.".csv");
  }
}

?>
