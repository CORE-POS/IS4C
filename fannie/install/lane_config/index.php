<?php
/*******************************************************************************

    Copyright 2010,2012 Whole Foods Co-op

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
include('ini.php');
include('../util.php');
?>
<html>
<head>
<title>IT CORE Lane Global Configuration</title>
<link rel="stylesheet" href="../../src/css/install.css" type="text/css" />
<script type="text/javascript" src="../../src/jquery/jquery.js"></script>
</head>
<body>
<?php
echo showLinkToFannie();
echo showInstallTabsLane("Lane Necessities", '');
?>

<form action=index.php method=post>
<h1>IT CORE Lane Global Configuration: Necessities</h1>

<p style="line-height: 1.2em;">
Use these forms for values that will be used on all lanes.
<br />To install, on each lane: $LANE/install/ &nbsp; &gt; &nbsp; "Upgrade ini.php via server"
<br />That only installs changes made here (Fannie) since the last time it was run on the lane.
<br />The first time it is run it will probably overwrite everything in ini.php,
<br />including config done locally with $LANE/install/*.php
<br />22Sep12 Eric Lee In my first try at this nothing in $LANE/ini.php was changed, no reason given.
<br /> I will see about making util.confsave() return more informative failure messages.
</p>

<h3>Basics</h3>
OS: <select name=OS>
<?php
if (isset($_REQUEST['OS'])) $CORE_LOCAL->set('OS',$_REQUEST['OS']);
if ($CORE_LOCAL->get('OS')=="") $CORE_LOCAL->set('OS','other');
if ($CORE_LOCAL->get('OS') == 'win32'){
	echo "<option value=win32 selected>Windows</option>";
	echo "<option value=other>*nix</option>";
}
else {
	echo "<option value=win32>Windows</option>";
	echo "<option value=other selected>*nix</option>";
}
confsave('OS',"'".$CORE_LOCAL->get('OS')."'");
?>
</select><br />
<hr />
<h3>Database set up</h3>
Lane database host: 
<?php
if (isset($_REQUEST['LANE_HOST'])) $CORE_LOCAL->set('localhost',$_REQUEST['LANE_HOST']);
if ($CORE_LOCAL->get('localhost')=="") $CORE_LOCAL->set('localhost','127.0.0.1');
printf("<input type=text name=LANE_HOST value=\"%s\" />",
	$CORE_LOCAL->get('localhost'));
confsave('localhost',"'".$CORE_LOCAL->get('localhost')."'");
?>
<br />
Lane database type:
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
confsave('DBMS',"'".$CORE_LOCAL->get('DBMS')."'");
?>
</select><br />
Lane user name:
<?php
if (isset($_REQUEST['LANE_USER'])) $CORE_LOCAL->set('localUser',$_REQUEST['LANE_USER']);
if ($CORE_LOCAL->get('localUser')=="") $CORE_LOCAL->set('localUser','root');
printf("<input type=text name=LANE_USER value=\"%s\" />",
	$CORE_LOCAL->get('localUser'));
confsave('localUser',"'".$CORE_LOCAL->get('localUser')."'");
?>
<br />
Lane password:
<?php
if (isset($_REQUEST['LANE_PASS'])) $CORE_LOCAL->set('localPass',$_REQUEST['LANE_PASS']);
printf("<input type=password name=LANE_PASS value=\"%s\" />",
	$CORE_LOCAL->get('localPass'));
confsave('localPass',"'".$CORE_LOCAL->get('localPass')."'");
?>
<br />
Lane operational DB:
<?php
if (isset($_REQUEST['LANE_OP_DB'])) $CORE_LOCAL->set('pDatabase',$_REQUEST['LANE_OP_DB']);
if ($CORE_LOCAL->get('pDatabase')=="") $CORE_LOCAL->set('pDatabase','opdata');
printf("<input type=text name=LANE_OP_DB value=\"%s\" />",
	$CORE_LOCAL->get('pDatabase'));
confsave('pDatabase',"'".$CORE_LOCAL->get('pDatabase')."'");
?>
<br />
Lane transaction DB:
<?php
if (isset($_REQUEST['LANE_TRANS_DB'])) $CORE_LOCAL->set('tDatabase',$_REQUEST['LANE_TRANS_DB']);
if ($CORE_LOCAL->get('pDatabase')=="") $CORE_LOCAL->set('pDatabase','translog');
printf("<input type=text name=LANE_TRANS_DB value=\"%s\" />",
	$CORE_LOCAL->get('tDatabase'));
confsave('tDatabase',"'".$CORE_LOCAL->get('tDatabase')."'");
?>
<br />
Server database host: 
<?php
if (isset($_REQUEST['SERVER_HOST'])) $CORE_LOCAL->set('mServer',$_REQUEST['SERVER_HOST']);
if ($CORE_LOCAL->get('mServer')=="") $CORE_LOCAL->set('mServer','127.0.0.1');
printf("<input type=text name=SERVER_HOST value=\"%s\" />",
	$CORE_LOCAL->get('mServer'));
confsave('mServer',"'".$CORE_LOCAL->get('mServer')."'");
?>
<br />
Server database type:
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
confsave('mDBMS',"'".$CORE_LOCAL->get('mDBMS')."'");
?>
</select><br />
Server user name:
<?php
if (isset($_REQUEST['SERVER_USER'])) $CORE_LOCAL->set('mUser',$_REQUEST['SERVER_USER']);
if ($CORE_LOCAL->get('mUser')=="") $CORE_LOCAL->set('mUser','root');
printf("<input type=text name=SERVER_USER value=\"%s\" />",
	$CORE_LOCAL->get('mUser'));
confsave('mUser',"'".$CORE_LOCAL->get('mUser')."'");
?>
<br />
Server password:
<?php
if (isset($_REQUEST['SERVER_PASS'])) $CORE_LOCAL->set('mPass',$_REQUEST['SERVER_PASS']);
printf("<input type=password name=SERVER_PASS value=\"%s\" />",
	$CORE_LOCAL->get('mPass'));
confsave('mPass',"'".$CORE_LOCAL->get('mPass')."'");
?>
<br />
Server database name:
<?php
if (isset($_REQUEST['SERVER_DB'])) $CORE_LOCAL->set('mDatabase',$_REQUEST['SERVER_DB']);
if ($CORE_LOCAL->get('mDatabase')=="") $CORE_LOCAL->set('mDatabase','core_trans');
printf("<input type=text name=SERVER_DB value=\"%s\" />",
	$CORE_LOCAL->get('mDatabase'));
confsave('mDatabase',"'".$CORE_LOCAL->get('mDatabase')."'");
?>
<hr />
<input type=submit value="Save" /> 
</form>

