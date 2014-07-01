<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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
/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 *
 * 14Jun12 EL Fix h1 page name, was "Fannie Membership"
*/

ini_set('display_errors','1');
include('../../config.php'); 
include('../util.php');
include('../../class-lib/FannieModule.php');
include('../../class-lib/FannieFunctions.php');
$FILEPATH = $FANNIE_ROOT;
?>
<a href="../index.php">Necessities</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="../auth.php">Authentication</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="../mem.php">Members</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="../stores.php">Stores</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="../update.php">Updates</a>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Modules
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<a href="../sample_data/extra_data.php">Sample Data</a>
<form action=index.php method=post>
<h1>Fannie Module System</h1>
<?php
if (is_writable('../../config.php')){
    echo "<span style=\"color:green;\"><i>config.php</i> is writeable</span>";
}
else {
    echo "<span style=\"color:red;\"><b>Error</b>: config.php is not writeable</span>";
}
echo '<hr />';

if (!isset($FANNIE_SYMBOLS)) $FANNIE_SYMBOLS = array();
unpack_symbols();

function __autoload($class_name){
    global $FANNIE_ROOT;
    bootstrap_load($FANNIE_ROOT.'class-lib',$class_name);
}

// sanity checks
if (!isset($FANNIE_SYMBOLS['functions']))
    $FANNIE_SYMBOLS['functions'] = array();
elseif (!is_array($FANNIE_SYMBOLS['functions']))
    $FANNIE_SYMBOLS['functions'] = array();

if (!isset($FANNIE_SYMBOLS['classes']))
    $FANNIE_SYMBOLS['classes'] = array();
elseif (!is_array($FANNIE_SYMBOLS['classes']))
    $FANNIE_SYMBOLS['classes'] = array();

get_available_modules($FANNIE_ROOT.'class-lib',$sysmods);
ksort($sysmods);

echo '<h3>System modules</h3>';
echo '<table cellspacing="0" cellpadding="4" border="1">';
echo '<tr><th>Module</th><th>Description</th><th>Enabled</th></tr>';
foreach($sysmods as $class=>$file){
    include_once($file);
    $obj = new $class();
    if (!is_object($obj)){
        printf('<tr><td colspan="3">Something is wrong with the %s module</td></tr>',
            $class);
    }
    else {
        if ($obj->required && !isset($FANNIE_SYMBOLS['classes'][$class])){
            $obj->enable();
        }
        else if (!$obj->required && isset($_REQUEST['submitted'])){
            if (isset($_REQUEST['SYS_MODS_'.$class]) && !isset($FANNIE_SYMBOLS['classes'][$class])){
                // box is checked, object not yet enabled
                $obj->enable();
            }
            elseif (!isset($_REQUEST['SYS_MODS_'.$class]) && isset($FANNIE_SYMBOLS['classes'][$class])){
                // box is not checked, object is currently enabled
                $obj->disable();
            }
        }

        printf('<tr><td>%s</td><td>%s</td><td><input type="checkbox"
            name="SYS_MODS_%s" value="1" %s %s /></td></tr>',
            $class,$obj->description,$class,
            (isset($FANNIE_SYMBOLS['classes'][$class]) ? 'checked' : ''),
            ($obj->required ? 'disabled="disabled"' : '')
        );
    }
}
echo '</table>';

?>
<hr />
<?php

get_available_modules($FANNIE_ROOT.'modules/plugins/',$usermods);
ksort($usermods);

echo '<h3>User modules</h3>';
echo '<table cellspacing="0" cellpadding="4" border="1">';
echo '<tr><th>Module</th><th>Description</th><th>Enabled</th></tr>';
foreach($usermods as $class=>$file){
    include_once($file);
    $obj = new $class();
    if (!is_object($obj)){
        printf('<tr><td colspan="3">Something is wrong with the %s module</td></tr>',
            $class);
    }
    else {
        if (isset($_REQUEST['submitted'])){
            if (isset($_REQUEST['USER_MODS_'.$class]) && !isset($FANNIE_SYMBOLS['classes'][$class])){
                // box is checked, object not yet enabled
                $obj->enable();
            }
            elseif (!isset($_REQUEST['USER_MODS_'.$class]) && isset($FANNIE_SYMBOLS['classes'][$class])){
                // box is not checked, object is currently enabled
                $obj->disable();
            }
        }

        printf('<tr><td>%s</td><td>%s</td><td><input type="checkbox"
            name="USER_MODS_%s" value="1" %s /></td></tr>',
            $class,$obj->description,$class,
            (isset($FANNIE_SYMBOLS['classes'][$class]) ? 'checked' : '')
        );
    }
}
echo '</table>';

save_symbols();
?>
<hr />
<input type="submit" name="submitted" value="Save Changes" />
</form>
