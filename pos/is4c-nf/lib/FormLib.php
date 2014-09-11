<?php
/*******************************************************************************

  Copyright 2014 Whole Foods Co-op

  This file is part of IT CORE.

  IT CORE is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  IT CORE is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  in the file license.txt along with IT CORE; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

*********************************************************************************/
 
class FormLib extends LibraryClass
{
    /**
      Drop all cached tokens
      @return [boolean] true
    */
    public static function clearTokens()
    {
        global $CORE_LOCAL;
        $CORE_LOCAL->set('crsfTokens', array());

        return true;
    }

    /**
      Store token in cache
      @return [boolean] true
    */
    public static function setToken($token)
    {
        global $CORE_LOCAL;
        $tokens = $CORE_LOCAL->get('crsfTokens');
        if (!is_array($tokens)) {
            $tokens = array();
        }
        $tokens[$token] = time();
        $CORE_LOCAL->set('crsfTokens', $tokens);

        return true;
    }

    /**
      Create a unique token and store it in cache
      @return [string] token
    */
    public static function generateToken()
    {
        $token = '';
        if (function_exists("hash_algos") && in_array("sha512",hash_algos())) {
            $token=hash("sha512",mt_rand(0,mt_getrandmax()));
        } else {
            for ($i=0;$i<128;++$i) {
                $r=mt_rand(0,35);
                if ($r<26) {
                    $c=chr(ord('a')+$r);
                } else {
                    $c=chr(ord('0')+$r-26);
                } 
                $token.=$c;
            }
        }
        self::setToken($token);

        return $token;
    }

    /**
      Get a hidden <input> with a valid token value
      @param $name [string, default crsfToken] name of form field
      @return [string] html field
    */
    public static function tokenField($name='crsfToken')
    {
        $new_token = self::generateToken();

        return sprintf('<input type="hidden" name="%s" value="%s" />',
                $name, $new_token);
    }

    /**
      Validate submitted token
      @param $name [string, default crsfToken] name of form field
      @return [boolean] 

      Tokens are one-time-use. Upon validation, that token
      is removed from the cache
    */
    public static function validateToken($name='crsfToken')
    {
        global $CORE_LOCAL;
        $my_token = self::get($name);
        if ($my_token === '') {
            return false;
        }

        $tokens = $CORE_LOCAL->get('crsfTokens');
        if (!is_array($tokens)) {
            $tokens = array();
        }
        foreach (array_keys($tokens) as $valid_token) {
            if ($valid_token === $my_token) {
                unset($tokens[$valid_token]);
                $CORE_LOCAL->set('crsfTokens', $tokens);

                return true;
            }
        }

        return false;
    }

    /**
      Get a form field value
      @param $name [string] field name
      @param $default_value [mixed, default empty string] value used
        if not present in GET or POST
      @return [mixed] field value
    */
    public static function get($name, $default_value='')
    {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        } elseif (isset($_POST[$name])) {
            return $_POST[$name];
        } else {
            return $default_value;
        }
    }
}

