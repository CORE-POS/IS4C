<?php
include('writecsv.php');

function parseitem($dowrite,$plu,$itemdesc,$tare,$shelflife,$price,$bycount,
                   $type,$exception,$text,$label,$graphics){
  include('ini.php');

  $session_key = '';
  for ($i = 0; $i < 20; $i++){
    $num = rand(97,122);
    $session_key = $session_key . chr($num);
  }
  
  for ($i = 0; $i < $num_scales; $i++){
    writeitem("{$CSV_dir}/{$session_key}_wi_scale_{$i}.csv",$scale_types[$i],
          $scale_ips[$i],$department,$dowrite,$plu,$itemdesc,$tare,
          $shelflife,$price,$bycount,$type,$exception,$label,$graphics);
    writetext("{$CSV_dir}/{$session_key}_et_scale_{$i}.csv",$scale_types[$i],
          $scale_ips[$i],$department,$plu,$text);
  }
  
  for ($i = 0; $i < $num_scales; $i++){
    exec("cp {$CSV_dir}/{$session_key}_wi_scale_{$i}.csv $DGW_dir");
    exec("rm -f {$CSV_dir}/{$session_key}_wi_scale_{$i}.csv");
    exec("cp {$CSV_dir}/{$session_key}_et_scale_{$i}.csv $DGW_dir");
    exec("rm -f {$CSV_dir}/{$session_key}_et_scale_{$i}.csv");
  }

}

function deleteitem($plu,$scalenum){
  include('ini.php');
  
  $session_key = '';
  for ($i = 0; $i < 20; $i++){
    $num = rand(97,122);
    $session_key = $session_key . chr($num);
  }

  for ($i = 0; $i < $num_scales; $i++){
      delete_item("{$CSV_dir}/{$session_key}_di_scale_{$i}.csv",$scale_types[$i],
            $scale_ips[$i],$department,$plu);
      delete_text("{$CSV_dir}/{$session_key}_dt_scale_{$i}.csv",$scale_types[$i],
            $scale_ips[$i],$department,$plu);
  }

  for ($i = 0; $i < $num_scales; $i++){
      exec("cp {$CSV_dir}/{$session_key}_di_scale_{$i}.csv $DGW_dir");
      exec("rm -f {$CSV_dir}/{$session_key}_di_scale_{$i}.csv");
      exec("cp {$CSV_dir}/{$session_key}_dt_scale_{$i}.csv $DGW_dir");
      exec("rm -f {$CSV_dir}/{$session_key}_dt_scale_{$i}.csv");
  }
}

