<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

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
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class VendorDepartmentUploadPage extends \COREPOS\Fannie\API\FannieUploadPage 
{
    public $title = "Fannie - Load Vendor Subcategories";
    public $header = "Upload Vendor subcategories";
    public $themed = true;

    public $description = '[Vendor Subcategories Import] loads names and numbers of vendor subcategories
    via spreadsheet.';

    protected $must_authenticate = true;
    protected $auth_classes = array('pricechange');

    protected $preview_opts = array(
        'deptID' => array(
            'display_name' => 'Subcategory Number *',
            'default' => 0,
            'required' => true
        ),
        'name' => array(
            'display_name' => 'Subcategory Name',
            'default' => 1,
        ),
        'margin' => array(
            'display_name' => 'Margin (%)',
            'default' => 2,
        ),
    );

    public function process_file($linedata, $indexes)
    {
        $dbc = $this->connection;
        $dbc->selectDB($this->config->get('OP_DB'));

        if (!isset($this->session->vid)) {
            $this->error_details = 'Missing vendor setting';
            return False;
        }
        $VENDOR_ID = $this->session->vid;

        $p = $dbc->prepare("SELECT vendorID,vendorName FROM vendors WHERE vendorID=?");
        $idR = $dbc->execute($p,array($VENDOR_ID));
        if ($dbc->num_rows($idR) == 0){
            $this->error_details = 'Cannot find vendor';
            return False;
        }
        $idW = $dbc->fetch_row($idR);
        $vendorName = $idW['vendorName'];

        $model = new VendorDepartmentsModel($dbc);

        foreach ($linedata as $data) {
            if (!is_array($data)) continue;

            if (!isset($data[$indexes['deptID']])) continue;
            if (!is_numeric($data[$indexes['deptID']])) continue;

            // grab data from appropriate columns
            $model->vendorID($VENDOR_ID);
            $model->deptID($data[$indexes['deptID']]);
            $model->name($indexes['name'] === false ? '' : $data[$indexes['name']]);
            $model->margin($indexes['margin'] === false ? 0 : $data[$indexes['margin']]);
            $model->save();
        }

        return true;
    }

    function results_content()
    {
        $ret = '<p>Import Complete</p>';
        $ret .= sprintf('<p><a class="btn btn-default" 
            href="VendorIndexPage.php?vid=%d">Back to Vendor</a></p>', $this->session->vid);
        unset($this->session->vid);

        return $ret;
    }

    function form_content()
    {
        global $FANNIE_OP_DB;
        $vid = FormLib::get_form_value('vid');
        if ($vid === ''){
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<div class="alert alert-danger">Error: No Vendor Selected</div>';
        }
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $vp = $dbc->prepare('SELECT vendorName FROM vendors WHERE vendorID=?');
        $vr = $dbc->execute($vp,array($vid));
        if ($dbc->num_rows($vr)==0){
            $this->add_onload_command("\$('#FannieUploadForm').remove();");
            return '<div class="alert alert-danger">Error: No Vendor Found</div>';
        }
        $vrow = $dbc->fetch_row($vr);
        $this->session->vid = $vid;
        return '<div class="well"><legend>Instructions</legend>
            Upload a price file for <i>'.$vrow['vendorName'].'</i> ('.$vid.'). File must be
            CSV. Files &gt; 2MB may be zipped.</div>';
    }

    public function preprocess()
    {
        if (php_sapi_name() !== 'cli') {
            /* this page requires a session to pass some extra
               state information through multiple requests */
            @session_start();
        }

        return parent::preprocess();
    }

    public function helpContent()
    {
        return '
        <p>
            <ul>
                <li>Only CSV files are supported. This works most reliably for large data sets.</li>
                <li>Maximum file size is usually 2MB. CSV files may be zipped to reduce
                    file size.</li>
                <li>The purpose of the preview screen is to specify the format of your
                    file. It shows the first five rows of data with dropdowns above each
                    column. Use the dropdowns to specify what (if any) data is present in 
                    each column. For example, if UPCs are in the 3rd column, set the dropdown
                    for the third column to UPC.</li>
                <li>Large files may take awhile to process. Give it 5 or 10 minutes before
                    deciding it didn\'t work.</li>
            </ul>
        </p>';
    }
}

FannieDispatch::conditionalExec();

