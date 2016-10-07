<?php

class MobileTransManager
{
    private $dbc;
    private $config;
    public function __construct($dbc, $config)
    {
        $this->dbc = $dbc;
        $this->config = $config;
    }

    public function setDB($dbc)
    {
        $this->dbc = $dbc;
    }

    public function getTransNo($emp, $reg)
    {
        $dbc = $this->dbc;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $getP = $dbc->prepare('SELECT trans_no FROM ' . $settings['MobileLaneDB'] . $dbc->sep() . 'MobileTrans WHERE emp_no=? AND register_no=?');
        $get = $dbc->getValue($getP, array($emp, $reg));
        if ($get !== false) {
            return $get;
        }
        $getP = $dbc->prepare('SELECT MAX(trans_no) FROM ' . $this->config->get('TRANS_DB') . $dbc->sep() . 'dtransactions WHERE emp_no=? AND register_no=?');
        $get = $dbc->getValue($getP, array($emp, $reg));
        if ($get !== false) {
            return $get+1;
        }

        return 1;
    }

    public function endTransaction($emp, $reg)
    {
        /**
          MobileTrans.pos_row_id is going into both
          dtransactions.pos_row_id AND dtransactions.trans_id
        */
        $model = new MobileTransModel($dbc);
        $cols = array_keys($model->getColumns());
        $cols = implode(',', $colums);
        $dt_cols = str_replace('pos_row_id', 'trans_id,pos_row_id', $cols);
        $mt_cols = $cols . ',pos_row_id';
        $settings = $this->config->get('PLUGIN_SETTINGS');

        $xfer = $dbc->prepare("
            INSERT INTO " . $this->config->get('TRANS_DB') . $dbc->sep() . "dtransactions
                ({$dt_cols})
            SELECT {$mt_cols}
            FROM " . $settings['MobileLaneDB'] . $dbc->sep() . "MobileTrans
            WHERE emp_no=?
                AND register_no=?");
        $dbc->execute($xfer, array($emp, $reg));

        $clearP = $dbc->prepare('
            DELETE FROM ' . $settings['MobileLaneDB'] . $dbc->sep() . 'MobileTrans
            WHERE emp_no=?
                AND register_no=?');
        $dbc->execute($clearP, array($emp, $reg));
    }
}

