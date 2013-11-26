<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
if (php_sapi_name() !== 'cli' || basename($_SERVER['PHP_SELF']) != basename(__FILE__)) {
    return;
}

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('UIGLib.php')) {
    include('UIGLib.php');
}
$dbc = FannieDB::get($FANNIE_OP_DB);

$UNFI_USERNAME = $FANNIE_PLUGIN_SETTINGS['UnfiInvoiceUser'];
$UNFI_PASSWORD = $FANNIE_PLUGIN_SETTINGS['UnfiInvoicePass'];

$SITE_URL = 'https://east.unfi.com';
$SSO_URL = 'https://sso.unfi.com/';
$POLICY_URL = 'https://east.unfi.com/my.policy';
$INVOICE_URL = 'https://east.unfi.com/invoices/listinvoices2.aspx';

$cookies = tempnam(sys_get_temp_dir(), 'cj_');

/**
  Download initial site and let it set any cookies
*/
$ch = curl_init($SITE_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
$login_page = curl_exec($ch);
curl_close($ch);
echo "Login (1/3)\n";

/**
  POST the policy url to the sso url
*/
$ch = curl_init($SSO_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'client_data=SecurityDevice?post_url='.urlencode($POLICY_URL));
$sso_page = curl_exec($ch);
curl_close($ch);
echo "Login (2/3)\n";

/**
  add username and password
*/
$post_data = 'username='.urlencode($UNFI_USERNAME);
$post_data .= '&password='.urlencode($UNFI_PASSWORD);

$ch = curl_init($POLICY_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
$login_result = curl_exec($ch);
curl_close($ch);
echo "Login (3/3)\n";

/**
  Get invoice download page
*/
$ch = curl_init($INVOICE_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
$invoice_page = curl_exec($ch);
curl_close($ch);
echo "Getting available dates\n";

/**
  Extract available dates
*/
$dates = array();
$dates_regex = '/<option.*value="(\d+.*?)"/';
preg_match_all($dates_regex, $invoice_page, $matches);
foreach($matches[1] as $match) {
    if ($match === '0') {
        continue;
    }
    // loose date validation
    if (strtotime($match) !== false) {
        $dates[] = $match;
    }
}

/**
  Extract hidden inputs
*/
$inputs_regex = '/<input .*name="(.+?)" .*value="(.*?)"/';
$post_data = '';
preg_match_all($inputs_regex, $invoice_page, $matches);
for($i=0; $i<count($matches[1]); $i++) {
    // skip over radio button
    if ($matches[1][$i] == 'ctl00$PlaceHolderMain$grp1') {
        continue;
    }
    $post_data .= $matches[1][$i].'='.urlencode($matches[2][$i]).'&';
}

$post_data .= 'ctl00$PlaceHolderMain$grp1=rdoCSV';

$check = $dbc->prepare('SELECT orderID FROM PurchaseOrder WHERE vendorID=? and userID=0
                    AND creationDate=? AND placedDate=?');
foreach($dates as $date) {
    $good_date = date('Y-m-d', strtotime($date));
    $doCheck = $dbc->execute($check, array(1, $good_date, $good_date));
    if ($dbc->num_rows($doCheck) > 0) {
        echo "Skipping $date (already imported)\n";
        continue;
    }

    $this_post = $post_data.'&ctl00$PlaceHolderMain$ddlInvoiceDate='.urlencode($date);
    echo "Downloading $date...\n";

    $filename = str_replace('/','-',$date).'.zip';
    $fp = fopen($filename, 'w');
    $ch = curl_init($INVOICE_URL);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $this_post);
    $invoice_file = curl_exec($ch);
    curl_close($ch);
    fclose($fp);

    echo "Importing invoices for $date\n";
    if (UIGLib::import($filename) === true) {
        unlink($filename);
    } else {
        echo "ERROR: IMPORT FAILED!\n";
    }
    
    // only download one day for now
    // remove when done testing
    break; 

    // politeness; pause between requests
    sleep(15);
}

/**
  Cleanup: delete cookie file
*/
unlink($cookies);
