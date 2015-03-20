<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
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
class InstallMenuPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie: Menu Builder';
    protected $header = 'Fannie: Menu Builder';

    public $description = "
    Class for the Menu install and config options page.
    ";
    public $themed = true;

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

    function body_content()
    {
        include('../config.php'); 
        ob_start();
        ?>
        <?php
        echo showInstallTabs('Menu');
        ?>

        <form action=InstallMenuPage.php method=post>
        <h1 class="install">
            <?php 
            if (!$this->themed) {
                echo "<h1 class='install'>{$this->header}</h1>";
            }
            ?>
        </h1>
        <?php

        if (is_writable('../config.php')){
            echo "<div class=\"alert alert-success\"><i>config.php</i> is writeable</div>";
        }
        else {
            echo "<div class=\"alert alert-danger\"><b>Error</b>: config.php is not writeable</div>";
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
        Whether to always show the Fannie Administration Menu.
        <ul>
        <li>Coops may prefer not to show the menu in order to maximize the space available on the page for the report or tool.
        <li>Some pages may show or not show the Fannie Menu regardless of this setting,
        but setting it to Yes will increase the number of pages on which the menu appears.
        </ul>
        <b>Show Admin menu</b>
        <!-- "windowdressing" is the term used in Class Lib 2.0 for the heading and navigation menu.
             Use this to set the value of $window_dressing. -->
        <?php 
        echo installSelectField('FANNIE_WINDOW_DRESSING', $FANNIE_WINDOW_DRESSING,
                    array(1 => 'Yes', 0 => 'No'), false);
        ?>
        </p>

        <hr  />
        <p class="ichunk">
        Use this tool to customize Fannie's menu. Usage:
        <ul>
        <li>Left hand text box contains the menu entry text
        <li>Right hand box contains a URL or a special value for other
        types of entries such as section headers.
        <li>URLs are relative to Fannie <i>unless</i> they begin with / or
        a protocol (http://, https://, etc).
        </ul>

        <h4 class="install">Fannie Menu Builder</h4>
        <?php
        $VALID_MENUS = array('Item Maintenance', 'Sales Batches', 'Reports', 'Membership', 'Synchronize', 'Admin', '__store__');
        if (!isset($FANNIE_MENU) || !is_array($FANNIE_MENU)) {
            include('../src/init_menu.php');
            $FANNIE_MENU = $INIT_MENU;
        } else {
            foreach ($FANNIE_MENU as $menu => $content) {
                if (!in_array($menu, $VALID_MENUS)) {
                    // menu is not valid
                    // reset to default
                    // obviously not ideal error recovery
                    include('../src/init_menu.php');
                    $FANNIE_MENU = $INIT_MENU;
                    break;
                }
            }
        }

        for ($i=0; $i<count($VALID_MENUS); $i++) {
            $post_titles = FormLib::get('m_title' . $i);
            $post_urls = FormLib::get('m_url' . $i);
            if (!is_array($post_titles) || !is_array($post_urls)) {
                continue;
            }
            /** rebuild from posted data **/
            $FANNIE_MENU[$VALID_MENUS[$i]] = array();
            $divider_count = 1;
            for ($j=0; $j<count($post_titles); $j++) {
                $p_title = $post_titles[$j];
                $p_url = $post_urls[$j];
                // url must have some kind of value
                // title may be empty on dividers
                if (empty($p_url)) {
                    continue;
                }
                if ($p_url == '__divider__') {
                    $p_title = 'divider' . $divider_count;
                    $divider_count++;
                } elseif (empty($p_title)) {
                    continue;
                }

                $FANNIE_MENU[$VALID_MENUS[$i]][$p_title] = $p_url;
            }
        }

        if (FormLib::get('import-menu') !== '') {
            $import = FormLib::get('import-menu');
            $json = json_decode($import, true);
            if ($json === null) {
                echo '<div class="alert alert-danger">Menu Import is not valid JSON</div>';
            } else {
                $valid = true;
                foreach ($json as $menu => $content) {
                    if (!in_array($menu, $VALID_MENUS)) {
                        echo '<div class="alert alert-danger"><strong>' 
                            . $menu . '</strong> is not a valid top-level menu</div>';
                        $valid = false;
                        break;
                    } elseif (!is_array($content)) {
                        echo '<div class="alert alert-danger">Entries for <strong>'
                            . $menu . '</strong> are not valid. It should be a JSON
                            object with keys representing menu titles and values
                            represeting URLs or the special values __header__ and
                            __divider__</div>';
                        $valid = false;
                        break;
                    }
                }
                if ($valid) {
                    $FANNIE_MENU = $json;
                    echo '<div class="alert alert-success">Imported menu</div>';
                }
            }
        }
        
        $saveStr = 'array(';
        $menu_number = 0;
        $select = '<select onchange="$(this).next(\'input\').val($(this).val());"
                        class="form-control">
                <option value="">URL</option>';
        $opts = array('__header__'=>'Section Header', '__divider__'=>'Divider Line');
        foreach ($FANNIE_MENU as $menu => $content) {
            $saveStr .= "'" . $menu . "' => array(";
            echo '<b>' . $menu . '</b>';
            echo '<ul id="menuset' . $menu_number . '">';
            foreach ($content as $m_title => $m_url) {
                $saveStr .= "'" . str_replace("'", "\\'", $m_title) . "' => '" . $m_url . "',";
                echo '<li class="form-inline">';
                printf('<input type="text" name="m_title%d[]" value="%s" class="form-control" />', 
                    $menu_number, $m_title); 
                echo $select;
                foreach ($opts as $key => $val) {
                    printf('<option %s value="%s">%s</option>',
                        ($key == $m_url ? 'selected' : ''),
                        $key, $val);
                }
                echo '</select>';
                printf('<input type="text" name="m_url%d[]" value="%s" class="form-control" />', 
                    $menu_number, $m_url); 
                echo ' [ <a href="" onclick="$(this).parent().remove(); return false;">Remove Entry</a> ]';
                echo '</li>';
            }
            $saveStr .= '),';
            echo '</ul>';
            $newEntry = sprintf('<li class="form-inline">
                            <input type="text" name="m_title%d[]" value="" class="form-control" />%s',
                            $menu_number, $select);
            foreach ($opts as $key => $val) {
                $newEntry .= sprintf('<option value="%s">%s</option>', $key, $val);
            }
            $newEntry .= sprintf('</select><input type="text" name="m_url%d[]" value="" class="form-control" />
                    [ <a href="" onclick="$(this).parent().remove(); return false;">Remove Entry</a> ]
                    </li>', $menu_number);
            echo '<div id="newEntry' . $menu_number . '" class="collapse">';
            echo $newEntry;
            echo '</div>';
            printf('[ <a href="" onclick="$(\'ul#menuset%d\').append($(\'#newEntry%d\').html()); return false;">Add New Entry</a>
                to %s ]',
                $menu_number, $menu_number, $menu);
            echo '<br />';
            $menu_number++;
        }
        $saveStr .= ')';
        confset('FANNIE_MENU', $saveStr);
        ?>
        <hr />
        <div class="form-group">
        <label>Hand-editable Menu / Export</label>
        <textarea class="form-control" rows="15">
<?php echo $this->prettyJSON(json_encode($FANNIE_MENU)); ?>
        </textarea>
        </div>
        <div class="form-group">
        <label>Import Menu (use same JSON format)</label>
        <textarea name="import-menu" class="form-control" rows="15"></textarea>
        </div>
        <p>
            <button type="submit" name="psubmit" value="1" class="btn btn-default">Save Configuration</button>
        </p>
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

    private function prettyJSON($json)
    {
        $result= '';
        $pos = 0;
        $strLen= strlen($json);
        $indentStr = '    ';
        $newLine = "\n";
        $prevChar= '';
        $outOfQuotes = true;

        for ($i=0; $i<=$strLen; $i++) {
            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;
            // If this character is the end of an element, 
            // output a new line and indent the next line.
            } else if (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                for ($j=0; $j<$pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element, 
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos ++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }

// InstallMenuPage
}

FannieDispatch::conditionalExec(false);

?>
