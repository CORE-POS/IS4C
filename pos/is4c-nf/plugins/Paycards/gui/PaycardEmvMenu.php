<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\gui\NoInputCorePage;
if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardEmvMenu extends NoInputCorePage 
{
    private $menu = array(
        'CC' => 'Credit',
        'DC' => 'Debit',
        'EBT' => 'EBT',
        'GIFT' => 'Gift',
    );
    private $clearToHome = 1;
    
    function preprocess()
    {
        $this->conf = new PaycardConf();
        $choice = FormLib::get('selectlist', false);
        if ($choice !== false) {
            $parser = new PaycardDatacapParser();
            switch ($choice) {
                case 'CAADMIN':
                    $this->change_page('PaycardEmvCaAdmin.php');
                    return false;
                case 'CC':
                case 'DC':
                case 'EMV':
                case 'EF':
                case 'EC':
                case 'GD':
                    $json = $parser->parse('DATACAP' . $choice);
                    $this->change_page($json['main_frame']);
                    return false;
                case 'PVEF':
                case 'PVEC':
                case 'PVGD':
                    $json = $parser->parse('PVDATACAP' . substr($choice, -2));
                    $this->change_page($json['main_frame']);
                case 'ACGD':
                case 'AVGD':
                    $json = $parser->parse(substr($choice,0,2) . 'DATACAPGD');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'EBT':
                    $this->menu = array(
                        'EF' => 'Food Sale',
                        'EC' => 'Cash Sale',
                        'PVEF' => 'Food Balance',
                        'PVEC' => 'Cash Balance',
                    );
                    $this->clearToHome = 0;
                    break;
                case 'GIFT':
                    $this->menu = array(
                        'GD' => 'Gift Sale',
                        'ACGD' => 'Activate Card',
                        'AVGD' => 'Reload Card',
                        'PVGD' => 'Check Balance',
                    );
                    $this->clearToHome = 0;
                    break;
                case 'CL':
                default:
                    if (FormLib::get('clear-to-home')) {
                        $this->change_page(MiscLib::baseUrl() . 'gui-modules/pos2.php');
                        return false;
                    }
                    break;
            }
        }
        if ($choice === false || $choice === 'CL' || $choice === '') {
            if ($this->conf->get('PaycardsDatacapMode') == 1) {
                $this->menu = array(
                    'EMV' => 'EMV Credit/Debit',
                    'CC' => 'Credit only',
                    'EBT' => 'EBT',
                    'GIFT' => 'Gift',
                );
            } elseif ($this->conf->get('PaycardsDatacapMode') == 2 || $this->conf->get('PaycardsDatacapMode') == 3) {
                $this->menu = array(
                    'EMV' => 'EMV Credit/Debit',
                    'CAADMIN' => 'Admin Functions',
                );
            }
        }

        return true;
    }
    
    function head_content()
    {
        ?>
        <script type="text/javascript" src="../../../js/selectSubmit.js"></script>
        <?php
        $this->addOnloadCommand("selectSubmit('#selectlist', '#selectform')\n");
        $this->addOnloadCommand("\$('#selectlist').focus();\n");
    } // END head() FUNCTION

    function body_content() 
    {
        $stem = MiscLib::baseURL() . 'graphics/';
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger">process card transaction</span>
        <form name="selectform" method="post" id="selectform"
            action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <input type="hidden" name="clear-to-home" value="<?php echo $this->clearToHome; ?>" />
        <?php if ($this->conf->get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollDown('#selectlist');">
            <img src="<?php echo $stem; ?>down.png" width="16" height="16" />
        </button>
        <?php } ?>
        <select id="selectlist" name="selectlist" size="5" style="width: 10em;"
            onblur="$('#selectlist').focus()">
        <?php
        $first =true;
        foreach ($this->menu as $val => $label) {
            printf('<option %s value="%s">%s</option>',
                ($first ? 'selected' : ''), $val, $label);
            $first = false;
        }
        ?>
        </select>
        <?php if ($this->conf->get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollUp('#selectlist');">
            <img src="<?php echo $stem; ?>up.png" width="16" height="16" />
        </button>
        <?php } ?>
        <p>
            <button class="pos-button" type="submit">Select [enter]</button>
            <button class="pos-button" type="submit" onclick="$('#selectlist').val('');">
                Cancel [clear]
            </button>
        </p>
        </div>
        </form>
        </div>
        <?php
    } // END body_content() FUNCTION
}

AutoLoader::dispatch();

