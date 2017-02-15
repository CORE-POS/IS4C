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

namespace COREPOS\common\ui;

/**
  @class CorePage
  Class for drawing screens
*/
class CorePage 
{
    public $description = "
    Base class for creating HTML pages.
    ";

    protected $onload_commands = array();
    protected $scripts = array();
    protected $css_files = array();
    protected $error_text;

    public $discoverable = true;
    public $page_set = 'Misc';
    public $doc_link = '';
    protected $window_dressing = true;
    public $has_unit_tests = false;

    /**
      Instance of DB connection object
    */
    protected $connection;

    /**
      Instance of logging object
    */
    protected $logger;

    /**
      Instance of configuration object
    */
    protected $config;

    public function __construct()
    {
        $this->start_timestamp = microtime(true);
    }

    /**
      DI Setter method for configuration
      @param $fc [FannieConfig] configuration object
    */
    public function setConfig($fc)
    {
        $this->config = $fc;
    }

    /**
      DI Setter method for logging
      @param $fl [FannieLogger] logging object
    */
    public function setLogger($fl)
    {
        $this->logger = $fl;
    }

    /**
      DI Setter method for database
      @param $sql [SQLManager] database object
    */
    public function setConnection($sql)
    {
        $this->connection = $sql;
    }

    /**
      Toggle using menus
      @param $menus boolean
    */
    protected function hasMenus($menus)
    {
        $this->window_dressing = ($menus) ? true : false;
    }

    protected function has_menus($menus)
    {
        $this->hasMenus($menus);
    }

    /**
      preFlight is the very first method
      called when drawing a page - even
      prior to preprocess. Abstract children
      i.e., child classes that act as parents
      for grandchild classes, may use this method
      to add functionality.
      @return none
    */
    protected function preFlight()
    {
    }

    /**
      postFlight is called very near the end of
      execution. If the page was drawn, postFlight
      is called prior to the closing body and HTML
      tags. If the page was not drawn (preprocess
      returned false), postFlight is called before
      the script exits.
      @return none
    */
    protected function postFlight()
    {
    }

    /**
      Get the standard header
      @return An HTML string
    */
    protected function getHeader()
    {
        return '<!doctype html><html>';
    }

    protected function get_header()
    {
        return $this->getHeader();
    }

    /**
      Get the standard footer
      @return An HTML string
    */
    protected function getFooter()
    {
        return '</html>';
    }

    protected function get_footer()
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
    protected function javascript_content()
    {
    }

    protected function javascriptContent()
    {
        return $this->javascript_content();
    }

    /**
      Add a script to the page using <script> tags
      @param $file_url the script URL
      @param $type the script type
    */
    protected function addScript($file_url, $type='text/javascript')
    {
        $this->scripts[$file_url] = $type;
    }

    protected function addFirstScript($file_url, $type='text/javascript')
    {
        $new = array($file_url => $type);
        foreach ($this->scripts as $url => $t) {
            $new[$url] = $t;
        }
        $this->scripts = $new;
    }

    protected function add_script($file_url,$type="text/javascript")
    {
        $this->addScript($file_url, $type);
    }

    protected function add_css_file($file_url)
    {
        $this->css_files[] = $file_url;
    }

    protected function addCssFile($file_url)
    {
        $this->add_css_file($file_url);
    }

    /**
      Define any CSS needed
      @return A CSS string
    */
    protected function css_content()
    {
    }

    protected function cssContent()
    {
        return $this->css_content();
    }

    /**
      Queue javascript commands to run on page load
    */
    protected function add_onload_command($str)
    {
        $this->onload_commands[] = $str;    
    }

    protected function addOnloadCommand($str)
    {
        $this->add_onload_command($str);
    }

    /**
      Check if there are any problems
      that might prevent the page from working
      properly.
    */
    protected function readinessCheck()
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
    protected function tableExistsReadinessCheck($database, $table)
    {
        $url = $this->config->get('URL');
        $dbc = $this->connection;
        $dbc->selectDB($database);
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
    protected function tableHasColumnReadinessCheck($database, $table, $column)
    {
        $url = $this->config->get('URL');
        if ($this->tableExistsReadinessCheck($database, $table) === false) {
            return false;
        }

        $dbc = $this->connection;
        $dbc->selectDB($database);
        $definition = $dbc->tableDefinition($table);
        if (!isset($definition[$column])) {
            $this->error_text = "<p>Table {$database}.{$table} needs to be updated.
                            <br /><a href=\"{$url}install/InstallUpdatesPage.php\">Click Here</a> to
                            run updates.</p>";
            return false;
        }

        return true;
    }

    public function unitTest($phpunit)
    {

    }

    public function draw_page()
    {
        $this->drawPage();
    }

    /**
      Check for input and display the page
    */
    public function drawPage()
    {
        $this->preFlight();
        if ($this->preprocess()) {

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
                echo $this->getFooter();
            } else {
                $body = str_ireplace('</html>','',$body);
                $body = str_ireplace('</body>','',$body);
                echo $body;
            }

            $this->writeJS();
            
            echo array_reduce($this->css_files,
                function ($carry, $css_url) {
                    return $carry . sprintf('<link rel="stylesheet" type="text/css" href="%s">' . "\n",
                                    $css_url);
                },
                ''
            );
            
            $page_css = $this->css_content();
            if (!empty($page_css)) {
                echo '<style type="text/css">';
                echo $page_css;
                echo '</style>';
            }

            $this->postFlight();

            echo '</body></html>';
        } else {
            $this->postFlight();
        }
    }

    protected function writeJS()
    {
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
            echo array_reduce($this->onload_commands, function($carry, $oc) { return $carry . $oc . "\n"; }, '');
            echo "});\n";
            echo '</script>';
        }
    }

    public function baseTest($phpunit)
    {
        $phpunit->assertEquals($this->getHeader(), $this->get_header());
        $phpunit->assertEquals($this->getFooter(), $this->get_footer());
        $this->addCssFile('/url.css');
        $this->addScript('/url.css');
        ob_start();
        $this->drawPage();
        $phpunit->assertNotEquals(0, strlen(ob_get_clean())); 

        include(dirname(__FILE__) . '/../../fannie/config.php');
        $this->connection = new \COREPOS\common\SQLManager($FANNIE_SERVER, $FANNIE_SERVER_DBMS, $FANNIE_OP_DB, $FANNIE_SERVER_USER, $FANNIE_SERVER_PW, true);
        $this->config = \FannieConfig::factory();
        $phpunit->assertEquals(true, $this->tableExistsReadinessCheck($FANNIE_OP_DB, 'products'));
        $phpunit->assertEquals(false, $this->tableExistsReadinessCheck($FANNIE_OP_DB, 'NOTproducts'));
        $phpunit->assertEquals(true, $this->tableHasColumnReadinessCheck($FANNIE_OP_DB, 'products', 'upc'));
        $phpunit->assertEquals(false, $this->tableHasColumnReadinessCheck($FANNIE_OP_DB, 'products', 'NOTupc'));
    }
}

