<?php

function wfc_am_check_email($email, $name)
{
    if (!strstr($email,'@') && !preg_match('/\d+/',$email) &&
        $email != 'no email'){
        $name .= ' '.$email;  
        $email = '';
    }

    return array($email, $name);
}

function wfc_am_get_names($name)
{
    $lname = ""; $fname="";
    if (strstr($name,' ')){
        $name = trim($name);
        $parts = explode(' ',$name);
        if (count($parts) > 1){
            $lname = $parts[count($parts)-1];
            for($i=0;$i<count($parts)-1;$i++)
                $fname .= ' '.$parts[$i];
        }
        else if (count($parts) > 0)
            $lname = $parts[0];
    } else {
        $lname = $name;
    }

    return array($fname, $lname);
}

