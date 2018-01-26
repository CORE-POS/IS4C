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

class LikeCodeTask extends FannieTask
{
    public $name = 'Product Like Code Maintenance';

    public $description = 'Scans recent transactions for like
    code activity and updates status on like codes.';

    public $default_schedule = array(
        'min' => 45,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    private function setLastSold($dbc, $likeCode, $upcs, $dlog)
    {
        $active = new LikeCodeActiveMapModel($dbc);
        list($inStr, $args) = $dbc->safeInClause($upcs);
        $transP = $dbc->prepare("
            SELECT store_id,
                MAX(tdate) AS last_sold
            FROM {$dlog}
            WHERE upc IN ({$inStr})
            GROUP BY store_id
            HAVING SUM(total) > 0");
        $transR = $dbc->execute($transP, $args);
        while ($transW = $dbc->fetchRow($transR)) {
            $active->likeCode($likeCode);
            $active->storeID($transW['store_id']);
            $active->lastSold($transW['last_sold']);
            $active->save();
        }
    }

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $dlog = $this->config->get('TRANS_DB') . $dbc->sep() . 'dlog_90_view';
        $dbc->startTransaction();
        $upcP = $dbc->prepare('SELECT upc FROM upcLike WHERE likeCode=?');
        $res = $dbc->query('SELECT likeCode FROM upcLike GROUP BY likeCode');
        while ($row = $dbc->fetchRow($res)) {
            $upcR = $dbc->execute($upcP, array($row['likeCode']));
            $upcs = array();
            while ($upcW = $dbc->fetchRow($upcR)) {
                $upcs[] = $upcW['upc'];
            }
            $this->setLastSold($dbc, $row['likeCode'], $upcs, $dlog);
        }
        $dbc->commitTransaction();
    }
}

