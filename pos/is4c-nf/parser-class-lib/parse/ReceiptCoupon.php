<?php

class ReceiptCoupon extends Parser
{
    public function check($str)
    {
        if (substr($str, 0, 2) == 'RC' && is_numeric(substr($str, 2))) {
            return true;
        } else {
            return false;
        }
    }

    public function parse($str)
    {
        $year = '20' . substr($str, 2, 2);
        $month = substr($str, 4, 2);
        $couponID = substr($str, -3);

        $expireTS = mktime(0, 0, 0, $month, 1, $year);
        $expireTS = strtotime(date('Y-m-t', $expireTS));

        $ret = $this->default_json();
        if (time() > $expireTS) {
            $ret['output'] = DisplayLib::boxMsg('Coupon is expired');
            return $ret;
        } else {
            $upc = '004' . '99999' . str_pad($couponID, 5, '0', STR_PAD_LEFT);
            $hc = new HouseCoupon();
            return $hc->handle($upc, $ret);
        }
    }
}
