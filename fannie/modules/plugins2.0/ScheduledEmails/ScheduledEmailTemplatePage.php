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

/**
*/
class ScheduledEmailTemplatePage extends FannieRESTfulPage
{
    protected $header = 'Scheduled Email Templates';
    protected $title = 'Scheduled Email Templates';
    public $description = '[Scheduled Email Templates] creates and edits email layouts';

    public function preprocess()
    {
        $this->__routes[] = 'post<id><email>';

        return parent::preprocess();
    }

    public function put_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['ScheduledEmailDB']);
        
        $template = new ScheduledEmailTemplatesModel($dbc);
        $template->name('NEW TEMPLATE');
        $template->save(); 

        return $_SERVER['PHP_SELF'];
    }

    public function delete_id_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['ScheduledEmailDB']);
        
        $template = new ScheduledEmailTemplatesModel($dbc);
        $template->scheduledEmailTemplateID($this->id);
        $template->delete();

        return $_SERVER['PHP_SELF'];
    }

    public function post_id_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['ScheduledEmailDB']);

        $template = new ScheduledEmailTemplatesModel($dbc);
        $template->scheduledEmailTemplateID($this->id);
        $template->name(FormLib::get('name'));
        $template->subject(FormLib::get('subject'));
        $template->hasText(FormLib::get('hasText', 0));
        $template->textCopy(FormLib::get('textCopy'));
        $template->hasHtml(FormLib::get('hasHTML', 0));
        $template->htmlCopy(FormLib::get('htmlCopy'));
        $template->save();

        return $_SERVER['PHP_SELF'] . '?id=' . $this->id;
    }

    public function post_id_email_handler()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['ScheduledEmailDB']);

        $template = new ScheduledEmailTemplatesModel($dbc);
        $template->scheduledEmailTemplateID($this->id);
        $template->load();

        ScheduledEmailSendTask::sendEmail($template, $this->email, array());
        
        return $_SERVER['PHP_SELF'] . '?id=' . $this->id;
    }

    public function get_id_view()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['ScheduledEmailDB']);
        
        $template = new ScheduledEmailTemplatesModel($dbc);
        $template->scheduledEmailTemplateID($this->id);
        if (!$template->load()) {
            return '<div class="alert alert-danger">Template not found</div>';
        }

        $ret = '<div class="panel panel-default">
            <div class="panel-heading">Edit Template</div>
            <div class="panel-body">
            <form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />';

        $ret .= '<div class="form-group">
            <label>Template Name</label>
            <input type="text" class="form-control" name="name" value="' . $template->name() . '" />
            </div>';

        $ret .= '<div class="form-group">
            <label>Message Subject</label>
            <input type="text" class="form-control" name="subject" value="' . $template->subject() . '" />
            </div>';

        $ret .= '<div class="form-group">
            <label>Text Content
                <input type="checkbox" name="hasText" value="1" ' . ($template->hasText() ? 'checked' : '') . ' />
            </label>
            <textarea class="form-control" rows="20" name="textCopy">' . $template->textCopy() . '</textarea>
            </div>';

        $ret .= '<div class="form-group">
            <label>HTML Content
                <input type="checkbox" name="hasHTML" value="1" ' . ($template->hasHTML() ? 'checked' : '') . ' />
            </label>
            <textarea class="form-control" rows="20" name="htmlCopy">' . $template->htmlCopy() . '</textarea>
            </div>';

        $ret .= '<div class="form-group">
            <button type="submit" class="btn btn-default">Save Template</button>
            |
            <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">Back to Template List</a>
            </div>
            </form>
            </div>
            </div>';

        $ret .= '<div class="panel panel-default">
            <div class="panel-heading">Test Template</div>
            <div class="panel-body">
            <form method="post">
            <input type="hidden" name="id" value="' . $this->id . '" />';
        $ret .= '<div class="form-group">
            <label>E-mail</label>
            <input type="email" class="form-control" name="email" />
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-default">Send Test Email</button>
                |
                <a href="' . $_SERVER['PHP_SELF'] . '" class="btn btn-default">Back to Template List</a>
            </div>
            </form>
            </div>
            </div>';

        return $ret;
    }

    public function get_view()
    {
        $dbc = $this->connection;
        $settings = $this->config->get('PLUGIN_SETTINGS');
        $dbc->selectDB($settings['ScheduledEmailDB']);

        $templates = new ScheduledEmailTemplatesModel($dbc);
        $ret = '<table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
            </thead>
            <tbody>';
        foreach ($templates->find('scheduledEmailTemplateID') as $t) {
            $ret .= sprintf('<tr>
                <td>%d</td>
                <td>%s</td>
                <td><a class="btn btn-default btn-xs" href="?id=%d">%s</a></td>
                <td><a class="btn btn-danger btn-xs" href="?_method=delete&id=%d"
                    onclick="return confirm(\'Delete template %s?\');">%s</a></td>
                </tr>',
                $t->scheduledEmailTemplateID(),
                $t->name(),
                $t->scheduledEmailTemplateID(),
                \COREPOS\Fannie\API\lib\FannieUI::editIcon(),
                $t->scheduledEmailTemplateID(),
                $t->name(),
                \COREPOS\Fannie\API\lib\FannieUI::deleteIcon()
            );
        }
        $ret .= '</tbody></table>';

        $ret .= '<p>
            <a href="?_method=put" class="btn btn-default">Create New Template</a>
            </p>';


        return $ret;
    }

    public function helpContent()
    {
        if ($this->__route_stem == 'get_id') {
            return '<p>
                The name of a template is strictly for internal use while
                the subject is what will appear in messages\' subject line
                when using this template. Templates may include text and/or
                HTML content but at least one of these options should be checked
                or messages will be blank.
                </p>
                <p>
                Both text and HTML content may include placeholder values that
                are filled in with details of the recipient member. Placeholder
                values must be enclosed in double curly braces - e.g., {{name}}.
                You can have as many placeholder as you want and may name them
                whatever you want. 
                </p>';
        } else {
            return '<p>
                Scheduled Email Templates define different kinds of
                messages that can be emailed to members. Use the Create
                New Template button to add templates. Use the Edit/pencil icon
                to edit an existing template and the Delete/trash icon to
                delete an existing template. Note that when a template is
                deleted, any queued messages using that template will be
                unable to send.
                </p>';
        }
    }
}

FannieDispatch::conditionalExec();

