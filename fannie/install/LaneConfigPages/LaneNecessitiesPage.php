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
/* PHP manual: "Although display_errors may be set at runtime (with ini_set()),
 * it won't have any affect if the script has fatal errors.
 * This is because the desired runtime action does not get executed."
 * It does in fact show at least some fatals.
 * Does it show warnings?
 * Should only be used when debugging.
*/
//ini_set('display_errors','1');
// includes config.php
include('ini.php');
include('../util.php');
/* Utilities for creating and populating tables.
include('../db.php');
include('../../config.php');
*/
include_once('../../classlib2.0/FannieAPI.php');

/**
    @class LaneNecessitiesPage
    Class for the Global Lane install and config options Necessities page.
*/
class LaneNecessitiesPage extends InstallPage {

    protected $title = 'CORE:PoS Global Lane Configuration: Necessities';
    protected $header = 'CORE:PoS Global Lane Configuration: Necessities';

    public $description = "
    Class for the Global Lane install and config options Necessities page.
    ";

    // This replaces the __construct() in the parent.
    public function __construct() {

        // To set authentication.
        FanniePage::__construct();

        $SRC = '../../src';
        // Link to a file of CSS by using a function.
        $this->add_css_file("$SRC/style.css");
        $this->add_css_file("$SRC/javascript/jquery-ui.css");
        $this->add_css_file("$SRC/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("$SRC/javascript/jquery.js");
        $this->add_script("$SRC/javascript/jquery-ui.js");

    // __construct()
    }

    // If chunks of CSS are going to be added the function has to be
    //  redefined to return them.
    // If this is to override x.css draw_page() needs to load it after the add_css_file
    /**
      Define any CSS needed
      @return A CSS string
    function css_content(){
        $css ="";
        return $css;
    //css_content()
    }
    */

    // If chunks of JS are going to be added the function has to be
    //  redefined to return them.
    /**
      Define any javascript needed
      @return A javascript string
    function javascript_content(){
        $js ="";
        return $js;
    }
    */

    function body_content(){
        global $CORE_LOCAL;

        ob_start();

        echo showLinkToFannie();
        echo showInstallTabsLane("Lane Necessities", '');

?>

<form action=LaneNecessitiesPage.php method=post>
<h1 class="install"><?php echo $this->header; ?></h1>

<?php
if (is_writable('../../config.php')){
    echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
    echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
?>

<p class="ichunk" style="line-height: 1.5em;">
Use these forms for values that will be used on all lanes.
<br />It saves having to set the same values on a number of lanes.
<br />Some values can be overridden for a lane from the lane's config forms.
<br />To install, on each lane: $LANE/install/ &nbsp; &gt; &nbsp; "Upgrade ini.php via server"
<br />That only installs changes made here (Fannie) since the last time it was run on the lane.
<br />The first time it is run it will probably overwrite everything in the lane ini.php,
<br /> including config done locally with $LANE/install/*.php
<br /><br />22Sep12 Eric Lee In my first try at this nothing in $LANE/ini.php was changed, no reason given.
<br /> I will see about making lane.util.php::confsave() return more informative failure messages.
</p>
<hr />

<h4 class="install">Basics</h4>
<p class="ichunk2">
<b>Lane Operating System:</b> <select name=OS>
<?php
if (isset($_REQUEST['OS']))
    $CORE_LOCAL->set('OS',$_REQUEST['OS']);
if ($CORE_LOCAL->get('OS')=="")
    $CORE_LOCAL->set('OS','other');
if ($CORE_LOCAL->get('OS') == 'win32'){
    echo "<option value=win32 selected>Windows</option>";
    echo "<option value=other>*nix</option>";
}
else {
    echo "<option value=win32>Windows</option>";
    echo "<option value=other selected>*nix</option>";
}
echo "</select>";
confsave('OS',"'".$CORE_LOCAL->get('OS')."'");
?>
</p>
<hr />

<h4 class="install">Lane Database characteristics</h4>
<p class="ichunk2 ichunk3">This describes how lanes are set up.  It does not establish or change any value.
<br />It assumes the values are the same for all lanes, that all lanes are set up the same way.
<br />Values can be overridden in configuration at the lane.
</p>
<p class="ichunk2"><b>Lane database host: </b> 
<?php
if (isset($_REQUEST['LANE_HOST'])) $CORE_LOCAL->set('localhost',$_REQUEST['LANE_HOST']);
if ($CORE_LOCAL->get('localhost')=="") $CORE_LOCAL->set('localhost','127.0.0.1');
printf("<input type=text name=LANE_HOST value=\"%s\" />",
    $CORE_LOCAL->get('localhost'));
echo "</p>";
confsave('localhost',"'".$CORE_LOCAL->get('localhost')."'");
?>
<p class="ichunk2"><b>Lane database type: </b>
<select name=LANE_DBMS>
<?php
if(isset($_REQUEST['LANE_DBMS'])) $CORE_LOCAL->set('DBMS',$_REQUEST['LANE_DBMS']);
if ($CORE_LOCAL->get('DBMS')=="") $CORE_LOCAL->set('DBMS','mysql');
if ($CORE_LOCAL->get('DBMS') == 'mssql'){
    echo "<option value=mysql>MySQL</option>";
    echo "<option value=mssql selected>SQL Server</option>";
}
else {
    echo "<option value=mysql selected>MySQL</option>";
    echo "<option value=mssql>SQL Server</option>";
}
echo "</p>";
confsave('DBMS',"'".$CORE_LOCAL->get('DBMS')."'");
?>
</select>
<p class="ichunk2"><b>Lane database user name: </b>
<?php
if (isset($_REQUEST['LANE_USER'])) $CORE_LOCAL->set('localUser',$_REQUEST['LANE_USER']);
if ($CORE_LOCAL->get('localUser')=="") $CORE_LOCAL->set('localUser','root');
printf("<input type=text name=LANE_USER value=\"%s\" />",
    $CORE_LOCAL->get('localUser'));
echo "</p>";
confsave('localUser',"'".$CORE_LOCAL->get('localUser')."'");
?>
<p class="ichunk2"><b>Lane database password: </b>
<?php
if (isset($_REQUEST['LANE_PASS'])) $CORE_LOCAL->set('localPass',$_REQUEST['LANE_PASS']);
printf("<input type=password name=LANE_PASS value=\"%s\" />",
    $CORE_LOCAL->get('localPass'));
echo "</p>";
confsave('localPass',"'".$CORE_LOCAL->get('localPass')."'");
?>
<p class="ichunk2"><b>Lane operational database name: </b>
<?php
if (isset($_REQUEST['LANE_OP_DB'])) $CORE_LOCAL->set('pDatabase',$_REQUEST['LANE_OP_DB']);
if ($CORE_LOCAL->get('pDatabase')=="") $CORE_LOCAL->set('pDatabase','opdata');
printf("<input type=text name=LANE_OP_DB value=\"%s\" />",
    $CORE_LOCAL->get('pDatabase'));
echo "</p>";
confsave('pDatabase',"'".$CORE_LOCAL->get('pDatabase')."'");
?>
<p class="ichunk2"><b>Lane transaction database name: </b>
<?php
if (isset($_REQUEST['LANE_TRANS_DB'])) $CORE_LOCAL->set('tDatabase',$_REQUEST['LANE_TRANS_DB']);
if ($CORE_LOCAL->get('pDatabase')=="") $CORE_LOCAL->set('pDatabase','translog');
printf("<input type=text name=LANE_TRANS_DB value=\"%s\" />",
    $CORE_LOCAL->get('tDatabase'));
echo "</p>";
confsave('tDatabase',"'".$CORE_LOCAL->get('tDatabase')."'");
?>
<hr />

<h4 class="install">Server Database characteristics</h4>
<p class="ichunk2 ichunk3">This section describes to the lane how the server is set up.
<br />It does not establish or change any value.
</p>
<p class="ichunk2"><b>Server database host: </b> 
<?php
if (isset($_REQUEST['SERVER_HOST'])) $CORE_LOCAL->set('mServer',$_REQUEST['SERVER_HOST']);
if ($CORE_LOCAL->get('mServer')=="") $CORE_LOCAL->set('mServer','127.0.0.1');
printf("<input type=text name=SERVER_HOST value=\"%s\" />",
    $CORE_LOCAL->get('mServer'));
echo "</p>";
confsave('mServer',"'".$CORE_LOCAL->get('mServer')."'");
?>
<p class="ichunk2"><b>Server database type: </b>
<select name=SERVER_TYPE>
<?php
if (isset($_REQUEST['SERVER_TYPE'])) $CORE_LOCAL->set('mDBMS',$_REQUEST['SERVER_TYPE']);
if ($CORE_LOCAL->get('mDBMS')=="") $CORE_LOCAL->set('mDBMS','mysql');
if ($CORE_LOCAL->get('mDBMS') == 'mssql'){
    echo "<option value=mysql>MySQL</option>";
    echo "<option value=mssql selected>SQL Server</option>";
}
else {
    echo "<option value=mysql selected>MySQL</option>";
    echo "<option value=mssql>SQL Server</option>";
}
echo "</select>";
echo "</p>";
confsave('mDBMS',"'".$CORE_LOCAL->get('mDBMS')."'");
?>
<p class="ichunk2"><b>Server database user name: </b>
<?php
if (isset($_REQUEST['SERVER_USER'])) $CORE_LOCAL->set('mUser',$_REQUEST['SERVER_USER']);
if ($CORE_LOCAL->get('mUser')=="") $CORE_LOCAL->set('mUser','root');
printf("<input type=text name=SERVER_USER value=\"%s\" />",
    $CORE_LOCAL->get('mUser'));
echo "</p>";
confsave('mUser',"'".$CORE_LOCAL->get('mUser')."'");
?>
<p class="ichunk2"><b>Server database password: </b>
<?php
if (isset($_REQUEST['SERVER_PASS'])) $CORE_LOCAL->set('mPass',$_REQUEST['SERVER_PASS']);
printf("<input type=password name=SERVER_PASS value=\"%s\" />",
    $CORE_LOCAL->get('mPass'));
echo "</p>";
confsave('mPass',"'".$CORE_LOCAL->get('mPass')."'");
?>
<p class="ichunk2"><b>Server transaction database name: </b>
<?php
if (isset($_REQUEST['SERVER_DB'])) $CORE_LOCAL->set('mDatabase',$_REQUEST['SERVER_DB']);
if ($CORE_LOCAL->get('mDatabase')=="") $CORE_LOCAL->set('mDatabase','core_trans');
printf("<input type=text name=SERVER_DB value=\"%s\" />",
    $CORE_LOCAL->get('mDatabase'));
echo "</p>";
confsave('mDatabase',"'".$CORE_LOCAL->get('mDatabase')."'");
?>
<hr />
<input type=submit value="Save Changes" /> 
</form>

<?php

        return ob_get_clean();

    // body_content
    }

// LaneNecessitiesPage  
}

FannieDispatch::conditionalExec(false);

?>
