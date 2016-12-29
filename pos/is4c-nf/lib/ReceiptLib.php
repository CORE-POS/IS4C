<?php
/*******************************************************************************

    Copyright 2001, 2004 Wedge Community Co-op

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

namespace COREPOS\pos\lib;
use COREPOS\pos\lib\Bitmap;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\PrintHandlers\PrintHandler;
use \CoreLocal;

/**
  @class ReceiptLib
  Receipt functions
*/
class ReceiptLib 
{
    static private $PRINT_OBJ;

    static private $EMAIL_PH = 'COREPOS\\pos\\lib\\PrintHandlers\EmailPrintHandler';
    static private $HTML_PH  = 'COREPOS\\pos\\lib\\PrintHandlers\HtmlEmailPrintHandler';

// --------------------------------------------------------------
static public function build_time($timestamp) {

    return strftime("%m/%d/%y %I:%M %p", $timestamp);
}
// --------------------------------------------------------------
static public function centerString($text) {

        return self::center($text, 59);
}
// --------------------------------------------------------------
static public function writeLine($text) 
{
    if (CoreLocal::get("print") != 0) {

        $printer_port = CoreLocal::get('printerPort');
        if (substr($printer_port, 0, 6) == "tcp://") {
            self::printToServer(substr($printer_port, 6), $text);
        } else {
            /* check fails on LTP1: in PHP4
               suppress open errors and check result
               instead 
            */
            //if (is_writable(CoreLocal::get("printerPort"))){
            $fptr = fopen(CoreLocal::get("printerPort"), "w");
            fwrite($fptr, $text);
            fclose($fptr);
        }
    }
}

/**
  Write text to server via TCP socket
  @param $print_server [string] host or host:port
  @param $text [string] text to print
  @return
   - [int]  1 => success
   - [int]  0 => problem sending text
   - [int] -1 => sent but no response. printer might be stuck/blocked
*/
static public function printToServer($printer_server, $text)
{
    $port = 9450;
    if (strstr($printer_server, ':')) {
        list($printer_server, $port) = explode(':', $printer_server, 2);
    }
    if (!function_exists('socket_create')) {
        return 0;
    }

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        return 0;
    }

    socket_set_block($socket);
    socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0)); 
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0)); 
    if (!socket_connect($socket, $printer_server, $port)) {
        return false;
    }

    $send_failed = false;
    while(true) {
        $num_written = socket_write($socket, $text);
        if ($num_written === false) {
            // error occurred
            $send_failed = true;
            break; 
        }

        if ($num_written >= strlen($text)) {
            // whole message has been sent
            // send ETX to signal message complete
            socket_write($socket, chr(0x3));
            break;
        } else {
            $text = substr($text, $num_written);
        }
    }

    $ack = socket_read($socket, 3);
    socket_close($socket);

    if ($send_failed) {
        return 0;
    } else if ($ack === false) {
        return -1;
    } else {
        return 1;
    }
}
// --------------------------------------------------------------

static public function center($text, $linewidth) {
    $blank = str_repeat(" ", 59);
    $text = trim($text);
    $lead = (int) (($linewidth - strlen($text)) / 2);
    $newline = substr($blank, 0, $lead).$text;
    return $newline;
}

// -------------------------------------------------------------

// -------------------------------------------------------------
static public function printReceiptHeader($dateTimeStamp, $ref) 
{
    $receipt = self::$PRINT_OBJ->TextStyle(True);
    $img_cache = CoreLocal::get('ImageCache');
    if (!is_array($img_cache)) $img_cache = array();

    for ($i=1; $i <= CoreLocal::get("receiptHeaderCount"); $i++){

        /**
          If the receipt header line includes non-printable characters,
          send it to the receipt printer exactly as-is.
          If the receipt header line is "nv" and a number, print the
          corresponding image # from the printer's nonvolatile RAM.
          If the receipt header line is a .bmp file (and it exists),
          print it on the receipt. Otherwise just print the line of
          text centered.
        */
        $headerLine = CoreLocal::get("receiptHeader".$i);
        $graphics_path = MiscLib::base_url().'graphics';
        if (!ctype_print($headerLine)) {
            $receipt .= self::$PRINT_OBJ->rawEscCommand($headerLine) . "\n";
        } elseif (preg_match('/nv(\d{1,3})/i', $headerLine, $match)) {
            $receipt .= self::$PRINT_OBJ->renderBitmapFromRam((int)$match[1]);
        } elseif (substr($headerLine,-4) == ".bmp" && file_exists($graphics_path.'/'.$headerLine)){
            // save image bytes in cache so they're not recalculated
            // on every receipt
            $img_file = $graphics_path.'/'.$headerLine;
            if (isset($img_cache[basename($img_file)]) && !empty($img_cache[basename($img_file)]) 
                && get_class(self::$PRINT_OBJ)!=self::$EMAIL_PH
                && get_class(self::$PRINT_OBJ)!=self::$HTML_PH
                ){
                $receipt .= $img_cache[basename($img_file)]."\n";
            }
            else {
                $img = self::$PRINT_OBJ->RenderBitmapFromFile($img_file);
                $receipt .= $img."\n";
                $img_cache[basename($img_file)] = $img;
                CoreLocal::set('ImageCache',$img_cache);
                $receipt .= "\n";
            }
        } else {
            /** put first header line in larger font **/
            if ($i == 1) {
                $receipt .= self::$PRINT_OBJ->TextStyle(true, false, true);
                $receipt .= self::$PRINT_OBJ->centerString(CoreLocal::get("receiptHeader$i"));
                $receipt .= self::$PRINT_OBJ->TextStyle(true);
            } else {
                $receipt .= self::$PRINT_OBJ->centerString(CoreLocal::get("receiptHeader$i"), false);
            }
            $receipt .= "\n";
        }
    }

    $receipt .= "\n";
    $receipt .= "Cashier: ".CoreLocal::get("cashier")."\n\n";

    $time = self::build_time($dateTimeStamp);
    $time = str_replace(" ","     ",$time);
    list($emp, $reg, $trans) = self::parseRef($ref);
    $ref = $emp . '-' . $reg . '-' . $trans;
    $spaces = 55 - strlen($time) - strlen($ref);
    $receipt .= $time.str_repeat(' ',$spaces).$ref."\n";
            
    return $receipt;
}

// Charge Footer split into two functions by apbw 2/1/05
//#'C - is this never called?
static public function printChargeFooterCust($dateTimeStamp, $ref, $program="charge") 
{
    $chgName = \COREPOS\pos\lib\MemberLib::getChgName();            // added by apbw 2/14/05 SCR

    $date = self::build_time($dateTimeStamp);

    /* Where should the label values come from, be entered?
       20Mar15 Eric Lee. Andy's comment was about Coop Cred which
         is now implemented as he describes.
       24Apr14 Andy
       Implementing these as ReceiptMessage subclasses might work
       better. Plugins could provide their own ReceiptMessage subclass
       with the proper labels (or config settings for the labels)
    */
    $labels = array();
    $labels['charge'] = array("CUSTOMER CHARGE ACCOUNT\n", "Charge Amount:");
    $labels['coopcred'] = array("COOP CRED ACCOUNT\n", "Credit Amount:");
    $labels['debit'] = array("CUSTOMER DEBIT ACCOUNT\n", "Debit Amount:");
    /* Could append labels from other modules
    foreach (CoreLocal::get('plugins') as $plugin)
        if isset($plugin['printChargeFooterCustLabels']) {
            $labels[]=$plugin['printChargeFooterCustLabels']
        }
    */

    $receipt = chr(27).chr(33).chr(5)."\n\n\n".self::centerString(_("C U S T O M E R   C O P Y"))."\n"
           .self::centerString("................................................")."\n"
           .self::centerString(CoreLocal::get("chargeSlip1"))."\n\n"
           . $labels["$program"][0]
           ._("Name: ").trim($chgName)."\n"        // changed by apbw 2/14/05 SCR
           ._("Member Number: ").trim(CoreLocal::get("memberID"))."\n"
           ._("Date: ").$date."\n"
           ._("REFERENCE #: ").$ref."\n"
           . $labels["$program"][1] . " $".number_format(-1 * CoreLocal::get("chargeTotal"), 2)."\n"
           .self::centerString("................................................")."\n"
           ."\n\n\n\n\n\n\n"
           .chr(27).chr(105);

    return $receipt;

}

/**
  Get a signature slip for use with a charge account
  @param $dateTimeStamp [string] representing date and time
  @param $ref [string] transaction identifer 
  @param $program [string, optional] identifier for different
    types of charge accounts that require different text
  @return [string] receipt text
*/
static public function printChargeFooterStore($dateTimeStamp, $ref, $program="charge") 
{
    $chgName = \COREPOS\pos\lib\MemberLib::getChgName();            // added by apbw 2/14/05 SCR
    
    $date = self::build_time($dateTimeStamp);

    /* Where should the label values come from, be entered?
       20Mar15 Eric Lee. Andy's comment was about Coop Cred which
         is now implemented as he describes.
       24Apr14 Andy
       Implementing these as ReceiptMessage subclasses might work
       better. Plugins could provide their own ReceiptMessage subclass
       with the proper labels (or config settings for the labels)
    */
    $labels = array();
    $labels['charge'] = array(
            _("CUSTOMER CHARGE ACCOUNT\n"),
            _("Charge Amount:"),
            _("I AGREE TO PAY THE ABOVE AMOUNT\n"),
            _("TO MY CHARGE ACCOUNT\n"),
    );
    $labels['debit'] = array(
            _("CUSTOMER DEBIT ACCOUNT\n"),
            _("Debit Amount:"),
            _("I ACKNOWLEDGE THE ABOVE DEBIT\n"),
            _("TO MY DEBIT ACCOUNT\n"),
    );

    /* Could append labels from other modules
    foreach (CoreLocal::get('plugins') as $plugin)
        if (isset($plugin['printChargeFooterCustLabels'])) {
            $labels[]=$plugin['printChargeFooterCustLabels']
        }
    */

    $receipt = "\n\n\n\n\n\n\n"
           .chr(27).chr(105)
           .chr(27).chr(33).chr(5)        // apbw 3/18/05 
           ."\n".self::centerString(CoreLocal::get("chargeSlip2"))."\n"
           .self::centerString("................................................")."\n"
           .self::centerString(CoreLocal::get("chargeSlip1"))."\n\n"
           . $labels["$program"][0]
           ._("Name: ").trim($chgName)."\n"        // changed by apbw 2/14/05 SCR
           ._("Member Number: ").trim(CoreLocal::get("memberID"))."\n"
           ._("Date: ").$date."\n"
           ._("REFERENCE #: ").$ref."\n"
           .$labels["$program"][1] . " $".number_format(-1 * CoreLocal::get("chargeTotal"), 2)."\n"
           . $labels["$program"][2]
           . $labels["$program"][3]
           ._("Purchaser Sign Below\n\n\n")
           ."X____________________________________________\n"
           .CoreLocal::get("fname")." ".CoreLocal::get("lname")."\n\n"
           .self::centerString(".................................................")."\n\n";

    return self::chargeBalance($receipt, $program, $ref);

}

static public function printCabCoupon($dateTimeStamp, $ref)
{
    $receipt = "\n";

    $receipt .= self::biggerFont(self::centerBig("WHOLE FOODS COMMUNITY CO-OP"))."\n\n";
    $receipt .= self::centerString("(218) 728-0884")."\n";
    $receipt .= self::centerString("MEMBER OWNED SINCE 1970")."\n";
    $receipt .= self::centerString(self::build_time($dateTimeStamp))."\n";
    $receipt .= self::centerString('Effective this date ONLY')."\n";
    $parts = explode("-",$ref);
    $receipt .= self::centerString("Cashier: $parts[0]")."\n";
    $receipt .= self::centerString("Transaction: $ref")."\n";
    $receipt .= "\n";
    $receipt .= "Your net purchase today of at least $30.00"."\n";
    $receipt .= "qualifies you for a WFC CAB COUPON"."\n";
    $receipt .= "in the amount of $3.00";
    $receipt .= " with\n\n";
    $receipt .= "GO GREEN TAXI (722-8090) or"."\n";
    $receipt .= "YELLOW CAB OF DULUTH (727-1515)"."\n";
    $receipt .= "from WFC toward the destination of\n";
    $receipt .= "your choice TODAY"."\n\n";

        
    $receipt .= ""
        ."This coupon is not transferable.\n" 
        ."One coupon/day/customer.\n"
        ."Any amount of fare UNDER the value of this coupon\n"
        ."is the property of the cab company.\n"
        ."Any amount of fare OVER the value of this coupon\n"
               ."is your responsibility.\n"
        ."Tips are NOT covered by this coupon.\n"
        ."Acceptance of this coupon by the cab driver is\n"
        ."subject to the terms and conditions noted above.\n"; 

    return $receipt;
}

/***** jqh 09/29/05 functions added for new receipt *****/
static public function biggerFont($str) {
    $receipt=chr(29).chr(33).chr(17);
    $receipt.=$str;
    $receipt.=chr(29).chr(33).chr(00);

    return $receipt;
}
static public function centerBig($text) {
    $blank = str_repeat(" ", 30);
    $text = trim($text);
    $lead = (int) ((30 - strlen($text)) / 2);
    $newline = substr($blank, 0, $lead).$text;
    return $newline;
}
/***** jqh end change *****/

/***** CvR 06/28/06 calculate current balance for receipt ****/
static public function chargeBalance($receipt, $program="charge", $trans_num='')
{
    \COREPOS\pos\lib\MemberLib::chargeOk();

    $labels = array();
    $labels['charge'] = array(_("Current IOU Balance:") , 1);
    $labels['debit'] = array(_("Debit available:"), -1);
    if (CoreLocal::get('InvertAR')) {
        $labels['charge'][1] = -1;
    }

    $dbc = Database::tDataConnect();
    list($emp, $reg, $trans) = self::parseRef($trans_num);
    $ar_depts = MiscLib::getNumbers(CoreLocal::get('ArDepartments'));
    $checkQ = "SELECT trans_id 
               FROM localtranstoday 
               WHERE 
                emp_no=" . ((int)$emp) . "
                AND register_no=" . ((int)$reg) . "
                AND trans_no=" . ((int)$trans);
    if (count($ar_depts) == 0) {
        $checkQ .= " AND trans_subtype='MI'";
    } else {
        $checkQ .= " AND (trans_subtype='MI' OR department IN (";
        foreach ($ar_depts as $ar_dept) {
            $checkQ .= $ar_dept . ',';
        }
        $checkQ = substr($checkQ, 0, strlen($checkQ)-1) . '))';
    }
    $checkR = $dbc->query($checkQ);
    $num_rows = $dbc->num_rows($checkR);

    $currActivity = CoreLocal::get("memChargeTotal");
    $currBalance = CoreLocal::get("balance") - $currActivity;
    
    if (($num_rows > 0 || $currBalance != 0) && CoreLocal::get("memberID") != CoreLocal::get('defaultNonMem')) {
        $chargeString = $labels["$program"][0] .
            " $".sprintf("%.2f",($labels["$program"][1] * $currBalance));
        $receipt = $receipt."\n\n".self::biggerFont(self::centerBig($chargeString))."\n";
    }
    
    return $receipt;
}

static public function normalFont() {
    return chr(27).chr(33).chr(5);
}
static public function boldFont() {
    return chr(27).chr(33).chr(9);
}
static private function initDriver()
{
    if (!is_object(self::$PRINT_OBJ)) {
        self::$PRINT_OBJ = PrintHandler::factory(CoreLocal::get('ReceiptDriver'));
    }
}
static public function bold()
{
    self::initDriver(); 
    return self::$PRINT_OBJ->TextStyle(true, true);
}
static public function unbold()
{
    self::initDriver(); 
    return self::$PRINT_OBJ->TextStyle(true, false);
}

static private function lookupLocal($ref)
{
    $dbc = Database::tDataConnect();
    list($empNo, $laneNo, $transNo) = self::parseRef($ref);

    $lookup = sprintf("SELECT 
        SUM(CASE WHEN p.local=1 THEN l.total ELSE 0 END) as localTTL,
        SUM(CASE WHEN l.trans_type IN ('I','D') then l.total ELSE 0 END) as itemTTL
        FROM localtranstoday AS l LEFT JOIN ".
        CoreLocal::get('pDatabase').$dbc->sep()."products AS p
        ON l.upc=p.upc
        WHERE l.trans_type IN ('I','D')
            AND emp_no=%d AND register_no=%d AND trans_no=%d",
        $empNo, $laneNo, $transNo);
    $lookup = $dbc->query($lookup);
    $ret = array('localTTL' => 0, 'itemTTL' => 0);
    if ($dbc->numRows($lookup) > 0) {
        $ret = $dbc->fetch_row($lookup);
    }
 
    return $ret;
}

static public function localTTL($ref)
{
    $row = self::lookupLocal($ref);
    if ($row['localTTL'] == 0) 
        return '';

    $str = sprintf(_("LOCAL PURCHASES = \$%.2f"), $row['localTTL']);
    return $str."\n";
}

static public function graphedLocalTTL($ref)
{
    $row = self::lookupLocal($ref);
    if ($row['localTTL'] == 0) 
        return '';

    $percent = ((float)$row['localTTL']) / ((float)$row['itemTTL']);
    $str = sprintf(_('LOCAL PURCHASES = $%.2f (%.2f%%)'), 
            $row['localTTL'], 100*$percent);
    $str .= "\n";

    $str .= self::$PRINT_OBJ->RenderBitmap(Bitmap::barGraph($percent), 'L');
    return $str."\n";
}

static private function getFetch()
{
    $FETCH_MOD = CoreLocal::get("RBFetchData");
    if ($FETCH_MOD == 'DefaultReceiptDataFetch') {
        $FETCH_MOD = 'COREPOS\\pos\\lib\\ReceiptBuilding\\DataFetch\\' . $FETCH_MOD;
    }
    return $FETCH_MOD == '' ? 'COREPOS\\pos\\lib\\ReceiptBuilding\\DataFetch\\DefaultReceiptDataFetch' : $FETCH_MOD;
}

static private function getFilter()
{
    $mod = CoreLocal::get("RBFilter");
    if ($mod == 'DefaultReceiptFilter' || $mod == 'InOrderReceiptFilter') {
        $mod = 'COREPOS\\pos\\lib\\ReceiptBuilding\\Filter\\' . $mod;
    }
    return $mod == '' ? 'COREPOS\\pos\\lib\\ReceiptBuilding\\Filter\\DefaultReceiptFilter' : $mod;
}

static private $sorts = array(
    'DefaultReceiptSort',
    'DiscountFirstReceiptSort',
    'DoubleSubtotalReceiptSort',
    'GroupSavingsSort',
    'InOrderReceiptSort',
);

static private function getSort()
{
    $mod = CoreLocal::get("RBSort");
    if ($mod != '' && in_array($mod, self::$sorts)) {
        $mod = 'COREPOS\\pos\\lib\\ReceiptBuilding\\Sort\\' . $mod;
    }
    return $mod == '' ? 'COREPOS\\pos\\lib\\ReceiptBuilding\\Sort\\DefaultReceiptSort' : $mod;
}

static private function getTag()
{
    $mod = CoreLocal::get("RBTag");
    if ($mod == 'DefaultReceiptTag') {
        $mod = 'COREPOS\\pos\\lib\\ReceiptBuilding\\Tag\\' . $mod;
    }
    return $mod == '' ? 'COREPOS\\pos\\lib\\ReceiptBuilding\\Tag\\DefaultReceiptTag' : $mod;
}

static public function receiptFromBuilders($reprint=False,$trans_num='')
{
    $empNo=0;$laneNo=0;$transNo=0;
    list($empNo, $laneNo, $transNo) = self::parseRef($trans_num);
    self::initDriver();

    $FETCH_MOD = self::getFetch();
    $mod = new $FETCH_MOD();
    $data = array();
    $dbc = Database::tDataConnect();
    $data = $mod->fetch($dbc,$empNo,$laneNo,$transNo);

    // load module configuration
    $FILTER_MOD = self::getFilter();
    $SORT_MOD = self::getSort();
    $TAG_MOD = self::getTag();

    $fil = new $FILTER_MOD();
    $recordset = $fil->filter($dbc, $data);

    $sort = new $SORT_MOD();
    $recordset = $sort->sort($recordset);

    $tag = new $TAG_MOD();
    $recordset = $tag->tag($recordset);

    $ret = "";
    $width = CoreLocal::get('ReceiptLineWidth');
    if (!is_numeric($width) || $width <= 0 || !$width) {
        $width = 56;
    }
    foreach ($recordset as $record) {
        $class_name = 'COREPOS\\pos\\lib\\ReceiptBuilding\\Format\\' . $record['tag'] . 'ReceiptFormat';
        if (!class_exists($class_name)) continue;
        $obj = new $class_name(self::$PRINT_OBJ, $width);

        $line = $obj->format($record);

        if ($obj->isBold()){
            $ret .= self::$PRINT_OBJ->TextStyle(True,True);
            $ret .= $line;
            $ret .= self::$PRINT_OBJ->TextStyle(True,False);
            $ret .= "\n";
        } else {
            $ret .= $line;
            $ret .= "\n";
        }
    }

    return $ret;
}

static public function receiptDetail($reprint=false, $trans_num='') 
{ 
    // put into its own function to make it easier to follow, and slightly 
    // modified for wider-spread use of joe's "new" receipt format --- apbw 7/3/2007
    if (CoreLocal::get("newReceipt") == 2) {
        return self::receiptFromBuilders($reprint, $trans_num);
    }

    $detail = "";
    $empNo=0; $laneNo=0; $transNo=0;
    list($empNo, $laneNo, $transNo) = self::parseRef($trans_num);
        
    if (CoreLocal::get("newReceipt") == 0 ) {
        // if old style has been specifically requested 
        // for a partial or reprint, use old format
        $query = "select linetoprint from rp_receipt
            where emp_no=$empNo and register_no=$laneNo
            and trans_no=$transNo order by trans_id";
        $dbc = Database::tDataConnect();
        $result = $dbc->query($query);
        $num_rows = $dbc->num_rows($result);
        // loop through the results to generate the items listing.
        for ($i = 0; $i < $num_rows; $i++) {
            $row = $dbc->fetchRow($result);
            $detail .= $row[0]."\n";
        }
    } else { 
        $dbc = Database::tDataConnect();
        /**
          The newReceipt=1 option should not be shown in the configuration
          UI if the view doesn't exist, but if the configuration gets
          messed up try to do something useful rather than printing
          nothing.
        */
        if (!$dbc->tableExists('rp_receipt_reorder_unions_g')) {
            return self::receiptFromBuilders($reprint, $trans_num);
        }

        // otherwise use new format 
        $query = "select linetoprint,sequence,dept_name,ordered, 0 as ".
                $dbc->identifierEscape('local')
            ." from rp_receipt_reorder_unions_g where emp_no=$empNo and "
            ." register_no=$laneNo and trans_no=$transNo "
            ." order by ordered,dept_name, " 
            ." case when ordered=4 then '' else upc end, "
                .$dbc->identifierEscape('sequence');

        $result = $dbc->query($query);
        $num_rows = $dbc->num_rows($result);
            
        // loop through the results to generate the items listing.
        $lastDept="";
        while ($row = $dbc->fetchRow($result)) {
            if ($row[2]!=$lastDept){  // department header
                
                if ($row['2']==''){
                    $detail .= "\n";
                } else{
                    $detail .= self::$PRINT_OBJ->TextStyle(True,True);
                    $detail .= $row[2];
                    $detail .= self::$PRINT_OBJ->TextStyle(True,False);
                    $detail .= "\n";
                }
            }
            /***** jqh 12/14/05 fix tax exempt on receipt *****/
            if ($row[1]==2 and CoreLocal::get("TaxExempt")==1){
                $detail .= "                                         TAX    0.00\n";
            } elseif ($row[1]==1 and CoreLocal::get("TaxExempt")==1){
                $queryExempt="select ".$dbc->concat(
                "right(".$dbc->concat('space(44)',"'SUBTOTAL'",'').", 44)",
                "right(".$dbc->concat('space(8)',$dbc->convert('runningTotal-tenderTotal','char'),'').", 8)", 
                "space(4)",'')." as linetoprint,
                1 as sequence,null as dept_name,3 as ordered,'' as upc
                from lttsummary";
                $resultExempt = $dbc->query($queryExempt);
                $rowExempt = $dbc->fetchRow($resultExempt);
                $detail .= $rowExempt[0]."\n";
            } else {
                if (CoreLocal::get("promoMsg") == 1 && $row[4] == 1 ){ 
                    // '*' added to local items 8/15/2007 apbw for eat local challenge 
                    $detail .= '*'.$row[0]."\n";
                } else {
                    if ( strpos($row[0]," TOTAL") ) {         
                        // if it's the grand total line . . .
                        $detail .= self::$PRINT_OBJ->TextStyle(True,True);
                        $detail .= $row[0]."\n";
                        $detail .= self::$PRINT_OBJ->TextStyle(True,False);
                    } else {
                        $detail .= $row[0]."\n";
                    }
                }
            }
            /***** jqh end change *****/
            
            $lastDept=$row[2];
        } // end for loop
    }

    return $detail;
}

static private function processColumn($col1)
{
    $c1max = 0;
    $col1s = array();
    foreach( $col1 as $c1) {
        $c1s = trim(str_replace(array(self::boldFont(),self::normalFont()), "", $c1));
        $col1s[] = $c1s;
        $c1max = max($c1max, strlen($c1s));
    }

    return array($col1s, $c1max);
}

static public function twoColumns($col1, $col2) {
    // init
    $max = 56;
    $text = "";
    // find longest string in each column, ignoring font change strings
    list($col1s, $c1max) = self::processColumn($col1);
    list($col2s, $c2max) = self::processColumn($col2);
    // space the columns as much as they'll fit
    $spacer = $max - $c1max - $c2max;
    // scan both columns
    for( $x=0; isset($col1[$x]) && isset($col2[$x]); $x++) {
        $c1r = trim($col1[$x]);  $c1l = strlen($col1s[$x]);
        $c2r = trim($col2[$x]);  $c2l = strlen($col2s[$x]);
        if( ($c1max+$spacer+$c2l) <= $max) {
            $text .= $c1r . str_repeat(" ", ($c1max+$spacer)-$c1l) . $c2r . "\n";
        } else {
            $text .= $c1r . "\n" . str_repeat(" ", $c1max+$spacer) . $c2r . "\n";
        }
    }
    // if one column is longer than the other, print the extras
    // (only one of these should happen since the loop above runs as long as both columns still have rows)
    for( $y=$x; isset($col1[$y]); $y++) {
        $text .= trim($col1[$y]) . "\n";
    } // col1 extras
    for( $y=$x; isset($col2[$y]); $y++) {
        $text .= str_repeat(" ", $c1max+$spacer) . trim($col2[$y]) . "\n";
    } // col2 extras
    return $text;
}

static public function parseRef($ref)
{
    $emp=$reg=$trans=0;
    if (strstr($ref, '-')) {
        list($emp, $reg, $trans) = explode('-', $ref, 3);
    } else if (strstr($ref, '::')) {
        // values in different order; rebuild correct $ref
        list($reg, $emp, $trans) = explode('::', $ref, 3);
        $ref = sprintf('%d-%d-%d',$emp,$reg,$trans);
    } else {
        list($emp, $reg, $trans) = explode('-', self::mostRecentReceipt(), 3);
    }

    return array($emp, $reg, $trans);
}

static private function setupReprint($where)
{
    // lookup trans information
    $dbc = Database::tDataConnect();
    $queryHeader = "
        SELECT
            MIN(datetime) AS dateTimeStamp,
            MAX(card_no) AS memberID,
            SUM(CASE WHEN upc='0000000008005' THEN total ELSE 0 END) AS couponTotal,
            SUM(CASE WHEN upc='DISCOUNT' THEN total ELSE 0 END) AS transDiscount,
            SUM(CASE WHEN trans_subtype IN ('MI','CX') THEN total ELSE 0 END) AS chargeTotal,
            SUM(CASE WHEN discounttype=1 THEN discount ELSE 0 END) AS discountTTL,
            SUM(CASE WHEN discounttype=2 THEN memDiscount ELSE 0 END) AS memSpecial
        FROM localtranstoday
        WHERE " . $where . "
            AND datetime >= " . $dbc->curdate() . "
        GROUP BY register_no,
            emp_no,
            trans_no";

    $header = $dbc->query($queryHeader);
    $row = $dbc->fetch_row($header);
    $dateTimeStamp = $row["dateTimeStamp"];
    $dateTimeStamp = strtotime($dateTimeStamp);
    
    // set session variables from trans information
    CoreLocal::set("memberID",$row["memberID"]);
    CoreLocal::set("memCouponTLL",$row["couponTotal"]);
    CoreLocal::set("transDiscount",$row["transDiscount"]);
    CoreLocal::set("chargeTotal",-1*$row["chargeTotal"]);
    CoreLocal::set("discounttotal",$row["discountTTL"]);
    CoreLocal::set("memSpecial",$row["memSpecial"]);

    // lookup member info
    $dbc = Database::pDataConnect();
    $queryID = "select LastName,FirstName,Type,blueLine from custdata 
        where CardNo = '".CoreLocal::get("memberID")."' and personNum=1";
    $result = $dbc->query($queryID);
    $row = $dbc->fetchRow($result);

    // set session variables from member info
    CoreLocal::set("lname",$row["LastName"]);
    CoreLocal::set("fname",$row["FirstName"]);
    CoreLocal::set('isMember', ($row['Type']=='PC' ? 1 : 0));
    CoreLocal::set("memMsg",$row["blueLine"]);
    if (CoreLocal::get("isMember") == 1) {
        CoreLocal::set("yousaved",number_format( CoreLocal::get("transDiscount") 
                + CoreLocal::get("discounttotal") + CoreLocal::get("memSpecial"), 2));
        CoreLocal::set("couldhavesaved",0);
        CoreLocal::set("specials",number_format(CoreLocal::get("discounttotal") 
                + CoreLocal::get("memSpecial"), 2));
    } else {
        CoreLocal::set("yousaved",CoreLocal::get("discounttotal"));
        CoreLocal::set("couldhavesaved",number_format(CoreLocal::get("memSpecial") == '' ? 0 : CoreLocal::get('memSpecial'), 2));
        CoreLocal::set("specials",CoreLocal::get("discounttotal"));
    }

    return $dateTimeStamp;
}

static private $msgMods = array(
    'BarcodeTransIdentifierMessage',
    'CCReceiptMessage',
    'DeclineReceiptMessage',
    'EbtReceiptMessage',
    'EquitySoldReceiptMessage',
    'GCBalanceReceiptMessage',
    'GCReceiptMessage',
    'GenericSigSlipMessage',
    'ReceiptMessage',
    'StoreCreditIssuedReceiptMessage',
);

static private function getTypeMap()
{
    $type_map = array();
    foreach(self::messageMods() as $class){
        if (in_array($class, self::$msgMods)) {
            $class = 'COREPOS\\pos\\lib\\ReceiptBuilding\\Messages\\' . $class;
        }
        if (!class_exists($class)) {
            continue;
        }
        $obj = new $class();
        if ($obj->standalone_receipt_type != '')
            $type_map[$obj->standalone_receipt_type] = $obj;
    }

    return $type_map;
}

static private function memberFooter($receipt, $ref)
{
    $mod = CoreLocal::get('ReceiptThankYou');
    if ($mod != '' && class_exists('COREPOS\\pos\\lib\\ReceiptBuilding\\ThankYou\\' . $mod)) {
        $mod = 'COREPOS\\pos\\lib\\ReceiptBuilding\\ThankYou\\' . $mod;
    } elseif ($mod === '' || !class_exists($mod)) {
        $mod = 'COREPOS\\pos\\lib\\ReceiptBuilding\\ThankYou\\DefaultReceiptThanks';
    }
    $obj = new $mod();
    $obj->setPrintHandler(self::$PRINT_OBJ);
    $receipt['any'] .= $obj->message($ref);

    return $receipt;
}

static private function receiptFooters($receipt, $ref)
{
    for ($i = 1; $i <= CoreLocal::get("receiptFooterCount"); $i++){
        $receipt['any'] .= self::$PRINT_OBJ->centerString(CoreLocal::get("receiptFooter$i"));
        $receipt['any'] .= "\n";
    }

    if (CoreLocal::get("store")=="wfc") {
        $refund_date = date("m/d/Y",mktime(0,0,0,date("n"),date("j")+30,date("Y")));
        $receipt['any'] .= self::$PRINT_OBJ->centerString("returns accepted with this receipt through ".$refund_date);
        $receipt['any'] .= "\n";
    }

    $chargeProgram = 'charge';
    /***** CvR add charge total to receipt bottom ****/
    $receipt['any'] = self::chargeBalance($receipt['any'], $chargeProgram, $ref);
    /**** CvR end ****/

    return $receipt;
}

static private function messageModFooters($receipt, $where, $ref, $reprint)
{
    // check if message mods have data
    // and add them to the receipt
    $dbc = Database::tDataConnect();
    $modQ = "SELECT ";
    $select_mods = array();
    foreach(self::messageMods() as $class){
        if (in_array($class, self::$msgMods)) {
            $class = 'COREPOS\\pos\\lib\\ReceiptBuilding\\Messages\\' . $class;
        }
        if (!class_exists($class)) {
            continue;
        }
        $obj = new $class();
        $obj->setPrintHandler(self::$PRINT_OBJ);
        $modQ .= $obj->select_condition().' AS '.$dbc->identifierEscape($class).',';
        $select_mods[$class] = $obj;
    }
    $modQ = rtrim($modQ,',');
    if (count($select_mods) > 0){
        $modQ .= ' FROM localtranstoday
                WHERE ' . $where . '
                    AND datetime >= ' . $dbc->curdate();
        $modR = $dbc->query($modQ);
        $row = array();
        if ($dbc->num_rows($modR) > 0) $row = $dbc->fetch_row($modR);
        foreach($select_mods as $class => $obj){
            if (!isset($row[$class])) continue;    
            if ($obj->paper_only)
                $receipt['print'] .= $obj->message($row[$class], $ref, $reprint);
            else
                $receipt['any'] .= $obj->message($row[$class], $ref, $reprint);
        }
    }

    return $receipt;
}

static private function messageMods()
{
    $message_mods = CoreLocal::get('ReceiptMessageMods');
    if (!is_array($message_mods)) $message_mods = array();

    return $message_mods;
}

/**
  generates a receipt string
  @param $arg1 string receipt type 
  @param $ref string transaction identifier
  @param $second boolean indicating it's a second receipt
  @param $email generate email-style receipt
  @return string receipt content
*/
static public function printReceipt($arg1, $ref, $second=False, $email=False) 
{
    if($second) $email = False; // store copy always prints
    if($arg1 != "full") $email = False;
    $reprint = $arg1 == 'reprint' ? true : false;
    $dateTimeStamp = time();

    list($emp, $reg, $trans) = self::parseRef($ref);
    $where = sprintf('emp_no=%d AND register_no=%d AND trans_no=%d',
                    $emp, $reg, $trans);

    if ($reprint) {
        $arg1 = 'full';
        $email = false;
        $second = false;
        $dateTimeStamp = self::setupReprint($where);
    }
    $chargeProgram = 'charge';

    self::$PRINT_OBJ = PrintHandler::factory(CoreLocal::get('ReceiptDriver'));
    $receipt = "";

    $noreceipt = (CoreLocal::get("receiptToggle")==1 ? 0 : 1);
    $ignoreNR = array("ccSlip");

    // find receipt types, or segments, provided via modules
    $type_map = self::getTypeMap();

    if ($noreceipt != 1 || in_array($arg1,$ignoreNR) || $email) {
        $receipt = self::printReceiptHeader($dateTimeStamp, $ref);

        if ($second) {
            $ins = self::$PRINT_OBJ->centerString(_("( S T O R E   C O P Y )"))."\n";
            $receipt = substr($receipt,0,3).$ins.substr($receipt,3);
        } elseif ($reprint !== false) {
            $ins = self::$PRINT_OBJ->centerString(_("***   R E P R I N T   ***"))."\n";
            $receipt = substr($receipt,0,3).$ins.substr($receipt,3);
        }

        if ($arg1 == "full") {
            $receipt = array('any'=>'','print'=>'');
            if ($email) {
                $eph = self::emailReceiptMod();
                self::$PRINT_OBJ = new $eph();
            }
            $receipt['any'] = self::printReceiptHeader($dateTimeStamp, $ref);

            $receipt['any'] .= self::receiptDetail($reprint, $ref);
            $receipt['any'] .= self::$PRINT_OBJ->addRenderingSpacer('end of items');

            $savingsMode = CoreLocal::get('ReceiptSavingsMode');
            if ($savingsMode != '' && class_exists('COREPOS\\pos\\lib\\ReceiptBuilding\\Savings\\' . $savingsMode)) {
                $savingsMode = 'COREPOS\\pos\\lib\\ReceiptBuilding\\Savings\\' . $savingsMode;
            } elseif ($savingsMode === '' || !class_exists($savingsMode)) {
                $savingsMode = 'COREPOS\\pos\\lib\\ReceiptBuilding\\Savings\\DefaultReceiptSavings';
            }
            $savings = new $savingsMode();
            $savings->setPrintHandler(self::$PRINT_OBJ);
            $receipt['any'] .= $savings->savingsMessage($ref);

            /**
              List local total as defined by settings
              Default to $ total if no setting exists
            */
            if (CoreLocal::get('ReceiptLocalMode') == 'total' || CoreLocal::get('ReceiptLocalMode') == '') {
                $receipt['any'] .= self::localTTL($ref);
            } elseif (CoreLocal::get('ReceiptLocalMode') == 'percent') {
                $receipt['any'] .= self::graphedLocalTTL($ref);
            }
            $receipt['any'] .= "\n";
    
            $receipt = self::memberFooter($receipt, $ref);
            $receipt = self::receiptFooters($receipt, $ref);
            $receipt = self::messageModFooters($receipt, $where, $ref, $reprint);

            if (CoreLocal::get('memberID') != CoreLocal::get('defaultNonMem')) {
                $memMessages = self::memReceiptMessages(CoreLocal::get("memberID"));
                $receipt['print'] .= $memMessages['print'];
                $receipt['any'] .= $memMessages['any'];
            }
            CoreLocal::set("equityNoticeAmt",0);

            // knit pieces back together if not emailing
            if (!$email) $receipt = ''.$receipt['any'].$receipt['print'];

            CoreLocal::set("headerprinted",0);
        } else if (isset($type_map[$arg1])) {
            $obj = $type_map[$arg1];
            $receipt = $obj->standalone_receipt($ref, $reprint);
        } else if ($arg1 == "cab") {
            $ref = CoreLocal::get("cabReference");
            $receipt = self::printCabCoupon($dateTimeStamp, $ref);
            CoreLocal::set("cabReference","");
        } else {
            $receipt = self::simpleReceipt($receipt, $arg1, $where);
        }
    }

    /* --------------------------------------------------------------
      print store copy of charge slip regardless of receipt print setting - apbw 2/14/05 
      ---------------------------------------------------------------- */
    $tmap = CoreLocal::get('TenderMap');
    // skip signature slips if using electronic signature capture (unless it's a reprint)
    if ((is_array($tmap) && isset($tmap['MI']) && $tmap['MI'] != 'SignedStoreChargeTender') || $reprint) {
        if (CoreLocal::get("chargeTotal") != 0 && ((CoreLocal::get("End") == 1 && !$second) || $reprint)) {
            if (is_array($receipt)) {
                $receipt['print'] .= self::printChargeFooterStore($dateTimeStamp, $ref, $chargeProgram);
            } else {
                $receipt .= self::printChargeFooterStore($dateTimeStamp, $ref, $chargeProgram);
            }
        }
    }
            
    $receipt = self::cutReceipt($receipt, $second);
    
    if (!in_array($arg1,$ignoreNR))
        CoreLocal::set("receiptToggle",1);
    if ($reprint){
        CoreLocal::set("memMsg","");
        CoreLocal::set("memberID","0");
        CoreLocal::set("percentDiscount",0);
        CoreLocal::set('isMember', 0);
    }
    return $receipt;
}

static private function cutReceipt($receipt, $second)
{
    if (is_array($receipt)){
        if ($second){
            // second always prints
            $receipt['print'] = $receipt['any'].$receipt['print'];
            $receipt['any'] = '';
        }
        if ($receipt['print'] !== ''){
            $receipt['print'] = $receipt['print']."\n\n\n\n\n\n\n";
            $receipt['print'] .= chr(27).chr(105);
        }
    } elseif ($receipt !== ""){
        $receipt = $receipt."\n\n\n\n\n\n\n";
        $receipt .= chr(27).chr(105);
    }

    return $receipt;
}

static private function simpleReceipt($receipt, $arg1, $where)
{
    /***** jqh 09/29/05 if receipt isn't full, then display receipt in old style *****/
    $query="select linetoprint from rp_receipt WHERE " . $where . ' ORDER BY trans_id';
    if ($arg1 == 'partial') {
        // partial has to use localtemptrans
        $query = 'SELECT linetoprint FROM receipt';
    }
    $dbc = Database::tDataConnect();
    $result = $dbc->query($query);
    $num_rows = $dbc->num_rows($result);

    // loop through the results to generate the items listing.
    for ($i = 0; $i < $num_rows; $i++) {
        $row = $dbc->fetchRow($result);
        $receipt .= $row[0]."\n";
    }
    /***** jqh end change *****/

    $dashes = "\n".self::centerString("----------------------------------------------")."\n";

    if ($arg1 == "partial") {
        $receipt .= $dashes.self::centerString(_("*    P A R T I A L  T R A N S A C T I O N    *")).$dashes;
    }
    elseif ($arg1 == "cancelled") {
        $receipt .= $dashes.self::centerString(_("*  T R A N S A C T I O N  C A N C E L L E D  *")).$dashes;
    }
    elseif ($arg1 == "resume") {
        $receipt .= $dashes.self::centerString(_("*    T R A N S A C T I O N  R E S U M E D    *")).$dashes
             .self::centerString("A complete receipt will be printed\n")
             .self::centerString("at the end of the transaction");
    }
    elseif ($arg1 == "suspended") {
        $receipt .= $dashes.self::centerString(_("*  T R A N S A C T I O N  S U S P E N D E D  *")).$dashes
                 .self::mostRecentReceipt();
    }

    return $receipt;
}

/** 
  Get per-member receipt messages
  @param $card_no [int] member number
  @return [array] receipt text
  Array keys are "any" and "print". 
 */
static public function memReceiptMessages($card_no)
{
    $dbc = Database::pDataConnect();
    $memQ = 'SELECT msg_text,modifier_module
          FROM custReceiptMessage
          WHERE card_no=' . ((int)$card_no) . '
          ORDER BY msg_text';
    // use newer CustomerNotifications table if present
    if (CoreLocal::get('NoCompat') == 1 || $dbc->tableExists('CustomerNotifications')) {
        $memQ = '
            SELECT message AS msg_text,
                modifierModule AS modifier_module
            FROM CustomerNotifications
            WHERE cardNo=' . ((int)$card_no) . '
                AND type=\'receipt\'
            ORDER BY message';
    }
    $memR = $dbc->query($memQ);
    $ret = array('any'=>'', 'print'=>'');
    while ($row = $dbc->fetch_row($memR)) {
        // EL This bit new for messages from plugins.
        $class_name = $row['modifier_module'];
        if (!empty($class_name) && class_exists('COREPOS\\pos\\lib\\ReceiptBuilding\\CustMessages\\' . $class_name)) {
            $class_name = 'COREPOS\\pos\\lib\\ReceiptBuilding\\CustMessages\\' . $class_name;
        }
        if (!empty($class_name) && class_exists($class_name)) {
            $obj = new $class_name();
            $obj->setPrintHandler(self::$PRINT_OBJ);
            $msg_text = $obj->message($row['msg_text']);
            if (is_array($msg_text)) {
                if (isset($msg_text['any'])) {
                    $ret['any'] .= $msg_text['any'];
                }
                if (isset($msg_text['print'])) {
                    $ret['print'] .= $msg_text['print'];
                }
            } else {
                $ret['any'] .= $msg_text;
            }
        } else {
            $ret['any'] .= $row['msg_text']."\n";
        }
    }

    return $ret;
}

/**
  get current receipt number
*/
static public function receiptNumber()
{
    return CoreLocal::get('CashierNo')
           . '-'
           . CoreLocal::get('laneno')
           . '-'
           . CoreLocal::get('transno');
}

/**
  Get most recent receipt number
*/
static public function mostRecentReceipt()
{
    $dbc = Database::tDataConnect();
    $query = "SELECT emp_no, register_no, trans_no
              FROM localtranstoday 
              ORDER BY datetime DESC";
    $query = $dbc->addSelectLimit($query, 1);
    $result = $dbc->query($query);
    if ($dbc->num_rows($result) == 0) {
        return false;
    } else {
        $row = $dbc->fetchRow($result);
        return $row['emp_no'] . '-' . $row['register_no'] . '-' . $row['trans_no'];
    }
}

static public function code39($barcode)
{
    if (!is_object(self::$PRINT_OBJ)) {
        self::$PRINT_OBJ = PrintHandler::factory(CoreLocal::get('ReceiptDriver'));
    }

    return self::$PRINT_OBJ->BarcodeCODE39($barcode);
}

static public function emailReceiptMod()
{
    if (class_exists('PHPMailer') && CoreLocal::get('emailReceiptHtml') != '' && class_exists(CoreLocal::get('emailReceiptHtml'))) {
        return self::$HTML_PH;
    } else {
        return self::$EMAIL_PH;
    }
}

}

