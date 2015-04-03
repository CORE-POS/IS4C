<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
  @class MeminfoModel

*/

if (!class_exists('FannieDB')) {
    include(dirname(__FILE__).'/../../FannieDB.php');
}

class MeminfoModel extends BasicModel 
{

    protected $name = 'meminfo';

    protected $preferred_db = 'op';

    protected $columns = array(
    'card_no' => array('type'=>'INT','primary_key'=>True,'default'=>0),
    'last_name' => array('type'=>'VARCHAR(30)'),
    'first_name' => array('type'=>'VARCHAR(30)'),
    'othlast_name' => array('type'=>'VARCHAR(30)'),
    'othfirst_name' => array('type'=>'VARCHAR(30)'),
    'street' => array('type'=>'VARCHAR(255)'),
    'city' => array('type'=>'VARCHAR(20)'),
    'state' => array('type'=>'VARCHAR(2)'),
    'zip' => array('type'=>'VARCHAR(10)'),
    'phone' => array('type'=>'VARCHAR(30)'),
    'email_1' => array('type'=>'VARCHAR(50)'),
    'email_2' => array('type'=>'VARCHAR(50)'),
    'ads_OK' => array('type'=>'TINYINT','default'=>1),
    'modified'=>array('type'=>'DATETIME','ignore_updates'=>true),
    );

    public function save()
    {
        $stack = debug_backtrace();
        $lane_push = false;
        if (isset($stack[1]) && $stack[1]['function'] == 'pushToLanes') {
            $lane_push = true;
        }

        if ($this->record_changed && !$lane_push) {
            $this->modified(date('Y-m-d H:i:s'));
        }

        return parent::save();
    }

    public function doc()
    {
        return '
Table: meminfo

Columns:
    card_no int
    last_name varchar
    first_name varchar
    othlast_name varchar
    othfirst_name varchar
    street varchar
    city varchar
    state varchar
    zip varchar 10
    phone varchar
    email_1 varchar
    email_2 varchar
    ads_OK tinyint

Depends on:
    custdata (table)

Use:
This table has contact information for a member,
i.e. it extends custdata on card_no.
See also: memContact.

Usage doesn\'t have to match mine (AT). The member section of
fannie should be modular enough to support alternate
usage of some fields.

card_no key to custdata and other customer tables.

Straightforward:
- street varchar 255
- city
- state
- zip
- phone
  long enough to include extension but don\'t put more than
  one number in it.

The name fields are for two different people.
This approach will work if your co-op allows only
1 or 2 people per membership, but custdata can hold
the same information in a more future-proof way,
i.e. support any number of people per membership,
so better to not use them in favour of custdata.

- email_1 for email
- email_2 for second phone

- ads_OK EL: Perhaps: flag for whether OK to send ads.
  Don\'t know whether implemented for this or any purpose.
        ';
    }

    /**
      Use custdata to set initial change timestamps
    */
    public function hookAddColumnmodified()
    {
        if ($this->connection->dbmsName() == 'mssql') {
            $this->connection->query('
                UPDATE meminfo
                SET m.modified=c.LastChange
                FROM meminfo AS m
                    INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1');
        } else {
            $this->connection->query('
                UPDATE meminfo AS m
                    INNER JOIN custdata AS c ON m.card_no=c.CardNo AND c.personNum=1
                SET m.modified=c.LastChange');
        }
    }

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

    public function last_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["last_name"])) {
                return $this->instance["last_name"];
            } else if (isset($this->columns["last_name"]["default"])) {
                return $this->columns["last_name"]["default"];
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
                'left' => 'last_name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["last_name"]) || $this->instance["last_name"] != func_get_args(0)) {
                if (!isset($this->columns["last_name"]["ignore_updates"]) || $this->columns["last_name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["last_name"] = func_get_arg(0);
        }
        return $this;
    }

    public function first_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["first_name"])) {
                return $this->instance["first_name"];
            } else if (isset($this->columns["first_name"]["default"])) {
                return $this->columns["first_name"]["default"];
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
                'left' => 'first_name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["first_name"]) || $this->instance["first_name"] != func_get_args(0)) {
                if (!isset($this->columns["first_name"]["ignore_updates"]) || $this->columns["first_name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["first_name"] = func_get_arg(0);
        }
        return $this;
    }

    public function othlast_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["othlast_name"])) {
                return $this->instance["othlast_name"];
            } else if (isset($this->columns["othlast_name"]["default"])) {
                return $this->columns["othlast_name"]["default"];
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
                'left' => 'othlast_name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["othlast_name"]) || $this->instance["othlast_name"] != func_get_args(0)) {
                if (!isset($this->columns["othlast_name"]["ignore_updates"]) || $this->columns["othlast_name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["othlast_name"] = func_get_arg(0);
        }
        return $this;
    }

    public function othfirst_name()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["othfirst_name"])) {
                return $this->instance["othfirst_name"];
            } else if (isset($this->columns["othfirst_name"]["default"])) {
                return $this->columns["othfirst_name"]["default"];
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
                'left' => 'othfirst_name',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["othfirst_name"]) || $this->instance["othfirst_name"] != func_get_args(0)) {
                if (!isset($this->columns["othfirst_name"]["ignore_updates"]) || $this->columns["othfirst_name"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["othfirst_name"] = func_get_arg(0);
        }
        return $this;
    }

    public function street()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["street"])) {
                return $this->instance["street"];
            } else if (isset($this->columns["street"]["default"])) {
                return $this->columns["street"]["default"];
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
                'left' => 'street',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["street"]) || $this->instance["street"] != func_get_args(0)) {
                if (!isset($this->columns["street"]["ignore_updates"]) || $this->columns["street"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["street"] = func_get_arg(0);
        }
        return $this;
    }

    public function city()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["city"])) {
                return $this->instance["city"];
            } else if (isset($this->columns["city"]["default"])) {
                return $this->columns["city"]["default"];
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
                'left' => 'city',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["city"]) || $this->instance["city"] != func_get_args(0)) {
                if (!isset($this->columns["city"]["ignore_updates"]) || $this->columns["city"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["city"] = func_get_arg(0);
        }
        return $this;
    }

    public function state()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["state"])) {
                return $this->instance["state"];
            } else if (isset($this->columns["state"]["default"])) {
                return $this->columns["state"]["default"];
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
                'left' => 'state',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["state"]) || $this->instance["state"] != func_get_args(0)) {
                if (!isset($this->columns["state"]["ignore_updates"]) || $this->columns["state"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["state"] = func_get_arg(0);
        }
        return $this;
    }

    public function zip()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["zip"])) {
                return $this->instance["zip"];
            } else if (isset($this->columns["zip"]["default"])) {
                return $this->columns["zip"]["default"];
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
                'left' => 'zip',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["zip"]) || $this->instance["zip"] != func_get_args(0)) {
                if (!isset($this->columns["zip"]["ignore_updates"]) || $this->columns["zip"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["zip"] = func_get_arg(0);
        }
        return $this;
    }

    public function phone()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["phone"])) {
                return $this->instance["phone"];
            } else if (isset($this->columns["phone"]["default"])) {
                return $this->columns["phone"]["default"];
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
                'left' => 'phone',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["phone"]) || $this->instance["phone"] != func_get_args(0)) {
                if (!isset($this->columns["phone"]["ignore_updates"]) || $this->columns["phone"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["phone"] = func_get_arg(0);
        }
        return $this;
    }

    public function email_1()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["email_1"])) {
                return $this->instance["email_1"];
            } else if (isset($this->columns["email_1"]["default"])) {
                return $this->columns["email_1"]["default"];
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
                'left' => 'email_1',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["email_1"]) || $this->instance["email_1"] != func_get_args(0)) {
                if (!isset($this->columns["email_1"]["ignore_updates"]) || $this->columns["email_1"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["email_1"] = func_get_arg(0);
        }
        return $this;
    }

    public function email_2()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["email_2"])) {
                return $this->instance["email_2"];
            } else if (isset($this->columns["email_2"]["default"])) {
                return $this->columns["email_2"]["default"];
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
                'left' => 'email_2',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["email_2"]) || $this->instance["email_2"] != func_get_args(0)) {
                if (!isset($this->columns["email_2"]["ignore_updates"]) || $this->columns["email_2"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["email_2"] = func_get_arg(0);
        }
        return $this;
    }

    public function ads_OK()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ads_OK"])) {
                return $this->instance["ads_OK"];
            } else if (isset($this->columns["ads_OK"]["default"])) {
                return $this->columns["ads_OK"]["default"];
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
                'left' => 'ads_OK',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["ads_OK"]) || $this->instance["ads_OK"] != func_get_args(0)) {
                if (!isset($this->columns["ads_OK"]["ignore_updates"]) || $this->columns["ads_OK"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["ads_OK"] = func_get_arg(0);
        }
        return $this;
    }

    public function modified()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["modified"])) {
                return $this->instance["modified"];
            } else if (isset($this->columns["modified"]["default"])) {
                return $this->columns["modified"]["default"];
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
                'left' => 'modified',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["modified"]) || $this->instance["modified"] != func_get_args(0)) {
                if (!isset($this->columns["modified"]["ignore_updates"]) || $this->columns["modified"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["modified"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

