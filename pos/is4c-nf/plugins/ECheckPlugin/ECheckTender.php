<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of IT CORE.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\Tenders\TenderModule;

/**
  @class ECheckTender
  Tender module for handling both paper
  and electronic checks
*/
class ECheckTender extends TenderModule 
{

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        $clearButton = array('OK [clear]' => 'parseWrapper(\'CL\');');
        if ( (CoreLocal::get("isMember") != 0 || CoreLocal::get('isStaff') != 0) && (($this->amount - CoreLocal::get("amtdue") - 0.005) > CoreLocal::get("dollarOver")) && (CoreLocal::get("cashOverLimit") == 1)) {
            return DisplayLib::boxMsg(
                _("member check tender cannot exceed total purchase by over $") . CoreLocal::get("dollarOver"),
                '',
                false,
                $clearButton
            );
        } else if( CoreLocal::get("isMember") == 0 && CoreLocal::get('isStaff') == 0 && ($this->amount - CoreLocal::get("amtdue") - 0.005) > 0) { 
            return DisplayLib::xboxMsg(_('non-member check tender cannot exceed total purchase'), $clearButton);
        }

        return true;
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        /**
          First prompt: choose check type
        */
        if (CoreLocal::get('msgrepeat') == 0) {
            CoreLocal::set('strEntered', ($this->amount*100) . $this->tender_code);
            CoreLocal::set('lastRepeat', 'echeckVerifyType');
            $plugin_info = new ECheckPlugin();
            return $plugin_info->pluginUrl() . '/ECheckVerifyPage.php?amount='.$this->amount;;
        } else if (CoreLocal::get('msgrepeat') == 1 && CoreLocal::get('lastRepeat') == 'echeckVerifyType') {
            CoreLocal::set('msgrepeat', 0);
            CoreLocal::set('lastRepeat', '');
        }

        /**
          If paper check, endorsing prompt
        */
        if (($this->tender_code == 'CK' || $this->tender_code == 'TC') && CoreLocal::get('enableFranking') == 1) {
            if (CoreLocal::get('msgrepeat') == 0) {
                CoreLocal::set('lastRepeat', 'echeckEndorse');
                return $this->endorsing();
            } else if (CoreLocal::get('msgrepeat') == 1 && CoreLocal::get('lastRepeat') == 'echeckEndorse') {
                CoreLocal::set('msgrepeat', 0);
                CoreLocal::set('lastRepeat', '');
            }
        }

        return true;
    }

    /**
      Setup session data & prompt strings
      for check endorsing
    */
    protected function endorsing()
    {
        $ref = trim(CoreLocal::get("CashierNo"))."-"
            .trim(CoreLocal::get("laneno"))."-"
            .trim(CoreLocal::get("transno"));

        if ($this->amount === False) {
            $this->amount = $this->defaultTotal();
        }

        $msg = "<br />"._("insert")." ".$this->name_string.
            ' for $'.sprintf('%.2f',$this->amount).
            "<br />"._("press enter to endorse");
        $msg .= "<p><font size='-1'>"._("clear to cancel")."</font></p>";
        if (CoreLocal::get("LastEquityReference") == $ref) {
            $msg .= "<div style=\"background:#993300;color:#ffffff;
                margin:3px;padding: 3px;\">
                There was an equity sale on this transaction. Did it get
                endorsed yet?</div>";
        }

        CoreLocal::set("boxMsg",$msg);
        CoreLocal::set('strEntered', (100*$this->amount).$this->tender_code);

        return MiscLib::base_url().'gui-modules/boxMsg2.php?endorse=check&endorseAmt='.$this->amount;
    }

}

