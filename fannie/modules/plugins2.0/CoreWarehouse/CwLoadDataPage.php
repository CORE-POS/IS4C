<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CwLoadDataPage extends FanniePage {

    protected $title = 'Core Warehouse : Load Data';
    protected $header = 'Core Warehouse : Load Data';

    public $page_set = 'Plugin :: Core Warehouse';
    public $description = '[Core Warehouse Load Data] pull historical transaction data into
    the warehouse storage tables.';
    public $themed = true;

    function preprocess(){
        global $FANNIE_PLUGIN_SETTINGS, $FANNIE_ARCHIVE_DB;
        $month = FormLib::get_form_value('month', False);
        $year = FormLib::get_form_value('year', False);
        $model = FormLib::get_form_value('model', False);

        if ($month && $year && $model){
            $class = $model.'Model';
            if (!class_exists($class))
                include_once('models/'.$class.'.php');
            $con = FannieDB::get($FANNIE_PLUGIN_SETTINGS['WarehouseDatabase']);
            $obj = new $class($con);
            $obj->reload($FANNIE_ARCHIVE_DB, $month, $year);
        }
        return True;
    }

    public function getModels(){
        $dir = opendir(dirname(__FILE__).'/models');
        $ret = array();
        while(($file=readdir($dir)) !== False){
            if ($file[0] == '.') continue;
            if (substr($file,-9) != 'Model.php') continue;
            if ($file == 'CoreWarehouseModel.php') continue;
            $ret[] = substr($file,0,strlen($file)-4);
        }
        rsort($ret);

        return $ret;
    }

    function body_content(){
        ob_start();
        ?>
        <form action="CwLoadDataPage.php" method="post">
        <div class="form-group form-inline">
        <label>Table</label>
        <select name="model" class="form-control">
        <?php 
        foreach($this->getModels() as $file){
            printf('<option>%s</option>',
                substr($file,0,strlen($file)-10));
        }
        ?>
        </select>
        </div>
        <div class="form-group form-inline">
            <label>Month</label>
        <select name="month" class="form-control">
        <?php for ($i=1;$i<=12;$i++){
            printf('<option value="%d">%s</option>',
                $i,date('F',mktime(0,0,0,$i,1)));
        } ?>
        </select>
        <input type="number" class="form-control" name="year" 
            required min="1900" max="2999" step="1"
            value="<?php echo date('Y'); ?>" />
        </div>
        <p>
            <button type="submit" class="btn btn-default">Reoad Data</button>
        </p>
        </form>
        <div class="well">
        You can use this page as a command line tool, too. It's a better option if
        there's <b>lots</b> of data to deal with.
        </div>
        <?php
        return ob_get_clean();
    }
}

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])){
    $obj = new CwLoadDataPage();
    if (php_sapi_name() != 'cli'){
        $obj->draw_page();
    }
    else {
        function print_cli_help(){
            echo "Usage: php CwReloadDataPage.php [-a || -m <model file>]\n";
            echo "\t[ -d <year-month-day] || [-s <start month> <start year> [-e <end month> <end year>]]\n";
            echo "Specify a single date or a range of months.\n";
        }
        function check_date($argv, $i, $type)
        {
            if (!isset($argv[$i+1])){
                throw new Exception("Missing $type month\n");
            } elseif (!isset($argv[$i+2])) {
                throw new Exception("Missing $type year\n");
            }
            $start = array($argv[$i+1],$argv[$i+2]);
            if ($start[0] < 1 || $start[0] > 12){
                throw new Exception("Invalid $type month\n");
            } elseif ($start[1] < 1950 || $start[1] > date('Y')){
                throw new Exception("Invalid $type year\n");
            }

            return $start;
        }
        $start = array();
        $end = array();
        $day = array();
        $file = False;
        $all = False;
        $models = $obj->getModels();
        for ($i=1;$i<count($argv);$i++){
            switch($argv[$i]){
            case '-s':
            case '--start':
                try {
                    $start = check_date($argv, $i, 'start');
                    $i += 2;
                } catch (Exception $ex) {
                    print_cli_help();
                    echo $ex->getMessage();
                    return 1;
                }
                break;
            case '-e':
            case '--end':
                try {
                    $end = check_date($argv, $i, 'end');
                    $i += 2;
                } catch (Exception $ex) {
                    print_cli_help();
                    echo $ex->getMessage();
                    return 1;
                }
                break;
            case '-m':
            case '--model-file':
                if (!isset($argv[$i+1])){
                    print_cli_help();
                    echo "No file given\n";
                    return 1;   
                }
                $file = $argv[$i+1];
                $i+=1;
                if (!file_exists($file)){
                    print_cli_help();
                    echo "File does not exist: $file\n";
                    return 1;   
                }
                $file = basename($file);
                break;
            case '-d':
            case '--date':
                if (!isset($argv[$i+1])){
                    print_cli_help();
                    echo "No date provided\n";
                    return 1;   
                }
                $date = $argv[$i+1];
                $i+=1;
                $tmp = explode('-',$date);
                if (count($tmp) != 3){
                    print_cli_help();
                    echo "Date format is YYYY-MM-DD\n";
                    return 1;   
                }
                $date = array($tmp[0],$tmp[1],$tmp[2]);
                break;
            case '-a':
            case '--all':
                $all = True;
                break;
            case '-h':
            case '-?':
            case '--help':
            default:
                print_cli_help();
                return 0;   
                break;
            }
        }

        if (!$all && !$file){
            print_cli_help();
            echo "Must specify model file or use option -a for 'all'\n";
            return 1;   
        }
        if (empty($start) && empty($date)){
            print_cli_help();
            echo "Either date or Start & end month is required\n";
            return 1;   
        }
        if (empty($end)){
            $end = array(date('n'),date('Y'));
        }

        if (!class_exists('CoreWarehouseModel'))
            include_once(dirname(__FILE__).'/models/CoreWarehouseModel.php');

        if ($file){
            $models = array(substr($file,0,strlen($file)-4));
        }
        rsort($models);

        $con = FannieDB::get($FANNIE_PLUGIN_SETTINGS['WarehouseDatabase']);
        foreach($models as $class){
            echo "Reloading data for $class\n";
            if (!class_exists($class))
                include(dirname(__FILE__).'/models/'.$class.'.php');
            $obj = new $class($con);
            if (empty($date))
                $obj->reload($FANNIE_ARCHIVE_DB, $start[0], $start[1], $end[0], $end[1]);
            else
                $obj->refresh_data($FANNIE_ARCHIVE_DB, $date[1], $date[0], $date[2]);
        }
    }
}
