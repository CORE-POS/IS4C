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
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
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
        $infoP = $this->connection->prepare("SELECT * FROM AccessDiscounts WHERE cardNo=?");
        $info = $this->connection->getRow($infoP, array($this->id));
        if (!is_array($info)) {
            return 'No access discount information found!';
        }

        return <<<HTML
<table>
    <tr>
        <td class="yellowbg">Last Renewed</td>
        <td>{$info['lastRenewal']}</td>
    </tr>
    <tr>
        <td class="yellowbg">Program</td>
        <td>{$this->programs[$info['programID']]}</td>
    <tr>
        <td class="yellowbg">Renewed By</td>
        <td>{$info['renewerName']}</td>
    </tr>
    <tr>
        <td class="yellowbg">Discount Expires</td>
        <td>{$info['expires']}</td>
    </tr>
    <tr>
        <td class="yellowbg">Notes</td>
        <td></td>
    </tr>
    <tr>
        <td colspan="2">{$info['notes']}</td>
    </tr>
</table>
HTML;

    }

    public function css_content(){
        return '
            .greenbg { background: #006633; }
            .greentxt { color: #006633; }
            .yellowbg { background: #FFFF33; }
            .yellowtxt { color: #FFFF33; }
        ';
    }
}

FannieDispatch::conditionalExec();

