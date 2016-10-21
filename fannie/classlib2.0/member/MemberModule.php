<?php
/*******************************************************************************

    Copyright 2010 Whole Foods Co-op, Duluth, MN

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

namespace COREPOS\Fannie\API\member;
use \FannieDB;
use \FannieConfig;

class MemberModule
{

    const META_WIDTH_FULL = 100;
    const META_WIDTH_HALF = 50;
    const META_WIDTH_THIRD = 33;

    /**
      Get connection to member database
      @return [SQLManager object]
    */
    public function db()
    {
        if (!class_exists('FannieDB')) {
            include_once(dirname(__FILE__) . '/../data/FannieDB.php');
        }

        return FannieDB::get(FannieConfig::factory()->get('OP_DB'));
    }

    public function width()
    {
        return self::META_WIDTH_FULL;
    }

    /**
      Get form for editing member information
      @param $memNum [int] member number
      @param $country [string] locale code. Default "US"
      @return [string] HTML form fields
    */
    public function showEditForm($memNum,$country="US")
    {
        return '';
    }

    /**
      Extract data from GET/POST and save
      member information
      @param $memNum [int] member number
      @param $json [array] current member information
      @return [string] message. empty string indicates success
    */
    public function saveFormData($memNum, $json=array())
    {
        return $json;
    }

    /**
      Module's information is searchable
      @return [boolean]
    */
    public function hasSearch()
    {
        return false;
    }

    /**
      Get form for searching member information
      @param $country [string] locale code. Default "US"
      @return [string] HTML form fields
    */
    public function showSearchForm($country="US")
    {
        return '';
    }

    /**
      Perform search based on GET/POST data
      @return [keyed array] member number => description
    */
    public function getSearchResults()
    {
        return array();
    }

    /**
      Get any javascript that goes with
      the search form
      @return [string] javascript
    */
    public function getSearchJavascript()
    {
        return '';
    }

    /**
      Get list of commands to run when
      the search page is loaded
      @return [array] of javascript commands
    */
    public function getSearchLoadCommands()
    {
        return array();
    }

    /**
      Get any javascript that goes with
      the editing form
      @return [string] javascript
    */
    public function getEditJavascript()
    {
        return '';
    }

    /**
      Get list of commands to run when
      the edit page is loaded
      @return [array] of javascript commands
    */
    public function getEditLoadCommands()
    {
        return array();
    }

    public static function getAccount()
    {
        return self::$account;
    }

    private static $account;

    public static function setAccount($a)
    {
        self::$account = $a;
    }
}

