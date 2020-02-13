<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op, Duluth, MN

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
include(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class PatronageChecks extends FannieRESTfulPage
{
    protected $title = "Fannie :: Patronage Checks";
    protected $header = "Fannie :: Patronage Checks";
    public $discoverable = false;

    public function preprocess()
    {
        $this->__routes[] = 'get<reprint><mem><fy>';

        return parent::preprocess();
    }

    public function get_reprint_mem_fy_view()
    {
        return 'Sorry, reprint functionality has been removed';
    }

    public function get_view()
    {
        return 'This is a placeholder to aovid dead links';
    }
}

FannieDispatch::conditionalExec();

