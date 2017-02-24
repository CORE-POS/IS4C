<?php

use COREPOS\pos\parser\Parser;
use COREPOS\pos\lib\Scanning\SpecialUPCs\HouseCoupon;
use COREPOS\pos\lib\Database;

/**
  A linked coupon is a coupon associated with a particular owner
  but redeemed by someone else

  Input format: [owner number]=>[coupon ID]

  This parser applies the specified coupon then sets numflag for
  that record to the associated owner and charflag to IC
*/
class LinkedCoupon extends Parser
{
    public function check($str)
    {
        return preg_match('/^(\d+)=>(\d+)$/', $str) ? true : false;
    }

    public function parse($str)
    {
        $matched = preg_match('/^(\d+)=>(\d+)$/', $str, $matches);
        $card_no = $matches[1];
        $coupID = $matches[2];
        $upc = '00499999' . str_pad($coupID, 5, '0', STR_PAD_LEFT);

        $ret = $this->default_json();
        $houseCoupon = new HouseCoupon($this->session);
        $ret = $houseCoupon->handle($upc, $ret);

        $dbc = Database::tDataConnect();
        $tagP = $dbc->prepare("UPDATE localtemptrans SET numflag=?, charflag='IC' WHERE upc=?");
        $dbc->execute($tagP, array($card_no, $upc));

        return $ret;
    }
}

