<?php
    include_once("/pos/is4c/lib/initialize.php");
    if (!function_exists("get_users")) {
        include_once("/pos/is4c/lib/query.php");
    }

    function get_config_auto($config_item){
        switch ($config_item){
            case "OS":
                return ("<input type='button' value='Auto' onclick=\"$('#OS').attr('value', '" . strtolower(PHP_OS) . "'); return false\" />");
                break;
            case "localhost":
                return ("<input type='button' value='Auto' onclick=\"$('#localhost').attr('value', '" . $_SERVER['SERVER_ADDR'] . "'); return false\" />");
                break;
            default:
                return "";
        }
    }

    function get_configuration_groups(){
        $conf_groups = get_configuration_groups_query();
        return $conf_groups;
    }

    function get_configuration_settings($configuration_group){
        $conf_settings = get_configuration_group_settings_query($configuration_group);
        return $conf_settings;
    }

    function apply_configurations()
    {
        $conf_settings = get_configurations();
        foreach($conf_settings as $conf_setting) {
            $_SESSION[$conf_setting["key"]] = $conf_setting["value"];
        }
    }

    function is_config_set()
    {
        return configs_set();
    }
