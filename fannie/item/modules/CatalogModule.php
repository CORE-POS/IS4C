<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op, Duluth, MN

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

/**
  @class CatalogModule
  This is a non-editing module to provide greater visibility into
  what vendor data exists from an item, whether it's part of a
  SKU mapping, and breakdown relationships.
*/
class CatalogModule extends \COREPOS\Fannie\API\item\ItemModule 
{
    public function width()
    {
        return self::META_WIDTH_FULL;
    }

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {
        $upc = BarcodeLib::padUPC($upc);
        $dbc = $this->db();
        $listP = $dbc->prepare('SELECT vendorName, COUNT(*) AS num
            FROM vendorItems AS i 
                INNER JOIN vendors AS v ON i.vendorID=v.vendorID
            WHERE i.upc=?
            GROUP BY vendorName');
        $vendors = array();
        $listR = $dbc->execute($listP, array($upc));
        while ($listW = $dbc->fetchRow($listR)) {
            $vendors[] = $listW['vendorName'] . '(' . $listW['num'] . ')';
        }
        $vendors = count($vendors) == 0 ? 'None' : implode(',', $vendors);

        $parentP = $dbc->prepare('
            SELECT a.upc, a.brand, a.description
            FROM VendorBreakdowns AS b
                INNER JOIN vendorItems AS v ON v.sku=b.sku AND v.vendorID=b.vendorID
                INNER JOIN products AS a ON b.upc=a.upc AND b.vendorID=a.default_vendor_id
            WHERE v.upc=?');
        $parentR = $dbc->execute($parentP, array($upc));
        $parent = '';
        if ($row = $dbc->fetchRow($parentR)) {
            $parent = sprintf('This item breaks down into <a href="ItemEditorPage.php?searchupc=%s">%s</a> %s %s<br />',
                $row['upc'], $row['upc'], $row['brand'], $row['description']);
        }

        $childP = $dbc->prepare('
            SELECT v.upc, v.brand, v.description
            FROM VendorBreakdowns AS b
                INNER JOIN vendorItems AS v ON v.sku=b.sku AND v.vendorID=b.vendorID
            WHERE b.upc=?');
        $childR = $dbc->execute($childP, array($upc));
        $child = '';
        if ($row = $dbc->fetchRow($childR)) {
            $child = sprintf('This item is part of <a href="ItemEditorPage.php?searchupc=%s">%s</a> %s %s<br />',
                $row['upc'], $row['upc'], $row['brand'], $row['description']);
        }

        $mappedP = $dbc->prepare('SELECT v.sku FROM vendorSKUtoPLU AS v WHERE v.upc=?');
        $mappedR = $dbc->execute($mappedP, array($upc));
        $mapped = $dbc->numRows($mappedR) > 0 ? 'This is a SKU-mapped item<br />' : '';

        $ret = '';
        $ret = '<div id="CatalogFieldset" class="panel panel-default">';
        $ret .=  "<div class=\"panel-heading\">
                <a href=\"\" onclick=\"\$('#CatalogContents').toggle();return false;\">
                Vendor Catalog
                </a></div>";
        $css = ($expand_mode == 1) ? '' : ' collapse';
        $ret .= '<div id="CatalogContents" class="panel-body' . $css . '">';

        $ret .= 'In Vendor catalog(s): ' . $vendors . '<br />'
            . $mapped
            . $parent
            . $child; 

        $ret .= '</div>' . '<!-- /#CatalogContents -->';
        $ret .= '</div>' . '<!-- /#CatalogFieldset -->';

        return $ret;
    }
}

