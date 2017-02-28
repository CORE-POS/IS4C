<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

namespace COREPOS\pos\lib\Tenders;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use \CoreLocal;

/**
  @class TenderModule
  Base class for modular tenders
*/
class TenderModule 
{

    protected $tender_code;
    protected $amount;

    protected $name_string = '';
    protected $change_type = 'CA';
    protected $change_string = 'Change';
    protected $min_limit = 0;
    protected $max_limit = 0;
    protected $ends_trans = true;

    /**
      Constructor
      @param $code two letter tender code
      @param $amt tender amount

      If you override this, be sure to call the
      parent constructor
    */
    public function __construct($code, $amt)
    {
        $this->tender_code = $code;
        $this->amount = $amt;

        $dbc = Database::pDataConnect();
        $query = "SELECT TenderID,TenderCode,TenderName,TenderType,
            ChangeMessage,MinAmount,MaxAmount,MaxRefund,";
        if (CoreLocal::get('NoCompat') != 1) {
            $tenderTable = $dbc->tableDefinition('tenders');
            $query .= isset($tenderTable['EndsTransaction']) ? '' : '1 AS ';
        }
        $query .= " EndsTransaction FROM
            tenders WHERE tendercode = ?";
        $prep = $dbc->prepare($query);
        $result = $dbc->execute($prep, array($this->tender_code));

        if ($dbc->numRows($result) > 0) {
            $row = $dbc->fetchRow($result);
            $this->name_string = $row['TenderName'];
            $this->change_type = $row['TenderType'];
            $this->change_string = $row['ChangeMessage'];
            $this->min_limit = $row['MinAmount'];
            $this->max_limit = $row['MaxAmount'];
            $this->ends_trans = $row['EndsTransaction'] ? true : false;
        }
    }

    /**
      Check for errors
      @return True or an error message string
    */
    public function errorCheck()
    {
        //force negative entered value when the total is negative.
        if (CoreLocal::get("amtdue") <0 && $this->amount >= 0) {
            $this->amount = -1 * $this->amount;
        }

        $clearButton = array(_('OK [clear]') => 'parseWrapper(\'CL\');');

        if (CoreLocal::get("LastID") == 0) {
            return DisplayLib::boxMsg(
                _("no transaction in progress"),
                '',
                false,
                $clearButton
            );
        } elseif (CoreLocal::get('refund') == 1) {
            CoreLocal::set('refund', 0);
            return DisplayLib::boxMsg(
                _("refund cannot apply to tender"),
                '',
                false,
                $clearButton
            );
        } else if ($this->amount > 99999.99) {
            return DisplayLib::boxMsg(
                _("tender amount of") . " " . $this->amount . "<br />" . _("exceeds allowable limit"),
                '',
                false,
                $clearButton
            );
        } else if (CoreLocal::get("ttlflag") == 0) {
            return DisplayLib::boxMsg(
                _("transaction must be totaled before tender can be accepted"),
                '',
                false,
                array(_('Total [subtotal]') => 'parseWrapper(\'TL\');$(\'#reginput\').focus();', _('Dimiss [clear]') => 'parseWrapper(\'CL\');')
            );
        } else if ($this->name_string === "") {
            return DisplayLib::inputUnknown();
        } elseif (CoreLocal::get('fntlflag') && CoreLocal::get('fsEligible') < 0 && abs($this->amount - CoreLocal::get('fsEligible')) < 0.005) {
            // not actually an error
            // if return tender exactly matches FS elgible return amount
            // pass through so the subsequent exact amount error
            // does not occur.
        } elseif (abs($this->amount - CoreLocal::get('amtdue')) > 0.005 && CoreLocal::get("amtdue") < 0 
                     && $this->amount !=0) {
            // the return tender needs to be exact because the transaction state can get weird.
            return DisplayLib::xboxMsg(
                _("return tender must be exact"),
                $clearButton
            );
        } elseif(CoreLocal::get("amtdue")>0 && $this->amount < 0) { 
            return DisplayLib::xboxMsg(
                _("Why are you using a negative number for a positive sale?"),
                $clearButton
            );
        } elseif (CoreLocal::get('TenderHardMinMax') && $this->amount > $this->max_limit) {
            return DisplayLib::boxMsg(
                "$" . $this->amount . " " . _("is greater than tender limit for") . " " . $this->name_string,
                '',
                false,
                $clearButton
            );
        }

        return true;
    }
    
    /**
      Set up state and redirect if needed
      @return True or a URL to redirect
    */
    public function preReqCheck()
    {
        if ($this->amount > $this->max_limit && CoreLocal::get("msgrepeat") == 0) {
            CoreLocal::set("boxMsg",
                "$" . $this->amount . " " . _("is greater than tender limit for") . " " . $this->name_string
            );
            CoreLocal::set('lastRepeat', 'confirmTenderAmount');
            CoreLocal::set('boxMsgButtons', array(
                _('Confirm [enter]') => '$(\'#reginput\').val(\'\');submitWrapper();',
                _('Cancel [clear]') => '$(\'#reginput\').val(\'CL\');submitWrapper();',
            ));

            return MiscLib::base_url().'gui-modules/boxMsg2.php';
        } else if (CoreLocal::get('msgrepeat') == 1 && CoreLocal::get('lastRepeat') == 'confirmTenderAmount') {
            CoreLocal::set('msgrepeat', 0);
            CoreLocal::set('lastRepeat', '');
        }

        if ($this->amount - CoreLocal::get("amtdue") > 0) {
            CoreLocal::set("change",$this->amount - CoreLocal::get("amtdue"));
            CoreLocal::set("ChangeType", $this->change_type);
        } else {
            CoreLocal::set("change",0);
        }

        return true;
    }

    /**
      Add tender to the transaction
    */
    public function add()
    {
        TransRecord::addRecord(array(
            'description' => $this->name_string,
            'trans_type' => 'T',
            'trans_subtype' => $this->tender_code,
            'total' => -1 * $this->amount,
        ));
    }

    /**
      What type should be used for change records associated with this tender.
      @return string tender code
    */
    public function changeType()
    {
        return $this->change_type;
    }

    /**
      What description should be used for change records associated with this tender
      @return string change description
    */
    public function changeMsg()
    {
        return $this->change_string;
    }

    /**
      Allow the tender to be used without specifying a total
      @return boolean
    */
    public function allowDefault()
    {
        return true;
    }

    public function endsTransaction()
    {
        return $this->ends_trans;
    }

    /**
      Value to use if no total is provided
      @return number
    */
    public function defaultTotal()
    {
        return CoreLocal::get('amtdue');
    }

    /**
      Prompt for the cashier when no total is provided
      @return string URL
    
      Typically this sets up session variables and returns
      the URL for boxMsg2.php.
    */
    public function defaultPrompt()
    {
        $amt = $this->DefaultTotal();
        CoreLocal::set('boxMsg',
            '<br />'
          . _('tender $') . sprintf('%.2f',$amt) . ' as ' . $this->name_string 
        );
        CoreLocal::set('strEntered', (100*$amt).$this->tender_code);
        CoreLocal::set('boxMsgButtons', array(
            _('Confirm [enter]') => '$(\'#reginput\').val(\'\');submitWrapper();',
            _('Cancel [clear]') => '$(\'#reginput\').val(\'CL\');submitWrapper();',
        ));

        return MiscLib::base_url().'gui-modules/boxMsg2.php?quiet=1';
    }

    /**
      Error message shown if tender cannot be used without
      specifying a total
      @return html string
    */
    public function disabledPrompt()
    {
        $clearButton = array(_('OK [clear]') => 'parseWrapper(\'CL\');');
        return DisplayLib::boxMsg(
            _('Amount required for ') . $this->name_string,
            '',
            false,
            $clearButton
        );
    }

    protected function frankingPrompt()
    {
        if (CoreLocal::get("enableFranking") != 1) {
            return parent::defaultPrompt();
        }

        CoreLocal::set('RepeatAgain', false);

        $ref = trim(CoreLocal::get("CashierNo"))."-"
            .trim(CoreLocal::get("laneno"))."-"
            .trim(CoreLocal::get("transno"));

        if ($this->amount === False) {
            $this->amount = $this->defaultTotal();
        }

        $msg = "<br />"._("insert")." ".$this->name_string.
            ' for $'.sprintf('%.2f',$this->amount) . '<br />';
        if (CoreLocal::get("LastEquityReference") == $ref) {
            $msg .= "<div style=\"background:#993300;color:#ffffff;
                margin:3px;padding: 3px;\">"
                . _('There was an equity sale on this transaction. Did it get
                endorsed yet?') . "</div>";
        }

        CoreLocal::set("boxMsg",$msg);
        CoreLocal::set('strEntered', (100*$this->amount).$this->tender_code);
        CoreLocal::set('boxMsgButtons', array(
            _('Endorse [enter]') => '$(\'#reginput\').val(\'\');submitWrapper();',
            _('Cancel [clear]') => '$(\'#reginput\').val(\'CL\');submitWrapper();',
        ));

        return MiscLib::base_url().'gui-modules/boxMsg2.php?endorse=check&endorseAmt='.$this->amount;
    }
}

