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
use COREPOS\pos\lib\Authenticate;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\FormLib;
use COREPOS\pos\lib\Drawers;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class drawerPage extends NoInputCorePage 
{
    private $is_admin = false;
    private $my_drawer = 0;
    private $available = array();

    private function giveUp()
    {
        if (empty($this->available) && !$this->is_admin && $this->my_drawer == 0){
            // no drawer available and not admin
            // sign out and go back to main login screen
            Database::setglobalvalue("LoggedIn", 0);
            CoreLocal::set("LoggedIn",0);
            CoreLocal::set("training",0);
            $this->change_page($this->page_url."gui-modules/login2.php");
        } else {
            $this->change_page($this->page_url."gui-modules/pos2.php");
        }

        return false;
    }

    private function takeOver($new_drawer)
    {
        // take over a drawer
        if ($this->my_drawer != 0){
            // free up the current drawer if it exists
            Drawers::kick();
            Drawers::free($this->my_drawer);
        }
        // switch to the requested drawer
        Drawers::assign(CoreLocal::get('CashierNo'),$new_drawer);
        Drawers::kick();
        $this->my_drawer = $new_drawer;
    }

    private function switchDrawer($new_drawer)
    {
        foreach($this->available as $id){
            // verify the requested drawer is available
            if ($new_drawer == $id){
                if ($this->my_drawer != 0){
                    // free up the current drawer if it exists
                    Drawers::kick();
                    Drawers::free($this->my_drawer);
                }
                // switch to the requested drawer
                Drawers::assign(CoreLocal::get('CashierNo'),$new_drawer);
                Drawers::kick();
                $this->my_drawer = $new_drawer;

                break;
            }
        }
    }

    function preprocess()
    {
        $this->my_drawer = Drawers::current();
        $this->available = Drawers::available();
        $this->is_admin = false;
        $sec = Authenticate::getPermission(CoreLocal::get('CashierNo'));
        if ($sec >= 30) {
            $this->is_admin = true;
        }

        if (FormLib::get('selectlist', false) !== false) {
            $choice = FormLib::get('selectlist');
            if (empty($choice)) {
                return $this->giveUp();
            }
            if (substr($choice,0,2) == 'TO' && $this->is_admin){
                $this->takeOver(substr($choice, 2));
            } elseif (substr($choice,0,2) == 'SW') {
                $this->switchDrawer(substr($choice, 2));
            }
        }

        return true;
    }

    function head_content()
    {
        ?>
        <script type="text/javascript" src="../js/selectSubmit.js"></script>
        <?php
    } // END head() FUNCTION

    function body_content() 
    {
        $msg = 'You are using drawer #'.$this->my_drawer;
        if ($this->my_drawer == 0)
            $msg = 'You do not have a drawer';
        $num_drawers = (CoreLocal::get('dualDrawerMode')===1) ? 2 : 1;
        $dbc = Database::pDataConnect();
        ?>
        <div class="baseHeight">
        <div class="centeredDisplay colored">
            <span class="larger"><?php echo $msg; ?></span>
            <br />
        <form id="selectform" method="post" action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <select name="selectlist" id="selectlist" onblur="$('#selectlist').focus();">
        <option value=''>
        <?php 
        if ($this->is_admin){
            for($i=0;$i<$num_drawers;$i++){
                $nameQ = 'SELECT FirstName FROM drawerowner as d
                    LEFT JOIN employees AS e ON e.emp_no=d.emp_no
                    WHERE d.drawer_no='.($i+1);
                $name = $dbc->query($nameQ);
                if ($dbc->num_rows($name) > 0)
                    $name = array_pop($dbc->fetch_row($name));
                if (empty($name)) $name = 'Unassigned';
                printf('<option value="TO%d">Take over drawer #%d (%s)</option>',
                    ($i+1),($i+1),$name);
            }
        }
        elseif (count($this->available) > 0){
            foreach($this->available as $num){
                printf('<option value="SW%d">Switch to drawer #%d</option>',
                    $num,$num);
            }
        }
        else 
            echo '<option value="">No actions available</option>';
        ?>
        </select>
        </form>
        <p>
        <span class="smaller"><?php echo _("clear to cancel"); ?></span>
        </p>
        </div>
        </div>
        <?php
        $this->add_onload_command("selectSubmit('#selectlist', '#selectform')\n");
        $this->add_onload_command("\$('#selectlist').focus();");
    } // END body_content() FUNCTION

    public function unitTest($phpunit)
    {
        ob_start();
        $phpunit->assertEquals(false, $this->giveUp());
        $this->switchDrawer(1);
        $this->takeOver(1);
        $phpunit->assertEquals(1, $this->my_drawer);
        ob_get_clean();
    }
}

AutoLoader::dispatch();

