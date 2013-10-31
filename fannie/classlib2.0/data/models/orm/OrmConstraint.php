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
  @class OrmConstraint
*/
class OrmConstraint {

    private $pieces = array();

    public function export(){
        $sql = '( ';
        $args = array();
        foreach($this->pieces as $p){
            $sql .= $p['sql'].' AND ';
            $args += $args;
        }
        if (substr($sql,-4) == 'AND '){
            $sql = substr($sql,0,strlen($sql)-4).')';
        }
        return array('sql'=>$sql,'args'=>$args);
    }

    public function _or($c1, $c2){
        $left = $c1->export();
        $right = $c2->export();    
        $sql = $left['sql'].' OR '.$right['sql'];
        $args = $left['args'] + $right['args'];
        $this->pieces[] =  array('sql'=>$sql,'args'=>$args);
    }

    public function ne($model, $op1, $op2){
        $sql = ' ';
        $args = array();
        if (method_exists($model, $op1))
            $sql .= $model->db()->identifier_escape($op1);
        else {
            $sql .= '?';
            $args[] = $op1;    
        }
        $sql .= ' <> ';
        if (method_exists($model, $op2))
            $sql .= $model->db()->identifier_escape($op2);
        else {
            $sql .= '?';
            $args[] = $op2;    
        }
        $sql .= ' ';
        $this->pieces[] = array('sql'=>$sql,'args'=>$args);
    }

    public function gt($model, $op1, $op2){
        $sql = ' ';
        $args = array();
        if (method_exists($model, $op1))
            $sql .= $model->db()->identifier_escape($op1);
        else {
            $sql .= '?';
            $args[] = $op1;    
        }
        $sql .= ' > ';
        if (method_exists($model, $op2))
            $sql .= $model->db()->identifier_escape($op2);
        else {
            $sql .= '?';
            $args[] = $op2;    
        }
        $sql .= ' ';
        $this->pieces[] = array('sql'=>$sql,'args'=>$args);
    }

    public function gte($model, $op1, $op2){
        $sql = ' ';
        $args = array();
        if (method_exists($model, $op1))
            $sql .= $model->db()->identifier_escape($op1);
        else {
            $sql .= '?';
            $args[] = $op1;    
        }
        $sql .= ' >= ';
        if (method_exists($model, $op2))
            $sql .= $model->db()->identifier_escape($op2);
        else {
            $sql .= '?';
            $args[] = $op2;    
        }
        $sql .= ' ';
        $this->pieces[] = array('sql'=>$sql,'args'=>$args);
    }

    public function lt($model, $op1, $op2){
        $sql = ' ';
        $args = array();
        if (method_exists($model, $op1))
            $sql .= $model->db()->identifier_escape($op1);
        else {
            $sql .= '?';
            $args[] = $op1;    
        }
        $sql .= ' < ';
        if (method_exists($model, $op2))
            $sql .= $model->db()->identifier_escape($op2);
        else {
            $sql .= '?';
            $args[] = $op2;    
        }
        $sql .= ' ';
        $this->pieces[] = array('sql'=>$sql,'args'=>$args);
    }

    public function lte($model, $op1, $op2){
        $sql = ' ';
        $args = array();
        if (method_exists($model, $op1))
            $sql .= $model->db()->identifier_escape($op1);
        else {
            $sql .= '?';
            $args[] = $op1;    
        }
        $sql .= ' <= ';
        if (method_exists($model, $op2))
            $sql .= $model->db()->identifier_escape($op2);
        else {
            $sql .= '?';
            $args[] = $op2;    
        }
        $sql .= ' ';
        $this->pieces[] = array('sql'=>$sql,'args'=>$args);
    }

    public function between($model, $op1, $op2, $op3){
        $sql = ' ';
        $args = array();
        if (method_exists($model, $op1))
            $sql .= $model->db()->identifier_escape($op1);
        else {
            $sql .= '?';
            $args[] = $op1;    
        }
        $sql .= ' BETWEEN ';
        if (method_exists($model, $op2))
            $sql .= $model->db()->identifier_escape($op2);
        else {
            $sql .= '?';
            $args[] = $op2;    
        }
        $sql .= ' AND ';
        if (method_exists($model, $op3))
            $sql .= $model->db()->identifier_escape($op3);
        else {
            $sql .= '?';
            $args[] = $op3;    
        }
        $this->pieces[] = array('sql'=>$sql,'args'=>$args);
    }

    function in(){
        if(func_num_args() < 3) return;
        $model = func_get_arg(0);
        if(!is_object($model)) return;
        $sql = ' ';
        $args = array();
        $op1 = func_get_arg(1);
        if (method_exists($model, $op1))
            $sql .= $model->db()->identifier_escape($op1);
        else {
            $sql .= '?';
            $args[] = $op1;    
        }
        $sql .= ' IN (';
        for($i=2;$i<func_num_args();$i++){
            $op = func_get_arg($i);
            if (method_exists($model, $op))
                $sql .= $model->db()->identifier_escape($op);
            else {
                $sql .= '?';
                $args[] = $op;    
            }
            $sql .= ',';
        }
        $sql = substr($sql,0,strlen($sql)-1).')';
        $this->pieces[] = array('sql'=>$sql,'args'=>$args);
    }

}
