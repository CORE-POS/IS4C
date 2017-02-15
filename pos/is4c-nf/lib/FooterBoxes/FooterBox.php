<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of IT CORE.

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

namespace COREPOS\pos\lib\FooterBoxes;
use COREPOS\pos\lib\LocalStorage\WrappedStorage;

/**
  @class FooterBox
  Base class for displaying footer

  The footer contain five boxes. Stores
  can select a different module for each
  box.
*/
class FooterBox 
{
    /**
      CSS here will be applied (in-line) to the
      header content. If you define a different
      width alignment might go haywire.
    */
    public $header_css = '';
    public $header_css_class = '';
    /**
      CSS here will be applied (in-line) to the
      display content. If you define a different
      width alignment might go haywire.
    */
    public $display_css = '';
    public $display_css_class = '';

    protected $session;

    public function __construct($session)
    {
        $this->session = $session;
    }

    /**
      Define the header for this box
      @return An HTML string
    */
    public function header_content()
    {
        return "";
    }

    /**
      Define the content for this box
      @return An HTML string
    */
    public function display_content()
    {
        return "";
    }

    private static $builtin = array(
        'EveryoneSales',
        'FooterBox',
        'MemSales',
        'MultiTotal',
        'PatronagePts',
        'SavedOrCouldHave',
        'TotalSavingFooter',
        'TransPercentDiscount',
    );

    public static function factory($class)
    {
        if ($class != '' && in_array($class, self::$builtin)) {
            $class = 'COREPOS\\pos\\lib\FooterBoxes\\' . $class;
            return new $class(new WrappedStorage());
        } elseif ($class != '' && class_exists($class)) {
            return new $class(new WrappedStorage());
        }

        return new COREPOS\pos\lib\FooterBoxes\FooterBox(new WrappedStorage());
    }
}

/**
  @example EgoFooter.php

  Footer Box modules are pretty simple. The header_content()
  method defines the label above the box and the 
  display_content() method defines the box itself. If needed
  you can define CSS via the class properties.

  This module just shows a constant reminder about the
  store's most critical department.
*/

