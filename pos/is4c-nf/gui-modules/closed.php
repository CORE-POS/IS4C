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
use COREPOS\pos\lib\Authenticate;
use COREPOS\pos\lib\CoreState;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\Drawers;
use COREPOS\pos\lib\LaneLogger;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\UdpComm;
use COREPOS\pos\lib\Kickers\Kicker;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class closed extends BasicCorePage 
{

    private $boxCSS;
    private $msg;
    protected $hardware_polling = false;

    public function preprocess()
    {
        $this->boxCSS = 'coloredArea';
        $this->img = $this->page_url."graphics/key-icon.png";
        $this->msg = '';
        $this->body_class = '';

        return true;
    }

    public function head_content()
    {
        $this->scanner_scale_polling(false);
        $this->add_onload_command("\$('#scalebox').css('display','none');");
    }

    public function body_content()
    {
        ?>
        <div id="loginTopBar">
            <div class="name" style="border-radius: 4px 4px 0px 0px;">
                I S 4 C
            </div>
            <div class="version">
                P H P &nbsp; D E V E L O P M E N T
                &nbsp; V E R S I O N &nbsp; 2 .0 .0
            </div>
            <div class="welcome coloredArea" style="border-radius: 0px 0px 4px 4px;">
                <?php echo _("W E L C O M E"); ?>
            </div><?php echo $logging; ?>
        </div>
        <div id="loginCenter">
        <div id="loginBox" class="box <?php echo $this->boxCSS; ?> rounded">
            <img alt="key" src='<?php echo $this->img ?>' />
            <br />
            <br />
        </div>    
        </div>
        <div id="loginExit">
            <?php 
            echo _("EXIT");
            ?>
            <a href=""><img id="exit" style="border:0;" alt="exit"  src="<?php echo $this->page_url; ?>graphics/switchblue2.gif" /></a>
    
        </div>
        <form name="hidden">
        <input type="hidden" name="scan" value="noScan">
        </form>
        <?php
    } // END true_body() FUNCTION

}

AutoLoader::dispatch();


