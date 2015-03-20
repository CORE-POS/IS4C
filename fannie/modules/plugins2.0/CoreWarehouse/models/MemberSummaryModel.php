<?php
/*******************************************************************************

    Copyright 2015 Whole Foods Co-op

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
  @class MemberSummaryModel
*/
class MemberSummaryModel extends CoreWarehouseModel
{

    protected $name = "MemberSummary";

    protected $columns = array(
    'card_no' => array('type'=>'INT', 'primary_key'=>true),
    'firstVisit' => array('type'=>'DATETIME'),
    'lastVisit' => array('type'=>'DATETIME'),
    'totalSpending' => array('type'=>'MONEY'),
    'totalSpendingRank' => array('type'=>'INT'),
    'averageSpending' => array('type'=>'MONEY'),
    'averageSpendingRank' => array('type'=>'INT'),
    'totalItems' => array('type'=>'DOUBLE'),
    'averageItems' => array('type'=>'DOUBLE'),
    'totalVisits' => array('type'=>'INT'),
    'totalVisitsRank' => array('type'=>'INT'),
    'spotlightStart' => array('type'=>'DATETIME'),
    'spotlightEnd' => array('type'=>'DATETIME'),
    'spotlightTotalSpending' => array('type'=>'MONEY'),
    'spotlightAverageSpending' => array('type'=>'MONEY'),
    'spotlightTotalItems' => array('type'=>'DOUBLE'),
    'spotlightAverageItems' => array('type'=>'DOUBLE'),
    'spotlightTotalVisits' => array('type'=>'INT'),
    'yearStart' => array('type'=>'DATETIME'),
    'yearEnd' => array('type'=>'DATETIME'),
    'yearTotalSpending' => array('type'=>'MONEY'),
    'yearTotalSpendingRank' => array('type'=>'INT'),
    'yearAverageSpending' => array('type'=>'MONEY'),
    'yearAverageSpendingRank' => array('type'=>'INT'),
    'yearTotalItems' => array('type'=>'DOUBLE'),
    'yearAverageItems' => array('type'=>'DOUBLE'),
    'yearTotalVisits' => array('type'=>'INT'),
    'yearTotalVisitsRank' => array('type'=>'INT'),
    'oldlightTotalSpending' => array('type'=>'MONEY'),
    'oldlightAverageSpending' => array('type'=>'MONEY'),
    'oldlightTotalItems' => array('type'=>'DOUBLE'),
    'oldlightAverageItems' => array('type'=>'DOUBLE'),
    'oldlightTotalVisits' => array('type'=>'INT'),
    'longlightTotalSpending' => array('type'=>'MONEY'),
    'longlightAverageSpending' => array('type'=>'MONEY'),
    'longlightTotalItems' => array('type'=>'DOUBLE'),
    'longlightAverageItems' => array('type'=>'DOUBLE'),
    'longlightTotalVisits' => array('type'=>'INT'),
    );

    public function refresh_data($trans_db, $month, $year, $day=False)
    {
        $config = FannieConfig::factory();
        $settings = $config->get('PLUGIN_SETTINGS');
        $dbc = FannieDB::get($settings['WarehouseDatabase']);

        $today = time();
        $lastmonth = mktime(0, 0, 0, date('n',$today)-1, 1, date('Y',$today));
        $spotlight_months = array();
        for ($i=0; $i<2; $i++) {
            $spotlight_months[] = mktime(0, 0, 0, date('n',$lastmonth)-$i, 1, date('Y',$lastmonth));
        }
        $lastyear = mktime(0, 0, 0, date('n',$lastmonth)-11, 1, date('Y',$lastmonth));

        $basicQ = '
            SELECT card_no,
                MIN(date_id) AS firstVisit,
                MAX(date_id) AS lastVisit,
                SUM(total) AS totalSpending,
                AVG(total) AS averageSpending,
                SUM(quantity) AS totalItems,
                AVG(quantity) AS averageItems,
                SUM(transCount) AS totalVisits
            FROM sumMemSalesByDay
            WHERE date_id BETWEEN ? AND ?
            GROUP BY card_no';
        $basicP = $dbc->prepare($basicQ);

        $all_time_args = array(
            0,
            date('Ymt', $lastmonth),
        );

        $dbc->query('TRUNCATE TABLE MemberSummary');

        $insQ = '
            INSERT INTO MemberSummary
            (card_no, firstVisit, lastVisit, totalSpending,
            averageSpending, totalItems, averageItems,
            totalVisits) '
            . $basicQ;
        $insP = $dbc->prepare($insQ);

        $basicR = $dbc->execute($insP, $all_time_args);

        $spotlight_args = array(
            date('Ym01', $spotlight_months[count($spotlight_months)-1]),
            date('Ymt', $spotlight_months[0]),
        );
        $spotlight_start = date('Y-m-01', $spotlight_months[count($spotlight_months)-1]);
        $spotlight_end = date('Y-m-t', $spotlight_months[0]);
        $basicR = $dbc->execute($basicP, $spotlight_args);
        $upP = $dbc->prepare('
            UPDATE MemberSummary
            SET spotlightStart=?,
                spotlightEnd=?,
                spotlightTotalSpending=?,
                spotlightAverageSpending=?,
                spotlightTotalItems=?,
                spotlightAverageItems=?,
                spotlightTotalVisits=?
            WHERE card_no=?');
        while ($spotlight = $dbc->fetchRow($basicR)) {
            $dbc->execute($upP, array(
                $spotlight_start,
                $spotlight_end,
                $spotlight['totalSpending'],
                $spotlight['averageSpending'],
                $spotlight['totalItems'],
                $spotlight['averageItems'],
                $spotlight['totalVisits'],
                $spotlight['card_no'],
            ));
        }

        $year_args = array(
            date('Ym01', $lastyear),
            date('Ymt', $lastmonth),
        );
        $basicR = $dbc->execute($basicP, $year_args);
        $year_start = date('Y-m-01', $lastyear);
        $year_end = date('Y-m-t', $lastmonth);
        $upP = $dbc->prepare('
            UPDATE MemberSummary
            SET yearStart=?,
                yearEnd=?,
                yearTotalSpending=?,
                yearAverageSpending=?,
                yearTotalItems=?,
                yearAverageItems=?,
                yearTotalVisits=?
            WHERE card_no=?');
        while ($year = $dbc->fetchRow($basicR)) {
            $dbc->execute($upP, array(
                $year_start,
                $year_end,
                $year['totalSpending'],
                $year['averageSpending'],
                $year['totalItems'],
                $year['averageItems'],
                $year['totalVisits'],
                $year['card_no'],
            ));
        }


        $oldlight = array(strtotime($spotlight_args[0]), strtotime($spotlight_args[1]));
        $oldlight_args = array(
            date('Ym01', mktime(0,0,0,date('n',$oldlight[0]),1,date('Y',$oldlight[0])-1)),
            date('Ymt', mktime(0,0,0,date('n',$oldlight[1]),1,date('Y',$oldlight[1])-1)),
        );
        $upP = $dbc->prepare('
            UPDATE MemberSummary
            SET oldlightTotalSpending=?,
                oldlightAverageSpending=?,
                oldlightTotalItems=?,
                oldlightAverageItems=?,
                oldlightTotalVisits=?
            WHERE card_no=?');
        $basicR = $dbc->execute($basicP, $oldlight_args);
        while ($old = $dbc->fetchRow($basicR)) {
            $dbc->execute($upP, array(
                $old['totalSpending'],
                $old['averageSpending'],
                $old['totalItems'],
                $old['averageItems'],
                $old['totalVisits'],
                $old['card_no'],
            ));
        }

        $longSQL = '';
        $long_args = array($spotlight_args[0]);
        foreach ($spotlight_months as $m) {
            $longSQL .= '?,';
            $long_args[] = date('m', $m);
        }
        $longSQL = substr($longSQL, 0, strlen($longSQL)-1);

        $basicQ = '
            SELECT card_no,
                MIN(date_id) AS firstVisit,
                MAX(date_id) AS lastVisit,
                SUM(total) AS totalSpending,
                AVG(total) AS averageSpending,
                SUM(quantity) AS totalItems,
                AVG(quantity) AS averageItems,
                SUM(transCount) AS totalVisits
            FROM sumMemSalesByDay
            WHERE date_id < ?
                AND SUBSTRING(CONVERT(date_id,CHAR),5,2) IN (' . $longSQL . ')
            GROUP BY card_no';
        $basicP = $dbc->prepare($basicQ);

        $upP = $dbc->prepare('
            UPDATE MemberSummary
            SET longlightTotalSpending=?,
                longlightAverageSpending=?,
                longlightTotalItems=?,
                longlightAverageItems=?,
                longlightTotalVisits=?
            WHERE card_no=?');
        $basicR = $dbc->execute($basicP, $oldlight_args);
        $basicR = $dbc->execute($basicP, $long_args);
        while ($long = $dbc->fetchRow($basicR)) {
            $dbc->execute($upP, array(
                $long['totalSpending'],
                $long['averageSpending'],
                $long['totalItems'],
                $long['averageItems'],
                $long['totalVisits'],
                $long['card_no'],
            ));
        }

        // do ranks

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY totalSpending DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET totalSpendingRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY averageSpending DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET averageSpendingRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY totalVisits DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET totalVisitsRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY yearTotalSpending DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET yearTotalSpendingRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY yearAverageSpending DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET yearAverageSpendingRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }

        $rank = 1;
        $query = '
            SELECT card_no
            FROM MemberSummary
            ORDER BY yearTotalVisits DESC, card_no';
        $rankP = $dbc->prepare('
            UPDATE MemberSummary
            SET yearTotalVisitsRank=?
            WHERE card_no=?');
        $result = $dbc->query($query);
        while ($row = $dbc->fetchRow($result)) {
            $dbc->execute($rankP, array($rank, $row['card_no']));
            $rank++;
        }
    }


    /* START ACCESSOR FUNCTIONS */

    public function card_no()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["card_no"])) {
                return $this->instance["card_no"];
            } else if (isset($this->columns["card_no"]["default"])) {
                return $this->columns["card_no"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'card_no',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["card_no"]) || $this->instance["card_no"] != func_get_args(0)) {
                if (!isset($this->columns["card_no"]["ignore_updates"]) || $this->columns["card_no"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["card_no"] = func_get_arg(0);
        }
        return $this;
    }

    public function firstVisit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["firstVisit"])) {
                return $this->instance["firstVisit"];
            } else if (isset($this->columns["firstVisit"]["default"])) {
                return $this->columns["firstVisit"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'firstVisit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["firstVisit"]) || $this->instance["firstVisit"] != func_get_args(0)) {
                if (!isset($this->columns["firstVisit"]["ignore_updates"]) || $this->columns["firstVisit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["firstVisit"] = func_get_arg(0);
        }
        return $this;
    }

    public function lastVisit()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["lastVisit"])) {
                return $this->instance["lastVisit"];
            } else if (isset($this->columns["lastVisit"]["default"])) {
                return $this->columns["lastVisit"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'lastVisit',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["lastVisit"]) || $this->instance["lastVisit"] != func_get_args(0)) {
                if (!isset($this->columns["lastVisit"]["ignore_updates"]) || $this->columns["lastVisit"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["lastVisit"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalSpending"])) {
                return $this->instance["totalSpending"];
            } else if (isset($this->columns["totalSpending"]["default"])) {
                return $this->columns["totalSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'totalSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalSpending"]) || $this->instance["totalSpending"] != func_get_args(0)) {
                if (!isset($this->columns["totalSpending"]["ignore_updates"]) || $this->columns["totalSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalSpendingRank()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalSpendingRank"])) {
                return $this->instance["totalSpendingRank"];
            } else if (isset($this->columns["totalSpendingRank"]["default"])) {
                return $this->columns["totalSpendingRank"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'totalSpendingRank',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalSpendingRank"]) || $this->instance["totalSpendingRank"] != func_get_args(0)) {
                if (!isset($this->columns["totalSpendingRank"]["ignore_updates"]) || $this->columns["totalSpendingRank"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalSpendingRank"] = func_get_arg(0);
        }
        return $this;
    }

    public function averageSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["averageSpending"])) {
                return $this->instance["averageSpending"];
            } else if (isset($this->columns["averageSpending"]["default"])) {
                return $this->columns["averageSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'averageSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["averageSpending"]) || $this->instance["averageSpending"] != func_get_args(0)) {
                if (!isset($this->columns["averageSpending"]["ignore_updates"]) || $this->columns["averageSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["averageSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function averageSpendingRank()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["averageSpendingRank"])) {
                return $this->instance["averageSpendingRank"];
            } else if (isset($this->columns["averageSpendingRank"]["default"])) {
                return $this->columns["averageSpendingRank"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'averageSpendingRank',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["averageSpendingRank"]) || $this->instance["averageSpendingRank"] != func_get_args(0)) {
                if (!isset($this->columns["averageSpendingRank"]["ignore_updates"]) || $this->columns["averageSpendingRank"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["averageSpendingRank"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalItems"])) {
                return $this->instance["totalItems"];
            } else if (isset($this->columns["totalItems"]["default"])) {
                return $this->columns["totalItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'totalItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalItems"]) || $this->instance["totalItems"] != func_get_args(0)) {
                if (!isset($this->columns["totalItems"]["ignore_updates"]) || $this->columns["totalItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function averageItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["averageItems"])) {
                return $this->instance["averageItems"];
            } else if (isset($this->columns["averageItems"]["default"])) {
                return $this->columns["averageItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'averageItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["averageItems"]) || $this->instance["averageItems"] != func_get_args(0)) {
                if (!isset($this->columns["averageItems"]["ignore_updates"]) || $this->columns["averageItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["averageItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalVisits()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalVisits"])) {
                return $this->instance["totalVisits"];
            } else if (isset($this->columns["totalVisits"]["default"])) {
                return $this->columns["totalVisits"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'totalVisits',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalVisits"]) || $this->instance["totalVisits"] != func_get_args(0)) {
                if (!isset($this->columns["totalVisits"]["ignore_updates"]) || $this->columns["totalVisits"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalVisits"] = func_get_arg(0);
        }
        return $this;
    }

    public function totalVisitsRank()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["totalVisitsRank"])) {
                return $this->instance["totalVisitsRank"];
            } else if (isset($this->columns["totalVisitsRank"]["default"])) {
                return $this->columns["totalVisitsRank"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'totalVisitsRank',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["totalVisitsRank"]) || $this->instance["totalVisitsRank"] != func_get_args(0)) {
                if (!isset($this->columns["totalVisitsRank"]["ignore_updates"]) || $this->columns["totalVisitsRank"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["totalVisitsRank"] = func_get_arg(0);
        }
        return $this;
    }

    public function spotlightStart()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["spotlightStart"])) {
                return $this->instance["spotlightStart"];
            } else if (isset($this->columns["spotlightStart"]["default"])) {
                return $this->columns["spotlightStart"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'spotlightStart',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["spotlightStart"]) || $this->instance["spotlightStart"] != func_get_args(0)) {
                if (!isset($this->columns["spotlightStart"]["ignore_updates"]) || $this->columns["spotlightStart"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["spotlightStart"] = func_get_arg(0);
        }
        return $this;
    }

    public function spotlightEnd()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["spotlightEnd"])) {
                return $this->instance["spotlightEnd"];
            } else if (isset($this->columns["spotlightEnd"]["default"])) {
                return $this->columns["spotlightEnd"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'spotlightEnd',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["spotlightEnd"]) || $this->instance["spotlightEnd"] != func_get_args(0)) {
                if (!isset($this->columns["spotlightEnd"]["ignore_updates"]) || $this->columns["spotlightEnd"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["spotlightEnd"] = func_get_arg(0);
        }
        return $this;
    }

    public function spotlightTotalSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["spotlightTotalSpending"])) {
                return $this->instance["spotlightTotalSpending"];
            } else if (isset($this->columns["spotlightTotalSpending"]["default"])) {
                return $this->columns["spotlightTotalSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'spotlightTotalSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["spotlightTotalSpending"]) || $this->instance["spotlightTotalSpending"] != func_get_args(0)) {
                if (!isset($this->columns["spotlightTotalSpending"]["ignore_updates"]) || $this->columns["spotlightTotalSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["spotlightTotalSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function spotlightAverageSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["spotlightAverageSpending"])) {
                return $this->instance["spotlightAverageSpending"];
            } else if (isset($this->columns["spotlightAverageSpending"]["default"])) {
                return $this->columns["spotlightAverageSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'spotlightAverageSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["spotlightAverageSpending"]) || $this->instance["spotlightAverageSpending"] != func_get_args(0)) {
                if (!isset($this->columns["spotlightAverageSpending"]["ignore_updates"]) || $this->columns["spotlightAverageSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["spotlightAverageSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function spotlightTotalItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["spotlightTotalItems"])) {
                return $this->instance["spotlightTotalItems"];
            } else if (isset($this->columns["spotlightTotalItems"]["default"])) {
                return $this->columns["spotlightTotalItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'spotlightTotalItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["spotlightTotalItems"]) || $this->instance["spotlightTotalItems"] != func_get_args(0)) {
                if (!isset($this->columns["spotlightTotalItems"]["ignore_updates"]) || $this->columns["spotlightTotalItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["spotlightTotalItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function spotlightAverageItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["spotlightAverageItems"])) {
                return $this->instance["spotlightAverageItems"];
            } else if (isset($this->columns["spotlightAverageItems"]["default"])) {
                return $this->columns["spotlightAverageItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'spotlightAverageItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["spotlightAverageItems"]) || $this->instance["spotlightAverageItems"] != func_get_args(0)) {
                if (!isset($this->columns["spotlightAverageItems"]["ignore_updates"]) || $this->columns["spotlightAverageItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["spotlightAverageItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function spotlightTotalVisits()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["spotlightTotalVisits"])) {
                return $this->instance["spotlightTotalVisits"];
            } else if (isset($this->columns["spotlightTotalVisits"]["default"])) {
                return $this->columns["spotlightTotalVisits"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'spotlightTotalVisits',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["spotlightTotalVisits"]) || $this->instance["spotlightTotalVisits"] != func_get_args(0)) {
                if (!isset($this->columns["spotlightTotalVisits"]["ignore_updates"]) || $this->columns["spotlightTotalVisits"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["spotlightTotalVisits"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearStart()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearStart"])) {
                return $this->instance["yearStart"];
            } else if (isset($this->columns["yearStart"]["default"])) {
                return $this->columns["yearStart"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearStart',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearStart"]) || $this->instance["yearStart"] != func_get_args(0)) {
                if (!isset($this->columns["yearStart"]["ignore_updates"]) || $this->columns["yearStart"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearStart"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearEnd()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearEnd"])) {
                return $this->instance["yearEnd"];
            } else if (isset($this->columns["yearEnd"]["default"])) {
                return $this->columns["yearEnd"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearEnd',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearEnd"]) || $this->instance["yearEnd"] != func_get_args(0)) {
                if (!isset($this->columns["yearEnd"]["ignore_updates"]) || $this->columns["yearEnd"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearEnd"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearTotalSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearTotalSpending"])) {
                return $this->instance["yearTotalSpending"];
            } else if (isset($this->columns["yearTotalSpending"]["default"])) {
                return $this->columns["yearTotalSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearTotalSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearTotalSpending"]) || $this->instance["yearTotalSpending"] != func_get_args(0)) {
                if (!isset($this->columns["yearTotalSpending"]["ignore_updates"]) || $this->columns["yearTotalSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearTotalSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearTotalSpendingRank()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearTotalSpendingRank"])) {
                return $this->instance["yearTotalSpendingRank"];
            } else if (isset($this->columns["yearTotalSpendingRank"]["default"])) {
                return $this->columns["yearTotalSpendingRank"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearTotalSpendingRank',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearTotalSpendingRank"]) || $this->instance["yearTotalSpendingRank"] != func_get_args(0)) {
                if (!isset($this->columns["yearTotalSpendingRank"]["ignore_updates"]) || $this->columns["yearTotalSpendingRank"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearTotalSpendingRank"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearAverageSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearAverageSpending"])) {
                return $this->instance["yearAverageSpending"];
            } else if (isset($this->columns["yearAverageSpending"]["default"])) {
                return $this->columns["yearAverageSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearAverageSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearAverageSpending"]) || $this->instance["yearAverageSpending"] != func_get_args(0)) {
                if (!isset($this->columns["yearAverageSpending"]["ignore_updates"]) || $this->columns["yearAverageSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearAverageSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearAverageSpendingRank()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearAverageSpendingRank"])) {
                return $this->instance["yearAverageSpendingRank"];
            } else if (isset($this->columns["yearAverageSpendingRank"]["default"])) {
                return $this->columns["yearAverageSpendingRank"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearAverageSpendingRank',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearAverageSpendingRank"]) || $this->instance["yearAverageSpendingRank"] != func_get_args(0)) {
                if (!isset($this->columns["yearAverageSpendingRank"]["ignore_updates"]) || $this->columns["yearAverageSpendingRank"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearAverageSpendingRank"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearTotalItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearTotalItems"])) {
                return $this->instance["yearTotalItems"];
            } else if (isset($this->columns["yearTotalItems"]["default"])) {
                return $this->columns["yearTotalItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearTotalItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearTotalItems"]) || $this->instance["yearTotalItems"] != func_get_args(0)) {
                if (!isset($this->columns["yearTotalItems"]["ignore_updates"]) || $this->columns["yearTotalItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearTotalItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearAverageItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearAverageItems"])) {
                return $this->instance["yearAverageItems"];
            } else if (isset($this->columns["yearAverageItems"]["default"])) {
                return $this->columns["yearAverageItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearAverageItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearAverageItems"]) || $this->instance["yearAverageItems"] != func_get_args(0)) {
                if (!isset($this->columns["yearAverageItems"]["ignore_updates"]) || $this->columns["yearAverageItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearAverageItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearTotalVisits()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearTotalVisits"])) {
                return $this->instance["yearTotalVisits"];
            } else if (isset($this->columns["yearTotalVisits"]["default"])) {
                return $this->columns["yearTotalVisits"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearTotalVisits',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearTotalVisits"]) || $this->instance["yearTotalVisits"] != func_get_args(0)) {
                if (!isset($this->columns["yearTotalVisits"]["ignore_updates"]) || $this->columns["yearTotalVisits"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearTotalVisits"] = func_get_arg(0);
        }
        return $this;
    }

    public function yearTotalVisitsRank()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["yearTotalVisitsRank"])) {
                return $this->instance["yearTotalVisitsRank"];
            } else if (isset($this->columns["yearTotalVisitsRank"]["default"])) {
                return $this->columns["yearTotalVisitsRank"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'yearTotalVisitsRank',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["yearTotalVisitsRank"]) || $this->instance["yearTotalVisitsRank"] != func_get_args(0)) {
                if (!isset($this->columns["yearTotalVisitsRank"]["ignore_updates"]) || $this->columns["yearTotalVisitsRank"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["yearTotalVisitsRank"] = func_get_arg(0);
        }
        return $this;
    }

    public function oldlightTotalSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["oldlightTotalSpending"])) {
                return $this->instance["oldlightTotalSpending"];
            } else if (isset($this->columns["oldlightTotalSpending"]["default"])) {
                return $this->columns["oldlightTotalSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'oldlightTotalSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["oldlightTotalSpending"]) || $this->instance["oldlightTotalSpending"] != func_get_args(0)) {
                if (!isset($this->columns["oldlightTotalSpending"]["ignore_updates"]) || $this->columns["oldlightTotalSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["oldlightTotalSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function oldlightAverageSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["oldlightAverageSpending"])) {
                return $this->instance["oldlightAverageSpending"];
            } else if (isset($this->columns["oldlightAverageSpending"]["default"])) {
                return $this->columns["oldlightAverageSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'oldlightAverageSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["oldlightAverageSpending"]) || $this->instance["oldlightAverageSpending"] != func_get_args(0)) {
                if (!isset($this->columns["oldlightAverageSpending"]["ignore_updates"]) || $this->columns["oldlightAverageSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["oldlightAverageSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function oldlightTotalItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["oldlightTotalItems"])) {
                return $this->instance["oldlightTotalItems"];
            } else if (isset($this->columns["oldlightTotalItems"]["default"])) {
                return $this->columns["oldlightTotalItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'oldlightTotalItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["oldlightTotalItems"]) || $this->instance["oldlightTotalItems"] != func_get_args(0)) {
                if (!isset($this->columns["oldlightTotalItems"]["ignore_updates"]) || $this->columns["oldlightTotalItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["oldlightTotalItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function oldlightAverageItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["oldlightAverageItems"])) {
                return $this->instance["oldlightAverageItems"];
            } else if (isset($this->columns["oldlightAverageItems"]["default"])) {
                return $this->columns["oldlightAverageItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'oldlightAverageItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["oldlightAverageItems"]) || $this->instance["oldlightAverageItems"] != func_get_args(0)) {
                if (!isset($this->columns["oldlightAverageItems"]["ignore_updates"]) || $this->columns["oldlightAverageItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["oldlightAverageItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function oldlightTotalVisits()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["oldlightTotalVisits"])) {
                return $this->instance["oldlightTotalVisits"];
            } else if (isset($this->columns["oldlightTotalVisits"]["default"])) {
                return $this->columns["oldlightTotalVisits"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'oldlightTotalVisits',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["oldlightTotalVisits"]) || $this->instance["oldlightTotalVisits"] != func_get_args(0)) {
                if (!isset($this->columns["oldlightTotalVisits"]["ignore_updates"]) || $this->columns["oldlightTotalVisits"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["oldlightTotalVisits"] = func_get_arg(0);
        }
        return $this;
    }

    public function longlightTotalSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["longlightTotalSpending"])) {
                return $this->instance["longlightTotalSpending"];
            } else if (isset($this->columns["longlightTotalSpending"]["default"])) {
                return $this->columns["longlightTotalSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'longlightTotalSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["longlightTotalSpending"]) || $this->instance["longlightTotalSpending"] != func_get_args(0)) {
                if (!isset($this->columns["longlightTotalSpending"]["ignore_updates"]) || $this->columns["longlightTotalSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["longlightTotalSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function longlightAverageSpending()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["longlightAverageSpending"])) {
                return $this->instance["longlightAverageSpending"];
            } else if (isset($this->columns["longlightAverageSpending"]["default"])) {
                return $this->columns["longlightAverageSpending"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'longlightAverageSpending',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["longlightAverageSpending"]) || $this->instance["longlightAverageSpending"] != func_get_args(0)) {
                if (!isset($this->columns["longlightAverageSpending"]["ignore_updates"]) || $this->columns["longlightAverageSpending"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["longlightAverageSpending"] = func_get_arg(0);
        }
        return $this;
    }

    public function longlightTotalItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["longlightTotalItems"])) {
                return $this->instance["longlightTotalItems"];
            } else if (isset($this->columns["longlightTotalItems"]["default"])) {
                return $this->columns["longlightTotalItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'longlightTotalItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["longlightTotalItems"]) || $this->instance["longlightTotalItems"] != func_get_args(0)) {
                if (!isset($this->columns["longlightTotalItems"]["ignore_updates"]) || $this->columns["longlightTotalItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["longlightTotalItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function longlightAverageItems()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["longlightAverageItems"])) {
                return $this->instance["longlightAverageItems"];
            } else if (isset($this->columns["longlightAverageItems"]["default"])) {
                return $this->columns["longlightAverageItems"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'longlightAverageItems',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["longlightAverageItems"]) || $this->instance["longlightAverageItems"] != func_get_args(0)) {
                if (!isset($this->columns["longlightAverageItems"]["ignore_updates"]) || $this->columns["longlightAverageItems"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["longlightAverageItems"] = func_get_arg(0);
        }
        return $this;
    }

    public function longlightTotalVisits()
    {
        if(func_num_args() == 0) {
            if(isset($this->instance["longlightTotalVisits"])) {
                return $this->instance["longlightTotalVisits"];
            } else if (isset($this->columns["longlightTotalVisits"]["default"])) {
                return $this->columns["longlightTotalVisits"]["default"];
            } else {
                return null;
            }
        } else if (func_num_args() > 1) {
            $value = func_get_arg(0);
            $op = $this->validateOp(func_get_arg(1));
            if ($op === false) {
                throw new Exception('Invalid operator: ' . func_get_arg(1));
            }
            $filter = array(
                'left' => 'longlightTotalVisits',
                'right' => $value,
                'op' => $op,
                'rightIsLiteral' => false,
            );
            if (func_num_args() > 2 && func_get_arg(2) === true) {
                $filter['rightIsLiteral'] = true;
            }
            $this->filters[] = $filter;
        } else {
            if (!isset($this->instance["longlightTotalVisits"]) || $this->instance["longlightTotalVisits"] != func_get_args(0)) {
                if (!isset($this->columns["longlightTotalVisits"]["ignore_updates"]) || $this->columns["longlightTotalVisits"]["ignore_updates"] == false) {
                    $this->record_changed = true;
                }
            }
            $this->instance["longlightTotalVisits"] = func_get_arg(0);
        }
        return $this;
    }
    /* END ACCESSOR FUNCTIONS */
}

