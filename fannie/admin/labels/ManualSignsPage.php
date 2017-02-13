<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

use COREPOS\Fannie\API\item\ItemText;

require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class ManualSignsPage extends FannieRESTfulPage 
{

    protected $header = 'Create Generic Signs';
    protected $title = 'Create Generic Signs';
    public $description = '[Generic Signs] builds signage PDFs from user-inputted text.';
    private $items = array();

    public function preprocess()
    {
        $this->addRoute('post<u>');
        return parent::preprocess();
    }

    protected function post_u_handler()
    {
        list($inStr, $args) = $this->connection->safeInClause($this->u);
        $prep = $this->connection->prepare('
            SELECT p.upc,
                ' . ItemText::longBrandSQL() . ',
                ' . ItemText::longDescriptionSQL() . ',
                ' . ItemText::signSizeSQL() . '
            FROM products AS p
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
            WHERE p.upc IN (' . $inStr . ')
                AND p.store_id=?
            ORDER BY p.upc');
        $args[] = $this->config->get('STORE_ID');
        $res = $this->connection->execute($prep, $args);
        while ($row = $this->connection->fetchRow($res)) {
            $this->items[] = $row;
        }

        return true;
    }

    public function post_handler()
    {
        $brands = FormLib::get('brand');
        $descriptions = FormLib::get('description');
        $prices = FormLib::get('price');
        $scales = FormLib::get('scale');
        $sizes = FormLib::get('size');
        $origins = FormLib::get('origin');
        $start = FormLib::get('start');
        $end = FormLib::get('end');

        $items = array();
        for ($i=0; $i<count($descriptions); $i++) {
            if ($descriptions[$i] == '') {
                continue;
            }
            $items[] = array(
                'upc' => '',
                'description' => $descriptions[$i],
                'posDescription' => $descriptions[$i],
                'brand' => $brands[$i],
                'normal_price' => $prices[$i],
                'units' => 1,
                'size' => $sizes[$i],
                'sku' => '',
                'vendor' => '',
                'scale' => $scales[$i],
                'numflag' => 0,
                'startDate' => $start[$i],
                'endDate' => $end[$i],
                'originName' => $origins[$i],
                'originShortName' => $origins[$i],
            );
        }

        $class = FormLib::get('signmod');
        $obj = new $class($items, 'provided');
        $obj->drawPDF();

        return false;
    }

    protected function post_u_view()
    {
        return $this->get_view();
    }

    public function get_view()
    {
        $ret = '';
        $ret .= '<form target="_blank" action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post" id="signform">';
        $mods = FannieAPI::listModules('\COREPOS\Fannie\API\item\FannieSignage');
        sort($mods);

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<label>Layout</label>: 
            <select name="signmod" class="form-control" >';
        foreach ($mods as $m) {
            $name = $m;
            if (strstr($m, '\\')) {
                $pts = explode('\\', $m);
                $name = $pts[count($pts)-1];
            }
            $ret .= sprintf('<option %s value="%s">%s</option>',
                        ($m == $this->config->get('DEFAULT_SIGNAGE') ? 'selected' : ''),
                        $m, $name);
        }
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" name="pdf" value="Print" 
                    class="btn btn-default">Print</button>';
        $ret .= '</div>';
        $ret .= '<hr />';

        $ret .= <<<HTML
<table class="table table-bordered table-striped small">
    <thead>
    <tr>
        <th>Brand</th>
        <th>Description</th>
        <th>Price</th>
        <th>Scale</th>
        <th>Size</th>
        <th>Origin/Reg. Price</th>
        <th>Start Date</th>
        <th>End Date</th>
    </tr>
    </thead>
    <tbody>
    <tr class="info">
        <td>
            <input type="text" class="form-control input-sm" placeholder="Change All"
                onchange="if (this.value !== '') $('.input-brand').val(this.value);" />
        </td>
        <td>
            <input type="text" class="form-control input-sm" placeholder="Change All"
                onchange="if (this.value !== '') $('.input-description').val(this.value);" />
        </td>
        <td>
            <input type="text" class="form-control price-field input-sm" placeholder="Change All"
                onchange="if (this.value !== '') $('.input-price').val(this.value);" />
        </td>
        <td>
            <select class="form-control input-sm" onchange="if (this.value !== '-1') $('.input-scale').val(this.value);">
                <option value="-1">Change All</option>
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
        <td>
            <input type="text" class="form-control input-sm" placeholder="Change All"
                onchange="if (this.value !== '') $('.input-size').val(this.value);" />
        </td>
        <td>
            <input type="text" class="form-control input-sm" placeholder="Change All"
                onchange="if (this.value !== '') $('.input-origin').val(this.value);" />
        </td>
        <td>
            <input type="text" class="form-control input-sm date-field" placeholder="Change All"
                onchange="if (this.value !== '') $('.input-start').val(this.value);" />
        </td>
        <td>
            <input type="text" class="form-control input-sm date-field" placeholder="Change All"
                onchange="if (this.value !== '') $('.input-end').val(this.value);" />
        </td>
    </tr>
HTML;
        $max = count($this->items) > 32 ? count($this->items) : 32;
        for ($i=0; $i<$max; $i++) {
            $brand = isset($this->items[$i]) ? $this->items[$i]['brand'] : '';
            $desc = isset($this->items[$i]) ? $this->items[$i]['description'] : '';
            $size = isset($this->items[$i]) ? $this->items[$i]['size'] : '';
            $ret .= <<<HTML
<tr>
    <td><input type="text" name="brand[]" class="form-control input-sm input-brand" value="{$brand}" /></td>
    <td><input type="text" name="description[]" class="form-control input-sm input-description" value="{$desc}" /></td>
    <td><input type="text" name="price[]" class="form-control input-sm input-price price-field" /></td>
    <td><select name="scale[]" class="form-control input-sm input-scale">
        <option value="0">No</option>
        <option value="1">Yes</option>
    </select></td>
    <td><input type="text" name="size[]" class="form-control input-sm input-size" value="{$size}" /></td>
    <td><input type="text" name="origin[]" class="form-control input-sm input-origin" /></td>
    <td><input type="text" name="start[]" class="form-control input-sm input-start date-field" /></td>
    <td><input type="text" name="end[]" class="form-control input-sm input-end date-field" /></td>
</tr>
HTML;
        }
        $ret .= '</tbody></table>';

        return $ret;
    }

    public function helpContent()
    {
        return '
            <p>
            This tool creates a sign PDF based on the selected and layout
            and the text entered in the table. It is not necessary to fill
            out all rows; only rows with descriptions will get signs. The
            single non-text field, scale, controls whether a "/lb" indication
            should be attached to the price.
            <p>
            The first row of the table is provided strictly for quick edits.
            No sign is printed for the first row but any changes made to the
            first row are automatically copied through to the other rows.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

