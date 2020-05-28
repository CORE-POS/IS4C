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

use COREPOS\Fannie\API\data\pipes\OutgoingEmail;

if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('wfcuRegistryModel')) {
    include(__DIR__ . '/wfcuRegistryModel.php');
}

class RegistryApply extends FannieRESTfulPage
{
    public function preprocess()
    {
        $this->__routes[] = 'get<json>';

        return parent::preprocess;
    }

    public function setJson($j)
    {
        $this->json = $j;
    }

    /**
      Update WFC-U Registry based on a JSON encoded array
    */
    public function get_json_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $json = json_decode(base64_decode($this->json), true);
        if (!is_array($json)) {
            echo 'Invalid data!';
            return false;
        } 
        $model = new wfcuRegistryModel($dbc);
        foreach ($json['upcs'] as $i => $upc) {
            $qty = $json['qtys'][$i];
            if (strlen($json['phone']) == 10) {
                $phone = $json['phone'];
                $phone = substr($phone, 0, 3) . '-'. substr($phone, 3, 3) . '-' . substr($phone, 6);
                $json['phone'] = $phone;
            }
            for ($i=$qty;$i>0;$i--) {
                $model->reset();
                $model->upc($upc);
                $id = $model->getFirstAvailSeat($upc);
                $model->id($id);
                $model->first_name(strtoupper($json['firstName']));
                $model->last_name(strtoupper($json['lastName']));
                $model->card_no($json['card_no']);
                $model->details($json['notes']);
                $model->email(strtoupper($json['email']));
                $model->phone($json['phone']);
                $model->payment($json['payment']);
                $model->save();
            }
        }
        $seats = $model->getNumSeatAvail($upc);
        $puModel = new ProductUserModel($dbc);
        $puModel->upc(str_pad($upc, 13, '0', STR_PAD_LEFT));
        $puModel->load();
        if ($seats == 0 && $puModel->soldOut() == 0) {
            $model->setSoldOut($upc);
            if (!OutgoingEmail::available()) {
                return false;
            } else {
                $mail = OutgoingEmail::get();
                $mail->From = 'automail@wholefoods.coop';
                $mail->FromName = 'WFC-U Class Registration Alerts';
                $mail->addAddress('it@wholefoods.coop');
                $mail->addAddress('brand@wholefoods.coop');
                $mail->Subject = 'WFC-U Class Registration Alert';
                $msg = "This class is full and has been removed from the online store.";
                $msg .= "\n";
                $msg .= "All seats have been filled.";
                $msg .= "\n";
                $msg .= "UPC for this class: $upc";
                $msg .= "\n";
                $msg .= "$className<br/>";
                $msg .= "\n";
                $mail->Body = strip_tags($msg);
                $ret = $mail->send();
            }
        }

        header('Location: WfcClassRegistryPage.php');

        return false;
    }

    public function send($msg,$to,$model)
    {
        $className = $model->class();

        return $ret ? true : false;
    }
} 
FannieDispatch::conditionalExec();
