<?php
/*******************************************************************************

    Copyright 2013 Whole Foods Co-op

    This file is part of IT CORE.

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

class FannieHelp {

    /**
      Build a standardized tooltip
      @param $text the full help text
      @param $doc_link URL into CORE documentation [optional]
      @param $tag HTML tag type for text [default is span]
      @return an HTML string
    */
    static public function toolTip($text, $doc_link=False, $tag='span')
    {
        global $FANNIE_URL;
        $id = '_fhtt'.rand(0, 999999);
        $img = $FANNIE_URL.'src/img/buttons/help16.png';

        $text = preg_replace('/\s\s+/',' ',$text);

        $snippet = strlen($text) > 100 ? strip_tags(substr($text,0,100)).' ...' : False;
        if ($snippet || $doc_link) {
            $snippet .= ' (Click for more)';
        }

        if ($doc_link) {
            if (!$snippet) {
                $snippet = $text;
            }
            $text .= sprintf(' (<a href="%s">CORE Documentation</a>)',$doc_link);
        }

        if ($snippet || $doc_link) {
            return sprintf('<a href="" 
                onclick="$(\'#%s\').toggle();return false;"><img src="%s" title="%s" /></a>
                <%s id="%s" style="display:none;">%s</%s>',
                $id, $img, $snippet,
                $tag, $id, $text, $tag
            );
        } else {
            return sprintf('<a href="" onclick="return false;"><img src="%s" title="%s" /></a>',
                    $img, $text
            );
        }
    }

}

