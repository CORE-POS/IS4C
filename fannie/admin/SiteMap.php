<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

include('../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}

class SiteMap extends FannieRESTfulPage
{
    protected $header = 'Fannie :: Site Map';
    protected $title = 'Site Map';

    public $must_authenticate = true;
    public $auth_classes = array('admin');

    public $description = '[Site Map] is a list of all known pages. It\'s very likely the page
    you\'re on right now!';

    public function get_view()
    {
        global $FANNIE_ROOT, $FANNIE_URL;
        $pages = FannieAPI::listModules('FanniePage');
        $sets = array();
        foreach ($pages as $p) {
            $obj = new $p();
            if (!$obj->discoverable) {
                continue;
            }
            $reflect = new ReflectionClass($obj);
            $url = $FANNIE_URL . str_replace($FANNIE_ROOT, '', $reflect->getFileName());
            if (!isset($sets[$obj->page_set])) {
                $sets[$obj->page_set] = array();
            }
            $sets[$obj->page_set][$p] = array(
               'url' => $url,
               'info' => $obj->description, 
            );
        }

        $keys = array_keys($sets);
        sort($keys);
        $ret = '<ul>';
        foreach ($keys as $set_name) {
            $ret .= '<li>' . $set_name;
            $ret .= '<ul>';
            $page_keys = array_keys($sets[$set_name]);
            sort($page_keys);
            foreach ($page_keys as $page_key) {
                $description = $sets[$set_name][$page_key]['info'];
                $url = $sets[$set_name][$page_key]['url'];
                $linked = preg_replace('/\[(.+)\]/', '<a href="' . $url . '">\1</a>', $description);
                if ($linked === $description) {
                    $linked .= ' (<a href="' . $url . '">Link</a>)';
                }
                $ret .= '<li>' . $linked . '</li>';
            }
            $ret .= '</ul>';
            $ret .= '</li>';
        }
        $ret .= '</ul>';

        return $ret;
    }
}

FannieDispatch::conditionalExec();

