<?php

include(__DIR__ . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('CTDB')) {
    include(__DIR__ . '/CTDB.php');
}

class CTRecipes extends FannieRESTfulPage
{
    protected $header = 'CT Recipes';
    protected $title = 'CT Recipes';

    /**
     * Convert RTF formatted text to HTML
     * Uses "unrtf" command line utility
     */
    private function rtfToHtml($rtf)
    {
        $temp = tempnam(sys_get_temp_dir(), 'rtf');
        file_put_contents($temp, $rtf);
        exec("unrtf {$temp}", $output);
        unlink($temp);

        $html = implode("\n", $output);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $body = $dom->getElementsByTagName('body')->item(0);
        $ret = '';
        foreach ($body->childNodes as $childNode) {
            $ret .= $dom->saveHTML($childNode);
        }

        return $ret;
    }

    /**
     * Get proper ingredient list for the item
     */
    private function getIngredientList($dbc, $id)
    {
        /**
         * Query notes:
         *
         * Joining on ConvUnit gives possible conversions to
         * other units. UnitKind 1 is weight, 2 is volume
         *
         * Items with zero quantity are normally filler text
         *
         * Non-food items like packaging can be flagged to ignore
         * via IgnoreNutr
         */
        $query = "SELECT i.ItemID, i.ItemName, i.Ingredients,
                v.Quantity1, u.UnitSing, u.UnitKind,
                z.UnitSing AS conv, z.UnitKind AS convKind,
                c.Quantity1 AS amtIn, c.Quantity2 AS amtOut,
                v.RecordID
            FROM Recipe AS r
                INNER JOIN RecpItems AS m ON r.RecipeID=m.RecipeID
                INNER JOIN Inv AS i ON m.ItemID=i.ItemID
                LEFT JOIN RecpInv AS v ON r.RecipeID=v.RecipeID AND m.ItemID=v.ItemID
                LEFT JOIN Units AS u ON v.UnitID1=u.UnitID
                LEFT JOIN ConvUnit AS c ON u.UnitID=c.UnitID1 AND i.ItemID=c.ItemID
                LEFT JOIN Units AS z ON z.UnitID=c.UnitID2
            WHERE r.RecipeID=?
                and v.Quantity1 > 0
                and i.IgnoreNutr = 0
            ORDER BY v.RecordID";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($id));

        /**
         * Build the initial list with item name, weight, and volume.
         * RecordID is used as a key because the join against possible conversions
         * could result in multile rows to a given item
         */
        $list = array();
        while ($row = $dbc->fetchRow($res)) {
            $rID = $row['RecordID'];

            /**
             * Initialize record if needed with name and null weight, volume
             */
            if (!isset($list[$rID])) {
                $name = $this->fixName($row['ItemName']);
                if ($row['Ingredients']) {
                    $name .= ' (' . $row['Ingredients'] . ')';
                }
                $list[$rID] = array('name' => $name, 'weight' => null, 'volume' => null);
            }

            /**
             * If it's a volume and none is registered for this item yet,
             * get the volume in fl oz then check for a weight conversion
             */
            if ($row['UnitKind'] == 2 && $list[$rID]['volume'] === null) {
                $floz = $this->volToFlOz($row['Quantity1'], $row['UnitSing']);
                $list[$rID]['volume'] = $floz;
                if ($row['convKind'] == 1) {
                    $convOz = $this->weightToOz($row['amtOut'], $row['conv']);
                    $list[$rID]['weight'] = ($row['Quantity1'] / $row['amtIn']) * $convOz;
                }
            }

            /*
             * If it's a weight and none is registered for this item yet,
             * get the weight in oz then check for a volume conversion
             */
            if ($row['UnitKind'] == 1 && $list[$rID]['weight'] === null) {
                $oz = $this->weightToOz($row['Quantity1'], $row['UnitSing']);
                $list[$rID]['weight'] = $oz;
                if ($row['convKind'] == 2) {
                    $convFz = $this->volToFlOz($row['amtOut'], $row['conv']);
                    $list[$rID]['volume'] = ($row['Quantity1'] / $row['amtIn']) * $convOz;
                }
            }
        }

        /**
         * Fill in missing weight or volume values by 
         * assuming 1 oz == 1 fl oz
         */
        for ($i=0; $i<count($list); $i++) {
            $cur = $list[$i];
            if ($cur['volume'] !== null && $cur['weight'] === null) {
                $list[$i]['weight'] = $cur['volume'];
            }
            if ($cur['weight'] !== null && $cur['volume'] === null) {
                $list[$i]['volume'] = $cur['weight'];
            }
        }

        $list = $this->sortIngredients($list);

        $ret = '';
        foreach ($list as $i) {
            $ret .= $i['name'] . ', ';
        }
        if ($ret != '') {
            $ret = substr($ret, 0, strlen($ret) - 2);
        }

        return $ret;
    }

    /**
     * Sort by volume first, in reverse (largest => smallest)
     * then sort alphabetically
     */
    private function sortIngredients($ing)
    {
        $compare = function($a, $b) {
            if ($a['volume'] < $b['volume']) {
                return 1;
            } elseif ($a['volume'] > $b['volume']) {
                return -1;
            } else {
                if ($a['name'] < $b['name']) {
                    return -1;
                } elseif ($a['name'] > $b['name']) {
                    return 1;
                }
            }

            return 0;
        };

        usort($ing, $compare);

        return $ing;
    }

    /**
     * Remove commas and re-order the string
     * e.g., "chiles, ancho, drived" becomes
     * "dried ancho chiles"
     */
    private function fixName($name)
    {
        if (!strstr($name, ',')) {
            return $name;
        }
        $parts = explode(',', $name);
        $parts = array_reverse($parts);

        return implode(' ', $parts);
    }

    /**
     * Convert given amount & unit to fl oz
     */
    private function volToFlOz($amt, $unit)
    {
        switch (trim(strtolower($unit))) {
            case 'qt':
                return 32 * $amt;
            case 'cup':
                return 8 * $amt;
            case 'tsp':
                return 0.17 * $amt;
        }

        return $amt;
    }

    /**
     * Convert given amount & unit to oz
     */
    private function weightToOz($amt, $unit)
    {
        switch (trim(strtolower($unit))) {
            case 'lb':
                return 16 * $amt;
            case 'kg':
                return 35.274 * $amt;
            case 'g':
                return 0.35274 * $amt;
        }
        
        return $amt;
    }

    private function getAllergens($dbc, $id)
    {
        $query = "SELECT a.AllergenName
            FROM Recipe AS r
                INNER JOIN RecpItems AS m ON r.RecipeID=m.RecipeID
                INNER JOIN InvAllergens AS g ON m.ItemID=g.ItemID
                INNER JOIN Allergens AS a ON g.AllergenID=a.AllergenID
            WHERE r.RecipeID=?
            GROUP BY a.AllergenName
            ORDER BY a.AllergenName";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, array($id));
        if ($dbc->numRows($res) == 0) {
            return '';
        }

        $ret = 'Contains: ';
        while ($row = $dbc->fetchRow($res)) {
            $ret .= $row['AllergenName'] . ', ';
        }

        return substr($ret, 0, strlen($ret) - 2);
    }

    protected function get_id_view()
    {
        $dbc = CTDB::get();
        $nameP = $dbc->prepare("SELECT RecipeName, Instructions FROM Recipe WHERE RecipeID=?");
        $recipe= $dbc->getRow($nameP, array($this->id));
        $recipe['Instructions'] = $this->rtfToHtml($recipe['Instructions']);

        $prep = $dbc->prepare("SELECT i.ItemID, i.ItemName,
                v.Quantity1, v.PreInstr, v.PostInstr, u.UnitSing
            FROM Recipe AS r
                INNER JOIN RecpItems AS m ON r.RecipeID=m.RecipeID
                INNER JOIN Inv AS i ON m.ItemID=i.ItemID
                LEFT JOIN RecpInv AS v ON r.RecipeID=v.RecipeID AND m.ItemID=v.ItemID
                LEFT JOIN Units AS u ON v.UnitID1=u.UnitID
            WHERE r.RecipeID=?
            ORDER BY v.RecordID"); 
        $res = $dbc->execute($prep, array($this->id));
        $list = '<ul>';
        while ($row = $dbc->fetchRow($res)) {
            if (!$row['Quantity1']) {
                $row['Quantity1'] = '';
                $row['UnitSing'] = '';
            }
            if (strstr($row['Quantity1'], '.')) {
                $row['Quantity1'] = sprintf('%.2f', $row['Quantity1']);
            }
            $list .= sprintf('<li>%s %s %s %s %s</li>',
                $row['Quantity1'], $row['UnitSing'], $row['PreInstr'], $row['ItemName'], $row['PostInstr']);
        }
        $list .= '</ul>';

        $ing = $this->getIngredientList($dbc, $this->id);
        $allergens = $this->getAllergens($dbc, $this->id);

        return <<<HTML
<b>{$recipe['RecipeName']}</b><br />
{$list}
<p>
{$recipe['Instructions']}
</p>
<p>
<b>Ingredients</b>: {$ing}
<br />$allergens
</p>
HTML;
    }

    protected function get_view()
    {
        $dbc = CTDB::get();
        $res = $dbc->query("SELECT RecipeID, RecipeName FROM Recipe ORDER BY RecipeName");
        $list = '';
        while ($row = $dbc->fetchRow($res)) {
            $list .= sprintf('<a href="CTRecipes.php?id=%d">%s</a>', $row['RecipeID'], $row['RecipeName']);
            $list .= '<br />';
        }

        return <<<HTML
{$list}
HTML;
    }
}

FannieDispatch::conditionalExec();

