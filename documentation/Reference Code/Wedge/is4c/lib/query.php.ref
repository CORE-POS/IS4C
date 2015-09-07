<?php
    if (!function_exists("pDataConnect")) {
        include_once("/pos/is4c/connect.php");
    }

    /*Returns an associative array of active employees.*/
    function get_users() {
        $query =
            'SELECT emp_no,'
            . '    FirstName,'
            . '    LastName'
            . '    FROM employees'
            . '    WHERE empactive = 1;';
        $rows = sql_fetch_assoc_array(sql_query($query, pDataConnect()));
        return $rows;
    }

    /*Takes an employee number and returns information about the employee*/
    function get_user_info($emp_no) {
        $emp_no = mysql_real_escape_string($emp_no);
        $query =
            'SELECT FirstName,'
            . '    LastName'
            . '    FROM employees'
            . '    WHERE emp_no = \'' . $emp_no . '\''
            . '        AND empactive = 1;';
        $row = sql_fetch_array(sql_query($query, pDataConnect()));

        if ($row) {
            $employee["EmpNo"] = $emp_no;
            $employee["FirstName"] = $row["FirstName"];
            $employee["LastName"] = $row["LastName"];
            return $employee;
        }
        return false;
    }

    /*Takes a password and returns the employee number*/
    function user_pass($password) {
        $password = mysql_real_escape_string($password);
        $query =
            'SELECT emp_no'
            . '    FROM employees'
            . '    WHERE CashierPassword = \'' . $password . '\''
            . '        OR AdminPassword = \'' . $password . '\';';
        $rows = sql_fetch_assoc_array(sql_query($query, pDataConnect()));
        if ($rows)
        {
            return $rows['emp_no'];
        }
        else
        {
            return false;
        }
    }

    function user_pass_priv($password){
        $password = mysql_real_escape_string($password);
        $query =
            'SELECT emp_no'
            . '    FROM employees'
            . '    WHERE empactive = 1'
            . '        AND frontendsecurity >= 11'
            . '        AND (CashierPassword = \'' . $password . '\''
            . '            OR AdminPassword = \'' . $password . '\');';
        $rows = sql_fetch_assoc_array(sql_query($query, pDataConnect()));
        if ($rows)
        {
            return $rows['emp_no'];
        }
        else
        {
            return false;
        }
    }

    /*Takes an employee ID number, and return $true if that user is currently logged in*/
    function user_logged_in($emp_no) {
        $emp_no = mysql_real_escape_string($emp_no);
        $query =
            'SELECT LoggedIn'
            . '    FROM globalvalues'
            . '    WHERE CashierNo = \'' . $emp_no . '\''
            . '        AND LoggedIn = 0;';
        $num_rows = sql_num_rows(sql_query($query, pDataConnect()));

        if ($num_rows)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    //Returns the global values from opdata.globalvalues.
    function get_global_values() {
        $query =
            'SELECT *'
                . '    FROM globalvalues;';
        $row = sql_fetch_array(sql_query($query, pDataConnect()));
        if ($row)
        {
            $global_values["CashierNo"] = $row["CashierNo"];
            $global_values["CashierName"] = $row["Cashier"];
            $global_values["LoggedIn"] = $row["LoggedIn"];
            $global_values["TransNo"] = $row["TransNo"];
            $global_values["TTLFlag"] = $row["TTLFlag"];
            $global_values["FntlFlag"] = $row["FntlFlag"];
            $global_values["TaxExempt"] = $row["TaxExempt"];
            return $global_values;
        }
        else
        {
            return false;
        }
    }

    function get_configuration_groups_query() {
        $query =
            'SELECT group_id,
                group_name
                FROM `opdata`.`configurationGroups`
                WHERE group_id > 0;';
        $result = sql_query($query, pDataConnect());
        for ($i = 0; $i < sql_num_rows($result); $i++) {
            $row = sql_fetch_assoc_array($result);
            $conf_groups[$i] = $row;
        }
        return $conf_groups;
    }

    function get_configuration_group_settings_query($configuration_group) {
        $configuration_group = mysql_real_escape_string($configuration_group);
        $query =
            'SELECT `key`,
                value,
                type
                FROM `opdata`.`configuration`
                WHERE group_id = ' . $configuration_group;
        $result = sql_query($query, pDataConnect());
        for ($i = 0; $i < sql_num_rows($result); $i++) {
            $row = sql_fetch_assoc_array($result);
            $conf_settings[$i] = $row;
        }
        return $conf_settings;
    }

    function get_configurations() {
        $query =
            'SELECT `key`,
                value
                FROM `opdata`.`configuration`
                WHERE group_id > 0;';
        $result = sql_query($query, pDataConnect());
        for ($i = 0; $i < sql_num_rows($result); $i++) {
            $row = sql_fetch_assoc_array($result);
            $conf_settings[$i] = $row;
        }
        return $conf_settings;
    }

    // Checks for a list of configuration values that must be set for IS4C to operate.
    // If any of these configurations are not set, then it returen false.
    function configs_set() {
       	$query =
            'SELECT value
                FROM `opdata`.`configuration`
                WHERE `key` IN
                (
                    "OS",
                    "store",
                    "localhost",
                    "tDatabase",
                    "pDatabase",
                    "DBMS",
                    "localUser",
                    "laneno"
                );';
       	$result = sql_query($query, pDataConnect());
       	if ($result) {
        	for ($i = 0; $i < sql_num_rows($result); $i++) {
            	$row = sql_fetch_assoc_array($result);
            	if ($row["value"] == NULL)
            	{
                	return false;
            	}
        	}
        	return true;
       	} else {
			return false;
		}
    }

    function save_configurations($configurations) {
        // Set all flag fields to 0.
        // Those that have been checked will be reset back to 1.
        $query =
            'SELECT conf_id
                FROM `opdata`.`configuration`
                WHERE type = "flag";';
        $result = sql_query($query, pDataConnect());
        for ($i = 0; $i < sql_num_rows($result); $i++) {
            $row = sql_fetch_assoc_array($result);
            $query =
                'UPDATE `opdata`.`configuration`
                    SET value = 0
                    WHERE conf_id = "' . $row["conf_id"]  . '";';
            $execute = sql_query($query, pDataConnect());
        }
        // Save the configurations to the database.
        foreach($configurations as $key => $value) {
            $query =
                'UPDATE `opdata`.`configuration`
                    SET value = "' . $value . '"
                    WHERE `key` = "' . $key . '";';
            $result = sql_query($query, pDataConnect());
        }
    }
