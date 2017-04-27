<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

use COREPOS\pos\lib\gui\BasicCorePage;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\UdpComm;

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

/**
  @class QuantityEntryPage
  This page is for keying in quantities when an
  item is configured to require a manually keyed
  quantity.

  Two GET arguments are expected:
  - "entered-item" is what the cashier entered. This
    may be simply a UPC but can include prefixes
    if necessary. For example, prefix "RF" on refunds.
  - "qty-mode" see MODE_INTEGER, MODE_PRECISE

  MODE_INTEGER (0) is for items that are not sold by
  weight. Fractional or decimal quantities are not
  permitted. The value entered is exactly what is 
  used for quantity.

  MODE_PRECISE (1) is for items that are sold by
  weight but are too light to measure accurately with
  the regular scanner-scale. This value must be
  exactly three digits and will be divided by 1000.
  That is, entering "123" gives an effective quantity
  of 0.123. This is only appropriate for items that
  always weigh less than 1.0.
*/
class QuantityEntryPage extends BasicCorePage 
{
    protected $boxColor;
    protected $msg;

    const MODE_INTEGER = 0;
    const MODE_PRECISE = 1;
    const MODE_VERBATIM = 2;

    private function getPrefixes($input)
    {
        $plu = $input;
        $prefix = '';
        // trim numeric characters from right side of
        // input. what remains, if anything, should be
        // prefixes to the UPC
        $matched = preg_match('/^(\D*)(\d+)$/', $input, $matches);
        if ($matched) {
            $prefix = $matches[1];
            $plu = $matches[2];
        }

        return array($plu, $prefix);
    }

    /**
      Transpose $mode based on ManualWeightMode
    */
    private function refineMode($mode)
    {
        if ($mode == self::MODE_PRECISE) {
            return $this->session->get('ManualWeightMode') == 1 ? self::MODE_VERBATIM : self::MODE_PRECISE;
        }

        return $mode;
    }

    function preprocess()
    {
        $this->boxColor="coloredArea";
        $this->msg = _("quantity required");
        $mode = $this->refineMode($this->form->tryGet('qty-mode'));
        if ($mode == self::MODE_PRECISE) {
            $this->msg = _('precision weight required');
        }

        $qtty = strtoupper(trim($this->form->tryGet('reginput')));
        if ($qtty == "CL") {
            /**
              Clear cancels
            */
            $this->session->set("qttyvalid",0);
            $this->session->set("quantity",0);
            $this->change_page($this->page_url."gui-modules/pos2.php");
            return false;
        } elseif (is_numeric($qtty) && $qtty < 9999 && $qtty >= 0) {
            $qtty = $this->validateQty($mode, $qtty);
            if ($qtty === true) {
                return true;
            }

            $input = FormLib::get('entered-item');
            list($plu, $prefix) = $this->getPrefixes($input);
            $this->session->set("qttyvalid",1);
            $inp = $prefix . $qtty . '*' . $plu;
            $this->change_page(
                $this->page_url
                . "gui-modules/pos2.php"
                . '?reginput=' . $inp
                . '&repeat=1');

            return false;
        } elseif ($qtty !== '') {
            $this->boxColor="errorColoredArea";
            $this->msg = _("invalid quantity");
            if ($mode == self::MODE_PRECISE) {
                $this->msg = _('invalid precision weight');
            }
        }

        return true;
    }

    private function validateQty($mode, $qtty)
    {
        /**
          If it's a number, check error conditions.
          The number should always be an integer. In
          precision mode the number must be exactly three
          digits. This mode is for very light items
          that should be measured in thousands instead
          of hundreths. If the number is valid it's converted
          back to decimal in precision mode.
        */
        if ($mode == self::MODE_VERBATIM && !is_numeric($qtty)) {
            $this->msg = _('invalid quantity<br />enter number');
            return true;
        } elseif ($mode != self::MODE_VERBATIM && $qtty != ((int)$qtty)) {
            $this->boxColor="errorColoredArea";
            if ($mode == self::MODE_PRECISE) {
                $this->msg = _("invalid precision weight") 
                        . '<br />'
                        . _('enter three digits');
            } else {
                $this->msg = _("invalid quantity") 
                        . '<br />'
                        . _('enter whole number');
            }

            return true;
        } elseif ($mode == self::MODE_PRECISE && strlen($qtty) != 3) {
            $this->boxColor="errorColoredArea";
            $this->msg = _('invalid precision weight')
                    . '<br />'
                    . _('enter three digits');
            return true;
        } elseif ($mode == self::MODE_PRECISE) {
            $qtty /= 1000.00;
            $qtty = round($qtty, 3);
        }

        return $qtty;
    }

    function head_content()
    {
        $this->noscan_parsewrapper_js();
        echo '<script type="text/javascript" src="../js/formAdd.js"></script>';
    }

    function body_content()
    {
        $this->input_header();
        echo DisplayLib::printheaderb();

        $mode = $this->form->tryGet('qty-mode', 0);
        $this->addOnloadCommand("formAdd('#formlocal','qty-mode','{$mode}');\n");
        $item = $this->form->tryGet('entered-item', $this->session->get('strEntered'));
        $this->addOnloadCommand("formAdd('#formlocal','entered-item','{$item}');\n");

        ?>
        <div class="baseHeight">
        <div class="<?php echo $this->boxColor; ?> centeredDisplay">
        <span class="larger">
        <?php echo $this->msg ?>
        </span><br />
        <p>
        <?php echo _("enter quantity or clear to cancel"); ?>
        </p> 
        </div>
        </div>

        <?php
        UdpComm::udpSend('errorBeep');
        echo "<div id=\"footer\">";
        echo DisplayLib::printfooter();
        echo "</div>";
    } // END true_body() FUNCTION

    public function unitTest($phpunit)
    {
        $phpunit->assertEquals(true, $this->validateQty(self::MODE_PRECISE, 1.5));
        $phpunit->assertEquals(true, $this->validateQty(self::MODE_INTEGER, 1.5));
        $phpunit->assertEquals(true, $this->validateQty(self::MODE_PRECISE, 10));
        $phpunit->assertEquals(true, $this->validateQty(self::MODE_PRECISE, 1000));
        $phpunit->assertEquals(0.100, $this->validateQty(self::MODE_PRECISE, 100));
        $phpunit->assertEquals(array('1234', 'RF'), $this->getPrefixes('RF1234'));
    }
}

AutoLoader::dispatch();

