<?php

namespace COREPOS\common\sql;

class CharSets
{
    static private $MAP = array(
        'mysql' => array(
            'iso-8859-1' => 'latin1',
            'cp-1252' => 'latin1',
            'utf-8' => 'utf8',
        ),
        'pgsql' => array(
            'iso-8859-1' => 'LATIN1',
            'cp-1252' => 'WIN1252',
            'utf-8' => 'UTF8',
        ),
    );

    public static function get($sql_flavor, $http_encoding)
    {
        $sql_flavor = strtolower($sql_flavor);
        $http_encoding = strtolower($http_encoding);
        foreach (self::$MAP as $flavor => $submap) {
            if (strpos($sql_flavor, $flavor) !== false) {
                if (isset($submap[$http_encoding])) {
                    return $submap[$http_encoding];
                }
            }
        }

        return false;
    }
}

