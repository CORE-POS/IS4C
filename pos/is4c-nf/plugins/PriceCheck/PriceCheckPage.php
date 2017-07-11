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

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\Scanning\DiscountType;
include_once(dirname(__FILE__).'/../../lib/AutoLoader.php');

class PriceCheckPage extends NoInputCorePage 
{
    private $upc = '';
    private $found = false;
    private $pricing = array(
        'sale'=>false,
        'actual_price'=>'',
        'memPrice'=>'',
        'description'=>'',
        'department'=>'',
        'regular_price'=>'',
    );

    function preprocess()
    {
        if (strtoupper(FormLib::get('reginput') == 'CL')) {
            // cancel
            $this->change_page($this->page_url."gui-modules/pos2.php");

            return false;
        } elseif (FormLib::get('reginput') != '' || FormLib::get('upc') != '') {
            // use reginput as UPC unless it's empty
            $this->upc = FormLib::get('reginput');
            if ($this->upc == '') {
                $this->upc = FormLib::get('upc');
            }
            $this->upc = str_pad($this->upc,13,'0',STR_PAD_LEFT);

            // lookup item info
            $row = $this->getItem();
            if ($row !== false) {
                $this->found = true;

                $this->formatPricing($row);
                $this->pricing['description'] = $row['description'];
                $this->pricing['department'] = $row['dept_name'];

                MiscLib::goodBeep();
            }

            // user hit enter and there is a valid UPC present
            if (FormLib::get('reginput', false) === '' && $this->found) {
                $qstr = '?reginput=' . urlencode($this->upc) . '&repeat=1';
                $this->change_page($this->page_url."gui-modules/pos2.php" . $qstr);

                return false;
            }
        }

        return true;
    }

    private function formatPricing($row)
    {
        $discounttype = MiscLib::nullwrap($row["discounttype"]);
        $DiscountObject = $this->getDiscountType($discounttype);

        if ($DiscountObject->isSale()) {
            $this->pricing['sale'] = true;
        }
        $info = $DiscountObject->priceInfo($row,1);
        $this->pricing['actual_price'] = sprintf('$%.2f%s',
            $info['unitPrice'],($row['scale']>0?' /lb':''));
        $this->pricing['regular_price'] = sprintf('$%.2f%s',
            $info['regPrice'],($row['scale']>0?' /lb':''));
        if ($info['memDiscount'] > 0) {
            $this->pricing['memPrice'] = sprintf('$%.2f%s',
                ($info['unitPrice']-$info['memDiscount']),
                ($row['scale']>0?' /lb':''));
        }
    }

    private function getDiscountType($discounttype)
    {
        return DiscountType::getObject($discounttype, $this->session);
    }

    private function getItem()
    {
        $dbc = Database::pDataConnect();
        $query = "select inUse,upc,description,normal_price,scale,deposit,
            qttyEnforced,department,local,cost,tax,foodstamp,discount,
            discounttype,specialpricemethod,special_price,groupprice,
            pricemethod,quantity,specialgroupprice,specialquantity,
            mixmatchcode,idEnforced,tareweight,d.dept_name
            from products, departments d where department = d.dept_no
            AND upc = ?";
        $prep = $dbc->prepare($query);
        $result = $dbc->getRow($prep, array($this->upc));

        return $result;
    }

    function head_content()
    {
        $this->default_parsewrapper_js();
        $this->scanner_scale_polling(true);
    }

    private function noUpcText()
    {
        $info = _("not a valid item");
        $inst = array(
            _("[scan] another item"),
            _("[clear] to cancel"),
        );
        
        return array($info, $inst);
    }

    private function upcText()
    {
        $info = $this->pricing['description'] . '<br />'
                . $this->pricing['department'] . '<br />'
                . _("Current Price") . ": " . $this->pricing['actual_price'] . '<br />'
                . _("Regular Price") . ": " . $this->pricing['regular_price'];
        if (!empty($this->pricing['memPrice'])) {
            $info .= "<br />(" . _("Member Price") . ": " . $this->pricing['memPrice'] . ")";
        }
        
        $inst = array(
            _("[scan] another item"),
            _("[enter] to ring this item"),
            _("[clear] to cancel"),
        );
        
        return array($info, $inst);
    }

    function body_content()
    {
        $this->add_onload_command("\$('#reginput').focus();\n");
        $info = _("price check");
        $inst = array(
            _("[scan] item"),
            _("[clear] to cancel"),
        );
        if (!empty($this->upc)) {
            if (!$this->found) {
                list($info, $inst) = $this->noUpcText();
                $this->upc = "";
                MiscLib::errorBeep();                
            } else {
                list($info, $inst) = $this->upcText();
            }
        }
        ?>
        <div class="baseHeight">
        <div class="coloredArea centeredDisplay">
        <span class="larger">
        <?php echo $info ?>
        </span><br />
        <form name="form" id="formlocal" method="post" 
            autocomplete="off" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <input type="text" name="reginput" tabindex="0" 
            onblur="$('#reginput').focus();" id="reginput" />
        <input type="hidden" name="upc" value="<?php echo $this->upc; ?>" />
        </form>
        <p>
        <span id="localmsg"><?php foreach($inst as $i) echo $i."<br />" ?></span>
        </p>
        </div>
        </div>
        <?php
    } // END true_body() FUNCTION

}

AutoLoader::dispatch();

