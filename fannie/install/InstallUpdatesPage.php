<?php
/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of Fannie.

    Fannie is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    Fannie is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

//ini_set('display_errors','1');
include('../config.php'); 
include('updates/Update.php');
include('util.php');
include('db.php');
include_once('../classlib2.0/FannieAPI.php');
include_once('../cron/tasks/GiterateTask.php');

/**
    @class InstallUpdatesPage
    Class for the Updates install and config options
*/
class InstallUpdatesPage extends InstallPage {

    protected $title = 'Fannie: Updates';
    protected $header = 'Fannie: Updates';

    public $description = "
    Class for the Updates install and config options page.
    ";

    // This replaces the __construct() in the parent.
    public function __construct() {

        // To set authentication.
        FanniePage::__construct();

        // Link to a file of CSS by using a function.
        $this->add_css_file("../src/style.css");
        $this->add_css_file("../src/javascript/jquery-ui.css");
        $this->add_css_file("../src/css/install.css");

        // Link to a file of JS by using a function.
        $this->add_script("../src/javascript/jquery.js");
        $this->add_script("../src/javascript/jquery-ui.js");

    // __construct()
    }

    // If chunks of CSS are going to be added the function has to be
    //  redefined to return them.
    // If this is to override x.css draw_page() needs to load it after the add_css_file
    /**
      Define any CSS needed
      @return A CSS string
    function css_content(){
        $css ="";
        return $css;
    //css_content()
    }
    */

    // If chunks of JS are going to be added the function has to be
    //  redefined to return them.
    /**
      Define any javascript needed
      @return A javascript string
    function javascript_content(){
        $js ="";
        return $js;

    }
    */

    private function normalize_db_name($name){
        global $FANNIE_OP_DB, $FANNIE_TRANS_DB, $FANNIE_ARCHIVE_DB;
        if ($name == 'op') return $FANNIE_OP_DB;
        elseif($name == 'trans') return $FANNIE_TRANS_DB;
        elseif($name == 'archive') return $FANNIE_ARCHIVE_DB;
        else return False;
    }

    function body_content(){
        ob_start();
        echo showInstallTabs('Updates');
?>

<h1 class="install"><?php echo $this->header; ?></h1>
<p class="ichunk">Database Updates.</p>
<?php
        if (FormLib::get_form_value('mupdate') !== ''){
            $updateClass = FormLib::get_form_value('mupdate');
            echo '<div style="border: solid 1px #999; padding:10px;">';
            echo 'Attempting to update model: "'.$updateClass.'"<br />';
            if (!class_exists($updateClass))
                echo 'Error: class not found<br />';
            elseif(!is_subclass_of($updateClass, 'BasicModel'))
                echo 'Error: not a valid model<br />';  
            else {
                $updateModel = new $updateClass(null);
                $db_name = $this->normalize_db_name($updateModel->preferredDB());
                if ($db_name === False)
                    echo 'Error: requested database unknown';
                else {
                    ob_start();
                    $changes = $updateModel->normalize($db_name, BasicModel::NORMALIZE_MODE_APPLY);
                    $details = ob_get_clean();
                    if ($changes === False)
                        echo 'An error occurred.';
                    else
                        echo 'Update complete.';
                    printf(' <a href="" onclick="$(\'#updateDetails\').toggle();return false;"
                        >Details</a><pre style="display:none;" id="updateDetails">%s</pre>',
                        $details);
                }
            }
            echo '</div>';
        }

        $obj = new BasicModel(null);
        $models = $obj->getModels();
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
                    >Details</a><br /><pre style="display:none;" id="mDetails%s">%s</pre><br />
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
                    >Details</a><br /><pre style="display:none;" id="mDetails%s">%s</pre></li>',
                    $class, $class, $details
                );
            }
        }
        echo '</ul>';
?>
<hr />
<p class="ichunk">CORE Updates.</p>
<em>This is new; consider it alpha-y. Commit any changes before running an update.</em><br />
<?php
        $giterate_info = DataCache::check('GiterateTask');
        if ($giterate_info == false) {
            echo 'Updater has not been run recently. See <b>Check for Updates</b> task';
            echo '<br />';
            echo '<a href="../cron/management/CronManagementPage.php">Manage Scheduled Tasks</a>';
        } else {
            echo $giterate_info;
            if (strstr($giterate_info, 'New version ')) {
                echo '<br />To apply update, run: ';
                echo '<pre>' . GiterateTask::genCommand() . ' --update</pre>';
            }
        }
        return ob_get_clean();

    // body_content
    }

// InstallUpdatesPage
}

FannieDispatch::conditionalExec(false);

?>
