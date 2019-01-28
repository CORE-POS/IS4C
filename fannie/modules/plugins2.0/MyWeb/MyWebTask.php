<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

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
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}

/**
 * @class MyWebTask
 *
 * Gather local data that will be uploaded to & displayed on
 * the personalized web site. Actual work is deferred to the
 * underlying models.
*/
class MyWebTask extends FannieTask 
{
    public $name = 'My Web Data Task';

    public $description = 'Refreshes data gathered by MyWeb Plugin';

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $model = new IdentifiersModel($dbc);
        $model->etl($this->config);

        $model = new MySpecialOrdersModel($dbc);
        $model->etl($this->config);

        $model = new MyStatsModel($dbc);
        $model->etl($this->config);

        $model = new MyReceiptsModel($dbc);
        $model->etl($this->config);

        $model = new MyRoundUpsModel($dbc);
        $model->etl($this->config);

        $model = new MyEquityModel($dbc);
        $model->etl($this->config);
    }
}

