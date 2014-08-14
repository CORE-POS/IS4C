<?php
/*******************************************************************************

    Copyright 2012 West End Food Co-op, Toronto, ON, Canada

    This file is part of Fannie.

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

/* HELP

   members.update.from.CiviCRM.php

     Updates Fannie membership data from CiviCRM 3.4.4.
     Matches records on custdata.CardNo = civicrm_membership.id
     Add CiviCRM records that don't exist in Fannie.

*/

/* members.update.from.CiviCRM.php
   update IS4C members tables from CiviCRM membership tables
     
 --FUNCTIONALITY { - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 -=proposed o=in-progress +=working  x=removed/disabled

 + SELECT all of the currently valid members from CiviCRM
   - that were created or changed since a certain date.
       There is not a straightforward way to find this in Civi.
 + v.1
     + Write to a tab-delimited file for export to IS4C.
     x In a separate script, on pos[dev], read this file and populate IS4C tables.
       Probably won't do this if direct access from IS4C-side is possible.

 + v.2
 + Populate the IS4C custdata and other tables:
     - update the name and contact-point data
        but not re-initialize IS4C fields that are populated for new records.
     - create new records as in getMembers.php
   + Make 2 or more custdata records for Civi Household records.

 - Outstanding
   - putting membership fees in IS4C
     - different contact-points in some records, for some people
     - sort out households and organizations at Civi end.

 --functionality } - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

 'Z --COMMENTZ { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

 13Oct12 EL Ignore member card number 0; assigned in Civi when member edited and
              member_card_number2_21 is not assigned a real value.
  3Oct12 EL Get member card number from civicrm_value_identification_and_cred_5.member_card_number2_21
.            and populate memberCard.
 29Aug12 EL Set memberIdOffset to 0 from 4000. Note that clearIS4C() will need different params now
                            or maybe need to work differently.
  7Aug12 EL Enable email, to me at gmail.
 13Jul12 EL -> Try doing dbConn2 as add_connection. It may be necessary.
               It is important that the databases for each conn have different names.
               See nightly.dtrans.php for example, but it isn't clear how you distinguish the two.
                               See the (foo,$database) arg, which defaults to the first one.
                             There they are both on the same server.
                             Perhaps the usage is limited to transfers.
            -> Try SetFetchMode
 12Jul12 EL For IS4C environment.

Differences between mysqli and SQLManager syntax:
Both work here.

// Connect:
$dbConn = @new mysqli("$CIVICRM_SERVER", "$CIVICRM_SERVER_USER", "$CIVICRM_SERVER_PW", "$CIVICRM_DB");
$dbConn = new SQLManager($CIVICRM_SERVER,$CIVICRM_SERVER_DBMS,$CIVICRM_DB,
        $CIVICRM_SERVER_USER,$CIVICRM_SERVER_PW);

// Same:
$selectCivi = "SELECT id, contact_id from civicrm_membership LIMIT 10;";
$civim = $dbConn->query("$selectCivi");

// Fetch:
while ( $row = $civim->fetch_row() ) {}
while ( $row = $dbConn->fetch_row($civim) ) {}

$dbConn->connect_errno and $dbConn->connect_error don't exist in SQLManager, use $dbConn->error

 --upadateMembers at point of port from webfaction:- - - - - - - - - - - - - - - - - -
 11Jul EL + log final email message
                    o Better to log results to file
                      - and email that?
                    + Run this on the whole set.
                    - Clear and then run with real member id#s.  Maybe not for a while yet.
                    -> Handle multiple first name.  Should this be a Household?
There is also a Household for them, #2115, linking to #823 and #824.  Is #1187 obsolete?
1187    Individual  0       Peter/Debbie        Fleming/Adams                   80 Ritchie Ave.         Toronto M6R 2J9 1108    1039                    661 4   0   2010-07-06  2010-07-06                          
823 Individual  0       Peter       Fleming                 80 Ritchie Ave.         Toronto M6R 2J9 1108    1039    416 537 6576    peterfleming@sympatico.ca   0   0   565 4   0   2010-01-02  2010-01-02      100.00  7   1   2010-03-12 00:00:00     1
824 Individual  0       Debbie      Adams                   80 Ritchie Ave.         Toronto M6R 2J9 1108    1039        debbieadams@sympatico.ca    0   0   835 4   0   2010-03-12  2010-03-12      100.00  7   1   2010-03-12 00:00:00     0
824 Individual  0       Debbie      Adams                   80 Ritchie Ave.         Toronto M6R 2J9 1108    1039        debbieadams@sympatico.ca    0   0   835 4   0   2010-03-12  2010-03-12      100.00  7   1   2010-03-12 00:00:00     0
                    -> Re multiple records for a person:
                        - Add is_primary to address, phone and email field sets
                        - Capture 2nd phone for meminfo.email_2
                          There's no current place in IS4C for other 2nd+ contact points.
                        + LEFT JOIN on email.  Doesn't help dups but gets 18 records w/o email.
                        + Try DISTINCT. Gets rid of dups.
 10Jul EL + To run by cron:
                        + email results to admin(s).
                        + func to handle, email errors instead of die.
                        + .sh to run this. Mainly to cd to ~/is4c.  Can PHP do that?
                        + cronjob to run the .sh or this.
                        + Defeat clearing IS4C tables: clearIS4C
                          - Undo Janna jigger in clearIS4C.
  9Jul EL Branch from getMembers.php to:
            + update the name and contact-point data
                      but not re-initialize IS4C fields that are populated for new records.
            + create new records as in getMembers.php (as in getMembers.php)

 --getMembers at point of branch:- - - - - - - - - - - - - - - - - - - - - - - - -
  7Jul EL + Do complete run.  Doesn't crash, not perfect.
            o Fixing funcs.
            o Household, other multiples working. Data style not settled.
            + Handle Organization Name as last name
            -> Is there a cashier-side lookup?
            -> Code this to lookup-and-update for existing members.
  5Jul EL + memDates and memContact done.
            -> How to identify new records for a later run?
                        xDatestamp? None exists.
                          -Last member#
            -> Code stockpurchases. Worth the trouble?
                       - Is there a change-db method in the conn?
                       - How to identify the membership purchase:
                           - Explicit
                             - Bond + $5
                             - Any cannery, if not one of the others.
  4Jul EL + Test after at least part of meminfo assignment is coded. OK!
  2Jul EL + Code part of each of
              + custdata and (lint ok, not tested) Values complete?  Check is4c table.
                        + meminfo
  1Jul EL With better join: 1584 items.
             With distinct c.id: 1170.  Why 695 in Civi members list?  Still many c.id dups
                      Distinct means the whole row is unique.
                      With manual c.id de-dup then 700, so I will assume that's same as the Civi list.
 30Jun EL Start to build select from Civi.  1164 rows to handle.  449 if 1/contact.id
                    Re: Version 1, why cannot use "select into outfile":
                    INTO OUTFILE '${outFile}' FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n'
                    Access error ...
                    See:
                     http://community.webfaction.com/questions/7465/export-database-to-csv
                      Says to use phpMyAdmin export.
                    Can connect to posdev!

 --'p PHP study
. {} needed around multi-dimension array references in quoted strings.
. Parser does not complain about: if ( foo == "x" ), i.e. s/b $foo.

 --'m MYSQL study
 http://ca.php.net/manual/en/book.mysqli.php
 http://ca.php.net/manual/en/class.mysqli.php

 mysqli::close realized as [$bool=] $dbConn->close()
 mysqli::$connect_errno realized as [$str=] $dbConn->connect_errno()

 new mysqli(x,y,x)  returns a connection object, $conn
 $q="SELECT ..."
 $conn->query("$q") returns a result-set object, $members
 $s="INSERT/UPDATE/DELETE/SELECT ..."
 $conn->prepare("$s") returns a statement object, $stmt
 $members->fetch_row() returns a plain array, $rw

 --'q SQLManager study
Is this, directly from ADODB, available here?  What does the include in SQLManager imply?
  function ErrorMsg()
  {
    if ($this->_errorMsg) return '!! '.strtoupper($this->dataProvider.' '.$this->databaseType).': '.$this->_errorMsg;
    else return '';
  }
 --commentz } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

*/

//'F --FUNCTIONS { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Clear the test records from the IS4C tables.
// CardNo or card_no range between 4000 and 6000
function clearIS4C($low = 417, $high = 1500) {

    global $dbConn2;
    global $is4cTables;

    // Argument to where.
    $clearWhere = "";

    foreach ($is4cTables as $table_desc) {
        list($db, $table, $cn) = explode("|", $table_desc);
        //$clearWhere = "$cn = 4471";
        $clearWhere = "$cn BETWEEN $low AND $high;";
        $query = "DELETE FROM $table WHERE ${clearWhere};";
//echo "$query\n";
        if ( TRUE && $db == "core_op" ) {
            $rslt = $dbConn2->query("$query");
            if ( $dbConn2->errno ) {
                return(sprintf("DML failed: %s\n", $dbConn2->error));
                //$dbConn2->close();
                //die("dying ...");
                //exit();
            }
        } elseif ( $db == "core_trans" ) {
            // Need another connection for this db.
            1;
        } else {
            // In fact a problem, but not likely.
            1;
        }
    }

    return("OK");

// clearIS4C
}


/* v2: Return an array of custdata for the memberId.
        Calling script to test for #-of-items in array: 0=none, >=1 if some.
        Return error-string if the lookup failed.
*/
/* v1: Return TRUE if the Civi membership number is known in ISC4C custdata.
        Return FALSE if not.
        Return error-string if the lookup failed.
        Is there something to gain by getting some data, such a number-of persons
         from custdata at this point?
         Names for household members?
*/
function searchIS4C($member) {

    global $dbConn2;

    $is4cMembers = array();
    $sel = "SELECT CardNo, personNum, FirstName, LastName FROM custdata where CardNo = ${member};";
    $rslt = $dbConn2->query("$sel");
    if ( $dbConn2->errno ) {
        $msg = sprintf("Error: DQL failed: %s\n", $dbConn2->error);
        $is4cMembers[] = array($msg);
        return($is4cMembers);
    }
    // What is $rslt if 0 rows?  Does it exist?
    //$n = 0;
    while ( $row = $dbConn2->fetch_row($rslt) ) {
        //$n++;
        $is4cMembers[] = array($row[CardNo], $row[personNum], $row[FirstName], $row[LastName]);
    }
    return($is4cMembers);

// searchIS4C
}

// #'t
function searchIS4C2($member) {

    global $dbConn2;

    $is4cOp = array();
    $sel = "SELECT c.CardNo as cCard,
    i.card_no as iCard,
    t.card_no as tCard,
    d.card_no as dCard,
    r.card_no as rCard
    FROM custdata c
LEFT JOIN meminfo i ON c.CardNo = i.card_no
LEFT JOIN memContact t ON c.CardNo = t.card_no
LEFT JOIN memDates d ON c.CardNo = d.card_no
LEFT JOIN memberCards r ON c.CardNo = r.card_no
    WHERE c.CardNo = ${member};";
    $rslt = $dbConn2->query("$sel");
    if ( $dbConn2->errno ) {
        $msg = sprintf("Error: DQL failed: %s\n", $dbConn2->error);
        $is4cOp[] = array($msg);
        return($is4cOp);
    }

    while ( $row = $dbConn2->fetch_row($rslt) ) {
        $is4cOp['custdata'] = "update";
        $is4cOp['meminfo'] = ( $row[iCard] != "" ) ? "update" : "insert";
        $is4cOp['memContact'] = ( $row[tCard] != "" ) ? "update" : "insert";
        $is4cOp['memDates'] = ( $row[dCard] != "" ) ? "update" : "insert";
        $is4cOp['memberCards'] = ( $row[rCard] != "" ) ? "update" : "insert";
        break;
    }

    return($is4cOp);

// searchIS4C2
}

// Insert the records for this Individual, Household or Organization.
// Return "OK" if all OK or abort returning message on any error.
function insertToIS4C() {

    global $dbConn2;

    global $insertCustdata;
    global $insertMeminfo;
    global $insertMemContact;
    global $insertMemDates;
    global $insertMemberCards;
    global $insertStockpurchases;

    global $debug;

//echo "In insertToIS4C\n";

    $statements = array($insertMeminfo,
        $insertMemContact,
        $insertMemDates,
        $insertMemberCards);
    $statement = "";

    if ( count($insertCustdata) > 0 ) {
        foreach ($insertCustdata as $statement) {
            if ( $debug == 1) 
                echo $statement, "\n";
//continue;
            $rslt = $dbConn2->query("$statement");
            if ( 1 && $dbConn2->errno ) {
                return(sprintf("Error: Insert failed: %s\n", $dbConn2->error));
            }
        }
    }
    else {
        //echo "No custdata to insert.\n";
        1;
    }

    foreach ($statements as $statement) {
        if ( $statement != "" ) {
            if ( $debug == 1) 
                echo $statement, "\n";
//continue;
            $rslt = $dbConn2->query("$statement");
            if ( 1 && $dbConn2->errno ) {
                return(sprintf("Error: Insert failed: %s\n", $dbConn2->error));
            }
        }
    }

    // stockpurchases is in a different db.

    return("OK");

// insertToIS4C
}

// Update the records for this Individual, Household or Organization.
// Return "OK" if all OK or abort returning message on any error.
function updateIS4C() {

    global $dbConn2;

//echo "In updateIS4C\n";

    global $updateCustdata;
    global $updateMeminfo;
    global $updateMemContact;
    global $updateMemDates;
    global $updateMemberCards;
    global $updateStockpurchases;

    global $debug;

    $statements = array($updateMeminfo,
        $updateMemContact,
        $updateMemDates,
        $updateMemberCards);
    $statement = "";

    if ( count($updateCustdata) > 0 ) {
        foreach ($updateCustdata as $statement) {
            if ( $debug == 1) 
                echo $statement, "\n";
//continue;
            $rslt = $dbConn2->query("$statement");
            if ( 1 && $dbConn2->errno ) {
                return(sprintf("Error: Update failed: %s\n", $dbConn2->error));
            }
        }
    }
    else {
        //echo "No custdata to update.\n";
        1;
    }

    foreach ($statements as $statement) {
        if ( $statement != "" ) {
            if ( $debug == 1) 
                echo $statement, "\n";
//continue;
            $rslt = $dbConn2->query("$statement");
            if ( 1 && $dbConn2->errno ) {
                return(sprintf("Error: Update failed: %s\n", $dbConn2->error));
            }
        }
    }

    // stockpurchases is in a different db.

    return("OK");

// updateIS4C
}


// Each IS4C table is represented by an assoc array.
function clearWorkVars() {

    // in core_op
    // Card#, Person#, Name
    global $custdata;
    // Contact points for Card#
    global $meminfo;
    // Whether/how to contact.
    global $memContact;
    // Membership start, i.e. join date
    global $memDates;
    // Member Card barcode lookup.
    global $memberCards;
    // in core_trans
    global $stockpurchases;

    // in core_op
    // Card#, Person#, Name
//  $custdata[CardNo] = 0;
//  $custdata[personNum] = 0;
    $flds = array_keys($custdata);
    foreach ($flds as $field) {
        $custdata[$field] = "";
    }

    $flds = array_keys($meminfo);
    foreach ($flds as $field) {
        $meminfo[$field] = "";
    }

    $flds = array_keys($memDates);
    foreach ($flds as $field) {
        $memDates[$field] = "";
    }

    $flds = array_keys($memContact);
    foreach ($flds as $field) {
        $memContact[$field] = "";
    }

    $flds = array_keys($memberCards);
    foreach ($flds as $field) {
        $memberCards[$field] = "";
    }

    $flds = array_keys($stockpurchases);
    foreach ($flds as $field) {
        $stockpurchases[$field] = "";
    }

    global $insertCustdata;
    global $insertMeminfo;
    global $insertMemContact;
    global $insertMemDates;
    global $insertMemberCards;
    global $insertStockpurchases;

    global $updateCustdata;
    global $updateMeminfo;
    global $updateMemContact;
    global $updateMemDates;
    global $updateMemberCards;
    global $updateStockpurchases;

    $insertCustdata = array();
    $insertMeminfo = "";
    $insertMemContact = "";
    $insertMemDates = "";
    $insertMemberCards = "";
    $insertStockpurchases = "";

    $updateCustdata = array();
    $updateMeminfo = "";
    $updateMemContact = "";
    $updateMemDates = "";
    $updateMemberCards = "";
    $updateStockpurchases = "";

// clearWorkVars
}

// Return province or state name from code format 1=abbreviation or 2=full-name
function getProvince($num = 0, $format = 1) {

    $province = "";

    if ( $format == 1 ) {
        switch ($num) {
            case 1108:
                $province = "ON";
                break;
            default:
                $province = "XX";
                break;
        }
    }

    return($province);

//getProvince
}

// o Return in format "A9A 9A9"
function fixPostalCode($str = "") {
    $str = strtoupper($str);
    // Remove anything but uppercase letters and numbers.
    $str = preg_replace("/[^A-Z\d]/", "", $str);
    // Format: A9A 9A9
    //  Leaves non-postal-code content alone.
    $str = preg_replace("/([A-Z]\d[A-Z])(\d[A-Z]\d)/", "$1 $2", $str);
    return($str);
//fixPostalCode
}

/* o Return in format 999-999-9999
    unless the original wasn't even close.
*/
function fixPhone($str = "") {
    $str_orig = $str;
    $str = preg_replace("/[^\d]/", "", $str);
    $str = preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $str);
    if ( preg_match("/^(\d{3})-(\d{3})-(\d{4})$/", $str) ) {
        return($str);
    } else {
        $str_orig = str_replace("'", "''", $str_orig);
        return($str_orig);
    }
//fixPhone
}

/* City:
    + tolower if ALL CAPS
  + Capitalize first letter of each word
  + Double apostrophes
*/
function fixCity($str = "") {
    if ( preg_match("/[A-Z]{3}/", $str) ) {
        $str = strtolower($str);
    }
    $str = ucwords($str);
    $str = str_replace("'", "''", $str);
    return($str);
//fixCity
}

/* Name:
    + tolower if ALL CAPS
  + Capitalize first letter of each word
  + Double apostrophes
*/
function fixName($str = "") {
    if ( preg_match("/[A-Z]{3}/", $str) ) {
        $str = strtolower($str);
        // First letter after hyphen
        $str = preg_replace("/(-[A-Z])/", "$1", $str);
        if ( "$1" != "" ) {
            $upper1 = strtoupper("$1");
            $str = str_replace("$1", "$upper1", $str);
        }
        //$str = preg_replace("/(-[A-Z])/", strtoupper($1), $str); // T_LNUMBER error
        $str = ucwords($str);
    }
    // Is all-lowercase + hyphen space apostrophe
    elseif ( preg_match("/^[- 'a-z]+$/", $str) ) {
        // Need exceptions: "di", "de la", ... ?
        $str = ucwords($str);
    }
    // Already mixed-case
    else {
        1;
    }
    $str = str_replace("'", "''", $str);
    return($str);
//fixName
}

/* Address:
    + tolower if ALL CAPS
  + Capitalize first letter of each word
  + Double apostrophes
*/
function fixAddress($str = "") {
    if ( preg_match("/[A-Z]{3}/", $str) ) {
        $str = strtolower($str);
    }
    $str = ucwords($str);
    $str = str_replace("'", "''", $str);
    return($str);
//fixAddress
}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
*/
function dieHere($msg="") {

    global $dbConn;
    global $dbConn2;
    global $insertCount;
    global $updateCount;
    global $admins;

    $subject = "PoS: Error: Update IS4C members";
    $message = "$msg";
    //$message = "Added: $insertCount  Updated: $updateCount\n";
    $adminString = implode(" ", $admins);

    $lastLine = exec("echo \"$message\" | mail -s \"$subject\" $adminString");
    // echo "Not ready to email: $msg\n";
    // Ordinary success returns nothing, or "".
    echo "from mailing: {$lastLine}\n";

    if ( $dbConn ) {
        // Warning: mysqli::close(): Couldn't fetch mysqli in /home/parkdale/is4c/updateMembers.php on line next
        @$dbConn->close();
    } else {
        //echo "No dbConn to close.\n";
        1;
    }

    if ( $dbConn2 ) {
        @$dbConn2->close();
    } else {
        //echo "No dbConn2 to close.\n";
        1;
    }

    //echo "End of dieHere\n";
    exit();

//dieHere
}

// Return an array of the name keys: 1, 3, etc.
function getNameKeys($row) {

    $names = array();
    $key = "";
    $n = 0;
    foreach (array_keys($row) as $key) {
        // Test for odd number.
        // http://ca.php.net/manual/en/function.array-filter.php
        // Also works:
        //if ( ($n % 2) != 0 ) {}
        if ($n & 1) {
            $names[] = $key;
            //echo "$n odd: $key\n";
        } else {
            1;
            //echo "$n not-odd: $key\n";
        }
        $n++;
    }

    return($names);

//getNameKeys
}

// Return an array of the values from the odd-numbered elements i.e. hash-name keys: 1, 3, etc.
// I.e. reduce the duplication of a BOTH array.
// Getting the evens would have the same result.
function getNameValues($row) {

    $values = array();
    $val = "";
    $n = 0;
    foreach ($row as $val) {
        // Test for odd number.
        // http://ca.php.net/manual/en/function.array-filter.php
        // Also works:
        //if ( ($n % 2) != 0 ) {}
        if ($n & 1) {
            $values[] = $val;
            //echo "$n odd: $key\n";
        } else {
            1;
            //echo "$n not-odd: $key\n";
        }
        $n++;
    }

    return($values);

//getNameValues
}

// --functions } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// --PREP - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/* Turn off all error reporting
 Re error suppression with @:
 http://ca.php.net/manual/en/language.operators.errorcontrol.php
 See that re: set_error_handler() and error_reporting()
*/
//error_reporting(0);

/* Report simple running errors
 These kind of errors go to STDOUT or STDERR before they are tested for or trapped.
 But see note about suppression with @, above.
*/
error_reporting(E_ERROR | E_WARNING);
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

//'C --CONSTANTS { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

include('../config.php');
// Connection id's, etc.
include('../config_wefc.php');
include('../src/SQLManager.php');
include($FANNIE_ROOT.'src/cron_msg.php');

// What does this do?
set_time_limit(0);

// Tab-delimited.
$outFile = "../logs/members_up.tab";

// Log. Cumulative. Still a bit vague on what s/b written here.
$logFile = "../logs/updateMembers.log";

// version of this program
// 1 = write to file
// 2 = direct to posdev
// -> This isn't being observed.
$version = 1;

// test: 4000  production: 0
$memberIdOffset = 0;

// Whether to clear or write anything to IS4C
$writeIS4C = 1;

// Controls some monitoring and info.
$debug = 0;

// People to whom news is mailed.
$admins = array("el66gr@gmail.com");

$is4cTableNames = array('custdata', 'meminfo', 'memContact', 'memDates', 'memberCards', 'stockpurchases');

// --constants } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

//'V --VARIABLES { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Counter of tab-delim lines.
$outCount = 0;
// Counter of rows from raw select.
$inCount = 0;

$lastCid = "";
$dupCid = 0;
$uniqueCid = 0;
$isDupCid = 0;
$insertCount = 0;
$updateCount = 0;

/* Arrays for IS4C tables
   Initialized by clearWorkVars()
*/
// in core_op
// Card#, Person#, Name
$custdata = array(
    "CardNo" => "",
    "personNum" => "",
    "LastName" => "",
    "FirstName" => "",
    "Type" => "",
    "memType" => "",
    "blueLine" => "",
    "id" => ""
);
// Contact points for Card#
$meminfo = array(
    "card_no" => "",
    "last_name" => "",
    "first_name" => "",
    "street" => "",
    "city" => "",
    "state" => "",
    "zip" => "",
    "phone" => "",
    "email_1" => "",
    "email_2" => "",
    "ads_OK" => ""
);
// Whether/how to contact.
$memContact = array(
    "card_no" => "",
    "x" => ""
);
// Membership start, i.e. join date
$memDates = array(
    "card_no" => "",
    "x" => ""
);
// Member Card barcode lookup.
$memberCards = array(
    "card_no" => "",
    "upc" => ""
    );
// in core_trans
$stockpurchases = array(
    "card_no" => "",
    "x" => ""
);

$is4cTables = array("core_op|custdata|CardNo",
    "core_op|meminfo|card_no",
    "core_op|memContact|card_no",
    "core_op|memDates|card_no",
    "core_op|memberCards|card_no",
    "core_trans|stockpurchases|card_no");

$insertCustdata = array();
$insertMeminfo = "";
$insertMemContact = "";
$insertMemDates = "";
$insertMemberCards = "";
$insertStockpurchases = "";

$updateCustdata = array();
$updateMeminfo = "";
$updateMemContact = "";
$updateMemDates = "";
$updateMemberCards = "";
$updateStockpurchases = "";

// insert or update
$is4cOp = array();
//$is4cOp = "";

// --variables } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

//'M --MAIN - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

clearWorkVars();
/*
echo "CardNo:", $custdata[CardNo], "\n";
exit();
*/

// The "@" prevents the error from being reported immediately,
//  but the test further on will still see it.
//$dbConn = @new mysqli("$CIVICRM_SERVER", "$CIVICRM_SERVER_USER", "$CIVICRM_SERVER_PW", "$CIVICRM_DB");
$dbConn = new SQLManager($CIVICRM_SERVER,$CIVICRM_SERVER_DBMS,$CIVICRM_DB,
        $CIVICRM_SERVER_USER,$CIVICRM_SERVER_PW);
//      $CIVICRM_SERVER_USER,"xx");
//Orig:
//$dbConn = @new mysqli("$CIVI_IP", "$CIVI_USER", "$CIVI_PASSWORD", "$CIVI_DB");
// Cannot do this - func doesn't exist, or object doesn't.
//$conn->SetFetchMode(ADODB_FETCH_ASSOC);
//$dbConn->SetFetchMode(ADODB_FETCH_ASSOC);

/* mysqli How to trap errors?
 * The die trap does not work.
 * $dbConn = new mysqli("$CIVI_IP", "$CIVI_USER", "$CIVI_PASSWORD", "$CIVI_DB")
     or die($dbConn->connect_error);
 * This works:
 * $dbConn exists even if the connection failed.
 * connect_errno returns 0 if no error.
*/
/* SQLManager - this doesn't trap a real error
   If there is a forced error there is a Fatal further down at a place that doesn't make sense.
*/
$message = $dbConn->error();
if ( $message != "" ) {
    dieHere("$message");
}
else {
    $message = "1CiviCRM connection did not fail";
    if ( $debug == 1) 
        echo "$message\n";
}
if ( FALSE && $dbConn->error() ) {
    $message = sprintf("Connect1 failed: %s\n", $dbConn->error());
    dieHere("$message");
    //die("dying ...");
    //exit();
}
//echo "Hello?\n";
$message = "2CiviCRM connection did not fail";
//dieHere("$message");
if ( $debug == 1) 
    echo "$message\n";

/* Assignment doesn't fail.
   But it doesn't affect the behaviour of fetch_array
     If not assigned, is DEFAULT
//$dbConn->fetchMode = ADODB_FETCH_NUM;
$fm = $dbConn->fetchMode;
// If no assignment this shows "" but matches FETCH_DEFAULT.
echo "fm: >{$fm}<\n";
switch ($fm) {
    // 3
    case ADODB_FETCH_BOTH:
        echo "Is both\n";
        break;
    // 2
    case ADODB_FETCH_ASSOC:
        echo "Is assoc\n";
        break;
    // 1
    case ADODB_FETCH_NUM:
        echo "Is num\n";
        break;
    // 0
    case ADODB_FETCH_DEFAULT:
        echo "Is default\n";
        break;
    case "":
        echo "Is ''\n";
        break;
    case FALSE:
        echo "Is FALSE\n";
        break;
    default:
    echo "Ain't nothin\n";
}
dieHere("After fm");
*/

// Little tests of civicrm connection.
if (0) {

    $selectCivi = "SELECT id, contact_id from civicrm_membership LIMIT 5;";
    $civim = $dbConn->query("$selectCivi");
    // Does not complain about error in MySQL statement.
    // See $LOGS/queries.log for them.
    if ( $dbConn->errno ) {
        $message = printf("Select failed: %s\n", $dbConn->error);
        dieHere("$message");
    }

    // Quick test.
    echo "Civi Members Numbered\n";
    // PHP Fatal error:  Call to undefined method ADORecordSet_mysql::fetch_row() in /var/www/IS4C/fannie/cron/nightly.update.members.php on line 694
    // PHP Fatal error:  Call to undefined method ADORecordSet_mysql::fetch_array() in /var/www/IS4C/fannie/cron/nightly.update.members.php on line 694
//$res = $sql->query("SELECT month(datetime),year(datetime) FROM dtransactions");
//$row = $sql->fetch_row($res);
    //mysqli: while ( $row = $civim->fetch_row() ) {}
    while ( $row = $dbConn->fetch_array($civim) ) {
        // The numeric keys come first. 0,2,4. Name keys 1, 3, 5.
        $flds = getNameKeys($row);
        //$flds = array_keys($row);
        $lineOut = implode("\t", $flds) . "\n";
        echo $lineOut;
        $lineOut = implode("\t", array($row[id], $row[contact_id])) . "\n";
        echo $lineOut;
        // This gives duplicate values, for each of: first number, then name reference.
        //$lineOut = implode("\t", $row) . "\n";
        // Reduce to one set.
        /*
        $vals = getNameValues($row);
        $lineOut = implode("\t", $vals) . "\n";
        echo $lineOut;
        */
    }
    
    dieHere("Little test c OK, bailing ...");

// Enable/defeat little tests of civicrm connection
}

// Enable/defeat is4c connection
if ( 1 ) {

//echo "Start connection to is4c.\n";
//$dbConn2 = @new mysqli("$IS4C_IP", "$IS4C_USER", "$IS4C_PASSWORD", "$IS4C_DB");

$dbConn2 = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
if ( $dbConn2->connect_errno ) {
    $message = sprintf("Connect2 failed: %s\n", $dbConn2->connect_error);
    dieHere("$message");
    // if ( $dbConn ) {
    //  $dbConn->close();
    // }
    // die("dying ...");
    //exit();
}


  /**
Can we use this?  No
$dbConn2->SetFetchMode(ADODB_FETCH_ASSOC);
  * PEAR DB Compat - do not use internally. 
  *
  * The fetch modes for NUMERIC and ASSOC for PEAR DB and ADODB are identical
  *   for easy porting :-)
  *
  * @param mode The fetchmode ADODB_FETCH_ASSOC or ADODB_FETCH_NUM
  * @returns    The previous fetch mode
  function SetFetchMode($mode)
  {
    $old = $this->fetchMode;
    $this->fetchMode = $mode;

    if ($old === false) {
    global $ADODB_FETCH_MODE;
      return $ADODB_FETCH_MODE;
    }
    return $old;
  }
  */
  /**
    Get a column name by index
    @param $result_object A result set
    @param $index Integer index
    @param $which_connection see method close()
    @return The column name
  function fetch_field($result_object,$index,$which_connection=''){
    if ($which_connection == '')
      $which_connection = $this->default_db;
    return $result_object->FetchField($index);
  }
  */



// Little tests of is4c connection.
if (0) {

    $selectIs4c = "SELECT CardNo, LastName from custdata LIMIT 5;";
    $customers = $dbConn2->query("$selectIs4c");
    // ->errno probably doesn't exist in SQLManager
    if ( $dbConn2->errno ) {
        $message = sprintf("Select failed: %s\n", $dbConn->error);
        dieHere($message);
    }

    // Quick test.
    echo "IS4C Numbered\n";
    while ( $row = $dbConn2->fetch_row($customers) ) {
        // Why does $row contain each field twice?
        //  Because $row is a BOTH list and hash; use hash syntax.
        // array_keys gets both names and numbers.
        //$flds = array_keys($row);
        $flds = getNameKeys($row);
        $lineOut = implode("\t", $flds) . "\n";
        echo $lineOut;
//      echo "count:", count($row), "\n";
        $vals = getNameValues($row);
        $lineOut = implode("\t", $vals) . "\n";
        echo $lineOut;
        $lineOut = implode("\t", array($row[CardNo], $row[LastName])) . "\n";
        echo $lineOut;
    }
    die("IS4C OK, bailing ...");

// Enable/defeat little tests of is4c connection
}

// Enable/defeat clearing testrecords
// For update generally don't want to do this.
if (FALSE) {
    $resultString = clearIS4C();
    if ( $resultString != "OK" ) {
        dieHere("$resultString");
    }
    //dieHere("Done clearing");
// Enable/defeat clearing testrecords
}

// Enable/defeat is4c connection
}

//$selWhere = "r.contribution_type_id is not null";
//$selWhere = "m.id is not null";
//$selWhere = "r.total_amount is null or r.total_amount = 0.00";
//$selWhere = "m.id = 901";
//$selWhere = "c.id = 1840";
$selWhere = "1";

// Syntax: "LIMIT 10"
$selLimit = "";
//$selLimit = "LIMIT 10";

/* Re the big select:
   Try using DISTINCT c.id or DISTINCT m.id. No, that only filters whole identical rows.
   Re the join:
    There must be: _membership, _contact
    There will always be: _email
    There might be one or more: _address and/or _phone and/or _contribution
     The field sequence is similar to the Civi export.

There seem to be two or more records for almost all people.
Happens if there are two+ phones or emails.
But why are there two of. What is the difference:
61  Individual  0       Bina        Mittal                  288 Indian Road         Toronto     1108    1039    416 766 7800    bina.mittal@mitcer.com  0   0   478 4   0   2010-08-11  2010-08-11      5.00    2   1   2010-04-17 00:00:00 3   0
61  Individual  0       Bina        Mittal                  288 Indian Road         Toronto     1108    1039    416 766 7800    bina.mittal@mitcer.com  0   0   478 4   0   2010-08-11  2010-08-11      5.00    2   1   2010-04-17 00:00:00 3   0
There is only one:
42  Individual  0       Graeme      Hussey                                                  graemehussey@yahoo.com  0   0   850 4   0   2009-11-02  2009-11-02      1200.00 7   1   2009-11-02 14:04:00     0
*/
// 'S
$selectMembers = "SELECT DISTINCT
c.id as contact_id
 ,c.contact_type, c.is_opt_out, c.nick_name
 , c.first_name, c.middle_name, c.last_name
 , c.household_name, c.primary_contact_id, c.organization_name ,c.employer_id
,a.street_address ,a.supplemental_address_1 as address1, a.supplemental_address_2 as address2
 ,a.city ,a.postal_code , a.state_province_id, a.country_id
,p.phone
,e.email ,e.on_hold, e.is_bulkmail
,m.id as member_id, m.membership_type_id as mti, m.is_pay_later as mipl
 ,m.join_date, m.start_date, m.end_date 
,r.total_amount
,r.contribution_type_id as cti
,r.contribution_status_id as csi
,r.receive_date
,r.payment_instrument_id as cpi
,r.is_pay_later as cipl
,v.member_card_number2_21 as mcard
FROM
civicrm_membership m INNER JOIN civicrm_contact c ON c.id = m.contact_id
LEFT JOIN civicrm_email e ON m.contact_id = e.contact_id
LEFT JOIN civicrm_address a ON m.contact_id = a.contact_id
LEFT JOIN civicrm_phone p ON m.contact_id = p.contact_id
LEFT JOIN civicrm_contribution r ON m.contact_id = r.contact_id
LEFT JOIN civicrm_value_identification_and_cred_5 v ON m.contact_id = v.entity_id
WHERE $selWhere
ORDER BY c.id, r.contribution_type_id
$selLimit;";

$members = $dbConn->query("$selectMembers");
//$members = $dbConn->query("$selectMembers", MYSQLI_STORE_RESULT);
// How to trap error?
if ( $dbConn->errno ) {
    $message = sprintf("Select failed: %s\n", $dbConn->error);
    dieHere("$message");
    //$dbConn->close();
    //die("dying ...");
    //exit();
}

if ( $version = 1 ) {
    $outer = fopen("$outFile", "w");
    if ( ! $outer ) {
        //$dbConn->close();
        dieHere("Could not open $outFile\n");
    }
}

$logger = fopen("$logFile", "a");
if ( ! $logger ) {
    dieHere("Could not open $logFile\n");
}

//echo "After ->query \n";
// Would it help to have both name and number keys? Don't think so.
// was fetch_assoc
while ( $row = $dbConn->fetch_row($members) ) {

    $inCount++;
    if ( $inCount == 1 ) {
        // Write the names of the fields as column heads to the export file.
        //$flds = array_keys($row);
        $flds = getNameKeys($row);
        //echo "Fields: ", implode(" ", $flds), "\n";
        $lineOut = implode("\t", $flds) . "\n";
        $writeOK = fwrite($outer, $lineOut);
        //if ( $inCount > 0 ) { break; }
    }

    if ( TRUE && $row['contact_id'] == $lastCid ) {
        $dupCid++;
        $isDupCid = 1;
        // continue;
    } else {
        $uniqueCid++;
        $isDupCid = 0;
        $lastCid = "$row[contact_id]";
    }

    /* For the first record for each member (same contact_id):
         o If there is data for the previous member, update or insert it and clear the working vars.
         Assume that the name and contact-point data is equally good in all rows.
         + Compose the member-number for custdata.CardNo from m.id = member_id + memberIdOffset
         + For Household or if first_name contains " and "
             o Try to parse/compose separate first and last names
    */
    if ( $isDupCid == 0 ) {
        // If this isn't the first pass.
        if ( $writeIS4C && $custdata[CardNo] != "" ) {

//echo "To DML is4cOp >${is4cOp}< CardNo >$custdata[CardNo]<\n";
            // Is the op for the just-finished member to be insert or update?
            if ( True || $is4cOp1 == "insert" ) {
                // Insert the records for this Individual, Household or Organization.
                $resultString = insertToIS4C();
                if ( $resultString != "OK" ) {
                    //$dbConn->close();
                    //$dbConn2->close();
                    dieHere("$resultString");
                }
                //$insertCount++;
            }
            // update existing members.
            if ( True || $is4cOp1 == "update" ) {
                $resultString = updateIS4C();
                if ( $resultString != "OK" ) {
                    //$dbConn->close();
                    //$dbConn2->close();
                    dieHere("$resultString");
                }
                //$updateCount++;
            }
            /*
            else {
                //$dbConn->close();
                //$dbConn2->close();
                dieHere("Unknown is4cOp >${is4cOp}< CardNo >$custdata[CardNo]<\n");
            }
            */

            // Each IS4C table is represented by an assoc array.
            clearWorkVars();
            $is4cOp = array();
            $is4cOp1 = "";
            $customers = array();

        }

        /* Populate the IS4C-data arrays.
             $row element names do not include the table prefix "c." etc.
        */
        /* custdata
        */
        $custdata[CardNo] = $row[member_id] + $memberIdOffset;

        // #'sIs this member already in IS4C?
        $customers = searchIS4C($custdata[CardNo]);
        // Error is in [0][0]
        // Is waiting to process the error here worth it?
        if ( preg_match("/^Error/", $customers[0][0]) ) {
            //$dbConn->close();
            //$dbConn2->close();
            // Q: Why do these print "Array[0]"? The test of $customers[0][0] works.
            // A: braces are needed.
            dieHere("{$customers[0][0]}");
        }
        // Decide where the operation to each IS4C table will be update or insert.
        //  There is another test of whether there is anything to add/change.
        $is4cOp = array();
        if ( count($customers) == 0 ) {
            $is4cOp1 = "insert";
            foreach ($is4cTableNames as $table) {
                $is4cOp["$table"] = "insert";
            }
            $insertCount++;
        } else {
            $is4cOp1 = "update";
            // Find out wether the operation to each table will be insert or update.
            $is4cOp = searchIS4C2($custdata[CardNo]);
            if ( preg_match("/^Error/", $is4cOp[0][0]) ) {
                dieHere("{$is4cOp[0][0]}");
            }
            $updateCount++;
        }

        // This lets autoincrement do its thing.
        $custdata[id] = "";
        // Fields that are the same for all.
        $custdata[CashBack] = 999.99;   // double
        $custdata[Type] = "PC";
        $custdata[memType] = $row[mti]; // int

        // See if the record is for more than one person.
        $isMultiple = 0; $isMultipleFirst = 0; $isMultipleLast = 0;
        $firstNames = array();
        $lastNames = array();
        $insertCustdata = array();
        // If so, flag and prepare the first person data.
        // Organization
        if ( $row[household_name] != "" ) {
            // E.g. "Inge and John Crowther", "Klucha / Northrup", "Annandale/Stevenson"
            // The "Crowther" example probably shouldn't be done that way.  They are also Org, Clover Roads.
            $lastNames = preg_split("/ ?\/ ?/", $row[household_name]);
            if ( count($lastNames) > 1 ) {
                $isMultipleLast = 1;
            }
            $i = -1;
            foreach ($lastNames as $lastName) {
                $i++;
                // Why does assignment to fN[] not work here? I think it does.  Some other problem.
                $firstNames[$i] = $row[$first_name];
                //echo "lastNames $i >",$lastNames[$i],"< $row[last_name]\n";
                //echo "Names $i >",$lastNames[$i],"<  >$row[first_name]<\n";
            }
        }
        // Organization
        elseif ( $row[organization_name] != "" ) {
            $lastNames[] = $row[organization_name];
            $firstNames[] = $row[first_name];
            //echo "Names 0 >",$lastNames[0],"<  >$row[first_name]<\n";
        }
        // Individual coded for multiple
        elseif ( preg_match("/ and /", $row[first_name]) ) {
            // E.g. Irina and Ionel
            $firstNames = explode(" and ", $row[first_name]);
            // $isMultipleFirst = 1; // not used
            foreach ($firstNames as $firstName) {
                $lastNames[] = $row[last_name];
            }
        }
        // Regular, i.e. single-name, Individual
        else {
            $firstNames[] = $row[first_name];
            $lastNames[] = $row[last_name];
            1;
        }

        // Make a custdata record for each person.
        for ($personNum = 1 ; $personNum <= count($firstNames); $personNum++ ) {
            $custdata[personNum] = $personNum;
            // Index to names arrays.
            $i = $personNum - 1;
            $custdata[FirstName] = fixName($firstNames[$i]);
            $custdata[LastName] = fixName($lastNames[$i]);
            // blueLine should start with a '"', but in case of quoting chaos,
            //  wait until otherwise working.
            $custdata[blueLine] = "\"$custdata[CardNo] $custdata[LastName]";

            // Is this premature?  Contribution recordds not examined.
            if ( $is4cOp[custdata] == "insert" ) {
                // $insertCustdata is an array of statements to execute later.
                $insertCustdata[$i] = "INSERT INTO custdata (
CardNo,
personNum,
LastName,
FirstName,
CashBack,
Type,
memType,
blueLine,
id)
VALUES (
$custdata[CardNo],
$custdata[personNum],
'$custdata[LastName]',
'$custdata[FirstName]',
$custdata[CashBack],
'$custdata[Type]',
$custdata[memType],
'$custdata[blueLine]',
'$custdata[id]'
);";
            }
            elseif ( $is4cOp[custdata] == "update" ) {
                // $updateCustdata is an array of statements to execute later.
                $updateCustdata[$i] = "UPDATE custdata SET 
LastName = '$custdata[LastName]'
, FirstName = '$custdata[FirstName]'
, blueLine = '$custdata[blueLine]'
WHERE CardNo = $custdata[CardNo]
AND
personNum = $custdata[personNum]
;";
            }
            else {
                echo "Bad is4cOp >{$is4cOp[custdata]}<\n";
                1;
            }

        // each person on the card
        }

        /* meminfo
        */
        $meminfo[card_no] = $custdata[CardNo];
        // Need fixAddress to capitalize first letter of each word.
        $meminfo[street] = fixAddress($row[street_address]);
            if ( $row[supplemental_address_1] != "" ) {
                $meminfo[street] .= ", $row[supplemental_address_1]";
            }
            if ( $row[supplemental_address_2] != "" ) {
                $meminfo[street] .= ", $row[supplemental_address_2]";
            }
        // Need fixCity to capitalize first letter.
        $meminfo[city] = fixCity($row[city]);
        $meminfo[state] = getProvince($row[state_province_id], 1);
        $meminfo[zip] =  fixPostalCode($row[postal_code]);
        $meminfo[phone] = fixPhone($row[phone]);
        $meminfo[email_1] = $row[email];
        // Use for 2nd phone is there is one. None I know of.
        $meminfo[email_2] = "";
        // What should the source for this be?
        $meminfo[ads_OK] = "1";

        if ( $is4cOp[meminfo] == "insert" ) {
            // Compose the insert statement.
            $insertMeminfo = "INSERT INTO meminfo (
card_no
,street
,city
,state
,zip
,phone
,email_1
,email_2
,ads_OK
)
VALUES (
$meminfo[card_no]
, '$meminfo[street]'
, '$meminfo[city]'
, '$meminfo[state]'
, '$meminfo[zip]'
, '$meminfo[phone]'
, '$meminfo[email_1]'
, '$meminfo[email_2]'
, $meminfo[ads_OK]
);";

        // update
        } else {
            $updateMeminfo = "UPDATE meminfo SET
street =  '$meminfo[street]'
,city = '$meminfo[city]'
,state = '$meminfo[state]'
,zip = '$meminfo[zip]'
,phone = '$meminfo[phone]'
,email_1 = '$meminfo[email_1]'
,email_2 = '$meminfo[email_2]'
,ads_OK = $meminfo[ads_OK]
WHERE card_no = $meminfo[card_no]
;";
        }

        /* memDates
             Date the person became a member.
             May change if expiry implemented, so code.
        */
        if ( $row[start_date] != "" ) {

            $memDates[card_no] = $custdata[CardNo];
            // Civi is date, IS4C is datetime
            //   The time part is set to 00:00:00
            // Is conversion needed? Seems OK without.
            $memDates[start_date] = $row[start_date];
            if ( $row[end_date] != "" ) {
                $memDates[end_date] = $row[end_date];
            }

            if ( $is4cOp[memDates] == "insert" ) {
                // Compose the insert statement.
                $insertMemDates = "INSERT INTO memDates (
card_no
,start_date
,end_date
)
VALUES (
$memDates[card_no]
, '$memDates[start_date]'
, '$memDates[end_date]'
);";
            } else {
                // Compose the update statement.
                $updateMemDates = "UPDATE memDates SET
start_date = '$memDates[start_date]'
, end_date = '$memDates[end_date]'
WHERE card_no = $memDates[card_no]
;";
            }

        // memDates, if anything to record.
        }

        /* memContact
             Preference about being contacted.
                0 => no contact
                1 => snail mail  # WEFC doesn't do, so not used.
                2 => email  # Default.
                3 => both   # Not used.
             May want to do only if "no".
        */
        // Assign, regardless of value.
        if ( TRUE || $row[is_opt_out] = 1 ) {
            $memContact[card_no] = $custdata[CardNo];
            // Civi is date, IS4C is datetime
            if ( $row[is_opt_out] == 1 ) {
                // no contact
                $memContact[pref] = 0;
            } else {
                // email
                $memContact[pref] = 2;
            }

            if ( $is4cOp[memContact] == "insert" ) {
                // Compose the insert statement.
                $insertMemContact = "INSERT INTO memContact (
card_no
,pref
)
VALUES (
$memContact[card_no]
, '$memContact[pref]'
);";

            } else {
                // Compose the update statement.
                $updateMemContact = "UPDATE memContact SET
pref = '$memContact[pref]'
WHERE card_no = $memContact[card_no]
;";
            }

        // memContact, do or not.
        }

        /* #'m memberCards
        */
        if ( $row[mcard] != "" && $row[mcard] != "0" ) {

            $memberCards[card_no] = $custdata[CardNo];
            $memberCards[upc] = sprintf("00401229%05d", $row[mcard]);

            if ( $is4cOp[memberCards] == "insert" ) {
                // Compose the insert statement.
                $insertMemberCards = "INSERT INTO memberCards (
card_no
,upc
)
VALUES (
$memberCards[card_no]
, '$memberCards[upc]'
);";
            } else {
                // Compose the update statement.
                $updateMemberCards = "UPDATE memberCards SET
upc = '$memberCards[upc]'
WHERE card_no = $memberCards[card_no]
;";
            }

        // memberCards, if anything to record.
        }

        /* stockpurchases
        */

        // Local monitor
        if ( ($uniqueCid % 10) == 0 ) {
            //echo "Done: $uniqueCid members.\n";
            1;
        }

    // Each unique Cid (member)
    }

    $vals = getNameValues($row);
    $lineOut = implode("\t", $vals) . "\n";
    $writeOK = fwrite($outer, $lineOut);
    if ( ! $writeOK ) {
        //$dbConn->close();
        dieHere("Could not write to $outFile\n");
    }
    $outCount++;

// each Civi $row
}

// Do the last Civi row.
if ( $writeIS4C ) {

//echo "To DML is4cOp >${is4cOp}< CardNo >$custdata[CardNo]<\n";
    // Is the op for the just-finished member to be insert or update?
    if ( True || $is4cOp1 == "insert" ) {
        // Insert the records for this Individual, Household or Organization.
        $resultString = insertToIS4C();
        if ( $resultString != "OK" ) {
            //$dbConn->close();
            //$dbConn2->close();
            dieHere("$resultString");
        }
        //$insertCount++;
    }
    // update existing members.
    if ( True || $is4cOp1 == "update" ) {
        $resultString = updateIS4C();
        if ( $resultString != "OK" ) {
            //$dbConn->close();
            //$dbConn2->close();
            dieHere("$resultString");
        }
        //$updateCount++;
    }

    /*
    else {
        //$dbConn->close();
        //$dbConn2->close();
        dieHere("Unknown is4cOp1 >${is4cOp1}< CardNo >$custdata[CardNo]<\n");
    }
    */

// The last Civi row.
}

if ( $debug == 1) 
    echo "Reported on $inCount rows.\n";

if ( $debug == 1) 
    echo "Bye.\n";

/* Logging and reporting
*/
if ( $version = 1 ) {
    if ( $debug == 1) 
        echo "Wrote $outCount lines to ${outFile} dupCid: $dupCid\n";
    1;
}

$subject = "PoS: Update IS4C members: added $insertCount";
$message = "Added: $insertCount  Updated: $updateCount\n";
$adminString = implode(" ", $admins);

if ( $debug == 1) {
    echo "Not ready to email. subject: $subject  message: $message\n";
} else {
    $lastLine = exec("echo \"$message\" | mail -s \"$subject\" $adminString");
    // Ordinary success returns nothing, or "".
    echo "from mailing: {$lastLine}\n";
}

$now = date("Ymd_M H:i:s e");
$writeOK = fwrite($logger, "$now $message");

//echo cron_msg("Error reloading dlog_15");
echo cron_msg("Success updating members from CiviCRM: $message");

/* Tie up and shut down.
*/

// Close the result set.
// May not be able to do something else on the connection until this is done.
$members->close();
// If MYSQL_USE_RESULT mode is used, the you must:
//$members->mysqli_free_result();
// But maybe $members->close() does the same thing.

if ( $dbConn ) {
    $dbConn->close();
} else {
    //echo "No dbConn to close.\n";
    1;
}
//echo "After ->close \n";
if ( $dbConn2 ) {
    $dbConn2->close();
} else {
    //echo "No dbConn2 to close.\n";
    1;
}

if ( $version = 1 ) {
    fclose($outer);
}

// Close logfile
fclose($logger);

exit();

/*'W --WOODSHED { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

        //$a = count($row); echo "row has $a elements.\n";
        //echo implode("\t", $row), "\n";

FROM civicrm_contact c
INNER JOIN civicrm_membership m ON c.id = m.contact_id
INNER JOIN civicrm_address a on c.id = a.contact_id
INNER JOIN civicrm_phone p on c.id = p.contact_id
INNER JOIN civicrm_email e on c.id = e.contact_id
INNER JOIN civicrm_contribution r on c.id = r.contact_id

//printf ("%s  %s  %s\n", $row['id'], $row['name'], $row['label']);

// Little tests of posdev connection.
if (0) {

    $selectIs4c = "SELECT CardNo, LastName from custdata;";
    $customers = $dbConn2->query("$selectIs4c");
    if ( $dbConn2->errno ) {
        printf("Select failed: %s\n", $dbConn->error);
        $dbConn2->close();
        $dbConn->close();
        die("dying ...");
        //exit();
    }

    // Quick test.
    echo "IS4C Numbered\n";
    while ( $row = $customers->fetch_row() ) {
        $lineOut = implode("\t", $row) . "\n";
        echo $lineOut;
    }
    $dbConn2->close();
    $dbConn->close();
    die("bailing ...");

// Enable/defeat little tests of posdev connection
}

 --woodshed } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
*/
//for "c
/*
*/


?>
