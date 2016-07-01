<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op

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

use COREPOS\pos\lib\AjaxCallback;
use COREPOS\pos\lib\MiscLib;

if (!class_exists('AutoLoader')) include_once(dirname(__FILE__).'/../../../lib/AutoLoader.php');

class AjaxPaycardAuth extends AjaxCallback
{
    protected $encoding = 'json';

    public function ajax($input=array())
    {
        // send the request
        $json = array();
        $pconf = new PaycardConf();
        $pluginInfo = new Paycards();
        $json['main_frame'] = $pluginInfo->pluginUrl().'/gui/paycardSuccess.php';
        $json['receipt'] = false;
        foreach ($pconf->get("RegisteredPaycardClasses") as $rpc) {
            $myObj = new $rpc();
            if ($myObj->handlesType($pconf->get("paycard_type"))){
                break;
            }
        }

        $result = $myObj->doSend($pconf->get("paycard_mode"));
        if ($result === PaycardLib::PAYCARD_ERR_OK){
            $pconf->wipePAN();
            $json = $myObj->cleanup($json);
            $pconf->set("strRemembered","");
            $pconf->set("msgrepeat",0);
        } elseif ($result === PaycardLib::PAYCARD_ERR_NSF_RETRY) {
            // card shows balance < requested amount
            // try again with lesser amount
            $json['main_frame'] = $pluginInfo->pluginUrl().'/gui/paycardboxMsgAuth.php';
        } else {
            $pconf->reset();
            $pconf->set("msgrepeat",0);
            $json['main_frame'] = MiscLib::baseURL().'gui-modules/boxMsg2.php';
        }

        return $json;
    }
}

AjaxPaycardAuth::run();

