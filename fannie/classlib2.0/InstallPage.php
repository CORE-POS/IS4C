<?php
/*******************************************************************************

    Copyright 2012 Whole Foods Co-op

    This file is part of Fannie.

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

if (!class_exists('FanniePage')) {
    include_once(dirname(__FILE__).'/FanniePage.php');
}

/**
  @class InstallPage
  Class for Fannie Install-and-config pages, not using Fannie Admin menu.
*/
class InstallPage extends FanniePage 
{

    public $required = true;

    public $description = "
    Base class for install-and-config pages not using Admin menu.
    ";

    public function __construct() 
    {
        parent::__construct();
        /* This is the only privilege acceptable for these pages.
         * Overrides anything that might have been set in the parent.
        */
        $this->auth_classes = array('sysadmin');
    }

    /**
      Get the standard install-page header
      @return An HTML string
    */
    function getHeader()
    {
        global $FANNIE_ROOT, $FANNIE_WINDOW_DRESSING;
        ob_start();
        $page_title = $this->title;
        $header = $this->header;
        if (isset($FANNIE_WINDOW_DRESSING) && $FANNIE_WINDOW_DRESSING == True) {
            include($FANNIE_ROOT.'src/header.html');
        } else {
            include($FANNIE_ROOT.'src/header_install.html');
        }

        return ob_get_clean();
    }

    /**
      Get the standard install-page footer
      @return An HTML string
    */
    function getFooter(){
        global $FANNIE_ROOT, $FANNIE_AUTH_ENABLED, $FANNIE_URL, $FANNIE_WINDOW_DRESSING;
        ob_start();
        if (isset($FANNIE_WINDOW_DRESSING) && $FANNIE_WINDOW_DRESSING == True) {
            include($FANNIE_ROOT.'src/footer.html');
        } else {
            include($FANNIE_ROOT.'src/footer_install.html');
        }

        return ob_get_clean();
    }

}

