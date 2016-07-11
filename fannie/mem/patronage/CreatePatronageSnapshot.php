<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

    This file is part of CORE-POS.

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class CreatePatronageSnapshot extends FannieRESTfulPage
{
    protected $header = 'Create Patronage Snapshot';
    protected $title = 'Create Patronage Snapshot';
    public $themed = true;
    public $description = '[Patronage Snapshot] extracts a year of summarized transaction data into a working table
    to perform further patronage calculations and adjustments.';

    public function preprocess()
    {
        $this->__routes[] = 'get<date1><date2><mtype><stype>';

        return parent::preprocess();
    }

    protected function get_date1_date2_mtype_stype_handler()
    {
        global $FANNIE_TRANS_DB, $FANNIE_ROOT, $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_TRANS_DB);
        include($FANNIE_ROOT.'install/db.php');

        $mtype = "(";
        $mArgs = array();
        foreach ($this->mtype as $m) {
            $mtype .= '?,';
            $mArgs[] = (int)$m;
        }
        $mtype = rtrim($mtype,",").")";

        $stype = '(';
        $sArgs = array();
        foreach ($this->stype as $s) {
            $stype .= '?,';
            $sArgs[] = (int)$s;
        }
        $stype = rtrim($stype, ',') . ')';
        if (count($sArgs) == 0) {
            $stype = '(?)';
            $sArgs = array('-9999');
        }

        $dlog = DTransactionsModel::selectDlog($this->date1, $this->date2);

        if ($dbc->table_exists("dlog_patronage")) {
            $drop = $dbc->prepare("DROP TABLE dlog_patronage");
            $dbc->execute($drop);
        }
        $create = $dbc->prepare('CREATE TABLE dlog_patronage (card_no INT, trans_type VARCHAR(2), 
                trans_subtype VARCHAR(2), total DECIMAL(10,2), min_year INT, max_year INT,
                primary key (card_no, trans_type, trans_subtype))');
        $dbc->execute($create);

        $insQ = sprintf("
                INSERT INTO dlog_patronage
                SELECT d.card_no,
                    trans_type,
                    trans_subtype,
                    sum(total),
                    YEAR(MIN(tdate)) AS firstDate, 
                    YEAR(MAX(tdate)) AS lastDate
                FROM %s AS d
                LEFT JOIN %s%scustdata AS c ON c.CardNo=d.card_no AND c.personNum=1 
                LEFT JOIN %s%ssuspensions AS s ON d.card_no=s.cardno
                LEFT JOIN %s%sMasterSuperDepts AS m ON d.department=m.dept_ID
                WHERE d.trans_type IN ('I','D','S','T')
                    AND d.total <> 0 
                    AND (s.memtype1 IN %s OR c.memType IN %s)
                    AND (m.superID IS NULL OR m.superID NOT IN %s)
                    AND d.tdate BETWEEN ? AND ?
                GROUP BY d.card_no, trans_type, trans_subtype",
                $dlog,$FANNIE_OP_DB,$dbc->sep(),
                $FANNIE_OP_DB,$dbc->sep(),
                $FANNIE_OP_DB,$dbc->sep(),
                $mtype,$mtype,
                $stype);
        $args = $mArgs;
        foreach ($mArgs as $m) $args[] = $m; // need them twice
        foreach ($sArgs as $s) $args[] = $s;
        $args[] = $this->date1 . ' 00:00:00';
        $args[] = $this->date2 . ' 23:59:59';
    
        $prep = $dbc->prepare($insQ);
        $worked = $dbc->execute($prep,$args);

        if ($worked) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Patronage Snapshot Created');\n");
        } else {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'Error creating snapshot');\n");
        }

        return true;
    }

    protected function get_date1_date2_mtype_stype_view()
    {
        return '
            <div id="alert-area"></div>
            <p>
            <button type="button" class="btn btn-default"
                onclick="location=\'index.php\'; return false;">Patronage Menu</button>
            <button type="button" class="btn btn-default"
                onclick="location=\'CreatePatronageSnapshot.php\'; return false;">Create a new Snapshot</button>
            </p>';
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        ob_start();

        ?>
        <div class="well">
        Step one: gather member transactions for the year. Dates specify the start and
        end of the year. Inactive and terminated memberships will be included if their type,
        prior to suspension, matches one of the requested types.
        <b>Note: this step may take a couple minutes</b>.
        </div>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
        <label>Start Date</label>
        <input type="text" name="date1" id="date1" class="form-control date-field" required />
        <label>End Date</label>
        <input type="text" name="date2" id="date2" class="form-control date-field" required />
        <label>Member Type(s) to include</label>
        <div class="form-group well">
        <?php
        $typeQ = $dbc->prepare("
            SELECT memtype,
                memDesc,
                custdataType
            FROM ".$FANNIE_OP_DB.$dbc->sep()."memtype 
            ORDER BY memtype");
        $typeR = $dbc->execute($typeQ);
        while ($typeW = $dbc->fetch_row($typeR)) {
            printf('<input class="checkbox-inline" type="checkbox" value="%d" name="mtype[]"
                id="mtype%d" %s /> <label for="mtype%d">%s</label><br />',
                $typeW['memtype'],$typeW['memtype'],
                (strtoupper($typeW['custdataType']) == 'PC' ? 'checked' : ''),
                $typeW['memtype'],$typeW['memDesc']
            );
        }
        echo '</div>';
        echo '<label>Super Department(s) to exclude from purchases</label>
            <div class="form-group well">';
        $msd = $dbc->query('
            SELECT superID,
                super_name
            FROM MasterSuperDepts
            GROUP BY superID,
                super_name
            ORDER BY super_name');
        while ($m = $dbc->fetchRow($msd)) {
            printf('<input class="checkbox-inline" type="checkbox" value="%d" name="stype[]"
                id="style%d" %s /> <label for="stype%d">%s</label><br />',
                $m['superID'], $m['superID'], 
                ($m['superID'] == 0 ? 'checked' : ''),
                $m['superID'], $m['super_name']
            );
        }
        echo '</div>';

        echo '<p><button type="submit" class="btn btn-default">Create Table</button></p>';
        echo '</form>';

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>The first step of calculating patronage involves
            compiling member transaction data for the year. This will
            speed up many subsequent steps since it can be a lot of
            information to go through.</p>
            <p>The fiscal year is defined by the start and end dates.
            Only members in the select member type(s) will be included
            in the patronage calculation. Sales that are in the
            selected super departments will be <em>omitted</em> from
            members\' total purchases.</p>
            <p>Member purchase totals are organized by transaction
            type and subtype. Records whose department belong to
            super department number zero are excluded in keeping with
            the convention that super department zero contains
            non-inventory sales.</p>';
    }
}

FannieDispatch::conditionalExec();

