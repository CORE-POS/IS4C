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

class TendersModel extends BasicModel 
{

    protected $name = 'tenders';

    protected $preferred_db = 'op';

    protected $columns = array(
    'TenderID'    => array('type'=>'SMALLINT','primary_key'=>True),
    'TenderCode'    => array('type'=>'VARCHAR(2)','index'=>True),
    'TenderName'    => array('type'=>'VARCHAR(25)'),    
    'TenderType'    => array('type'=>'VARCHAR(2)'),    
    'ChangeMessage'    => array('type'=>'VARCHAR(25)'),
    'MinAmount'    => array('type'=>'MONEY','default'=>0.01),
    'MaxAmount'    => array('type'=>'MONEY','default'=>1000.00),
    'MaxRefund'    => array('type'=>'MONEY','default'=>1000.00),
    'TenderModule' => array('type'=>'VARCHAR(50)', 'default'=>"'TenderModule'"),
    );

    public function doc()
    {
        return '
Use:
List of tenders IT CORE accepts. TenderCode
should be unique; it\'s what cashiers type in
at the register as well as the identifier that
eventually shows up in transaction logs.

ChangeMessage, MinAmount, MaxAmount, and
MaxRefund all do exactly what they sound like.

TenderName shows up at the register screen
and on various reports.

TenderType and TenderID are mostly ignored.
        ';
    }

    public function getMap()
    {
        $prep = $this->connection->prepare('
            SELECT TenderCode,
                TenderModule
            FROM tenders
            WHERE TenderModule <> \'TenderModule\'');
        $result = $this->connection->execute($prep);
        $map = array();
        while ($w = $this->connection->fetch_row($result)) {
            $map[$w['TenderCode']] = $w['TenderModule'];
        }

        return $map;
    }

    public function hookAddColumnTenderModule()
    {
        CoreLocal::refresh();
        CoreState::loadParams();
        $current_map = CoreLocal::get('TenderMap');
        $update = $this->connection->prepare('
            UPDATE tenders
            SET TenderModule=?
            WHERE TenderCode=?
        ');
        foreach ($current_map as $code => $module) {
            $this->connection->execute($update, array($module, $code));
        }
    }

    /* START ACCESSOR FUNCTIONS */

    public function TenderID()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TenderID"])) {
                return $this->instance["TenderID"];
            } elseif(isset($this->columns["TenderID"]["default"])) {
                return $this->columns["TenderID"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["TenderID"] = func_get_arg(0);
        }
    }

    public function TenderCode()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TenderCode"])) {
                return $this->instance["TenderCode"];
            } elseif(isset($this->columns["TenderCode"]["default"])) {
                return $this->columns["TenderCode"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["TenderCode"] = func_get_arg(0);
        }
    }

    public function TenderName()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TenderName"])) {
                return $this->instance["TenderName"];
            } elseif(isset($this->columns["TenderName"]["default"])) {
                return $this->columns["TenderName"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["TenderName"] = func_get_arg(0);
        }
    }

    public function TenderType()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TenderType"])) {
                return $this->instance["TenderType"];
            } elseif(isset($this->columns["TenderType"]["default"])) {
                return $this->columns["TenderType"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["TenderType"] = func_get_arg(0);
        }
    }

    public function ChangeMessage()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["ChangeMessage"])) {
                return $this->instance["ChangeMessage"];
            } elseif(isset($this->columns["ChangeMessage"]["default"])) {
                return $this->columns["ChangeMessage"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["ChangeMessage"] = func_get_arg(0);
        }
    }

    public function MinAmount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["MinAmount"])) {
                return $this->instance["MinAmount"];
            } elseif(isset($this->columns["MinAmount"]["default"])) {
                return $this->columns["MinAmount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["MinAmount"] = func_get_arg(0);
        }
    }

    public function MaxAmount()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["MaxAmount"])) {
                return $this->instance["MaxAmount"];
            } elseif(isset($this->columns["MaxAmount"]["default"])) {
                return $this->columns["MaxAmount"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["MaxAmount"] = func_get_arg(0);
        }
    }

    public function MaxRefund()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["MaxRefund"])) {
                return $this->instance["MaxRefund"];
            } elseif(isset($this->columns["MaxRefund"]["default"])) {
                return $this->columns["MaxRefund"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["MaxRefund"] = func_get_arg(0);
        }
    }

    public function TenderModule()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["TenderModule"])) {
                return $this->instance["TenderModule"];
            } elseif(isset($this->columns["TenderModule"]["default"])) {
                return $this->columns["TenderModule"]["default"];
            } else {
                return null;
            }
        } else {
            $this->instance["TenderModule"] = func_get_arg(0);
        }
    }
    /* END ACCESSOR FUNCTIONS */
}

