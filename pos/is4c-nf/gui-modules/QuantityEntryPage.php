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
class QuantityEntryPage extends BasicPage 
{
	protected $box_color;
	protected $msg;

    const MODE_INTEGER = 0;
    const MODE_PRECISE = 1;

	function preprocess()
    {
		$this->box_color="coloredArea";
		$this->msg = _("quantity required");
        $mode = FormLib::get('qty-mode');
        if ($mode == self::MODE_PRECISE) {
            $this->msg = _('precision weight required');
        }

		if (!isset($_REQUEST['reginput'])) {
            return true;
        }

		$qtty = strtoupper(trim($_REQUEST["reginput"]));
		if ($qtty == "CL") {
            /**
              Clear cancels
            */
			CoreLocal::set("qttyvalid",0);
			CoreLocal::set("quantity",0);
			CoreLocal::set("msgrepeat",0);
			$this->change_page($this->page_url."gui-modules/pos2.php");
			return false;
		} elseif (is_numeric($qtty) && $qtty < 9999 && $qtty >= 0) {
            /**
              If it's a number, check error conditions.
              The number should always be an integer. In
              precision mode the number must be exactly three
              digits. This mode is for very light items
              that should be measured in thousands instead
              of hundreths. If the number is valid it's converted
              back to decimal in precision mode.
            */
            if ($qtty != ((int)$qtty)) {
                $this->box_color="errorColoredArea";
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
                $this->box_color="errorColoredArea";
                $this->msg = _('invalid precision weight')
                        . '<br />'
                        . _('enter three digits');
                return true;
            } elseif ($mode == self::MODE_PRECISE) {
                $qtty /= 1000.00;
                $qtty = round($qtty, 3);
            }

			$input_string = FormLib::get('entered-item');
			$plu = '';
            $prefix = '';
            // trim numeric characters from right side of
            // input. what remains, if anything, should be
            // prefixes to the UPC
            $matched = preg_match('/^(\D*)(\d+)$/', $input_string, $matches);
            if ($matched) {
                $prefix = $matches[1];
                $plu = $matches[2];
            } else {
                $plu = $input_string;
            }
			CoreLocal::set("qttyvalid",1);
			CoreLocal::set("strRemembered", $prefix . $qtty . '*' . $plu);
			CoreLocal::set("msgrepeat",1);
			$this->change_page($this->page_url."gui-modules/pos2.php");

			return false;
		}

		$this->box_color="errorColoredArea";
		$this->msg = _("invalid quantity");
        if ($mode == self::MODE_PRECISE) {
            $this->msg = _('invalid precision weight');
        }

		return true;
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

        $mode = FormLib::get('qty-mode', 0);
        $this->add_onload_command("formAdd('#formlocal','qty-mode','{$mode}');\n");
        $item = FormLib::get('entered-item', CoreLocal::get('strEntered'));
        $this->add_onload_command("formAdd('#formlocal','entered-item','{$item}');\n");

		?>
		<div class="baseHeight">
		<div class="<?php echo $this->box_color; ?> centeredDisplay">
		<span class="larger">
		<?php echo $this->msg ?>
		</span><br />
		<p>
		<?php echo _("enter quantity or clear to cancel"); ?>
		</p> 
		</div>
		</div>

		<?php
		CoreLocal::set("msgrepeat",2);
		CoreLocal::set("item",CoreLocal::get("strEntered"));
		UdpComm::udpSend('errorBeep');
		echo "<div id=\"footer\">";
		echo DisplayLib::printfooter();
		echo "</div>";
	} // END true_body() FUNCTION
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
	new QuantityEntryPage();
}

?>
