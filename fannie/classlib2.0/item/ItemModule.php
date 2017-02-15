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

namespace COREPOS\Fannie\API\item;

class ItemModule 
{
    const META_WIDTH_FULL = 100;
    const META_WIDTH_HALF = 50;
    const META_WIDTH_THIRD = 33;

    protected $config;
    protected $connection;
    protected $form;

    public function db()
    {
        if (is_object($this->connection)) {
            return $this->connection;
        }

        if (!class_exists('FannieDB')) {
            include_once(dirname(__FILE__) . '/../data/FannieDB.php');
        }

        return \FannieDB::get(\FannieConfig::factory()->get('OP_DB'));
    }

    public function setConfig(\FannieConfig $c)
    {
        $this->config = $c; 
    }

    public function setForm(\COREPOS\common\mvc\ValueContainer $f)
    {
        $this->form = $f;
    }

    public function setConnection(\SQLManager $s)
    {
        $this->connection = $s;
    }


    public function width()
    {
        return self::META_WIDTH_FULL;
    }

    public function showEditForm($upc, $display_mode=1, $expand_mode=1)
    {

    }

    public function getFormJavascript($upc)
    {
        return '';
    }

    public function saveFormData($upc)
    {

    }

    public function summaryRows($upc)
    {
        return array();
    }

    public function hasSearch()
    {
        return false;
    }

    public function showSearchForm()
    {

    }

    public function getSearchResults()
    {

    }
    
    public function runCron()
    {

    }
}

