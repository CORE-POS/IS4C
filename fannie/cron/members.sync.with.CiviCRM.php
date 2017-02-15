<?php
/*******************************************************************************

    Copyright 2013 West End Food Co-op, Toronto, ON, Canada

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

/* ChangeLog
 * 24May2015 Option to fill in placeholder NEW MEMBER records after every run.
 * 22Apr2015 For CiviCRM 4.6.2
 *           More vars in the external config file.
 *           members.sync.with.CiviCRM_new405.php
 *           New: get $dummy_membershp_type from CiviCRM
 *           New: move more co-op-specific assignments to the config include().
 */

/* HELP

members.sync.with.CiviCRM.php

Synchronizes membership data between CORE and CiviCRM 4.6.2.
Matches records on custdata.CardNo = civicrm_membership.id
 and updates the older of the pair from the newer.
Add records that don't exist in the other database,
 both ways.

*/

/* members.sync.with.CiviCRM.php
   update IS4C members tables from CiviCRM contact and membership tables
   update CiviCRM contact and membership tables from IS4C members tables
     
 --FUNCTIONALITY { - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
 -=proposed o=in-progress +=working  x=removed/disabled

 + SELECT all of the currently valid members from CiviCRM
 + SELECT all of the currently valid members from IS4C
 + Put them both in an array, sort on member# and date, and decide which record is more recent.
 + Handles IS4C tables: custdata, meminfo, memDates, memContact, memberCards
 .                 Not: stockpurchases, memberNotes
 + Handles Civi tables: _contact, _membership, _email, _phone, _address, _log, table-with-member-card
 .                 Only email, phone, address where is_primary = 1.
 .                 Not: 
 + Sets the datestamp in the target to the same as the datestamp in the source.
 + For entry of new records on the IS4C side, use a special range of custdata.CardNo
 .  When adding to Civi, get a new member# from there and change custdata.CardNo, et al. to that.

 --functionality } - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

 #'Z --COMMENTZ { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

 --commentz } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

*/

//#'F --FUNCTIONS { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/* Return an array of all the IS4C data for a single member.
 *  For WEFC_Toronto there is never more than one person for custdata.CardNo
 *  None of the other tables has more than one row for a given card_no.
 *  In Civi, there can be more than one _phone, _email, _address.
*/
function selectIS4C ($member_id) {

    global $dbConn2;
    global $dieMail;

    $selWhere = "1";

    $selLimit = "LIMIT 1";

    $orderBy = "";

    $distinct = "";

    $selectMember = "SELECT $distinct
c.CardNo
, c.personNum, c.LastName, c.FirstName
, c.memType, c.LastChange
, m.last_name ,m.first_name, m.othlast_name, m.othfirst_name
, m.street ,m.city, m.state ,m.zip
, m.phone , m.email_1, m.email_2
, m.ads_OK
, d.start_date, d.end_date
, t.pref
, r.upc as member_card_upc
FROM custdata c
LEFT JOIN meminfo m ON c.CardNo = m.card_no
LEFT JOIN memDates d ON c.CardNo = d.card_no
LEFT JOIN memContact t ON c.CardNo = t.card_no
LEFT JOIN memberCards r ON c.CardNo = r.card_no
WHERE c.CardNo = $member_id
$orderBy
$selLimit;";

    $member = $dbConn2->query("$selectMember");
    //$member = $dbConn->query("$selectMember", MYSQLI_STORE_RESULT);
    if ( $dbConn2->errno ) {
        $message = sprintf("IS4C select failed: %s\n", $dbConn2->error);
        dieHere("$message", $dieMail);
    }
    if ( ! $member ) {
        $msg = sprintf("selectIS4C failed on: %s", $selectMember);
        dieHere("$msg", $dieMail);
    }

    // Should this test for >1 row? >1 not expected.
    $row = $dbConn2->fetch_row($member);

    return($row);

// selectIS4C
}

/* Return civicrm_email.id of the first non-is_primary for the contact
 *  or 0 if there is none.
*/
function getCiviSecondEmail($contactId) {

    $retVal = 0;

    global $dbConn;
    global $dieMail;

    $sel = "SELECT id FROM civicrm_email
    WHERE contact_id = $contactId AND is_primary = 0
    ORDER BY id
    LIMIT 1";

    $rslt = $dbConn->query("$sel");
    if ( $dbConn->errno ) {
        $message = sprintf("Select failed: %s\n", $dbConn->error);
        dieHere("$message", $dieMail);
    }

    if ( $rslt ) {
        if ( $dbConn->num_rows($rslt) > 0 ) {
            $row = $dbConn->fetch_row($rslt);
            $retVal = $row[id];
        }
    } else {
        $msg = sprintf("getCiviSecondEmail failed on: %s", $sel);
        dieHere("$msg", $dieMail);
    }

    return($retVal);

// getCiviSecondEmail()
}

/* Return civicrm_phone.id of the first non-is_primary for the contact
 *  or 0 if there is none.
*/
function getCiviSecondPhone($contactId) {

    $retVal = 0;

    global $dbConn;
    global $dieMail;

    $sel = "SELECT id FROM civicrm_phone
    WHERE contact_id = $contactId AND is_primary = 0
    ORDER BY id
    LIMIT 1";

    $rslt = $dbConn->query("$sel");
    if ( $dbConn->errno ) {
        $message = sprintf("Select failed: %s\n", $dbConn->error);
        dieHere("$message", $dieMail);
    }

    if ( $rslt ) {
        if ( $dbConn->num_rows($rslt) > 0 ) {
            $row = $dbConn->fetch_row($rslt);
            $retVal = $row[id];
        }
    } else {
        $msg = sprintf("getCiviSecondPhone failed on: %s", $sel);
        dieHere("$msg", $dieMail);
    }

    return($retVal);

// getCiviSecondPhone()
}

/* Return an array of all the is_primary CiviCRM data for a single member.
 * Use is_primary=1 to get only the first/primary email,address,phone
*/
function selectCivi ($member_id) {

    global $dbConn;
    global $dieMail;
    global $memberCardTable;
    global $memberCardField;

    $selWhere = "1";

    // Syntax: "LIMIT 10"
    $selLimit = "";
    //$selLimit = "LIMIT 1";

    $selectMember = "SELECT
c.id as contact_id
 ,c.contact_type, c.is_opt_out
 , c.do_not_email, c.do_not_phone, c.do_not_mail
 , c.nick_name , c.first_name, c.middle_name, c.last_name
 , c.household_name, c.primary_contact_id, c.organization_name ,c.employer_id
,a.street_address ,a.supplemental_address_1 as address1, a.supplemental_address_2 as address2
 ,a.city ,a.postal_code , a.state_province_id, a.country_id
,p.phone
,e.email ,e.on_hold, e.is_bulkmail
,m.id as member_id, m.membership_type_id as mti, m.is_pay_later as mipl
 ,m.join_date, m.start_date, m.end_date, m.status_id as msi
,v.{$memberCardField} as mcard
,s.abbreviation as province
FROM
civicrm_membership m INNER JOIN civicrm_contact c ON c.id = m.contact_id
LEFT JOIN civicrm_email e ON m.contact_id = e.contact_id AND e.is_primary = 1
LEFT JOIN civicrm_address a ON m.contact_id = a.contact_id AND a.is_primary = 1
LEFT JOIN civicrm_phone p ON m.contact_id = p.contact_id AND p.is_primary = 1
LEFT JOIN {$memberCardTable} v ON m.contact_id = v.entity_id
LEFT JOIN civicrm_state_province s ON s.id = a.state_province_id
WHERE m.id = $member_id
ORDER BY c.id
$selLimit;";

    $member = $dbConn->query("$selectMember");
    if ( $dbConn->errno ) {
        $message = sprintf("Select failed: %s\n", $dbConn->error);
        dieHere("$message", $dieMail);
    }
    if ( ! $member ) {
        $msg = sprintf("selectCivi failed on: %s", $selectMember);
        dieHere("$msg", $dieMail);
    }

    $row = $dbConn->fetch_row($member);

    return($row);

// selectCivi
}

/* Assign Civi data to the local IS4C arrays.
 * Create and store the $insert* and $update* statement arrays.
*/
function assignLocalI ($row, $is4cOps, $updated) {

    global $dbConn2;
    // table-arrays.
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

    // DML arrays.
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

    /* custdata
    */
    $custdata[CardNo] = $row[member_id];

    // This lets autoincrement of custdata.id do its thing.
    $custdata[id] = "";
    // Fields that are the same for all.
    $custdata[CashBack] = 999.99;    // double
    $custdata[Type] = "PC";
    $custdata[memType] = $row[mti]; // int

    $firstNames = array();
    $lastNames = array();
    $insertCustdata = array();

    // Organization
    if ( $row[organization_name] != "" ) {
        $lastNames[] = $row[organization_name];
        $firstNames[] = $row[first_name];
        //echo "Names 0 >",$lastNames[0],"<  >$row[first_name]<\n";
    }

    // Regular, i.e. single-name, Individual
    else {
        if ( $row[middle_name] != "" ) {
            $firstNames[] = "{$row[first_name]}|{$row[middle_name]}";
        } else {
            $firstNames[] = $row[first_name];
        }
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
        $custdata[blueLine] = "\"$custdata[CardNo] $custdata[LastName]";
        $custdata[LastChange] = $updated;

        if ( $is4cOps[custdata] == "insert" ) {
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
LastChange,
id)
VALUES (
$custdata[CardNo],
$custdata[personNum],
{$dbConn2->escape($custdata[LastName])},
{$dbConn2->escape($custdata[FirstName])},
$custdata[CashBack],
{$dbConn2->escape($custdata[Type])},
$custdata[memType],
{$dbConn2->escape($custdata[blueLine])},
{$dbConn2->escape($custdata[LastChange])},
{$dbConn2->escape($custdata[id])}
);";
        }
        elseif ( $is4cOps[custdata] == "update" ) {
            // $updateCustdata is an array of statements to execute later.
            $updateCustdata[$i] = "UPDATE custdata SET 
LastName = {$dbConn2->escape($custdata[LastName])}
, FirstName = {$dbConn2->escape($custdata[FirstName])}
, LastChange = {$dbConn2->escape($custdata[LastChange])}
, blueLine = {$dbConn2->escape($custdata[blueLine])}
WHERE CardNo = $custdata[CardNo]
AND
personNum = $custdata[personNum]
;";
        }
        else {
            echo "Bad is4cOps, personNum: $personNum >{$is4cOps[custdata]}<\n";
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
    $meminfo[city] = fixCity($row[city]);
    $meminfo[zip] =  fixPostalCode($row[postal_code]);
    $meminfo[state] = fixProvince($row[state_province_id], $row[province], $meminfo[city], $meminfo[zip]);
    $meminfo[phone] = fixPhone($row[phone]);
    $meminfo[email_1] = $row[email];
    // Use for 2nd phone if there is one. None I know of.
    $meminfo[email_2] = "";
    // What should the source for this be? contact.is_opt_out == 1 -> ads_OK = 0.
    //$meminfo[ads_OK] = "1";
    $meminfo[ads_OK] = ($row[is_opt_out] == "1") ? "0" : "1";

    if ( $is4cOps[meminfo] == "insert" ) {
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
, {$dbConn2->escape($meminfo[street])}
, {$dbConn2->escape($meminfo[city])}
, {$dbConn2->escape($meminfo[state])}
, {$dbConn2->escape($meminfo[zip])}
, {$dbConn2->escape($meminfo[phone])}
, {$dbConn2->escape($meminfo[email_1])}
, {$dbConn2->escape($meminfo[email_2])}
, $meminfo[ads_OK]
);";

    // update
    } else {
        $updateMeminfo = "UPDATE meminfo SET
street =  {$dbConn2->escape($meminfo[street])}
,city = {$dbConn2->escape($meminfo[city])}
,state = {$dbConn2->escape($meminfo[state])}
,zip = {$dbConn2->escape($meminfo[zip])}
,phone = {$dbConn2->escape($meminfo[phone])}
,email_1 = {$dbConn2->escape($meminfo[email_1])}
,email_2 = {$dbConn2->escape($meminfo[email_2])}
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

        if ( $is4cOps[memDates] == "insert" ) {
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
            1 => postal mail only  # WEFC doesn't do, so not used.
            2 => email only    # Default.
            3 => both    # Not used.
         May want to do only if "no".
    */

    $memContact[card_no] = $custdata[CardNo];

    // 29Nov12 WEFC_Toronto really only does email at this point, so ingore the other do_not*
    if ( $row[do_not_email] == 1 ) {
        // no contact
        $memContact[pref] = 0;
    } else {
        // email
        $memContact[pref] = 2;
    }

    if ( $is4cOps[memContact] == "insert" ) {
        // Compose the insert statement.
        $insertMemContact = "INSERT INTO memContact (
card_no
,pref
)
VALUES (
$memContact[card_no]
, $memContact[pref]
);";
    } else {
        // Compose the update statement.
        $updateMemContact = "UPDATE memContact SET
pref = '$memContact[pref]'
WHERE card_no = $memContact[card_no]
;";
    }

    /* #'m memberCards
    */
    if ( $row[mcard] != "" && $row[mcard] != "0" ) {

        $memberCards[card_no] = $custdata[CardNo];
        $memberCards[upc] = sprintf("00401229%05d", $row[mcard]);

        if ( $is4cOps[memberCards] == "insert" ) {
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

// assignLocalI
}


/* Assign IS4C data to the local Civi arrays.
 * Create and store the $insert* and $update* statement arrays.
*/
function assignLocalC ($row, $civiOps, $updated, $civiContactId, $civiMemberId) {

    global $dbConn;
    // Base
    global $civicrm_contact;
    // Membership#
    global $civicrm_membership;
    global $civicrm_membership_status;
    // Membership changes
    global $civicrm_membership_log;
    // email
    global $civicrm_email;
    // addres
    global $civicrm_address;
    // phone
    global $civicrm_phone;
    // Membership card#
    global $civicrm_value_identification_and_cred;
    // Datestamp
    global $civicrm_log;

    global $adminId;

    /* SQL DML statements
    */
    global $insertContact;
    global $insertMembership;
    global $insertMembershipLog;
    global $insertEmail;
    global $insertAddress;
    global $insertPhone;
    global $insertMemberCard;
    global $insertLog;

    global $updateContact;
    global $updateMembership;
    global $updateEmail;
    global $updateAddress;
    global $updatePhone;
    global $updateMemberCard;
    global $updateLog;

    global $memberCardTable;
    global $memberCardField;

    // #'u
    /* In general:
     * - Use the civicrm_* array elements for prepared versions of the data for that table.
     * - Assign prepared versions of the data for that table to the civicrm_* array elements.
     * - Test civiOps[table_name] for whether to prepare an insert or update.
     * - Compose the statement and assign/append it to the statement list for that table.
     * - Create a civicrm_log record (datestamp) to match the IS4C datestamp.
    */

    // 1/0. 1 prevents any communication.
    $civicrm_contact[is_opt_out] = ($row[ads_OK] == 0) ? 1 : 0;

    if ( $row[pref] == 0 ) {
        $civicrm_contact[do_not_email] = 1;
        //$civicrm_contact[do_not_phone] = 1;
        $civicrm_contact[do_not_mail] = 1;
    } elseif ( $row[pref] == 1 ) {
        $civicrm_contact[do_not_email] = 1;
        $civicrm_contact[do_not_mail] = 0;
    // 2 is usual and at WEFC probably means nothing either way about postalmail
    } elseif ( $row[pref] == 2 ) {
        $civicrm_contact[do_not_email] = 0;
        $civicrm_contact[do_not_mail] = 0;
    } elseif ( $row[pref] == 3 ) {
        $civicrm_contact[do_not_email] = 0;
        $civicrm_contact[do_not_mail] = 0;
    } else {
        1;
    }

    // Community Partner or Producer: organizations.
    // IS4C convention s/b: LastName = Organization, FirsName = "" or "Joe Bloggs"
    if ( $row[memType] == 3 || $row[memType] == 5 ) {
        $civicrm_contact[contact_type] = "Organization";
        $civicrm_contact[organization_name] = $row[LastName];
        // Better to split FirstName on " " and assign to both first_name and last_name?
        $civicrm_contact[first_name] = $row[FirstName];
        if ( $row[LastName] != "" ) {
            $civicrm_contact[sort_name] = $row[LastName];
            $civicrm_contact[display_name] = $row[LastName];
        }
        elseif ( $row[email_1] != "" ) {
            $civicrm_contact[sort_name] = $row[email_1];
            $civicrm_contact[display_name] = $row[email_1];
        }
        else {
            1;
        }
    }
    // Eater or Worker
    elseif ( $row[memType] == 4 || $row[memType] == 6 || $row[FirstName] != "" ) {
        $civicrm_contact[contact_type] = "Individual";
        list($first,$middle) = explode("|", $row[FirstName]);
        $civicrm_contact[first_name] = $first;
        $civicrm_contact[middle_name] = $middle;
        $civicrm_contact[last_name] = $row[LastName];
        $row[FirstName] = str_replace("|", " ", $row[FirstName]);
        if ( $row[LastName] != "" ) {
            $civicrm_contact[sort_name] = "{$row[LastName]}, {$row[FirstName]}";
            $civicrm_contact[display_name] = "{$row[FirstName]} {$row[LastName]}";
        }
        elseif ( $row[email_1] != "" ) {
            $civicrm_contact[sort_name] = $row[email_1];
            $civicrm_contact[display_name] = $row[email_1];
        }
        else {
            1;
        }
    } else {
        // Unknown memType, or 1.  Does 1 ever happen? Is not offered in Civi but is in IS4C.
        // c.memType :: _membership.membership_type_id
        1;
    }

    // id is auto_increment.
    if ( $civiOps[civicrm_contact] == "insert" ) {
        $insertContact = "INSERT INTO civicrm_contact
        (id
        , source
        , first_name, middle_name, last_name
        , organization_name, sort_name, display_name
        , is_opt_out
        , do_not_email , do_not_mail
        )
        VALUES
        (''
        , {$dbConn->escape($civicrm_contact[contact_type])}
        , {$dbConn->escape($civicrm_contact[source])}
        , {$dbConn->escape($civicrm_contact[first_name])}
        , {$dbConn->escape($civicrm_contact[middle_name])}
        , {$dbConn->escape($civicrm_contact[last_name])}
        , {$dbConn->escape($civicrm_contact[organization_name])}
        , {$dbConn->escape($civicrm_contact[sort_name])}
        , {$dbConn->escape($civicrm_contact[display_name])}
        , $civicrm_contact[is_opt_out]
        , $civicrm_contact[do_not_email]
        , $civicrm_contact[do_not_mail]
        )";
    }
    else {
        $updateContact = "UPDATE civicrm_contact
        SET
            contact_type = {$dbConn->escape($civicrm_contact[contact_type])}
            , first_name = {$dbConn->escape($civicrm_contact[first_name])}
            , middle_name = {$dbConn->escape($civicrm_contact[middle_name])}
            , last_name = {$dbConn->escape($civicrm_contact[last_name])}
            , organization_name = {$dbConn->escape($civicrm_contact[organization_name])}
            , sort_name = {$dbConn->escape($civicrm_contact[sort_name])}
            , display_name = {$dbConn->escape($civicrm_contact[display_name])}
            , is_opt_out = $civicrm_contact[is_opt_out]
            , do_not_email = $civicrm_contact[do_not_email]
            , do_not_mail = $civicrm_contact[do_not_mail]
        WHERE id = $civiContactId";
    }

    /* Membership
    */
    $civicrm_membership[membership_type_id] = $row[memType];
    // These civi dates are date only, no time.
    $civicrm_membership[join_date] = substr($row[start_date], 0, 10);
    $civicrm_membership[start_date] = substr($row[start_date], 0, 10);
    $civicrm_membership[end_date] = substr($row[end_date], 0, 10);
    if ($civicrm_membership[end_date] == '0000-00-00')
        $civicrm_membership[end_date] = 'NULL';
    else
        $civicrm_membership[end_date] = "'" .  $civicrm_membership[end_date] . "'"; 

    // Skeletal new records were created earlier, inserts never done here. 
    if ( $civiOps[civicrm_membership] == "insert" ) {
        $insertMembership = "INSERT INTO civicrm_membership
        (id, contact_id
        , membership_type_id
        , status_id
        , join_date
        , start_date
        , end_date
        )
        VALUES
        ('', $civiContactId
        , $civicrm_membership[membership_type_id])
        , $civicrm_membership_status[New]
        , '$civicrm_membership[join_date]'
        , '$civicrm_membership[start_date]'
        , $civicrm_membership[end_date]
        )";
    }
    elseif ( $civiOps[civicrm_membership] == "update" ) {
        // Cannot assign status_id because it isn't edited in IS4C
        //  For records created here status_id was intialized on creation.
        $updateMembership = "UPDATE civicrm_membership
        SET
        membership_type_id = $civicrm_membership[membership_type_id]
        , join_date = '$civicrm_membership[join_date]'
        , start_date = '$civicrm_membership[start_date]'
        , end_date = $civicrm_membership[end_date]
        WHERE
        id = $civiMemberId";
        // o> 25Nov13. Used to be on contact_id.
        // contact_id = $civiContactId";
    }
    else {
        $noop = 1;
    }

    /* Membership log
     *  Always insert if done at all.
     *  modified_* agree with civicrm_log
    */
    if ( $civiOps[civicrm_membership_log] == "insert" ) {
        $civicrm_membership_log['modified_id'] = "$adminId";
        $civicrm_membership_log['modified_date'] = "$updated";
        $insertMembership = "INSERT INTO civicrm_membership_log
        (id
        , membership_id
        , status_id
        , start_date
        , end_date
        , modified_id
        , modified_date
        )
        VALUES
        (''
        , $civiMemberId
        , $civicrm_membership_status[Current]
        , '$civicrm_membership[start_date]'
        , $civicrm_membership[end_date]
        , $civicrm_membership_log[modified_id]
        , '$civicrm_membership_log[modified_date]'
        )";
    }

    /* Email(s)
     * For insert is_primary=1
     * For update:
     *  There is always one where is_primary=1
     * + First change the one where is_primary=1
     * + If there is another
     *   + See if there is one with is_primary=0
     *     + If yes, update that one.
     *     + If not, insert one with is_primary=0
    */

    $civicrm_email[email] = $row[email_1];

    if ( $civiOps[civicrm_email] == "insert" ) {
        $civicrm_email[location_type_id] = 1;
        $civicrm_email[is_primary] = 1;
        $civicrm_email[is_bulkmail] = 1;
        $insertEmail[] = "INSERT INTO civicrm_email
        (id, contact_id
        , email
        , location_type_id
        , is_primary
        , is_bulkmail
        )
        VALUES
        ('', $civiContactId
        , {$dbConn->escape($civicrm_email[email])}
        , $civicrm_email[location_type_id]
        , $civicrm_email[is_primary]
        , $civicrm_email[is_bulkmail]
        )";

        // If there is another one insert it, is_primary=0.
        if ( isEmail($row[email_2]) ) {
            $civicrm_email[email] = $row[email_2];
            $civicrm_email[location_type_id] = 2;    // We don't actually know.
            // In fact 0 is default.
            $civicrm_email[is_primary] = 0;
            $insertEmail[] = "INSERT INTO civicrm_email
            (id, contact_id
            , email
            , location_type_id
            , is_primary
            )
            VALUES
            ('', $civiContactId
            , {$dbConn->escape($civicrm_email[email])}
            , $civicrm_email[location_type_id]
            , $civicrm_email[is_primary]
            )";
        }
    }
    else {
        $updateEmail[] = "UPDATE civicrm_email
        SET
        email = {$dbConn->escape($civicrm_email[email])}
        WHERE contact_id = $civiContactId AND is_primary = 1";

        /* If there is another one
         *  Look for the id of one non-primary at Civi
         *   If there is one
         *    update it on id#
         *   If not,
         *    insert it, is_primary=0.
        */
        if ( isEmail($row[email_2]) ) {
            $civicrm_email[email] = $row[email_2];
            $civicrm_email[is_primary] = 0;
            $email_id = getCiviSecondEmail($civiContactId);
            if ( $email_id != 0 ) {
                $updateEmail[] = "UPDATE civicrm_email
                SET
                email = {$dbConn->escape($civicrm_email[email])}
                , is_primary = $civicrm_email[is_primary]
                WHERE id = $email_id";
            }
            else {
                $insertEmail[] = "INSERT INTO civicrm_email
                (id, contact_id
                , email
                , is_primary
                )
                VALUES
                ('', $civiContactId
                , {$dbConn->escape($civicrm_email[email])}
                , $civicrm_email[is_primary]
                )";
            }
        }

    // update Email
    }

    /* Address - IS4C only supports one.
    */
    $row[street] = str_replace("\n"," ",$row[street]);
    $civicrm_address[street_address] = fixAddress($row[street]);
    $civicrm_address[city] = fixCity($row[city]);
    $civicrm_address[postal_code] = fixPostalCode($row[zip]);
    $civicrm_address[state_province_id] = getProvinceId($row[state]);

    if ( $civiOps[civicrm_address] == "insert" ) {
        if ( $civicrm_address[street_address] != "" ) {
            $civicrm_address[location_type_id] = 1;
            $civicrm_address[is_primary] = 1;
            $insertAddress[] = "INSERT INTO civicrm_address
            (id, contact_id
            , street_address
            , city
            , postal_code
            , state_province_id
            , location_type_id
            , is_primary
            )
            VALUES
            ('', $civiContactId
            , {$dbConn->escape($civicrm_address[street_address])}
            , {$dbConn->escape($civicrm_address[city])}
            , {$dbConn->escape($civicrm_address[postal_code])}
            , $civicrm_address[state_province_id]
            , $civicrm_address[location_type_id]
            , $civicrm_address[is_primary]
            )";

        }
    }
    else {
        // This will set-empty but not delete if foo=="".
        $updateAddress[] = "UPDATE civicrm_address
        SET
        street_address = {$dbConn->escape($civicrm_address[street_address])}
        , city = {$dbConn->escape($civicrm_address[city])}
        , postal_code = {$dbConn->escape($civicrm_address[postal_code])}
        , state_province_id = $civicrm_address[state_province_id]
        WHERE contact_id = $civiContactId AND is_primary = 1";

    // update Address
    }


    /* Phone(s)
     * For insert, first: is_primary=1, 2nd: is_primary=0
     * For update:
     *  There is always one where is_primary=1
     * + First change the one where is_primary=1
     * + If there is another
     *   + See if there is one with is_primary=0
     *     + If yes, update that one.
     *     + If not, insert one with is_primary=0
    */

    // Does it need some validation?
    $civicrm_phone[phone] = $row[phone];

    if ( $civiOps[civicrm_phone] == "insert" ) {
        if ( $civicrm_phone[phone] != "" ) {
            $civicrm_phone[location_type_id] = 1;
            $civicrm_phone[is_primary] = 1;
            $insertPhone[] = "INSERT INTO civicrm_phone
            (id, contact_id
            , phone
            , location_type_id
            , is_primary
            )
            VALUES
            ('', $civiContactId
            , {$dbConn->escape($civicrm_phone[phone])}
            , $civicrm_phone[location_type_id]
            , $civicrm_phone[is_primary]
            )";

            // If there is another one insert it, is_primary=0.
            if ( isPhone($row[email_2]) ) {
                $civicrm_phone[phone] = $row[email_2];
                $civicrm_phone[location_type_id] = 2;    // We don't actually know.
                // In fact 0 is default.
                $civicrm_phone[is_primary] = 0;
                $insertPhone[] = "INSERT INTO civicrm_phone
                (id, contact_id
                , phone
                , location_type_id
                , is_primary
                )
                VALUES
                ('', $civiContactId
                , {$dbConn->escape($civicrm_phone[phone])}
                , $civicrm_email[location_type_id]
                , $civicrm_phone[is_primary]
                )";
            }
        }
    }
    else {
        // This will set-empty but not delete if phone=="".
        $updatePhone[] = "UPDATE civicrm_phone
        SET
        phone = {$dbConn->escape($civicrm_phone[phone])}
        WHERE contact_id = $civiContactId AND is_primary = 1";

        /* If there is another one
         *  Look for the id of one non-primary at Civi
         *   If there is one
         *    update it on id#
         *   If not,
         *    insert it, is_primary=0.
        */
        if ( isPhone($row[email_2]) ) {
            $civicrm_phone[phone] = $row[email_2];
            $civicrm_phone[is_primary] = 0;
            $phone_id = getCiviSecondPhone($civiContactId);
            if ( $phone_id != 0 ) {
                $updatePhone[] = "UPDATE civicrm_phone
                SET
                phone = {$dbConn->escape($civicrm_phone[phone])}
                , is_primary = $civicrm_phone[is_primary]
                WHERE id = $phone_id";
            }
            else {
                $insertPhone[] = "INSERT INTO civicrm_phone
                (id, contact_id
                , phone
                , is_primary
                )
                VALUES
                ('', $civiContactId
                , {$dbConn->escape($civicrm_phone[phone])}
                , $civicrm_phone[is_primary]
                )";
            }
        }

    // update Phone
    }

    // Membership card#.
    if ( $row[member_card_upc] != "" ) {
        $civicrm_value_identification_and_cred["$memberCardField"] =
            ltrim(substr($row[member_card_upc],8,5), "0");
    }
    else {
        $civicrm_value_identification_and_cred["$memberCardField"] = 'NULL';
    }
    if ( $civiOps["$memberCardTable"] == "insert" ) {
        $insertMemberCard = "INSERT INTO $memberCardTable
        (id
        , entity_id
        , $memberCardField)
        VALUES
        (''
        , $civiContactId
        , $civicrm_value_identification_and_cred[$memberCardField])";
    }
    else {
        $updateMemberCard = "UPDATE $memberCardTable
        SET $memberCardField = $civicrm_value_identification_and_cred[$memberCardField]
        WHERE entity_id = $civiContactId";
    }

    // Datestamp
    // Create a civicrm_log record (datestamp) to match the IS4C datestamp.
    $civicrm_log['entity_table'] = "civicrm_contact";
    $civicrm_log['entity_id'] = $civiContactId;
    $civicrm_log['data'] = "{$civicrm_log['entity_table']},{$civicrm_log['entity_id']}";
    // This is civicrm_contact.id of an "IS4C" record in Civi.
    $civicrm_log['modified_id'] = "$adminId";
    $civicrm_log['modified_date'] = "$updated";

    $insertLog = "INSERT INTO civicrm_log
    (id
    , entity_table
    , entity_id
    , data
    , modified_id
    , modified_date)
    VALUES
    ('',
    '$civicrm_log[entity_table]',
    $civicrm_log[entity_id],
    '$civicrm_log[data]',
    $civicrm_log[modified_id],
    '$civicrm_log[modified_date]'
    )";

// assignLocalC
}

/* Assign IS4C data to the local Civi arrays.
 * Create and store the $insert* and $update* statement arrays.
 * Only for civicrm_log and maybe civicrm_membership_log.
*/
function assignLocalClog ($row, $civiOps, $updated, $civiContactId, $civiMemberId) {

    global $dbConn;
    // Base
    global $civicrm_contact;
    // Membership#
    global $civicrm_membership;
    global $civicrm_membership_status;
    // Membership changes
    global $civicrm_membership_log;
    // email
    global $civicrm_email;
    // addres
    global $civicrm_address;
    // phone
    global $civicrm_phone;
    // Membership card#
    global $civicrm_value_identification_and_cred;
    // Datestamp
    global $civicrm_log;

    global $adminId;

    /* SQL DML statements
    */
    global $insertContact;
    global $insertMembership;
    global $insertMembershipLog;
    global $insertEmail;
    global $insertAddress;
    global $insertPhone;
    global $insertMemberCard;
    global $insertLog;

    global $updateContact;
    global $updateMembership;
    global $updateEmail;
    global $updateAddress;
    global $updatePhone;
    global $updateMemberCard;
    global $updateLog;

    global $memberCardTable;
    global $memberCardField;

    // #'u
    /* In general:
     * - Use the civicrm_* array elements for prepared versions of the data for that table.
     * - Assign prepared versions of the data for that table to the civicrm_* array elements.
     * - Test civiOps[table_name] for whether to prepare an insert or update.
     * - Compose the statement and assign/append it to the statement list for that table.
     * - Create a civicrm_log record (datestamp) to match the IS4C datestamp.
    */

    /* Membership
    */
    $civicrm_membership[membership_type_id] = $row[memType];
    // These civi dates are date only, no time.
    $civicrm_membership[join_date] = substr($row[start_date], 0, 10);
    $civicrm_membership[start_date] = substr($row[start_date], 0, 10);
    $civicrm_membership[end_date] = substr($row[end_date], 0, 10);
    if ($civicrm_membership[end_date] == '0000-00-00')
        $civicrm_membership[end_date] = 'NULL';
    else
        $civicrm_membership[end_date] = "'" .  $civicrm_membership[end_date] . "'"; 


    /* Membership log
     *  Always insert if done at all.
     *  modified_* agree with civicrm_log
    */
    if ( True && $civiOps[civicrm_membership_log] == "insert" ) {
        $civicrm_membership_log['modified_id'] = "$adminId";
        $civicrm_membership_log['modified_date'] = "$updated";
        $insertMembership = "INSERT INTO civicrm_membership_log
        (id
        , membership_id
        , status_id
        , start_date
        , end_date
        , modified_id
        , modified_date
        , membership_type_id
        )
        VALUES
        (''
        , $civiMemberId
        , $civicrm_membership_status[Current]
        , '$civicrm_membership[start_date]'
        , $civicrm_membership[end_date]
        , $civicrm_membership_log[modified_id]
        , '$civicrm_membership_log[modified_date]'
        , $civicrm_membership[membership_type_id]
        )";
    }

    // Datestamp
    // Create a civicrm_log record (datestamp) to match the IS4C datestamp.
    $civicrm_log['entity_table'] = "civicrm_contact";
    $civicrm_log['entity_id'] = $civiContactId;
    $civicrm_log['data'] = "{$civicrm_log['entity_table']},{$civicrm_log['entity_id']}";
    // This is civicrm_contact.id of an "IS4C" record in Civi.
    $civicrm_log['modified_id'] = "$adminId";
    $civicrm_log['modified_date'] = "$updated";

    $insertLog = "INSERT INTO civicrm_log
    (id
    , entity_table
    , entity_id
    , data
    , modified_id
    , modified_date)
    VALUES
    ('',
    '$civicrm_log[entity_table]',
    $civicrm_log[entity_id],
    '$civicrm_log[data]',
    $civicrm_log[modified_id],
    '$civicrm_log[modified_date]'
    )";

// assignLocalClog
}


/* Insert or Update CiviCRM from IS4C.
*/
function toCivi($mode, $member, $updated) {

    global $civiOps;
    global $debug;
    global $dryRun;
    global $civiTableNames;
    global $civiContactId;
    global $dieMail;
    global $tempMemberRange;

    $funcName = "toCivi";
    if ( $debug )
        goOrDie("In $funcName debug: $debug > ");

    if ($dryRun) {
        echo "Bailing on dryRun.\n";
        return True;
    }

    $newMember = 0;
    $upshot = "";

    if ( $mode == "insert" ) {

        if ( $member > $tempMemberRange ) {

            //#'h Get a contact number from Civi.
            $civiContactId = getNewContactId($member);
            if ( $debug > 0 )
                goOrDie("Got new Civi contact# $civiContactId");
            if ( $civiContactId == 0 ) {
                dieHere("$funcName unable to get new Civi contact#.", $dieMail);
            }

            //#'i Get a member number from Civi.
            $newMember = getNewMemberId($civiContactId);
            if ( $debug > 0 )
                goOrDie("Got new Civi member# $newMember");
            if ( $newMember == 0 ) {
                dieHere("$funcName unable to get new Civi member#.", $dieMail);
            }

            //#'j Change the tempMember to newMember in all IS4C tables.
            $upshot = adjustIS4C($member, $newMember, $updated);
            if ( $debug > 0 )
                goOrDie("Upshot of changing IS4C $member to $newMember : >{$upshot}<");
            if ( $upshot != "OK" ) {
                dieHere("$funcName unable to adjust IS4C for new member.", $dieMail);
            }

            // Rejoin the mainstream to finish the Civi side as though it were an update:
            $mode = "update";
            $member = $newMember;

        }
        // This should never happen.
        //  Means that a member was entered in IS4C with id below the new-record range.
        else {
            $msg = "$funcName insert to Civi from regular IS4C range : " .
                "member: $member <= $tempMemberRange. Not done.";
            if ( $debug > 0 ) {
                goOrDie("$msg");
            }
            problemHere($msg);
            return;
        }
    }

    /* Get all the data from IS4C for this member.
    */
    $row = selectIS4C($member);

    if ($debug) {
        $msg = "IS4C member: $member first: {$row[FirstName]} last: {$row[LastName]}
        street: {$row[street]}
        start_date: {$row[start_date]}
        pref: {$row[pref]}
        upc: {$row[member_card_upc]}
        ";
        goOrDie($msg);
    }

    /* Find out and note whether the operation to each Civi table will be insert or update.
     *  In other words, whether a record for this member exists in each Civi table.
     * Assign civiContactId
    */
    $civiOps = searchCivi2($member, $row);    // see 't
    if ( preg_match("/^Error/", $civiOps[0][0]) ) {
        dieHere("{$civiOps[0][0]}", $dieMail);
    }

    if ($debug) {
        print_r($civiOps);
        //foreach ($civiOps as $key => $value) { echo "civiOps $key :: $value\n";    }
        goOrDie("After searchCivi2: civiContactId: $civiContactId");
    }

    // Populate the local arrays
    // and create the DML statements.
    assignLocalC($row, $civiOps, $updated, $civiContactId, $member);

    if ($debug) {
        goOrDie("Before toCivi DML for member: $member debug: $debug");
        1;
    }

    // Make the changes to Civi tables.
    // The datestamp is an insert to civicrm_log
    $resultString = insertToCivi();
    if ( $resultString != "OK" ) {
        dieHere("$resultString", $dieMail );
    }
    $resultString = updateCivi($mode);
    if ( $resultString != "OK" ) {
        dieHere("$resultString", $dieMail);
    }

    if ($debug) {
        goOrDie("After toCivi DML for member: $member ");
    }

    // Clear for next operation.
    clearCiviWorkVars();
    $civiOps = array();

    return $member;
    //return True;

// toCivi()
}



/* Insert or Update CiviCRM from IS4C.
 * Specialized for only doing insert to civicrm_log
*/
function toCiviLog($mode, $member, $updated) {

    global $civiOps;
    global $debug;
    global $dryRun;
    global $civiTableNames;
    global $civiContactId;
    global $dieMail;
    global $tempMemberRange;

    $funcName = "toCiviLog";
    if ( $debug )
        goOrDie("In $funcName debug: $debug > ");

    if ($dryRun) {
        echo "Bailing on dryRun.\n";
        return True;
    }

    $newMember = 0;
    $upshot = "";

    // For toCiviLog() it never will be.
    if ( $mode == "insert" ) {

        if ( $member > $tempMemberRange ) {

            //#'h Get a contact number from Civi.
            $civiContactId = getNewContactId($member);
            if ( $debug > 0 )
                goOrDie("Got new Civi contact# $civiContactId");
            if ( $civiContactId == 0 ) {
                dieHere("$funcName unable to get new Civi contact#.", $dieMail);
            }

            //#'i Get a member number from Civi.
            $newMember = getNewMemberId($civiContactId);
            if ( $debug > 0 )
                goOrDie("Got new Civi member# $newMember");
            if ( $newMember == 0 ) {
                dieHere("$funcName unable to get new Civi member#.", $dieMail);
            }

            //#'j Change the tempMember to newMember in all IS4C tables.
            $upshot = adjustIS4C($member, $newMember, $updated);
            if ( $debug > 0 )
                goOrDie("Upshot of changing IS4C $member to $newMember : >{$upshot}<");
            if ( $upshot != "OK" ) {
                dieHere("$funcName unable to adjust IS4C for new member.", $dieMail);
            }

            // Rejoin the mainstream to finish the Civi side as though it were an update:
            $mode = "update";
            $member = $newMember;

        }
        // This should never happen.
        //  Means that a member was entered in IS4C with id below the new-record range.
        else {
            $msg = "$funcName insert to Civi from regular IS4C range : " .
                "member: $member <= $tempMemberRange. Not done.";
            if ( $debug > 0 ) {
                goOrDie("$msg");
            }
            problemHere($msg);
            return;
        }
    }

    /* Get all the data from IS4C for this member.
    */
    $row = selectIS4C($member);

    if ($debug) {
        $msg = "IS4C member: $member first: {$row[FirstName]} last: {$row[LastName]}
        street: {$row[street]}
        start_date: {$row[start_date]}
        pref: {$row[pref]}
        upc: {$row[member_card_upc]}
        ";
        goOrDie($msg);
    }

    /* Find out and note whether the operation to each Civi table will be insert or update.
     *  In other words, whether a record for this member exists in each Civi table.
     * Assign civiContactId
    */
    $civiOps = searchCivi2($member, $row);
    //$civiOps['civicrm_log'] = "insert";
    /* If you want _membership_log let searchCivi2() do it.
     * $civiOps['civicrm_membership_log'] = "insert";
     */
    if ( preg_match("/^Error/", $civiOps[0][0]) ) {
        dieHere("{$civiOps[0][0]}", $dieMail);
    }

    if ($debug) {
        print_r($civiOps);
        //foreach ($civiOps as $key => $value) { echo "civiOps $key :: $value\n";    }
        goOrDie("After searchCivi2: civiContactId: $civiContactId");
    }

    // Populate the local arrays
    // and create the DML statements.
    assignLocalClog($row, $civiOps, $updated, $civiContactId, $member);
    //assignLocalC($row, $civiOps, $updated, $civiContactId, $member);

    if ($debug) {
        goOrDie("Before toCivi DML for member: $member debug: $debug");
        1;
    }

    // Make the changes to Civi tables.
    // The datestamp is an insert to civicrm_log
    $resultString = insertToCivi();
    if ( $resultString != "OK" ) {
        dieHere("$resultString", $dieMail );
    }
    /*
    $resultString = updateCivi($mode);
    if ( $resultString != "OK" ) {
        dieHere("$resultString", $dieMail);
    }

    if ($debug) {
        goOrDie("After {$funcName} DML for member: $member ");
    }
     */

    // Clear for next operation.
    clearCiviWorkVars();
    $civiOps = array();

    return $member;
    //return True;

// toCiviLog()
}


/*  Return the number of a newly-created CiviCRM contact or 0 on failure.
 * 
*/
function getNewContactId($tempMember) {

    global $dbConn;
    global $debug;
    global $dieMail;

    $funcName = "getNewContactId";

    $retVal = 0;

    if ( $debug > 0 )
        $ans = readline("In $funcName > ");

    $sql = "INSERT INTO civicrm_contact (last_name) VALUES ('NEW_$tempMember')";
    $rslt = $dbConn->query("$sql");
    if ( $dbConn->errno ) {
        $msg = sprintf("Failed: %s", $dbConn->error);
        dieHere($msg, $dieMail);
    }
    if ( ! $rslt ) {
        $msg = "$funcName failed: $sql";
        dieHere($msg, $dieMail);
    }

    // Get the _contact.id, which was created by auto-increment
    //$retVal = $dbConn->insertID(); // returns 0, don't know why.
    $sql = "SELECT LAST_INSERT_ID()";
    $rslt = $dbConn->query("$sql");
    if ( $dbConn->errno ) {
        $msg = sprintf("Failed: %s", $dbConn->error);
        dieHere($msg, $dieMail);
    }
    if ( ! $rslt ) {
        $msg = "$funcName failed: $sql";
        dieHere($msg, $dieMail);
    }
    $rows = $dbConn->num_rows($rslt);
    if ( $rows > 0 ) {
        $row = $dbConn->fetch_row($rslt);
        $retVal = $row[0];
    } else {
        $retVal = 0;
    }

    if ( $retVal == 0 ) {
        $msg = sprintf("Failed to return new contact id for tempMember: %d", $tempMember);
        dieHere($msg, $dieMail);
    }

    if ( $debug ) {
        $msg = sprintf("Returned new contact id %d for tempMember: %d", $retVal, $tempMember);
        goOrDie("In $funcName :: $msg :: debug: $debug > ");
    }

    return($retVal);

//getNewContactId
}

/* Return the id# of a newly-created CiviCRM membership or 0 on failure.
 * Assign .status_id in the new record.  It isn't maintained in IS4C.
*/
function getNewMemberId($contactId) {

    global $dbConn;
    global $debug;
    global $dieMail;
    global $civicrm_membership_status;
    global $dummy_membership_type;

    $funcName = "getNewMemberId";

    if ( $debug > 0 ) 
        $ans = readline("In $funcName > ");

    $retVal = 0;

    $sql = "INSERT INTO civicrm_membership " .
        "(contact_id, membership_type_id, status_id) " .
        "VALUES ($contactId" .
        ", $dummy_membership_type" .
        ", $civicrm_membership_status[New]" .
    ")";
    $rslt = $dbConn->query("$sql");
    if ( $dbConn->errno ) {
        $msg = sprintf("Failed: %s", $dbConn->error);
        dieHere($msg, $dieMail);
    }
    if ( ! $rslt ) {
        $msg = "$funcName failed: $sql";
        dieHere($msg, $dieMail);
    }

    // Get the _membership.id, which was created by auto-increment
    //$retVal = $dbConn->insertID(); // returns 0, don't know why.
    $sql = "SELECT LAST_INSERT_ID()";
    $rslt = $dbConn->query("$sql");
    if ( $dbConn->errno ) {
        $msg = sprintf("Failed: %s", $dbConn->error);
        dieHere($msg, $dieMail);
    }
    if ( ! $rslt ) {
        $msg = "$funcName failed: $sql";
        dieHere($msg, $dieMail);
    }
    $rows = $dbConn->num_rows($rslt);
    if ( $rows > 0 ) {
        $row = $dbConn->fetch_row($rslt);
        $retVal = $row[0];
    } else {
        $retVal = 0;
    }

    if ( $retVal == 0 ) {
        $msg = sprintf("Failed to return new member id %d for contactId: %d", $retVal, $contactId);
        dieHere($msg, $dieMail);
    }

    if ( $debug ) {
        $msg = sprintf("Returned new member id %d for contactId: %d", $retVal, $contactId);
        goOrDie("In $funcName :: $msg :: debug: $debug > ");
    }

    return($retVal);

//getNewMemberId
}

/* Change the tempMember to newMember in all IS4C tables.
 * Return "OK" on success, message on failure. Or die from here on failure?
 *  In fact it dies on any error. Should it?
*/
function adjustIS4C($tempMember, $newMember, $updated) {

    global $dbConn2;
    global $debug;
    global $is4cTableNames;
    global $is4cOps;
    global $dieMail;

    $funcName = "adjustIS4C";
    if ( $debug > 0 )
        $ans = readline("In $funcName to change $tempMember to $newMember> ");

    $retVal = "";

    //   Check first that it doesn't already exist.  Bad if it does.
    $sql = "SELECT CardNo FROM custdata WHERE CardNo = $newMember";
    $rslt = $dbConn2->query("$sql");
    if ( $dbConn2->errno ) {
        $msg = sprintf("Failed: %s", $dbConn2->error);
        dieHere($msg, $dieMail);
    }
    if ( ! $rslt ) {
        $msg = "In $funcName check for dup new member $newMember failed: $sql";
        dieHere($msg, $dieMail);
    }
    $rows = $dbConn2->num_rows($rslt);
    if ( $rows > 0 ) {
        $msg = "Trouble in $funcName: custdata.CardNo $newMember already exists.";
        dieHere($msg, $dieMail);
    }

    $is4cOps = searchIS4C2($tempMember);
    if ( preg_match("/^Error/", $is4cOps[0][0]) ) {
        dieHere("{$is4cOps[0][0]}", $dieMail);
    }

    // The IS4C NEW MEMBER apparatus creates rows in custdata, meminfo, memContact, memDates,
    //  and in memberCards if there is one.
    // Do custdata first so triggers on other tables will be able to find it.

    $statements = array();
    $statements[] = "UPDATE custdata set CardNo = $newMember, blueLine = CONCAT('\"$newMember ', LastName) WHERE CardNo = $tempMember";
    $statements[] = "UPDATE meminfo set card_no = $newMember WHERE card_no = $tempMember";
    $statements[] = "UPDATE memContact set card_no = $newMember, pref = 2 WHERE card_no = $tempMember";
    $statements[] = "UPDATE memDates set card_no = $newMember WHERE card_no = $tempMember";
    if ( $is4cOps[memberCards] == "update" ) {
        $statements[] = "UPDATE memberCards set card_no = $newMember WHERE card_no = $tempMember";
    }
    $statements[] = "UPDATE memberNotes set cardno = $newMember WHERE cardno = $tempMember";
    //   Set the datestamp to what it was originally, to agree with what Civi is now.
    $statements[] = "UPDATE custdata SET LastChange = '$updated' WHERE CardNo = $newMember";
    $statement = "";

    foreach ($statements as $statement) {
        if ( $statement != "" ) {
            if ( $debug > 0 ) {
                echo $statement, "\n";
                if ( $debug == 1)
                    continue;
            }
            $rslt = $dbConn2->query("$statement");
            if ( $dbConn2->errno ) {
                dieHere(sprintf("$funcName failed: %s\n", $dbConn2->error), $dieMail);
            }
            if ( ! $rslt ) {
                dieHere("$funcName failed: $statement", $dieMail);
            }
        }
    }

    $retVal = "OK";

    return($retVal);

//adjustIS4C()
}

/* Insert or Update IS4C from CiviCRM.
*/
function toIS4C($mode, $member, $updated) {

    global $is4cOps;
    global $debug;
    global $dryRun;
    global $is4cTableNames;
    global $dieMail;

    if ($debug)
        $ans = readline("In toIS4C mode: $mode $member > ");

    // debug=2 allows inserts and updates while displaying messages.
    //$debug = 2;

    if ($dryRun) {
        echo "Bailing on dryRun.";
        return True;
    }

    // Get all the data from Civi for this member.
    // You don't know contact_id at this point.
    $row = selectCivi($member);

    if ( $mode == "insert" ) {
        foreach ($is4cTableNames as $table) {
            $is4cOps["$table"] = "insert";
        }
    } else {
    // Note which IS4C operations will be inserts, which updates.
    // Find out whether the operation to each table will be insert or update.
        $is4cOps = searchIS4C2($member);
        if ( preg_match("/^Error/", $is4cOps[0][0]) ) {
            dieHere("{$is4cOps[0][0]}", $dieMail);
        }
    }

    // Populate the local arrays
    // and create the DML statements.
    assignLocalI($row, $is4cOps, $updated);

    if ($debug) {
        goOrDie("Before DML for member: $member debug: $debug");
        1;
    }

    // Make the changes to IS4C tables.
    $resultString = insertToIS4C($mode);
    if ( $resultString != "OK" ) {
        dieHere("$resultString", $dieMail);
    }
    // Important to update after insert so custdata.LastChange is last thing assigned.
    // Important to update custdata if any related table is inserted or updated.
    $resultString = updateIS4C($mode);
    if ( $resultString != "OK" ) {
        dieHere("$resultString", $dieMail);
    }

    if ($debug) {
        goOrDie("After DML for member: $member ");
    }

    // Clear for next operation.
    //  Better to do at start.
    // Each IS4C table is represented by an assoc array.
    clearIs4cWorkVars();
    $is4cOps = array();    // Is the clearing needed?

    return True;

// toIS4C()
}

// Return the datestamp of the start of the last run if that run finished ok,
//  otherwise return epoch.
function getLatestRun($file) {
    global $dieMail;
    $reporter = fopen("$file", "r");
    if ( ! $reporter ) {
        dieHere("Could not open $file\n", $dieMail);
    }
    
    $finished = False;
    while (($line = fgets($reporter)) !== false) {
        if ( strpos($line, "STARTED") === 0 ) {
            list($junk, $lastStart) = explode(" ", $line, 2);
        }
        if ( strpos($line, "FINISHED_OK") === 0 ) {
            $finished = True;
        }
    }
    fclose($reporter);
    if ( !$finished ) {
        $lastStart = "0000-00-00 00:00:00";
    }
    return($lastStart);

}

// Clear the test records from the IS4C tables.
// CardNo or card_no range between 4000 and 6000
function clearIS4C($low = 417, $high = 1500) {

    global $dbConn2;
    global $is4cTables;
    global $dieMail;

    // Argument to where.
    $clearWhere = "";

    foreach ($is4cTables as $table_desc) {
        list($db, $table, $cn) = explode("|", $table_desc);
        //$clearWhere = "$cn = 4471";
        $clearWhere = "$cn BETWEEN $low AND $high;";
        $query = "DELETE FROM $table WHERE ${clearWhere};";
        if ( TRUE && $db == "core_op" ) {
            $rslt = $dbConn2->query("$query");
            if ( $dbConn2->errno ) {
                $msg = sprintf("DML failed: %s\n", $dbConn2->error);
                dieHere("$msg", $dieMail);
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


/*  Return an array of custdata for the memberId.
        Calling script to test for #-of-items in array: 0=none, >=1 if some.
        Return error-string if the lookup failed.
*/
function searchIS4C($member) {

    global $dbConn2;
    global $dieMail;

    $is4cMembers = array();
    $sel = "SELECT CardNo, personNum, FirstName, LastName FROM custdata where CardNo = ${member};";
    $rslt = $dbConn2->query("$sel");
    if ( $dbConn2->errno ) {
        $msg = sprintf("Error: DQL failed: %s\n", $dbConn2->error);
        $is4cMembers[] = array($msg);
        return($is4cMembers);
    }
    if ( ! $rslt ) {
        $msg = sprintf("searchIS4C failed on: %s", $sel);
        dieHere("$msg", $dieMail);
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

/* Find which IS4C tables the member (card_no) is known in.
 * Return array of member-related table names and whether the operation will be
 *  to insert (add) or update.
 *  $is4cOps[table-name][insert|update]
*/
function searchIS4C2($member) {

    global $dbConn2;
    global $dieMail;

    $is4cOps = array();

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
        $is4cOps[] = array($msg);
        return($is4cOps);
    }
    if ( ! $rslt ) {
        $msg = sprintf("Failed on: %s", $sel);
        dieHere("$msg", $dieMail);
    }

    while ( $row = $dbConn2->fetch_row($rslt) ) {
        $is4cOps['custdata'] = "update";
        $is4cOps['meminfo'] = ( $row[iCard] != "" ) ? "update" : "insert";
        $is4cOps['memContact'] = ( $row[tCard] != "" ) ? "update" : "insert";
        $is4cOps['memDates'] = ( $row[dCard] != "" ) ? "update" : "insert";
        $is4cOps['memberCards'] = ( $row[rCard] != "" ) ? "update" : "insert";
        break;
    }

    return($is4cOps);

// searchIS4C2
}

/* Find which Civi tables the member (contact_id, via member_id) is known in.
 * Return array of member-related table names and whether the operation will be
 *  to insert (add) or update.
 *  $civiOps[table-name][insert|update]
*/
function searchCivi2($member, $is4cData=array()) {

    global $dbConn;
    global $dieMail;
    global $memberCardTable;
    global $memberCardField;
    global $debug;

    // Because we can only return one thing, assign this global to avoid another lookup.
    global $civiContactId;

    $civiOps = array();

    $selLimit = "LIMIT 1";

    //#'t
    $sel = "SELECT DISTINCT
c.id as c_id
,a.contact_id as a_id
,p.contact_id as p_id
,e.contact_id as e_id
,m.contact_id as m_id
    ,m.membership_type_id
    ,m.status_id
    ,m.join_date
    ,m.start_date
    ,m.end_date
,v.entity_id as v_id
FROM
civicrm_membership m
INNER JOIN civicrm_contact c ON c.id = m.contact_id
LEFT JOIN civicrm_email e ON m.contact_id = e.contact_id
LEFT JOIN civicrm_address a ON m.contact_id = a.contact_id
LEFT JOIN civicrm_phone p ON m.contact_id = p.contact_id
LEFT JOIN $memberCardTable v ON m.contact_id = v.entity_id
WHERE m.id = $member
ORDER BY c.id
$selLimit;";

    $rslt = $dbConn->query("$sel");
    // Not all errors, e.g. missing table alias, produce dbConn->errno
    if ( ! $rslt ) {
        $msg = "Returned False: $sel\n";
        $civiOps[] = array($msg);
        return($civiOps);
    }
    if ( $dbConn->errno ) {
        $msg = sprintf("Error: DQL failed: %s\n", $dbConn->error);
        $civiOps[] = array($msg);
        return($civiOps);
    }

    $rows = $dbConn->num_rows($rslt);

    while ( $row = $dbConn->fetch_row($rslt) ) {
        $civiContactId = $row[c_id];
        $civiOps['civicrm_contact'] = "update";
        $civiOps['civicrm_membership'] = ( $row[m_id] != "" ) ? "update" : "insert";
        // Log membership inserts and actual changes.
        //$civiOps['civicrm_membership_log'] = "init";    // for debugging
        if ($civiOps['civicrm_membership'] == "insert") {
            $civiOps['civicrm_membership_log'] = "insert";
        }
        else {
            if ($row[end_date] == 'NULL')
                $row[end_date] = '0000-00-00';
            if (
                //$row[status_id] == $is4cData[foo] // if IS4C stores status.
                substr($row[start_date],0,9) == substr($is4cData[start_date],0,9) &&
                substr($row[end_date],0,9) == substr($is4cData[end_date],0,9) &&
                $row[membership_type_id] == $is4cData[memType]
            ) {
                $civiOps['civicrm_membership'] = "none";
                $civiOps['civicrm_membership_log'] = "none";
            }
            else {
                $civiOps['civicrm_membership_log'] = "insert";
            }
        }
        $civiOps['civicrm_email'] = ( $row[e_id] != "" ) ? "update" : "insert";
        $civiOps['civicrm_address'] = ( $row[a_id] != "" ) ? "update" : "insert";
        $civiOps['civicrm_phone'] = ( $row[p_id] != "" ) ? "update" : "insert";
        $civiOps["$memberCardTable"] = ( $row[v_id] != "" ) ? "update" : "insert";
        $civiOps['civicrm_log'] = "insert";
        break;
    }

    if ( $debug )
        goOrDie("In searchCivi2: rows: $rows  civiContactId: $civiContactId");

    return($civiOps);

// searchCivi2
}

// Insert any new records for this Individual or Organization.
// Return "OK" if all OK or abort returning message on any error.
function insertToIS4C($mode) {

    global $dbConn2;
    global $dieMail;

    global $insertCustdata;
    global $insertMeminfo;
    global $insertMemContact;
    global $insertMemDates;
    global $insertMemberCards;
    global $insertStockpurchases;

    global $debug;

    $statements = array($insertMeminfo,
        $insertMemContact,
        $insertMemDates,
        $insertMemberCards);
    $statement = "";

    if ( $debug > 0 )
        echo "In insertToIS4C debug: $debug\n";

    foreach ($statements as $statement) {
        if ( $statement != "" ) {
            if ( $debug > 0 ) {
                echo $statement, "\n";
                if ( $debug == 1 )
                    continue;
            }
            $rslt = $dbConn2->query("$statement");
            if ( $dbConn2->errno ) {
                return(sprintf("Error: Insert failed: %s\n", $dbConn2->error));
            }
            if ( ! $rslt ) {
                return("Failed: $statement");
            }
        }
    }

    // Must do custdata last to assign LastChange rather than have its value
    //  come as a result of triggers on related tables.
    // Will those triggers barf if custdata doesn't exist?
    //  If so must update custdata after other inserts.
    if ( count($insertCustdata) > 0 ) {
        foreach ($insertCustdata as $statement) {
            if ( $debug > 0 ) {
                echo $statement, "\n";
                if ( $debug == 1) {
                    echo "NOT executed.\n";
                    continue;
                }
            }
            $rslt = $dbConn2->query("$statement");
            if ( $dbConn2->errno ) {
                return(sprintf("Error: Insert failed: %s\n", $dbConn2->error));
            }
            if ( ! $rslt ) {
                return("Failed: $statement");
            }
        }
    }
    else {
        if ( $debug > 0 )
            echo "No custdata to insert. Mode: $mode\n";
        1;
    }

    // stockpurchases is in a different db.

    return("OK");

// insertToIS4C
}

// Run any updates to the records for this Individual, Household or Organization.
// Return "OK" if all OK or abort returning message on any error.
function updateIS4C($mode) {

    global $dbConn2;
    global $dieMail;

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

    if ( $debug > 0 )
        echo "In updateIS4C debug: $debug\n";

    foreach ($statements as $statement) {
        if ( $statement != "" ) {
            if ( $debug > 0 ) {
                echo $statement, "\n";
                if ( $debug == 1) {
                    echo "NOT executed.\n";
                    continue;
                }
            }
            $rslt = $dbConn2->query("$statement");
            if ( $dbConn2->errno ) {
                return(sprintf("Error: Update failed: %s\n", $dbConn2->error));
            }
            if ( ! $rslt ) {
                return("Failed: $statement");
            }
        }
    }

    // Must do custdata last to assign LastChange rather than have its value
    //  come as a result of triggers on related tables.
    if ( count($updateCustdata) > 0 ) {
        foreach ($updateCustdata as $statement) {
            if ( $debug > 0 )  {
                echo $statement, "\n";
                if ( $debug == 1 )
                    continue;
            }
            $rslt = $dbConn2->query("$statement");
            if ( $dbConn2->errno ) {
                return(sprintf("Error: Update failed: %s\n", $dbConn2->error));
            }
            if ( ! $rslt ) {
                return("Failed: $statement");
            }
        }
    }
    else {
        if ( $debug > 0 )
            echo "No custdata to update. Mode: $mode\n";
        1;
    }

    // stockpurchases is in a different db.

    return("OK");

// updateIS4C
}

// Insert any new records for this Individual or Organization.
// Return "OK" if all OK or abort returning message on any error.
function insertToCivi() {

    global $dbConn;
    global $dieMail;

    global $insertContact;
    global $insertMembership;
    global $insertEmail;
    global $insertAddress;
    global $insertPhone;
    global $insertMemberCard;
    global $insertLog;

    global $debug;

    $statements = array();
    $statements[] = $insertContact;
    $statements[] = $insertMembership;
    $statements[] = $insertMembershipLog;
    $statements = array_merge($statements,$insertEmail);
    $statements = array_merge($statements,$insertAddress);
    $statements = array_merge($statements,$insertPhone);
    $statements[] = $insertMemberCard;
    $statements[] = $insertLog;

    $statement = "";

    if ( $debug > 0 )
        echo "In insertToCivi debug: $debug\n";

    foreach ($statements as $statement) {
        if ( $statement != "" ) {
            if ( $debug > 0 ) {
                echo $statement, "\n";
                if ( $debug == 1 )
                    continue;
            }
            $rslt = $dbConn->query("$statement");
            if ( $dbConn->errno ) {
                return(sprintf("Error: Insert failed: %s\n", $dbConn->error));
            }
            if ( ! $rslt ) {
                return("Failed: $statement");
            }
        }
    }

    return("OK");

// insertToCivi
}

// Run any Updates to the records for this Individual, Household or Organization.
// Return "OK" if all OK or abort returning message on any error.
function updateCivi() {

    global $dbConn;
    global $dieMail;

    global $updateContact;
    global $updateMembership;
    global $updateEmail;
    global $updateAddress;
    global $updatePhone;
    global $updateMemberCard;
    // civicrm_log is never updated, only inserted-to.
    //global $updateLog;

    global $debug;

    $statements = array();
    $statements[] = $updateContact;
    $statements[] = $updateMembership;
    $statements = array_merge($statements,$updateEmail);
    $statements = array_merge($statements,$updateAddress);
    $statements = array_merge($statements,$updatePhone);
    $statements[] = $updateMemberCard;

    $statement = "";

    if ( $debug > 0 )
        echo "In updateCivi debug: $debug\n";

    foreach ($statements as $statement) {
        if ( $statement != "" ) {
            if ( $debug > 0 ) {
                echo $statement, "\n";
                if ( $debug == 1)
                    continue;
            }
            $rslt = $dbConn->query("$statement");
            if ( $dbConn->errno ) {
                return(sprintf("Error: Update failed: %s\n", $dbConn->error));
            }
            if ( ! $rslt ) {
                return("Failed: $statement");
            }
        }
    }

    return("OK");

// updateCivi
}

// Each IS4C table is represented by an assoc array.
function clearIs4cWorkVars() {

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
//    $custdata[CardNo] = 0;
//    $custdata[personNum] = 0;
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

// clearIs4cWorkVars
}

/* Each CiviCRM table is represented by an assoc array.
 * Lists of insert and update statements.
*/
function clearCiviWorkVars() {

    /* The table arrays.
    */
    // Base
    global $civicrm_contact;
    // Membership#
    global $civicrm_membership;
    // Membership log
    global $civicrm_membership_log;
    // email
    global $civicrm_email;
    // addres
    global $civicrm_address;
    // phone
    global $civicrm_phone;
    // Membership card#
    global $civicrm_value_identification_and_cred;
    // Datestamp
    global $civicrm_log;

    $flds = array_keys($civicrm_contact);
    foreach ($flds as $field) {
        $civicrm_contact[$field] = "";
    }

    $flds = array_keys($civicrm_membership);
    foreach ($flds as $field) {
        $civicrm_membership[$field] = "";
    }

    $flds = array_keys($civicrm_membership_log);
    foreach ($flds as $field) {
        $civicrm_membership_log[$field] = "";
    }

    $flds = array_keys($civicrm_email);
    foreach ($flds as $field) {
        $civicrm_email[$field] = "";
    }

    $flds = array_keys($civicrm_address);
    foreach ($flds as $field) {
        $civicrm_address[$field] = "";
    }

    $flds = array_keys($civicrm_phone);
    foreach ($flds as $field) {
        $civicrm_phone[$field] = "";
    }

    $flds = array_keys($civicrm_value_identification_and_cred);
    foreach ($flds as $field) {
        $civicrm_value_identification_and_cred[$field] = "";
    }

    $flds = array_keys($civicrm_log);
    foreach ($flds as $field) {
        $civicrm_log[$field] = "";
    }

    /* SQL DML statements
    */
    global $insertContact;
    global $insertMembership;
    global $insertMembershipLog;
    global $insertEmail;
    global $insertAddress;
    global $insertPhone;
    global $insertMemberCard;
    global $insertLog;

    global $updateContact;
    global $updateMembership;
    global $updateEmail;
    global $updateAddress;
    global $updatePhone;
    global $updateMemberCard;
    global $updateLog;

    /* SQL insert statements
     *  Are arrays if multiple is possible, e.g. email or phone
    */
    $insertContact = "";
    $insertMembership = "";
    $insertMembershipLog = "";
    $insertEmail = array();
    $insertAddress = array();
    $insertPhone = array();
    $insertMemberCard = "";
    $insertLog = "";

    /* SQL update statements
     *  Are arrays if multiple is possible, e.g. email or phone
    */
    $updateContact = "";
    $updateMembership = "";
    $updateAddress = array();
    $updateEmail = array();
    $updatePhone = array();
    $updateMemberCard = "";
    $updateLog = "";

// clearCiviWorkVars
}


// Return civicrm_state_province.id for the argument or NULL if not known. 
function getProvinceId($str) {

    global $dbConn;
    global $dieMail;

    $retVal = "";

    $str = strtoupper($str);
    // if ( $str == "ON" ) { $retVal = 1108; }

    $sel = "SELECT id, country_id
    FROM civicrm_state_province
    WHERE abbreviation = '$str' AND country_id in (1228,1039) LIMIT 1";
    $rslt = $dbConn->query("$sel");
    if ( $dbConn->errno ) {
        $msg = sprintf("Failed: %s", $dbConn->error);
        dieHere("$msg", $dieMail);
    }
    if ( $rslt ) {
        $row = $dbConn->fetch_row($rslt);
        $retVal = $row[id];
        if ( $retVal == "" ) {
            $retVal = "NULL";
        }
    } else {
        $msg = sprintf("getProvinceId failed on: %s", $sel);
        dieHere("$msg", $dieMail);
    }

    return($retVal);

//getProvinceId
}

/* Return the province/state abbreviation.
 * If none supplied try to deduce from city or postal code
*/
function fixProvince($id = 0, $prov, $city, $postcode) {

    $retVal = "";

    if ( $prov != "" ) {
        $retVal = $prov;
    }
    elseif ( stripos("|Toronto|Etobicoke|Mississauga|North York|Scarborough|", "|${city}|") !== FALSE ) {
        $retVal = "ON";
    }
    elseif ( strpos("MLPKN", substr($postcode,0,1) ) !== FALSE ) {
        $retVal = "ON";
    }
    else {
        $retVal = "XX";
    }

    return($retVal);

//fixProvince
}

// Return in format "A9A 9A9". Leaves zip codes alone.
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

/* Return in format 999-999-9999
   unless the original wasn't even close.
   Does not try to handle extensions.
*/
function fixPhone($str = "") {
    $str_orig = $str;
    $str = preg_replace("/[^\d]/", "", $str);
    $str = preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $str);
    if ( preg_match("/^(\d{3})-(\d{3})-(\d{4})$/", $str) ) {
        return($str);
    } else {
        // Let dbc->escape() do this.
        //$str_orig = str_replace("'", "''", $str_orig);
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
    // Let dbc->escape() do this.
    //$str = str_replace("'", "''", $str);
    return($str);
//fixCity
}

/* Name:
    + tolower if ALL CAPS
  + Capitalize first letter of each word
  + Double apostrophes
*/
function fixName($str = "") {
    if ( $str == 'NULL' )
        $str = '';
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
    // Let dbc->escape() do this.
    //$str = str_replace("'", "''", $str);
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
    // Let dbc->escape() do this.
    //$str = str_replace("'", "''", $str);
    return($str);
//fixAddress
}

/* Return TRUE if the string looks like an email address, FALSE otherwise.
*/
function isEmail($str) {

    $retVal = preg_match("/^[^@]+@.+\.[a-z]{3,4}$/i", $str);

    return($retVal);

//isEmail()
}

/* Return TRUE if the string looks like a phone number, FALSE otherwise.
 * Not a precise match, just plausible.
*/
function isPhone($str) {

    $retVal = preg_match("/\d{3}.\d{3}.\d{4}/", $str);

    return($retVal);

//isPhone()
}

/* Abort, with message to $mail=1 email or $mail=0 terminal.
 * Better to depend on $dieMail than $mail?
*/
function dieHere($msg="", $mail="1") {

    global $dbConn;
    global $dbConn2;
    global $admins;
    global $dieMail;

    if ( $mail ) {
        $subject = "PoS: Error: Update members";
        $message = "$msg";
        $adminString = implode(" ", $admins);

        $lastLine = exec("echo \"$message\" | mail -s \"$subject\" $adminString");
        // Ordinary success returns nothing, or "".
        if ( $lastLine != "" ) {
            echo "from mailing: {$lastLine}\n";
        }
        echo cron_msg("$msg\n");
    }
    else {
        echo "dieHere: $msg\n";
    }

    if ( $dbConn ) {
        // Warning: mysqli::close(): Couldn't fetch mysqli in /home/parkdale/is4c/updateMembers.php on line next
        @$dbConn->close();
    } else {
        1;
    }

    if ( $dbConn2 ) {
        @$dbConn2->close();
    } else {
        1;
    }

    exit();

//dieHere
}


/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
*/
function problemHere($msg="") {

    global $problemPrefix;
    global $problems;

    $problemPrefix = "*** PROBLEM ";
    $problems[] = $msg;

//problemHere
}

/* Return an array of the name keys: 1, 3, etc.
*/
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

/* Little tests of civicrm connection. Then die.
 * civicrm_membership
*/
function civiTestAndDie($dbConn) {

    global $dieMail;

    $selectCivi = "SELECT id, contact_id from civicrm_membership LIMIT 5;";
    $civim = $dbConn->query("$selectCivi");
    // Does not complain about error in MySQL statement.
    //  $civim is FALSE in that case.
    // See $LOGS/queries.log for them.
    if ( $dbConn->errno ) {
        $message = printf("Select failed: %s\n", $dbConn->error);
        dieHere("$message", 0);
    }
    if ( ! $civim ) {
        $msg = sprintf("Failed on: %s", $selectCivi);
        dieHere("$msg", 0);
    }

    // Quick test.
    echo "Civi Members Numbered\n";
    //mysqli: while ( $row = $civim->fetch_row() ) {}
    while ( $row = $dbConn->fetchRow($civim) ) {
        // The numeric keys come first. 0,2,4. Name keys 1, 3, 5.
        $flds = getNameKeys($row);
        //$flds = array_keys($row);
        $lineOut = implode("\t", $flds) . "\n";
        echo $lineOut;
        $lineOut = implode("\t", array($row[id], $row[contact_id])) . "\n";
        echo $lineOut;
    }

    dieHere("Civi test OK, bailing ...", 0);

// civiTestAndDie()
}

/* Little tests of civicrm connection. Then die.
 * civicrm_contact
*/
function civiTestAndDie2($dbConn) {

    global $dieMail;

    $selectCivi = "SELECT id, first_name, last_name FROM civicrm_contact LIMIT 5;";
    $civim = $dbConn->query("$selectCivi");
    // Does not complain about error in MySQL statement.
    //  $civim is FALSE in that case.
    // See $LOGS/queries.log for them.
    if ( $dbConn->errno ) {
        $message = printf("Select failed: %s\n", $dbConn->error);
        dieHere("$message", 0);
    }
    if ( ! $civim ) {
        $msg = sprintf("Failed on: %s", $selectCivi);
        dieHere("$msg", 0);
    }

    // Quick test.
    echo "Civi Contacts, Numbered\n";
    //mysqli: while ( $row = $civim->fetch_row() ) {}
    while ( $row = $dbConn->fetchRow($civim) ) {
        // The numeric keys come first. 0,2,4. Name keys 1, 3, 5.
        //$flds = getNameKeys($row);
        //$lineOut = implode("\t", $flds) . "\n";
        //echo $lineOut;
        //$lineOut = implode("\t", array($row['id'], $row['first_name']),
         //   $row['last_name']) . "\n";
        //echo $lineOut;
        printf("%s %s, %s%s",$row['id'], $row['last_name'],
            $row['first_name'], "\n");
    }

    dieHere("Civi2 test OK, bailing ...", 0);

// civiTestAndDie2()
}

// Little tests of is4c connection.
function is4cTestAndDie($dbConn2) {

    global $dieMail;

    $selectIs4c = "SELECT CardNo, LastName from custdata LIMIT 5;";
    $customers = $dbConn2->query("$selectIs4c");
    if ( $dbConn2->errno ) {
        $message = sprintf("Select failed: %s\n", $dbConn->error);
        dieHere($message, $dieMail);
    }
    if ( ! $customers ) {
        $msg = sprintf("Failed on: %s", $selectIs4c);
        dieHere("$msg", 0);
    }

    echo "IS4C Numbered\n";
    while ( $row = $dbConn2->fetch_row($customers) ) {
        $flds = getNameKeys($row);
        $lineOut = implode("\t", $flds) . "\n";
        echo $lineOut;
        $vals = getNameValues($row);
        $lineOut = implode("\t", $vals) . "\n";
        echo $lineOut;
        $lineOut = implode("\t", array($row[CardNo], $row[LastName])) . "\n";
        echo $lineOut;
    }
    dieHere("IS4C OK, bailing ...", 0);

// is4cTestAndDie();
}

/* Pause with information with abort option.
 * ^C also terminates, without ceremony.
*/
function goOrDie($prompt) {
    $ans = readline("$prompt [q] > ");
    if ( strpos($ans, "q") === 0 ) {
        dieHere("Quitting", 0);
    } elseif ( $ans === FALSE ) {
        dieHere("on ^D", 0);
    } else {
        //echo "Go.\n";
        1;
    }
}

/* Return integer of first membership type
 * or error message on failure.
 */
function getDummyMembershipType($dbConn) {
    $dbQ = "SELECT id FROM civicrm_membership_type ORDER BY id";
    $dbR = $dbConn->query("$dbQ");
    if ($dbR === False) {
        return "Query failed: $dbQ";
    } elseif ( $dbConn->num_rows($dbR) > 0 ) {
        $row = $dbConn->fetch_row($dbR);
        return $row[id];
    } else {
        return "There are no CiviCRM membership types defined.";
    }
}

/* #'AAdd (fill in gaps in the range) the placeholder membership records for
 *  New Members.
 * @return integer the number added, 0 if none,
 *  or string error message on failure.
 */
function addTempMembers($dbc="")
{

    //global $dbConn2;

    global $tempMemberRange;
    /* End of range to fill with NEW MEMBER records.
     * 0 means "do no fill"
     */
    global $tempMemberRangeMax;
    /* Placeholder custdata.LastName that will be:
     * - ignored when it occurrs in the range of synced records
     * - assigned to new placeholder records if filling is being done.
     */
    global $tempMemberLastName;
    /* custdata.memType for placeholder records.
     * Must be real.
     */
    global $tempMemberMemberType;
    /* memContact.pref for placeholder records.
     * Must be real.
     */
    global $tempMemberContactPref;
    $retval = null;
    $errors = '';

    if ($dbc == "") {
        $errors = "addTempMembers() no database connection supplied.";
        return $errors;
    }

    /* Much of what follows is lifted from $MEM/NewMemberTool.php
     */

    $name = $tempMemberLastName;
    /* Validate in memtypes.memtype
     */
    $mtype = $tempMemberMemberType;
    $memtypes = new MemtypeModel($dbc);
    $memtypes->memtype($mtype);
    $mtypes = $memtypes->find();
    if (count($mtypes) == 0) {
        $errors = "Member type $mtype is not known.";
        return $errors;
    }
    /* Validate in memContactPrefs.pref_id
     */
    $pref = $tempMemberContactPref;
    $memprefs = new MemContactPrefsModel($dbc);
    $memprefs->pref_id($pref);
    $mprefs = $memprefs->find();
    if (count($mprefs) == 0) {
        $errors = "Contact preference $pref is not known.";
        return $errors;
    }

    $mt = $dbc->tableDefinition('memtype');
    $dQuery = "SELECT custdataType,discount,staff,ssi from memtype " .
        "WHERE memtype=?";
    $defaultsQ = $dbc->prepare($dQuery);
    if ($dbc->tableExists('memdefaults') && 
        (!isset($mt['custdataType']) || !isset($mt['discount']) ||
        !isset($mt['staff']) || !isset($mt['ssi']))) {
        $dQuery = "SELECT cd_type as custdataType,discount,staff,SSI as ssi " .
                "FROM memdefaults WHERE memtype=?";
        $defaultsQ = $dbc->prepare($dQuery);
    }
    $defaultsR = $dbc->execute($defaultsQ,array($mtype));
    $defaults = $dbc->fetch_row($defaultsR);

    $start = $tempMemberRange + 1;
    $end = $tempMemberRangeMax;

    $custdata = new CustdataModel($dbc);
    /* Pre-populate most custdata fields. */
    $custdata->personNum(1);
    $custdata->LastName($name);
    $custdata->FirstName('');
    $custdata->CashBack(999.99);
    $custdata->Balance(0);
    $custdata->memCoupons(0);
    $custdata->Discount($defaults['discount']);
    $custdata->Type($defaults['custdataType']);
    $custdata->staff($defaults['staff']);
    $custdata->SSI($defaults['ssi']);
    $custdata->memType($mtype);

    $meminfo = new MeminfoModel($dbc);
    /* Pre-populate most meminfo fields. */
    $meminfo->last_name('');
    $meminfo->first_name('');
    $meminfo->othlast_name('');
    $meminfo->othfirst_name('');
    $meminfo->street('');
    $meminfo->city('');
    $meminfo->state('');
    $meminfo->zip('');
    $meminfo->phone('');
    $meminfo->email_1('');
    $meminfo->email_2('');

    $chkP = $dbc->prepare('SELECT CardNo FROM custdata WHERE CardNo=?');
    $mdP = $dbc->prepare("INSERT INTO memDates VALUES (?,NULL,NULL)");
    $mcP = $dbc->prepare("INSERT INTO memContact (card_no,pref) VALUES (?,?)");
    $membersAdded = 0;
    for($i=$start; $i<=$end; $i++) {
        // skip if record already exists
        $chkR = $dbc->execute($chkP,array($i));
        if ($dbc->num_rows($chkR) > 0) {
            continue;
        }

        $custdata->CardNo($i);
        $custdata->blueLine($i.' '.$name);
        $custdata->save();

        $meminfo->card_no($i);
        $meminfo->save();

        /* memDates */
        $dbc->execute($mdP, array($i));
        /* memContact */
        $dbc->execute($mcP, array($i,$pref));

        $membersAdded++;
    }

    $retval = ($errors) ? $errors : $membersAdded;
    return $retval;

// addTempMembers()
}



// --functions } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// --PREP - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

/* Report simple running errors
 These kind of errors go to STDOUT or STDERR before they are tested for or trapped.
 But see note about suppression with @, above.
*/
error_reporting(E_ERROR | E_WARNING);
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

//#'C --CONSTANTS { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

include(dirname(__FILE__) . '/../config.php');
//require('../src/SQLManager.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!function_exists('cron_msg')) {
    include($FANNIE_ROOT.'src/cron_msg.php');
}

/* No limit on PHP execution time.
 * This program does not ordinarily take very long to run.
set_time_limit(0);
 */

/* Development-related vars.
*/
/*
 * Controls some monitoring and info.
 * 0= production, 1=notify, no-write to db, 2=notify but write to db.
 */
$debug = 0;
/*
 * Whether dieHere() =1 sends email or =0 Displays the message.
 * Optional arg to dieHere(); defaults to 1.
 */
$dieMail = 1;    // -> Restore to 1 for production
/*
 * Used in composing vars for Civi db access.
 */
$dev = "";
//$dev = "_DEV";
/*
 * 0=normal, 1=return from to[ISC4C|Civi]() without changing anything.
 */
$dryRun = 0;

/* The log files will be created if they don't exist
 * as long as fannie/logs is writeableC.
 */
if (!is_writable('../logs')) {
    $message = $FANNIE_ROOT.
    'logs must be writable by the user running this program.';
    dieHere($message, $dieMail);
}

// Only used during development, for a local source for remote data.
$outFile = "../logs/updateMembers.tab";

/* Log. Cumulative.
 * Contains the one-line summary emailed to ops.
*/
$logFile = "../logs/updateMembers.log";

/* Run report. Cumulative.
 * An item for each change in a run.
*/
$reportFile = "../logs/updateMembersReport.log";

// test: 4000  production: 0
$memberIdOffset = 0;
/* IS4C member#s above this are placeholders for card_no's that will
 * be obtained from CiviCRM.
 * See toCivi().
 * The value is co-op specific, so set in the external configuration.
 $tempMemberRange = 65000;
 */

/* The CiviCRM user-defined table and field are named by CiviCRM at the time
 *  and they are created and will likely vary from one installation to another.
 *  See config_civicrm.php
$memberCardTable = "civicrm_value_identification_and_cred_1";
$memberCardField = "member_card_number_1";
if ( $dev != "" ) {
    $memberCardTable = "civicrm_value_identification_and_cred_4";
    $memberCardField = "member_card_number_16";
}
*/

/* People to whom news is mailed.
 * Set in confic_civicrm.php
$admins = array("el66gr@gmail.com");
 */

$is4cTableNames = array('custdata',
    'meminfo',
    'memContact',
    'memDates',
    'memberCards',
    'stockpurchases');
$civiTableNames = array('civicrm_contact',
    'civicrm_membership',
    'civicrm_email',
    'civicrm_address',
    'civicrm_phone',
    "$memberCardTable",
    'civicrm_membership_log',
    'civicrm_log');

    $civicrm_membership_status = array(
        'New' => 1,
        'Current' => 2,
        'Cancelled' => 6
    );

/* To use when getting new membership numbers,
 *  before the real one is known.
 *  Must exist in civicrm_membership_type.
 */
$dummy_membership_type = NULL;

/* CiviCRM civicrm_contact.id authorizing modifications.
 * Used in civicrm_log.
 * Must be real.
 * Is co-op-specific. See config_civicrm.php
$adminId = 5;
if ($dev != "") {
    $adminId = 130;
}
 */

/* A set of connection constants and other co-op-specific constants.
 * In a file that will not be made public e.g. in a github repo.
$CIVICRM_SERVER = "a.b.com";
$CIVICRM_SERVER_DBMS = 'MYSQL';
$CIVICRM_SERVER_USER = "civi_user";
$CIVICRM_SERVER_PW = "civi_password";
$CIVICRM_DB = "civi_db";
// Beginning of range of temporary IS4C CardNo values,
//  pending assignment of real values by this program.
$tempMemberRange = 65000;
// CiviCRM civicrm_contact.id for IS4C Admin user.
$adminId = 5;
// Send notices to these addresses.
$admins = array("joe.bloggs@gmail.com");
// CiviCRM membership card# table and field. Optional.
$memberCardTable = "civicrm_value_identification_and_cred_1";
$memberCardField = "member_card_number_1";
// Range of regular and temporary-regular IS4C CardNo values.
$is4cMin = 470;
$is4cMax = 99900;
 */
$civiConfig = $FANNIE_ROOT . 'config_civicrm.php';
if (!is_readable($civiConfig)) {
    $message = "The file $civiConfig which defines database connection " .
        "and other values needed by this program doesn't exist.\n" .
        "See the source code of the program for what it must contain.";
    dieHere("$message", $dieMail);
}
require($civiConfig);

// --constants } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

//#'V --VARIABLES { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Counter of tab-delim lines.
$outCount = 0;
// Counter of rows from raw select.
$inCount = 0;

$noChange = 0;
$insertI = 0;
$updateI = 0;
$insertC = 0;
$updateC = 0;

/* If > 0 flag in email to op.
 * See problemHere()
*/
$problemPrefix = "";
$problems = array();

/* Arrays for IS4C tables
   Initialized by clearIs4cWorkVars()
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
    "LastChange" => "",
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

// Operation: insert or update
$is4cOps = array();

/* Vars for CiviCRM {
*/

// civicrm_contact.id
$civiContactId = 0;

// Base contact record.
$civicrm_contact = array(
    "id" => "",
// 'Individual' 'Organization'
    "contact_type" => "",
// Not email. [0]/1
    "do_not_email" => "",
// Not phone. [0]/1 - 29Nov12 Leave alone for now.
    "do_not_phone" => "",
// Not mail. [0]/1 - 29Nov12 Leave alone for now.
    "do_not_mail" => "",
// No contact at all: [0]/1
//  Can be set by the contact in response to an email.
//  Map to meminfo.ads_OK, inverted.
    "is_opt_out" => "",
// Last, First or email
    "sort_name" => "",
// First Last or email
    "display_name" => "",
// Leave alone. Rarely used. Is a string of numbers:
// 1=phone  2=email 3=postal-mail
// Might partially-map to memContact.pref if used more.
    "preferred_commuication_method" => "",
// Is text. For inserts, 'IS4C', otherwise leave alone.
    "source" => "",
    "first_name" => "",
    "middle_name" => "",
    "last_name" => "",
// Use? 1=Dr. Where are real codes? Table civicrm_prefix doesn't exist.
//    "prefix_id" => "",
    "organization_name" => "",
// [0]/1 Leave alone.  Rarely if ever used in Civi and not supported in IS4C.
    "is_deleted" => "",
    "x" => ""
);

/* membership_type_id:
 * 1=Non-member
 * 2=Placeholder 2
 * 3=Community Partner
 * 4=Eater
 * 5=Producer
 * 6=Worker
 * 7=Intra-Coop
 * 8=Volunteer
 * 9=CC Worker
 *
 * status_id:
 * 1=New
 * 2=Current
 * 6=Cancelled
*/

// Membership
$civicrm_membership = array(
    "id" => "",
    "contact_id" => "",
    "membership_type_id" => "",
    // 25Nov13 Added. -> No support yet.
    "status_id" => "",
    "join_date" => "",
    "start_date" => "",
    // 25Nov13 Added. -> No support yet.
    "end_date" => "",
    "x" => ""
);

// Membership log
// 25Nov13 Added. o> No support yet.
$civicrm_membership_log = array(
    "id" => "",
    "membership_id" => "",
    "membership_type_id" => "",
    "status_id" => "",
    "start_date" => "",
    "end_date" => "",
    "modified_date" => "",
    "modified_id" => "",
    "x" => ""
);

/* location_type_id:
 * 1=Home
 * 2=Work
 * 3=Main
 * 4=Other
 * 5=Billing
*/

// Email
$civicrm_email = array(
    "id" => "",
    "contact_id" => "",
    "location_type_id" => "",
// Primary: [0]/1 On first insert=1, on second allow default. On update, leave alone.
    "is_primary" => "",
    "email" => "",
// OK to mail: [0]/1 On insert=1, On update, leave alone.
    "is_bulkmail" => "",
    "x" => ""
);

// Address
$civicrm_address = array(
    "id" => "",
    "contact_id" => "",
    "location_type_id" => "",
// Primary: [0]/1 On first insert=1, on second allow default. On update, leave alone.
    "is_primary" => "",
    "street_address" => "",
    "city" => "",
    "state_province_id" => "",
    "postal_code" => "",
    "country_id" => "",
    "x" => ""
);

// Phone
$civicrm_phone = array(
    "id" => "",
    "contact_id" => "",
    "location_type_id" => "",
    "is_primary" => "",
    "phone" => "",
    "phone_ext" => "",
// Primary: [0]/1 On first insert=1, on second allow default. On update, leave alone.
    "x" => ""
);

// Membership card#.
$civicrm_value_identification_and_cred = array(
    "id" => "",
    "entity_id" => "",
    "$memberCardField" => "",
    "x" => ""
);

// Update log. Always insert.
$civicrm_log = array(
    "id" => "",
// 'civicrm_contact'
    "entity_table" => "",
// contact_id
    "entity_id" => "",
// "'$entity_table,$contact_id'"
    "data" => "",
// Need a contact_id for IS4C.
    "modified_id" => "",
// datetime
    "modified_date" => "",
    "x" => ""
);

$civiTables = array("dbname|civicrm_contact|id",
    "dbname|civicrm_membership|contact_id",
    "dbname|civicrm_membership_log|membership_id",
    "dbname|civicrm_email|contact_id",
    "dbname|civicrm_address|contact_id",
    "dbname|civicrm_phone|contact_id",
    "dbname|{$memberCardTable}|contact_id",
    "dbname|civicrm_log|entity_id");

/* SQL insert statements
 *  Are arrays if multiple is possible, e.g. email or phone
*/
$insertContact = "";
$insertMembership = "";
$insertMembershipLog = "";
$insertEmail = array();
$insertAddress = array();
$insertPhone = array();
$insertMemberCard = "";
$insertLog = "";

/* SQL update statements
 *  Are arrays if multiple is possible, e.g. email or phone
*/
$updateContact = "";
$updateMembership = "";
$updateAddress = array();
$updateEmail = array();
$updatePhone = array();
$updateMemberCard = "";
$updateLog = "";

// Operation: insert or update
$civiOps = array();

// civi vars }

// --variables } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

//#'M --MAIN - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

clearIs4cWorkVars();
clearCiviWorkVars();

// The "@" prevents the error from being reported immediately,
//  but the test further on will still see it.
if ( ! $dev ) {
$dbConn = new SQLManager($CIVICRM_SERVER,$CIVICRM_SERVER_DBMS,$CIVICRM_DB,
        $CIVICRM_SERVER_USER,$CIVICRM_SERVER_PW);
} else {
    $dbConn = new SQLManager($CIVICRM_SERVER,$CIVICRM_SERVER_DBMS,$CIVICRM_DB_DEV,
            $CIVICRM_SERVER_USER_DEV,$CIVICRM_SERVER_PW_DEV);
}

$message = $dbConn->error();
if ( $message != "" ) {
    dieHere("$message", $dieMail);
}

/* Little tests of civiCRM connection.
dieHere("Civi connection did not fail, bailing ...", 0);
civiTestAndDie2($dbConn);
*/

$dbConn2 = new SQLManager($FANNIE_SERVER,$FANNIE_SERVER_DBMS,$FANNIE_OP_DB,
        $FANNIE_SERVER_USER,$FANNIE_SERVER_PW);
if ( $dbConn2->connect_errno ) {
    $message = sprintf("Connect2 failed: %s\n", $dbConn2->connect_error);
    dieHere("$message", $dieMail);
}

/*Get the timestamp of the last run of this program
 * Not sure that is safe. Always use epoch.
 * The pairs with same datestamps are ignored.
 * If parts of pairs lost, then trouble.  How would that happen?
*/
$epoch = "0000-00-00 00:00:00";
$latestRunDate = $epoch;
//$latestRunDate = getLatestRun($reportFile);

if (!isset($adminId)) {
    dieHere("\$adminId is not set. Is usually set in config_civicrm.php\n", $dieMail);
}
if ($tempMemberRange == 0) {
    dieHere("\$tempMemberRange is 0. Probably not what you want.\n", $dieMail);
}
if (!isset($tempMemberRange)) {
    dieHere("\$tempMemberRange is not set. Is usually set in config_civicrm.php\n", $dieMail);
}
if ($tempMemberRange == 0) {
    dieHere("\$tempMemberRange is 0. Probably not what you want.\n", $dieMail);
}

$dummy_membership_type = getDummyMembershipType($dbConn);
if (!preg_match('/^\d+$/',$dummy_membership_type)) {
    dieHere($dummy_membership_type, $dieMail);
}
//dieHere("OK dummy_membership_type: $dummy_membership_type", $dieMail);

/* Open the log files */

/*  5Jan13 At this point only really used for debugging.
 *         Stop using it in production.
*/
$outer = fopen("$outFile", "w");
if ( ! $outer ) {
    dieHere("Could not open $outFile\n", $dieMail);
}

// Contains the one-line summary emailed to ops.
$logger = fopen("$logFile", "a");
if ( ! $logger ) {
    dieHere("Could not open $logFile\n", $dieMail);
}

/* Contains START and FINISH datestamps and
 * a list of what was done to each record:
 * No-change
 * Update, and which is source and target.
 * Insert, and which is source and target.
*/
$reporter = fopen("$reportFile", "a");
if ( ! $reporter ) {
    dieHere("Could not open $reportFile\n", $dieMail);
}
$dbNow = date("Y-m-d H:i:s"); // " e"
fwrite($reporter, "STARTED: $dbNow\n");

/* End of open the log files */


// #'N ->True in production.
//     False in development, False to disable getting actual data and use local source instead.
if ( False ) {

/* CiviCRM members with date modified.
 * There is a record for each modified_date; 2nd+ are ignored in the loop.
 * Use the most recent of _log.modified_date, _membership_log.modified.
 *  Note that _membership_.log modified_date is a day, not date+time.
 *  Change of membership type in Civi also creates and Activity about it
 *   and a _log with entit_id =1 and
 *   .data ="Activity created for source=3, target=9",
 *   where source is the _contact.id of the changer and
 *   target is the contact.id is the member being changed.
*/
$c_selectMembers = "SELECT DISTINCT
c.id as contact_id
,c.first_name
,c.last_name
,m.id as member_id
 ,m.join_date
,v.{$memberCardField} as mcard
,u.modified_date udate
,p.modified_date pdate
,CASE WHEN p.modified_date > u.modified_date THEN p.modified_date ELSE u.modified_date END
    as change_date
FROM civicrm_membership m
    INNER JOIN civicrm_contact c ON c.id = m.contact_id
    LEFT JOIN {$memberCardTable} v ON m.contact_id = v.entity_id
    LEFT JOIN civicrm_log u ON m.contact_id = u.entity_id
        AND u.entity_table = 'civicrm_contact'
    LEFT JOIN civicrm_membership_log p ON m.id = p.membership_id
WHERE
    CASE WHEN p.modified_date > u.modified_date
        THEN p.modified_date
        ELSE u.modified_date END > '$latestRunDate'
    AND c.is_deleted = 0
    AND NOT (m.is_override = 1 AND m.status_id = 6)
ORDER BY c.id,
    m.id,
    u.modified_date DESC,
    udate DESC, pdate DESC;";

// Defeat set False for _log.php
if (True) {
$c_members = $dbConn->query("$c_selectMembers");
if ( $dbConn->errno ) {
    $message = sprintf("Select failed: %s\n", $dbConn->error);
    dieHere("$message", $dieMail);
}
if ( ! $c_members ) {
    $msg = sprintf("Failed on: %s", $c_selectMembers);
    dieHere("$msg", $dieMail);
}
}

// Populate the big list with Civi members.
$allMembers = array();
$ami = -1;
$lastCid = -1;
$lastMid = -1;
$memberForContact = -1;
// Defeat set False for _log.php
while ( True && $c_row = $dbConn->fetch_row($c_members) ) {

    if ( $c_row['contact_id'] == $lastCid ) {
        if ( $c_row['member_id'] == $lastMid ) {
            $dupCid++;
            $isDupCid = 1;
        } else {
            $uniqueCid++;
            $isDupCid = 0;
            $problems[] = "{$c_row[first_name]} {$c_row[last_name]} " .
                "contact $lastCid has membership $lastMid and " .
                "membership {$c_row[member_id]}";
            $lastMid = "$c_row[member_id]";
        }
    } else {
        $uniqueCid++;
        $isDupCid = 0;
        $lastCid = "$c_row[contact_id]";
        $lastMid = "$c_row[member_id]";
        $memberForContact = "$c_row[member_id]";
    }

    // For the first record for each member (same contact_id and member_id):
    if ( $isDupCid == 0 ) {

        $ami++;
        $allMembers[$ami] = sprintf("%05d|%s|C", $c_row[member_id], $c_row[change_date]);
        //$allMembers[$ami] = sprintf("%05d|%s|C", $c_row[member_id], $c_row[modified_date]);

    }
}

/* Get all the the members from IS4C, except placeholder "NEW MEMBER"s
 *  and the Dummy 99900-99998 and Non-Member 99999.
 *  Set in config_civicrm.php
 * $is4cMin = 470;
 * $is4cMax = 99900;
 * $tempMemberLastName = 'NEW MEMBER';
 */
if (empty($tempMemberLastName)) {
    $tempMemberLastName = 'NEW MEMBER';
}
$i_selectMembers ="SELECT CardNo, LastChange
FROM custdata
WHERE CardNo between $is4cMin AND $is4cMax
 AND (LastName IS NULL OR LastName != '{$tempMemberLastName}')
 AND NumberOfChecks != 9";
$i_members = $dbConn2->query("$i_selectMembers");
if ( $dbConn2->errno ) {
    $message = sprintf("Select failed: %s\n", $dbConn->error);
    dieHere($message, $dieMail);
}
if ( ! $i_members ) {
    $msg = sprintf("Failed on: %s", $i_selectMembers);
    dieHere("$msg", $dieMail);
}
$lastCardNo = "";
while ( $i_row = $dbConn2->fetch_row($i_members) ) {
    if ( $i_row[CardNo] == $lastCardNo )
        continue;
    $allMembers[] = sprintf("%05d|%s|I", $i_row[CardNo], $i_row[LastChange]);
    $lastCardNo = $i_row[CardNo];
}

sort($allMembers);

/* This file isn't used except during development.
*/
foreach ( $allMembers as $item ) {
        fwrite($outer, "$item\n");
}
if ( count($problems) > 0 ) {
    fwrite($outer, implode("\n", $problems));
    echo implode("\n", $problems), "\n";;
}

/* In some development situations you may want to stop here.
dieHere("Got real list in $outFile\n", 0);
echo "Got real list in $outFile\n";
 */

// Enable/Defeat normal member-getting from dbs.
}
else {
    // During dev't use this instead of getting from dbms
    echo "Using updateMembers.txt\n";
    echo "dryRun: $dryRun\n";
    $allMembers = file("../logs/updateMembers_real.txt", FILE_IGNORE_NEW_LINES);
}

// #'L --LOOP

$ami = -1;
$lastAmi = (count($allMembers) - 1);

// Get the next pair.
list($m1,$d1,$s1) = explode("|", $allMembers[++$ami]);
    $m1 = ltrim($m1, "0");
list($m2,$d2,$s2) = explode("|", $allMembers[++$ami]);
    $m2 = ltrim($m2, "0");
$prevAmi = ($ami - 1);
$nextAmi = ($ami + 1);

/* maxM is a card_no, not civicrm_contact.id
 * Only used in development.
$maxM = 490;
 */
// Does not work if there is only one item in $allMembers.
while ( $ami <= $lastAmi ) {

    /* 21Apr2015 I think this was for a jiggered run to populate civicrm_log
     * with the current values from IS4C.
    list($m1,$d1,$s1) = explode("|", $allMembers[++$ami]);
    // Used in development
    if ( $m1 > $maxM || $m2 > $maxM ) {
    if ( $debug > 0 )
        echo "Exit on $m1 > $maxM\n";
        break;
    }
    $msg = " $m1:$d1 adding log\n";
    $problems[] = $msg;
    fwrite($reporter, $msg);
    if ( $debug > 0 )
        echo $msg;
    //$dryRun = 1;
    $updateC++;
    toCiviLog("update", $m1, $d1);
    continue;
     */

    /* Used in development
     *  to stop after a certain number of actions.
    if ( $noChange > 9999 )
        break;
    if ( $updateI > 1 )
        break;
    if ( $updateC > 1 )
        break;
    if ( $insertI > 1 )
        break;
    if ( $insertC > 9 )
        break;
    // Only in development.
    //fwrite($outer, "#${prevAmi}/$m1 : #${ami}/$m2\n");
    */

    if ( $debug ) {
        echo "ami#${prevAmi} of $lastAmi/$m1 : ami#${ami}/$m2\n";
    }
    if ( $m1 == $m2 ) {
        if ( $d1 == $d2 ) {
            $noChange++;
            $msg = " $m1:$d1 = $m2:$d2 -> do nothing\n";
            fwrite($reporter, $msg);
            if ( $debug > 0 )
                echo $msg;
        }
        elseif ( "$d1" < "$d2" ) {
            $msg = " $m1:$d1 < $m2:$d2 -> update $s1\n";
            $problems[] = $msg;
            fwrite($reporter, $msg);
            if ( $debug > 0 )
                echo $msg;
            if ( $s1 == "I" ) {
                $updateI++;
                toIS4C("update",$m1, $d2);
            } elseif ( $s1 == "C" ) {
                $updateC++;
                toCivi("update", $m1, $d2);
            } else {
                $msg = "Unknown s1 >${s1}< for update.";
                fwrite($reporter, " $msg\n");
                dieHere("$msg", $dieMail);
            }
        }
        else {
            $msg = "How can ami# $prevAmi >$d1< be > >$d2< ?";
            fwrite($reporter, " $msg\n");
            dieHere("$msg", $dieMail);
        }

        if ( $nextAmi > $lastAmi ) {
            // Do nothing and force exit.
            $ami++;
        }
        else {
            // Get the first of the next pair.
            list($m1,$d1,$s1) = explode("|", $allMembers[++$ami]);
                $m1 = ltrim($m1, "0");
            // Is it the last in the list?
            if ( ($ami + 0) == $lastAmi ) {
                // What do do?  Must be add?
                // Do it, then ++ami to let/force loop to end.
                if ( $debug > 0 )
                    echo "A at lastAmi: $m1, $d1, $s1\n";
                $otherSource = ( $s1 == "I" ) ? "C" : "I";
                $msg = " $m1/$s1 != $m2/$s2 -> 1add $m1 to $otherSource";
                if ( $debug > 0 )
                    echo $msg;
                if ( $otherSource == "I" ) {
                    $insertI++;
                    toIS4C("insert", $m1, $d1);
                } elseif ( $otherSource == "C" ) {
                    $insertC++;
                    $asMember = toCivi("insert", $m1, $d1);
                    $msg .= " as $asMember";
                } else {
                    $msg = "Unknown s1 >${s1}< for add.";
                    fwrite($reporter, " $msg\n");
                    dieHere("$msg", $dieMail);
                }
                $msg .= "\n";
                $problems[] = $msg;
                fwrite($reporter, $msg);
                // Force exit
                $ami++;
            }
            // Get the second of the next pair.
            else {
                list($m2,$d2,$s2) = explode("|", $allMembers[++$ami]);
                    $m2 = ltrim($m2, "0");
                $prevAmi = ($ami - 1);
                $nextAmi = ($ami + 1);
            }
        }

    }
    // Don't match.
    // Assume m1 needs to be added to other-source.
    elseif ( $m1 < $m2 ) {
        $otherSource = ( $s1 == "I" ) ? "C" : "I";
        $msg = " $m1/$s1 != $m2/$s2 -> 2add $m1 to $otherSource";
        if ( $debug > 0 )
            echo "$msg\n";
        /* The apparatus for adding */
        if ( $otherSource == "I" ) {
            $insertI++;
            toIS4C("insert", $m1, $d1);
        } elseif ( $otherSource == "C" ) {
            $insertC++;
            $asMember = toCivi("insert", $m1, $d1);
            $msg .= " as $asMember";
        } else {
            fwrite($reporter, " $msg\n");
            $msg2 = "Unknown s1 >${s1}< for add.";
            fwrite($reporter, " $msg2\n");
            dieHere("$msg2", $dieMail);
        }
        $msg .= "\n";
        $problems[] = $msg;
        fwrite($reporter, $msg);

        // Get the next pair.
        //  Shift the current #*2 to #*1.
        list($m1,$d1,$s1) = explode("|", $allMembers[$ami]);
            $m1 = ltrim($m1, "0");
        // Check for the last one.
        if ( ($ami + 0) == $lastAmi ) {
            // What do do?  Must be add?
            // Do it, then ++ami to let/force loop to end.
            if ( $debug > 0 )
                echo "B at lastAmi: $m1, $d1, $s1\n";
            $otherSource = ( $s1 == "I" ) ? "C" : "I";
            $msg = " $m1/$s1 != $m2/$s2 -> 3add $m1 to $otherSource";
            if ( $debug > 0 )
                echo $msg;
            if ( $otherSource == "I" ) {
                $insertI++;
                toIS4C("insert", $m1, $d1);
            } elseif ( $otherSource == "C" ) {
                $insertC++;
                $asMember = toCivi("insert", $m1, $d1);
                $msg .= " as $asMember";
            } else {
                $msg = "Unknown s1 >${s1}< for add.";
                fwrite($reporter, " $msg\n");
                dieHere("$msg", $dieMail);
            }
            $msg .= "\n";
            $problems[] = $msg;
            fwrite($reporter, $msg);
            // Force exit
            $ami++;
        }
        else {
            //  Get the next #2.
            list($m2,$d2,$s2) = explode("|", $allMembers[++$ami]);
                $m2 = ltrim($m2, "0");
            $prevAmi = ($ami - 1);
            $nextAmi = ($ami + 1);
        }

    }
    else {
        $msg = "How can ami# $prevAmi >$m1< be > >$m2< ?";
        dieHere("$msg", $dieMail);
    }

// Each member --loop
}

/* #'D Re-populate the range of dummy "NEW MEMBER" records.
 */
$tempMembersAdded = null;
$tempMembersAddedMessage = '';
if (!empty($tempMemberRangeMax) && $tempMemberRangeMax > $tempMemberRange) {
    $tempMembersAdded = addTempMembers($dbConn2);
    $tempMembersAddedMessage = "\nFilled in IS4C placeholder members: $tempMembersAdded for $tempMemberRange to $tempMemberRangeMax";
    //$tempMembersAddedMessage = "\nFilled in IS4C placeholder members: $tempMembersAdded";
}

/* #'R Logging and reporting
*/

$dbNow = date("Y-m-d H:i:s"); // " e"
$subject = "{$problemPrefix}PoS: $dbNow Update members: added $insertC " .
    "to CiviCRM, $insertI to IS4C";
$shortMessage = ($dryRun == 1)?"Dry Run: ":"";
$shortMessage .= "No change needed: $noChange " .
    "\nAdded to CiviCRM: $insertC " .
    "\nUpdated CiviCRM: $updateC " .
    "\nAdded to IS4C: $insertI " .
    "\nUpdated IS4C: $updateI " .
    $tempMembersAddedMessage .
    "\n";
$cronMessage = str_replace(" \n", " : ", $shortMessage);
if ( count($problems) > 0 ) {
    $shortMessage .= implode("\n", $problems);
    $shortMessage .= "\n";
}
$adminString = implode(" ", $admins);

$lastLine = exec("/bin/echo -en \"$shortMessage\" | mail -s \"$subject\" $adminString");
// Ordinary success returns nothing, or "".
if ( $lastLine != "" ) {
    echo "from mailing: {$lastLine}\n";
}

$now = date("Ymd_M H:i:s e");
fwrite($logger, "$now  $shortMessage");

$dbNow = date("Y-m-d H:i:s"); // " e"
fwrite($reporter, "FINISHED_OK: $dbNow\n");

// For dayend.log
echo cron_msg("{$problemPrefix}Success syncing members between CiviCRM and IS4C: $cronMessage\n");

/* Tie up and shut down.
*/

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

// Close various-development-use file
//fclose($outer);

// Close logfile
fclose($logger);

// Close reportfile
fclose($reporter);

exit();


/*#'W --WOODSHED { - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

 --woodshed } - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

*/


