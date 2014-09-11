<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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


/* --FUNCTIONALITY- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 17Oct2012 Eric Lee noted:
    *  This is meant to be called by ../management/index.php, which base64_encode()'s fn.

*/

/* --COMMENTS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

    * 17Oct2012 Eric Lee Add comments, error checks.
    *            Add checkBase64Encoded().
    *            Test for base64_encoded and if not use urldecode() instead.
    *            Add window.close() button.

*/

/**
 * Check a string of base64 encoded data to make sure it has actually
 * been encoded.
 *
 * @param $encodedString string Base64 encoded string to validate.
 * @return Boolean Returns true when the given string only contains
 * base64 characters; returns false if there is even one non-base64 character.
 *
 * Source: http://ca3.php.net/manual/en/function.base64-decode.php
 */
function checkBase64Encoded($encodedString) {
    $length = strlen($encodedString);
 
    // Check every character.
    for ($i = 0; $i < $length; ++$i) {
        $c = $encodedString[$i];
        if (
            ($c < '0' || $c > '9')
            && ($c < 'a' || $c > 'z')
            && ($c < 'A' || $c > 'Z')
            && ($c != '+')
            && ($c != '/')
            && ($c != '=')
        ) {
            // Bad character found.
            return false;
        }
    }
    // Only good characters found.
    return true;
}

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (basename(__FILE__) != basename($_SERVER['PHP_SELF'])) {
    return;
}
$preload = FannieAPI::listModules('FannieTask');

$fn = isset($_REQUEST['fn'])?$_REQUEST['fn']:'';
if ($fn == ''){
    echo "No file specified";
    exit;
}

if ( checkBase64Encoded($fn) ) {
    $fn = $FANNIE_ROOT.'cron/'.base64_decode($fn);
} else {
    $fn = $FANNIE_ROOT.'cron/'.urldecode($fn);
}

if (!file_exists($fn) && !class_exists(basename($fn))){
    echo "File: >${fn}< does not exist.";
    exit;
}

$doc = '';
if (file_exists($fn)) {
    // Read the file into a string.
    $data = file_get_contents($fn);
    /* Parse into an array ($tokens) of arrays($t), one for each token where:
     * $t[0] the kind of token, e.g. T_COMMENT
     * $t[1] the content of the token, e.g. the entire comment.
     * $t[2] the line number in the file
    */
    $tokens = token_get_all($data);
    $doc = "";
    foreach($tokens as $t){
        if ($t[0] == T_COMMENT){
            if (strstr($t[1],"HELP"))
                $doc .= $t[1]."\n";
        }
    }
} else {
    $class = basename($fn);
    $obj = new $class();
    $doc = $obj->description;
}

echo "<html><head><title>";
echo basename($fn);
echo "</title></head><body>";
echo "<pre>";
if (!empty($doc))
    echo $doc;
else
    echo "Sorry, no documentation for this script: >{$fn}<";
echo "</pre>";
echo "<p><button onclick='window.close();'>Close Window</button></p>";
echo "</body></html>";

?>
