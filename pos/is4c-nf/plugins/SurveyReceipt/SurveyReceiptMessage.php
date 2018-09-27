<?php

use COREPOS\pos\lib\ReceiptLib;
use COREPOS\pos\lib\ReceiptBuilding\Messages\ReceiptMessage;

class SurveyReceiptMessage extends ReceiptMessage
{
    public function select_condition()
    {
        return "1";
    }

    public function message($val, $ref, $reprint=False)
    {
        if ($reprint) {
            return '';
        }
        
        $PRINT = $this->printHandler;
        $receipt = '';

        $receipt .= $PRINT->TextStyle(true, false, true);
        $receipt .= $PRINT->centerString('$5 for your thoughts!') . "\n";
        $receipt .= $PRINT->TextStyle(true);
        $receipt .= $PRINT->centerString("Tell us about today\'s shopping trip.") . "\n";
        $receipt .= $PRINT->centerString('Visit '
                . $PRINT->TextStyle(true, true)
                . 'coopslisten.smg.com'
                . $PRINT->TextStyle(true, false)
        ) . "\n";
        $receipt .= $PRINT->centerString("within the next 3 days for") . "\n";
        $receipt .= $PRINT->TextStyle(true, true);
        $receipt .= $PRINT->centerString('$5 off your next purchase of $25+') . "\n";
        $receipt .= $PRINT->TextStyle(true, false);
        $receipt .= $PRINT->centerString(str_repeat('*', 40)) . "\n";

        $col1 = array('Survey Code: '
                . $PRINT->TextStyle(true, true)
                . CoreLocal::get('SurveyCode')
                . $PRINT->TextStyle(true, false)
        );
        $col1[] = 'Date: '
                . $PRINT->TextStyle(true, true)
                . date('m/d/Y')
                . $PRINT->TextStyle(true, false);
        $col2 = array('Transaction Code: '
                . $PRINT->TextStyle(true, true)
                . $ref
                . $PRINT->TextStyle(true, false)
        );
        $col2[] = 'Time: '
                . $PRINT->TextStyle(true, true)
                . date('g:ia')
                . $PRINT->TextStyle(true, false);
        $receipt .= PrintLib::twoColumns($col1, $col2) . "\n";
        $receipt .= $PRINT->centerString(str_repeat('*', 40)) . "\n";
        $receipt .= $PRINT->TextStyle(true, true);
        $receipt .= $PRINT->centerString('Save this receipt!') . "\n";
        $receipt .= $PRINT->TextStyle(true, false);
        $receipt .= $PRINT->centerString('Write down your post-survey validation code and') . "\n";
        $receipt .= $PRINT->centerString('redeem within 30 days of original purchase') . "\n";
        $receipt .= "\n";
        $receipt .= $PRINT->centerString('Validation Code: __________________') . "\n";
        $receipt .= "\n";

        return $receipt;
    }
}

