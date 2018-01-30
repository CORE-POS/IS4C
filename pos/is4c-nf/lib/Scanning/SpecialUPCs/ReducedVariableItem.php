<?php

namespace COREPOS\pos\lib\Scanning\SpecialUPCs;
use COREPOS\pos\lib\Scanning\SpecialUPC;
use COREPOS\pos\lib\Database;
use COREPOS\pos\parser\parse\UPC;

/**
 * @class ReducedVariableItem
 *
 * This utilizes an EAN-13 scale barcode as an alias
 * for the normal UPC-A scale barcode. The EAN-13 value
 * is rewritten and rung up as the normal UPC-A item
 * but it's flagged RD. This is intended to help track
 * when an individual package has been sold at a reduced
 * price.
 */
class ReducedVariableItem extends SpecialUPC
{
    public function isSpecial($upc)
    {
        return substr($upc, 0, 3) == '021';
    }

    public function handle($upc, $json)
    {
        // parse the normal UPC
        $upc = '002' . substr($upc, 3);
        $parser = new UPC($this->session);
        $ret = $parser->parse($upc);

        // change the added record's charflag to RD
        $dbc = Database::tDataConnect();
        $rewrite = '002' . substr($upc, 3, 5) . '00000';
        $prep = $dbc->prepare('SELECT trans_id FROM localtemptrans WHERE upc=? ORDER BY trans_id DESC');
        $tID = $dbc->getValue($prep, array($rewrite));
        $upP = $dbc->prepare("UPDATE localtemptrans SET charflag='RD' WHERE upc=? AND trans_id=?");
        $dbc->execute($upP, array($rewrite, $tID));

        return $ret;
    }
}

