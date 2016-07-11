<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
  @class ArEomSummaryModel
*/
class ArEomSummaryModel extends BasicModel
{

    protected $name = "AR_EOM_Summary";
    protected $preferred_db = 'trans';

    protected $columns = array(
    'cardno' => array('type'=>'INT', 'primary_key'=>true),
    'memName' => array('type'=>'VARCHAR(100)'),
    'priorBalance' => array('type'=>'MONEY'),
    'threeMonthCharges' => array('type'=>'MONEY'),
    'threeMonthPayments' => array('type'=>'MONEY'),
    'threeMonthBalance' => array('type'=>'MONEY'),
    'twoMonthCharges' => array('type'=>'MONEY'),
    'twoMonthPayments' => array('type'=>'MONEY'),
    'twoMonthBalance' => array('type'=>'MONEY'),
    'lastMonthCharges' => array('type'=>'MONEY'),
    'lastMonthPayments' => array('type'=>'MONEY'),
    'lastMonthBalance' => array('type'=>'MONEY'),
    );

    public function doc()
    {
        return '
Use:
List of customer start/end AR balances
over past few months

Maintenance:
cron/nightly.ar.php, after updating ar_history,
 truncates ar_history_backup and then appends all of ar_history
        ';
    }
}

