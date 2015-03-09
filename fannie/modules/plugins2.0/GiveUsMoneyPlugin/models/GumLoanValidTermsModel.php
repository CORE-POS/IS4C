<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class GumLoanValidTermsModel

  This table maintains a list of loan terms (lengths)
  that are available. Total principal limit is optional
  and means the co-op would like to take no more than
  $totalPrincipalLimit dollars in loans with that
  term. This can be useful for spreading out loans so
  they are not all coming due simultaneously.
*/
class GumLoanValidTermsModel extends BasicModel
{

    protected $name = "GumLoanValidTerms";

    protected $columns = array(
    'gumLoanValidTermID' => array('type'=>'INT', 'increment'=>true, 'index'=>true),
    'termInMonths' => array('type'=>'INT', 'primary_key'=>true),
    'totalPrincipalLimit' => array('type'=>'MONEY', 'default'=>0),
    );

    /* START ACCESSOR FUNCTIONS */

    public function gumLoanValidTermID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["gumLoanValidTermID"])) {
                return $this->instance["gumLoanValidTermID"];
            } else if (isset($this->columns["gumLoanValidTermID"]["default"])) {
                return $this->columns["gumLoanValidTermID"]["default"];
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
                'left' => 'gumLoanValidTermID',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["gumLoanValidTermID"]) || $this->instance["gumLoanValidTermID"] != func_get_args(0)) {
                if (!isset($this->columns["gumLoanValidTermID"]["ignore_updates"]) || $this->columns["gumLoanValidTermID"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["gumLoanValidTermID"] = func_get_arg(0);
        }
        return $this;
    }

    public function termInMonths()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["termInMonths"])) {
                return $this->instance["termInMonths"];
            } else if (isset($this->columns["termInMonths"]["default"])) {
                return $this->columns["termInMonths"]["default"];
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
                'left' => 'termInMonths',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["termInMonths"]) || $this->instance["termInMonths"] != func_get_args(0)) {
                if (!isset($this->columns["termInMonths"]["ignore_updates"]) || $this->columns["termInMonths"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["termInMonths"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalPrincipalLimit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalPrincipalLimit"])) {
                return $this->instance["totalPrincipalLimit"];
            } else if (isset($this->columns["totalPrincipalLimit"]["default"])) {
                return $this->columns["totalPrincipalLimit"]["default"];
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
                'left' => 'totalPrincipalLimit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalPrincipalLimit"]) || $this->instance["totalPrincipalLimit"] != func_get_args(0)) {
                if (!isset($this->columns["totalPrincipalLimit"]["ignore_updates"]) || $this->columns["totalPrincipalLimit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalPrincipalLimit"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

