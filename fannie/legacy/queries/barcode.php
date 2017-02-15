<?php

$MODIFICATIONS = 0;

$MOD_UPC_CHECK = 1<<0;
$MOD_EAN_CHECK = 1<<1;
$MOD_UPC_EE = 1<<2;
$MOD_SCALE_UPC = 1<<3;
$MOD_ORG_NINE = 1<<4;

function fixBarcode($barcode){
    global $MODIFICATIONS,$MOD_UPC_CHECK,$MOD_EAN_CHECK,$MOD_UPC_E,$MOD_SCALE_UPC,$MOD_ORG_NINE;
    return $barcode;

    # strip leading zeros
    while ($barcode[0] == "0"){
        $barcode = substr($barcode,1);
    }

    # zero out the end of "2" prefixed UPCs (scale stickered items)
    if (strlen($barcode) == 11 and $barcode[0] == 2){
        if (substr($barcode,5) != "000000"){
            $barcode = substr($barcode,0,5)."000000";
            $MODIFICATIONS = $MODIFICATIONS | $MOD_SCALE_UPC;
        }
    }
    elseif (strlen($barcode) == 5 and $barcode[0] == "9"){
        $barcode = substr($barcode,1);
        $MODIFICATIONS = $MODIFICATIONS | $MOD_ORG_NINE;
    }
    else {
        # remove check digits from UPC-A or EAN-13
        # expand UPC-E
        if (strlen($barcode)<=12 and hasUPCcheck(str_pad($barcode,12,"0",STR_PAD_LEFT))){
            $barcode = substr($barcode,0,strlen($barcode)-1);
            $MODIFICATIONS = $MODIFICATIONS | $MOD_UPC_CHECK;
        }
        elseif (strlen($barcode)<=13 and  hasEANcheck(str_pad($barcode,13,"0",STR_PAD_LEFT))){
            $barcode = substr($barcode,0,strlen($barcode)-1);
            $MODIFICATIONS = $MODIFICATIONS | $MOD_EAN_CHECK;
        }
        elseif (strlen($barcode) == 6 or strlen($barcode) == 7){
            $expansion = expandUPCE($barcode);
            if ($expansion){
                $barcode = $expansion;
                $MODIFICATIONS = $MODIFICATIONS | $MOD_UPC_E;
            }
        }
    }


    return str_pad($barcode,13,"0",STR_PAD_LEFT);
}

function hasUPCcheck($barcode){
    if (strlen(ltrim($barcode,'0')) < 11) return False;

    $sum1 = 0;
    $sum2 = 0;
    for ($i = 0; $i < 11; $i++){
        if ($i%2==0) $sum1 += (int)$barcode[$i];
        else $sum2 += (int)$barcode[$i];
    }
    $total = (3*$sum1)+$sum2;

    $checkDigit = 10 - ($total%10);
    $checkDigit = $checkDigit % 10;

    if ($barcode[11] == $checkDigit) return True;
    else return False;
}

function hasEANcheck($barcode){
    if (strlen(ltrim($barcode,'0')) < 11) return False;

    $sum1 = 0;
    $sum2 = 0;
    for ($i = 0; $i < 12; $i++){
        if ($i%2==0) $sum1 += (int)$barcode[$i];
        else $sum2 += (int)$barcode[$i];
    }
    $total = (3*$sum2)+$sum1;

    $checkDigit = 10 - ($total%10);
    $checkDigit = $checkDigit % 10;

    if ($barcode[12] == $checkDigit) return True;
    else return False;
}

function expandUPCE($barcode){
    $check = "";
    if (strlen($barcode) == 7) $check = $barcode[6];

    $expanded = "";
    switch((int)$barcode[5]){
    case 0:
    case 1:
    case 2:
        $expanded = "0".substr($barcode,0,2).$barcode[5]+"00";
        $expanded .= "00".substr($barcode,2,3);
        break;
    case 3:
        $expanded = "0".substr($barcode,0,3)."00";    
        $expanded .= "000".substr($barcode,3,2);
        break;
    case 4:
        $expanded = "0".substr($barcode,0,4)."0";
        $expanded .= "0000".$barcode[4];
        break;
    default:
        $expanded = "0".substr($barcode,0,5);
        $expanded .= "0000".$barcode[5];
        break;
    }

    if ($check != "" and hasUPCcheck($expanded.$check)) return $expanded;
    elseif ($check != "" and !hasUPCcheck($expanded.$check)) return False;
    else return $expanded;
}

function printMods($original_upc){
    global $MODIFICATIONS,$MOD_UPC_CHECK,$MOD_EAN_CHECK,$MOD_UPC_E,$MOD_SCALE_UPC,$MOD_ORG_NINE;
    if ($MODIFICATIONS == 0) return;

    print "<div id=mods><i>";
    print "The UPC you entered has been modified:<br />";
    if ($MODIFICATIONS & $MOD_UPC_CHECK)
        print "The UPC check digit has been removed<br />";
    if ($MODIFICATIONS & $MOD_EAN_CHECK)
        print "The EAN check digit has been removed<br />";
    if ($MODIFICATIONS & $MOD_UPC_E)
        print "The UPC-E has been expanded to UPC-A<br />";
    if ($MODIFICATIONS & $MOD_SCALE_UPC)
        print "The last 6 digits have been changed to zero for the scale UPC<br />";
    if ($MODIFICATIONS & $MOD_ORG_NINE)
        print "The leading 9 has been removed from the organic produce PLU<br />";
    print "If you really want to use this UPC: ".$original_upc.", ";
    print "<a href=productTest.php?upc=".$original_upc."&forceUPC=yes>Click Here</a> to override";
    print "</i></div>";
}

