<?php

class VhRedeemedTask extends FannieTask
{
    public function run()
    {
	global $FANNIE_TRANS_DB;
        $dbc = FannieDB::get($this->config->get('TRANS_DB'));
	$dba = FannieDB::get($this->config->get('OP_DB'));
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $vhdb = $settings['VolunteerHoursDB'];

        $res = $dbc->query('
            SELECT card_no,
                tdate,
                trans_num,
                total,
                quantity
            FROM '.$FANNIE_TRANS_DB.'.dlog_15
            WHERE trans_type=\'T\'
                AND trans_subtype=\'VH\'
        ');
        $chkP = $dbc->prepare('
            SELECT *
            FROM ' . $vhdb . $dbc->sep() . 'VolunteerHoursActivity
            WHERE cardNo=?
                AND tdate=?
                AND transNum=?');
        $addP = $dbc->prepare('
            INSERT INTO ' . $vhdb . $dbc->sep() . 'VolunteerHoursActivity
                (tdate, cardNo, hoursWorked, hoursRedeemed, transNum)
            VALUES
                (?, ?, 0, ?, ?)');
        while ($row = $dbc->fetchRow($res)) {
            $chkR = $dbc->execute($chkP, array($row['card_no'], $row['tdate'], $row['trans_num']));
            if ($dbc->numRows($chkR) == 0) {
                $dbc->execute($addP, array($row['tdate'], $row['card_no'], $row['quantity'], $row['trans_num']));
            }
        }
		
	/*
	 *  Update CustomerNotifications table.blueline
	 */
	$del = $dba->query('DELETE FROM CustomerNotifications WHERE source = \'VhRedeemedTask\'');

	$upd = $dba->query('INSERT INTO CustomerNotifications (cardNo,source,type,message,modifierModule) 
		(SELECT cardNo, \'VhRedeemedTask\',\'blueline\', CONCAT(\'VHrs: \', ROUND(SUM(hoursWorked) - SUM(hoursRedeemed),2)), \'VhRedeemedTask\' 
		FROM ' . $vhdb . $dba->sep() . 'VolunteerHoursActivity 
		WHERE cardNo IN(
			SELECT cardNo FROM ' . $vhdb . $dba->sep() . 'VolunteerHoursActivity
			GROUP BY cardNo 
			HAVING (SUM(hoursWorked) - SUM(hoursRedeemed)) > 0
		)
		GROUP BY cardNo)');
		
    }
}

