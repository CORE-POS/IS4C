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
ini_set('display_errors','1');
?>
<?php 
include('../config.php'); 
?>
<html>
<head><title>Fannie Sanity Checks</title>
</head>
<body>
<a href="index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Authentication
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="mem.php">Members</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="stores.php">Stores</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="sample_data/extra_data.php">Sample Data</a>
<form action=debug.php method=get>
<h1>Fannie sanity checks</h1>
<?php
// path detection
$FILEPATH = rtrim($_SERVER['SCRIPT_FILENAME'],'debug.php');
$FILEPATH = rtrim($FILEPATH,'/');
$FILEPATH = rtrim($FILEPATH,'install');
$FANNIE_ROOT = $FILEPATH;

if (is_writable($FILEPATH.'config.php')){
    echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
    echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
    echo "<br />Full path is: ".$FILEPATH.'config.php'."<br />";
    if (function_exists('posix_getpwuid')){
        $chk = posix_getpwuid(posix_getuid());
        echo "PHP is running as: ".$chk['name']."<br />";
    }
    else
        echo "PHP is (probably) running as: ".get_current_user()."<br />";
}
?>
<hr  />
<b>PHP Modules Checks</b><br />
<?php
if (function_exists("mysql_connect"))
    echo "<span style=\"color:green;\">MySQL support enabled</span>";
else
    echo "<span style=\"color:red;\">MySQL support missing</span>";
echo "<br />";
echo '<blockquote>MySQL support is only necessary is you have MySQL
    servers or lanes</blockquote>';
echo "<br />";
if (function_exists("mssql_connect"))
    echo "<span style=\"color:green;\">MSSQL support enabled</span>";
else
    echo "<span style=\"color:red;\">MSSQL support missing</span>";
echo "<br />";
echo '<blockquote>MSSQL support is only necessary is you have MSSQL
    servers or lanes</blockquote>';
echo "<br />";
if (function_exists("curl_init"))
    echo "<span style=\"color:green;\">cURL support enabled</span>";
else
    echo "<span style=\"color:red;\">cURL support missing</span>";
echo "<br />";
echo '<blockquote>cURL support is used for scheduled, custom lane-server
    database syncs. 
    </blockquote>';
echo "<br />";
if (class_exists("DOMDocument"))
    echo "<span style=\"color:green;\">DOM/XML Classes enabled</span>";
else
    echo "<span style=\"color:red;\">DOM/XML Classes missing</span>";
echo "<br />";
echo '<blockquote>DOM Objects are used in some reports to transform output
    to proper .xls documents. The Excel option for these reports will not
    work without it.
    </blockquote>';
echo "<br />";
if (function_exists("ldap_connect"))
    echo "<span style=\"color:green;\">LDAP support enabled</span>";
else
    echo "<span style=\"color:red;\">LDAP support missing</span>";
echo "<br />";
echo '<blockquote>Only necessary is authentication is enabled with the
    LDAP option.
    </blockquote>';
?>
<br />
<i>Resolving PHP problems: normally your package manager will install what
you need, but naming conventions aren't strictly identical. Look at PHP
related packages and it's usually pretty straightforward. A package with
both PHP and MySQL in the name probably provides MySQL support. Typically
you'll need to restart Apache after installing new PHP packages. If that doesn't
work, you may need to enable extensions manually in php.ini (typically /etc/php.ini)
and restart Apache again.</i><br />
<a href="" onclick="document.getElementById('phpinfodiv').style.display='block';return false;">Show full PHP Installation Info</a>
<div id="phpinfodiv" style="display:none;"><?php phpinfo(); ?></div>
<hr />
<b>Writable directories</b><br />
<i>Upload directories are scattered all over the place for compatibility's sake (sys_get_temp_dir requires
rather new PHP versions). Errors here are only relevant if you use a given feature</i><br />
<?php
if (is_writable($FILEPATH.'logs/'))
    echo "<span style=\"color:green;\">Logging directory is writable</span>";
else {
    echo "<span style=\"color:red;\">Logging directory is not writeable</span>";
    echo "<br />Full path is ".$FILEPATH.'logs/';
}
echo "<br />";
?>
<hr />
<input type=submit value="Refresh" />
</form>
</body>
</html>
