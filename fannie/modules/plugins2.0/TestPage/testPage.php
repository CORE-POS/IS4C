<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of CORE-POS.

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
  The purpose of this page is to test out/learn 
  how to use javascript to send an ajax request
  in Fannie. 
*/
class testPage extends FanniePage 
{

    public $description = "[a] test";
    public $themed = true;

    protected $must_authenticate = true;

    protected $header = "Fannie :: test page";
    protected $title = "Test page";

    private $display_function;
    private $coupon_id;
    
    public function css_content()
    {
        return '
        tr.green td.sub {
            background:#ccffcc;
        }
        tr.red td.sub {
            background:#F7BABA;
        }
        tr.white td.sub {
            background:#ffffff;
        }
        tr.yellow td.sub{
            background:#ffff96;
        }
        tr.selection td.sub {
            background:#add8e6;
        }
        td.srp {
            text-decoration: underline;
        }
        .background {
            border-radius: 15px;
        }
        body {
            background-color: #E4DDC5;
        }
        ';
    }
    
    public  function body_content()
    {
        echo "<fieldset class=\"background\">";
        echo "your page starts here.<br>";
        $this->addScript('testAjax.js');
        
        ?>
            <div class="form-inline" align="left">
                <form action="testPage.php" onsubmit="test(first_name, last_name)"; return false;>
                    <div class="form-group">
                    <label>Sign-up New Student</label><br>
                        <input type="text" class="form-control" name="first_name" placeholder="First Name">
                        <input type="text" class="form-control" name="last_name" placeholder="Last Name">
               
                        <button type="submit" class="btn btn-default">Add Student</button>
                    </div><br><br>
                </form>
            </div><br><br>
        
        <div id='ajax-resp'>
        <?php
        
        echo $_SESSION['foo'];
        echo "your page ends here.<br>";
    }
    
}

FannieDispatch::conditionalExec();









