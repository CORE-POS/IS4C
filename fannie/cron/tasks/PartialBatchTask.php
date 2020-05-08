<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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

use COREPOS\Fannie\API\data\ItemSync;

class PartialBatchTask extends FannieTask
{
    public $name = 'Partial Batch';

    public $description = 'Start and stop partial sales batches. Actual precision of start/stop
times will be limited by how frequently this task runs.';

    public $log_start_stop = false;

    public $default_schedule = array(
        'min' => '*/5',
        'hour' => '6-23',
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    /**
      Implementation notes:

      This does not attempt to capture any more complex pricing mechanisms. It
      also skips creating prodUpdate records. The nature of the task means it
      needs to run FAST and the dilligent checking the normal sales batch task does
      to determine exactly which fields changed has an overhead that may be
      unacceptable here.

      Expanding on this task later once the basics are proven to work makes more
      sense than taking away features in the future the prove too time consuming.
    */
    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $today = date('Y-m-d');
        $now = new DateTime();

        $multiStore= false;
        list($curP, $unsaleP, $saleP, $query) = $this->oneStoreQueries($dbc);
        if ($this->config->get('STORE_MODE') == 'HQ') {
            $multiStore= true;
            list($curP, $unsaleP, $saleP, $query) = $this->multiStoreQueries($dbc);
        }
        $changedUPCs = array();

        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($today));
        $hour = date('G');
        $minute = ltrim(date('i'), '0');
        while ($row = $dbc->fetchRow($res)) {
            if (!$this->appliesToday($row['repetition'])) {
                continue;
            }

            $start = new DateTime($today . ' ' . $row['startTime']);
            $end = new DateTime($today . ' ' . $row['endTime']);
            if ($row['paddingMinutes']) {
                $end = $end->add(new DateInterval('PT' . $row['paddingMinutes'] . 'M'));
            }
            $itemArgs = array($row['upc']);
            if ($multiStore) {
                $itemArgs = array($row['upc'], $row['storeID']);
            }

            if ($now >= $end) {
                // batch should stop
                // if the batchID does not match leave it alone
                $current = $dbc->getRow($curP, array($itemArgs));
                if ($current['batchID'] == $row['batchID']) {
                    $dbc->execute($unsaleP, array($itemArgs));
                    $changedUPCs[] = $row['upc'];
                }
            } elseif ($now >= $start) {
                // batch should start
                // Matching batchID should mean this sale has already started
                $current = $dbc->getRow($curP, array($itemArgs));
                if ($current['batchID'] != $row['batchID'] && ($current['discounttype'] == 0 || $row['overwriteSales'] == 1)) {
                    $saleArgs = array(
                        $row['salePrice'],
                        $row['batchID'],
                        $row['discounttype'],
                        $today . ' ' . $row['startTime'],
                        $today . ' ' . $row['endTime'],
                        $row['upc'],
                    );
                    if ($multiStore) {
                        $saleArgs[] = $row['storeID'];
                    }
                    $dbc->execute($saleP, $saleArgs);
                    $changedUPCs[] = $row['upc'];
                }
            }
        }

        ItemSync::sync($changedUPCs);
    }

    private function oneStoreQueries($dbc)
    {
        $curP = $dbc->prepare('SELECT discounttype, batchID FROM products WHERE upc=?');
        $unsaleP = $dbc->prepare('UPDATE products SET special_price=0, batchID=0, discounttype=0, start_date=\'1900-01-01\', end_date=\'1900-01-01\' WHERE upc=?');
        $saleP = $dbc->prepare('UPDATE products SET special_price=?, batchID=?, discounttype=?, start_date=?, end_date=? WHERE upc=?');

        $query = 'SELECT p.startTime,
                p.endTime,
                p.paddingMinutes,
                p.overwriteSales,
                p.repetition,
                l.salePrice,
                b.discounttype,
                l.upc,
                p.batchID,
                m.storeID
            FROM PartialBatches AS p
                INNER JOIN batches AS b ON p.batchID=b.batchID
                INNER JOIN batchList AS l ON p.batchID=l.batchID
                INNER JOIN StoreBatchMap AS m ON b.batchID=m.batchID
            WHERE ? BETWEEN b.startDate AND b.endDate
                AND b.discounttype > 0';
        return array($curP, $unsaleP, $saleP, $query);
    }

    private function multiStoreQueries($dbc)
    {
        $curP = $dbc->prepare('SELECT discounttype, batchID FROM products WHERE upc=? AND store_id=?');
        $unsaleP = $dbc->prepare('UPDATE products SET special_price=0, batchID=0, discounttype=0, start_date=\'1900-01-01\', end_date=\'1900-01-01\'
            WHERE upc=? AND store_id=?');
        $saleP = $dbc->prepare('UPDATE products SET special_price=?, batchID=?, discounttype=?, start_date=?, end_date=?
            WHERE upc=? AND store_id=?');

        $query = 'SELECT p.startTime,
                p.endTime,
                p.paddingMinutes,
                p.overwriteSales,
                p.repetition,
                l.salePrice,
                b.discounttype,
                l.upc,
                p.batchID
            FROM PartialBatches AS p
                INNER JOIN batches AS b ON p.batchID=b.batchID
                INNER JOIN batchList AS l ON p.batchID=l.batchID
            WHERE ? BETWEEN b.startDate AND b.endDate
                AND b.discounttype > 0';
        return array($curP, $unsaleP, $saleP, $query);
    }

    private function appliesToday($repetition)
    {
        $day = date('N');
        switch (strtolower($repetition)) {
            case 'daily':
                return true;
            case 'weekdays':
                return $day < 6;
            case 'short weekends':
                return $day >= 6;
            case 'long weekends':
                return $day >= 5;
            case 'monday':
                return $day == 1;
            case 'tuesday':
                return $day == 2;
            case 'wednesday':
                return $day == 3;
            case 'thursday':
                return $day == 4;
            case 'friday':
                return $day == 5;
            case 'saturday':
                return $day == 6;
            case 'sunday':
                return $day == 7;
        }

        return false;
    }
}

