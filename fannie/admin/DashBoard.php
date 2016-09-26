<?php
/*******************************************************************************

    Copyright 2016 Whole Foods Co-op

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

include(dirname(__FILE__) . '/../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT . 'classlib2.0/FannieAPI.php');
}
if (!function_exists('installSelectField')) {
    include_once($FANNIE_ROOT . 'install/util.php');
}

class DashBoard extends FannieRESTfulPage
{
    public $description = '[Dashboard] displays current system status';
    protected $header = 'Dashboard';
    protected $title = 'Dashboard';

    protected function post_handler()
    {
        $eat = $this->emailConfiguration();
        $mon_conf = 'array(';
        foreach (FormLib::get('mon', array()) as $mod) {
            $mon_conf .= "'" . str_replace(':', '\\', $mod) . "',";
        }
        $mon_conf = rtrim($mon_conf, ',') . ')';
        confset('MON_ENABLED', $mon_conf);

        return 'DashBoard.php';
    }

    private function emailConfiguration()
    {
        include(dirname(__FILE__) . '/../config.php');
        $warn = !class_exists('PHPMailer') ? '<div class="alert alert-warning">' . _('PHPMailer is missing. Use composer to install it or notifications will not be sent.') . '</div>' : '';
        $ret = '<div class="panel panel-default">
            <div class="panel-heading">
                <a href="" onclick="$(\'#email-config\').toggle(); return false;">
                ' . _('Notification Configuration') . '</a>
            </div>
            <div class="panel-body collapse" id="email-config">
                <form method="post">
                ' . $warn . '
                <div class="form-group">
                <label>' . _('Notification Email Address(es)') . '</label>
                ' . installTextField('MON_SMTP_ADDR', $MON_SMTP_ADDR, '') . '
                </div>
                <div class="form-group">
                <label>' . _('SMTP Host') . '</label>
                ' . installTextField('MON_SMTP_HOST', $MON_SMTP_HOST, '127.0.0.1') . '
                </div>
                <div class="form-group">
                <label>' . _('SMTP Port') . '</label>
                ' . installTextField('MON_SMTP_PORT', $MON_SMTP_PORT, '25') . '
                </div>
                <div class="form-group">
                <label>SMTP SSL/TLS</label>
                ' . installSelectField('MON_SMTP_ENC', $MON_SMTP_ENC, array('None','SSL','TLS'), 'None') . '
                <label>' . _('SMTP Auth') . '</label>
                ' . installSelectField('MON_SMTP_AUTH', $MON_SMTP_AUTH, array('No', 'Yes'), 'No') . '
                </div>
                <div class="form-group">
                <label>' . _('SMTP Auth Username') . '</label>
                ' . installTextField('MON_SMTP_USER', $MON_SMTP_USER, '') . '
                </div>
                <div class="form-group">
                <label>' . _('SMTP Auth Password') . '</label>
                ' . installTextField('MON_SMTP_PW', $MON_SMTP_PW, '') . '
                </div>
                <p>
                    <button type="submit" class="btn btn-default btn-core">' . _('Save Settings') . '</button>
                </p>
            </div>
            </div>';
        return $ret;
    }

    private function monitorConfiguration()
    {
        $mods = FannieAPI::listModules('\COREPOS\Fannie\API\monitor\Monitor');
        include(dirname(__FILE__) . '/../config.php');
        if (!isset($MON_ENABLED) || !is_array($MON_ENABLED)) {
            $MON_ENABLED = array();
        }
        $ret = '<div class="panel panel-default">
            <div class="panel-heading">
                <a href="" onclick="$(\'#mon-config\').toggle(); return false;">
                ' . _('Monitors Configuration') . '</a>
            </div>
            <div class="panel-body collapse" id="mon-config">
                <form method="post">
            <table class="table table-bordered small">';
        foreach ($mods as $mod) {
            $ret .= sprintf('<tr>
                <td><input type="checkbox" name="mon[]" value="%s" %s /></td>
                <td>%s</td>
                </tr>',
                str_replace('\\', ':', $mod),
                (in_array($mod, $MON_ENABLED) ? 'checked' : ''),
                $mod);
        }
        $ret .= '</table>
                <p>
                    <button type="submit" class="btn btn-default btn-core">' . _('Save Settings') . '</button>
                </p>
            </div>
            </div>';

        return $ret;
    }

    public function get_view()
    {
        $mods = FannieAPI::listModules('\COREPOS\Fannie\API\monitor\Monitor');
        $cache = unserialize(COREPOS\Fannie\API\data\DataCache::getFile('forever', 'monitoring'));
        if (!$cache) {
            return '<div class="alert alert-danger">' . _('No Dashboard data available. Is the Monitoring Task enabled?') . '</div>';
        }
        ob_start();
        echo $this->emailConfiguration();
        echo $this->monitorConfiguration();
        $enabled = $this->config->get('MON_ENABLED');
        foreach ($mods as $class) {
            if (is_array($enabled) && !in_array($class, $enabled)) {
                continue;
            }
            if (!isset($cache[$class])) {
                printf(_("No data for %s<br />"), $class);
            } else {
                $obj = new $class($this->config);
                $out = $obj->display($cache[$class]);
                echo '<strong>' . $class . '</strong><br />';
                echo $out . "<br />";
            }
        }

        return ob_get_clean();
    }

    public function helpContent()
    {
        return _('<p>The Dashboard configures monitoring modules and displays
            their most recent output.</p>
            <p>Click <em>Notification Configuration</em> to access email notification
            settings. An SMTP connection is required to send emails.</p>
            <p>Click <em>Monitors Configuration</em> to choose which monitors
            are enabled.</p>
            <p>The main section of the page displays each active monitor\'s
            most recent assessment of system status. If this page is blank
            make sure at least one monitor is active and the Monitoring Task
            is enabled in scheduled tasks.</p>');
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $phpunit->assertNotEquals(0, strlen($this->monitorConfiguration()));
        $phpunit->assertNotEquals(0, strlen($this->emailConfiguration()));
    }
}

FannieDispatch::conditionalExec();

