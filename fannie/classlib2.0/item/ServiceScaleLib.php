<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

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

namespace COREPOS\Fannie\API\item;
use \FannieConfig;
use \FannieDB;
use \BarcodeLib;

class ServiceScaleLib 
{
    /* CSV fields for WriteOneItem & ChangeOneItem records
       Required does not mean you *have to* specify a value,
       but the default will be included if you omit that field.
       Non-required fields won't be sent to the scale at all
       unless specified by the caller
    */
    public static $WRITE_ITEM_FIELDS = array(
        'RecordType' => array('name'=>'Record Type', 'required'=>true, 'default'=>'ChangeOneItem'),
        'PLU' => array('name'=>'PLU Number', 'required'=>true, 'default'=>'0000'),
        'Description' => array('name'=>'Item Description', 'required'=>false, 'default'=>'', 'quoted'=>true),
        'ReportingClass' => array('name'=>'Reporting Class', 'required'=>true, 'default'=>'999999'),
        'Label' => array('name'=>'Label Type 01', 'required'=>false, 'default'=>'53'),
        'Tare' => array('name'=>'Tare 01', 'required'=>false, 'default'=>'0'),
        'ShelfLife' => array('name'=>'Shelf Life', 'required'=>false, 'default'=>'0'),
        'Price' => array('name'=>'Price', 'required'=>true, 'default'=>'0.00'),
        'ByCount' => array('name'=>'By Count', 'required'=>false, 'default'=>'0'),
        'Type' => array('name'=>'Item Type', 'required'=>true, 'default'=>'Random Weight'),
        'NetWeight' => array('name'=>'Net Weight', 'required'=>false, 'default'=>'0'),
        'Graphics' => array('name'=>'Graphics Number', 'required'=>false, 'default'=>'0'),
    );

    static public function sessionKey()
    {
        $session_key = '';
        for ($i = 0; $i < 20; $i++) {
            $num = rand(97,122);
            $session_key = $session_key . chr($num);
        }

        return $session_key;
    }

    static public function scaleOnline($host, $port=6000)
    {
        $soc = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($soc, SOL_SOCKET, SO_RCVTIMEO, array('sec'=>2, 'usec'=>0));
        socket_set_option($soc, SOL_SOCKET, SO_SNDTIMEO, array('sec'=>2, 'usec'=>0));
        if (@socket_connect($soc, $host, $port)) {
            socket_close($soc);
            return true;
        } else {
            return false;
        }
    }

    static public function upcToPLU($upc)
    {
        $upc = BarcodeLib::padUPC($upc);
        $len = FannieConfig::config('SPLU_LENGTH');
        if ($len != 4 && $len != 5) {
            $len = 4;
        }

        if ($len == 5) {
            preg_match("/^002(\d\d\d\d\d)/",$upc,$matches);
            $s_plu = $matches[1];
        } else {
            preg_match("/^002(\d\d\d\d)0/",$upc,$matches);
            $s_plu = $matches[1];
            if ($s_plu == '0000') {
                preg_match("/^0020(\d\d\d\d)/",$upc,$matches);
                $s_plu = $matches[1];
            }
        }

        return $s_plu;
    }

    static public function pluToUPC($plu)
    {
        $len = FannieConfig::config('SPLU_LENGTH');
        if ($len != 4 && $len != 5) {
            $len = 4;
        }
        $plu = str_pad($plu, $len, '0', STR_PAD_LEFT);
        $plu .= str_repeat('0', 10 - $len);

        return BarcodeLib::padUPC('2' . $plu);
    }

    /**
      Get attributes for a given label number
      @param $label_number [integer]
      @return keyed array
        - align => vertical or horizontal
        - fixed_weight => boolean
        - graphics => boolean
    */
    static public function labelToAttributes($label_number)
    {
        $ret = array(
            'align' => 'vertical',
            'fixed_weight' => false,
            'graphics' => false,
        );
        switch ($label_number) {
            case 23:
                $ret['fixed_weight'] = true;
                break;
            case 53:
                $ret['graphics'] = true;
                break;
            case 63:
                $ret['fixed_weight'] = true;
                $ret['align'] = 'horizontal';
                break;
            case 103:
                break;
            case 113:
                $ret['align'] = 'horizontal';
                break;
        }

        return $ret;
    }

    /**
      Get appropriate label number for given attributes
      @param $align [string] vertical or horizontal
      @param $fixed_weight [boolean, default false]
      @param $graphics [boolean, default false]
      @return [integer] label number
    */
    static public function attributesToLabel($align, $fixed_weight=false, $graphics=false)
    {
        if ($graphics) {
            return 53;
        }

        if ($align == 'horizontal') {
            return ($fixed_weight) ? 63 : 133;
        } else {
            return ($fixed_weight) ? 23 : 103;
        }
    }

    static public function getModelByHost($host)
    {
        $model = self::getSingletonModel();
        $model->host($host);
        $matches = $model->find();
        if (count($matches) > 0) {
            return $matches[0];
        } else {
            return false;
        }
    }

    static private $model = null;
    static private function getSingletonModel()
    {
        if (self::$model === null) {
            $model = new \ServiceScalesModel(FannieDB::get(FannieConfig::config('OP_DB')));
        }

        return $model;
    }

    static public function labelTranslate($label, $scale_type)
    {
        $dbc = FannieDB::get(FannieConfig::config('OP_DB'));
        $confP = $dbc->prepare("SELECT mappedType AS labelType, descriptionWidth, textWidth 
            FROM ScaleLabels WHERE scaleType=? AND labelType=?");
        $mapped = $dbc->getRow($confP, array($scale_type, $label));
        if ($mapped) {
            return $mapped;
        }
        
        if (substr(strtoupper($scale_type), 0, 3) == 'MT_') {
            return self::toledoLabel($label);
        }

        return array('labelType' => $label, 'descriptionWidth'=>26, 'textWidth'=>0);
    }

    static private function toledoLabel($label)
    {
        switch ($label) {
            case 53:
                return array('labelType' => 3, 'descriptionWidth'=>26, 'textWidth'=>0);
            case 23:
            case 63:
                return array('labelType' => 2, 'descriptionWidth'=>26, 'textWidth'=>0);
            case 103:
            case 133:
            default:
                return array('labelType' => 1, 'descriptionWidth'=>26, 'textWidth'=>0);
        }
    }
}

