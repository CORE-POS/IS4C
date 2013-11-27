<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of Fannie.

    IT CORE is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IT CORE is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/**
  Build a class "FC" containing the contents of
  Fannie's config.php as static properties.  

  You can't add static properties to a class after
  it's defined so making this work dynamically
  is a bit wacky. fc_init() reads config.php, 
  parses out the variables, and writes a temporary
  file containing PHP code that defines the class.
  It then includes that temporary file.

  The point of this experiment is to avoid needing
  "global" keywords. Instead of writing "global $FANNIE_OP_DB"
  at the beginning of a method you can use FC::$FANNIE_OP_DB.
  If that proves useful, this probably needs some
  performance optimization with file hashes or something
  to avoid constantly rebuilding the class defintion.
  That really only needs to happen if config.php
  changes, but then the definition should live somewhere
  predictable rather than in a temporary file.
*/
function fc_init()
{
    $config = dirname(__FILE__).'/../config.php';

    if (file_exists($config))
    {
        include($config);
        $tmp = tempnam(sys_get_temp_dir(),'');
        $fp = fopen($tmp,'w');
        $symbols = array();
        fwrite($fp,'<?php class FC { ');
        foreach(token_get_all(file_get_contents($config)) as $token) {
            if ($token[0] != T_VARIABLE) {
                continue;
            }

            $symbol = substr($token[1],1);
            $symbols[$symbol] = $$symbol;
            fwrite($fp, 'static public $'.$symbol.';');
            /**
              If the variable starts with FANNIE_, create
              a shorthand. E.g., FC:$FANNIE_OP_DB and
              FC:$OP_DB are both valid references with
              the same value.
            */
            if (substr($symbol, 0, 7) == 'FANNIE_') {
                $symbols[substr($symbol,7)] = $$symbol;
                fwrite($fp, 'static public $'.substr($symbol, 7).';');
            }
        }
        fwrite($fp,'} ?>');
        fclose($fp);
        include($tmp);
        unlink($tmp);
        foreach($symbols as $name => $value) {
            FC::$$name = $value;
        }
    }
}

if (!class_exists('FC')) {
    fc_init();
}

?>
