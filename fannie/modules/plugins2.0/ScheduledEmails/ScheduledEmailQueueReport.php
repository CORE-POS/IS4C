<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../../../config.php');
if (!class_exists('FannieAPI')) {
    include(dirname(__FILE__) . '/../../../classlib2.0/FannieAPI.php');
}

class ScheduledEmailQueueReport extends FannieReportPage
{
    protected $header = 'Scheduled Email Queue';
    protected $title = 'Scheduled Email Queue';
    public $description = '[Scheduled Email Queue] lists send and pending messages.';
    public $report_set = 'System';

    protected $required_fields = array('submit');
    protected $report_headers = array('Scheduled Send Date', 'Template', 'Mem#', 'Sent', 'Sent Date', 'Sent to Email');

    public function fetch_report_data()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['ScheduledEmailDB']);

        $query = '
            SELECT name,
                cardNo,
                sendDate,
                sent,
                sentDate,
                sentToEmail
            FROM ScheduledEmailQueue AS q
                LEFT JOIN ScheduledEmailTemplates AS t 
                    ON q.scheduledEmailTemplateID=t.scheduledEmailTemplateID
            WHERE 1=1 '; 
        $args = array();
        if (FormLib::get('member') != '') {
            $query .= ' AND CardNo=? ';
            $args[] = FormLib::get('member');
        }
        if (FormLib::get('date1') != '' && FormLib::get('date2') != '') {
            $query .= ' AND sendDate BETWEEN ? AND ? ';
            $args[] = FormLib::get('date1') . ' 00:00:00';
            $args[] = FormLib::get('date2') . ' 23:59:59';
        }
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);
        $data = array();
        while ($w = $dbc->fetchRow($res)) {
            $data[] = array(
                $w['sendDate'],
                $w['name'],
                $w['cardNo'],
                ($w['sent'] ? 'Yes' : 'No'),
                ($w['sentDate'] ? $w['sentDate'] : 'n/a'),
                ($w['sentToEmail'] ? $w['sentToEmail'] : 'n/a'),
            );
        }

        return $data;
    }

    public function form_content()
    {
        return '<form method="get" class="form-horizontal">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label class="col-sm-3">Member#</label>
                        <div class="col-sm-8">
                            <input type="text" name="member" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-5">
                            <button type="submit" value="1" name="submit" class="btn btn-default">Get Report</button>
                        </div>
                    </div>
                </div>'
                . str_replace('required', '', FormLib::standardDateFields()) . '
            </div>
            </form>';
    }

    public function helpContent()
    {
        return '<p>
            Enter a member number to see all messages queued and sent to that member.
            Enter a date range to see all messages scheduled to be sent within those
            dates. You can enter both a member number and a date range to view a subset
            of messages for that member.
            </p>';
    }
}

FannieDispatch::conditionalExec();

