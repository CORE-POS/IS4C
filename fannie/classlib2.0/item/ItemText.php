<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

    This file is part of CORE-POS.

    CORE-POS is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    CORE-POS is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

namespace COREPOS\Fannie\API\item;

/**
  @class ItemText
  Utility functions for manipulating item-related text in
  a consistent way
*/
class ItemText
{
    public static function longBrandSQL($primary='u', $secondary='p')
    {
        return self::pseudoCoalesce('brand', $primary, $secondary);
    }

    public static function longDescriptionSQL($primary='u', $secondary='p')
    {
        return self::pseudoCoalesce('description', $primary, $secondary);
    }

    public static function signSizeSQL($primary='p', $secondary='v')
    {
        return self::pseudoCoalesce('size', $primary, $secondary);
    }

    private static function pseudoCoalesce($field, $primary, $secondary)
    {
        return sprintf('CASE WHEN %s.%s IS NULL OR %s.%s=\'\' OR %s.%s=\'0\' THEN %s.%s ELSE %s.%s END AS %s',
            $primary, $field,
            $primary, $field,
            $primary, $field,
            $secondary, $field,
            $primary, $field,
            $field);
    }
}

