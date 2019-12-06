<?php

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TotalActions\TotalAction;

class BagFeeTotal extends TotalAction
{
    public function apply()
    {
        $dbc = Database::tDataConnect();

        $prep = $dbc->prepare("SELECT SUM(total) FROM localtemptrans WHERE trans_subtype IN ('EF','EC','WI')");
        $tenderAmt = $dbc->getValue($prep);
        if (CoreLocal::get('memType') == 5 || abs($tenderAmt) > 0.005) {
            $dbc->query("UPDATE localtemptrans SET total=0 WHERE upc='0000000001354'");
            return MiscLib::baseURL() . 'gui-modules/pos2.php?reginput=TO&repeat=1';
        }

        return true;
    }
}

