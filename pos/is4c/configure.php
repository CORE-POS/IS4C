<?php
    include_once("lib/conf.php");
	include_once("connect.php");
    include_once("lib/query.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>Lane Configuration</title>
        <link rel="stylesheet" type="text/css" href="css/is4c.css" />
    </head>
    <script type="text/javascript" src="/js/jquery-1.4.2.min.js"></script>
    <body>
        <table id='login'>
            <tr>
                <td id='is4c_header'>
                    <b>I S 4 C</b>
                </td>
                <td id='full_header'>
                    L A N E &nbsp; C O N F I G U R A T I O N
                </td>
            </tr>
            <tr>
                <td id='line' colspan='2'></td>
            </tr>
            <tr>
                <td id='welcome'>
                   S E T U P
                </td>
                <td></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>
                    <p>
                        <?php
                            if (isset($_SESSION["config_saved"]) && $_SESSION["config_saved"]) {?>
                                Configurations Saved
                            <?php
                                $_SESSION["config_saved"] = FALSE;
                            }
                        ?>
                    </p>
                    <div class='config_forms'>
                        <form action='/lib/apply_configurations.php' method='post'>
                        	<input type='submit' value='Save Changes'/>
                            <?php
                                $groups = get_configuration_groups();
                                foreach ($groups as $group) {
                            ?>
                            <fieldset>
	                        	
                                <legend><?=$group["group_name"]?></legend>
                                <?php
                                    $configs = get_configuration_settings($group["group_id"]);
                                    foreach ($configs as $config) {
                                ?>
                                <label for='<?=$config["key"]?>'><?=$config["key"]?>:</label>
                                <input type='<?=$config["type"]=="flag"?'checkbox':'text'?>' <?=$config["type"]=="flag"&&$config["value"]==1?'checked=\'checked\'':''?> value='<?=$config["type"]=="flag"?1:$config["value"]?>' name='<?=$config["key"]?>' id='<?=$config["key"]?>' /> <?=get_config_auto($config["key"])?> <br />
                                <?php } ?>
                                    
                            </fieldset>
                                <?php
                                }
                            ?>
                        </form>
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>
