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

namespace COREPOS\Fannie\API
{

/**
  @class InstallPage
  Class for Fannie Install-and-config pages, not using Fannie Admin menu.
*/
class InstallPage extends \FanniePage 
{

    public $required = true;

    public $description = "
    Base class for install-and-config pages not using Admin menu.
    ";
    public $page_set = 'Installation';

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
        ob_start();
        $page_title = $this->title;
        $header = $this->header;
        if ($this->themed) {
            echo parent::getHeader(); 
        } elseif ($this->config->get('WINDOW_DRESSING')) {
            include(dirname(__FILE__) . '/../src/header.html');
        } else {
            include(dirname(__FILE__) . '/../src/header_install.html');
        }

        return ob_get_clean();
    }

    /**
      Get the standard install-page footer
      @return An HTML string
    */
    function getFooter()
    {
        $FANNIE_AUTH_ENABLED = $this->config->get('AUTH_ENABLED');
        $FANNIE_URL = $this->config->get('URL');
        ob_start();
        if ($this->themed) {
            echo parent::getFooter(); 
        } elseif ($this->config->get('WINDOW_DRESSING')) {
            include(dirname(__FILE__) . '/../src/footer.html');
        } else {
            include(dirname(__FILE__) . '/../src/footer_install.html');
        }

        return ob_get_clean();
    }

}

}

namespace 
{
    class InstallPage extends \COREPOS\Fannie\API\InstallPage {}
}

