<?php

class PaycardConf
{
    public function get($key)
    {
        return CoreLocal::get($key);
    }

    public function set($key, $val)
    {
        return CoreLocal::set($key, $val);
    }

    public function reset()
    {
        $this->set("paycard_manual",0);
        $this->set("paycard_amount",0.00);
        $this->set("paycard_mode",0);
        $this->set("paycard_id",0);
        $this->set("paycard_PAN",'');
        $this->set("paycard_exp",'');
        $this->set("paycard_name",'Customer');
        $this->set("paycard_tr1",false);
        $this->set("paycard_tr2",false);
        $this->set("paycard_tr3",false);
        $this->set("paycard_type",0);
        $this->set("paycard_issuer",'Unknown');
        $this->set("paycard_response",array());
        $this->set("paycard_trans",'');
        $this->set('PaycardRetryBalanceLimit', 0);
        $this->set('EmvSignature', false);
        $this->set("paycard_recurring", false);
    }

    /**
      Clear card data variables from session

      <b>Storing card data in session is
      not recommended</b>.
    */
    public function wipePAN()
    {
        $this->set("paycard_tr1",false);
        $this->set("paycard_tr2",false);
        $this->set("paycard_tr3",false);
        $this->set("paycard_PAN",'');
        $this->set("paycard_exp",'');
    }
}
