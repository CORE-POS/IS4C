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

    /**
      Page has been updated to support themeing
    */
    public $themed = false;

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

    /**
      Include javascript necessary to integrate linea
      scanner device
    */
    protected $enable_linea = false;

    protected $error_text;

    /**
      Instance of configuration object
    */
    protected $config;

    /**
      Instance of logging object
    */
    protected $logger;

    /**
      Instance of DB connection object
    */
    protected $connection;

    public function __construct()
    {
        $auth_default = FannieConfig::config('AUTH_DEFAULT', false);
        $coop_id = FannieConfig::config('COOP_ID');
        if ($auth_default && !$this->must_authenticate) {
            $this->must_authenticate = $auth_default;
        }
        if (isset($coop_id) && $coop_id == 'WEFC_Toronto') {
            $this->auth_classes[] = 'admin';
        }
        /*
        */
    }

    /**
      DI Setter method for configuration
      @param $fc [FannieConfig] configuration object
    */
    public function setConfig(FannieConfig $fc)
    {
        $this->config = $fc;
    }

    /**
      DI Setter method for logging
      @param $fl [FannieLogger] logging object
    */
    public function setLogger(FannieLogger $fl)
    {
        $this->logger = $fl;
    }

    /**
      DI Setter method for database
      @param $sql [SQLManager] database object
    */
    public function setConnection(SQLManager $sql)
    {
        $this->connection = $sql;
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
        $url = $this->config->get('URL');
        ob_start();
        $page_title = $this->title;
        $header = $this->header;
        $headerConfig = $this->config;
        $BACKEND_NAME = $this->config->get('BACKEND_NAME', 'Fannie');
        if ($this->themed) {
            include(dirname(__FILE__) . '/../src/header.bootstrap.html');
            $this->addJQuery();
            if (!$this->addBootstrap()) {
                echo '<em>Warning: bootstrap does not appear to be installed. Try running composer update</em>';
            }
            $this->addScript($url . 'src/javascript/jquery-ui.js');
            $this->addScript($url . 'src/javascript/calculator.js');
            $this->addCssFile($url . 'src/javascript/jquery-ui.css?id=20140625');
            $this->addCssFile($url . 'src/css/configurable.php');
            $this->addCssFile($url . 'src/css/core.css');
            $this->addCssFile($url . 'src/css/print.css');
            $this->add_onload_command('standardFieldMarkup();');
        } else {
            include(dirname(__FILE__) . '/../src/header.html');
        }

        if ($this->enable_linea) {
            $this->addScript($url . 'src/javascript/linea/cordova-2.2.0.js');
            $this->addScript($url . 'src/javascript/linea/ScannerLib-Linea-2.0.0.js');
        }

        return ob_get_clean();
    }

    /**
      Add css and js files required for bootstrap.
      If a version installed via composer is present, that
      is the version used.
      @return [boolean] success
    */
    public function addBootstrap()
    {
        $url = $this->config->get('URL');
        $path1 = dirname(__FILE__) . '/../src/javascript/composer-components/';
        $path2 = dirname(__FILE__) . '/../src/javascript/';
        if (file_exists($path1 . 'bootstrap/js/bootstrap.min.js')) {
            $this->addScript($url . 'src/javascript/composer-components/bootstrap/js/bootstrap.min.js');
        } elseif (file_exists($path2 . 'bootstrap/js/bootstrap.min.js')) {
            $this->addScript($url . 'src/javascript/bootstrap/js/bootstrap.min.js');
        } else {
            return false; // bootstrap not found!
        }

        return true;
    }

    /**
      Add jquery js file to the page
      If present, the version installed via composer is used
      @return [boolean] success
    */
    public function addJQuery()
    {
        $url = $this->config->get('URL');
        $path1 = dirname(__FILE__) . '/../src/javascript/composer-components/';
        $path2 = dirname(__FILE__) . '/../src/javascript/';
        if (file_exists($path1 . 'jquery/jquery.min.js')) {
            $this->addFirstScript($url . 'src/javascript/composer-components/jquery/jquery.min.js');
        } elseif (file_exists($path2 . 'jquery.js')) {
            $this->addFirstScript($url . 'src/javascript/jquery.js');
        } else {
            return false;
        }

        return true;
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
        $FANNIE_AUTH_ENABLED = $this->config->get('AUTH_ENABLED');
        $FANNIE_URL = $this->config->get('URL');
        ob_start();
        if ($this->themed) {
            include(dirname(__FILE__) . '/../src/footer.bootstrap.html');
            $modal = $this->helpModal();
            if ($modal) {
                echo "\n" . $modal . "\n";
            }
        } else {
            include(dirname(__FILE__) . '/../src/footer.html');
        }

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

    protected function lineaJS()
    {
        ob_start();
        ?>
/**
  Enable linea scanner on page
  @param selector - jQuery selector for the element where
    barcode data should be entered
  @param callback [optional] function called after
    barcode scan

  If the callback is omitted, the parent <form> of the
  selector's element is submitted.
*/
function enableLinea(selector, callback)
{
    Device = new ScannerDevice({
        barcodeData: function (data, type){
            var upc = data.substring(0,data.length-1);
            if ($(selector).length > 0){
                $(selector).val(upc);
                if (typeof callback === 'function') {
                    callback();
                } else {
                    $(selector).closest('form').submit();
                }
            }
        },
        magneticCardData: function (track1, track2, track3){
        },
        magneticCardRawData: function (data){
        },
        buttonPressed: function (){
        },
        buttonReleased: function (){
        },
        connectionState: function (state){
        }
    });
    ScannerDevice.registerListener(Device);
}
        <?php

        return ob_get_clean();
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

    private function addFirstScript($file_url, $type='text/javascript')
    {
        $new = array($file_url => $type);
        foreach ($this->scripts as $url => $t) {
            $new[$url] = $t;
        }
        $this->scripts = $new;
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
        $redirect = $_SERVER['REQUEST_URI'];
        $url = $this->config->get('URL') . 'auth/ui/loginform.php';
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
        $url = $this->config->get('URL');
        $dbc = FannieDB::get($database);
        if (!$dbc->tableExists($table)) {
            $this->error_text = "<p>Missing table {$database}.{$table}
                            <br /><a href=\"{$url}install/\">Click Here</a> to
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
        $url = $this->config->get('URL');
        if ($this->tableExistsReadinessCheck($database, $table) === false) {
            return false;
        }

        $dbc = FannieDB::get($database);
        $definition = $dbc->tableDefinition($table);
        if (!isset($definition[$column])) {
            $this->error_text = "<p>Table {$database}.{$table} needs to be updated.
                            <br /><a href=\"{$url}install/InstallUpdatesPage.php\">Click Here</a> to
                            run updates.</p>";
            return false;
        }

        return true;
    }

    /**
      Helper method to wrap helpContent()
      in markup for a bootstrap modal dialog.
    */
    protected function helpModal()
    {
        $help = $this->helpContent();
        if (!$help) {
            return false;
        }
        $BACKEND_NAME = $this->config->get('BACKEND_NAME', 'Fannie');

        return '
            <div class="modal" id="help-modal" role="modal">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">
                                <span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
                            </button>
                            <h4>' . 
                                preg_replace('/^Fannie(.*)$/', $BACKEND_NAME . '$1', $this->title) . '
                            </h4>
                        </div>
                        <div class="modal-body">' . $help . '</div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>';
    }

    /**
      User-facing help text explaining how to 
      use a page.
      @return [string] html content
    */
    public function helpContent()
    {
        return false;
    }

    public function unitTest($phpunit)
    {

    }

    /**
      Check for input and display the page
    */
    public function drawPage()
    {
        if (!($this->config instanceof FannieConfig)) {
            $this->config = FannieConfig::factory();
        }

        if (!$this->checkAuth() && $this->must_authenticate) {
            $this->loginRedirect();
            exit;
        } elseif ($this->preprocess()) {

            /**
              Global setting overrides default behavior
              to force the menu to appear.
            */
            if ($this->config->get('WINDOW_DRESSING')) {
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
            if (!empty($js_content) || !empty($this->onload_commands) || $this->themed) {
                echo '<script type="text/javascript">';
                echo $js_content;
                echo "\n\$(document).ready(function(){\n";
                foreach($this->onload_commands as $oc)
                    echo $oc."\n";
                echo "});\n";
                if ($this->themed) {
                    ?>
function showBootstrapAlert(selector, type, msg)
{
    var alertbox = '<div class="alert alert-' + type + '" role="alert">';
    alertbox += '<button type="button" class="close" data-dismiss="alert">';
    alertbox += '<span>&times;</span></button>';
    alertbox += msg + '</div>';
    $(selector).append(alertbox);
}
function showBootstrapPopover(element, original_value, error_message)
{
    var timeout = 1500;
    if (error_message == '') {
        error_message = 'Saved!';
    } else {
        element.val(original_value);
        timeout = 3000;
    }
    element.popover({
        html: true,
        content: error_message,
        placement: 'auto bottom'
    });
    element.popover('show');
    setTimeout(function(){element.popover('destroy');}, timeout);
}
function mathField(elem)
{
    try {
        console.log(elem);
        console.log(elem.value);
        var newval = calculator.parse(elem.value);
        elem.value = newval;
    } catch (e) { }
}
function standardFieldMarkup()
{
    $('input.date-field').datepicker({
        dateFormat: 'yy-mm-dd',    
        changeYear: true,
        yearRange: "c-10:c+10",
    });
    $('input.math-field').change(function (event) {
        mathField(event.target);
    });
}
                    <?php
                }
                if ($this->enable_linea) {
                    echo $this->lineaJS();
                }
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

