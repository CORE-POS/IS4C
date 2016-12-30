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
use COREPOS\pos\lib\Drawers;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class drawerPage extends NoInputCorePage 
{
    private $isAdmin = false;
    private $myDrawer = 0;
    private $available = array();

    private function giveUp()
    {
        if (empty($this->available) && !$this->isAdmin && $this->myDrawer == 0){
            // no drawer available and not admin
            // sign out and go back to main login screen
            Database::setglobalvalue("LoggedIn", 0);
            $this->session->set("LoggedIn",0);
            $this->session->set("training",0);
            $this->change_page($this->page_url."gui-modules/login2.php");
        } else {
            $this->change_page($this->page_url."gui-modules/pos2.php");
        }

        return false;
    }

    private function takeOver($drawer)
    {
        // take over a drawer
        $drawers = new Drawers($this->session, Database::pDataConnect());
        if ($this->myDrawer != 0){
            // free up the current drawer if it exists
            $drawers->kick();
            $drawers->free($this->myDrawer);
        }
        // switch to the requested drawer
        $drawers->assign($this->session->get('CashierNo'),$drawer);
        $drawers->kick();
        $this->myDrawer = $drawer;
    }

    private function switchDrawer($drawer)
    {
        $drawers = new Drawers($this->session, Database::pDataConnect());
        foreach($this->available as $id){
            // verify the requested drawer is available
            if ($drawer == $id){
                if ($this->myDrawer != 0){
                    // free up the current drawer if it exists
                    $drawers->kick();
                    $drawers->free($this->myDrawer);
                }
                // switch to the requested drawer
                $drawers->assign($this->session->get('CashierNo'),$drawer);
                $drawers->kick();
                $this->myDrawer = $drawer;

                break;
            }
        }
    }

    function preprocess()
    {
        $drawers = new Drawers($this->session, Database::pDataConnect());
        $this->myDrawer = $drawers->current();
        $this->available = $drawers->available();
        $this->isAdmin = false;
        $sec = Authenticate::getPermission($this->session->get('CashierNo'));
        if ($sec >= 30) {
            $this->isAdmin = true;
        }

        try {
            $choice = $this->form->selectlist;
            if (empty($choice)) {
                return $this->giveUp();
            }
            if (substr($choice,0,2) == 'TO' && $this->isAdmin){
                $this->takeOver(substr($choice, 2));
            } elseif (substr($choice,0,2) == 'SW') {
                $this->switchDrawer(substr($choice, 2));
            }
        } catch (Exception $ex) {}

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
        $msg = sprintf(_('You are using drawer #%d'), $this->myDrawer);
        if ($this->myDrawer == 0)
            $msg = _('You do not have a drawer');
        $numDrawers = ($this->session->get('dualDrawerMode')===1) ? 2 : 1;
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
        if ($this->isAdmin){
            for($i=0;$i<$numDrawers;$i++){
                $nameQ = 'SELECT FirstName FROM drawerowner as d
                    LEFT JOIN employees AS e ON e.emp_no=d.emp_no
                    WHERE d.drawer_no='.($i+1);
                $name = $dbc->query($nameQ);
                if ($dbc->num_rows($name) > 0)
                    $name = array_pop($dbc->fetch_row($name));
                if (empty($name)) $name = _('Unassigned');
                $opt = sprintf(_('Take over drawer #%d (%s)'), ($i+1), $name);
                printf('<option value="TO%d">%s</option>', ($i+1), $opt);
            }
        }
        elseif (count($this->available) > 0){
            foreach($this->available as $num){
                $opt = sprintf(_('Switch to drawer #%d'), $num);
                printf('<option value="SW%d">%s</option>', $num, $opt);
            }
        }
        else 
            echo '<option value="">' . _('No actions available') . '</option>';
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
        $phpunit->assertEquals(1, $this->myDrawer);
        ob_get_clean();
    }
}

AutoLoader::dispatch();

