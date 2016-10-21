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

class TendersModel extends BasicModel 
{

    protected $name = 'tenders';

    protected $preferred_db = 'op';

    protected $normalize_lanes = true;

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
    'SalesCode' => array('type'=>'INT'),
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

    /**
      Lookup existing TenderMap in parameters
      and write appropriate values in new
      TenderModule column
    */
    public function hookAddColumnTenderModule()
    {
        $settingR = $this->connection->query('
            SELECT param_value 
            FROM parameters
            WHERE param_key=\'TenderMap\'
            ORDER BY store_id,
                lane_id');
        if ($this->connection->numRows($settingR) > 0) {
            $settingW = $this->connection->fetchRow($settingR);
            $tender_map = $settingW['param_value'];
            $update = $this->connection->prepare('
                UPDATE tenders
                SET TenderModule=?
                WHERE TenderCode=?
            ');
            foreach (explode(',', $tender_map) as $tender) {
                list($code, $module) = explode('=>', $tender, 2);
                $this->connection->execute($update, array($module, $code));
            }
        }
    }
}

