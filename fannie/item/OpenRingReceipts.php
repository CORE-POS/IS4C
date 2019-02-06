<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Community Co-op

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

require(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include(__DIR__ . '/../classlib2.0/FannieAPI.php');
}

class OpenRingReceipts extends FannieRESTfulPage
{
    protected $header = 'Open Ring Receipts';
    protected $title = 'Open Ring Receipts';

    public $description = '[Open Ring Receipts] finds receipts on a given day that
    contain an open ring. Optionally filter the list to receipts containing
    both an open ring and a UPC that did not scan. These are likely items that need
    to be entered into POS.';
    public $themed = true;
    private $receipts = array();

    public function preprocess()
    {
        $this->__routes[] = 'get<date1><date2>';
        $this->__routes[] = 'get<upc><date1><date2>';

        return parent::preprocess();
    }

    public function get_upc_date1_date2_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $dlog = DTransactionsModel::selectDlog($this->date1, $this->date2);
        $dtrans = DTransactionsModel::selectDtrans($this->date1, $this->date2);

        $badP = $dbc->prepare("
            SELECT YEAR(datetime) AS year,
                MONTH(datetime) AS month,
                DAY(datetime) AS day,
                emp_no,
                register_no,
                trans_no
            FROM " . $dtrans . " AS d
            WHERE trans_type = 'L'
                AND upc = ?
                AND datetime BETWEEN ? AND ?
                AND description='BADSCAN'
                AND emp_no <> 9999
                AND register_no <> 99");
        $args = array(
            BarcodeLib::padUPC($this->upc),
            date('Y-m-d 00:00:00', strtotime($this->date1)),
            date('Y-m-d 23:59:59', strtotime($this->date2)),
        );

        $openP = $dbc->prepare("
            SELECT upc,
                description,
                department,
                dept_name
            FROM " . $dlog . " AS d
                LEFT JOIN departments AS t ON d.department=t.dept_no
            WHERE tdate BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND (trans_type = 'D' OR trans_type='C')
                AND description != 'DONATIONS'
            GROUP BY d.upc,
                d.description,
                d.department,
                t.dept_name");
        $badR = $dbc->execute($badP, $args);
        $this->receipts = array();
        while ($badW = $dbc->fetch_row($badR)) {
            $openArgs = array(
                date('Y-m-d 00:00:00', mktime(0, 0, 0, $badW['month'], $badW['day'], $badW['year'])),
                date('Y-m-d 23:59:59', mktime(0, 0, 0, $badW['month'], $badW['day'], $badW['year'])),
                $badW['emp_no'],
                $badW['register_no'],
                $badW['trans_no'],
            );
            $openR = $dbc->execute($openP, $openArgs);
            $receipt = array(
                'date' => date('Y-m-d', mktime(0, 0, 0, $badW['month'], $badW['day'], $badW['year'])),
                'trans' => $badW['emp_no'] . '-' . $badW['register_no'] . '-' . $badW['trans_no'],
                'rings' => array(),
            );
            while ($openW = $dbc->fetch_row($openR)) {
                $receipt['rings'][] = $openW;
            }
            $this->receipts[] = $receipt;
        }

        return true;
    }

    public function get_date1_date2_handler()
    {
        $dbc = FannieDB::get($this->config->get('OP_DB'));
        $dlog = DTransactionsModel::selectDlog($this->date1, $this->date2);
        $dtrans = DTransactionsModel::selectDtrans($this->date1, $this->date2);

        $openQ = '
            SELECT YEAR(tdate) AS year,
                MONTH(tdate) AS month,
                DAY(tdate) AS day,
                emp_no,
                register_no,
                trans_no
            FROM ' . $dlog . ' AS d
                INNER JOIN MasterSuperDepts AS m ON m.dept_ID=d.department
            WHERE tdate BETWEEN ? AND ?
                AND trans_type=\'D\'
                AND m.superID <> 0
            GROUP BY YEAR(tdate),
                MONTH(tdate),
                DAY(tdate),
                emp_no,
                register_no,
                trans_no
            HAVING SUM(total) <> 0';

        $badQ = '
            SELECT upc
            FROM ' . $dtrans . ' AS d
            WHERE datetime BETWEEN ? AND ?
                AND emp_no=?
                AND register_no=?
                AND trans_no=?
                AND trans_type=\'L\'
                AND description=\'BADSCAN\'
                AND d.upc LIKE \'0%\'
                AND d.upc NOT LIKE \'00000000000%\'';

        $openP = $dbc->prepare($openQ);
        $badP = $dbc->prepare($badQ);
        $filter = FormLib::get('badscans', false);

        $this->receipts = array();
        $openR = $dbc->execute($openP, array($this->date1 . ' 00:00:00', $this->date2 . ' 23:59:59'));
        while ($openW = $dbc->fetchRow($openR)) {
            $ts = mktime(0, 0, 0, $openW['month'], $openW['day'], $openW['year']);
            if ($filter) {
                $args = array(
                    date('Y-m-d 00:00:00', $ts),
                    date('Y-m-d 23:59:59', $ts),
                    $openW['emp_no'],
                    $openW['register_no'],
                    $openW['trans_no'],
                );
                if (FormLib::get('upc') != '') {
                    $args[] = FormLib::get('upc');
                }
                $badR = $dbc->execute($badP, $args);
                if (!$badR || $dbc->num_rows($badR) == 0) {
                    continue;
                }
            }
            $this->receipts[] = array(
                'date' => date('Y-m-d', $ts),
                'trans_num' => $openW['emp_no'] . '-' . $openW['register_no'] . '-' . $openW['trans_no'],
            );
        }

        return true;
    }

    public function get_upc_date1_date2_view()
    {
        if (!is_array($this->receipts) || count($this->receipts) == 0) {
            return '<div class="alert alert-danger">No matches found</div>';
        }

        $upc = FormLib::get('upc');
        $ret = '<div id="openRings"><table class="table table-condensed small">';
        foreach ($this->receipts as $receipt) {
            $ret .= sprintf('<tr>
                        <th>%s</th>
                        <th>%s</th>
                        <th><a href="#" data-href="/admin/LookupReceipt/RawReceipt.php?date=%s&trans=%s#reportTable1"
                            class="btn btn-default btn-xs viewReceipt">View Receipt</a></th>',
                        $receipt['date'],
                        $receipt['trans'],
                        $receipt['date'],
                        $receipt['trans']
                    );
            foreach ($receipt['rings'] as $item) {
                $ret .= sprintf('<tr>
                        <td>&nbsp;</td>
                        <td>%s</td>
                        <td>%s</td>
                        <td>%d</td>
                        <td>%s</td>
                        </tr>',
                        $item['upc'],
                        $item['description'],
                        $item['department'],
                        $item['dept_name']
                );
            }
        }
        $ret .= '</table></div>';

        return "
            <div class='row'>
                <div class='col-md-4'>
                    <div class='panel panel-default'>
                        <div class='panel-heading'>
                            <strong id='receipts'>Open Rings for</strong> <input class='upc' value=$upc />
                        </div>
                        $ret
                    </div>
                        <strong>
                            Add to Ignored Barcodes: 
                            <a data-toggle='modal' data-target='#ignorebarcode-modal'> click here </a>
                        </strong>
                    <div id='IgnoredBarcodes'>
                    <!--
                        <form>
                            <div class='col-md-6'>
                                <div class='form-group'>
                                    <label>UPC:</label>
                                    <input type='text' class='form-control' id='upc' name='Upc' />
                                </div>
                            </div>
                            <div class='col-md-6'>
                                <div class='form-group'>
                                    <label>Reason:</label>
                                    <input type='text' class='form-control' id='reason' name='Reason' />
                                </div>
                            </div>
                        </form>
                            <div class='col-md-6'>
                                <div class='form-group'>
                                    <button class='btn btn-default' id='submitIgnored'>Submit</button>
                                </div>
                            </div>
                        -->
                    </div>
                </div>
                <div class='col-md-8'>
                    <iframe class='receiptIframe' id='receiptIframe'></iframe>
                </div>
            </div>
            {$this->modal()}
        ";
    }


    public function modal()
    {
        return <<<HTML
<div class="modal" id="ignorebarcode-modal" role="modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <iframe src='IgnoredBarcodeEditor.php#form-start' class='ignoredIframe'></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default close-btn" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
HTML;

    }

    public function get_date1_date2_view()
    {
        $ret = '';
        if (!is_array($this->receipts) || count($this->receipts) == 0) {
            $ret .= '<div class="alert alert-danger">No matches found</div>';
        } else {
            $url_stem = $this->config->get('URL');
            $ret .= '<ul>';
            foreach ($this->receipts as $receipt) {
                $ret .= sprintf('<li><a href="%sadmin/LookupReceipt/RenderReceiptPage.php?date=%s&receipt=%s"
                                    target="_rp_%s_%d">%s</a></li>',
                            $url_stem, $receipt['date'], $receipt['trans_num'],
                            $receipt['date'], $receipt['trans_num'], $receipt['trans_num']);
            }
            $ret .= '</ul>';
        }

        $ret .= '<p>
            <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">New Search</a>
            </p>';

        return $ret;
    }

    public function get_view()
    {
        return '
            <form action="' . $_SERVER['PHP_SELF'] . '" type="get">
            <div class="col-sm-5">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="text" name="date1" id="date1" class="form-control date-field" required />
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="text" name="date2" id="date2" class="form-control date-field" required />
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="badscans" value="1" checked />
                        Only receipts with unknown UPCs
                    </label>
                </div>
                <p>
                    <button type="submit" class="btn btn-default">Lookup Receipts</button>
                </p>
            </div>
            <div class="col-sm-5">
                ' . FormLib::dateRangePicker() . '
            </div>
            </form>';
    }

    public function css_content()
    {
        return '
            .modal-body {
                height: 70vh;
            }
            .receiptIframe {
                width: 100%;
                height: 65vh;
                border: 1px solid #EFEFEF;
            }
            .ignoredIframe {
                width: 100%;
                height: 100%;
                border: none;
            }
            .upc {
                border: none;
                background: rgba(0,0,0,0);
            }
            #openRings {
                height: 50vh;
                overflow-y: auto;
                border: 1px solid #EFEFEF;
            }
        ';
    }

    public function javascript_content()
    {
        return <<<JAVASCRIPT
$(".viewReceipt").click(function(){
    $('a').each(function(){
        c = $(this).hasClass('active');
        if (c == true) {
            $(this).removeClass('active');
        }
    });
    $(this).addClass('active');
    var baseUrl = getBaseUrl();
    var src = $(this).attr("data-href");
    src = baseUrl + ".." + src;
    $("#receiptIframe").attr("src", src)
});
function getBaseUrl() {
    var re = new RegExp(/^.*\//);
    return re.exec(window.location.href);
}
$('#submitIgnored').click(function(){
    //alert('start');
    var upc = $('#upc').val();
    var reason = $('#reason').val();
    $.ajax({
        type: "post",
        url: "IgnoredBarcodeEditor.php",
        data: "upc="+upc+"&reason="+reason,
        success: function(resp)
        {
            //alert('maybe worked');
        }
    });
});
JAVASCRIPT;
    }

    public function helpContent()
    {
        return '<p>
            Locate receipts in a given date range that
            include open rings. Optionally filter to only
            those receipts that also include UPCs that did
            not scan. Typically this combination indicates
            a product has not been entered into POS.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->date1 = date('Y-m-d');
        $this->date2 = date('Y-m-d');
        $phpunit->assertNotEquals(0, strlen($this->get_date1_date2_view()));
        $this->upc = '0000000004011';
        $phpunit->assertNotEquals(0, strlen($this->get_upc_date1_date2_view()));
        $phpunit->assertEquals(true, $this->get_date1_date2_handler());
        $phpunit->assertEquals(true, $this->get_upc_date1_date2_handler());
    }
}

FannieDispatch::conditionalExec();

