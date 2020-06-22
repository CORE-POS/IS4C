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

require(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../../classlib2.0/FannieAPI.php');
}
if (!class_exists('CTDB')) {
    include(__DIR__ . '/CTDB.php');
}

class SignsFromCheftec extends FannieRESTfulPage 
{

    protected $header = 'Create Signs from ChefTec';
    protected $title = 'Create Signs from ChefTec';
    public $description = '[Signs From ChefTec] builds signage PDFs from ChefTec DBMS.';
    private $items = array();

    public function preprocess()
    {
        $this->addRoute('post<u>', 'get<queueID>');
        return parent::preprocess();
    }

    public function post_handler()
    {
        $ids = FormLib::get('recipeID');

        $items = array();
        for ($i=0; $i<count($ids); $i++) {
            $item = array('recipeID' => $ids[$i]);
            $items[] = $item;
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
        if (FormLib::get('queueID') == 6 && $this->config->get('COOP_ID') == 'WFC_Duluth') {
            $mods = array('Produce4UpP', 'Produce4UpSingle', 'Legacy:WFC Produce');
            $offset = 'checked';
            $clearBtn = '<a href="SignsFromCheftec.php?_method=delete&id=' . FormLib::get('queueID') . '"
                class="btn btn-default pull-right">Clear Queue</a>';
        }

        $ret .= '<div class="form-group form-inline">';
        $ret .= '<label>Layout</label>: 
            <select name="signmod" class="form-control" >';
        foreach ($mods as $m) {
            $name = $m;
            if (strstr($m, '\\')) {
                $pts = explode('\\', $m);
                $name = $pts[count($pts)-1];
            }

        }

        $ret .= sprintf('<option %s value="%s">%s</option>',
                    'selected',
                    "Legacy:Cheftec Signs 4UP", 
                    "Legacy:Cheftec Signs 4UP");
        $ret .= '</select>';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<button type="submit" name="pdf" value="Print" 
                    class="btn btn-default">Print</button> ';
        $ret .= $clearBtn;
        $ret .= '</div>';
        $ret .= '<hr />';

        $ret .= $this->formTableBody($this->items);
        $ret .= '</tbody></table>';

        return $ret;
    }

    private function formTableBody($items)
    {
        $dbc = CTDB::get();

        $recipes = array();
        $res = $dbc->query("SELECT RecipeID, RecipeName FROM DataDir.dbo.Recipe");
        while ($row = $dbc->fetchRow($res)) {
            $name = $row['RecipeName'];
            $id = $row['RecipeID'];
            if (!in_array($name, $recipes)) {
                $recipes[$id] = $name;
            }
        }

        $formRecipe = "";
        foreach ($recipes as $id => $name) {
            if (!ctype_space($name)) {
                $formRecipe .= <<<HTML
<div class="form-group recipes">
    <input type="checkbox" name="recipeID[]" value=$id />
    <label for="recipeID[]">$name</label>
</div>
HTML;
            }
        }

        return <<<HTML
<div class="row">
    <div class="col-lg-4">
        <div class="form-group">
            <input type="text" class="form-control" id="search-name" placeholder="search" autofocus/>
        </div>
        $formRecipe
        $ret
    </div>
    <div class="col-lg-4"> </div>
    <div class="col-lg-4"></div>
</div>
HTML;
    }

    public function javascript_content()
    {
        return <<<JAVASCRIPT
$('#search-name').keyup(function(){
    var text = $(this).val();
    $('.recipes').each(function(){
        $(this).show();
    });
    $('.recipes').each(function(){
        var name = $(this).find('label').text();
        name = name.toLowerCase();
        text = text.toLowerCase();
        console.log(name+', '+text);
        if (name.includes(text)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
});
JAVASCRIPT;
    }

    public function helpContent()
    {
        return '<p>This tool creates a sign PDF based on recipe information in ChefTec.<p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
    }
}

FannieDispatch::conditionalExec();

