<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

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

//ini_set('display_errors','1');
include(dirname(__FILE__) . '/../config.php'); 
if (!class_exists('FannieAPI')) {
    include_once(dirname(__FILE__) . '/../classlib2.0/FannieAPI.php');
}
if (!function_exists('confset')) {
    include(dirname(__FILE__) . '/util.php');
}
if (!function_exists('dropDeprecatedStructure')) {
    include(dirname(__FILE__) . '/db.php');
}

/**
    @class InstallUpdatesPage
    Class for the Updates install and config options
*/
class InstallUpdatesPage extends \COREPOS\Fannie\API\InstallPage {

    protected $title = 'Fannie: Updates';
    protected $header = 'Fannie: Updates';

    public $description = "
    Class for the Updates install and config options page.
    ";

    private function normalize_db_name($name)
    {
        if ($name == 'op') {
            return $this->config->get('OP_DB');
        } elseif ($name == 'trans') {
            return $this->config->get('TRANS_DB');
        } elseif ($name == 'archive') {
            return $this->config->get('ARCHIVE_DB');
        } elseif (substr($name, 0, 7) == 'plugin:') {
            $settings = $this->config->get('PLUGIN_SETTINGS');
            $pluginDB = substr($name, 7);
            if (isset($settings[$pluginDB])) {
                return $settings[$pluginDB];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    function body_content(){
        ob_start();
        echo showInstallTabs('Updates');
?>
<p class="ichunk">Database Updates.</p>
<?php
        if (FormLib::get_form_value('mupdate') !== ''){
            $updateClass = FormLib::get_form_value('mupdate');
            echo '<div class="well">';
            echo 'Attempting to update model: "'.$updateClass.'"<br />';
            if (!class_exists($updateClass))
                echo '<div class="alert alert-danger">Error: class not found</div>';
            elseif(!is_subclass_of($updateClass, 'BasicModel'))
                echo '<div class="alert alert-danger">Error: not a valid model</div>';
            else {
                $updateModel = new $updateClass(null);
                $db_name = $this->normalize_db_name($updateModel->preferredDB());
                if ($db_name === False)
                    echo '<div class="alert alert-danger">Error: requested database unknown</div>';
                else {
                    ob_start();
                    $changes = $updateModel->normalize($db_name, BasicModel::NORMALIZE_MODE_APPLY, true);
                    $details = ob_get_clean();
                    if ($changes === False)
                        echo '<div class="alert alert-danger">An error occured applying the update</div>';
                    else
                        echo '<div class="alert alert-success">Update complete</div>';
                    printf(' <a href="" onclick="$(\'#updateDetails\').toggle();return false;"
                        >Details</a><pre class="collapse" id="updateDetails">%s</pre>',
                        $details);
                }
            }
            echo '</div>';
        }

        $obj = new BasicModel(null);
        $models = FannieAPI::listModules('BasicModel');
        $cmd = new ReflectionClass('BasicModel');
        $cmd = $cmd->getFileName();
        echo '<ul>';
        foreach($models as $class){
            $model = new $class(null);
            $db_name = $this->normalize_db_name($model->preferredDB());
            if ($db_name === False) continue;
        
            ob_start();
            $changes = $model->normalize($db_name, BasicModel::NORMALIZE_MODE_CHECK);
            $details = ob_get_clean();

            if ($changes === False){
                printf('<li>%s had errors.', $class);
            }
            elseif($changes > 0){
                printf('<li>%s has updates available.', $class);
            }
            elseif($changes < 0){
                printf('<li>%s does not match the schema but cannot be updated.', $class);
            }

            if ($changes > 0){
                $reflector = new ReflectionClass($class);
                $model_file = $reflector->getFileName();
                printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
                    >Details</a><br /><pre class="collapse" id="mDetails%s">%s</pre><br />
                    To apply changes <a href="InstallUpdatesPage.php?mupdate=%s">Click Here</a>
                    or run the following command:<br />
                    <pre>php %s --update %s %s</pre>
                    </li>',
                    $class, $class, $details, $class,
                    $cmd, $db_name, $model_file
                    );
            }
            else if ($changes < 0 || $changes === False){
                printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
                    >Details</a><br /><pre class="collapse" id="mDetails%s">%s</pre></li>',
                    $class, $class, $details
                );
            }
        }
        echo '</ul>';

        return ob_get_clean();
    // body_content
    }

    private static function versionSort($a, $b) 
    {
        $a_valid = preg_match('/^(.*?)(\d+)\D(\d+)\D(\d+)\D/', $a, $a_parts);
        $b_valid = preg_match('/^(.*?)(\d+)\D(\d+)\D(\d+)\D/', $b, $b_parts);
        if (!$a_valid && !$b_valid) {
            return 0;
        } elseif ($a_valid && !$b_valid) {
            return 1;
        } elseif (!$a_valid && $b_valid) {
            return -1;
        }

        if ($a_parts[2] > $b_parts[2]) { // major 
            return 1;
        } elseif ($a_parts[2] < $b_parts[2]) { // major 
            return -1;
        } elseif ($a_parts[3] > $b_parts[3]) { // minor 
            return 1;
        } elseif ($a_parts[3] < $b_parts[3]) { // minor
            return -1;
        } elseif ($a_parts[4] > $b_parts[4]) { // revision
            return 1;
        } elseif ($a_parts[4] < $b_parts[4]) { // revision
            return -1;
        } elseif (empty($a_parts[1]) && !empty($b_parts[1])) { // has prefix
            return 1;
        } elseif (!empty($a_parts[1]) && empty($b_parts[1])) { // has prefix
            return -1;
        } else {
            return 0;
        }
    }

// InstallUpdatesPage
}

FannieDispatch::conditionalExec();

