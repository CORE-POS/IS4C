<?php
namespace COREPOS\pos\lib\Tenders;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use \CoreLocal;

class NickelRoundingTender extends TenderModule
{
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

        $adjustment = 0;
        $lastDigit = (int)substr(CoreLocal::get('amtdue'), -1);
        switch ($lastDigit) {
            case 1:
            case 6:
                $adjustment = -0.01;
                break;
            case 2:
            case 7:
                $adjustment = -0.02;
                break;
            case 3:
            case 8:
                $adjustment = 0.02;
                break;
            case 4:
            case 9:
                $adjustment = 0.01;
                break;
        }

        if (abs($this->amount - (CoreLocal::get("amtdue") + $adjustment)) > 0.005) {
            CoreLocal::set("change",$this->amount - (CoreLocal::get("amtdue") + $adjustment));
            CoreLocal::set("ChangeType", $this->change_type);
            TransRecord::addRecord(array(
                'description' => 'NICKEL ROUND',
                'trans_type' => 'T',
                'trans_subtype' => 'NR',
                'total' => $adjustment,
            ));
            CoreLocal::set('amtdue', CoreLocal::get('amtdue') + $adjustment);
        } else {
            CoreLocal::set("change",0);
        }

        return true;
    }

}
