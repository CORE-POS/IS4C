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

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class SiteUsageReport extends FannieReportPage 
{

    public $description = '[Site Usage Report] lists which pages, reports, and tools are used most';
    public $themed = true;

    protected $title = "Fannie : Site Usage Report";
    protected $header = "Site Usage Report";
    protected $report_cache = 'none';
    protected $report_headers = array('Page', 'Total Visits', 'Unique Users', 'Oldest', 'Newest');
    protected $required_fields = array('date1', 'date2');
    protected $sort_column = 1;
    protected $sort_direction = 1;

    public function fetch_report_data()
    {
        $d1 = FormLib::get_form_value('date1',date('Y-m-d'));
        $d2 = FormLib::get_form_value('date2',date('Y-m-d'));
        $dates = array($d1.' 00:00:00', $d2.' 23:59:59');
        $query = '
            SELECT pageName,
                COUNT(*) AS visits,
                MIN(tdate) AS oldest,
                MAX(tdate) AS mostRecent,
                COUNT(DISTINCT userHash) AS numUsers
            FROM usageStats
            WHERE tdate BETWEEN ? AND ?
            GROUP BY pageName
            ORDER BY COUNT(*) DESC';
        $dbc = $this->connection;
        $dbc->selectDB($this->config->OP_DB);
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $dates);

        $data = array();
        while ($w = $dbc->fetchRow($res)) {
            $data[] = array(
                $w['pageName'],
                $w['visits'],
                $w['numUsers'],
                $w['oldest'],
                $w['mostRecent'],
            );
        }

        return $data;
    }

    public function form_content()
    {
        ?>
        <form method=get class="form-horizontal">
        <div class="row">
            <div class="col-sm-6">
                <p>
                    <label>Start Date</label>
                    <input class="form-control date-field" required type=text id=date1 name=date1 />
                </p>
                <p>
                    <label>End Date</label>
                    <input class="form-control date-field" required type=text id=date2 name=date2 />
                </p>
            </div>
            <div class="col-sm-6">
                <p>
                <?php echo FormLib::date_range_picker(); ?>
                </p>
            </div>
        </div>
        <p>
            <button type=submit name=submit value="Submit" class="btn btn-default">Submit</button>
            <label><input type=checkbox name=excel /> Excel</label>
        </p>
        </form>
        <?php
    }

    public function helpContent()
    {
        return '<p>
            Site Usage shows information about how CORE itself
            is being used. The page names are presented in an
            admin/developer oriented way. Somewhat in-depth
            familiarity with the project is necessary to 
            understand what the report is showing.
            </p>';
    }

}

FannieDispatch::conditionalExec();

?>
