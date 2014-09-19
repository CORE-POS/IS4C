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
include(dirname(__FILE__).'/util.php');
include(dirname(__FILE__).'/db.php');
include_once('../classlib2.0/FannieAPI.php');

/**
    @class InstallIndexPage
    Class for the System Necessities (Install Home) install and config options
*/
class InstallIndexPage extends InstallPage {

    protected $title = 'Fannie install checks: Necessities';
    protected $header = 'Fannie install checks: Necessities';

    public $description = "
    Class for the System Necessities install and config options page.
    Tests for necessary system features.
    Creates databases and tables, creates and re-creates views.
    Should be run after every upgrade.
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
        echo "<h1 class='install'>{$this->header}</h1>";

        // Path detection: Establish ../../
        $FILEPATH = rtrim(__FILE__,"$self");
        $URL = rtrim($_SERVER['SCRIPT_NAME'],"$self");
        $FILEPATH = rtrim($FILEPATH,'/');
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
            echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
            echo "<hr />";
        } else {
            echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
            echo "<blockquote>";
            echo "config.php ({$FILEPATH}config.php) is Fannie's main configuration file.";
            echo "<ul>";
            echo "<li>If this file exists, ensure it is writable by the user running PHP (see above)";
            echo "<li>If the file does not exist, copy config.dist.php ({$FILEPATH}config.dist.php) to config.php";
            echo "<li>If neither file exists, create a new config.php ({$FILEPATH}config.php) containing:";
            echo "</ul>";
            echo "<pre style=\"font:fixed;background:#ccc;\">
&lt;?php
?&gt;
</pre>";
            echo "</blockquote>";
            echo '<input type="submit" value="Refresh this page" />';
            echo "</form>";
            return ob_get_clean();
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
            echo "<span style=\"color:red;\"><b>Error</b>: no database driver available</span>";
            echo "<blockquote>";
            echo 'Install at least one of the following PHP extensions: pdo_mysql, mysqli, mysql,
                or mssql. If you installed one or more of these and are still seeing this
                error, make sure they are enabled in your PHP configuration and try 
                restarting your web server.';
            echo "</blockquote>";
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

        echo '<br />Testing Operational DB connection:';
        $sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
                $FANNIE_OP_DB,$FANNIE_SERVER_USER,
                $FANNIE_SERVER_PW);
        $createdOps = false;
        if ($sql === false) {
            echo "<span style=\"color:red;\">Failed</span>";
        } else {
            echo "<span style=\"color:green;\">Succeeded</span>";
            $msgs = $this->create_op_dbs($sql);
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

        echo '<br />Testing Transaction DB connection:';
        $sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
                $FANNIE_TRANS_DB,$FANNIE_SERVER_USER,
                $FANNIE_SERVER_PW);
        $createdTrans = false;
        if ($sql === false) {
            echo "<span style=\"color:red;\">Failed</span>";
        } else {
            echo "<span style=\"color:green;\">Succeeded</span>";
            $msgs = $this->create_trans_dbs($sql);
            foreach ($msgs as $msg) {
                if ($msg['error'] == 0) continue;
                echo $msg['error_msg'] . '<br />';
            }
            $msgs = $this->create_dlogs($sql);
            foreach ($msgs as $msg) {
                if ($msg['error'] == 0) continue;
                echo $msg['error_msg'] . '<br />';
            }
            $createdTrans = true;
        }
        if ($createdOps && $createdTrans) {
            $msgs = $this->create_delayed_dbs();
            foreach ($msgs as $msg) {
                if ($msg['error'] == 0) continue;
                echo $msg['error_msg'] . '<br />';
            }

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
        echo "<br />Testing Transaction DB connection:";
        $sql = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
                $FANNIE_ARCHIVE_DB,$FANNIE_SERVER_USER,
                $FANNIE_SERVER_PW);
        if ($sql === false) {
            echo "<span style=\"color:red;\">Failed</span>";
        } else {
            echo "<span style=\"color:green;\">Succeeded</span>";
            $msgs = $this->create_archive_dbs($sql);
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
            $style = ($i == 0)?'block':'none';
            echo "<div id=\"lanedef$i\" style=\"display:$style;\">";
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
        <li><?php check_writeable('../logs/queries.log'); ?>
            <ul>
            <li>Contains failed database queries</li>
            <li>
            <a href="" onclick="$('#queryLogView').toggle(); return false;">See Recent Entries</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="../logs/LogViewer.php?logfile=<?php echo base64_encode('queries.log'); ?>">View Entire Log</a>
            <?php $queries = $log->getLogFile('../logs/queries.log', 100); ?>
            <pre id="queryLogView" class="tailedLog highlight"><?php echo $queries; ?></pre>
            </li>
            </ul>  
        <li><?php check_writeable('../logs/php-errors.log'); ?>
            <ul>
            <li>Contains PHP notices, warnings, and errors</li>
            <li>
            <a href="" onclick="$('#phpLogView').toggle(); return false;">See Recent Entries</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="../logs/LogViewer.php?logfile=<?php echo base64_encode('php-errors.log'); ?>">View Entire Log</a>
            <?php $phplog = $log->getLogFile('../logs/php-errors.log', 100); ?>
            <pre id="phpLogView" class="tailedLog highlight"><?php echo $phplog; ?></pre>
            </li>
            </ul>
        <li><?php check_writeable('../logs/dayend.log'); ?>
            <ul>
            <li>Contains output from scheduled tasks</li>
            <li>
            <a href="" onclick="$('#dayendLogView').toggle(); return false;">See Recent Entries</a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="../logs/LogViewer.php?logfile=<?php echo base64_encode('dayend.log'); ?>">View Entire Log</a>
            <?php $dayend = $log->getLogFile('../logs/dayend.log', 100); ?>
            <pre id="dayendLogView" class="tailedLog highlight"><?php echo $dayend; ?></pre>
            </li>
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
            1 => 'Yes (displayed)',
            2 => 'Yes (logged)',
            0 => 'No',
        );
        echo '<tr><td align="right">Verbose Error Messages</td>'
            . '<td>' . installSelectField('FANNIE_CUSTOM_ERRORS', $FANNIE_CUSTOM_ERRORS, $errorOpts, false, false)
            . '</td></tr>';

        $taskOpts = array(
            99 => 'Never email on error',
            1  => 'All Errors',
            2  => 'Small Errors and bigger',
            3  => 'Medium Errors and bigger',
            4  => 'Large Errors and bigger',
            5  => 'Only the Worst Errors',
        );

        echo '<tr><td>Task Error Severity resulting in emails</td>'
            . '<td>' . installSelectField('FANNIE_TASK_THRESHOLD', $FANNIE_TASK_THRESHOLD, $taskOpts, 4, false)
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

        echo '<tr><td>Catch-all Dept#</td>'
            . '<td>' . installTextField('FANNIE_MISC_DEPT', $FANNIE_MISC_DEPT, 800, false)
            . '</td></tr>';

        echo '</table>';
        ?>
        <hr />
        <input type=submit value="Re-run" />
        </form>


        <?php

        return ob_get_clean();

    // body_content()
    }


    function create_op_dbs($con){
        require(dirname(__FILE__).'/../config.php'); 

        $ret = array();

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'employees','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'departments','op');

        /**
          @deprecated 22Jan14
          Somewhat deprecated. Others' code may rely on this
          so it's still created
        */
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'deptMargin','op');

        /**
          @deprecated 22Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'deptSalesCodes','op');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'dateRestrict','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'subdepts','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'superdepts','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'superDeptNames','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'superDeptEmails','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'superMinIdView','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'MasterSuperDepts','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'products','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'productBackup','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'likeCodes','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'upcLike','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'taxrates','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'prodExtra','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'prodFlags','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'prodPhysicalLocation','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'productUser','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'prodUpdate','op');

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'prodUpdateArchive','op');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'prodPriceHistory','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'prodDepartmentHistory','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batches','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchList','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchType','op');

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchowner','op');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchCutPaste','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchBarcodes','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchMergeTable','op');

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchMergeProd','op');
        */

        /**
          @deprecated 22Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'likeCodeView','op');
        */

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchMergeLC','op');
        */

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchPriority30','op');
        */

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchPriority20','op');
        */

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchPriority10','op');
        */

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchPriority0','op');
        */

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'batchPriority','op');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'unfi','op');

        /**
          @deprecated 22Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'unfi_order','op');
        */

        /**
          @deprecated 22Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'unfi_diff','op');
        */

        /**
          @deprecated 22Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'unfi_all','op');
        */

        /**
          @deprecated 22Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'unfiCategories','op');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'shelftags','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'custdata','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'custdataBackup','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'custAvailablePrefs','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'custPreferences','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'meminfo','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'memtype','op');

        /**
          @deprecated 22Jan14
          memtype has sufficient columns now
          table kept around until confirming it can be deleted
        */
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'memdefaults','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'memberCards','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'memDates','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'memContact','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'memContactPrefs','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'tenders','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'customReceipt','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'houseCoupons','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'houseVirtualCoupons','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'houseCouponItems','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'autoCoupons','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'disableCoupon','op');
        
        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'productMargin','op');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'origins','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'originCountry','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'originStateProv','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'originCustomRegion','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'originName','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'ProductOriginsMap','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'vendors','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'vendorItems','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'vendorContact','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'vendorDeliveries','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'vendorSKUtoPLU','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'vendorSRPs','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'vendorDepartments','op');

        /**
         @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'vendorLoadScripts','op');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'ServiceScales','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'scaleItems','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'ServiceScaleItemMap','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'PurchaseOrder','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'PurchaseOrderItems','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'PurchaseOrderSummary','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'emailLog','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'UpdateLog','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'memberNotes','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'suspensions','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'reasoncodes','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'suspension_history','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'CustomerAccountSuspensions','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'cronBackup','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'customReports','op');

        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'AdSaleDates','op');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'custReceiptMessage','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'usageStats','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'ShrinkReasons','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'Stores','op');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
                'parameters','op');

        return $ret;

    // create_op_dbs()
    }

    function create_trans_dbs($con)
    {
        require(dirname(__FILE__).'/../config.php'); 

        $ret = array();

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'alog','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'PaycardTransactions','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'efsnetRequest','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'efsnetResponse','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'efsnetRequestMod','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'efsnetTokens','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ccReceiptView','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'voidTransHistory','trans');

        /* invoice stuff is very beta; not documented yet */
        $invCur = "CREATE TABLE InvDelivery (
            inv_date datetime,
            upc varchar(13),
            vendor_id int,
            quantity double,
            price float,
            INDEX (upc))";
        if (!$con->table_exists('InvDelivery',$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($invCur,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $invCur = "CREATE TABLE InvDeliveryLM (
            inv_date datetime,
            upc varchar(13),
            vendor_id int,
            quantity double,
            price float)";
        if (!$con->table_exists('InvDeliveryLM',$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($invCur,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $invArc = "CREATE TABLE InvDeliveryArchive (
            inv_date datetime,
            upc varchar(13),
            vendor_id int,
            quantity double,
            price float,
            INDEX(upc))";
        if (!$con->table_exists('InvDeliveryArchive',$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($invArc,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $invRecent = "CREATE VIEW InvRecentOrders AS
            SELECT inv_date,upc,sum(quantity) as quantity,
            sum(price) as price
            FROM InvDelivery GROUP BY inv_date,upc
            UNION ALL
            SELECT inv_date,upc,sum(quantity) as quantity,
            sum(price) as price
            FROM InvDeliveryLM GROUP BY inv_date,upc";
        if (!$con->table_exists('InvRecentOrders',$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($invRecent,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $union = "CREATE VIEW InvDeliveryUnion AS
            select upc,vendor_id,sum(quantity) as quantity,
            sum(price) as price,max(inv_date) as inv_date
            FROM InvDelivery
            GROUP BY upc,vendor_id
            UNION ALL
            select upc,vendor_id,sum(quantity) as quantity,
            sum(price) as price,max(inv_date) as inv_date
            FROM InvDeliveryLM
            GROUP BY upc,vendor_id
            UNION ALL
            select upc,vendor_id,sum(quantity) as quantity,
            sum(price) as price,max(inv_date) as inv_date
            FROM InvDeliveryArchive
            GROUP BY upc,vendor_id";
        if (!$con->table_exists("InvDeliveryUnion",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($union,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $total = "CREATE VIEW InvDeliveryTotals AS
            select upc,sum(quantity) as quantity,
            sum(price) as price,max(inv_date) as inv_date
            FROM InvDeliveryUnion
            GROUP BY upc";
        if (!$con->table_exists("InvDeliveryTotals",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($total,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }


        
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ar_history_backup','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'AR_EOM_Summary','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'lane_config','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'CapturedSignature','trans');

        return $ret;

    // create_trans_dbs()
    }

    function create_dlogs($con){
        require(dirname(__FILE__).'/../config.php'); 

        $ret = array();

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'dtransactions','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'transarchive','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'suspended','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'SpecialOrders','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'SpecialOrderDeptMap','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'SpecialOrderHistory','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'PendingSpecialOrder','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'CompleteSpecialOrder','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'dlog','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'dlog_90_view','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'dlog_15','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'suspendedtoday','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'TenderTapeGeneric','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ar_live_balance','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ar_history','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ar_history_sum','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'stockpurchases','trans');

        /**
          @deprecated 22Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'stockSum_purch','trans');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'stockSumToday','trans');

        /**
          @deprecated 22Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'newBalanceStockToday_test','trans');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'equity_history_sum','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'equity_live_balance','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'memChargeBalance','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'unpaid_ar_balances','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'unpaid_ar_today','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'dheader','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'dddItems','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'CashPerformDay','trans');
        /**
          @deprecated 21Jan14
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'CashPerformDay_cache','trans');
        */

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'houseCouponThisMonth','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'skuMovementSummary','trans');

        return $ret;

    // create_dlogs()
    }

    function create_delayed_dbs(){
        require(dirname(__FILE__).'/../config.php'); 

        $ret = array();

        $con = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
            $FANNIE_OP_DB,$FANNIE_SERVER_USER,
            $FANNIE_SERVER_PW);

        $ret[] = dropDeprecatedStructure($con, $FANNIE_OP_DB, 'expingMems', true);

        $ret[] = dropDeprecatedStructure($con, $FANNIE_OP_DB, 'expingMems_thisMonth', true);

        $con = db_test_connect($FANNIE_SERVER,$FANNIE_SERVER_DBMS,
            $FANNIE_TRANS_DB,$FANNIE_SERVER_USER,
            $FANNIE_SERVER_PW);

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ar_history_today','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'ar_history_today_sum','trans');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_TRANS_DB,
                'AR_statementHistory','trans');

        $invSalesView = "CREATE VIEW InvSales AS
            select datetime as inv_date,upc,quantity,total as price
            FROM transarchive WHERE ".$con->monthdiff($con->now(),'datetime')." <= 1
            AND scale=0 AND trans_status NOT IN ('X','R') 
            AND trans_type = 'I' AND trans_subtype <> '0'
            AND register_no <> 99 AND emp_no <> 9999";
        if (!$con->table_exists("InvSales",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($invSalesView,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $invRecentSales = "CREATE VIEW InvRecentSales AS
            select t.upc, 
            max(t.inv_date) as mostRecentOrder,
            sum(CASE WHEN s.quantity IS NULL THEN 0 ELSE s.quantity END) as quantity,
            sum(CASE WHEN s.price IS NULL THEN 0 ELSE s.price END) as price
            from InvDeliveryTotals as t
            left join InvSales as s
            on t.upc=s.upc and
            ".$con->datediff('s.inv_date','t.inv_date')." >= 0
            group by t.upc";
        if (!$con->table_exists("InvRecentSales",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($invRecentSales,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $invSales = "CREATE TABLE InvSalesArchive (
            inv_date datetime,
            upc varchar(13),
            quantity double,
            price float,
            INDEX(upc))";
        if (!$con->table_exists('InvSalesArchive',$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($invSales,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $union = "CREATE VIEW InvSalesUnion AS
            select upc,sum(quantity) as quantity,
            sum(price) as price
            FROM InvSales
            WHERE ".$con->monthdiff($con->now(),'inv_date')." = 0
            GROUP BY upc
            UNION ALL
            select upc,sum(quantity) as quantity,
            sum(price) as price
            FROM InvSalesArchive
            GROUP BY upc";
        if (!$con->table_exists("InvSalesUnion",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($union,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $total = "CREATE VIEW InvSalesTotals AS
            select upc,sum(quantity) as quantity,
            sum(price) as price
            FROM InvSalesUnion
            GROUP BY upc";
        if (!$con->table_exists("InvSalesTotals",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($total,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }
            
        $adj = "CREATE TABLE InvAdjustments (
            inv_date datetime,
            upc varchar(13),
            diff double,
            INDEX(upc))";
        if (!$con->table_exists("InvAdjustments",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($adj,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $adjTotal = "CREATE VIEW InvAdjustTotals AS
            SELECT upc,sum(diff) as diff,max(inv_date) as inv_date
            FROM InvAdjustments
            GROUP BY upc";
        if (!$con->table_exists("InvAdjustTotals",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($adjTotal,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $opstr = $FANNIE_OP_DB;
        if ($FANNIE_SERVER_DBMS=="mssql") $opstr .= ".dbo";
        $inv = "CREATE VIEW Inventory AS
            SELECT d.upc,
            d.quantity AS OrderedQty,
            CASE WHEN s.quantity IS NULL THEN 0
                ELSE s.quantity END AS SoldQty,
            CASE WHEN a.diff IS NULL THEN 0
                ELSE a.diff END AS Adjustments,
            CASE WHEN a.inv_date IS NULL THEN '1900-01-01'
                ELSE a.inv_date END AS LastAdjustDate,
            d.quantity - CASE WHEN s.quantity IS NULL
                THEN 0 ELSE s.quantity END + CASE WHEN
                a.diff IS NULL THEN 0 ELSE a.diff END
                AS CurrentStock
            FROM InvDeliveryTotals AS d
            INNER JOIN $opstr.vendorItems AS v 
            ON d.upc = v.upc
            LEFT JOIN InvSalesTotals AS s
            ON d.upc = s.upc LEFT JOIN
            InvAdjustTotals AS a ON d.upc=a.upc";
        if (!$con->table_exists("Inventory",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($inv,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }

        $cache = "CREATE TABLE InvCache (
            upc varchar(13),
            OrderedQty int,
            SoldQty int,
            Adjustments int,
            LastAdjustDate datetime,
            CurrentStock int)";
        if (!$con->table_exists("InvCache",$FANNIE_TRANS_DB)){
            $prep = $con->prepare_statement($cache,$FANNIE_TRANS_DB);
            $con->exec_statement($prep,array(),$FANNIE_TRANS_DB);
        }
        
        return $ret;

    // create_delayed_dbs()
    }

    function create_archive_dbs($con) {
        require(dirname(__FILE__).'/../config.php'); 

        $ret = array();

        $dstr = date("Ym");
        $archive = "transArchive".$dstr;
        $dbconn = ".";
        if ($FANNIE_SERVER_DBMS == "MSSQL")
            $dbconn = ".dbo.";

        if ($FANNIE_ARCHIVE_METHOD == "partitions")
            $archive = "bigArchive";

        $query = "CREATE TABLE $archive LIKE 
            {$FANNIE_TRANS_DB}{$dbconn}dtransactions";
        if ($FANNIE_SERVER_DBMS == "MSSQL"){
            $query = "SELECT TOP 1 * INTO $archive FROM 
                {$FANNIE_TRANS_DB}{$dbconn}dtransactions";
        }
        if (!$con->table_exists($archive,$FANNIE_ARCHIVE_DB)){
            $create = $con->prepare_statement($query,$FANNIE_ARCHIVE_DB);
            $con->exec_statement($create,array(),$FANNIE_ARCHIVE_DB);
            // create the first partition if needed
            if ($FANNIE_ARCHIVE_METHOD == "partitions"){
                $con->query('ALTER TABLE bigArchive CHANGE COLUMN store_row_id store_row_id BIGINT UNSIGNED');
                $p = "p".date("Ym");
                $limit = date("Y-m-d",mktime(0,0,0,date("n")+1,1,date("Y")));
                $partQ = sprintf("ALTER TABLE `bigArchive` 
                    PARTITION BY RANGE(TO_DAYS(`datetime`)) 
                    (PARTITION %s 
                        VALUES LESS THAN (TO_DAYS('%s'))
                    )",$p,$limit);
                $prep = $con->prepare_statement($partQ);
                $con->exec_statement($prep);
            }
        }

        $dlogView = "SELECT
            datetime AS tdate,
            register_no,
            emp_no,
            trans_no,
            upc,
            description,
            CASE WHEN (trans_subtype IN ('CP','IC') OR upc like('%000000052')) then 'T' WHEN upc = 'DISCOUNT' then 'S' else trans_type end as trans_type,
            CASE WHEN upc = 'MAD Coupon' THEN 'MA' 
               WHEN upc like('%00000000052') THEN 'RR' ELSE trans_subtype END as trans_subtype,
            trans_status,
            department,
            quantity,
            scale,
            cost,
            unitPrice,
            total,
            regPrice,
            tax,
            foodstamp,
            discount,
            memDiscount,
            discountable,
            discounttype,
            voided,
            percentDiscount,
            ItemQtty,
            volDiscType,
            volume,
            VolSpecial,
            mixMatch,
            matched,
            memType,
            staff,
            numflag,
            charflag,
            card_no,
            trans_id,
            pos_row_id,
            store_row_id,
            ".$con->concat(
                $con->convert('emp_no','char'),"'-'",
                $con->convert('register_no','char'),"'-'",
                $con->convert('trans_no','char'),'')
            ." as trans_num
            FROM $archive
            WHERE trans_status NOT IN ('D','X','Z')
            AND emp_no <> 9999 and register_no <> 99";

        $dlog_view = ($FANNIE_ARCHIVE_METHOD != "partitions") ? "dlog".$dstr : "dlogBig";
        if (!$con->table_exists($dlog_view,$FANNIE_ARCHIVE_DB)){
            $prep = $con->prepare_statement("CREATE VIEW $dlog_view AS $dlogView",
                $FANNIE_ARCHIVE_DB);
            $con->exec_statement($prep,array(),$FANNIE_ARCHIVE_DB);
        }

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'sumUpcSalesByDay','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'sumRingSalesByDay','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'vRingSalesToday','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'sumDeptSalesByDay','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'vDeptSalesToday','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'sumFlaggedSalesByDay','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'sumMemSalesByDay','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'sumMemTypeSalesByDay','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'sumTendersByDay','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'sumDiscountsByDay','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'reportDataCache','arch');

        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'weeksLastQuarter','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'productWeeklyLastQuarter','arch');
        $ret[] = create_if_needed($con,$FANNIE_SERVER_DBMS,$FANNIE_ARCHIVE_DB,
                'productSummaryLastQuarter','arch');

        return $ret;

    // create_archive_dbs()
    }

// InstallIndexPage
}

FannieDispatch::conditionalExec();

?>
