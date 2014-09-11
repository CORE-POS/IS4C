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

/**
  @class MemberCardsModel

*/

if (!class_exists('FannieDB')) {
    include(dirname(__FILE__).'/../FannieDB.php');
}
if (!class_exists('BarcodeLib')) {
    include(dirname(__FILE__).'/../../lib/BarcodeLib.php');
}

class MemberCardsModel extends BasicModel 
{
    
    protected $name = 'memberCards';

    protected $preferred_db = 'op';
    
    protected $columns = array(
    'card_no' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'upc' => array('type'=>'VARCHAR(13)','primary_key'=>True,'default'=>'')
    );

    protected $unique = array('card_no');

    /* START ACCESSOR FUNCTIONS */

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } else if (isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'card_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["card_no"]) || $this->instance["card_no"] != func_get_args(0)) {
                if (!isset($this->columns["card_no"]["ignore_updates"]) || $this->columns["card_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["card_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function upc()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["upc"])) {
                return $this->instance["upc"];
            } else if (isset($this->columns["upc"]["default"])) {
                return $this->columns["upc"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'upc',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["upc"]) || $this->instance["upc"] != func_get_args(0)) {
                if (!isset($this->columns["upc"]["ignore_updates"]) || $this->columns["upc"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["upc"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */

    /**
      5Jul13 static stuff is legacy functionality
      that predates the BasicModel class.
      Can be removed when no calls to these functions
      remain in Fannie.
    
    /**
      Update memberCards record for an account
      @param $card_no the member number
      @param $upc the barcode
    */
    public static function update($card_no,$upc){
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
    
        $delP = $dbc->prepare_statement("DELETE FROM memberCards WHERE card_no=?");
        $delR = $dbc->exec_statement($delP,array($card_no));

        /** don't create entry w/o UPC */
        if ($upc != ''){
            $upc = BarcodeLib::padUPC($upc);
            $insP = $dbc->prepare_statement("INSERT INTO memberCards (card_no, upc)
                    VALUES (?, ?)");
            $insR = $dbc->exec_statement($insP,array($card_no,$upc));
            return $insR;
        }
        else return $delR;
    }

}

