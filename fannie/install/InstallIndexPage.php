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

ini_set('display_errors','1');
if (!file_exists(dirname(__FILE__).'/../config.php')){
    echo "Missing config file!<br />";
    echo "Create a file named config.php in ".realpath(dirname(__FILE__).'/../').'<br />';
    echo "and put this in it:<br />";
    echo "<div style=\"border: 1px solid black;padding: 5em;\">";
    echo '&lt;?php<br />';
    echo '</div>';  
    exit;   
}

require(dirname(__FILE__).'/../config.php'); 
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(dirname(__FILE__).'/util.php');
}
if (!function_exists('create_if_neeed')) {
    include(dirname(__FILE__).'/db.php');
}

/**
    @class InstallIndexPage
    Class for the System Necessities (Install Home) install and config options
*/
class InstallIndexPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie install checks: Necessities';
    protected $header = 'Fannie install checks: Necessities';

    public $description = "
    Class for the System Necessities install and config options page.
    Tests for necessary system features.
    Creates databases and tables, creates and re-creates views.
    Should be run after every upgrade.
    ";
    public $themed = true;

    // This replaces the __construct() in the parent.
    public function __construct() {

        // To set authentication.
        FanniePage::__construct();

        // Link to a file of CSS by using a function.
        $this->add_css_file("../src/javascript/jquery-ui.css");
        $this->add_css_file("../src/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("../src/javascript/jquery.js");
        $this->add_script("../src/javascript/jquery-ui.js");

        $this->add_script('../src/javascript/syntax-highlighter/scripts/jquery.syntaxhighlighter.min.js');
        $this->add_onload_command('
            $.SyntaxHighlighter.init({
                baseUrl: \'../src/javascript/syntax-highlighter\',
                prettifyBaseUrl: \'../src/javascript/syntax-highlighter/prettify\',
                wrapLines: false
            });');

    // __construct()
    }

    /**
      Define any CSS needed
      @return A CSS string
    */
    function css_content()
    {
        return '
        pre.tailedLog {
            display: none;
            border: solid 1px black;
            background: #eee;
            padding: 1em;
            height: 200px;
            overflow: scroll;
        }
        ';
    //css_content()
    }

    //  redefined to return them.
    /**
      Define any javascript needed
      @return A javascript string
    function javascript_content(){
        $js="";
        return $js;
    }
    */

    function body_content()
    {
        include('../config.php'); 
        ob_start();

        echo showInstallTabs('Necessities');
        $self = basename($_SERVER['PHP_SELF']);

        echo "<form action='$self' method='post'>";
        if (!$this->themed) {
            echo "<h1 class='install'>{$this->header}</h1>";
        }

        // Path detection: Establish ../../
        $FILEPATH = rtrim(__FILE__,"$self");
	if (DIRECTORY_SEPARATOR == '\\') {
	    $FILEPATH = str_replace(DIRECTORY_SEPARATOR, '/', $FILEPATH);
	}
        $URL = rtrim($_SERVER['SCRIPT_NAME'],"$self");
        $FILEPATH = rtrim($FILEPATH, '/');
        $URL = rtrim($URL,'/');
        $FILEPATH = rtrim($FILEPATH,'install');
        $URL = rtrim($URL,'install');
        $FANNIE_ROOT = $FILEPATH;
        $FANNIE_URL = $URL;

        if (function_exists('posix_getpwuid')) {
            $chk = posix_getpwuid(posix_getuid());
            echo "PHP is running as: ".$chk['name']."<br />";
        } else {
            echo "PHP is (probably) running as: ".get_current_user()."<br />";
        }

        if (is_writable($FILEPATH.'config.php')) {
            confset('FANNIE_ROOT',"'$FILEPATH'");
            confset('FANNIE_URL',"'$URL'");
            echo "<div class=\"alert alert-success\"><i>config.php</i> is writeable</div>";
            echo "<hr />";
        } else {
            echo "<div class=\"alert alert-danger\"><b>Error</b>: config.php is not writeable</div>";
            echo "<div class=\"well\">";
            echo "config.php ({$FILEPATH}config.php) is Fannie's main configuration file.";
            echo "<ul>";
            echo "<li>If this file exists, ensure it is writable by the user running PHP (see above)";
            echo "<li>If the file does not exist, copy config.dist.php ({$FILEPATH}config.dist.php) to config.php";
            echo "<li>If neither file exists, create a new config.php ({$FILEPATH}config.php) containing:";
            echo "</ul>";
            echo "<pre>
&lt;?php
?&gt;
</pre>";
            echo "</div>";
            echo '<button type="submit" class="btn btn-default">Refresh this page</button>';
            echo "</form>";
            return ob_get_clean();
        }

        if (!is_dir(dirname(__FILE__) . '/../../vendor')) {
            echo "<div class=\"alert alert-warning\"><b>Warning</b>: dependencies appear to be missing.</div>";
            echo '<div class=\"well\">';
            echo 'Install <a href="https://getcomposer.org/">Composer</a> then run ';
            echo "<pre>";
            echo '$ cd "' . $FILEPATH . "\"\n";
            echo '$ /path/to/composer.phar update';
            echo '</div>';
        } else {
            $json = file_get_contents(dirname(__FILE__) . '/../../composer.json');
            $obj = json_decode($json);
            $missing = false;
            foreach (get_object_vars($obj->require) as $package => $version) {
                if (!is_dir(dirname(__FILE__) . '/../../vendor/' . $package)) {
                    $missing = true;
                    echo "<div class=\"alert alert-danger\"><b>Warning</b>: package " . $package . " is not installed.</div>";
                }
            }
            if ($missing) {
                echo '<div class="well">Install dependencies by running';
                echo "<pre>";
                echo '$ cd "' . substr($FILEPATH, 0, strlen($FILEPATH)-7) . "\"\n";
                echo '$ /path/to/composer.phar update';
                echo '</pre></div>';
            }
        }

        /**
            Detect databases that are supported
        */
        $supportedTypes = array();
        if (extension_loaded('pdo') && extension_loaded('pdo_mysql'))
            $supportedTypes['PDO_MYSQL'] = 'PDO MySQL';
        if (extension_loaded('mysqli'))
            $supportedTypes['MYSQLI'] = 'MySQLi';
        if (extension_loaded('mysql'))
            $supportedTypes['MYSQL'] = 'MySQL';
        if (extension_loaded('mssql'))
            $supportedTypes['MSSQL'] = 'MSSQL';

        if (count($supportedTypes) == 0) {
            echo "<div class=\"alert alert-danger\"><b>Error</b>: no database driver available</div>";
            echo "<div class=\"well\">";
            echo 'Install at least one of the following PHP extensions: pdo_mysql, mysqli, mysql,
                or mssql. If you installed one or more of these and are still seeing this
                error, make sure they are enabled in your PHP configuration and try 
                restarting your web server.';
            echo "</div>";
            exit;
        }
        $db_keys = array_keys($supportedTypes);
        $defaultDbType = $db_keys[0];

        echo '<h4 class="install"><a href="" onclick="$(\'#serverConfTable\').toggle(); return false;">Main Server</a> +</h4>';
        echo '<table id="serverConfTable">'; 
    
        echo '<tr><td>Server Database Host</td>'
            . '<td>' . installTextField('FANNIE_SERVER', $FANNIE_SERVER, '127.0.0.1')
            . '</td></tr>';

        echo '<tr><td>Server Database Type</td>'
            . '<td>' . installSelectField('FANNIE_SERVER_DBMS', $FANNIE_SERVER_DBMS, $supportedTypes, $defaultDbType)
            . '</td></tr>';

        echo '<tr><td>Server Database Username</td>'
            . '<td>' . installTextField('FANNIE_SERVER_USER', $FANNIE_SERVER_USER, 'root')
            . '</td></tr>';

        echo '<tr><td>Server Database Password</td>'
            . '<td> ' . installTextField('FANNIE_SERVER_PW', $FANNIE_SERVER_PW, '', true, array('type'=>'password'))
            . '</td></tr>';

        echo '<tr><td>Server Operational DB name</td>'
            . '<td>' . installTextField('FANNIE_OP_DB', $FANNIE_OP_DB, 'core_op')
            . '</td></tr>';

        echo '<tr><td>Server Transaction DB name</td>'
            . '<td>' . installTextField('FANNIE_TRANS_DB', $FANNIE_TRANS_DB, 'core_trans')
            . '</td></tr>';;
        echo '</table>';

        $sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
                $FANNIE_OP_DB,$FANNIE_SERVER_USER,
                $FANNIE_SERVER_PW);
        $createdOps = false;
        if ($sql === false) {
            echo "<div class=\"alert alert-danger\">Testing Operational DB connection failed</div>";
        } else {
            echo "<div class=\"alert alert-success\">Testing Operational DB connection succeeded</div>";
            $msgs = $this->create_op_dbs($sql, $FANNIE_OP_DB);
            $createdOps = true;
            foreach ($msgs as $msg) {
                if ($msg['error'] == 0) continue;
                echo $msg['error_msg'] . '<br />';
            }

            // create auth tables later than the original
            // setting in case db settings were wrong
            if (isset($FANNIE_AUTH_ENABLED) && $FANNIE_AUTH_ENABLED === true) {
                if (!function_exists('table_check')) {
                    include($FILEPATH.'auth/utilities.php');
                }
                table_check();
            }
        }

        $sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
                $FANNIE_TRANS_DB,$FANNIE_SERVER_USER,
                $FANNIE_SERVER_PW);
        $createdTrans = false;
        if ($sql === false) {
            echo "<div class=\"alert alert-danger\">Testing Transaction DB connection failed</div>";
        } else {
            echo "<div class=\"alert alert-success\">Testing Transaction DB connection succeeded</div>";
            $msgs = $this->create_trans_dbs($sql, $FANNIE_TRANS_DB, $FANNIE_OP_DB);
            foreach ($msgs as $msg) {
                if ($msg['error'] == 0) continue;
                echo $msg['error_msg'] . '<br />';
            }
            $createdTrans = true;
        }
        if ($createdOps && $createdTrans) {
            // connected to both databases
            // collapse config fields
            $this->add_onload_command('$(\'#serverConfTable\').hide();');
        }
        ?>
        <hr />
        <?php
        echo '<h4 class="install"><a href="" onclick="$(\'#archiveConfTable\').toggle(); return false;">Transaction Archiving</a> +</h4>';
        echo '<table id="archiveConfTable">'; 

        echo '<tr><td>Archive DB name</td>'
            . '<td>' . installTextField('FANNIE_ARCHIVE_DB', $FANNIE_ARCHIVE_DB, 'trans_archive')
            . '</td></tr>';

        echo '<tr><td>Archive Method</td>'
            . '<td>' . installSelectField('FANNIE_ARCHIVE_METHOD', $FANNIE_ARCHIVE_METHOD, array('partitions', 'tables'), 'partitions')
            . '</td></tr>';

        echo '</table>';

        //local archiving - set up now
        $sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
                $FANNIE_ARCHIVE_DB,$FANNIE_SERVER_USER,
                $FANNIE_SERVER_PW);
        if ($sql === false) {
            echo "<div class=\"alert alert-danger\">Testing Archive DB connection failed</div>";
        } else {
            echo "<div class=\"alert alert-success\">Testing Archive DB connection succeeded</div>";
            $msgs = $this->create_archive_dbs($sql, $FANNIE_ARCHIVE_DB, $FANNIE_ARCHIVE_METHOD);
            foreach ($msgs as $msg) {
                if ($msg['error'] == 0) continue;
                echo $msg['error_msg'] . '<br />';
            }
            $this->add_onload_command('$(\'#archiveConfTable\').hide();');
        }
        ?>
        <hr />
        <h4 class="install">Lanes</h4>
        Number of lanes
        <?php
        if (!isset($FANNIE_NUM_LANES)) $FANNIE_NUM_LANES = 0;
        if (isset($_REQUEST['FANNIE_NUM_LANES'])) $FANNIE_NUM_LANES = $_REQUEST['FANNIE_NUM_LANES'];
        confset('FANNIE_NUM_LANES',"$FANNIE_NUM_LANES");
        echo "<input type=text name=FANNIE_NUM_LANES value=\"$FANNIE_NUM_LANES\" size=3 />";
        ?>
        <br />
        <?php
        if ($FANNIE_NUM_LANES == 0) confset('FANNIE_LANES','array()');
        else {
        ?>
        <script type=text/javascript>
        function showhide(i,num){
            for (var j=0; j<num; j++){
                if (j == i)
                    document.getElementById('lanedef'+j).style.display='block';
                else
                    document.getElementById('lanedef'+j).style.display='none';
            }
        }
        </script>
        <?php
        echo "<select onchange=\"showhide(this.value,$FANNIE_NUM_LANES);\">";
        for($i=0; $i<$FANNIE_NUM_LANES;$i++){
            echo "<option value=$i>Lane ".($i+1)."</option>";
        }
        echo "</select><br />";

        $conf = 'array(';
        for($i=0; $i<$FANNIE_NUM_LANES; $i++){
            $style = ($i == 0)?'':'class="collapse"';
            echo "<div id=\"lanedef$i\" $style>";
            if (!isset($FANNIE_LANES[$i])) $FANNIE_LANES[$i] = array();
            $conf .= 'array(';

            if (!isset($FANNIE_LANES[$i]['host'])) $FANNIE_LANES[$i]['host'] = '127.0.0.1';
            if (isset($_REQUEST["LANE_HOST_$i"])){ $FANNIE_LANES[$i]['host'] = $_REQUEST["LANE_HOST_$i"]; }
            $conf .= "'host'=>'{$FANNIE_LANES[$i]['host']}',";
            echo "Lane ".($i+1)." Database Host: <input type=text name=LANE_HOST_$i value=\"{$FANNIE_LANES[$i]['host']}\" /><br />";
            
            if (!isset($FANNIE_LANES[$i]['type'])) $FANNIE_LANES[$i]['type'] = $defaultDbType;
            if (isset($_REQUEST["LANE_TYPE_$i"])) $FANNIE_LANES[$i]['type'] = $_REQUEST["LANE_TYPE_$i"];
            $conf .= "'type'=>'{$FANNIE_LANES[$i]['type']}',";
            echo "Lane ".($i+1)." Database Type: <select name=LANE_TYPE_$i>";
            foreach ($supportedTypes as $val=>$label){
                printf('<option value="%s" %s>%s</option>',
                    $val,
                    ($FANNIE_LANES[$i]['type'] == $val)?'selected':'',
                    $label);
            }
            echo "</select><br />";

            if (!isset($FANNIE_LANES[$i]['user'])) $FANNIE_LANES[$i]['user'] = 'root';
            if (isset($_REQUEST["LANE_USER_$i"])) $FANNIE_LANES[$i]['user'] = $_REQUEST["LANE_USER_$i"];
            $conf .= "'user'=>'{$FANNIE_LANES[$i]['user']}',";
            echo "Lane ".($i+1)." Database Username: <input type=text name=LANE_USER_$i value=\"{$FANNIE_LANES[$i]['user']}\" /><br />";

            if (!isset($FANNIE_LANES[$i]['pw'])) $FANNIE_LANES[$i]['pw'] = '';
            if (isset($_REQUEST["LANE_PW_$i"])) $FANNIE_LANES[$i]['pw'] = $_REQUEST["LANE_PW_$i"];
            $conf .= "'pw'=>'{$FANNIE_LANES[$i]['pw']}',";
            echo "Lane ".($i+1)." Database Password: <input type=password name=LANE_PW_$i value=\"{$FANNIE_LANES[$i]['pw']}\" /><br />";

            if (!isset($FANNIE_LANES[$i]['op'])) $FANNIE_LANES[$i]['op'] = 'opdata';
            if (isset($_REQUEST["LANE_OP_$i"])) $FANNIE_LANES[$i]['op'] = $_REQUEST["LANE_OP_$i"];
            $conf .= "'op'=>'{$FANNIE_LANES[$i]['op']}',";
            echo "Lane ".($i+1)." Operational DB: <input type=text name=LANE_OP_$i value=\"{$FANNIE_LANES[$i]['op']}\" /><br />";

            if (!isset($FANNIE_LANES[$i]['trans'])) $FANNIE_LANES[$i]['trans'] = 'translog';
            if (isset($_REQUEST["LANE_TRANS_$i"])) $FANNIE_LANES[$i]['trans'] = $_REQUEST["LANE_TRANS_$i"];
            $conf .= "'trans'=>'{$FANNIE_LANES[$i]['trans']}'";
            echo "Lane ".($i+1)." Transaction DB: <input type=text name=LANE_TRANS_$i value=\"{$FANNIE_LANES[$i]['trans']}\" /><br />";

            $conf .= ")";
            echo "</div>";  

            if ($i == $FANNIE_NUM_LANES - 1)
                $conf .= ")";
            else
                $conf .= ",";
        }
        confset('FANNIE_LANES',$conf);

        }
        ?>
        <a href="LaneConfigPages/index.php">Edit Global Lane Configuration Page</a>
        <hr />
        <h4 class="install">Logs &amp; Debugging</h4>
        Fannie writes to the following log files:
        <?php
        if (!class_exists('LogViewer')) {
            include(dirname(__FILE__) . '/../logs/LogViewer.php');
        }
        $log = new LogViewer();
        ?>
        <ul>
        <li><?php check_writeable('../logs/fannie.log'); ?>
            <ul>
            <li>Contains info, notice, warning, error, critical, alert, and emergency level messages.</li>
            <li>
            <a href="" onclick="$('#dayendLogView').toggle(); return false;">See Recent Entries</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="../logs/LogViewer.php?logfile=<?php echo base64_encode('fannie.log'); ?>">View Entire Log</a>
            <?php $dayend = $log->getLogFile('../logs/fannie.log', 100); ?>
            <pre id="dayendLogView" class="tailedLog highlight"><?php echo $dayend; ?></pre>
            </li>
            <li>If this file is missing, messages may be written to legacy log file dayend.log</li>
            </ul>  
        <li><?php check_writeable('../logs/debug_fannie.log'); ?>
            <ul>
            <li>Contains debug level messages including failed queries and PHP notice/warning/error messages.</li>
            <li>
            <a href="" onclick="$('#dayendLogView').toggle(); return false;">See Recent Entries</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="../logs/LogViewer.php?logfile=<?php echo base64_encode('debug_fannie.log'); ?>">View Entire Log</a>
            <?php $dayend = $log->getLogFile('../logs/debug_fannie.log', 100); ?>
            <pre id="dayendLogView" class="tailedLog highlight"><?php echo $dayend; ?></pre>
            </li>
            <li>If this file is missing, messages may be written to legacy log file php_errors.log &amp; queries.log</li>
            </ul>  
        </ul>
        <?php
        echo '<table>';

        echo '<tr><td align="right">Color-Highlighted Logs</td>'
            . '<td>' . installSelectField('FANNIE_PRETTY_LOGS', $FANNIE_PRETTY_LOGS, array('true'=>'Yes', 'false'=>'No'), false, false)
            . '</td></tr>';

        echo '<tr><td align="right">Log Rotation Count</td>'
            . '<td>' . installTextField('FANNIE_LOG_COUNT', $FANNIE_LOG_COUNT, 5, false)
            . '</td></tr>';

        $errorOpts = array(
            1 => 'Yes',
            0 => 'No',
        );
        if ($FANNIE_CUSTOM_ERRORS > 1) {
            $FANNIE_CUSTOM_ERRORS = 1;
        }
        echo '<tr><td align="right">Verbose Debug Messages</td>'
            . '<td>' . installSelectField('FANNIE_CUSTOM_ERRORS', $FANNIE_CUSTOM_ERRORS, $errorOpts, false, false)
            . '</td></tr>';

        $taskOpts = array(
            99 => 'Never email on error',
            0  => 'Emergency',
            1  => 'Alert',
            2  => 'Critical',
            3  => 'Error',
            4  => 'Warning',
            5  => 'Notice',
            6  => 'Info',
            7  => 'Debug',
        );

        echo '<tr><td>Task Error Severity resulting in emails</td>'
            . '<td>' . installSelectField('FANNIE_TASK_THRESHOLD', $FANNIE_TASK_THRESHOLD, $taskOpts, 99, false)
            . '</td></tr>';

        echo '</table>';

        ?>
        <hr />
        <h4 class="install">Co-op</h4>
        Use this to identify code that is specific to your co-op.
        <br />Particularly important if you plan to contribute to the CORE IT code base.
        <br />Try to use a code that will not be confused with any other, e.g. "WEFC_Toronto" instead of "WEFC".
        <br />Co-op ID: 
        <?php
        echo installTextField('FANNIE_COOP_ID', $FANNIE_COOP_ID);
        ?>

        <hr />
        <h4 class="install">Locale</h4>
        Set the Country and Language where Fannie will run.
        <br />If these are not set in Fannie configuration but are set in the Linux environment the environment values will be used as
        defaults that can be overridden by settings here.

        <?php
        echo '<br />Country: ';
        //Use I18N country codes.
        $countries = array("US"=>"USA", "CA"=>"Canada");
        echo installSelectField('FANNIE_COUNTRY', $FANNIE_COUNTRY, $countries, '');

        echo '<br />Language: ';
        //Use I18N language codes.
        $langs = array("en"=>"English", "fr"=>"French", "sp"=>"Spanish");
        echo installSelectField('FANNIE_LANGUAGE', $FANNIE_LANGUAGE, $langs, '');
        ?>
        <hr />
        <h4 class="install">Back Office Transactions</h4>
        <i>Values used when generating transaction data via Fannie
        instead of through an actual POS terminal. The corrections department
        is only used for balancing individual transactions. Total sales
        to that department via generated transactions should always be
        zero. The catch-all department is used when generated transactions
        will generate a sale (or refund) but it is not known where the 
        amount belongs for accounting purposes.
        </i><br />
        <?php
        echo '<table>';
        
        echo '<tr><td>Employee#</td>'
            . '<td>' . installTextField('FANNIE_EMP_NO', $FANNIE_EMP_NO, 1001, false)
            . '</td></tr>';

        echo '<tr><td>Register#</td>'
            . '<td>' . installTextField('FANNIE_REGISTER_NO', $FANNIE_REGISTER_NO, 30, false)
            . '</td></tr>';

        echo '<tr><td>Corrections Dept#</td>'
            . '<td>' . installTextField('FANNIE_CORRECTION_DEPT', $FANNIE_CORRECTION_DEPT, 800, false)
            . '</td></tr>';

        echo '<tr><td>Patronage Transfer Dept#</td>'
            . '<td>' . installTextField('FANNIE_PATRONAGE_DEPT', $FANNIE_PATRONAGE_DEPT, 800, false)
            . '</td></tr>';

        echo '<tr><td>Catch-all Dept#</td>'
            . '<td>' . installTextField('FANNIE_MISC_DEPT', $FANNIE_MISC_DEPT, 800, false)
            . '</td></tr>';

        echo '</table>';
        ?>
        <hr />
        <p>
            <button type="submit" class="btn btn-default">Save Configuration</button>
        </p>
        </form>


        <?php

        return ob_get_clean();

    // body_content()
    }


    public function create_op_dbs($con, $op_db_name)
    {
        $ret = array();

        $models = array(
            // TABLES
            'AutoCouponsModel',
            'BatchesModel',
            'BatchListModel',
            'BatchCutPasteModel',
            'BatchBarcodesModel',
            'BatchTypeModel',
            'BatchMergeTableModel',
            'CronBackupModel',
            'CustdataModel',
            'CustdataBackupModel',
            'CustAvailablePrefsModel',
            'CustPreferencesModel',
            'CustReceiptMessageModel',
            'CustomerAccountSuspensionsModel',
            'CustomReceiptModel',
            'CustomReportsModel',
            'DateRestrictModel',
            'DepartmentsModel',
            'DisableCouponModel',
            'EmployeesModel',
            'HouseCouponsModel',
            'HouseCouponItemsModel',
            'HouseVirtualCouponsModel',
            'LikeCodesModel',
            'UpcLikeModel',
            'MemberCardsModel',
            'MemberNotesModel',
            'MemDatesModel',
            'MeminfoModel',
            'MemtypeModel',
            'MemContactModel',
            'MemContactPrefsModel',
            'OriginsModel',
            'OriginCountryModel',
            'OriginStateProvModel',
            'OriginCustomRegionModel',
            'ParametersModel',
            'PatronageModel',
            'ProductsModel',
            'ProductBackupModel',
            'ProductUserModel',
            'ProductOriginsMapModel',
            'ProdExtraModel',
            'ProdFlagsModel',
            'ProdPhysicalLocationModel',
            'ProdUpdateModel',
            'ProdDepartmentHistoryModel',
            'ProdPriceHistoryModel',
            'PurchaseOrderModel',
            'PurchaseOrderItemsModel',
            'PurchaseOrderSummaryModel',
            'ReasoncodesModel',
            'ScaleItemsModel',
            'ServiceScalesModel',
            'ServiceScaleItemMapModel',
            'ShelftagsModel',
            'ShrinkReasonsModel',
            'SpecialDeptMapModel',
            'SubDeptsModel',
            'SuperDeptsModel',
            'SuperDeptEmailsModel',
            'SuperDeptNamesModel',
            'StoresModel',
            'SuspensionsModel',
            'SuspensionHistoryModel',
            'TaxRatesModel',
            'TendersModel',
            'UsageStatsModel',
            'VendorsModel',
            'VendorContactModel',
            'VendorDeliveriesModel',
            'VendorItemsModel',
            'VendorSRPsModel',
            'VendorSKUtoPLUModel',
            'VendorBreakdownsModel',
            'VendorDepartmentsModel',
            // VIEWS
            'SuperMinIdViewModel',
            'MasterSuperDeptsModel',
        );
        foreach ($models as $class) {
            $obj = new $class($con);
            $ret[] = $obj->createIfNeeded($op_db_name);
        }

        /**
          @deprecated 22Jan14
          Somewhat deprecated. Others' code may rely on this
          so it's still created
          @update 06Nov14
          All uses are checked first for existence. Not necessary
          to create on new install.
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$op_db_name,
                'deptMargin','op');
        */

        /**
          @deprecated 22Jan14
          Only used for legacy pages
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'unfi','op');
        */

        /**
          @deprecated 22Jan14
          memtype has sufficient columns now
          table kept around until confirming it can be deleted
          @update 06Nov14
          No longer needs to be created on new installs
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'memdefaults','op');
        */

        /**
          @deprecated 06Nov14 andy
          origins table has name fields removing need for view
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'originName','op');
        */

        /**
          @deprecated 06Nov14 andy
          Relates to old, never finished email statement/invoice function
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'emailLog','op');
        */

        /**
          @deprecated 06Nov14 andy
          Relates to old, pre-BasicModel update mechanism
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'UpdateLog','op');
        */

        $ret[] = dropDeprecatedStructure($con, $op_db_name, 'expingMems', true);

        $ret[] = dropDeprecatedStructure($con, $op_db_name, 'expingMems_thisMonth', true);

        return $ret;

    // create_op_dbs()
    }

    public function create_trans_dbs($con, $trans_db_name, $op_db_name)
    {
        require(dirname(__FILE__).'/../config.php'); 

        $ret = array();
        $models = array(
            // TABLES
            'DTransactionsModel',
            'TransArchiveModel',
            'SuspendedModel',
            'DLog15Model',
            'ArHistoryModel',
            'ArHistoryBackupModel',
            'ArHistorySumModel',
            'ArEomSummaryModel',
            'CapturedSignatureModel',
            'CashPerformDayModel',
            'EfsnetRequestModel',
            'EfsnetRequestModModel',
            'EfsnetResponseModel',
            'EfsnetTokensModel',
            'EquityHistorySumModel',
            'PaycardTransactionsModel',
            'SpecialOrdersModel',
            'SpecialOrderDeptMapModel',
            'SpecialOrderHistoryModel',
            'PendingSpecialOrderModel',
            'CompleteSpecialOrderModel',
            'StockpurchasesModel',
            'VoidTransHistoryModel',
            // VIEWS
            'DLogModel',
            'DLog90ViewModel',
            'ArHistoryTodayModel', // requires dlog
            'ArHistoryTodaySumModel', //requires dlog
            'CcReceiptViewModel',
            'StockSumTodayModel', // requires dlog
            'SuspendedTodayModel',
            'TenderTapeGenericModel', // requires dlog
            'UnpaidArBalancesModel',
            'UnpaidArTodayModel', // requires ar_history_today_sum, unpaid_ar_balances
            'ArLiveBalanceModel', // requires ar_history_today_sum
            'EquityLiveBalanceModel', // requires stockSumToday
            'MemChargeBalanceModel', // requires ar_live_balance,
            'HouseCouponThisMonthModel', // requires dlog_90_view
        );
        foreach ($models as $class) {
            $obj = new $class($con);
            if (method_exists($obj, 'addExtraDB')) {
                $obj->addExtraDB($op_db_name);
            }
            $ret[] = $obj->createIfNeeded($trans_db_name);
        }

        /**
          @deprecated 7Nov14
          Not used lane side
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'alog','trans');
        */

        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvDelivery', false);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvDeliveryLM', false);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvDeliveryArchive', false);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvRecentOrders', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvDeliveryUnion', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvDeliveryTotals', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvSales', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvRecentSales', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvSalesArchive', false);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvSalesUnion', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvSalesTotals', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvSalesAdjustments', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvAdjustTotals', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'Inventory', true);
        $ret[] = dropDeprecatedStructure($con, $trans_db_name, 'InvCache', false);
        
        /**
          @deprecated 7Nov14
          No longer used lane side
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'lane_config','trans');
        */

        return $ret;

    // create_trans_dbs()
    }

    function create_dlogs($con){
        require(dirname(__FILE__).'/../config.php'); 

        $ret = array();

        /**
          @deprecated 10Nov2014
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'dheader','trans');
        */

        /**
          @deprecated 10Nov2014
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'dddItems','trans');
        */

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'CashPerformDay_cache','trans');
        */

        /**
          14Nov2014 Andy
          Not sure this is the correct way to cache & compare
          product UPCs with vendor SKUs
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'skuMovementSummary','trans');
        */

        return $ret;

    // create_dlogs()
    }

    function create_archive_dbs($con, $archive_db_name, $archive_method) 
    {
        $ret = array();
        $models = array(
            'ReportDataCacheModel',
            'WeeksLastQuarterModel',
            'ProductWeeklyLastQuarterModel',
            'ProductSummaryLastQuarterModel',
        );
        if ($archive_method == 'partitions') {
            $models[] = 'BigArchiveModel';
            $models[] = 'DLogBigModel';
        } else {
            $models[] = 'MonthlyArchiveModel';
            $models[] = 'MonthlyDLogModel';
        }
        foreach ($models as $class) {
            $obj = new $class($con);
            if (method_exists($obj, 'setDate')) {
                $obj->setDate(date('Y'), date('m'));
            }
            $ret[] = $obj->createIfNeeded($archive_db_name);
        }

        /**
          14Nov2014 Andy
          Removing these structures. Duplicates for most exist
          in the CoreWarehouse database. 
        $dbms = strtoupper($con->dbms_name()); // code below expect capitalization
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'sumUpcSalesByDay','arch');
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'sumRingSalesByDay','arch');
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'vRingSalesToday','arch');
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'sumDeptSalesByDay','arch');
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'vDeptSalesToday','arch');
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'sumFlaggedSalesByDay','arch');
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'sumMemSalesByDay','arch');
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'sumMemTypeSalesByDay','arch');
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'sumTendersByDay','arch');
        $ret[] = create_if_needed($con,$dbms,$archive_db_name,
                'sumDiscountsByDay','arch');
        */

        return $ret;

    // create_archive_dbs()
    }

// InstallIndexPage
}

FannieDispatch::conditionalExec();

?>
