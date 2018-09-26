<?php

namespace COREPOS\pos\lib\ReceiptBuilding\Messages;
use COREPOS\pos\lib\ReceiptLib;
use \CoreLocal;

class SurveyReceiptMessage extends ReceiptMessage
{
    public function select_condition()
    {
        return "SELECT 1";
    }

    public function message($val, $ref, $reprint=False)
    {
        if ($reprint) {
            return '';
        }

        return 'Still need survey template';
    }
}

