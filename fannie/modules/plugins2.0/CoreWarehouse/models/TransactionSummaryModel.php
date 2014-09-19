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

class TransactionSummaryModel extends CoreWarehouseModel {

    protected $name = 'transactionSummary';
    
    protected $columns = array(
    'date_id' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'trans_num' => array('type'=>'VARCHAR(25)','primary_key'=>True,'default'=>''),
    'register_no' => array('type'=>'SMALLINT'),
    'emp_no' => array('type'=>'SMALLINT'),
    'tenderTotal' => array('type'=>'MONEY'),
    'taxTotal' => array('type'=>'MONEY'),
    'discountTotal' => array('type'=>'MONEY'),
    'percentDiscount' => array('type'=>'DOUBLE'),
    'retailTotal' => array('type'=>'MONEY'),
    'retailQty' => array('type'=>'DOUBLE'),
    'nonRetailTotal' => array('type'=>'MONEY'),
    'nonRetailQty' => array('type'=>'DOUBLE'),
    'ringCount' => array('type'=>'INT'),
    'start_time' => array('type'=>'DATETIME'),
    'end_time' => array('type'=>'DATETIME'),
    'duration' => array('type'=>'INT'),
    'card_no' => array('type'=>'INT','index'=>True),
    'memType' => array('type'=>'SMALLINT','index'=>True)
    );

    public function refresh_data($trans_db, $month, $year, $day=False){
        global $FANNIE_OP_DB;
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

        // 5Jul2013 - percentDiscount not currently exposed via dlog
        $sql = "INSERT INTO ".$this->name." 
            SELECT DATE_FORMAT(tdate, '%Y%m%d') as date_id,
            trans_num,
            register_no,
            emp_no,
            SUM(CASE WHEN trans_type='T' THEN total ELSE 0 END) as tenderTotal,
            SUM(CASE WHEN upc='TAX' THEN total ELSE 0 END) as taxTotal,
            SUM(CASE WHEN upc='DISCOUNT' THEN total ELSE 0 END) as discountTotal,
            0 as percentDiscount,
            SUM(CASE WHEN trans_type IN ('I','D') AND m.superID <> 0 THEN total else 0 END) as retailTotal,
            SUM(CASE WHEN trans_type IN ('I','D') AND m.superID <> 0 AND trans_status='M' THEN itemQtty 
                WHEN trans_type IN ('I','D') AND m.superID <> 0 AND unitPrice=0.01 THEN 1 
                WHEN trans_type IN ('I','D') AND m.superID <> 0 AND trans_status<>'M'
                AND unitPrice<>0.01 THEN quantity ELSE 0 END) as retailQty,
            SUM(CASE WHEN trans_type IN ('I','D') AND m.superID = 0 THEN total else 0 END) as retailTotal,
            SUM(CASE WHEN trans_type IN ('I','D') AND m.superID = 0 AND trans_status='M' THEN itemQtty 
                WHEN trans_type IN ('I','D') AND m.superID = 0 AND unitPrice=0.01 THEN 1 
                WHEN trans_type IN ('I','D') AND m.superID = 0 AND trans_status<>'M'
                AND unitPrice<>0.01 THEN quantity ELSE 0 END) as retailQty,
            SUM(CASE WHEN trans_type in ('I','D') THEN 1 ELSE 0 END) as ringCount,
            MIN(tdate) as start_time,
            MAX(tdate) as end_time, "
            .$this->connection->seconddiff('MIN(tdate)','MAX(tdate)')." as duration,
            MAX(card_no) as card_no,
            MAX(memType) as memType
            FROM $target_table as t LEFT JOIN "
            .$FANNIE_OP_DB.$this->connection->sep()."MasterSuperDepts as m
            ON t.department=m.dept_ID
            WHERE tdate BETWEEN ? AND ? AND upc <> 'RRR'
            GROUP BY DATE_FORMAT(tdate,'%Y%m%d'), trans_num";
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

    public function trans_num()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["trans_num"])) {
                return $this->instance["trans_num"];
            } else if (isset($this->columns["trans_num"]["default"])) {
                return $this->columns["trans_num"]["default"];
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
                'left' => 'trans_num',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["trans_num"]) || $this->instance["trans_num"] != func_get_args(0)) {
                if (!isset($this->columns["trans_num"]["ignore_updates"]) || $this->columns["trans_num"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["trans_num"] = func_get_arg(0);
        }
        return $this;
    }

    public function register_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["register_no"])) {
                return $this->instance["register_no"];
            } else if (isset($this->columns["register_no"]["default"])) {
                return $this->columns["register_no"]["default"];
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
                'left' => 'register_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["register_no"]) || $this->instance["register_no"] != func_get_args(0)) {
                if (!isset($this->columns["register_no"]["ignore_updates"]) || $this->columns["register_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["register_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function emp_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["emp_no"])) {
                return $this->instance["emp_no"];
            } else if (isset($this->columns["emp_no"]["default"])) {
                return $this->columns["emp_no"]["default"];
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
                'left' => 'emp_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["emp_no"]) || $this->instance["emp_no"] != func_get_args(0)) {
                if (!isset($this->columns["emp_no"]["ignore_updates"]) || $this->columns["emp_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["emp_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function tenderTotal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["tenderTotal"])) {
                return $this->instance["tenderTotal"];
            } else if (isset($this->columns["tenderTotal"]["default"])) {
                return $this->columns["tenderTotal"]["default"];
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
                'left' => 'tenderTotal',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["tenderTotal"]) || $this->instance["tenderTotal"] != func_get_args(0)) {
                if (!isset($this->columns["tenderTotal"]["ignore_updates"]) || $this->columns["tenderTotal"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["tenderTotal"] = func_get_arg(0);
        }
        return $this;
    }

    public function taxTotal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["taxTotal"])) {
                return $this->instance["taxTotal"];
            } else if (isset($this->columns["taxTotal"]["default"])) {
                return $this->columns["taxTotal"]["default"];
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
                'left' => 'taxTotal',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["taxTotal"]) || $this->instance["taxTotal"] != func_get_args(0)) {
                if (!isset($this->columns["taxTotal"]["ignore_updates"]) || $this->columns["taxTotal"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["taxTotal"] = func_get_arg(0);
        }
        return $this;
    }

    public function discountTotal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["discountTotal"])) {
                return $this->instance["discountTotal"];
            } else if (isset($this->columns["discountTotal"]["default"])) {
                return $this->columns["discountTotal"]["default"];
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
                'left' => 'discountTotal',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["discountTotal"]) || $this->instance["discountTotal"] != func_get_args(0)) {
                if (!isset($this->columns["discountTotal"]["ignore_updates"]) || $this->columns["discountTotal"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["discountTotal"] = func_get_arg(0);
        }
        return $this;
    }

    public function percentDiscount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["percentDiscount"])) {
                return $this->instance["percentDiscount"];
            } else if (isset($this->columns["percentDiscount"]["default"])) {
                return $this->columns["percentDiscount"]["default"];
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
                'left' => 'percentDiscount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["percentDiscount"]) || $this->instance["percentDiscount"] != func_get_args(0)) {
                if (!isset($this->columns["percentDiscount"]["ignore_updates"]) || $this->columns["percentDiscount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["percentDiscount"] = func_get_arg(0);
        }
        return $this;
    }

    public function retailTotal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["retailTotal"])) {
                return $this->instance["retailTotal"];
            } else if (isset($this->columns["retailTotal"]["default"])) {
                return $this->columns["retailTotal"]["default"];
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
                'left' => 'retailTotal',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["retailTotal"]) || $this->instance["retailTotal"] != func_get_args(0)) {
                if (!isset($this->columns["retailTotal"]["ignore_updates"]) || $this->columns["retailTotal"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["retailTotal"] = func_get_arg(0);
        }
        return $this;
    }

    public function retailQty()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["retailQty"])) {
                return $this->instance["retailQty"];
            } else if (isset($this->columns["retailQty"]["default"])) {
                return $this->columns["retailQty"]["default"];
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
                'left' => 'retailQty',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["retailQty"]) || $this->instance["retailQty"] != func_get_args(0)) {
                if (!isset($this->columns["retailQty"]["ignore_updates"]) || $this->columns["retailQty"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["retailQty"] = func_get_arg(0);
        }
        return $this;
    }

    public function nonRetailTotal()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["nonRetailTotal"])) {
                return $this->instance["nonRetailTotal"];
            } else if (isset($this->columns["nonRetailTotal"]["default"])) {
                return $this->columns["nonRetailTotal"]["default"];
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
                'left' => 'nonRetailTotal',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["nonRetailTotal"]) || $this->instance["nonRetailTotal"] != func_get_args(0)) {
                if (!isset($this->columns["nonRetailTotal"]["ignore_updates"]) || $this->columns["nonRetailTotal"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["nonRetailTotal"] = func_get_arg(0);
        }
        return $this;
    }

    public function nonRetailQty()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["nonRetailQty"])) {
                return $this->instance["nonRetailQty"];
            } else if (isset($this->columns["nonRetailQty"]["default"])) {
                return $this->columns["nonRetailQty"]["default"];
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
                'left' => 'nonRetailQty',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["nonRetailQty"]) || $this->instance["nonRetailQty"] != func_get_args(0)) {
                if (!isset($this->columns["nonRetailQty"]["ignore_updates"]) || $this->columns["nonRetailQty"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["nonRetailQty"] = func_get_arg(0);
        }
        return $this;
    }

    public function ringCount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ringCount"])) {
                return $this->instance["ringCount"];
            } else if (isset($this->columns["ringCount"]["default"])) {
                return $this->columns["ringCount"]["default"];
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
                'left' => 'ringCount',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ringCount"]) || $this->instance["ringCount"] != func_get_args(0)) {
                if (!isset($this->columns["ringCount"]["ignore_updates"]) || $this->columns["ringCount"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ringCount"] = func_get_arg(0);
        }
        return $this;
    }

    public function start_time()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["start_time"])) {
                return $this->instance["start_time"];
            } else if (isset($this->columns["start_time"]["default"])) {
                return $this->columns["start_time"]["default"];
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
                'left' => 'start_time',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["start_time"]) || $this->instance["start_time"] != func_get_args(0)) {
                if (!isset($this->columns["start_time"]["ignore_updates"]) || $this->columns["start_time"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["start_time"] = func_get_arg(0);
        }
        return $this;
    }

    public function end_time()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["end_time"])) {
                return $this->instance["end_time"];
            } else if (isset($this->columns["end_time"]["default"])) {
                return $this->columns["end_time"]["default"];
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
                'left' => 'end_time',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["end_time"]) || $this->instance["end_time"] != func_get_args(0)) {
                if (!isset($this->columns["end_time"]["ignore_updates"]) || $this->columns["end_time"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["end_time"] = func_get_arg(0);
        }
        return $this;
    }

    public function duration()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["duration"])) {
                return $this->instance["duration"];
            } else if (isset($this->columns["duration"]["default"])) {
                return $this->columns["duration"]["default"];
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
                'left' => 'duration',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["duration"]) || $this->instance["duration"] != func_get_args(0)) {
                if (!isset($this->columns["duration"]["ignore_updates"]) || $this->columns["duration"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["duration"] = func_get_arg(0);
        }
        return $this;
    }

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

    public function memType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["memType"])) {
                return $this->instance["memType"];
            } else if (isset($this->columns["memType"]["default"])) {
                return $this->columns["memType"]["default"];
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
                'left' => 'memType',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["memType"]) || $this->instance["memType"] != func_get_args(0)) {
                if (!isset($this->columns["memType"]["ignore_updates"]) || $this->columns["memType"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["memType"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}
