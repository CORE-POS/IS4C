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
include('util.php');
include('db.php');
include_once('../classlib2.0/FannieAPI.php');

/**
    @class InstallMenuPage
    Class for the Menu install and config options
*/
class InstallMenuPage extends InstallPage {

    protected $title = 'Fannie: Menu Builder';
    protected $header = 'Fannie: Menu Builder';

    public $description = "
    Class for the Menu install and config options page.
    ";

    // This replaces the __construct() in the parent.
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

    // If chunks of CSS are going to be added the function has to be
    //  redefined to return them.
    // If this is to override x.css draw_page() needs to load it after the add_css_file
    /**
      Define any CSS needed
      @return A CSS string
    */
    function css_content(){
        $css ="";

        return $css;

    //css_content()
    }

    // If chunks of JS are going to be added the function has to be
    //  redefined to return them.
    /**
      Define any javascript needed
      @return A javascript string
    function javascript_content(){

    }
    */

    function body_content(){
        include('../config.php'); 
        ob_start();
        ?>
        <?php
        echo showInstallTabs('Menu');
        ?>

        <form action=InstallMenuPage.php method=post>
        <h1 class="install"><?php echo $this->header; ?></h1>
        <?php

        if (is_writable('../config.php')){
            echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
        }
        else {
            echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
            echo "<br />Full path is: ".'../config.php'."<br />";
            if (function_exists('posix_getpwuid')){
                $chk = posix_getpwuid(posix_getuid());
                echo "PHP is running as: ".$chk['name']."<br />";
            }
            else
                echo "PHP is (probably) running as: ".get_current_user()."<br />";
        }
        ?>

        <hr  />
        <p class="ichunk">
        Fannie Administration Menu at left or top.
        <ul>
        <li>Menu at left is traditional; on top may allows more horizontal space on the page for the report or tool.
        <li>Under construction: The "top" option is only available for the configurable menu at this point.
        </ul>
        <b>Admin menu position</b>
        <select name=FANNIE_NAV_POSITION>
        <?php
        if (!isset($FANNIE_NAV_POSITION)) $FANNIE_NAV_POSITION = 'left';
        if (isset($_REQUEST['FANNIE_NAV_POSITION'])) $FANNIE_NAV_POSITION = $_REQUEST['FANNIE_NAV_POSITION'];
        if ($FANNIE_NAV_POSITION == 'top'){
            confset('FANNIE_NAV_POSITION',"'top'");
            echo "<option value='left'>Left</option><option value='top' selected>Top</option>";
        }
        else{
            confset('FANNIE_NAV_POSITION',"'left'");
            echo "<option value='left' selected>Left</option><option value='top'>Top</option>";
        }
        echo "</select>";
        ?>
        </p>

        <hr  />
        <p class="ichunk">
        Whether to always show the Fannie Administration Menu.
        <ul>
        <li>Coops may prefer not to show the menu in order to maximize the space available on the page for the report or tool.
        <li>Some pages may show or not show the Fannie Menu regardless of this setting,
        but setting it to Yes will increase the number of pages on which the menu appears.
        </ul>
        <b>Show Admin menu</b>
        <!-- "windowdressing" is the term used in Class Lib 2.0 for the heading and navigation menu.
             Use this to set the value of $window_dressing. -->
        <select name=FANNIE_WINDOW_DRESSING>
        <?php
        if (!isset($FANNIE_WINDOW_DRESSING)) $FANNIE_WINDOW_DRESSING = False;
        if (isset($_REQUEST['FANNIE_WINDOW_DRESSING'])) $FANNIE_WINDOW_DRESSING = $_REQUEST['FANNIE_WINDOW_DRESSING'];
        if ($FANNIE_WINDOW_DRESSING === True || $FANNIE_WINDOW_DRESSING == 'Yes'){
            confset('FANNIE_WINDOW_DRESSING','True');
            echo "<option selected>Yes</option><option>No</option>";
        }
        else{
            confset('FANNIE_WINDOW_DRESSING','False');
            echo "<option>Yes</option><option selected>No</option>";
        }
        echo "</select>";
        ?>
        </p>

        <hr  />
        <p class="ichunk">
        Use this tool to customize Fannie's left hand menu. Usage:
        <ul>
        <li>To add a new menu entry, type it in the appropriate 'New' box.
        <li>To delete an entry, clear its 'Text' box. Bear in mind sub-entries
        will also be deleted.
        <li>URLs are relative to Fannie <i>unless</i> they begin with / or
        a protocol (http://, https://, etc).
        </ul>
        <b>Configurable Menu Enabled</b>
        <select name=FANNIE_DYNAMIC_MENU>
        <?php
        if (!isset($FANNIE_DYNAMIC_MENU)) $FANNIE_DYNAMIC_MENU = False;
        if (isset($_REQUEST['FANNIE_DYNAMIC_MENU'])) $FANNIE_DYNAMIC_MENU = $_REQUEST['FANNIE_DYNAMIC_MENU'];
        if ($FANNIE_DYNAMIC_MENU === True || $FANNIE_DYNAMIC_MENU == 'Yes'){
            confset('FANNIE_DYNAMIC_MENU','True');
            echo "<option selected>Yes</option><option>No</option>";
        }
        else{
            confset('FANNIE_DYNAMIC_MENU','False');
            echo "<option>Yes</option><option selected>No</option>";
        }
        echo "</select>";
        ?>
        </p>

        <h4 class="install">Fannie Menu Builder</h4>
        <?php
        if (!isset($FANNIE_MENU) || !is_array($FANNIE_MENU)) $FANNIE_MENU=array();
        if (isset($_REQUEST['label1'])){
            $READ_BACK = array();
            $READ_BACK = $this->fm_read($READ_BACK,'1');
            $FANNIE_MENU = $READ_BACK;
        }
        if (empty($FANNIE_MENU)){
            include('../src/defaultmenu.php');
        }

        $this->fm_draw($FANNIE_MENU);
        $saveStr = $this->fm_to_string($FANNIE_MENU);
        confset('FANNIE_MENU',$saveStr);
        ?>
        <hr />
        <input type=submit value="Refresh" />
        </form>
        </body>
        </html>

        <?php

        return ob_get_clean();

    // body_content
    }

    /**
        Read POST variables recursively into the proper
        array format
    */
    function fm_read($arr,$parent='1'){
        if (!isset($_REQUEST['label'.$parent]) || !is_array($_REQUEST['label'.$parent]))
            return $arr;
        for($i=0;$i<count($_REQUEST['label'.$parent]);$i++){
            if (empty($_REQUEST['label'.$parent][$i]))
                continue;
            $new_entry = array();
            $new_entry['label'] = $_REQUEST['label'.$parent][$i];
            $new_entry['url'] = $_REQUEST['url'.$parent][$i];
            if (isset($_REQUEST['subheading'.$parent]) && isset($_REQUEST['subheading'.$parent][$i]))
                $new_entry['subheading'] = $_REQUEST['subheading'.$parent][$i];
            $new_entry['submenu'] = array();
            $new_entry = $this->fm_read($new_entry,$parent.'_'.($i+1));
            if ($parent == '1'){ 
                $arr[] = $new_entry;
            }
            else {
                $arr['submenu'][] = $new_entry;
            }
        }
        return $arr;
    }

    /**
        Convert menu array to a string that can be
        written to config.php.
    */
    function fm_to_string($arr){
        $ret = 'array(';
        for($i=0;$i<count($arr);$i++){
            if (!isset($arr[$i]['label']) || empty($arr[$i]['label']))
                continue;
            $ret .= 'array(';
            $ret .= "'label'=>'".str_replace("'","",$arr[$i]['label'])."',";
            $ret .= "'url'=>'".(isset($arr[$i]['url'])?$arr[$i]['url']:'')."',";
            if (isset($arr[$i]['subheading']))
                $ret .= "'subheading'=>'".str_replace("'","",$arr[$i]['subheading'])."',";
            if (isset($arr[$i]['submenu']) && is_array($arr[$i]['submenu']))
                $ret .= "'submenu'=>".$this->fm_to_string($arr[$i]['submenu']);
            $ret = rtrim($ret,',');
            $ret .= '),';
        }
        $ret = rtrim($ret,',');
        $ret .= ')';
        return $ret;
    }

    /**
        Draw menu recursively
    */
    function fm_draw($arr,$parent='1'){
        echo '<ul>';
        $i=1;
        foreach($arr as $item){
            printf('<li>Text:<input type="text" name="label%s[]" value="%s" /> ',
                $parent,$item['label']);
            if ($parent == '1'){
                printf('Sub:<input type="text" name="subheading%s[]" value="%s" /> ',
                    $parent,(isset($item['subheading'])?$item['subheading']:''));
            }
            printf('URL:<input type="text" size="50" name="url%s[]" value="%s" /> ',
                $parent,$item['url']);
            echo "\n";
            if (!isset($item['submenu']) || !is_array($item['submenu'])){
                $item['submenu'] = array();
            }
            if(empty($item['submenu'])){
                printf('<a href="" 
                    onclick="$(\'#submenu%s_%s\').show();return false;"
                    >Add submenu</a>',$parent,$i);
                    
            }
            echo '</li><li id="submenu'.$parent.'_'.$i.'"';
            if (empty($item['submenu'])) echo 'style="display:none;"';
            echo '>';
            $this->fm_draw($item['submenu'], $parent.'_'.$i);
            echo '</li>';
            echo "\n";
            $i++;
        }   
        printf('<li>New:<input type="text" name="label%s[]" value="" /> ',$parent);
        printf('URL:<input type="text" size="50" name="url%s[]" value="" /></li>',$parent);
        echo '<br />';
        echo '</ul>'."\n";
    }

// InstallMenuPage
}

FannieDispatch::conditionalExec(false);

?>
