<?php
/*******************************************************************************

    Copyright 2017 Whole Foods Community Co-op

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


require(dirname(__FILE__) . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class EOMReportingPage extends \COREPOS\Fannie\API\FannieReportTool
{
    protected $header = 'EOMReportingPage';
    protected $title = 'EOMReportingPage';
    public $description = '[EOMReportingPage] Access EOM reports.';
    public $report_set = 'Finance';
    protected $must_authenticate = true;
    protected $auth_classes = array('editmembers');

    public function body_content()
    {
        return '<iframe frameborder="0" height="1200px" width="100%" src="../../legacy/members/EOM_Reporting/"></iframe>';
    }

    public function helpContent()
    {
        return '<p>Access EOM reports.</p>';
    }
}

FannieDispatch::conditionalExec();

