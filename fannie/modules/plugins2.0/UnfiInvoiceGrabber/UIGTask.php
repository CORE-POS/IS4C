<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}
if (!class_exists('UIGLib')) {
    include('UIGLib.php');
}

class UIGTask extends FannieTask
{
    public $username_field = 'UnfiInvoiceUser';
    public $password_field = 'UnfiInvoicePass';
    public $vendor_id = 1;

    public function run()
    {
        global $FANNIE_OP_DB, $FANNIE_PLUGIN_SETTINGS;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $UNFI_USERNAME = $FANNIE_PLUGIN_SETTINGS[$this->username_field];
        $UNFI_PASSWORD = $FANNIE_PLUGIN_SETTINGS[$this->password_field];

        $LOGIN_URL = 'https://customers.unfi.com/_login/LoginPage/Login.aspx';
        $IFRAME_DOMAIN = 'https://stsuser.unfi.com';
        $HOME_URL = 'https://customers.unfi.com/_trust/pages/home.aspx';
        $SESSION_URL = 'https://stsuser.unfi.com/default.aspx/GetSessionValue';
        $INVOICE_URL = 'https://customers.unfi.com/Pages/ReportDetail.aspx?ReportID=41&ReportName=Invoices%20Download';
        $REPORT_GEN_URL = 'https://customers.unfi.com/_layouts/15/UNFI.UPO.WP.DynamicReportParams/AjaxBridge.aspx/SaveReportParams';

        $cookies = tempnam(sys_get_temp_dir(), 'cj_');

        /**
          Step 1:
          Download the login page
        */
        $ch = curl_init($LOGIN_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        $login_page = curl_exec($ch);
        curl_close($ch);

        /**
          Get hidden fields from login page
        */
        $inputs_regex = '/<input .*?name="(.+?)" .*?value="(.*?)"/';
        preg_match_all($inputs_regex, $login_page, $matches);
        $login_post = '';
        for($i=0; $i<count($matches[1]); $i++) {
            $login_post .= $matches[1][$i] . '=' . urlencode($matches[2][$i]) . '&';
        }
        /**
          add username and password
        */
        $login_post .= 'userName='.urlencode($UNFI_USERNAME);
        $login_post .= '&Password='.urlencode($UNFI_PASSWORD);

        /**
         POST login info back to login page
        */
        $ch = curl_init($LOGIN_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $login_post);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $body = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $referer = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        preg_match_all($inputs_regex, $body, $matches);
        $post_data = '';
        for($i=0;$i<count($matches[1]);$i++) {
            // complication; convert undo html encoding in the xml
            // e.g., &lt and then reencode for url
            // e.g., %3C
            $post_data .= $matches[1][$i] . '=' . urlencode(htmlspecialchars_decode(($matches[2][$i])));
            if ($i < count($matches[1])-1) {
                $post_data .= '&';
            }
        }

        $ch = curl_init($HOME_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $body = curl_exec($ch);
        $referer = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        /**
        Requesting session value isn't necessary
        Using a browser does this but I never get
        a valid result when using the script and it
        doesn't seem to matter.
        $ch = curl_init($SESSION_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        $session_page = curl_exec($ch);
        curl_close($ch);
        */

        /**
          Get invoice download page
        */
        $ch = curl_init($INVOICE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
        $invoice_page = curl_exec($ch);
        curl_close($ch);

        // not sure if this is actually needed
        // browser ends up with this cookie
        $fp = fopen($cookies, 'a');
        fwrite($fp, "customers.unfi.com\tFALSE\t/\tFALSE\t0]\tWSS_FullScreenMode\tfalse\n");
        fclose($fp);

        /**
          Extract available dates
          They're now embedded in javascript as JSON list
          of objects instead of being in a <select> field
        */
        $dates = array();
        $json_regex = '/dataSource:\s*(\[.*?\])/';
        preg_match_all($json_regex, $invoice_page, $matches);
        foreach($matches[1] as $match) {
            $data = json_decode($match);
            if (strtotime($data[0]->Text)) {
                $dates = $data;
                break;
            }
        }

        /**
          Extract inputs by id
          They contain some useful information for the
          actual downloads.
        */
        $id_regex = '/<input .*?id="(.+?)" .*?value="(.*?)"/';
        preg_match_all($id_regex, $invoice_page, $matches);
        $inputs = array();
        for($i=0; $i<count($matches[1]); $i++) {
            $inputs[$matches[1][$i]] = $matches[2][$i];
        }
        // I think only this one needs to be decoded
        if (isset($inputs['claims'])) {
            $inputs['claims'] = json_decode(htmlspecialchars_decode($inputs['claims']));
        }

        $check = $dbc->prepare('SELECT orderID FROM PurchaseOrder WHERE vendorID=? and userID=0
                            AND creationDate=? AND placedDate=?');
        $temp_dir = sys_get_temp_dir();
        foreach($dates as $date) {
            $good_date = date('Y-m-d', strtotime($date->Text));
            $doCheck = $dbc->execute($check, array($this->vendor_id, $good_date, $good_date));
            $diff = time() - strtotime($date->Text);
            $repeat = false;
            if ($dbc->num_rows($doCheck) > 0 && $diff > (7 * 24 * 60 * 60)) {
                continue;
            } elseif ($diff > (30 * 24 * 60 *60)) {
                continue;
            } elseif ($dbc->num_rows($doCheck) > 0) {
                $repeat = true;
            }

            /**
              POST a JSON value to request a particular report
              The response will be a simple JSON object containing
              the actual file URL.
              { "d" : "http://customer.unfi.com/path/to/file.zip" }
            */
            $cv = 'CustomerNumber->>' . $inputs['hdnCustomerNumber'];
            $cv .= '||InvoiceDate->>' . $date->Value;
            $cv .= '||SelectedChain->>' . $inputs['hdnCustomerNumber'];
            $cv .= '||Delimiter->>csv||Hyphen->>0';
            $cv .= '||ReportPath->>' . $inputs['hdnReportPath'];

            $json_request = array(
                'ControlsAndValues' => $cv,
                'ReportOptions' => 'zip',
                'userID' => $inputs['claims']->UserId,
                'reportID' => 41,
                'customerNumber' => $inputs['hdnCustomerNumber'],
                'emailAddress' => $inputs['claims']->EmailAddress,
                'chainAccounts' => '',
                'actionType' => 'Save',
            );

            $ch = curl_init($REPORT_GEN_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
            curl_setopt($ch, CURLOPT_REFERER, $INVOICE_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            $json = json_encode($json_request);
            $json = str_replace("\\", '', $json);
            $json = str_replace('"reportID":41', '"reportID":"41"', $json);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            // authorization is definitely needed; the rest may
            // or may not be. Debugging took awhile
            curl_setopt($ch, CURLOPT_HTTPHEADER, 
                array(
                    "Content-Type: application/json; charset=utf-8",
                    'Authorization: ' . $inputs['hfTokValidator'], 
                    'X-Requested-With: XMLHttpRequest',
                    'Accept: application/json, text/javascript, */*; q=0.01',
                    'Accept-Language: en-US,en;q=0.5',
                    'User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:28.0) Gecko/20100101 Firefox/28.0',
                    'Pragma: no-cache',
                    'Cache-Control: no-cache',
                )
            ); 
            $gen_report = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($gen_report);

            if ($response) {
                $filename = $temp_dir . '/' . str_replace('/','-',$date->Text).'.zip';
                $fp = fopen($filename, 'w');
                $ch = curl_init($response->d);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies);
                curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies);
                $invoice_file = curl_exec($ch);
                curl_close($ch);
                fclose($fp);

                $this->cronMsg("Importing invoices for " . $date->Text, FannieLogger::INFO);
                if (UIGLib::import($filename, $this->vendor_id, $repeat) === true) {
                    unlink($filename);
                } else {
                    $this->cronMsg("ERROR: IMPORT FAILED!", FannieLogger::ERROR);
                }
            
                // only download one day for now
                // remove when done testing
                //break; 
            }

            // politeness; pause between requests
            sleep(15);
        }

        /**
          Cleanup: delete cookie file
        */
        unlink($cookies);
    }

}

