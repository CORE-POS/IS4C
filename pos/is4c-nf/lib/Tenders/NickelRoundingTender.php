<?php
namespace COREPOS\pos\lib\Tenders;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use \CoreLocal;

/**
  Cash tender that rounds the amount due to the nearest nickel and records
  the adjustment as a trans_type 'T', trans_subtype 'NR' line.

  Setup:
  - In Fannie, add an NR tender to core_op.tenders so the rounding lines
    have a name in tender reports
  - On the lane, set NR to DisabledTender in the extra tender settings so
    cashiers cannot key it as a tender
  - Map the cash tender to NickelRoundingTender
*/
class NickelRoundingTender extends TenderModule
{
    /**
      errorCheck() calls parent::errorCheck(), and the plain TenderModule
      running first would refuse the rounded return tender
    */
    public static function includesBaseChecks()
    {
        return true;
    }

    /**
      Check for errors. A return tender must equal the nickel-rounded
      amount; the parent's exact-return check only knows the unrounded one.
      @return True or an error message string
    */
    public function errorCheck()
    {
        $amtdue = CoreLocal::get('amtdue');
        $adjustment = $this->nickelAdjustment($amtdue);
        if ($amtdue < 0 && $adjustment != 0 && $this->amount != 0) {
            $tendered = -1 * abs($this->amount);
            $this->amount = $amtdue; // let the parent's other checks run against the unrounded total
            $ret = parent::errorCheck();
            $this->amount = $tendered;
            if ($ret === true && abs($tendered - ($amtdue + $adjustment)) > 0.005) {
                return DisplayLib::xboxMsg(
                    _("return tender must be exact") . ": " . number_format(abs($amtdue + $adjustment), 2),
                    array(_('OK [clear]') => 'parseWrapper(\'CL\');')
                );
            }

            return $ret;
        }

        return parent::errorCheck();
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

        // Rounds on whichever tender this is, so in a split payment (cash then card) the first cash tender rounds the whole total
        $adjustment = $this->nickelAdjustment(CoreLocal::get('amtdue'));

        /* If there is an adjustment to make, make it.
         * Allow for the case of the amount tendered being within the amount-of-adjustment
         *  of the amount due.
         */
        if ((abs($this->amount - (CoreLocal::get("amtdue") + $adjustment)) > 0.005) ||
            ((abs($this->amount - (CoreLocal::get('amtdue') + $adjustment)) < 0.005) && ($adjustment != 0))  // float == misses exact tenders
           )
        {
            CoreLocal::set("change", round($this->amount - (CoreLocal::get("amtdue") + $adjustment), 2));  // round to cents
            CoreLocal::set("ChangeType", $this->change_type);
            TransRecord::addRecord(array(
                'description' => 'NICKEL ROUND',
                'trans_type' => 'T',
                'trans_subtype' => 'NR',
                'total' => $adjustment,
            ));
            CoreLocal::set('amtdue', round(CoreLocal::get('amtdue') + $adjustment, 2));  // round to cents
        } else {
            CoreLocal::set("change",0);
        }

        return true;
    }

    /**
      Adjustment that brings an amount due to the nearest nickel
      @param $amtdue amount due (negative for a refund)
      @return adjustment in dollars
    */
    private function nickelAdjustment($amtdue)
    {
        $adjustment = 0;
        $lastDigit = (int)substr(number_format($amtdue,2), -1);
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

        /* For a Refund reverse the adjustment. */
        if ($adjustment != 0 && $amtdue < 0) {
            $adjustment = ($adjustment * -1);
        }

        return $adjustment;
    }
}
