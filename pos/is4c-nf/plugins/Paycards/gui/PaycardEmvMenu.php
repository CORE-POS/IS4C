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

include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class PaycardEmvMenu extends NoInputCorePage 
{
    private $menu = array(
        'CC' => 'Credit',
        'DC' => 'Debit',
        'EBT' => 'EBT',
        'GIFT' => 'Gift',
    );
    private $clear_to_home = 1;
    
    function preprocess()
    {
        if (isset($_REQUEST["selectlist"])) {
            $parser = new PaycardDatacapParser();
            switch ($_REQUEST['selectlist']) {
                case 'CAADMIN':
                    $this->change_page('PaycardEmvCaAdmin.php');
                    return false;
                case 'CC':
                    $json = $parser->parse('DATACAPCC');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'DC':
                    $json = $parser->parse('DATACAPDC');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'EMV':
                    $json = $parser->parse('DATACAPEMV');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'EF':
                    $json = $parser->parse('DATACAPEF');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'EC':
                    $json = $parser->parse('DATACAPEC');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'GD':
                    $json = $parser->parse('DATACAPGD');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'PVEF':
                    $json = $parser->parse('PVDATACAPEF');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'PVEC':
                    $json = $parser->parse('PVDATACAPEC');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'PVGD':
                    $json = $parser->parse('PVDATACAPGD');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'ACGD':
                    $json = $parser->parse('ACDATACAPGD');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'AVGD':
                    $json = $parser->parse('AVDATACAPGD');
                    $this->change_page($json['main_frame']);
                    return false;
                case 'EBT':
                    $this->menu = array(
                        'EF' => 'Food Sale',
                        'EC' => 'Cash Sale',
                        'PVEF' => 'Food Balance',
                        'PVEC' => 'Cash Balance',
                    );
                    $this->clear_to_home = 0;
                    break;
                case 'GIFT':
                    $this->menu = array(
                        'GD' => 'Gift Sale',
                        'ACGD' => 'Activate Card',
                        'AVGD' => 'Reload Card',
                        'PVGD' => 'Check Balance',
                    );
                    $this->clear_to_home = 0;
                    break;
                case 'CL':
                default:
                    if (isset($_REQUEST['clear-to-home']) && $_REQUEST['clear-to-home']) {
                        $this->change_page(MiscLib::baseUrl() . 'gui-modules/pos2.php');
                        return false;
                    }
                    break;
            }
        }
        if (!isset($_REQUEST['selectlist']) || $_REQUEST['selectlist'] == 'CL' || $_REQUEST['selectlist'] === '') {
            if (CoreLocal::get('PaycardsDatacapMode') == 1) {
                $this->menu = array(
                    'EMV' => 'EMV Credit/Debit',
                    'EBT' => 'EBT',
                    'GIFT' => 'Gift',
                );
            } elseif (CoreLocal::get('PaycardsDatacapMode') == 2 || CoreLocal::get('PaycardsDatacapMode') == 3) {
                $this->menu = array(
                    'EMV' => 'EMV Credit/Debit',
                    'CAADMIN' => 'Admin Functions',
                );
            }
        }

        return true;
    }
    
    function head_content(){
        ?>
        <script type="text/javascript" src="../../../js/selectSubmit.js"></script>
        <?php
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
        $this->add_onload_command("\$('#selectlist').focus();\n");
    } // END head() FUNCTION

    function body_content() 
    {
        $stem = MiscLib::baseURL() . 'graphics/';
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored rounded">
        <span class="larger">process card transaction</span>
        <form name="selectform" method="post" id="selectform"
            action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <input type="hidden" name="clear-to-home" value="<?php echo $this->clear_to_home; ?>" />
        <?php if (CoreLocal::get('touchscreen')) { ?>
        <button type="button" class="pos-button coloredArea"
            onclick="scrollDown('#selectlist');">
            <img src="<?php echo $stem; ?>down.png" width="16" height="16" />
        </button>
        <?php } ?>
        <select id="selectlist" name="selectlist" size="5" style="width: 10em;"
            onblur="$('#selectlist').focus()">
        <?php
        $i = 0;
        foreach ($this->menu as $val => $label) {
            printf('<option %s value="%s">%s</option>',
                ($i == 0 ? 'selected' : ''), $val, $label);
            $i++;
        }
        ?>
        </select>
        <?php if (CoreLocal::get('touchscreen')) { ?>
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

if (basename(__FILE__) == basename($_SERVER['PHP_SELF']))
    new PaycardEmvMenu();
