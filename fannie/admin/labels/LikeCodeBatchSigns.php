<?php

include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class LikeCodeBatchSigns extends FannieRESTfulPage
{
    protected $title = "Fannie : Like Code Batch Signs";
    protected $header = "Like Code Batch Signs";

    public $discoverable = false;

    protected function post_id_handler()
    {
        $mod  = FormLib::get('sign');
        if (substr($mod, 0, 7) == 'Legacy:') {
            COREPOS\Fannie\API\item\signage\LegacyWrapper::setWrapped(substr($mod, 7));
            $mod = 'COREPOS\\Fannie\\API\\item\\signage\\LegacyWrapper';
        }
        $mod = str_replace('-', '\\', $mod);
        $brands = FormLib::get('brand');
        $items = FormLib::get('desc');
        $lcs = FormLib::get('lc', array());
        $prices = FormLib::get('price');
        $scales = FormLib::get('scale');
        $origins = FormLib::get('origin');
        $excludes = FormLib::get('exclude', array());
        $data = array();
        for ($i=0; $i<count($lcs); $i++) {
            if (in_array($lcs[$i], $excludes)) {
                continue;
            }
            $data[] = array(
                'upc' => '',
                'description' => $items[$i],
                'posDescription' => $items[$i],
                'brand' => $brands[$i],
                'normal_price' => $prices[$i],
                'units' => 1,
                'size' => '',
                'sku' => '',
                'vendor' => '',
                'scale' => $scales[$i],
                'numflag' => 0,
                'startDate' => '',
                'endDate' => '',
                'originName' => $origins[$i],
                'originShortName' => $origins[$i],
            );
        }

        $obj = new $mod($data, 'provided');
        $obj->drawPDF();

        return false;
    }

    private function getSignOpts()
    {
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
        foreach ($mods as $m) {
            $name = $m;
            if (strstr($m, '\\')) {
                $pts = explode('\\', $m);
                $name = $pts[count($pts)-1];
            }
            if ($name === 'LegacyWrapper') continue;
            $opts[$m] = $name;
        }

        $ret = '';
        foreach ($opts as $mod => $name) {
            $ret .= sprintf('<option value="%s">%s</option>',
                str_replace('\\', '-', $mod), $name);
        }

        return $ret;
    }

    protected function get_id_view()
    {
        $query = 'SELECT * FROM batchList WHERE batchID=?';
        if (!FormLib::get('all')) {
            $query .= ' AND signMultiplier=0';
        }
        $prep = $this->connection->prepare($query);
        $res = $this->connection->execute($prep, array($this->id));
        $prodP = $this->connection->prepare('SELECT 
            p.description, p.brand, x.description as uDesc, x.brand as uBrand, p.scale
            FROM upcLike AS u
                INNER JOIN products AS p ON p.upc=u.upc
                LEFT JOIN productUser AS x on u.upc=x.upc
            WHERE u.likeCode=?
            ORDER BY x.description DESC, p.description DESC');
        $lcP = $this->connection->prepare("SELECT * FROM likeCodes WHERE likeCode=?");
        $mapP = $this->connection->prepare("SELECT * FROM LikeCodeActiveMap WHERE likeCode=? AND storeID=?");
        $store = FormLib::get('store', COREPOS\Fannie\API\lib\Store::getIdByIp());
        $stores = FormLib::storePicker('store', false, 'toggleAll();');
        $all = FormLib::get('all') ? 'checked' : '';
        $table = '';
        while ($row = $this->connection->fetchRow($res)) {
            $likeCode = substr($row['upc'], 2);
            $prod = $this->connection->getRow($prodP, array($likeCode));
            $lc = $this->connection->getRow($lcP, array($likeCode));
            $map = $this->connection->getRow($mapP, array($likeCode, $store));
            if ($lc['organic']) {
                $prod['uBrand'] = 'Organic';
            }
            $table .= sprintf('<tr><td><a href="../../item/likecodes/LikeCodeEditor.php?start=%d">%d</a>
                <input type="hidden" name="lc[]" value="%s" />
                <input type="hidden" name="price[]" value="%s" />
                <input type="hidden" name="scale[]" value="%s" />
                </td>
                <td><input type="text" name="brand[]" class="form-control input-sm" value="%s" /></td>
                <td><input type="text" name="desc[]" class="form-control input-sm" value="%s" /></td>
                <td><input type="text" name="origin[]" class="form-control input-sm" value="%s" /></td>
                <td class="orgStatus"><a href="" onclick="toggleOrganic(event); return false;">%s</a></td>
                <td class="defaultSign"><a href="" onclick="toggleSign(event); return false;">%s</a></td>
                <td><input type="checkbox" class="exclude" name="exclude[]" value="%s" /></td>
                </tr>',
                $likeCode,
                $likeCode,
                $likeCode,
                $row['salePrice'],
                $prod['scale'],
                ($prod['uBrand'] ? $prod['uBrand'] : $prod['brand']),
                ($prod['uDesc'] ? ucwords(strtolower($prod['uDesc'])) : ucwords(strtolower($prod['description']))),
                ($lc['signOrigin'] ? $lc['origin'] : ''),
                ($lc['organic'] ? 'Organic' : 'Non-Organic'),
                $map['defaultSign'],
                $likeCode
            );
        }
        $signs = $this->getSignOpts();
        $all = FormLib::get('all') ? 'checked' : '';

        return <<<HTML
<form method="post">
<p class="form-inline">
<input type="hidden" name="id" id="id" value="{$this->id}" />
{$stores['html']}
<select name="sign" id="sign" class="form-control">{$signs}</select>
<!--<label><input type="checkbox" {$all} name="all" value="1" /> All</label>-->
<button type="submit" class="btn btn-default">Print</button>
<label><input type="checkbox" id="all" onchange="toggleAll();" {$all} /> Show all items</label>
</p>
<table class="table">
<tr>
    <th>LC</th>
    <th>Brand</th>
    <th>Item</th>
    <th>Origin</th>
    <th>Organic</th>
    <th>Default Sign</th>
    <th>Exclude</th>
</tr>
{$table}
</table>
</form>
<script type="text/javascript">
var organicMode = 99;
var signMode = 99;
function toggleAll() {
    var dstr = '?id=' + $('#id').val();
    dstr += '&store=' + $('select[name=store]').val();
    dstr += '&all=' + ($('#all').prop('checked') ? '1' : '0');
    location= 'LikeCodeBatchSigns.php' + dstr;
}
function orgMatches(str) {
    if (organicMode == 99) {
        return true;
    } else if (organicMode == 1 && str == 'Organic') {
        return true;
    } else if (organicMode == 0 && str == 'Non-Organic') {
        return true;
    }

    return false;
}
function signMatches(str) {
    if (signMode == 99) {
        return true;
    } else if (signMode == str) {
        return true;
    }

    return false;
}
function redoChecks() {
    $('td.orgStatus').each(function() {
        var org = $(this).text();
        var sign = $(this).closest('tr').find('.defaultSign').text();
        if (orgMatches(org) && signMatches(sign)) {
            $(this).closest('tr').find('.exclude').prop('checked', false);
        } else {
            $(this).closest('tr').find('.exclude').prop('checked', true);
        }
    });
}
function toggleOrganic(ev) {
    if (ev.target.textContent == 'Non-Organic' && organicMode != 0) {
        organicMode = 0;
    } else if (ev.target.textContent == 'Non-Organic') {
        organicMode = 99;
    } else if (ev.target.textContent == 'Organic'&& organicMode != 1) {
        organicMode = 1;
    } else if (ev.target.textContent == 'Organic') {
        organicMode = 99;
    }
    redoChecks();
}
function toggleSign(ev) {
    if (signMode == ev.target.textContent) {
        signMode = 99;
    } else {
        signMode = ev.target.textContent;
        $('#sign option').prop('selected', false);
        $('#sign option').each(function() {
            if ($(this).text() == signMode) {
                $(this).prop('selected', true);
            }
        });
    }
    redoChecks();
}
</script>
HTML;
    }

    public function unitTest($phpunit)
    {
        $this->id = 1;
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }
}

FannieDispatch::conditionalExec();

