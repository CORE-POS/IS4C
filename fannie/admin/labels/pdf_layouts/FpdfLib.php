<?php
class FpdfLib 
{
    /**
     *  static method strtolower_inpara
     *  change all chars to lower case inside parenthesis
     **/
    static function strtolower_inpara($str)
    {
        preg_match_all('#\((.*?)\)#', $str, $match);
        foreach ($match as $k => $array) {
            if ($k == 0) {
                foreach ($array as $j => $line) {
                    $lower = strtolower($line);
                    $newstr = str_replace($line, $lower, $str);
                    $str = $newstr;
                }
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

}
