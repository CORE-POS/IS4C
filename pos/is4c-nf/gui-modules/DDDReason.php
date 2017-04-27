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

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\Database;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

// based upon RefundComment class
class DDDReason extends NoInputCorePage 
{
    private $reasons = array();

    public function preprocess()
    {
        // pre-emptively lookup available reasons
        $dbc = Database::pDataConnect();
        $result = $dbc->query('SELECT shrinkReasonID, description
                              FROM ShrinkReasons');
        if ($dbc->numRows($result) <= 1) {
            // zero or one reasons
            $this->session->set('shrinkReason', 0);
            $row = $dbc->fetchRow($result);
            if ($row) {
                // exactly one reason configured. 
                // just use that reason and continue
                // to next step
                $this->session->set('shrinkReason', $row['shrinkReasonID']);
            }
            $this->change_page($this->page_url."gui-modules/adminlogin.php?class=COREPOS-pos-lib-adminlogin-DDDAdminLogin");

            return false;
        }

        while($row = $dbc->fetchRow($result)) {
            $this->reasons[$row['shrinkReasonID']] = $row['description'];
        }

        try {
            $input = $this->form->selectlist;
            $this->session->set("shrinkReason", 0);
            $url = $this->page_url . "gui-modules/pos2.php";
            if ($input != "CL" && $input == '') {
                $this->session->set("shrinkReason", (int)$input);
                $url = $this->page_url . "gui-modules/adminlogin.php?class=COREPOS-pos-lib-adminlogin-DDDAdminLogin";
            }
            $this->change_page($url);

            return false;
        } catch (Exception $ex) {}

        return true;
    }
    
    public function head_content()
    {
        echo '<script type="text/javascript" src="../js/selectSubmit.js"></script>';
    } 

    public function body_content() 
    {
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored">
                <span class="larger"><?php echo _('Why are these items being marked as shrink/unsellable?'); ?></span>
        <form name="selectform" method="post" 
            id="selectform" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
            <select name="selectlist" id="selectlist"
                onblur="$('#selectlist').focus();">
            <?php
            foreach($this->reasons as $id => $label) {
                printf('<option value="%d">%s</option>', $id, $label);
            }
            ?>
            </select>
        </form>
        <p>
        <span class="smaller"><?php echo _('[clear] to cancel'); ?></span>
        </p>
        </div>
        </div>    
        <?php
        $this->addOnloadCommand("\$('#selectlist').focus();\n");
        $this->addOnloadCommand("selectSubmit('#selectlist', '#selectform')\n");
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        ob_start();
        $this->head_content();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
        $this->reasons = array(1 => 'test');
        ob_start();
        $this->body_content();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean()));
    }
}

AutoLoader::dispatch();

