<?php

use COREPOS\pos\lib\Tenders\TenderModule;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;

class MBTender extends TenderModule 
{
    private function getEligibleAmount()
    {
        // TODO
        // depends on spec details, obviously
        return 0;
    }

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        $clearButton = array(_('OK [clear]') => 'parseWrapper(\'CL\');');
        if ($this->getEligibleAmount() <= 0.005) {
            return DisplayLib::boxMsg(
                _("no market bucks eligible items"),
                '',
                false,
                $clearButton
            );
        }

        return true;
    }

    /**
      Set up state and redirect if needed. This
      is typically used to insert a confirmation screen
      @return True or a URL to redirect

      I imagine these can't be overtendered - i.e., if
      the voucher amount is more than the eligible amount
      it'll be redeemed for the eligible amount and excess
      value is lost. This only shows a confirmation screen
      if the voucher will be redeemed for less than full
      value.
    */
    public function preReqCheck()
    {
        $eligible = $this->getEligibleAmount();
        if (($this->amount - $eligible) > 0.005 && CoreLocal::get('msgrepeat') == 0) {
            CoreLocal::set("boxMsg", sprintf('Redeem for $%.2f', $eligible));
            CoreLocal::set('lastRepeat', 'confirmMB');
            CoreLocal::set('boxMsgButtons', array(
                _('Confirm [enter]') => '$(\'#reginput\').val(\'\');submitWrapper();',
                _('Cancel [clear]') => '$(\'#reginput\').val(\'CL\');submitWrapper();',
            ));

            return MiscLib::baseURL().'gui-modules/boxMsg2.php';
        } elseif (CoreLocal::get('msgrepeat') == 1 && CoreLocal::get('lastRepeat') == 'confirmMB') {
            CoreLocal::set('msgrepeat', 0);
            CoreLocal::set('lastRepeat', '');
        }

        if (($this->amount - $eligible) > 0.005) {
            $this->amount = $eligible;
        }
        
        return true;
    }

    /**
      Allowing a tender w/o value probably doesn't make sense here 
      @return boolean
    */
    public function allowDefault()
    {
        return false;
    }
}

