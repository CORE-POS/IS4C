<?php
include(realpath(dirname(__FILE__).'/../lib/AutoLoader.php'));
AutoLoader::loadMap();
?>
<html>
<head>
<title>Database Updates</title>
<style type="text/css">
body {
	line-height: 1.5em;
}
</style>
<script type="text/javascript" src="../js/jquery.js"></script>
</head>
<body>
<?php include('tabs.php'); ?>
<div id="wrapper">
<h2>IT CORE Lane Installation: Database Updates</h2>
<?php
// apply selected update
if (isset($_REQUEST['mupdate'])) {
    $updateClass = $_REQUEST['mupdate'];
    echo '<div style="border: solid 1px #999; padding:10px;">';
    echo 'Attempting to update model: "'.$updateClass.'"<br />';
    if (!class_exists($updateClass)) {
        echo 'Error: class not found<br />';
    } elseif(!is_subclass_of($updateClass, 'BasicModel')) {
        echo 'Error: not a valid model<br />';	
    } else {
        $updateModel = new $updateClass(null);
        $db_name = InstallUtilities::normalizeDbName($updateModel->preferredDB());
        if ($db_name === false) {
            echo 'Error: requested database unknown';
        } else {
            ob_start();
            $changes = $updateModel->normalize($db_name, BasicModel::NORMALIZE_MODE_APPLY, true);
            $details = ob_get_clean();
            if ($changes === false) {
                echo 'An error occurred.';
            } else {
                echo 'Update complete.';
            }
            printf(' <a href="" onclick="$(\'#updateDetails\').toggle();return false;"
                >Details</a><pre style="display:none;" id="updateDetails">%s</pre>',
                $details);
        }
    }
    echo '</div>';
}

// list available updates
$cmd = new ReflectionClass('BasicModel');
$cmd = $cmd->getFileName();
$mods = AutoLoader::listModules('BasicModel');
$adds = 0;
$unknowns = 0;
$errors = 0;
echo '<ul>';
foreach($mods as $class) {

    $model = new $class(null);

    $db_name = InstallUtilities::normalizeDbName($model->preferredDB());
    if ($db_name === false) {
        echo '<li>Error: Unknown database "'.$model->preferredDB().'" for model '.$class;
        $errors++;
        continue;
    }

    ob_start();
    $changes = $model->normalize($db_name, BasicModel::NORMALIZE_MODE_CHECK);
    $details = ob_get_clean();

    if ($changes === false) {
        printf('<li>%s had errors.', $class);
        $errors++;
    } elseif($changes > 0) {
        printf('<li>%s has updates available.', $class);
        $adds += $changes;
    } elseif($changes < 0) {
        printf('<li>%s does not match the schema but cannot be updated.', $class);
        $unknowns += $changes;
    }

    if ($changes > 0) {
        printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
            >Details</a><br /><pre style="display:none;" id="mDetails%s">%s</pre><br />
            To apply changes <a href="updates.php?mupdate=%s">Click Here</a>
            or run the following command:<br />
            <pre>php %s --update %s %s</pre>
            </li>',
            $class, $class, $details, $class,
            $cmd, $db_name, $class
            );
    } else if ($changes < 0 || $changes === false) {
        printf(' <a href="" onclick="$(\'#mDetails%s\').toggle();return false;"
            >Details</a><br /><pre style="display:none;" id="mDetails%s">%s</pre></li>',
            $class, $class, $details
        );
    }
}
echo '</ul>';
echo '<hr />';
echo '<table cellspacing="0" cellpadding="4" border="1">';
echo '<tr><th colspan="2">Check Complete</td></tr>';
printf('<tr><td>Errors</td><td align="right">%d</td></tr>', $errors);
printf('<tr><td>Updates</td><td align="right">%d</td></tr>', $adds);
printf('<tr><td>Oddities</td><td align="right">%d</td></tr>', $unknowns);
?>
</div> <!--	wrapper -->
</body>
</html>
