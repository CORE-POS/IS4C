<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

use COREPOS\Fannie\API\QueuedReportPage;

class QueuedReportsTask extends FannieTask
{
    public $name = 'Run queued reports';

    public $description = 'Runs queued reports.
Each time this task runs one queued report is
processed and emailed.';

    public $log_start_stop = false;

    public $default_schedule = array(
        'min' => '1,6,11,16,21,26,31,36,41,46,51,56',
        'hour' => '7-22',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $report = new QueuedReportPage();
        $report->setConfig($this->config);
        $report->setLogger($this->logger);
        $report->setConnection(FannieDB::get($this->config->get('OP_DB')));
        $report->runNextReport();
    }
}

