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
  @class MemDatesModel

*/

if (!class_exists('FannieDB')) {
    include(dirname(__FILE__).'/../FannieDB.php');
}

class MemDatesModel extends BasicModel 
{

    protected $name = 'memDates';

    protected $preferred_db = 'op';

    protected $columns = array(
    'card_no' => array('type'=>'INT','primary_key'=>True),
    'start_date'=>array('type'=>'DATETIME'),
    'end_date'=>array('type'=>'DATETIME')    
    );

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

    public function start_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["start_date"])) {
                return $this->instance["start_date"];
            } else if (isset($this->columns["start_date"]["default"])) {
                return $this->columns["start_date"]["default"];
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
                'left' => 'start_date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["start_date"]) || $this->instance["start_date"] != func_get_args(0)) {
                if (!isset($this->columns["start_date"]["ignore_updates"]) || $this->columns["start_date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["start_date"] = func_get_arg(0);
        }
        return $this;
    }

    public function end_date()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["end_date"])) {
                return $this->instance["end_date"];
            } else if (isset($this->columns["end_date"]["default"])) {
                return $this->columns["end_date"]["default"];
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
                'left' => 'end_date',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["end_date"]) || $this->instance["end_date"] != func_get_args(0)) {
                if (!isset($this->columns["end_date"]["ignore_updates"]) || $this->columns["end_date"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["end_date"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */

    /**
      5Jul13 static stuff is legacy functionality
      that predates the BasicModel class.
      Can be removed when no calls to these functions
      remain in Fannie.
    */
    
    /**
      Update memDates record for an account
      @param $card_no the member number
      @param $start the starting date
      @param $end the ending date
    */
    public static function update($card_no,$start,$end)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        self::initRecord($dbc,$card_no);

        $upP = $dbc->prepare_statement("UPDATE memDates SET start_date=?,
                end_date=? WHERE card_no=?");
        $upR = $dbc->exec_statement($upP, array($start,$end,$card_no));

        return $upR;
    }

    private static function initRecord($dbc,$card_no)
    {
        $q = $dbc->prepare_statement("SELECT card_no FROM memDates WHERE card_no=?");
        $r = $dbc->exec_statement($q,array($card_no));

        if ($dbc->num_rows($r) == 0) {
            $ins = $dbc->prepare_statement("INSERT INTO memDates (card_no,
                start_date,end_date) VALUES (?, NULL, NULL)");
            $dbc->exec_statement($ins,array($card_no));
        }
    }

}

