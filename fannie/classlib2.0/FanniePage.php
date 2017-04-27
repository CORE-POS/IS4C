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

/**
  @class FanniePage
  Class for drawing screens
*/
class FanniePage extends \COREPOS\common\ui\CorePage 
{
    /**
      Page uses newer bootstrap based UI
    */
    public $themed = true;

    /** force users to login immediately */
    protected $must_authenticate = false;
    /** name of the logged in user (or False is no one is logged in) */
    protected $current_user = false;
    /** list of either auth_class(es) or array(auth_class, start, end) tuple(s) */
    protected $auth_classes = array();

    protected $title = 'Page window title';
    protected $header = 'Page displayed header';

    /** wrapper around $_SESSION superglobal **/
    protected $session;

    /**
      Include javascript necessary to integrate linea
      scanner device
    */
    protected $enable_linea = false;

    public function __construct()
    {
        $this->start_timestamp = microtime(true);

        $auth_default = FannieConfig::config('AUTH_DEFAULT', false);
        $coop_id = FannieConfig::config('COOP_ID');
        if ($auth_default && !$this->must_authenticate) {
            $this->must_authenticate = $auth_default;
        }
        if (isset($coop_id) && $coop_id == 'WEFC_Toronto') {
            $this->auth_classes[] = 'admin';
        }

        $path = realpath(__DIR__ . '/../');
        $this->session = new COREPOS\common\NamedSession($path);
    }

    public function preprocess()
    {
        $ret = parent::preprocess();
        /**
          Global setting overrides default behavior
          to force the menu to appear.
        */
        if ($this->config->get('WINDOW_DRESSING')) {
            $this->window_dressing = true;
        }

        return $ret;
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
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // windows has trouble with symlinks
                $this->addScript($url . 'src/javascript/jquery-ui-1.10.4/js/jquery-ui-1.10.4.min.js');
            } else {
                $this->addScript($url . 'src/javascript/jquery-ui.js');
            }
            $this->addScript($url . 'src/javascript/calculator.js');
            $this->addScript($url . 'src/javascript/core.js');
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // windows has trouble with symlinks
                $this->addCssFile($url . 'src/javascript/jquery-ui-1.10.4/css/smoothness/jquery-ui.min.css?id=20140625');
            } else {
                $this->addCssFile($url . 'src/javascript/jquery-ui.css?id=20140625');
            }
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
            $this->addScript($url . 'src/javascript/linea/WebHub.js');
            $this->addScript($url . 'src/javascript/linea/core.js');
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
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // windows has trouble with symlinks
            $this->addFirstScript($url . 'src/javascript/jquery-1.11.1/jquery-1.11.1.min.js');
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
        $START_TIMESTAMP = $this->start_timestamp;
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

    protected function lineaJS()
    {
        ob_start();
        ?>
function lineaBarcode(upc, selector, callback) {
    upc = upc.substring(0,upc.length-1);
    if ($(selector).length > 0){
        $(selector).val(upc);
        if (typeof callback === 'function') {
            callback();
        } else {
            $(selector).closest('form').submit();
        }
    }
}
var IPC_PARAMS = { selector: false, callback: false };
function ipcWrapper(upc, typeID, typeStr) {
    lineaBarcode(upc, IPC_PARAMS.selector, IPC_PARAMS.callback);
}
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
        barcodeData: function(data, type) {
            var upc = data.substring(0,data.length-1);
            if (upc.length == 6) {
                upc = '0' + upc;
            }
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

    if (typeof WebBarcode == 'object') {
        WebBarcode.onBarcodeScan(function(ev) {
            var data = ev.value;
            lineaBarcode(data, selector, callback);
        });
    }

    // for webhub
    WebHub.Settings.set({
        barcodeFunction: function (upc, typeID, typeStr) {
            upc = upc.substring(0,upc.length-1);
            if ($(selector).length > 0){
                $(selector).val(upc);
                if (typeof callback === 'function') {
                    callback();
                } else {
                    $(selector).closest('form').submit();
                }
            }
        }
    });

    function lineaSilent()
    {
        if (typeof cordova.exec != 'function') {
            setTimeout(lineaSilent, 100);
        } else {
            if (Device) {
                Device.setScanBeep(false, []);
            }
        }
    }
    lineaSilent();
}
        <?php

        return ob_get_clean();
    }

    /**
      Send user to login page
    */
    public function loginRedirect()
    {
        $redirect = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
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

        return false;
    }

    public function check_auth()
    {
        return $this->checkAuth();
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
        $this->addOnloadCommand("\$('.modal').draggable({handle:'.modal-header'});\n");

        return '
            <div class="modal" id="help-modal" role="modal">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close close-btn" data-dismiss="modal">
                                <span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
                            </button>
                            <h4>' . 
                                preg_replace('/^Fannie(.*)$/', $BACKEND_NAME . '$1', $this->title) . '
                            </h4>
                        </div>
                        <div class="modal-body">' . $help . '</div>
                        <div id="help-feedback" class="container col-sm-6 collapse">
                            <input type="hidden" name="page" class="help-feedback" value="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" />
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="help-feedback form-control" 
                                    placeholder="Optional; if blank you won\'t get a response" />
                            </div>
                            <div class="form-group">
                                <label>How could "Help" on this page be improved</label>
                                <textarea name="comments" class="form-control help-feedback" rows="10"></textarea>
                            </div>
                            <div class="form-group">
                                <button type="button" class="btn btn-default" onclick="
                                    $.ajax({ method: \'post\', url: \'' . $this->config->URL . 'admin/HelpPopup.php\',
                                        data: $(\'.help-feedback\').serialize() })
                                    .always(function() { $(\'#help-feedback\').hide(); $(\'#help-feedback-done\').show(); });
                                ">
                                    Send Feedback
                                </button>
                            </div>
                        </div>
                        <div id="help-feedback-done" class="collapse">
                            <div class="alert alert-success">Feedback submitted</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" id="feedback-btn" class="btn btn-default" 
                                onclick="$(\'#help-feedback\').show();$(this).hide();">Feedback</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal"
                                onclick="var helpWindow=window.open(\''. $this->config->URL . 'admin/HelpPopup.php\',
                                \'CORE Help\', \'height=500,width=300,scrollbars=1,resizable=1\');"
                                id="popout-btn">Pop Out</button>
                            <button type="button" class="btn btn-default close-btn" data-dismiss="modal">Close</button>
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
        return '<!-- need doc -->
            <h3>Oh no!</h3>
            <p>This page hasn\'t been documented</p>';
    }

    public function preFlight()
    {
        if (!($this->config instanceof FannieConfig)) {
            $this->config = FannieConfig::factory();
        }

        if (!$this->checkAuth() && $this->must_authenticate) {
            $this->loginRedirect();
            exit;
        }
    }

    public function setPermissions($p)
    {
        $this->auth_classes = array($p);
    }

    protected $twig = null;
    public function setTwig($t)
    {
        $this->twig = $t;
    }
}

