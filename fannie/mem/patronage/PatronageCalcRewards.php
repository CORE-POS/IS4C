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

class PatronageCalcRewards extends FannieRESTfulPage
{
    protected $title = "Fannie :: Patronage Tools";
    protected $header = "Calculate Rewards";
    public $description = '[Patronage Rewards] calculates the rewards column for work-in-progress patronage data.';
    public $themed = true;

    public function get_id_handler()
    {
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);

        $types = "";
        $list = preg_split("/\W+/",$this->id,-1,PREG_SPLIT_NO_EMPTY);
        $args = array();
        foreach ($list as $l) {
            $types .= '?,';
            $args[] = $l;
        }
        $types = substr($types,0,strlen($types)-1);

        $fetchQ = sprintf("SELECT card_no,SUM(total) as total
            FROM %s%sdlog_patronage
            WHERE trans_type='T'
            AND trans_subtype IN (%s)
            GROUP BY card_no",$FANNIE_TRANS_DB,$dbc->sep(),$types);
        $prep = $dbc->prepare($fetchQ);
        $fetchR = $dbc->execute($prep,$args);

        $upP = $dbc->prepare("UPDATE patronage_workingcopy
            SET rewards=? WHERE cardno=?");
        $reward_count = 0;
        $error_count = 0;
        while ($fetchW = $dbc->fetch_row($fetchR)) {
            if ($fetchW['total']==0) continue;
            $added = $dbc->execute($upP,array($fetchW['total'],$fetchW['card_no']));
            if ($added) {
                $reward_count++;
            } else {
                $error_count++;
            }
        }
    
        if ($reward_count > 0) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'success', 'Added reward to $reward_count accounts')\n");
        } else {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'warning', 'Zero account had rewards')\n");
        }
        if ($error_count > 0) {
            $this->add_onload_command("showBootstrapAlert('#alert-area', 'danger', 'Error occurred on $reward_count accounts')\n");
        }

        return true;
    }

    public function get_id_view()
    {
        return '
            <div id="alert-area"></div>
            <p>
            <button type="button" class="btn btn-default"
                onclick="location=\'index.php\'; return false;">Patronage Menu</button>
            <button type="button" class="btn btn-default"
                onclick="location=\'PatronageCalcRewards.php\'; return false;">Re-Calculate</button>
            </p>';
    }

    public function get_view()
    {
        ob_start();
        ?>
        <div class="well">
        Step three: calculate additonal member rewards based on tender type.
        Rewards are benefits received during the year that should be subtracted
        from total patronage. Use spaces or commas between tender codes.
        </div>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get">
        <label>Tender Code(s)</label>
        <input type="text" name="id" class="form-control" />
        <p>
            <button type="submit" class="btn btn-default">Calculate Rewards</button>
        </p>
        </form>
        <?php

        return ob_get_clean();
    }

    public function helpContent()
    {
        return '<p>Rewards are a catch-all for other benefits
            received by members throughout the year. In store coupons
            are probably the most common entry here. Leaving rewards
            at zero is also perfectly fine.</p>';
    }
}

FannieDispatch::conditionalExec();

