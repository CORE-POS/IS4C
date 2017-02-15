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

include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class PriceOverride extends NoInputCorePage {

    private $itemDescription = '';
    private $price = '';
    private $scale = 0;

    function preprocess()
    {
        $lineID = CoreLocal::get("currentid");
        $dbc = Database::tDataConnect();
        
        $query = "SELECT description,unitPrice,department,scale FROM localtemptrans
            WHERE trans_type IN ('I','D') AND upc <> '0'
            AND trans_id=".((int)$lineID);
        $res = $dbc->query($query);
        if ($dbc->numRows($res)==0){
            // current record cannot be repriced
            $this->change_page($this->page_url."gui-modules/pos2.php");
            return false;
        }
        $row = $dbc->fetchRow($res);
        $this->itemDescription = $row['description'];
        $this->price = sprintf('$%.2f',$row['unitPrice']);
        $this->scale = $row['scale'];

        try {
            $input = strtoupper($this->form->reginput);

            if ($input == "CL"){
                if ($this->price == "$0.00"){
                    $this->markZeroRecord($lineID);
                }
                // override canceled; go home
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return False;
            } elseif (is_numeric($input) && $input != 0){
                $this->rePrice($input, $lineID, $this->isBottleReturn($row['department']));
                $this->change_page($this->page_url."gui-modules/pos2.php");
                return false;
            }
        } catch (Exception $ex) {}

        return True;
    }

    private function markZeroRecord($lineID)
    {
        $dbc = Database::tDataConnect();
        $query = sprintf("UPDATE localtemptrans SET trans_type='L',
                    trans_subtype='OG',charflag='PO',total=0
                    WHERE trans_id=".(int)$lineID);
        $res = $dbc->query($query);

        return $res === false ? false : true;
    }

    private function isBottleReturn($dept)
    {
        if ($dept == CoreLocal::get("BottleReturnDept")) {
            return true;
        }

        $deptmods = CoreLocal::get('SpecialDeptMap');
        if (is_array($deptmods) && isset($deptmods[$row['department']])){
            foreach($deptmods[$row['department']] as $mod){
                if ($mod === 'BottleReturnDept') {
                    return true;
                }
            }
        }
        return false;
    }

    private function rePrice($input, $lineID, $negate)
    {
        $dbc = Database::tDataConnect();
        $cents = 0;
        $dollars = 0;
        if (strlen($input)==1 || strlen($input)==2) {
            $cents = $input;
        } else {
            $cents = substr($input,-2);
            $dollars = substr($input,0,strlen($input)-2);
        }
        $ttl = ((int)$dollars) + ((int)$cents / 100.0);
        $ttl = number_format($ttl,2);
        if ($negate) {
            $ttl = $ttl * -1;
        }
            
        $query = sprintf("UPDATE localtemptrans SET unitPrice=%.2f, regPrice=%.2f,
            total = quantity*%.2f, charflag='PO'
            WHERE trans_id=%d",$ttl,$ttl,$ttl,$lineID);
        $res = $dbc->query($query);    

        return $res === false ? false : true;
    }
    
    function body_content() 
    {
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored">
        <span class="larger"><?php echo _('enter purchase price'); ?></span>
        <form name="overrideform" method="post" 
            id="overrideform" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <input type="text" id="reginput" name='reginput' tabindex="0" onblur="$('#reginput').focus()" />
        </form>
        <span><?php echo $this->itemDescription; ?> - <?php echo $this->price; ?>
            <?php echo ($this->scale ? ' /lb' : 'each'); ?></span>
        <p>
        <span class="smaller"><?php echo _('[clear] to cancel'); ?></span>
        </p>
        </div>
        </div>    
        <?php
        $this->add_onload_command("\$('#reginput').focus();\n");
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        $phpunit->assertInternalType('boolean', $this->isBottleReturn(1));
        $this->rePrice(1, 1, false);
        $this->rePrice(101, 1, true);
        $this->markZeroRecord(1);
        ob_start();
        $this->body_content();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
    }
}

AutoLoader::dispatch();

