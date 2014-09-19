<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

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

if (!class_exists('FannieAuth')) {
    include(dirname(__FILE__).'/auth/FannieAuth.php');
}

/**
  @class FanniePage
  Class for drawing screens
*/
class FanniePage 
{

    public $required = True;

    public $description = "
    Base class for creating HTML pages.
    ";

    public $discoverable = true;

    public $page_set = 'Misc';

    public $doc_link = '';

    /** force users to login immediately */
    protected $must_authenticate = False;
    /** name of the logged in user (or False is no one is logged in) */
    protected $current_user = False;
    /** list of either auth_class(es) or array(auth_class, start, end) tuple(s) */
    protected $auth_classes = array();

    protected $title = 'Page window title';
    protected $header = 'Page displayed header';
    protected $window_dressing = True;
    protected $onload_commands = array();
    protected $scripts = array();
    protected $css_files = array();

    protected $error_text;

    public function __construct()
    {
        global $FANNIE_AUTH_DEFAULT, $FANNIE_COOP_ID;
        if (isset($FANNIE_AUTH_DEFAULT) && !$this->must_authenticate) {
            $this->must_authenticate = $FANNIE_AUTH_DEFAULT;
        }
        if (isset($FANNIE_COOP_ID) && $FANNIE_COOP_ID == 'WEFC_Toronto') {
            $this->auth_classes[] = 'admin';
        }
        /*
        */
    }

    /**
      Toggle using menus
      @param $menus boolean
    */
    public function hasMenus($menus)
    {
        $this->window_dressing = ($menus) ? true : false;
    }

    public function has_menus($menus)
    {
        $this->hasMenus($menus);
    }

    /**
      Get the standard header
      @return An HTML string
    */
    public function getHeader()
    {
        global $FANNIE_ROOT;
        ob_start();
        $page_title = $this->title;
        $header = $this->header;
        include($FANNIE_ROOT.'src/header.html');

        return ob_get_clean();
    }
    public function get_header()
    {
        return $this->getHeader();
    }

    /**
      Get the standard footer
      @return An HTML string
    */
    public function getFooter()
    {
        global $FANNIE_ROOT, $FANNIE_AUTH_ENABLED, $FANNIE_URL;
        ob_start();
        include($FANNIE_ROOT.'src/footer.html');

        return ob_get_clean();
    }
    public function get_footer()
    {
        return $this->getFooter();
    }

    /**
      Handle pre-display tasks such as input processing
      @return
       - True if the page should be displayed
       - False to stop here

      Common uses include redirecting to a different module
      and altering body content based on input
    */
    public function preprocess()
    {
        return true;
    }
    
    /**
      Define the main displayed content
      @return An HTML string
    */
    public function body_content()
    {

    }

    public function bodyContent()
    {
        return $this->body_content();
    }

    public function errorContent()
    {
        return $this->error_text;
    }

    /**
      Define any javascript needed
      @return A javascript string
    */
    public function javascript_content(){

    }

    public function javascriptContent()
    {
        return $this->javascript_content();
    }

    /**
      Add a script to the page using <script> tags
      @param $file_url the script URL
      @param $type the script type
    */
    public function addScript($file_url, $type='text/javascript')
    {
        $this->scripts[$file_url] = $type;
    }

    public function add_script($file_url,$type="text/javascript")
    {
        $this->addScript($file_url, $type);
    }

    public function add_css_file($file_url)
    {
        $this->css_files[] = $file_url;
    }

    public function addCssFile($file_url)
    {
        $this->add_css_file($file_url);
    }

    /**
      Define any CSS needed
      @return A CSS string
    */
    public function css_content()
    {

    }

    public function cssContent()
    {
        return $this->css_content();
    }

    /**
      Queue javascript commands to run on page load
    */
    public function add_onload_command($str)
    {
        $this->onload_commands[] = $str;    
    }

    public function addOnloadCommand($str)
    {
        $this->add_onload_command($str);
    }

    /**
      Send user to login page
    */
    public function loginRedirect()
    {
        global $FANNIE_URL;
        $redirect = $_SERVER['REQUEST_URI'];
        $url = $FANNIE_URL.'auth/ui/loginform.php';
        header('Location: '.$url.'?redirect='.$redirect);
    }

    public function login_redirect()
    {
        $this->loginRedirect();
    }

    /**
      Check if the user is logged in
    */
    public function checkAuth()
    {
        foreach($this->auth_classes as $class) {
            $try = false;
            if (is_array($class) && count($class) == 3) {
                $try = FannieAuth::validateUserQuiet($class[0],$class[1],$class[2]);
            } else {
                $try = FannieAuth::validateUserQuiet($class);
            }
            if ($try) {
                $this->current_user = $try;
                return true;
            }
        }
        $try = FannieAuth::checkLogin();
        if ($try && empty($this->auth_classes)) {
            $this->current_user = $try;
            return true;
        }

        return False;
    }

    public function check_auth()
    {
        return $this->checkAuth();
    }

    public function draw_page()
    {
        $this->drawPage();
    }

    /**
      Check if there are any problems
      that might prevent the page from working
      properly.
    */
    public function readinessCheck()
    {
        return true;
    }

    /**
      Helper method
      Check if a given table exists. Sets an appropriate message
      in $error_text if the table is not present.
      @param $database [string] database name
      @param $table [string] table name
      @return [boolean]
    */
    public function tableExistsReadinessCheck($database, $table)
    {
        global $FANNIE_URL;
        $dbc = FannieDB::get($database);
        if (!$dbc->tableExists($table)) {
            $this->error_text = "<p>Missing table {$database}.{$table}
                            <br /><a href=\"{$FANNIE_URL}install/\">Click Here</a> to
                            create necessary tables.</p>";
            return false;
        }

        return true;
    }

    /**
      Helper method
      Check if a given table has a specific column. 
      Sets an appropriate message
      in $error_text if the column (or table) is not present.
      @param $database [string] database name
      @param $table [string] table name
      @param $column [string] column name
      @return [boolean]
    */
    public function tableHasColumnReadinessCheck($database, $table, $column)
    {
        global $FANNIE_URL;
        if ($this->tableExistsReadinessCheck($database, $table) === false) {
            return false;
        }

        $dbc = FannieDB::get($database);
        $definition = $dbc->tableDefinition($table);
        if (!isset($definition[$column])) {
            $this->error_text = "<p>Table {$database}.{$table} needs to be updated.
                            <br /><a href=\"{$FANNIE_URL}install/InstallUpdatesPage.php\">Click Here</a> to
                            run updates.</p>";
            return false;
        }

        return true;
    }

    /**
      Check for input and display the page
    */
    public function drawPage()
    {
        global $FANNIE_WINDOW_DRESSING;

        if (!$this->checkAuth() && $this->must_authenticate) {
            $this->loginRedirect();
            exit;
        } elseif ($this->preprocess()) {

            /**
              Global setting overrides default behavior
              to force the menu to appear.
            */
            if (isset($FANNIE_WINDOW_DRESSING) && $FANNIE_WINDOW_DRESSING) {
                $this->window_dressing = true;
            }
            
            if ($this->window_dressing) {
                echo $this->getHeader();
            }
            
            if ($this->readinessCheck() !== false) {
                $body = $this->bodyContent();
            } else {
                $body = $this->errorContent();
            }

            if ($this->window_dressing) {
                echo $body;
                $footer = $this->getFooter();
                $footer = str_ireplace('</html>','',$footer);
                $footer = str_ireplace('</body>','',$footer);
                echo $footer;
            } else {
                $body = str_ireplace('</html>','',$body);
                $body = str_ireplace('</body>','',$body);
                echo $body;
            }

            foreach($this->scripts as $s_url => $s_type) {
                printf('<script type="%s" src="%s"></script>',
                    $s_type, $s_url);
                echo "\n";
            }
            
            $js_content = $this->javascriptContent();
            if (!empty($js_content) || !empty($this->onload_commands)) {
                echo '<script type="text/javascript">';
                echo $js_content;
                echo "\n\$(document).ready(function(){\n";
                foreach($this->onload_commands as $oc)
                    echo $oc."\n";
                echo "});\n";
                echo '</script>';
            }

            foreach($this->css_files as $css_url) {
                printf('<link rel="stylesheet" type="text/css" href="%s">',
                    $css_url);
                echo "\n";
            }
            
            $page_css = $this->css_content();
            if (!empty($page_css)) {
                echo '<style type="text/css">';
                echo $page_css;
                echo '</style>';
            }

            echo '</body></html>';
        }
    }
}

