<?php

use COREPOS\pos\lib\ReceiptBuilding\Messages\ReceiptMessage;
use COREPOS\pos\lib\ReceiptLib;

/**
    Attach the market bucks info to a receipt using a ReceiptMessage
*/
class MBReceiptMessage extends ReceiptMessage
{
    /**
        Outer processing is basically going to do

            SELECT select_condition() FROM current_transaction

        and add the receipt message if the value is non-zero
        It's not strictly necessary to calculate the exact amount
        that should be issued here but it'll save a query if you can
    */
    public function select_condition()
    {
        return "SUM(CASE WHEN transaction_should_qualify_for_mb THEN non_zero_value ELSE 0 END)";
    }

    /**
      Print the actual message
      @param $val [number] value returned by select_condition()
      @param $ref [string] employee-register-transaction
      @param $reprint [boolean]

      If the issue amount can't be calculated in select_condition(), use $ref
      to get more information and come up with a correct $val.
    */
    public function message($val, $ref, $reprint=False)
    {
        if ($val <= 0) return '';
        
        $slip = ReceiptLib::centerString("................................................")."\n\n";
        $slip .= ReceiptLib::centerString("( C U S T O M E R   C O P Y )")."\n";
        $slip .= ReceiptLib::biggerFont("Market Bucks issued")."\n\n";
        $slip .= ReceiptLib::biggerFont(sprintf("Amount \$%.2f",$val))."\n\n";

        if ( CoreLocal::get("fname") != "" && CoreLocal::get("lname") != ""){
            $slip .= "Name: ".CoreLocal::get("fname")." ".CoreLocal::get("lname")."\n\n";
        } else {
            $slip .= "Name: ____________________________________________\n\n";
        }
        $slip .= "Ph #: ____________________________________________\n\n";

        $slip .= "\n\n";
        $code39 = floor(100*$val) . 'MB';
        $slip .= ReceiptLib::code39($code39, true) . "\n\n";

        $slip .= " * no cash back on bonus voucher purchases\n";
        $slip .= " * change amount is not transferable to\n   another voucher\n";
        $slip .= " * expires " . date('F j, Y', strtotime('1 month from now')) . "\n";
        $slip .= ReceiptLib::centerString("................................................")."\n";

        return $slip;
    }

    public $paper_only = true;
}

