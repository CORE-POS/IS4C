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
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class ManualSignsPage extends FannieRESTfulPage 
{

    protected $header = 'Create Generic Signs';
    protected $title = 'Create Generic Signs';
    public $description = '[Generic Signs] builds signage PDFs from user-inputted text.';
    private $items = array();

    public function preprocess()
    {
        $this->addRoute('post<u>', 'get<queueID>');
        return parent::preprocess();
    }

    protected function delete_id_handler()
    {
        $prep = $this->connection->prepare("DELETE FROM shelftags WHERE id=?");
        $this->connection->execute($prep, array($this->id));

        return 'QueueTagsByLC.php';
    }

    protected function post_u_handler()
    {
        list($inStr, $args) = $this->connection->safeInClause($this->u);
        $prep = $this->connection->prepare('
            SELECT p.upc,
                ' . ItemText::longBrandSQL() . ',
                ' . ItemText::longDescriptionSQL() . ',
                ' . ItemText::signSizeSQL() . ',
                p.scale,
                NULL AS startDate,
                NULL AS endDate,
                \'\' AS price,
                \'\' AS origin
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

    protected function get_queueID_handler()
    {
        $prep = $this->connection->prepare('
            SELECT p.upc,
                ' . ItemText::longBrandSQL() . ',
                ' . ItemText::longDescriptionSQL() . ',
                ' . ItemText::signSizeSQL() . ',
                CASE WHEN u.upc IS NULL OR u.upc=\'\' THEN 0 ELSE 1 END AS signText,
                p.scale,
                CASE WHEN p.discounttype=1 AND p.special_price > 0 THEN p.special_price ELSE p.normal_price END AS price,
                p.normal_price,
                CASE WHEN p.discounttype=1 AND p.special_price > 0 THEN p.start_date ELSE NULL END AS startDate,
                CASE WHEN p.discounttype=1 AND p.special_price > 0 THEN p.end_date ELSE NULL END AS endDate,
                \'\' AS origin
            FROM products AS p
                INNER JOIN shelftags AS s ON p.upc=s.upc
                LEFT JOIN productUser AS u ON p.upc=u.upc
                LEFT JOIN vendorItems AS v ON p.upc=v.upc AND p.default_vendor_id=v.vendorID
            WHERE s.id=?
                AND p.store_id=?
            ORDER BY p.upc');
        $args = array($this->queueID, $this->config->get('STORE_ID'));
        $res = $this->connection->execute($prep, $args);
        $prevUPC = false;
        $lcP = $this->connection->prepare("SELECT origin, signOrigin, organic, likeCodeDesc FROM likeCodes AS l
            INNER JOIN upcLike AS u ON l.likeCode=u.likeCode
            WHERE u.upc=?");
        while ($row = $this->connection->fetchRow($res)) {
            if ($row['upc'] != $prevUPC) {
                if (($this->queueID == 6 || $this->queueID == 1042)  && $this->config->get('COOP_ID') == 'WFC_Duluth') {
                    $lcRow = $this->connection->getRow($lcP, array($row['upc']));
                    $row['origin'] = is_array($lcRow) && $lcRow['signOrigin'] ? $lcRow['origin'] : '';
                    if ($row['normal_price'] > $row['price']) {
                        $row['origin'] .= '/' . $row['normal_price'];
                    }
                    if (!$row['signText'] && is_array($lcRow) && $lcRow['likeCodeDesc']) {
                        $row['description'] = $lcRow['likeCodeDesc'];
                    }
                    $row['brand'] = is_array($lcRow) && $lcRow['organic'] ? 'ORGANIC' : '';
                } else {
                    // preserve normal behavior
                    $row['price'] = $row['normal_price'];
                    $row['startDate'] = '';
                    $row['endDate'] = '';
                }
                $this->items[] = $row;
            }
            $prevUPC = $row['upc'];
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
        $exclude = FormLib::get('exclude', array());
        $mult = FormLib::get('mult');
        $smartType = FormLib::get('smartType', false);

        $items = array();
        for ($i=0; $i<count($descriptions); $i++) {
            if ($descriptions[$i] == '') {
                continue;
            } elseif (in_array($i, $exclude)) {
                //continue;
            }
            $item = array(
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
                'smartType' => is_array($smartType) ? $smartType[$i] : '',
            );
            if (strstr($origins[$i], '/')) {
                list($origin, $regPrice) = explode('/', $origins[$i], 2);
                $item['originName'] = trim($origin);
                $item['originShortName'] = trim($origin);
                $item['nonSalePrice'] = trim($regPrice);
            }
            for ($j=0; $j<$mult[$i]; $j++) {
                $items[] = $item;
            }
        }

        $class = FormLib::get('signmod');
        if (substr($class, 0, 7) == "Legacy:") {
            COREPOS\Fannie\API\item\signage\LegacyWrapper::setWrapped(substr($class, 7));
            $class = 'COREPOS\\Fannie\\API\\item\\signage\\LegacyWrapper';
        }
        $obj = new $class($items, 'provided');
        $obj->drawPDF();

        return false;
    }

    protected function get_queueID_view()
    {
        return $this->get_view();
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
        $enabled = $this->config->get('ENABLED_SIGNAGE');
        if (count($enabled) > 0) {
            $mods = array_filter($mods, function ($i) use ($enabled) {
                return in_array($i, $enabled) || in_array(str_replace('\\', '-', $i), $enabled);
            });
        }
        sort($mods);
        $tagEnabled = $this->config->get('ENABLED_TAGS');
        foreach (COREPOS\Fannie\API\item\signage\LegacyWrapper::getLayouts() as $l) {
            if (in_array($l, $tagEnabled) && count($tagEnabled) > 0) {
                $mods[] = 'Legacy:' . $l;
            }
        }
        $offset = '';
        $clearBtn = '';
        if ((FormLib::get('queueID') == 6 || FormLib::get('queueID')  == 1042) && $this->config->get('COOP_ID') == 'WFC_Duluth') {
            $mods = array('Legacy:WFC Produce SmartSigns','Produce4UpP', 'Produce4UpSingle', 'Legacy:WFC Produce', 'Legacy:WFC Produce Single');
            $offset = 'checked';
            $clearBtn = '<a href="ManualSignsPage.php?_method=delete&id=' . FormLib::get('queueID') . '"
                class="btn btn-default pull-right">Clear Queue</a>';
        }

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<label>Layout</label>: 
            <select name="signmod" id="signmod" class="form-control" >';
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
                    class="btn btn-default">Print</button>
                 <label><input type="checkbox" name="offset" value="1" ' . $offset . ' /> Offset</label>';
        $jsCloneBtn = <<<JAVASCRIPT
$('tr.item-row').each(function(){
    let clone = $(this).html();
    clone = '<tr class=\'item-row\'>' + clone + '</tr>';
    let descText = $(this).find('td:eq(1)').find('input').val();
    if (descText.length > 0) {
        $(this).after(clone);
        console.log(clone);
    }
});
JAVASCRIPT;
        $ret .= ' | <label><a id="clone-trs" onclick="'.$jsCloneBtn.'">Duplicate Rows</a></label>';
        $ret .= ' | <label>Smart Signs Override</label>:&nbsp; <input type="checkbox" class="smartOverride" name="CoopDeals" id="smartCoopDealOverr" value=1> <label for="smartCoopDealOverr">Coop Deals</label> |';
        $ret .= '&nbsp; <input type="checkbox" class="smartOverride" name="ChaChing" id="smartChaChingOverr" > <label for="smartChaChingOverr" value=1>Cha-Ching!</label> |';
        $ret .= '&nbsp; <input type="checkbox" class="smartOverride" name="FreshDeals" id="smartFreshDealOverr" > <label for="smartFreshDealOverr" value=1>Fresh Deals<label>&nbsp;|';

        $ret .= '&nbsp; <input type="checkbox" class="smartOverride" name="Regular" id="smartRegularOverr" > <label for="smartRegularOverr" value=1>Regular<label>&nbsp;|';
        $ret .= '&nbsp; <input type="checkbox" class="smartOverride" name="RegularLocal" id="smartRegularLocalOverr" > <label for="smartRegularLocalOverr" value=1>Regular Local<label>&nbsp;|';

        $ret .= '&nbsp; <input type="checkbox" class="smartOverride" name="Organic" id="smartOrganicOverr" > <label for="smartOrganicOverr" value=1>Organic<label>&nbsp;|';
        $ret .= '&nbsp; <input type="checkbox" class="smartOverride" name="OrganicLocal" id="smartOrganicLocalOverr" > <label for="smartOrganicLocalOverr" value=1>Organic Local<label>';

        $ret .= $clearBtn;
        $ret .= '</div>';
        $ret .= '<hr />';

        $ret .= $this->formTableHeader();
        $ret .= $this->formTableBody($this->items);
        $ret .= '</tbody></table>';
        $ret .= '<input type="hidden" name="form_page" value="ManualSignsPage" />';

        $jsExec = <<<JAVASCRIPT
$('.exc').on('change', function(){
    let checked = $(this).is(':checked');
    if (checked === true) {
        $(this).closest('tr').find('.input-description').attr('disabled', true);
        $(this).closest('tr').find('.input-brand').attr('disabled', true);
        $(this).closest('tr').find('.input-price').attr('disabled', true);
        $(this).closest('tr').find('.input-scale').attr('disabled', true);
        $(this).closest('tr').find('.input-size').attr('disabled', true);
        $(this).closest('tr').find('.input-origin').attr('disabled', true);
        $(this).closest('tr').find('.input-start').attr('disabled', true);
        $(this).closest('tr').find('.input-end').attr('disabled', true);
    } else {
        $(this).closest('tr').find('.input-description').attr('disabled', false);
        $(this).closest('tr').find('.input-brand').attr('disabled', false);
        $(this).closest('tr').find('.input-price').attr('disabled', false);
        $(this).closest('tr').find('.input-scale').attr('disabled', false);
        $(this).closest('tr').find('.input-size').attr('disabled', false);
        $(this).closest('tr').find('.input-origin').attr('disabled', false);
        $(this).closest('tr').find('.input-start').attr('disabled', false);
        $(this).closest('tr').find('.input-end').attr('disabled', false);
    }
});
JAVASCRIPT;
        $this->addOnloadCommand($jsExec);

        $jsSmartOverr = <<<JAVASCRIPT
$('.smartOverride').on('click', function(){
    let id = $(this).attr('id');
    let type = $(this).attr('name');
    $('.smartOverride').each(function(){
        if (id != $(this).attr('id')) {
            let checked = $(this).is(':checked');
            if (checked) {
                $(this).trigger('click');
            }
        }
    });

    $('.smartTypeTd').each(function(){
        $(this).remove();
    });

    $('.item-row').each(function(){
        $(this).append("<td class=\"smartTypeTd\" style=\"display: none;\"><input type=\"text\" name=\"smartType[]\" value=\""+type+"\" /></td>");
    });
});
JAVASCRIPT;
        $this->addOnloadCommand($jsSmartOverr);

        return $ret;
    }

    private function formTableHeader()
    {
        if (FannieConfig::config('COOP_ID') == 'WFC_Duluth') {
            $ret = SignsLib::visualSignSelectHTML();
            $this->addOnloadCommand(SignsLib::visualSignSelectJS());
        }

        return <<<HTML
$ret
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
        <th>Exclude</th>
        <th>Multiple</th>
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
        <td>
            <input type="checkbox" onchange="$('.exc').prop('checked', $(this).prop('checked')).trigger('change');" />
        </td>
        <td>
            <input type="number" class="form-control input=sm" onchange="$('.mult').val($(this).val()).trigger('change');" value="1" />
        </td>
    </tr>
HTML;
    }

    private function formTableBody($items)
    {
        $max = count($items) > 32 ? count($items) : 32;
        $ret = '';
        for ($i=0; $i<$max; $i++) {
            $upc = isset($items[$i]) ? $items[$i]['upc'] : '';
            $brand = isset($items[$i]) ? $items[$i]['brand'] : '';
            $desc = isset($items[$i]) ? $items[$i]['description'] : '';
            $size = isset($items[$i]) ? $items[$i]['size'] : '';
            $price = isset($items[$i]) ? $items[$i]['price'] : '';
            $scaleY = isset($items[$i]) && $items[$i]['scale'] ? 'selected' : '';
            $origin = isset($items[$i]) ? $items[$i]['origin'] : '';
            $start = isset($items[$i]) ? $items[$i]['startDate'] : '';
            $end = isset($items[$i]) ? $items[$i]['endDate'] : '';
            $ret .= <<<HTML
<tr class="item-row">
    <td><input type="hidden" name="upc[]" class="upc" value="{$upc}"/>
        <input type="text" name="brand[]" class="form-control input-sm input-brand" value="{$brand}" /></td>
    <td style="min-width: 300px"><input type="text" name="description[]" class="form-control input-sm input-description" value="{$desc}" /></td>
    <td><input type="text" name="price[]" class="form-control input-sm input-price price-field" value="{$price}" /></td>
    <td><select name="scale[]" class="form-control input-sm input-scale">
        <option value="0">No</option>
        <option value="1" {$scaleY}>Yes</option>
    </select></td>
    <td><input type="text" name="size[]" class="form-control input-sm input-size" value="{$size}" /></td>
    <td><input type="text" name="origin[]" class="form-control input-sm input-origin" value="{$origin}" /></td>
    <td><input type="text" name="start[]" class="form-control input-sm input-start date-field" value="{$start}" /></td>
    <td><input type="text" name="end[]" class="form-control input-sm input-end date-field" value="{$end}" /></td>
    <td><input type="checkbox" class="exc" name="exclude[]" value="{$i}" /></td>
    <td><input type="number" name="mult[]" class="mult form-control input-sm" value="1"></td>
</tr>
HTML;
        }

        return $ret;
    }
    
    public function css_content()
    {

        $visualSelectCSS = SignsLib::visualSignSelectCSS();

        return <<<HTML
$visualSelectCSS
HTML;
    }

    public function javascript_content()
    {
        return <<<JAVASCRIPT
var lastChecked = null;
/*
    Shift Click Checkboxes
*/
var i = 0;
$('.exc').each(function(){
    $(this).attr('data-index', i);
    i++;
});
$('.exc').on("click", function(e){
    if(lastChecked && e.shiftKey) {
        var i = parseInt(lastChecked.attr('data-index'));
        var j = parseInt($(this).attr('data-index'));
        var checked = $(this).is(":checked");

        var low = i;
        var high = j;
        if (i>j){
            var low = j;
            var high = i;
        }

        for(var c = low; c < high; c++) {
            if (c != low && c!= high) {
                var check = checked ? true : false;
                $('input[data-index="'+c+'"').trigger('click');
            }
        }
    }
    lastChecked = $(this);
});
JAVASCRIPT;
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

