<?php

namespace COREPOS\pos\plugins\Paycards\card;

class CardValidator
{
    /**
      Validate number using Luhn's Algorithm
      @param $pan the card number
      @return
       - 1 if the number is valid
       - 0 if the number is invalid
    */
    public function validNumber($pan) 
    {
    /* Luhn Algorithm <en.wikipedia.org/wiki/Luhn_algorithm>
    1. Starting with the rightmost digit (which is the check digit) and moving left, double the value of every second digit.  For any
      digits that thus become 10 or more, add their digits together as if casting out nines. For example, 1111 becomes 2121, while
      8763 becomes 7733 (from 2*6=12 -> 1+2=3 and 2*8=16 -> 1+6=7).
    2. Add all these digits together. For example, if 1111 becomes 2121, then 2+1+2+1 is 6; and 8763 becomes 7733, so 7+7+3+3 is 20.
    3. If the total ends in 0 (put another way, if the total modulus 10 is congruent to 0), then the number is valid according to the
      Luhn formula; else it is not valid. So, 1111 is not valid (as shown above, it comes out to 6), while 8763 is valid (as shown above,
      it comes out to 20).
    */
        // prepare the doubling-summing conversion array
        $doublesum = array(0=>0,1=>2,2=>4,3=>6,4=>8,5=>1,6=>3,7=>5,8=>7,9=>9);
        // turn the number into a string, reverse it, and split it into an array of characters (which are digits)
        /* php5 */ //$digits = str_split(strrev((string)$pan));
        /* php4 */ $digits = preg_split('//', strrev((string)$pan), -1, PREG_SPLIT_NO_EMPTY);
        // run the sum
        $sum = 0;
        foreach( $digits as $index => $digit) {
            // $index starts at 0 but counts from the right, so we double any digit with an odd index
            if( ($index % 2) == 1)  $sum += $doublesum[(int)$digit];
            else                    $sum += (int)$digit;
        }
        // it has to end in 0 (meaning modulo:10 == 0)
        if( ($sum % 10) != 0)
            return 0;
        // ok
        return 1;
    } 


    // determine if the expiration date (passed as a string, MMYY) is a valid date and is not in the past
    // return 1 if ok, error code < 0 if not
    /**
      Validate expiration date
      @param $exp expiration formatted MMYY
      @return
       - 1 if ok
       - -1 if the argument is malformed
       - -2 if the month is smarch-y
       - -3 if the date is in the past
    */
    public function validExpiration($exp) 
    {
        // verify expiration format (MMYY)
        if( strlen($exp) != 4 || !ctype_digit($exp))
            return -1;
        // extract expiration parts (month, then year)
        $expireM = (int)substr($exp,0,2);
        $expireY = (int)substr($exp,2,2);
        // check month range
        if( $expireM < 1 || $expireM > 12)
            return -2;
        // get today's date
        $cardM = (int)date('n'); // Numeric representation of a month, without leading zeros (1 through 12)
        $cardY = (int)date('y'); // A two digit representation of a year (99 or 03)
        // check date
        if( $expireY < $cardY)
            return -3;
        if( $expireY == $cardY && $expireM < $cardM)
            return -3;
        // ok
        return 1;
    } 

    public function validateAmount($conf)
    {
        $amt = $conf->get('paycard_amount');
        $due = $conf->get("amtdue");
        $type = $conf->get("CacheCardType");
        $cashback = $conf->get('CacheCardCashBack');
        $balanceLimit = $conf->get('PaycardRetryBalanceLimit');
        if ($type == 'EBTFOOD') {
            $due = $conf->get('fsEligible');
        }
        if ($cashback > 0) $amt -= $cashback;
        if (!is_numeric($amt) || abs($amt) < 0.005) {
            return array(false, 'Enter a different amount');
        } elseif ($amt > 0 && $due < 0) {
            return array(false, 'Enter a negative amount');
        } elseif ($amt < 0 && $due > 0) {
            return array(false, 'Enter a positive amount');
        } elseif (($amt-$due)>0.005 && $type != 'DEBIT' && $type != 'EBTCASH') {
            return array(false, 'Cannot exceed amount due');
        } elseif (($amt-$due-0.005)>$cashback && ($type == 'DEBIT' || $type == 'EBTCASH')) {
            return array(false, 'Cannot exceed amount due plus cashback');
        } elseif ($balanceLimit > 0 && ($amt-$balanceLimit) > 0.005) {
            return array(false, 'Cannot exceed card balance');
        }
        return array(true, 'valid');
    }
}

