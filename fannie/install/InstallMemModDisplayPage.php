<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

//ini_set('display_errors','1');
include('../config.php');
if (!class_exists('FannieAPI')) {
         include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
include('util.php');
include('db.php');

/**
    @class InstallMemModDisplayPage
    Class for the MemModDisplay install and config options
*/
class InstallMemModDisplayPage extends InstallPage {

    protected $title = 'Fannie: Member Editor Module Display Order';
    protected $header = 'Fannie: Member Editor Module Display Order';

    public $description = "
    Class for the Member Editor Module Display Order configuration page.
    ";

    public function __construct() {

        // To set authentication.
        FanniePage::__construct();

        // Link to a file of CSS by using a function.
        $this->add_css_file("../src/style.css");
        $this->add_css_file("../src/javascript/jquery-ui.css");
        $this->add_css_file("../src/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("../src/javascript/jquery.js");
        $this->add_script("../src/javascript/jquery-ui.js");

    // __construct()
    }

    function body_content(){
        global $FANNIE_MEMBER_MODULES;
        ob_start();

        $parent = 'InstallMembershipPage.php';
        echo showLinkUp('Back to Membership',"$parent",'');

        echo "<h1 class='install'>{$this->header}</h1>";

        // Re-order the modules and report.
        if (isset($_REQUEST['ordering'])){
            $FANNIE_MEMBER_MODULES = array();
            foreach($_REQUEST['ordering'] as $o){
                if (!in_array($o,$FANNIE_MEMBER_MODULES)) 
                    $FANNIE_MEMBER_MODULES[] = $o;
            }
            $saveStr = 'array(';
            foreach($FANNIE_MEMBER_MODULES as $t)
                $saveStr .= '"'.$t.'",';
            $saveStr = rtrim($saveStr,',').")";
            echo "<blockquote><i>Order Updated</i></blockquote>";
            confset('FANNIE_MEMBER_MODULES',$saveStr);
        }

        $self = basename($_SERVER['PHP_SELF']);

        echo "<form action='$self' method='post'>";

        if (is_writable('../config.php')){
            echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
        }
        else {
            echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
        }
        echo "<hr />";

        $num = count($FANNIE_MEMBER_MODULES);
        if ($num == 0){
            echo "<i>Error: no modules enabled</i><br />";
            echo '<a href="mem.php">Back to Member Settings</a>';
            return ob_get_clean();
        }
        echo "<p class='ichunk'>The enabled modules are listed below in the order in which they will appear in the editor.
        <br />In each dropdown, choose the module you would like to appear in that position.
        </p>";
        for ($i=1;$i<=$num;$i++){
            echo "#$i: <select name=\"ordering[]\">";
            for($j=1;$j<=$num;$j++){
                printf("<option %s>%s</option>",
                    ($i==$j?'selected':''),
                    $FANNIE_MEMBER_MODULES[$j-1]);
            }
            echo "</select><p />";
        }
        ?>
        <input type="submit" value="Save Order of Modules" />
        </form>

        <?php

        return ob_get_clean();

    // body_content
    }

// InstallMemModDisplayPage
}

FannieDispatch::conditionalExec(false);

?>
