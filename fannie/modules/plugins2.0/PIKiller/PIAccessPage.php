<?php 
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
    include($FANNIE_ROOT.'/classlib2.0/FannieAPI.php');
}
if (!class_exists('PIKillerPage')) {
    include('lib/PIKillerPage.php');
}

class PIAccessPage extends PIKillerPage 
{
    protected $header = 'Access History';
    protected $title = 'Access History';
    public $discoverable = false;
    private $programs = array(
        1 => 'Emergency Assistance Program',
        2 => 'Energy Assistance Program',
        3 => 'Medicaid',
        4 => 'Section 8',
        5 => 'School Meal Program',
        6 => 'SNAP',
        7 => 'SSI or RSDI',
        8 => 'WIC',
    );

    protected function get_id_view()
    {
        $date = date('Y-m-d', strtotime('3 years ago'));
        $dlog = DTransactionsModel::selectDlog($date);

        $query = $this->connection->prepare("
            SELECT tdate, trans_num, numflag
            FROM {$dlog}
            WHERE upc='ACCESS'
                AND tdate >= ?
                AND card_no=?
            ORDER BY tdate DESC");

        $ret = '<tr><td>Access History since ' . $date;
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $res = $this->connection->execute($query, array($date, $this->id));
        $max = false;
        while ($row = $this->connection->fetchRow($res)) {
            if (!$max) {
                $max = new DateTime($row['tdate']);
            }
            $tdate = new DateTime($row['tdate']); 
            $ret .= sprintf('<tr><td>%s</td><td><a href="%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&receipt=%s">%s</a></td><td>%s</td></tr>',
                    $row['tdate'],
                    $this->config->get('URL'),
                    $tdate->format('Y-m-d'),
                    $row['trans_num'],
                    $row['trans_num'],
                    (isset($this->programs[$row['numflag']]) ? $this->programs[$row['numflag']] : '?')
            );
        }
        $ret .= '</table>';
        if ($max) {
            $max->add(new DateInterval('P1Y'));
            $ret .= sprintf('<p>Discount expires %s</p>', $max->format('Y-m-d'));
        } else {
            $ret .= '<p>No access activity found</p>';
        }
        $ret .= '</td></tr>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

