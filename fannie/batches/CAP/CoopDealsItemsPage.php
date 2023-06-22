<?php
/*******************************************************************************

    Copyright 2009,2013 Whole Foods Co-op

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

include(__DIR__ . '/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once(__DIR__ . '/../../classlib2.0/FannieAPI.php');
}

class CoopDealsItemsPage extends FannieRESTfulPage
{
    protected $title = "Fannie - Coop Deals DealSets Page";
    protected $header = "Coop Deals DealSets";

    public $description = '[] .';
    public $themed = true;

    protected $auth_classes = array('batches');
    protected $must_authenticate = true;


    public function preprocess()
    {
        $this->__routes[] = 'post<delSet>';

        return parent::preprocess();
    }

    public function post_delSet_handler()
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $delSet = FormLib::get('delSet');

        $args = array($delSet);
        $prep = $dbc->prepare("DELETE FROM CoopDealsItems WHERE dealSet = ?");
        $res = $dbc->execute($prep, array($delSet));

        return header("location: CoopDealsItemsPage.php");
    }

    public function get_view()
    {

        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));
        $td = '';

        $prep = $dbc->prepare("SELECT dealSet FROM CoopDealsItems
            GROUP BY dealSet;");
        $res = $dbc->execute($prep);
        while ($row = $dbc->fetchRow($res)) {
            $set = $row['dealSet'];
            $td .= sprintf("<tr><td>%s</td><td>%s</td></tr>",
                $set,
                "<form action=\"CoopDealsItemsPage.php\" method=\"post\" name\"form-$set\">
                    <button type=\"submit\" class=\"btn btn-default\" onclick=\"var c = confirm('Delete $set?'); return c; \">Delete this Set</button> 
                    <input type=\"hidden\" name=\"delSet\" value=\"$set\" /></form>"
            );
        }

        return <<<HTML
<div class="row">
    <div class="col-lg-4"></div>
    <div class="col-lg-4">
        <table class="table table-bordered"><thead></thead><tbody>$td</tbody></table>
    </div>
    <div class="col-lg-4">
        <a href="CapSalesIndexPage.php" class="btn btn-default">Go Back</a>
    </div>
</div>
HTML;
    }

    public function javascript_content()
    {
        return <<<HTML
HTML;
    }

    public function css_content()
    {
        return <<<HTML
HTML;
    }

    public function helpContent()
    {
        return <<<HTML
<p>View & Delete Coop Deals Items "Deal Sets."</p>
HTML;
    }

}
FannieDispatch::conditionalExec();
