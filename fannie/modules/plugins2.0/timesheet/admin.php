<?php # admin.php - General page to admin timesheet stuff. Protected by apache password.
    $page_title = "Fannie - Admin Module";
    $header = "Timesheet Administration";
    ob_start();
// if (!isset($_GET['confirm'])) require_once('../includes/header.html');
    if (!isset($_GET['confirm']));
    
    if (isset($_GET['function'])) {
        // Pick a function...view, add.
        switch ($_GET['function']) {
            case 'view':
                $mod = 'view';
                break;
            
            case 'add':
                $mod = 'add';
                break;
            
            case 'delete':
                $mod = 'delete';
                break;
            
            case 'edit':
                $mod = 'view';
                break;
            
            default:
                $mod = 'main';
        }
    } else {
        // Display main.
        $mod = 'main';
    }
    
    if (file_exists('./admin/' . $mod . '.php')) {
        require_once('./admin/' . $mod . '.php');
    } else {
        require_once('./admin/main.php');
    }
    
//    require_once('../includes/footer.html');
    ob_end_flush();
?>
