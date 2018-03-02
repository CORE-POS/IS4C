<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

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

class alertRegFull {} // compat

if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {

    include(dirname(__FILE__).'/../../../../config.php');
    if (!class_exists('FannieAPI')) {
        include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
    }
    if (!class_exists('wfcuRegistryModel')) {
        include_once($FANNIE_ROOT.'modules/plugins2.0/WfcClassRegistry/wfcuRegistryModel.php');
    }

    $ret = '';
    $upc = FormLib::get('upc');
    $dbc = FannieDB::get($FANNIE_OP_DB);
    $args = array($upc);
    $prep = $dbc->prepare("
        SELECT MAX(seat) - SUM(CASE WHEN first_name IS NOT NULL AND seatType=1 THEN 1 ELSE 0 END) as seatsLeft,
            p.description,
            p.soldOut
        FROM wfcuRegistry AS r
            LEFT JOIN productUser AS p ON LPAD(r.upc,13,'0')=p.upc
            WHERE r.upc = ?;
    ");
    $res = $dbc->execute($prep,$args);
    while ($row = $dbc->fetchRow($res)) {
        $left = $row['seatsLeft'];
        $className = $row['description'];
        $soldOut = $row['soldOut'];
    }
    if ($left < 3 && $soldOut == 0) {
        sendEmail();
    }

    if ($er = $dbc->error()) {
        $ret['error'] = 1;
        $ret['error_msg'] = 'Registration-Full Alert Query Failed.';
    }
    echo json_encode($ret);
}

function sendEmail()
{
    $headers = array(
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=iso-8859-1',
        'from: automail@wholefoods.coop'
    );
    $to = $this->config->get('ADMIN_EMAIL');
    if (class_exists('PHPMailer')) {
        $msg = '';
        $msg .= '<h1>WFC-U Class'.$upc.': '.$className.'</h1>This class has only
            '.$left.' seats left. Please change the weblisting status of this item 
            to \"sold-out\"'; 
        $mail = new PHPMailer();
        $mail->isHTML();
        $mail->addAddress($to);
        $mail->From = 'automail@wholefoods.coop';
        $mail->FromName = 'CORE POS Monitoring';
        $mail->Subject = 'Report: In Use Task';
        $mail->Body = $msg;
        if (!$mail->send()) {
            $this->logger->error('Error emailing WFC-U Registration-Full notification');
        }  else {
            $msg = 'The WFC-U Registration-Full message could not be formatted. [Error] : class PHPMailer could not found.';
            mail($to,'Report: WFC-U Registration-Full',$msg,implode("\r\n",$headers));
        }
    }
}
