<?php

class SigCapture
{
    public function __construct($conf)
    {
        $this->conf = $conf;
    }

    public function save($file, $dbc)
    {
        $bmp = file_get_contents($file);
        $format = 'BMP';
        $imgContent = $bmp;

        $capQ = 'INSERT INTO CapturedSignature
                    (tdate, emp_no, register_no, trans_no,
                     trans_id, filetype, filecontents)
                 VALUES
                    (?, ?, ?, ?,
                     ?, ?, ?)';
        $capP = $dbc->prepare($capQ);
        $args = array(
            date('Y-m-d H:i:s'),
            $this->conf->get('CashierNo'),
            $this->conf->get('laneno'),
            $this->conf->get('transno'),
            $this->conf->get('paycard_id'),
            $format,
            $imgContent,
        );
        $dbc->execute($capP, $args);

        unlink($file);
    } 

    private function isCredit()
    {
        $ret = ($this->conf->get('CacheCardType') == 'CREDIT' || $this->conf->get('CacheCardType') == '') ? true : false;
        if ($this->conf->get('paycard_type') == PaycardLib::PAYCARD_TYPE_GIFT) {
            $ret = false;
        } elseif ($this->conf->get('EmvSignature') === true) {
            $ret = true;
        }

        return $ret;
    }

    public function required()
    {
        // Signature Capture support
        // If:
        //   a) enabled
        //   b) a Credit transaction
        //   c) Over limit threshold OR a return OR a recurring charge
        $isCredit = $this->isCredit();
        $needSig = (
            $this->conf->get('paycard_amount') > $this->conf->get('CCSigLimit') 
            || $this->conf->get('paycard_amount') < 0
            || $this->conf->get('paycard_recurring')
            ) ? true : false;
        $isVoid = ($this->conf->get('paycard_mode') == PaycardLib::PAYCARD_MODE_VOID) ? true : false;

        return ($this->conf->get("PaycardsSigCapture") == 1 && $isCredit && $needSig && !$isVoid); 
    }
}

