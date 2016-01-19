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
include(dirname(__FILE__) . '/../config.php'); 
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(dirname(__FILE__) . '/util.php');
}
if (!function_exists('dropDeprecatedStructure')) {
    include(dirname(__FILE__) . '/db.php');
}

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

    function body_content()
    {
        include(dirname(__FILE__) . '/../config.php'); 
        ob_start();
        ?>
        <?php
        echo showInstallTabs('Menu');
        ?>

        <form action=InstallMenuPage.php method=post>
        <?php
        echo $this->writeCheck(dirname(__FILE__) . '/../config.php');
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
            include(dirname(__FILE__) . '/../src/init_menu.php');
            $FANNIE_MENU = $INIT_MENU;
        } else {
            foreach ($FANNIE_MENU as $menu => $content) {
                if (!in_array($menu, $VALID_MENUS)) {
                    // menu is not valid
                    // reset to default
                    // obviously not ideal error recovery
                    include(dirname(__FILE__) . '/../src/init_menu.php');
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
<?php echo \COREPOS\Fannie\API\lib\FannieUI::prettyJSON(json_encode($FANNIE_MENU)); ?>
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

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->body_content()));
    }

// InstallMenuPage
}

FannieDispatch::conditionalExec();

