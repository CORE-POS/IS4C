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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class SiteSearch extends FannieRESTfulPage
{
    public $themed = true;
    public $description = '[Site Search] provides meta-search to locate CORE reports and tools.';
    protected $header = 'Search for Pages';
    protected $title = 'Search for Pages';

    public function get_id_view()
    {
        $pages = FannieAPI::listModules('FanniePage');
        $strong = array();
        $weak = array();
        $search_term = strtoupper($this->id);

        foreach ($pages as $page_class) {
            $obj = new $page_class();
            if (!$obj->discoverable) {
                continue;
            }
            if (!empty($search_term) && strpos(strtoupper($obj->description), $search_term) !== false) {
                $strong[] = $obj;
            }
        }

        if (count($strong) == 0) {
            return '<div class="alert alert-danger">' . _('No matches') . '</div>' . $this->get_view();
        }

        $ret = '<div class="panel panel-default">
            <div class="panel-heading">' . _('Search Results') . '</div>
            <div class="panel-body"><ul>';
        $URL = $this->config->URL;
        $ROOT = $this->config->ROOT;
        foreach ($strong as $obj) {
            $reflect = new ReflectionClass($obj);
            $page_link = $URL . str_replace($ROOT, '', $reflect->getFileName());
            $description = $obj->description;
            $linked = preg_replace('/(' . $this->id . ')/i', '<strong>\1</strong>', $description);
            $linked = preg_replace('/\[(.+)\]/', '<a href="' . $page_link . '">\1</a>', $linked);
            if ($linked === $description) {
                $linked .= ' (<a href="' . $url . '">Link</a>)';
            }
            $ret .= '<li>' . $linked . '</li>';
        }
        $ret .= '</ul></div></div>';

        return $this->get_view() . $ret;
    }

    public function get_view()
    {
        $this->addOnloadCommand("\$('#search-term').focus();\n");
        return '
            <form method="get">
                <div class="form-group">
                    <input type="text" class="form-control" id="search-term"
                        placeholder="' . _('Enter search term') . '" name="id" required
                        pattern=".{3,}" title="' . _('Three characters minimum') . '" />
                    <button type="submit" class="btn btn-default">' . _('Search') . '</button>
                </div>
            </form>';
    }

    public function helpContent()
    {
        return _('<p>
            Enter search term(s) to locate pages, tools, and reports
            within CORE. For example, enter "Site Search" to find this
            particular page.
            </p>');
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->id = 'foo';
        $phpunit->assertNotEquals(0, strlen($this->get_id_view()));
    }
}

FannieDispatch::conditionalExec();

