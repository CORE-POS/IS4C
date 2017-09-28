<?php

/*******************************************************************************

    Copyright 2017 Whole Foods Co-op

    This file is part of CORE-POS.

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
  @class ScaleLabelsModel
*/
class ScaleLabelsModel extends BasicModel
{
    protected $name = "ScaleLabels";
    protected $preferred_db = 'op';

    protected $columns = array(
    'scaleLabelID' => array('type'=>'INT', 'increment'=>true, 'primary_key'=>true),
    'labelType' => array('type'=>'SMALLINT'),
    'scaleType' => array('type'=>'VARCHAR(50)'),
    'mappedType' => array('type'=>'VARCHAR(50)'),
    'descriptionWidth' => array('type'=>'SMALLINT', 'default'=>0),
    'textWidth' => array('type'=>'SMALLINT', 'default'=>0),
    );


    public function doc()
    {
        return <<<MD
This table maps default label types to scale-specific label
types. For historical reasons the base types are random-looking
integers.

* Label Type 23
    - Fixed Weight
    - Vertical Alignment
    - No graphics
* Label Type 63
    - Fixed Weight
    - Horizontal Alignment
    - No graphics
* Label Type 103
    - Random Weight
    - Vertical Alignment
    - No graphics
* Label Type 113
    - Random Weight
    - Horizontal Alignment
    - No graphics
* Label Type 53
    - Random Weight
    - Vertical Alignment
    - Safe handling graphics

Scale Type refers to ServiceScales.scaleType. The mapped type
is the scale-specific equivalent of one of the above label types.

Some scales may not support all types. The system will try to
make the best choice from available options.

The width settings are for wordwrapping descriptions and
expanded text. A value of 0 means no wrapping is applied
by CORE itself and the scale and/or intermediary software
can apply any necessary wrapping.
MD;
    }
}

