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

class InitProductAttributes extends FannieTask
{
    public $name = 'Initialize Product Attributes';

    public $description = 'Converts existing prodFlags values
    to JSON-encoded attributes in ProductAttributes';

    public $schedulable = false;

    public $default_schedule = array(
        'min' => 45,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));

        $model = new ProdFlagsModel($dbc);
        $flags = $model->find();

        $itemR = $dbc->query('SELECT upc, MAX(numflag) AS numflag FROM products GROUP BY upc HAVING MAX(numflag) > 0');
        $setP = $dbc->prepare("INSERT INTO ProductAttributes (upc, modified, attributes) VALUES (?, ?, ?)");
        $dbc->startTransaction();
        while ($itemW = $dbc->fetchRow($itemR)) {
            $upc = $itemW['upc'];
            $numflag = $itemW['numflag'];
            $json = array();
            foreach ($flags as $flag) {
                $attr = $flag->description();
                $bit = $flag->bit_number();
                $set = $numflag & (1 << ($bit-1));
                $json[$attr] = $set ? true : false;
            }
            $dbc->execute($setP, array($upc, date('Y-m-d H:i:s'), json_encode($json)));
        }
        $dbc->commitTransaction();
    }
}

