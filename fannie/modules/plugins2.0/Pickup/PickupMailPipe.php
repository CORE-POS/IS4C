<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__).'/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('PickupOrdersModel')) {
    include(__DIR__ . '/models/PickupOrdersModel.php');
}

/**
  Extract JSON attachments from email and feed them
  into the PIApply page to trigger updates.
*/
class PickupMailPipe extends \COREPOS\Fannie\API\data\pipes\AttachmentEmailPipe
{
    public function processMail($msg)
    {
        /** extract valid mime types **/
        $mimes = array('application/json');
        $info = $this->parseEmail($msg);
        
        $boundary = $this->hasAttachments($info['headers']);
        if ($boundary) {
            $pieces = $this->extractAttachments($info['body'], $boundary);
            foreach ($pieces['attachments'] as $a) {
                if (!in_array($a['type'], $mimes)) {
                    continue;
                }
                $json = json_decode($a['content'], true);
                $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
                $orderP = $dbc->prepare("INSERT INTO PickupOrders
                    (name, phone, vehicle, pDate, pTime, notes, storeID, status)
                    VALUES (?, ?, ?, ?, ?, ?, 2, 'NEW')");
                list($hour, $minute) = explode(':', $json['pTime'], 2);
                $hour -= 12;
                $dbc->execute($orderP, array(
                    $json['name'],
                    $json['phone'],
                    $json['vehicle'],
                    $json['pDate'],
                    $hour . ':' . $minute . 'PM',
                    $json['notes'],
                ));
                $orderID = $dbc->insertID();

                $itemP = $dbc->prepare("INSERT INTO PickupOrderItems
                    (pickupOrderID, upc, description, quantity, total)
                    VALUES (?, ?, ?, ?, ?)");
                foreach ($json['items'] as $row) {
                    $dbc->execute($itemP, array(
                        $orderID,
                        $row['upc'],
                        $row['item'],
                        $row['qty'],
                        $row['ttl'],
                    ));
                }
            }
        }
    }
}

if (php_sapi_name() === 'cli' && basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    ini_set('error_log', '/tmp/pickup.err');
    $obj = new PickupMailPipe();
    $message = file_get_contents("php://stdin");
    if (!empty($message)) {
        $obj->processMail($message);
    }
} 

