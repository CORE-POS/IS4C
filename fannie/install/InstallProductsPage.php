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
    @class InstallProductsPage
    Class for the Products install and config options
*/
class InstallProductsPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie: Products Settings';
    protected $header = 'Fannie: Products Settings';

    public $description = "
    Class for the Products install and config options page.
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

    /**
      Define any CSS needed
      @return A CSS string
    */
    function css_content(){
        $css = '
            tr.hilite {
                background-color: #5CAD5C;
            }
        ';
        return $css;
    //css_content()
    }

    /**
      Define any javascript needed
      @return A javascript string
    function javascript_content(){
        $js ="";
        return $js;
    }
    */

    function body_content()
    {
        include(dirname(__FILE__) . '/../config.php');

        ob_start();

        echo showInstallTabs('Products');
?>

        <form action=InstallProductsPage.php method=post>
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
        }
        ?>
        <hr />
        <h4 class="install">Product Information Modules</h4>
        The product editing interface displayed after you select a product at:
        <br /><a href="<?php echo $FANNIE_URL; ?>item/" target="_item"><?php echo $FANNIE_URL; ?>item/</a>
        <br />consists of fields grouped in several sections, called modules, listed below.
        <br />The enabled (active) ones are highlighted.
        <br />The <i>Show</i> setting controls whether or not the module is displayed. The <i>Auto</i>
              means only display the module if it is relevant to the current item.
        <br />The <i>Expand</i> setting controls whether the module is intially expanded or collapsed.
             The <i>Auto</i> option means display expanded if relevant to the current item.
        <br />
        <br /><b>Available Modules</b> <br />
        <?php
        $mods = FannieAPI::ListModules('ItemModule',True);
        sort($mods);
        ?>
        <table class="table">
        <tr>
            <th>Name</th>
            <th>Position</th>
            <th>Show</th>
            <th>Expand</th>
        </tr>
        <?php
        /**
          Change by Andy 2Jun14
          Store modules in a keyed array.
          Format:
           - module_name => settings array
             + seq [int] display order
             + show [int] yes/no/auto
             + expand [int] yes/no/auto

          The settings for each module control
          how it is displayed. The "auto" option
          will only print or expand a module if
          it is relevant for that particular item.
        */
        $in_mods = FormLib::get('_pm', array());
        $in_seq = FormLib::get('_pmSeq', array());
        $in_show = FormLib::get('_pmShow', array());
        $in_exp = FormLib::get('_pmExpand', array());
        for ($i=0; $i<count($in_mods); $i++) {
            if (!isset($in_show[$i]) || $in_show[$i] == 0) {
                if (isset($FANNIE_PRODUCT_MODULES[$in_mods[$i]])) {
                    unset($FANNIE_PRODUCT_MODULES[$in_mods[$i]]);
                }
                continue;
            }
            $FANNIE_PRODUCT_MODULES[$in_mods[$i]] = array(
                'seq' => isset($in_seq[$i]) ? $in_seq[$i] : 0,
                'show' => isset($in_show[$i]) ? $in_show[$i] : 0,
                'expand' => isset($in_exp[$i]) ? $in_exp[$i] : 0,
            );
        }

        /*
          Convert old settings to new format.
        */
        $legacy_indexes = array();
        $replacement_values = array();
        foreach ($FANNIE_PRODUCT_MODULES as $id => $m) {
            if (preg_match('/^\d+$/', $id)) {
                // old setting. convert to new.
                $legacy_indexes[] = $id;
                $replacement_values[$m] = array(
                    'seq' => $id,
                    'show' => 1,
                    'expand' => 1,
                );
            }
        }
        foreach ($legacy_indexes as $index) {
            unset($FANNIE_PRODUCT_MODULES[$index]);
        }
        foreach ($replacement_values as $name => $params) {
            $FANNIE_PRODUCT_MODULES[$name] = $params;
        }

        // set a default if needed
        if (count($FANNIE_PRODUCT_MODULES) == 0) {
            $FANNIE_PRODUCT_MODULES['BaseItemModule'] = array(
                'seq' => 0,
                'show' => 1,
                'expand' => 1,
            );
        }

        $default = array('seq' => 0, 'show' => 0, 'expand' => 0);
        $opts = array('No', 'Yes', 'Auto');
        foreach ($mods as $module) {
            $css = isset($FANNIE_PRODUCT_MODULES[$module]) ? 'class="info"' : '';
            printf('<tr %s><td>%s<input type="hidden" name="_pm[]" value="%s" /></td>', $css, $module, $module);
            $params = isset($FANNIE_PRODUCT_MODULES[$module]) ? $FANNIE_PRODUCT_MODULES[$module] : $default;
            printf('<td><input type="number" class="form-control" name="_pmSeq[]" value="%d" /></td>', $params['seq']);
            echo '<td><select name="_pmShow[]" class="form-control">';
            foreach ($opts as $id => $label) {
                printf('<option %s value="%d">%s</option>',
                    $id == $params['show'] ? 'selected' : '',
                    $id, $label);
            }
            echo '</select></td>';
            echo '<td><select name="_pmExpand[]" class="form-control">';
            foreach ($opts as $id => $label) {
                printf('<option %s value="%d">%s</option>',
                    $id == $params['expand'] ? 'selected' : '',
                    $id, $label);
            }
            echo '</select></td>';
            echo '</tr>';
        }
        $saveStr = 'array(';
        foreach ($FANNIE_PRODUCT_MODULES as $name => $info) {
            $saveStr .= sprintf("'%s'=>array('seq'=>%d,'show'=>%d,'expand'=>%d),",
                                $name, $info['seq'],
                                $info['show'], $info['expand']
            );
        }
        $saveStr = substr($saveStr, 0, strlen($saveStr)-1) . ')';
        confset('FANNIE_PRODUCT_MODULES', $saveStr);
        ?>
        </table>
        <hr />
        <label>Default Batch View</label>
        <?php
        $batch_opts = array(
            'all' => 'All',
            'current' => 'Current',
            'Pending' => 'Pending',
            'Historical' => 'Historical',
        ); 
        echo installSelectField('FANNIE_BATCH_VIEW', $FANNIE_BATCH_VIEW, $batch_opts, 'all');
        ?>
        <hr />
        <label>Default Reporting Departments View</label>
        <?php
        $report_opts = array(
            'range' => 'Range of Departments',
            'multi' => 'Multi Select',
        ); 
        echo installSelectField('FANNIE_REPORT_DEPT_MODE', $FANNIE_REPORT_DEPT_MODE, $report_opts, 'range');
        ?>
        <hr />
        <label>Default Shelf Tag Layout</label>
        <?php
        $layouts = 'No Layouts Found!';
        if (file_exists($FANNIE_ROOT.'admin/labels/scan_layouts.php') && !function_exists('scan_layouts')){
            include($FANNIE_ROOT.'admin/labels/scan_layouts.php');
            $layouts = scan_layouts();
        }
        echo installSelectField('FANNIE_DEFAULT_PDF', $FANNIE_DEFAULT_PDF, $layouts, 'Fannie Standard');
        ?>
        <label>Shelf Tag Data Source</label>
        <?php
        $mods = FannieAPI::listModules('TagDataSource');
        $source = array('' => 'Default');
        foreach ($mods as $m) {
            $source[$m] = $m;
        }
        echo installSelectField('FANNIE_TAG_DATA_SOURCE', $FANNIE_TAG_DATA_SOURCE, $source);
        ?>
        <label>Default Signage Layout</label>
        <?php
        $mods = FannieAPI::listModules('\COREPOS\Fannie\API\item\FannieSignage');
        echo installSelectField('FANNIE_DEFAULT_SIGNAGE', $FANNIE_DEFAULT_SIGNAGE, $mods);
        ?>
        <label>Default Account Coding</label>
        <?php
        $mods = array('\COREPOS\Fannie\API\item\Accounting', '\COREPOS\Fannie\API\item\StandardAccounting');
        $mods = array_merge($mods, FannieAPI::listModules('\COREPOS\Fannie\API\item\Accounting'));
        echo installSelectField('FANNIE_ACCOUNTING_MODULE', $FANNIE_ACCOUNTING_MODULE, $mods);
        ?>
        <hr />
        <h4 class="install">Service Scale Integration</h4>
        <p class='ichunk' style="margin:0.4em 0em 0.4em 0em;"><b>Data Gate Weigh directory</b>
        <?php
        echo installTextField('FANNIE_DGW_DIRECTORY', $FANNIE_DGW_DIRECTORY, '');
        if ($FANNIE_DGW_DIRECTORY !== '') {
            if (is_writable($FANNIE_DGW_DIRECTORY)) {
                echo "<div class=\"alert alert-success\">$FANNIE_DGW_DIRECTORY is writable</div>";
            } elseif (!file_exists($FANNIE_DGW_DIRECTORY)) {
                echo "<div class=\"alert alert-danger\">$FANNIE_DGW_DIRECTORY does not exist</div>";
            } else {
                echo "<div class=\"alert alert-danger\">$FANNIE_DGW_DIRECTORY is not writable</div>";
            }
        }
        ?>
        <p class='ichunk' style="margin:0.4em 0em 0.4em 0em;"><b>E-Plum directory</b>
        <?php
        echo installTextField('FANNIE_EPLUM_DIRECTORY', $FANNIE_EPLUM_DIRECTORY, '');
        if ($FANNIE_EPLUM_DIRECTORY !== '') {
            if (is_writable($FANNIE_EPLUM_DIRECTORY)) {
                echo "<div class=\"alert alert-success\">$FANNIE_EPLUM_DIRECTORY is writable</div>";
            } elseif (!file_exists($FANNIE_EPLUM_DIRECTORY)) {
                echo "<div class=\"alert alert-danger\">$FANNIE_EPLUM_DIRECTORY does not exist</div>";
            } else {
                echo "<div class=\"alert alert-danger\">$FANNIE_EPLUM_DIRECTORY is not writable</div>";
            }
        }
        ?>

        <hr />
        <h4 class="install">Product Editing</h4>
        <p class='ichunk' style="margin:0.4em 0em 0.4em 0em;"><b>Compose Product Description</b>: 
        <?php
        echo installSelectField('FANNIE_COMPOSE_PRODUCT_DESCRIPTION', $FANNIE_COMPOSE_PRODUCT_DESCRIPTION,
                    array(1 => 'Yes', 0 => 'No'), 0);
        ?>
        <br />If No products.description, which appears on the receipt, will be used as-is.
        <br />If Yes it will be shortened enough hold a "package" description made by
        concatenating products.size and products.unitofmeasure so that the whole
        string is still 30 or less characters:
        <br /> "Eden Seville Orange Marma 500g"
        </p>

        <p class='ichunk' style="margin:0.0em 0em 0.4em 0em;"><b>Compose Long Product Description</b>: 
        <?php
        echo installSelectField('FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION', $FANNIE_COMPOSE_LONG_PRODUCT_DESCRIPTION,
                    array(1 => 'Yes', 0 => 'No'), 0);
        ?>
        <br />If No productUser.description, which may be used in Product Verification, will be used as-is.
        <br />If Yes productUser.brand will be prepended and a "package" description made by
        concatenating products.size and products.unitofmeasure will be appended:
        <br /> "EDEN | Marmalade, Orange, Seville, Rough-Cut | 500g"<br />
        </p>

        <hr />
        <p>
            <button type="submit" class="btn btn-default">Save Configuration</button>
        </p>
        </form>

        <?php

        return ob_get_clean();

    // body_content
    }

// InstallProductsPage
}

FannieDispatch::conditionalExec(false);

?>
