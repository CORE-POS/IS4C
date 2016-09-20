<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
}

class PIApply extends FannieRESTfulPage 
{
    public function preprocess()
    {
        $this->__routes[] = 'get<id><email>';
        $this->__routes[] = 'get<json>';

        return parent::preprocess();
    }

    public function setJson($j)
    {
        $this->json = $j;
    }

    /**
      Update a member account based on a JSON encoded array
    */
    public function get_json_handler()
    {
        $json = json_decode(base64_decode($this->json), true);
        if (!is_array($json)) {
            echo 'Invalid data!';
            return false;
        } 
        $rest = array(
            'cardNo' => $json['card_no'],
            'addressFirstLine' => strtoupper($json['addr1']), 
            'addressSecondLine' => strtoupper($json['addr2']), 
            'city' => strtoupper($json['city']), 
            'state' => strtoupper($json['state']), 
            'zip' => $json['zip'], 
            'customers' => array(
                array(
                    'accountHolder' => 1,
                    'firstName' => strtoupper($json['fn']),
                    'lastName' => strtoupper($json['ln']),
                    'phone' => $json['ph'],
                    'email' => $json['email'],
                ),
            ),
        );
        foreach ($json['houseHold'] as $hh) {
            $rest['customers'][] = array(
                'accountHolder' => 0,
                'firstName' => strtoupper($hh[0]),
                'lastName' => strtoupper($hh[1]),
            );
        }
        \COREPOS\Fannie\API\member\MemberREST::post($json['card_no'], $rest);

        $custdata = new CustdataModel(FannieDB::get(FannieConfig::config('OP_DB')));
        $custdata->CardNo($json['card_no']);
        foreach ($custdata->find() as $c) {
            $c->pushToLanes();
        }
        header('Location: PIMemberPage.php?id=' . $json['card_no']);

        return false;
    }

    public function get_id_email_handler()
    {
        global $FANNIE_OP_DB;
        $mem = new MeminfoModel(FannieDB::get($FANNIE_OP_DB));
        $mem->card_no($this->id);
        $mem->email_1($this->email);
        $mem->save();

        header('Location: PIMemberPage.php?id=' . $this->id);

        return false;
    }

}

FannieDispatch::conditionalExec();

