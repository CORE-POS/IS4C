<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

/**
  @class AuditLib
*/
class AuditLib
{

    const BATCH_ADD =       1;
    const BATCH_EDIT =      2;
    const BATCH_DELETE =    3;

    /**
      Send email notification that item has been updated
      @param $upc [string] upc value or like code number
      @param $is_likecode [boolean] upc param is a like code
      @return [boolean] success/failure
    */
    public static function itemUpdate($upc, $likecode=false)
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $product = new ProductsModel($dbc);
        $product->upc($upc);
        $product->load();
        $desc = $product->description();

        $subject = "Item Update notification: ".$upc;

        $message = "Item $upc ($desc) has been changed\n";  
        $message .= "Price: " . $product->normal_price() . "\n";
        $taxQ = $dbc->prepare_statement('SELECT description FROM taxrates WHERE id=?');
        $taxR = $dbc->execute($taxQ, array($product->tax()));
        $taxname = 'No Tax';
        if ($dbc->num_rows($taxR) > 0) {
            $taxW = $dbc->fetch_row($taxR);
            $taxname = $taxW['description'];
        }
        $message .= "Tax: " . $taxname . "\n";
        $message .= "Foodstampable: " . ($product->foodstamp()==1 ? "Yes" : "No") . "\n";
        $message .= "Scale: " . ($product->scale()==1 ? "Yes" :"No") . "\n";
        $message .= "Discountable: " . ($product->discount()==1 ? "Yes" : "No") . "\n";
        if ($likecode) {
            $message .= "All items in this likecode ($likecode) were changed\n";
        }
        $message .= "\n";
        $message .= "Adjust this item?\n";
        $message .= "http://{$_SERVER['SERVER_NAME']}/{$FANNIE_URL}item/ItemEditorPage.php?searchupc=$upc\n";
        $message .= "\n";
        $username = FannieAuth::checkLogin();
        if (!$username) {
            $username = 'unknown';
        }
        $message .= "This change was made by user $username\n";

        $from = "From: automail\r\n";
        $to = self::getAddresses($product->department());
        if ($to === false) {
            // no one set to receive notices
            return false;
        }
        mail($to, $subject, $message, $from);

        return true;
    }

    static public function batchNotification($batchID, $upc, $type, $is_likecode=false)
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $lc = '';
        $desc = '';
        $dept = 0;
        if ($is_likecode) {
            if (substr($upc, 0, 2) == 'LC') {
                $lc = substr($upc, 2); 
            } else {
                $lc = $upc;
            }
            // upc is a like code. find the description
            // and a valid upc (hence inner join)
            $infoQ = 'SELECT p.department,
                        l.likeCodeDesc 
                      FROM upcLike AS u
                        INNER JOIN products AS p ON u.upc=p.upc
                        LEFT JOIN likeCodes AS l ON u.likeCode=l.likeCode
                      WHERE u.likeCode=?';
            $infoP = $dbc->prepare($infoQ);
            $infoR = $dbc->execute($infoP, array($lc));
            if ($dbc->num_rows($infoR) == 0) {
                // invalid like code
                return false;
            }
            $infoW = $dbc->fetch_row($infoR);
            $desc = $infoW['likeCodeDesc'];
            $dept = $infoW['department'];
        } else {
            $product = new ProductsModel($dbc);
            $product->upc($upc);
            $product->load();
            $desc = $product->description();
            $dept = $product->department();
        }

        $to = self::getAddresses($dept);
        if ($to === false) {
            // no one set to receive notices
            return false;
        }

        $batch = new BatchesModel($dbc);
        $batch->batchID($batchID);
        $batch->load();

        $batchList = new BatchListModel($dbc);
        $batchList->upc($upc);
        $batchList->batchID($batchID);
        $batchList->load();

        $subject = "Batch Update notification: " . $batch->batchName();

        $message = "Batch " . $batch->batchName() . " has been changed\n";
        if ($is_likecode) {
            $message .= 'Like code ' . $lc . '(' . $desc . ') ';
        } else {
            $message .= 'Item '. $upc . '(' . $desc . ') ';
        }
        switch($type) {
            case self::BATCH_ADD:
                $message .= "has been added to the batch\n";
                $message .= 'Sale Price: $' . $batchList->salePrice() . "\n";
                break;
            case self::BATCH_EDIT:
                $message .= "has been re-priced\n";
                $message .= 'Sale Price: $' . $batchList->salePrice() . "\n";
                break;
            case self::BATCH_DELETE:
                $message .= "has been deleted from the batch\n";
                break;
            default:
                $message .= "may have experienced unknown changes\n";
                return false; // remove after testing; don't send lots of these in error
                break;
        }

        $message .= "\n";
        $message .= "Go to the batch page:\n";
        $message .= "http://{$_SERVER['SERVER_NAME']}{$FANNIE_URL}batches/newbatch/\n";
        $message .= "\n";
        $username = FannieAuth::checkLogin();
        if (!$username) {
            $username = 'unknown';
        }
        $message .= "This change was made by user $username\n";

        $from = "From: automail\r\n";
        mail($to, $subject, $message, $from);

        return true;
    }

    /**
      Get all email addresses associated with
      the given department
      @param $dept [int] department number
      @return [string] email address(es) or [boolean] false
    */
    public static function getAddresses($dept)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $query = 'SELECT superID from superdepts WHERE dept_ID=? GROUP BY superID';
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($dept));
        $emails = '';
        while($row = $dbc->fetch_row($res)) {
            $model = new SuperDeptEmailsModel($dbc);
            $model->superID($row['superID']);
            if (!$model->load()) {
                continue;
            }
            $addr = $model->emailAddress();
            if (!strstr($emails, $addr)) {
                if ($emails !== '') {
                    $emails .= ', ';
                }
                $emails .= $addr;
            }
        }

        return ($emails === '') ? false : $emails;
    }
}

