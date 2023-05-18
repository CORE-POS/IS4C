<?php
use COREPOS\pos\parser\Parser;
use COREPOS\pos\lib\DisplayLib;
use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\TransRecord;
use COREPOS\pos\lib\MiscLib;

class VolunteerHoursParser extends Parser
{
    public function check($str)
    {
        if ($str == 'VHV') {
            return true;
        } elseif ($str == 'VHR') {
            return true;
        } else {
            return false;
        }
    }

    public function parse($str)
    {
     	$transDB = Database::tDataConnect();
    	$qtyQ = "SELECT SUM(quantity) FROM localtemptrans WHERE trans_subtype = 'VH'";
    	$qtyR = $transDB->query($qtyQ);
    	$qtyW = $transDB->fetch_row($qtyR);
    	$qty = $qtyW[0];
        $ret = $this->default_json();
        list($hours, $dollars) = $this->getHours(CoreLocal::get('memberID'));
    	$ttlct = CoreLocal::get('amtdue') / 20;  // discount is per $20 spent
    	$ttldisc = $ttlct * CoreLocal::get('VolunteerHourValue');
        if ($str == 'VHV') {
            $ret['output'] = DisplayLib::boxMsg(
            sprintf('Hours Available: %.2f<br />Value: $%.2f', $hours, $dollars),
                'Volunteer Hours',
                true
            );
        } elseif ($hours <= 0 || $dollars <= 0) {
            $ret['output'] = DisplayLib::boxMsg(
                _('No hours available'),
                'Volunteer Hours',
                true
            );
    	} elseif ($qty > 0) {
    	    $ret['output'] = DisplayLib::boxMsg(
    		  _('Discount already applied'),
    		  'Volunteer Hours',
    		  false
    	    );
        } else {
    	    // prevent spending more in discount than is available in hours
    	    if ($ttlct > $hours) {
        		$ttldisc = $hours * CoreLocal::get('VolunteerHourValue');
    	    }
            TransRecord::addRecord(array(
                'description' => 'VOLUNTEER HOURS',
                'trans_type' => 'T',
                'trans_subtype' => 'VH',
                'total' => MiscLib::truncate2(-1 * $ttldisc),
                'quantity' => $ttlct,
            ));
            $ret['output'] = DisplayLib::lastpage();
	        $ret['udpmsg'] = 'goodBeep';
	        $ret['redraw_footer'] = true;
        }

            return $ret;
    }

    private function getHours($member)
    {
        $dbc = Database::mDataConnect();
        $dbc->selectDB(CoreLocal::get('ServerVolunteerDB'));
        $prep = $dbc->prepare('
            SELECT SUM(hoursWorked) - SUM(hoursRedeemed)
            FROM VolunteerHoursActivity
            WHERE cardNo=?');
        $balance = $dbc->getValue($prep, array($member));
        if ($balance === false) {
            return array(0, 0);
        }
        return array($balance, $balance*CoreLocal::get('VolunteerHourValue'));  
    }
}

