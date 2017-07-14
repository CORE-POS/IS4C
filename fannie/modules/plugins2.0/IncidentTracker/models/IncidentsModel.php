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
  @class IncidentsModel
*/
class IncidentsModel extends BasicModel
{
    protected $name = "Incidents";
    protected $preferred_db = 'plugin:IncidentDB';

    protected $columns = array(
    'incidentID' => array('type'=>'INT', 'primary_key'=>true, 'increment'=>true),
    'incidentTypeID' => array('type'=>'INT'),
    'incidentSubTypeID' => array('type'=>'INT'),
    'incidentLocationID' => array('type'=>'INT'),
    'tdate' => array('type'=>'DATETIME'),
    'reportedBy' => array('type'=>'TINYINT', 'default'=>0),
    'uid' => array('type'=>'INT'),
    'image1' => array('type'=>'VARCHAR(255)'),
    'image2' => array('type'=>'VARCHAR(255)'),
    'details' => array('type'=>'TEXT'),
    'trespass' => array('type'=>'TINYINT', 'default'=>0),
    'police' => array('type'=>'TINYINT', 'default'=>0),
    'storeID' => array('type'=>'INT'),
    );
}

