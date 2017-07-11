<?php

namespace COREPOS\pos\install\conf;

class ParamConf
{
    // @hintable
    static public function save($sql, $key, $value) 
    {
        list($value, $save_as_array) = self::paramValueToArray($value);

        $saved = false;
        if ($sql !== false && $sql !== null) {
            $prep = $sql->prepare('SELECT param_value FROM parameters
                                        WHERE param_key=? AND lane_id=?');
            $exists = $sql->execute($prep, array($key, \CoreLocal::get('laneno')));
            if ($sql->num_rows($exists)) {
                $prep = $sql->prepare('
                    UPDATE parameters 
                    SET param_value=?,
                        is_array=?,
                        store_id=0
                    WHERE param_key=? 
                        AND lane_id=?');
                $saved = $sql->execute($prep, array($value, $save_as_array, $key, \CoreLocal::get('laneno')));
            } else {
                $prep = $sql->prepare('INSERT INTO parameters (store_id, lane_id, param_key,
                                        param_value, is_array) VALUES (0, ?, ?, ?, ?)');
                $saved = $sql->execute($prep, array(\CoreLocal::get('laneno'), $key, $value, $save_as_array));
            }
        }

        return ($saved) ? true : false;
    }

    static private function paramValueToArray($value)
    {
        $save_as_array = 0;
        if (is_array($value) && count($value) > 0 && isset($value[0])) {
            // normal array (probably)
            $tmp = '';
            foreach($value as $v) {
                $tmp .= $v.',';
            }
            $value = substr($tmp, 0, strlen($tmp)-1);
            $save_as_array = 1;
        } elseif (is_array($value) && count($value) > 0){
            // array with meaningful keys
            $tmp = '';
            foreach($value as $k => $v) {
                $tmp .= $k.'=>'.$v.','; 
            }
            $value = substr($tmp, 0, strlen($tmp)-1);
            $save_as_array = 1;
        } elseif (is_array($value)) {
            // empty array
            $value = '';
            $save_as_array = 1;
        }

        return array($value, $save_as_array);
    }

}

