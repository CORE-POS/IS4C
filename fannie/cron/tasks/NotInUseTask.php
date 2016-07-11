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

class NotInUseTask extends FannieTask
{

    public $name = 'Detect Not inUse Task';

    public $description = 'Review transaction logs and
report when items are sold that have been marked not inUse.';

    public $default_schedule = array(
        'min' => 22,
        'hour' => 3,
        'day' => '*',
        'month' => '*',
        'weekday' => '*',
    );

    public function run()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $url = $this->config->get('URL');
        $host = $this->config->get('HTTP_HOST');
        $dtrans = DTransactionsModel::selectDTrans($yesterday);

        $findP = $dbc->prepare('
            SELECT d.upc,
                d.description,
                d.department,
                p.inUse,
                count(*) AS occurences
            FROM ' . $dtrans . ' AS d '
                . DTrans::joinProducts('d') . '
            WHERE d.trans_type=\'L\'
                AND d.trans_subtype=\'OG\'
                AND d.charflag=\'IU\'
                AND d.emp_no <> 9999
                AND d.register_no <> 99
                AND d.datetime BETWEEN ? AND ?
            GROUP BY d.upc,
                d.description,
                d.department,
                p.inUse
            HAVING SUM(d.quantity) <> 0
        ');
        $findR = $dbc->execute($findP, array($yesterday . ' 00:00:00', $yesterday . ' 23:59:59'));
        while ($w = $dbc->fetchRow($findR)) {
            $msg = sprintf('%s (%s) was sold %d times while not inUse',
                $w['description'], $w['upc'], $w['occurences']);
            $email = \COREPOS\Fannie\API\lib\AuditLib::getAddresses($w['department']);
            if ($this->config->get('COOP_ID') === 'WFC_Duluth') {
                $email = $this->config->get('ADMIN_EMAIL');
            }
            if ($email) {
                $subject = 'Not In Use Report';
                $from = "From: automail\r\n";
                $msg .= "\n";
                $msg .= "http://{$host}/{$url}item/ItemEditorPage.php?searchupc={$w['upc']}\n";
                mail($email, $subject, $msg, $from);
            } else {
                $this->cronMsg($msg);
            }
        }
    }
}
