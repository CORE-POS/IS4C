<?php

use COREPOS\pos\lib\Database;
use COREPOS\pos\lib\TotalActions\TotalAction;

class AccessTotal extends TotalAction
{
    public function apply()
    {
        $dbc = Database::tDataConnect();
        $prep = $dbc->prepare('SELECT SUM(total) FROM localtemptrans WHERE department=992');
        $ttl = $dbc->getValue($prep);
        if ($ttl === false || $ttl === null || abs($ttl) < 0.005) {
            return true;
        }

        $prep = $dbc->prepare('SELECT SUM(total) FROM localtemptrans WHERE department IN (991, 992)');
        $ttl = $dbc->getValue($prep);
        if ($ttl >= 100) {
            return true;
        }

        $prep = $dbc->prepare("SELECT description FROM localtemptrans WHERE description='MATCHING FUNDS'");
        $comment = $dbc->getValue($prep);
        if ($comment == 'MATCHING FUNDS') {
            return true;
        }

        $pluginInfo = new AccessProgram();
        
        return $pluginInfo->pluginUrl() . '/AccessConfirmPage.php';
    }
}

