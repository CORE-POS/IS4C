<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op.

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

/* this module is intended for re-use. Just set 
 * Pass the name of a class with the
 * static properties: 
 *  - requestInfoHeader (upper message to display)
 *  - requestInfoMsg (lower message to display)
 * and static method:
 *  - requestInfoCallback(string $info)
 *
 * The callback receives the info entered by the 
 * cashier. To reject the entry as invalid, return
 * False. Otherwise return a URL to redirect to that
 * page or True to go to pos2.php.
 */

use COREPOS\pos\lib\gui\NoInputCorePage;
use COREPOS\pos\lib\MiscLib;
include_once(dirname(__FILE__).'/../lib/AutoLoader.php');

class requestInfo extends NoInputCorePage 
{

    private $requestHeader = '';
    private $requestMsg = '';

    private function validateClass($class)
    {
        $method = new ReflectionMethod($class, 'requestInfoCallback');
        if (!$method->isStatic() || !$method->isPublic())
            throw new Exception('bad method requestInfoCallback');
        $property = new ReflectionProperty($class, 'requestInfoMsg');
        if (!$property->isStatic() || !$property->isPublic())
            throw new Exception('bad property requestInfoMsg');
        $property = new ReflectionProperty($class, 'requestInfoHeader');
        if (!$property->isStatic() || !$property->isPublic())
            throw new Exception('bad property requestInfoHeader');

        return true;
    }

    private function handleInput($reginput, $class, $pos_home)
    {
        $reginput = strtoupper($reginput);
        if ($reginput == 'CL') {
            // clear; go home
            $this->change_page($pos_home);
            return false;
        } elseif ($reginput === '') {
            // blank. stay at prompt
            return true;
        } else {
            // give info to callback function
            $result = $class::requestInfoCallback($reginput);
            if ($result === true) {
                // accepted. go home
                $this->change_page($pos_home);
                return false;
            } elseif ($result === false) {
                // input rejected. try again
                $this->result_header = 'invalid entry';
                return true;
            } else {
                // callback wants to navigate to
                // another page
                $this->change_page($result);
                return false;
            }
        }
    }

    public function preprocess()
    {
        // get calling class (required)
        $class = $this->form->tryGet('class');
        $class = str_replace('-', '\\', $class);
        $pos_home = MiscLib::base_url().'gui-modules/pos2.php';
        if ($class === '' || !class_exists($class)){
            $this->change_page($pos_home);
            return False;
        }
        // make sure calling class implements required
        // method and properties
        try {
            $this->validateClass($class);
        }
        catch (Exception $e){
            $this->change_page($pos_home);
            return false;
        }

        $this->requestHeader = $class::$requestInfoHeader;
        $this->requestMsg = $class::$requestInfoMsg;

        // info was submitted
        try {
            return $this->handleInput($this->form->input, $class, $pos_home);
        } catch (Exception $ex) {}

        return true;
    }

    function body_content()
    {
        ?>
        <div class="baseHeight">
        <div class="colored centeredDisplay">
        <span class="larger">
        <?php echo $this->requestHeader; ?>
        </span>
        <form name="form" method="post" autocomplete="off" 
            action="<?php echo filter_input(INPUT_SERVER, 'PHP_SELF'); ?>">
        <input type="text" id="reginput" name='input' tabindex="0" onblur="$('#input').focus()" />
        <input type="hidden" name="class" value="<?php echo $this->form->tryGet('class'); ?>" />
        </form>
        <p>
        <?php echo $this->requestMsg; ?>
        </p>
        </div>
        </div>

        <?php
        $this->add_onload_command("\$('#reginput').focus();");
    } // END true_body() FUNCTION

    public function unitTest($phpunit)
    {
        $phpunit->assertEquals(true, $this->validateClass('COREPOS\\pos\\lib\\adminlogin\\AnyTenderReportRequest'));
        ob_start();
        $phpunit->assertEquals(false, $this->handleInput('CL', 'COREPOS\\pos\\lib\\adminlogin\\AnyTenderReportRequest', ''));
        $phpunit->assertEquals(true, $this->handleInput('', 'COREPOS\\pos\\lib\\adminlogin\\AnyTenderReportRequest', ''));
        $phpunit->assertEquals(true, $this->handleInput('asdf', 'COREPOS\\pos\\lib\\adminlogin\\AnyTenderReportRequest', ''));
        $phpunit->assertEquals(false, $this->handleInput('1', 'COREPOS\\pos\\lib\\adminlogin\\AnyTenderReportRequest', ''));
        ob_get_clean();
        ob_start();
        $this->body_content();
        $body = ob_get_clean();
        $phpunit->assertNotEquals(0, strlen($body));
    }

}

AutoLoader::dispatch();

