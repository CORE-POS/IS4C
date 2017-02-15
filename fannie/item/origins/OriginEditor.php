<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

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

include_once(dirname(__FILE__).'/../../config.php');
if (!class_exists('FannieAPI')) {
    include_once($FANNIE_ROOT.'classlib2.0/FannieAPI.php');
}

class OriginEditor extends FannieRESTfulPage 
{
    protected $header = 'Product Origins';
    protected $title = 'Product Origins';

    public $description = '[Origins Editor] manages complex data about where items come from
    geographically.';

    public function preprocess()
    {
        $this->__routes[] = 'get<country>';
        $this->__routes[] = 'get<new_country>';
        $this->__routes[] = 'get<state>';
        $this->__routes[] = 'get<new_state>';
        $this->__routes[] = 'get<custom>';
        $this->__routes[] = 'get<new_custom>';
        $this->__routes[] = 'post<countryID><name><abbr>';
        $this->__routes[] = 'post<stateID><name><abbr>';
        $this->__routes[] = 'post<customID><name>';
        $this->__routes[] = 'post<originID><custom><state><country><local>';
        $this->__routes[] = 'post<newCustom><newState><newCountry>';

        return parent::preprocess();
    }

    public function post_originID_custom_state_country_local_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginsModel($dbc);

        for ($i=0; $i<count($this->originID); $i++) {
            $country = isset($this->country[$i]) ? $this->country[$i] : null;
            $state = isset($this->state[$i]) ? $this->state[$i] : null;
            $custom = isset($this->custom[$i]) ? $this->custom[$i] : null;

            if (!$country && !$state && !$custom) {
                // at least one FK required
                continue;
            }

            $local = (isset($this->local[$i]) && $this->local[$i]) ? 1 : 0;

            $model->originID($this->originID[$i]);
            $model->customID($custom);
            $model->stateProvID($state);
            $model->countryID($country);
            $model->local($local);
            $model->save();
        }

        $this->normalizeOriginNames();

        return 'OriginEditor.php';
    }

    public function post_newCustom_newState_newCountry_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginsModel($dbc);

        if ($this->newCustom || $this->newState || $this->newCountry) {
            // at least one FK required
            $model->customID($this->newCustom);
            $model->stateProvID($this->newState);
            $model->countryID($this->newCountry);
            $model->local(0);
            $model->save();

            $this->normalizeOriginNames();
        }

        return 'OriginEditor.php';
    }

    public function get_new_country_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginCountryModel($dbc);
        $model->name('0 New Country Entry');
        $model->save();

        return 'OriginEditor.php?country=1';
    }

    public function get_new_state_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginStateProvModel($dbc);
        $model->name('0 New State/Prov Entry');
        $model->save();

        return 'OriginEditor.php?state=1';
    }

    public function get_new_custom_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginCustomRegionModel($dbc);
        $model->name('0 New Custom Region Entry');
        $model->save();

        return 'OriginEditor.php?custom=1';
    }

    public function post_countryID_name_abbr_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginCountryModel($dbc);

        for($i=0; $i<count($this->countryID); $i++) {
            if ($this->hasEntry($this->name, $i) && $this->hasEntry($this->abbr, $i)) {

                $model->countryID($this->countryID[$i]);
                $this->saveOrDelete($model, $model->countryID(), $i);
            }
        }

        return 'OriginEditor.php?country=1';
    }

    public function post_stateID_name_abbr_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginStateProvModel($dbc);

        for($i=0; $i<count($this->stateID); $i++) {
            if ($this->hasEntry($this->name, $i) && $this->hasEntry($this->abbr, $i)) {

                $model->stateProvID($this->stateID[$i]);
                $this->saveOrDelete($model, $model->stateProvID(), $i);
            }
        }

        return 'OriginEditor.php?state=1';
    }

    public function post_customID_name_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginCustomRegionModel($dbc);

        $delete = FormLib::get('delete', array());

        for($i=0; $i<count($this->customID); $i++) {
            if (!isset($this->name[$i])) {
                continue;
            } else if (empty($this->name[$i])) {
                continue;
            }

            $model->customID($this->customID[$i]);

            if (in_array($this->customID[$i], $delete)) {
                $model->delete();
            } else {
                $model->name($this->name[$i]);
                $model->save();
            }
        }

        return 'OriginEditor.php?custom=1';
    }

    public function get_country_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $countries = new OriginCountryModel($dbc);

        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">';
        $ret .= '<h3>Edit Countries</h3>';
        $ret .= '<p>';
        $ret .= '<button type="submit" class="btn btn-default">Save Countries</button>';
        $ret .= $this->spacer()
            . $this->newButton('new_country')
            . $this->spacer()
            . $this->homeButton();
        $ret .= '</p>';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>Name</th><th>Abbreviation</th>
                <th><img alt="delete" src="' . $FANNIE_URL . 'src/img/buttons/trash.png' . '" /></th>
                </tr>';
        foreach ($countries->find('name') as $c) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="countryID[]" value="%d" />
                            <td><input type="text" name="name[]" class="form-control" value="%s" /></td>
                            <td><input type="text" name="abbr[]" class="form-control" value="%s" /></td>
                            <td><input type="checkbox" name="delete[]" value="%d" /></td>
                            </tr>',
                            $c->countryID(),
                            $c->name(),
                            $c->abbr(),
                            $c->countryID()
            );
        }
        $ret .= '</table>';
        $ret .= '<p>';
        $ret .= '<button type="submit" class="btn btn-default">Save Countries</button>';
        $ret .= $this->spacer()
            . $this->homeButton();
        $ret .= '</p>';
        $ret .= '</form>';

        return $ret;
    }

    public function get_state_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $states = new OriginStateProvModel($dbc);

        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">';
        $ret .= '<h3>Edit States &amp; Provinces</h3>';
        $ret .= '<p>';
        $ret .= '<button type="submit" class="btn btn-default">Save</button>';
        $ret .= $this->spacer()
            . $this->newButton('new_state')
            . $this->spacer()
            . $this->homeButton();
        $ret .= '</p>';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>Name</th><th>Abbreviation</th>
                <th><img alt="delete" src="' . $FANNIE_URL . 'src/img/buttons/trash.png' . '" /></th>
                </tr>';
        foreach ($states->find('name') as $s) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="stateID[]" value="%d" />
                            <td><input type="text" name="name[]" class="form-control" value="%s" /></td>
                            <td><input type="text" name="abbr[]" class="form-control" value="%s" /></td>
                            <td><input type="checkbox" name="delete[]" value="%d" /></td>
                            </tr>',
                            $s->stateProvID(),
                            $s->name(),
                            $s->abbr(),
                            $s->stateProvID()
            );
        }
        $ret .= '</table>';
        $ret .= '<p>';
        $ret .= '<button type="submit" class="btn btn-default">Save</button>';
        $ret .= $this->spacer()
            . $this->homeButton();
        $ret .= '</p>';
        $ret .= '</form>';

        return $ret;
    }

    public function get_custom_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $customs = new OriginCustomRegionModel($dbc);

        $ret = '<form action="' . filter_input(INPUT_SERVER, 'PHP_SELF') . '" method="post">';
        $ret .= '<h3>Edit Custom Regions</h3>';
        $ret .= '<p>';
        $ret .= '<button type="submit" class="btn btn-default">Save Regions</button>';
        $ret .= $this->spacer()
            . $this->newButton('new_custom')
            . $this->spacer()
            . $this->homeButton();
        $ret .= '</p>';
        $ret .= '<table class="table">';
        $ret .= '<tr><th>Name</th>
                <th><img alt="delete" src="' . $FANNIE_URL . 'src/img/buttons/trash.png' . '" /></th>
                </tr>';
        foreach ($customs->find('name') as $c) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="customID[]" value="%d" />
                            <td><input type="text" name="name[]" class="form-control" value="%s" /></td>
                            <td><input type="checkbox" name="delete[]" value="%d" /></td>
                            </tr>',
                            $c->customID(),
                            $c->name(),
                            $c->customID()
            );
        }
        $ret .= '</table>';
        $ret .= '<p>';
        $ret .= '<button type="submit" class="btn btn-default">Save Regions</button>';
        $ret .= $this->spacer()
            . $this->homeButton();
        $ret .= '</p>';
        $ret .= '</form>';

        return $ret;
    }

    public function get_view()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $this->normalizeOriginNames();

        $customs = array();
        $model = new OriginCustomRegionModel($dbc);
        foreach ($model->find() as $m) {
            $customs[$m->customID()] = $m->name();
        }
        $states = array();
        $model = new OriginStateProvModel($dbc);
        foreach ($model->find() as $m) {
            $states[$m->stateProvID()] = $m->name();
        }
        $countries = array();
        $model = new OriginCountryModel($dbc);
        foreach ($model->find() as $m) {
            $countries[$m->countryID()] = $m->name();
        }

        $origins = new OriginsModel($dbc);
        $self = filter_input(INPUT_SERVER, 'PHP_SELF');
        $ret = <<<HTML
<form action="{$self}" method="post">
    <h3>Edit Origins</h3>
    <table class="table">
        <tr>
            <th>Short Name</th>
            <th>Full Name</th>
            <th><a href="{$self}?custom=1">Region</a></th>
            <th><a href="{$self}?state=1">State/Prov</a></th>
            <th><a href="{$self}?country=1">Country</a></th>
            <th>Local</th>
        </tr>
HTML;
        foreach ($origins->find('name') as $o) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="originID[]" value="%d" />
                            <td>%s</td>
                            <td>%s</td>',
                            $o->originID(),
                            $o->shortName(),
                            $o->name()
            );

            $ret .= '<td><select name="custom[]" class="form-control"><option value="">n/a</option>';
            foreach ($customs as $id => $label) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            ($id == $o->customID() ? 'selected' : ''),
                            $id, $label);
            }
            $ret .= '</select></td>';

            $ret .= '<td><select name="state[]" class="form-control"><option value="">n/a</option>';
            foreach ($states as $id => $label) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            ($id == $o->stateProvID() ? 'selected' : ''),
                            $id, $label);
            }
            $ret .= '</select></td>';

            $ret .= '<td><select name="country[]" class="form-control"><option value="">n/a</option>';
            foreach ($countries as $id => $label) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            ($id == $o->countryID() ? 'selected' : ''),
                            $id, $label);
            }
            $ret .= '</select></td>';

            $ret .= sprintf('<td><input type="checkbox" name="local[]" %s /></td>',
                        $o->local() == 1 ? 'checked' : '');

            $ret .= '</tr>';
        }
        $ret .= '</table>';
        $ret .= '<p><button type="submit" class="btn btn-default">Save Origins</button></p>';
        $ret .= '</form>';

        $customOpts = $this->arrayToSelect($customs);
        $stateOpts = $this->arrayToSelect($states);
        $countryOpts = $this->arrayToSelect($countries);
        $ret .= <<<HTML
<hr />
<form action="{$self}" method="post">
    <h3>Create New Origin</h3>
    <table class="table">
        <tr>
            <th><a href="{$self}?custom=1">Region</a></th>
            <th><a href="{$self}'?state=1">State/Prov</a></th>
            <th><a href="{$self}?country=1">Country</a></th>
        </tr>
        <tr>
            <td><select name="newCustom" class="form-control"><option value="">n/a</option>
                {$customOpts}
            </select></td>
            <td><select name="newState" class="form-control"><option value="">n/a</option>
                {$stateOpts}
            </select></td>
            <td><select name="newCountry" class="form-control"><option value="">n/a</option>
                {$countryOpts}
            </select></td>
        </tr>
    </table>
    <p><button type="submit" class="btn btn-default">Create</button></p>
</form>
HTML;

        return $ret;
    }

    private function arrayToSelect($arr)
    {
        $ret = '';
        foreach ($arr as $id => $label) {
            $ret .= sprintf('<option value="%d">%s</option>',
                        $id, $label);
        }

        return $ret;
    }

    /**
      The origins table references up to three
      other tables: originCountry, originStateProv,
      and originCustomRegion. origins.name and
      origins.shortName are derived from these other
      tables.

      THIS METHOD WILL UPDATE THE NAME FIELDS IN THE
      ORIGINS TABLE.

      origins.shortName will simply use the first
      one of these rules that works.
      1. corresponding originCustomRegion.name, if present
      2. corresponding originStateProv.name, if present
      3. corresponding originCountry.name, if present

      origins.name will incorporate all corresponding
      records from the other tables as a comma separated
      list in this order:

        customRegion, stateProv, country

      Any values not without a corresponding record are
      omitted.
    
      The 1st entry in the list will use the full
      name. The 2nd and/or 3rd entries will use an
      abbreviation if possible and otherwise the
      full name.

      Examples:
        Superior Compact, MN, USA
        Minnesota, USA
    */
    private function normalizeOriginNames()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        
        $origins = new OriginsModel($dbc);

        foreach ($origins->find() as $origin) {
            $name = '';
            $shortName = '';
            list($origin, $custom, $state, $country) = $this->getChildren($origin);

            if ($origin->customID()) {
                $name = $custom->name();
                $shortName = $custom->name();
            }

            if ($origin->stateProvID()) {
                list($name, $shortName) = $this->objToNames($state, $name, $shortName);
            }

            if ($origin->countryID()) {
                list($name, $shortName) = $this->objToNames($country, $name, $shortName);
            }

            $origin->name($name);
            $origin->shortName($shortName);
            $origin->save();
        }
    }

    private function getChildren($origin)
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $ret = array(
            'customID' => new OriginCustomRegionModel($dbc),
            'stateProvID' => new OriginStateProvModel($dbc),
            'countryID' => new OriginCountryModel($dbc),
        );

        foreach ($ret as $id => $obj)
        {
            $obj->$id($origin->$id());
            if (!$obj->load()) {
                $origin->$id(null);
            }
        }

        return array(
            $origin,
            $ret['customID'],
            $ret['stateProvID'],
            $ret['countryID'],
        );
    }

    private function objToNames($obj, $name, $shortName)
    {
        if (empty($name)) {
            $name = $obj->name();
        } else if ($obj->abbr() != '') {
            $name .= ', ' . $obj->abbr();
        } else {
            $name .= ', ' . $obj->name();
        }
        if (empty($shortName)) {
            $shortName = $obj->name();
        }

        return array($name, $shortName);
    }

    private function hasEntry($arr, $i)
    {
        if (isset($arr[$i]) && !empty($arr[$i])) {
            return true;
        } else {
            return false;
        }
    }

    private function saveOrDelete($model, $id, $index)
    {
        $delete = FormLib::get('delete', array());
        if (in_array($id, $delete)) {
            $model->delete();
        } else {
            $model->name($this->name[$index]);
            $model->abbr($this->abbr[$index]);
            $model->save();
        }
    }

    private function spacer()
    {
        return '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    }

    private function homeButton()
    {
        return '<button type="button" value="Home" class="btn btn-default"
                    onclick="location=\'OriginEditor.php\';return false;">Home</button>';
    }

    private function newButton($type)
    {
        return '<button type="button" value="Create New Entry" class="btn btn-default"
                    onclick="location=\'OriginEditor.php?' . $type . '\';return false;">Create New Entry</button>';
    }

    public function helpContent()
    {
        return '<p>
            Origins are where products come from. In many situations
            this may be overkill from a labor/maintenance standpoint,
            but origins can be defined in three tiers: countries,
            states/provinces, and custom regions. Custom regions may
            be smaller or larger than countries and/or states depending
            what is being tracked and measured.
            </p>';
    }

    public function unitTest($phpunit)
    {
        $phpunit->assertNotEquals(0, strlen($this->get_country_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_state_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_custom_view()));
        $phpunit->assertNotEquals(0, strlen($this->get_view()));
        $this->get_new_country_handler();
        $this->get_new_state_handler();
        $this->get_new_custom_handler();
        $this->countryID = array(1);
        $this->name = array('TEST');
        $this->abbr = array('TEST');
        $this->post_countryID_name_abbr_handler();
        $this->stateID = array(1);
        $this->post_stateID_name_abbr_handler();
        $this->customID = array(1);
        $this->post_customID_name_handler();
    }
}

FannieDispatch::conditionalExec();

