<?php

class WicModule extends \COREPOS\Fannie\API\item\ItemModule 
{
    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();
        $panelBody = '';
        $collapse = '';
        $official = $this->officialItem($dbc, $upc);
        if ($official) {
            $panelBody = $official;
        } else {
            $alias = $this->aliasItem($dbc, $upc);
            if ($alias) {
                $panelBody = $alias;
            } else {
                $panelBody = $this->nonWic();
                $collapse = 'collapse';
            }
        }
        $ret = '<div id="WicFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#WicFieldsetContent').toggle();return false;\">
                EWic Status</a>
                </div>";
        $ret .= '<div id="WicFieldsetContent" class="panel-body ' . $collapse . '">';
        $ret .= $panelBody;
        $ret .= '</div></div>';

        return $ret;
    }

    private function officialItem($dbc, $upc)
    {
        $prep = $dbc->prepare("
            SELECT i.upc, c.name AS cat, s.name AS sub
            FROM EWicItems AS i
                LEFT JOIN EWicCategories AS c ON i.eWicCategoryID=c.eWicCategoryID
                LEFT JOIN EWicSubCategories AS s ON i.eWicSubCategoryID=s.eWicSubCategoryID AND i.eWicCategoryID=s.eWicCategoryID
            WHERE i.upc=?
                AND alias IS NULL");
        $row = $dbc->getRow($prep, array($upc));
        if ($row) {
            return 'This item is on the <a href="../modules/plugins2.0/WIC/WicAplReport.php">official list</a>'
                . '<br />'
                . 'Category: ' . $row['cat']
                . '<br />'
                . 'Subcategory: ' . ($row['sub'] ? $row['sub'] : 'n/a');
        }

        return false;
    }
    private function aliasItem($dbc, $upc)
    {
        $prep = $dbc->prepare("
            SELECT i.upc, i.alias, c.name AS cat, s.name AS sub
            FROM EWicItems AS i
                LEFT JOIN EWicCategories AS c ON i.eWicCategoryID=c.eWicCategoryID
                LEFT JOIN EWicSubCategories AS s ON i.eWicSubCategoryID=s.eWicSubCategoryID AND i.eWicCategoryID=s.eWicCategoryID
            WHERE i.upc=?
                AND alias IS NOT NULL");
        $row = $dbc->getRow($prep, array($upc));
        if ($row) {
            $otherP = $dbc->prepare("SELECT description FROM products WHERE upc=?");
            $other = $dbc->getValue($otherP, array($row['alias']));
            return 'This item is <a href="../modules/plugins2.0/WIC/WicAplReport.php">mapped</a> onto another item'
                . '<br />'
                . $row['alias'] . ' ' . $other
                . '<br />'
                . 'Category: ' . $row['cat']
                . '<br />'
                . 'Subcategory: ' . ($row['sub'] ? $row['sub'] : 'n/a');
        }

        return false;
    }

    public function getFormJavascript($upc)
    {
        return <<<JAVASCRIPT
function wicAutocomplete() {
    if (window.$) {
        console.log('got jquery');
        $(document).ready(function () {
            bindAutoComplete('#wicMap', '../ws/', 'ewic');
        });
    } else {
        setTimeout(wicAutocomplete, 50);
    }
}
wicAutocomplete();
JAVASCRIPT;
    }
    private function nonWic()
    {
        return <<<HTML
<em>This is not currently an eligible item</em>
<div class="form-group">
    <label>Create Mapping</label>
    <input type="text" class="form-control" name="wicMap" id="wicMap" />
</div>
HTML;
    }

    public function SaveFormData($upc)
    {
        $mapped = FormLib::get('wicMap');
        if (is_numeric($mapped) && strlen($mapped) == 13) {
            $dbc = $this->db();
            $itemP = $dbc->prepare("SELECT * FROM EWicItems WHERE upc=? AND alias IS NULL");
            $item = $dbc->getRow($itemP, array($mapped));
            if ($item) {
                $insP = $dbc->prepare("INSERT INTO EWicAliases (upc, aliasedUPC) VALUES (?, ?)");
                $dbc->execute($insP, array($upc, $mapped));
                $insP = $dbc->prepare("INSERT INTO EWicItems (upc, upcCheck, alias, eWicCategoryID, eWicSubCategoryID)
                    VALUES (?, ?, ?, ?, ?)");
                $dbc->execute($insP, array($upc, $item['upcCheck'], $mapped, $item['eWicCategoryID'], $item['eWicSubCategoryID']));
            }
        }
    }
}

