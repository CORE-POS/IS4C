<?php
/*******************************************************************************

    Copyright 2014 Whole Foods Co-op, Duluth, MN

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

        header('Location: OriginEditor.php');

        return false;
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

        header('Location: OriginEditor.php');

        return false;
    }

    public function get_new_country_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginCountryModel($dbc);
        $model->name('0 New Country Entry');
        $model->save();

        header('Location: OriginEditor.php?country=1');

        return false;
    }

    public function get_new_state_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginStateProvModel($dbc);
        $model->name('0 New State/Prov Entry');
        $model->save();

        header('Location: OriginEditor.php?state=1');

        return false;
    }

    public function get_new_custom_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginCustomRegionModel($dbc);
        $model->name('0 New Custom Region Entry');
        $model->save();

        header('Location: OriginEditor.php?custom=1');

        return false;
    }

    public function post_countryID_name_abbr_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginCountryModel($dbc);

        $delete = FormLib::get('delete', array());

        for($i=0; $i<count($this->countryID); $i++) {
            if (!isset($this->name[$i]) || !isset($this->abbr[$i])) {
                continue;
            } else if (empty($this->name[$i]) && empty($this->abbr[$i])) {
                continue;
            }

            $model->countryID($this->countryID[$i]);

            if (in_array($this->countryID[$i], $delete)) {
                $model->delete();
            } else {
                $model->name($this->name[$i]);
                $model->abbr($this->abbr[$i]);
                $model->save();
            }
        }

        header('Location: OriginEditor.php?country=1');

        return false;
    }

    public function post_stateID_name_abbr_handler()
    {
        global $FANNIE_OP_DB;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $model = new OriginStateProvModel($dbc);

        $delete = FormLib::get('delete', array());

        for($i=0; $i<count($this->stateID); $i++) {
            if (!isset($this->name[$i]) || !isset($this->abbr[$i])) {
                continue;
            } else if (empty($this->name[$i]) && empty($this->abbr[$i])) {
                continue;
            }

            $model->stateProvID($this->stateID[$i]);

            if (in_array($this->stateID[$i], $delete)) {
                $model->delete();
            } else {
                $model->name($this->name[$i]);
                $model->abbr($this->abbr[$i]);
                $model->save();
            }
        }

        header('Location: OriginEditor.php?state=1');

        return false;
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

        header('Location: OriginEditor.php?custom=1');

        return false;
    }

    public function get_country_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $countries = new OriginCountryModel($dbc);

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<h3>Edit Countries</h3>';
        $ret .= '<input type="submit" value="Save Countries" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="button" value="Create New Entry" onclick="location=\'OriginEditor.php?new_country\';return false;" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="button" value="Home" onclick="location=\'OriginEditor.php\';return false;" />';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>Name</th><th>Abbreviation</th>
                <th><img alt="delete" src="' . $FANNIE_URL . 'src/img/buttons/trash.png' . '" /></th>
                </tr>';
        foreach ($countries->find('name') as $c) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="countryID[]" value="%d" />
                            <td><input type="text" name="name[]" value="%s" /></td>
                            <td><input type="text" name="abbr[]" value="%s" /></td>
                            <td><input type="checkbox" name="delete[]" value="%d" /></td>
                            </tr>',
                            $c->countryID(),
                            $c->name(),
                            $c->abbr(),
                            $c->countryID()
            );
        }
        $ret .= '</table>';
        $ret .= '<input type="submit" value="Save Countries" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="button" value="Home" onclick="location=\'OriginEditor.php\';return false;" />';
        $ret .= '</form>';

        return $ret;
    }

    public function get_state_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $states = new OriginStateProvModel($dbc);

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<h3>Edit States &amp; Provinces</h3>';
        $ret .= '<input type="submit" value="Save" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="button" value="Create New Entry" onclick="location=\'OriginEditor.php?new_state\';return false;" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="button" value="Home" onclick="location=\'OriginEditor.php\';return false;" />';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>Name</th><th>Abbreviation</th>
                <th><img alt="delete" src="' . $FANNIE_URL . 'src/img/buttons/trash.png' . '" /></th>
                </tr>';
        foreach ($states->find('name') as $s) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="stateID[]" value="%d" />
                            <td><input type="text" name="name[]" value="%s" /></td>
                            <td><input type="text" name="abbr[]" value="%s" /></td>
                            <td><input type="checkbox" name="delete[]" value="%d" /></td>
                            </tr>',
                            $s->stateProvID(),
                            $s->name(),
                            $s->abbr(),
                            $s->stateProvID()
            );
        }
        $ret .= '</table>';
        $ret .= '<input type="submit" value="Save" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="button" value="Home" onclick="location=\'OriginEditor.php\';return false;" />';
        $ret .= '</form>';

        return $ret;
    }

    public function get_custom_view()
    {
        global $FANNIE_OP_DB, $FANNIE_URL;
        $dbc = FannieDB::get($FANNIE_OP_DB);
        $customs = new OriginCustomRegionModel($dbc);

        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<h3>Edit Custom Regions</h3>';
        $ret .= '<input type="submit" value="Save Regions" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="button" value="Create New Entry" onclick="location=\'OriginEditor.php?new_custom\';return false;" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="button" value="Home" onclick="location=\'OriginEditor.php\';return false;" />';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr><th>Name</th>
                <th><img alt="delete" src="' . $FANNIE_URL . 'src/img/buttons/trash.png' . '" /></th>
                </tr>';
        foreach ($customs->find('name') as $c) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="customID[]" value="%d" />
                            <td><input type="text" name="name[]" value="%s" /></td>
                            <td><input type="checkbox" name="delete[]" value="%d" /></td>
                            </tr>',
                            $c->customID(),
                            $c->name(),
                            $c->customID()
            );
        }
        $ret .= '</table>';
        $ret .= '<input type="submit" value="Save Regions" />';
        $ret .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        $ret .= '<input type="button" value="Home" onclick="location=\'OriginEditor.php\';return false;" />';
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
        $ret = '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<h3>Edit Origins</h3>';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr>
                <th>Short Name</th>
                <th>Full Name</th>
                <th><a href="' . $_SERVER['PHP_SELF'] . '?custom=1">Region</a></th>
                <th><a href="' . $_SERVER['PHP_SELF'] . '?state=1">State/Prov</a></th>
                <th><a href="' . $_SERVER['PHP_SELF'] . '?country=1">Country</a></th>
                <th>Local</th>
                </tr>';
        foreach ($origins->find('name') as $o) {
            $ret .= sprintf('<tr>
                            <input type="hidden" name="originID[]" value="%d" />
                            <td>%s</td>
                            <td>%s</td>',
                            $o->originID(),
                            $o->shortName(),
                            $o->name()
            );

            $ret .= '<td><select name="custom[]"><option value="">n/a</option>';
            foreach ($customs as $id => $label) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            ($id == $o->customID() ? 'selected' : ''),
                            $id, $label);
            }
            $ret .= '</select></td>';

            $ret .= '<td><select name="state[]"><option value="">n/a</option>';
            foreach ($states as $id => $label) {
                $ret .= sprintf('<option %s value="%d">%s</option>',
                            ($id == $o->stateProvID() ? 'selected' : ''),
                            $id, $label);
            }
            $ret .= '</select></td>';

            $ret .= '<td><select name="country[]"><option value="">n/a</option>';
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
        $ret .= '<input type="submit" value="Save Origins" />';
        $ret .= '</form>';

        $ret .= '<hr />';

        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        $ret .= '<h3>Create New Origin</h3>';
        $ret .= '<table cellspacing="0" cellpadding="4" border="1">';
        $ret .= '<tr>
                <th><a href="' . $_SERVER['PHP_SELF'] . '?custom=1">Region</a></th>
                <th><a href="' . $_SERVER['PHP_SELF'] . '?state=1">State/Prov</a></th>
                <th><a href="' . $_SERVER['PHP_SELF'] . '?country=1">Country</a></th>
                </tr>';
        $ret .= '<tr>';
        $ret .= '<td><select name="newCustom"><option value="">n/a</option>';
        foreach ($customs as $id => $label) {
            $ret .= sprintf('<option value="%d">%s</option>',
                        $id, $label);
        }
        $ret .= '</select></td>';

        $ret .= '<td><select name="newState"><option value="">n/a</option>';
        foreach ($states as $id => $label) {
            $ret .= sprintf('<option value="%d">%s</option>',
                        $id, $label);
        }
        $ret .= '</select></td>';

        $ret .= '<td><select name="newCountry"><option value="">n/a</option>';
        foreach ($countries as $id => $label) {
            $ret .= sprintf('<option value="%d">%s</option>',
                        $id, $label);
        }
        $ret .= '</select></td>';
        $ret .= '</tr>';
        $ret .= '</table>';
        $ret .= '<input type="submit" value="Create" />';
        $ret .= '</form>';

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
        $custom = new OriginCustomRegionModel($dbc);
        $state = new OriginStateProvModel($dbc);
        $country = new OriginCountryModel($dbc);

        foreach ($origins->find() as $origin) {
            $name = '';
            $shortName = '';

            if ($origin->customID()) {
                $custom->customID($origin->customID());
                if ($custom->load()) {
                    $name = $custom->name();
                    $shortName = $custom->name();
                } else {
                    // remove invalid FK
                    $origin->customID(null);
                }
            }

            if ($origin->stateProvID()) {
                $state->stateProvID($origin->stateProvID());
                if ($state->load()) {
                    if (empty($name)) {
                        $name = $state->name();
                    } else if ($state->abbr() != '') {
                        $name .= ', ' . $state->abbr();
                    } else {
                        $name .= ', ' . $state->name();
                    }
                    if (empty($shortName)) {
                        $shortName = $state->name();
                    }
                } else {
                    $origin->stateProvID(null);
                }
            }

            if ($origin->countryID()) {
                $country->countryID($origin->countryID());
                if ($country->load()) {
                    if (empty($name)) {
                        $name = $country->name();
                    } else if ($country->abbr() != '') {
                        $name .= ', ' . $country->abbr();
                    } else {
                        $name .= ', ' . $country->name();
                    }
                    if (empty($shortName)) {
                        $shortName = $country->name();
                    }
                } else {
                    $origin->countryID(null);
                }
            }

            $origin->name($name);
            $origin->shortName($shortName);
            $origin->save();
        }
    }
}

FannieDispatch::conditionalExec();

