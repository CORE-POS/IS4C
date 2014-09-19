<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

global $FANNIE_ROOT;
if (!class_exists('CoreWarehouseModel'))
    include_once(dirname(__FILE__).'/CoreWarehouseModel.php');

class SumUpcSalesByDayModel extends CoreWarehouseModel {

    protected $name = 'sumUpcSalesByDay';
    
    protected $columns = array(
    'date_id' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'upc' => array('type'=>'VARCHAR(13)','primary_key'=>True,'default'=>''),
    'total' => array('type'=>'MONEY','default'=>0.00),
    'quantity' => array('type'=>'DOUBLE','default'=>0.00)
    );

    public function refresh_data($trans_db, $month, $year, $day=False){
        $start_id = date('Ymd',mktime(0,0,0,$month,1,$year));
        $start_date = date('Y-m-d',mktime(0,0,0,$month,1,$year));
        $end_id = date('Ymt',mktime(0,0,0,$month,1,$year));
        $end_date = date('Y-m-t',mktime(0,0,0,$month,1,$year));
        if ($day !== False){
            $start_id = date('Ymd',mktime(0,0,0,$month,$day,$year));
            $start_date = date('Y-m-d',mktime(0,0,0,$month,$day,$year));
            $end_id = $start_id;
            $end_date = $start_date;
        }

        $target_table = DTransactionsModel::selectDlog($start_date, $end_date);

        /* clear old entries */
        $sql = 'DELETE FROM '.$this->name.' WHERE date_id BETWEEN ? AND ?';
        $prep = $this->connection->prepare_statement($sql);
        $result = $this->connection->exec_statement($prep, array($start_id, $end_id));

        /* reload table from transarction archives */
        $sql = "INSERT INTO ".$this->name."
            SELECT DATE_FORMAT(tdate, '%Y%m%d') as date_id,
            upc,
            CONVERT(SUM(total),DECIMAL(10,2)) as total,
            CONVERT(SUM(CASE WHEN trans_status='M' THEN itemQtty 
                WHEN unitPrice=0.01 THEN 1 ELSE quantity END),DECIMAL(10,2)) as quantity
            FROM $target_table WHERE
            tdate BETWEEN ? AND ? AND
            trans_type IN ('I') AND upc <> '0'
            GROUP BY DATE_FORMAT(tdate,'%Y%m%d'), upc";
        $prep = $this->connection->prepare_statement($sql);
        $result = $this->connection->exec_statement($prep, array($start_date.' 00:00:00',$end_date.' 23:59:59'));
    }

    /* START ACCESSOR FUNCTIONS */

    public function date_id()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["date_id"])) {
                return $this->instance["date_id"];
            } else if (isset($this->columns["date_id"]["default"])) {
                return $this->columns["date_id"]["default"];
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
                'left' => 'date_id',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["date_id"]) || $this->instance["date_id"] != func_get_args(0)) {
                if (!isset($this->columns["date_id"]["ignore_updates"]) || $this->columns["date_id"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["date_id"] = func_get_arg(0);
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

    public function total()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["total"])) {
                return $this->instance["total"];
            } else if (isset($this->columns["total"]["default"])) {
                return $this->columns["total"]["default"];
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
                'left' => 'total',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["total"]) || $this->instance["total"] != func_get_args(0)) {
                if (!isset($this->columns["total"]["ignore_updates"]) || $this->columns["total"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["total"] = func_get_arg(0);
        }
        return $this;
    }

    public function quantity()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["quantity"])) {
                return $this->instance["quantity"];
            } else if (isset($this->columns["quantity"]["default"])) {
                return $this->columns["quantity"]["default"];
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
                'left' => 'quantity',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["quantity"]) || $this->instance["quantity"] != func_get_args(0)) {
                if (!isset($this->columns["quantity"]["ignore_updates"]) || $this->columns["quantity"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["quantity"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}
