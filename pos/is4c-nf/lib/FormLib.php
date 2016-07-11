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

namespace COREPOS\pos\lib;
 
// wrapper for backwards compatibility
class FormLib extends \COREPOS\common\FormLib
{
    /**
      Validate submitted token
      @param $name [string, default crsfToken] name of form field
      @return [boolean] 

      Tokens are one-time-use. Upon validation, that token
      is removed from the cache
    */
    public static function validateToken($name='crsfToken')
    {
        $my_token = self::get($name);
        if ($my_token === '') {
            return false;
        }

        $tokens = \CoreLocal::get('crsfTokens');
        if (!is_array($tokens)) {
            $tokens = array();
        }
        foreach (array_keys($tokens) as $valid_token) {
            if ($valid_token === $my_token) {
                unset($tokens[$valid_token]);
                \CoreLocal::set('crsfTokens', $tokens);

                return true;
            }
        }

        return false;
    }

    /**
      Drop all cached tokens
      @return [boolean] true
    */
    public static function clearTokens()
    {
        \CoreLocal::set('crsfTokens', array());

        return true;
    }

    /**
      Store token in cache
      @return [boolean] true
    */
    public static function setToken($token)
    {
        $tokens = \CoreLocal::get('crsfTokens');
        if (!is_array($tokens)) {
            $tokens = array();
        }
        $tokens[$token] = time();
        \CoreLocal::set('crsfTokens', $tokens);

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
            $token = sha1(mt_rand(0,mt_getrandmax()));
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
}

