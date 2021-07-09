<?php
class FpdfLib 
{
    static function abbreviation_to_upper($text)
    {
        $ABBREV= array('MN', 'WI', 'BBQ', 'TVP', 'TSP');
        $chunks = explode(' ', $text);
        $new_text = "";
        foreach ($chunks as $chunk) {
            if (in_array(strtoupper($chunk), $ABBREV)) {
                $chunk = strtoupper($chunk);
            }
            $new_text .= $chunk . " ";
        }

        return $new_text;
    }

    /**
     *  static method strtolower_inpara
     *  change all chars to lower case inside parenthesis
     **/
    static function strtolower_inpara($str)
    {
        $newstr = '';
        preg_match_all('#\((.*?)\)#', $str, $match);
        foreach ($match as $k => $array) {
            if ($k == 0) {
                foreach ($array as $j => $line) {
                    $lower = strtolower($line);
                    $newstr = str_replace($line, $lower, $str);
                    $str = $newstr;
                }
            } else {
                $newstr = $str;
            }
        }

        return $newstr;
    }


    /**
     *  static method strtolower_inpara
     *  remove "contains" statments from 
     *  string then create g single contains
     *  line at end of string 
     **/
    static function simplify_contains($str)
    {
        $newstr = '';
        $lines = explode("\n", $str);
        $removed = array();
        $tmp = '';
        foreach ($lines as $line) {
            if (strpos($line, "Contains") != false) {
                $pos = strpos($line, "Contains");
                $len = strlen($line);
                $tmp = substr($line, $pos);
                $tmp = strtolower($tmp);
                $tmp = explode(",", $tmp);
                foreach ($tmp as $v) {
                    if (!in_array($v, $removed)) {
                        $v = str_replace(' ', '', $v);
                        $pos2 = strpos($v, ':');
                        $v = str_replace('.', '', $v);
                        $v = str_replace(':', '', $v);
                        $ret_str = substr($v, $pos2);
                        if ($ret_str != '')
                            $removed[] = $ret_str;
                    }
                }
                $line = substr($line, 0, $pos - $len); 
            }
            $newstr .= $line."\n";
        }

        $tmp = 'Contains: ';
        $removed = array_unique($removed);
        $arr = array();
        foreach ($removed as $v) {
            if (strlen($v)  > 1 && !in_array(strtolower($v), $arr)) {
                $arr[] = strtolower($v);
            }
        }
        foreach ($arr as $k => $v) {
            $tmp .= ucwords($v); 
            if (array_key_exists($k+1, $arr)) {
                $tmp .= ", ";
            }
        }

        return $newstr .  $tmp;
    }


    static function getKeyByUpc($data, $upc)
    {
        foreach ($data as $k => $row) {
            if ($row['upc'] == $upc)
                return $k;
        }

        return rand();
    }

    static function sortProductsByPhysicalLocation($dbc, $data, $storeID)
    {

        $upcs = array();
        foreach ($data as $k => $row) {
            $upcs[] = $row['upc']; 
        }
        list($inStr, $args) = $dbc->safeInClause($upcs);
        $args[] = $storeID;
        $query = "SELECT p.upc, f.sections FROM FloorSectionsListView AS f INNER JOIN products AS p ON f.upc=p.upc WHERE p.upc IN ($inStr) AND storeID = ? ORDER BY sections;";
        $prep = $dbc->prepare($query);
        $res = $dbc->execute($prep, $args);

        $i = 0;
        while ($row = $dbc->fetchRow($res)) {
            $tmpKey = self::getKeyByUpc($data, $row['upc']);
            $data[$tmpKey]['order'] = $i;
            $i++;
        }

        $newData = array();
        $i = 0;
        $endOfList = 9999;
        foreach ($data as $k => $row) {
            $order = isset($row['order']) ? $row['order'] : $endOfList;
            //$order = $i;
            foreach ($row as $name => $value){
                $newData[$order][$name] = $value;
            }
            $i++;
            $endOfList++;
        }
        ksort($newData);

        return $newData;
    }

}
