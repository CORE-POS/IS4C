<?php

class WfcLib
{
    const ALIGN_RIGHT = 1;
    const ALIGN_LEFT = 2;
    const ALIGN_CENTER = 4;
    const TYPE_MONEY = 8;

    private static function tableHeader($col_headers, $formatting)
    {
        $ret = "<table cellspacing=0 cellpadding=4 border=1><tr>";
        $inc = 0;
        foreach ($col_headers as $c){
            while ($formatting[$inc] == 0) $inc++;
            $ret .= self::cellify("<u>".$c."</u>",$formatting[$inc++]&7);
        }
        $ret .= "</tr>";

        return $ret;
    }

    private static function tableBody($data, $col_order, $formatting)
    {
        if (count($data) == 0) {
            return "<tr>
                <td colspan=".count($col_order)." class=center>
                No results to report</td>
                </tr>";
        }

        $ret = '';
        foreach(array_keys($data) as $k){
            $ret .= "<tr>";
            foreach($col_order as $c){
                if($c == 0) $ret .= self::cellify($k,$formatting[$c]);
                else $ret .= self::cellify($data[$k][$c-1],$formatting[$c]);
            }
            $ret .= "</tr>";
        }

        return $ret;
    }

    private static function sumData($data, $sum_col)
    {
        $sum = 0;
        foreach(array_keys($data) as $k){
            $sum += $data[$k][$sum_col-1];
        }

        return $sum;
    }

    private static function sumRow($sum, $col_order, $formatting, $sum_col)
    {
        $ret = "<tr>";
        foreach($col_order as $c){
            if ($c+1 == $sum_col) $ret .= "<td>Total</td>";
            elseif ($c == $sum_col) $ret .= self::cellify($sum,$formatting[$c]);
            else $ret .= "<td>&nbsp;</td>";
        }
        $ret .= "</tr>";

        return $ret;
    }

    public static function tablify($data,$col_order,$col_headers,$formatting,$sum_col=-1)
    {
        $ret = "";
        
        $ret .= self::tableHeader($col_headers, $formatting);
        $ret .= self::tableBody($data, $col_order, $formatting);

        if ($sum_col != -1 && count($data) > 0){
            $sum = self::sumData($data, $sum_col);
            $ret .= self::sumRow($sum, $col_order, $formatting, $sum_col);
        }

        $ret .= "</table>";

        return $ret;
    }

    private static function cellify($data,$formatting)
    {
        $ret = "";
        if ($formatting & self::ALIGN_LEFT) $ret .= "<td class=left>";
        elseif ($formatting & self::ALIGN_RIGHT) $ret .= "<td class=right>";
        elseif ($formatting & self::ALIGN_CENTER) $ret .= "<td class=center>";

        if ($formatting & self::TYPE_MONEY) $ret .= sprintf("%.2f",$data);
        else $ret .= $data;

        $ret .= "</td>";

        return $ret;
    }

    private static $tenders = array("Cash"=>array(10120,0.0,0),
            "Check"=>array(10120,0.0,0),
            "WIC"=>array(10120,0.0,0),
            "Electronic Check"=>array(10120,0.0,0),
            "Rebate Check"=>array(10120,0.0,0),
            "Credit Card"=>array(10120,0.0,0),
            "EBT CASH."=>array(10120,0.0,0),
            "EBT FS"=>array(10120,0.0,0),
            "Gift Card"=>array(21205,0.0,0),
            "GIFT CERT"=>array(21200,0.0,0),
            "InStore Charges"=>array(10710,0.0,0),
            "Pay Pal"=>array(10120,0.0,0),
            "Coupons"=>array(10740,0.0,0),
            "InStoreCoupon"=>array(67710,0.0,0),
            "Store Credit"=>array(21200,0.0,0),
            "RRR Coupon"=>array(63380,0.0,0));
    public static function getTenders()
    {
        return self::$tenders;
    }

    private static $pCodes = array("41201"=>array(0.0),
            "41205"=>array(0.0),
            "41300"=>array(0.0),
            "41305"=>array(0.0),
            "41310"=>array(0.0),
            "41315"=>array(0.0),
            "41400"=>array(0.0),
            "41405"=>array(0.0),
            "41407"=>array(0.0),
            "41410"=>array(0.0),
            "41415"=>array(0.0),
            "41420"=>array(0.0),
            "41425"=>array(0.0),
            "41430"=>array(0.0),
            "41435"=>array(0.0),
            "41440"=>array(0.0),
            "41445"=>array(0.0),
            "41500"=>array(0.0),
            "41505"=>array(0.0),
            "41510"=>array(0.0),
            "41515"=>array(0.0),
            "41520"=>array(0.0),
            "41525"=>array(0.0),
            "41530"=>array(0.0),
            "41600"=>array(0.0),
            "41605"=>array(0.0),
            "41610"=>array(0.0),
            "41640"=>array(0.0),
            "41645"=>array(0.0),
            "41700"=>array(0.0),
            "41705"=>array(0.0));
    public static function getPCodes()
    {
        return self::$pCodes;
    }

    private static $others = array("600"=>array("64410","SUPPLIES",0.0),
            "604"=>array("&nbsp;","MISC PO",0.0),
            "700"=>array("63320","TOTES",0.0),
            "703"=>array("&nbsp;","MISCRECEIPT",0.0),
            "708"=>array("42225","CLASSES",0.0),
            "800"=>array("&nbsp;","IT Corrections",0.0),
            "881"=>array("42231","MISC #1",0.0),
            "882"=>array("42232","MISC #2",0.0),
            "900"=>array("21200","GIFTCERT",0.0),
            "902"=>array("21205","GIFTCARD",0.0),
            "990"=>array("10710","ARPAYMEN",0.0),
            "991"=>array("31110","CLASS B Equity",0.0),
            "992"=>array("31100","CLASS A Equity",0.0));
    public function getOtherCodes()
    {
        return self::$others;
    }
}

