<?php
    if (!isset($_SESSION["DBMS"]) || empty($_SESSION['DBMS']))
    {
        $handle = fopen("/pos/db_connect", "r");
        $contents = fread($handle, filesize("/pos/db_connect"));
        fclose($handle);

        preg_match('/[\r\n]?database:[^\r\n]*\n/', $contents, $match);
        preg_match('/[^: ]+$/', $match[0], $match);
        $_SESSION["DBMS"] = trim($match[0]);

        preg_match('/[\r\n]?server:[^\r\n]*\n/', $contents, $match);
        preg_match('/[^: ]*$/', $match[0], $match);
        $_SESSION["localhost"] = trim($match[0]);

        preg_match('/[\r\n]?schemas:[^\r\n]*\n/', $contents, $match);
        preg_match('/[^: ]*$/', $match[0], $match);
        $_SESSION["pDatabase"] = trim($match[0]);

        preg_match('/[\r\n]?username:[^\r\n]*\n/', $contents, $match);
        preg_match('/[^: ]*$/', $match[0], $match);
        $_SESSION["localUser"] = trim($match[0]);

        preg_match('/[\r\n]?password:[^\r\n]*\n/', $contents, $match);
        preg_match('/[^: ]*$/', $match[0], $match);
        $_SESSION["localPass"] = trim($match[0]);
    } else {
    }
?>
