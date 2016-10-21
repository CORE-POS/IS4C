<?php

namespace COREPOS\pos\lib\ReceiptBuilding\CustMessages;
use COREPOS\pos\lib\ReceiptLib;
use \CoreLocal;

class WfcEquityMessage extends CustomerReceiptMessage {

    function message($str)
    {
        $ret = "";
        if (strstr($str," == ") ){
            $lines = explode(" == ",$str);
            if (CoreLocal::get("equityNoticeAmt") > 0){
                if (isset($lines[0]) && is_numeric(substr($lines[0],13))){
                    $newamt = substr($lines[0],13) - CoreLocal::get("equityNoticeAmt");
                    $lines[0] = sprintf('EQUITY BALANCE DUE $%.2f',$newamt);
                    if ($newamt <= 0 && isset($lines[1]))
                        $lines[1] = "PAID IN FULL";
                }
            }
            foreach($lines as $line)
                $ret .= ReceiptLib::centerString($line)."\n";
        }
        else
            $ret .= $str;
        return $ret;
    }

}

