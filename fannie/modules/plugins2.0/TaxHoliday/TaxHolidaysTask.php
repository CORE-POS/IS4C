<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

include(dirname(__FILE__).'/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

/**
*/
class TaxHolidaysTask extends FannieTask 
{
    public $name = 'Start/stop Tax Holidays';

    public $description = 'Begins and ends tax holidays';

    public $default_schedule = array(
        'min' => 30,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $today = date('Y-m-d 00:00:00');
        $yesterday = date('Y-m-d 00:00:00', strtotime('yesterday'));
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $find = $dbc->prepare('SELECT taxHolidayID FROM TaxHolidays WHERE tdate=?');
        if ($dbc->getValue($find, array($today))) {
            $this->stopTaxes();
        } elseif ($dbc->getValue($find, array($yesterday))) {
            $this->startTaxes($dbc);
        }
    }

    private function stopTaxes()
    {
        foreach ($this->config->get('LANES') as $lane) {
            $lane_sql= new SQLManager($lane['host'],$lane['type'],
                            $lane['trans'],$lane['user'],$lane['pw']);
            if ($lane_sql->isConnected($lane['trans'])) {
                $lane_sql->query('UPDATE taxrates SET rate=0');
            }
        }
    }

    private function startTaxes($dbc)
    {
        $model = new TaxRatesModel($dbc);
        $taxes = $model->find();
        foreach ($this->config->get('LANES') as $lane) {
            $lane_sql= new SQLManager($lane['host'],$lane['type'],
                            $lane['trans'],$lane['user'],$lane['pw']);
            if ($lane_sql->isConnected($lane['trans'])) {
                foreach ($taxes as $tax) {
                    $lane_sql->query(sprintf(
                        'UPDATE taxrates SET rate=%f WHERE id=%d',
                        $tax->rate(), $tax->id()
                    ));
                }
            }
        }
    }
}

