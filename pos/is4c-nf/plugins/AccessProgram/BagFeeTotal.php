<?php

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\MiscLib;
use COREPOS\pos\lib\TotalActions\TotalAction;

class BagFeeTotal extends TotalAction
{
    public function apply()
    {
        $dbc = Database::tDataConnect();

        $prep = $dbc->prepare("SELECT
                SUM(CASE WHEN trans_subtype IN ('EF','EC','WI') THEN total ELSE 0 END) as tender,
                SUM(CASE WHEN department=701 THEN total ELSE 0 END) as donate,
                SUM(CASE WHEN upc='0000000010730' THEN total ELSE 0 END) as fees
            FROM localtemptrans");
        $info = $dbc->getRow($prep);
        $tenderAmt = $info['tender'];
        if (abs($info['fees']) > 0.005 && (CoreLocal::get('memType') == 5 || abs($tenderAmt) > 0.005)) {
            $dbc->query("UPDATE localtemptrans SET total=0 WHERE upc='0000000010730'");
            return MiscLib::baseURL() . 'gui-modules/pos2.php?reginput=TO&repeat=1';
        }

        /*
        $due = sprintf('%.2f', CoreLocal::get('amtdue'));
        if (substr($due, -2) != '00' && $info['fees'] > 0 && $info['donate'] > 0) {
            $reduce = '0.' . substr($due, -2);
            if ($reduce <= $info['donate']) {
                $upP = $dbc->prepare("UPDATE localtemptrans SET total=?
                    WHERE department=701");
                $dbc->execute($upP, array($info['donate'] - $reduce));
            }
        }
         */

        return true;
    }
}

