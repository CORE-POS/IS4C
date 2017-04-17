<?php
/*******************************************************************************

    Copyright 2009,2010 Whole Foods Co-op

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class BatchSignStylesPage extends FannieRESTfulPage 
{
    protected $must_authenticate = true;
    protected $auth_classes = array('batches','batches_audited');
    protected $title = 'Sales Sign Style';
    protected $header = 'Sales Sign Style';

    public $description = '[Sale Sign Style] manages how sale signage pricing appears.';

    protected function post_id_handler()
    {
        $batchList = new BatchListModel($this->connection);
        $batchList->batchID($this->id);
        try {
            $upc = $this->form->upc;
            $style = $this->form->style;
            for ($i=0; $i<count($upc); $i++) {
                $batchList->upc($upc[$i]);
                $batchList->signMultiplier($style[$i]);
                $batchList->save();
            }
        } catch (Exception $ex) {
        }

        return filter_input(INPUT_SERVER, 'PHP_SELF') . '?id=' . $this->id;
    }

    private function getStyles()
    {
        $mult = array_map(function($i){ return $i . '/X'; }, range(0,10));
        $mult = array_filter($mult, function($i){ return $i != '0/X' && $i != '1/X'; });

        $mult[1] = 'Normal';
        $mult['-1'] = '$X off';
        $mult['-2'] = 'X% off';
        $mult['-3'] = 'BOGO';
        $mult['-4'] = 'Exact';

        return $mult;
    }

    private function styleToOptions($styles, $val=1)
    {
        return array_reduce(array_keys($styles), function($carry, $key) use ($val, $styles) {
            return $carry . sprintf('<option %s value="%d">%s</option>',
                ($val == $key ? 'selected' : ''),
                $key, $styles[$key]);
        }, '');
    }

    private function getUpcItems()
    {
        $query = '
            SELECT l.upc,
                CASE WHEN u.brand IS NOT NULL AND u.brand <> \'\' THEN u.brand ELSE p.brand END as brand,
                CASE WHEN u.description IS NOT NULL AND u.description <> \'\' THEN u.description ELSE p.description END as description,
                p.normal_price,
                l.salePrice,
                l.signMultiplier
            FROM batchList AS l
                ' . DTrans::joinProducts('l', 'p', 'INNER') . '
                LEFT JOIN productUser AS u ON l.upc=u.upc
            WHERE l.batchID=? ';
        $args = array($this->id);
        if ($this->config->get('STORE_MODE') === 'HQ') {
            $query .= ' AND p.store_id=? ';
            $args[] = $this->config->get('STORE_ID');
        }
        $query .= ' ORDER BY l.upc';
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        $rows = array();
        while ($row = $this->connection->fetchRow($res)) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function getLcItems($rows)
    {
        $query = '
            SELECT l.upc,
                \'\' AS brand,
                c.likeCodeDesc AS description,
                0 AS normal_price,
                l.salePrice,
                l.signMultiplier
            FROM batchList AS l
                LEFT JOIN likeCodes AS c ON l.upc=' . $this->connection->concat("'LC'", 'c.likeCode', '') . '
            WHERE l.batchID=? 
                AND l.upc LIKE \'LC%\'';
        $args = array($this->id);
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $rows[] = $row;
        }

        return $rows;
    }

    protected function get_id_view()
    {
        $rows = $this->getUpcItems();
        $rows = $this->getLcItems($rows);

        $ret = '<form method="post">
            <table class="table table-bordered"><thead><tr>
            <th>UPC</th><th>Brand</th><th>Description</th>
            <th>Normal Price</th><th>Sale Price</th><th>Sign</th>
            </tr></thead><tbody>';

        $styles = $this->getStyles();
        foreach ($rows as $row) {
            $ret .= sprintf('<tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%.2f</td>
                <td>%.2f</td>
                <td><select class="form-control" name="style[]">
                %s
                </select>
                <input type="hidden" name="upc[]" value="%s" />
                </td>
                </tr>',
                \COREPOS\Fannie\API\lib\FannieUI::itemEditorLink($row['upc']),
                $row['brand'],
                $row['description'],
                $row['normal_price'],
                $row['salePrice'],
                $this->styleToOptions($styles, $row['signMultiplier']),
                $row['upc']
            );
        }
        $ret .= '</tbody></table>
        <p>
            <button type="submit" class="btn btn-default btn-core">Save</button>
            <a href="EditBatchPage.php?id=' . $this->id . '" class="btn btn-default btn-reset">Back to Batch</a>
        </p>
        <input type="hidden" name="id" value="' . $this->id . '" />
        </form>';

        return $ret;
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }

    public function helpContent()
    {
        return '<p>There are a variety of ways to format prices when generating
sale signage via Office. A sales batch can optionally specify how prices should
be formatted.</p>
        <p>In the default case, <em>Normal</em>, the system tries to guess whether
or not the price should be displayed with a multiplier (e.g., 3/$1) based on what
potential multipliers match the price. If no matches are found the price itself
is displayed.</p>
        <p>The <em>2/X</em> through <em>10/X</em> are used to set a specific multiplier.
If for example an item is on sale for $1, it could be advertised as 3/$3 or 5/$5.</p>
        <p>The <em>$X off</em> option will display the amount saved, in dollars, rather
than the sale prices. The <em>%X off</em> option does the same as a percentage.</p>
        <p><em>BOGO</em> will actually write "Buy one get one" on the sign. The
<em>Exact</em> option disables the default multiplier guessing. An item set to exact
will always display the exact sale price.</p>';
    }
}

FannieDispatch::conditionalExec();

