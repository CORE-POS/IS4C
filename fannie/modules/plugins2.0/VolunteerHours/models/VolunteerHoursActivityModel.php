<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

/**
  @class VolunteerHoursActivityModel
*/
class VolunteerHoursActivityModel extends BasicModel
{

    protected $name = "VolunteerHoursActivity";
    protected $preferred_db = 'plugin:VolunteerHoursDB';

    protected $columns = array(
    'volunteerHoursActivityID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'tdate' => array('type'=>'DATETIME'),
    'cardNo' => array('type'=>'INT', 'index'=>true),
    'hoursWorked' => array('type'=>'DOUBLE'),
    'uid' => array('type'=>'INT'),
    'hoursRedeemed' => array('type'=>'DOUBLE'),
    'transNum' => array('type'=>'VARCHAR(15)'),
    );

    /* START ACCESSOR FUNCTIONS */

    public function volunteerHoursActivityID()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('volunteerHoursActivityID');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('volunteerHoursActivityID', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('volunteerHoursActivityID', func_get_arg(0));
        }
        return $this;
    }

    public function tdate()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('tdate');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('tdate', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('tdate', func_get_arg(0));
        }
        return $this;
    }

    public function cardNo()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('cardNo');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('cardNo', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('cardNo', func_get_arg(0));
        }
        return $this;
    }

    public function hoursWorked()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('hoursWorked');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('hoursWorked', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('hoursWorked', func_get_arg(0));
        }
        return $this;
    }

    public function uid()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('uid');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('uid', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('uid', func_get_arg(0));
        }
        return $this;
    }

    public function hoursRedeemed()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('hoursRedeemed');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('hoursRedeemed', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('hoursRedeemed', func_get_arg(0));
        }
        return $this;
    }

    public function transNum()
    {
        if(func_num_args() == 0) {
            return $this->getColumn('transNum');
        } else if (func_num_args() > 1) {
            $literal = (func_num_args() > 2 && func_get_arg(2) === true) ? true : false;
            $this->filterColumn('transNum', func_get_arg(0), func_get_arg(1), $literal);
        } else {
            $this->setColumn('transNum', func_get_arg(0));
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

