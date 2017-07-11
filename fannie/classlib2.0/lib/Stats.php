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
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IT CORE; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

namespace COREPOS\Fannie\API\lib;

class Stats
{
    public static function leastSquare($points)
    {
        $avg_x = 0.0;
        $avg_y = 0.0;
        foreach ($points as $p) {
            $avg_x += $p[0];
            $avg_y += $p[1];
        }
        $avg_x /= (float)count($points);
        $avg_y /= (float)count($points);

        $numerator = 0.0;
        $denominator = 0.0;
        foreach ($points as $p) {
            $numerator += (($p[0] - $avg_x) * ($p[1] - $avg_y));
            $denominator += (($p[0] - $avg_x) * ($p[0] - $avg_x));
        }
        $slope = $numerator / $denominator;
        $y_intercept = $avg_y - ($slope * $avg_x);

        return array(
            'slope' => $slope,
            'y_intercept' => $y_intercept,
        );
    }

    /**
     * Exponential least squares fit coefficients
     */
    public static function exponentialFit($points)
    {
        $ret = new \stdClass();

        $a_numerator = 
            (array_reduce($points, function($c,$p){ return $c + (pow($p[0],2)*$p[1]); })
            * array_reduce($points, function($c,$p){ return $c + ($p[1] * log($p[1])); })) 
            -
            (array_reduce($points, function($c,$p){ return $c + ($p[0]*$p[1]); })
            * array_reduce($points, function($c,$p){ return $c + ($p[0] * $p[1] * log($p[1])); })); 

        $a_denominator = 
            (array_reduce($points, function($c,$p) { return $c + $p[1]; })
            * array_reduce($points, function($c,$p) { return $c + (pow($p[0],2)*$p[1]); }))
            -
            pow(
                array_reduce($points, function($c,$p) { return $c + $p[0]*$p[1]; }),
                2);

        $ret->a = ($a_denominator != 0) ? $a_numerator / $a_denominator : 0;

        $b_numerator = 
            (array_reduce($points, function($c,$p){ return $c + $p[1]; })
            * array_reduce($points, function($c,$p){ return $c + ($p[0] * $p[1] * log($p[1])); })) 
            -
            (array_reduce($points, function($c,$p){ return $c + ($p[0]*$p[1]); })
            * array_reduce($points, function($c,$p){ return $c + ($p[1] * log($p[1])); })); 
        $b_denominator = $a_denominator;

        $ret->b = ($b_denominator != 0) ? $b_numerator / $b_denominator : 0;

        return $ret;
    }

    public static function removeOutliers($arr)
    {
        $min_index = 0;
        $max_index = 0;
        for ($i=0; $i<count($arr); $i++) {
            if ($arr[$i][1] < $arr[$min_index][1]) {
                $min_index = $i;
            }
            if ($arr[$i][1] > $arr[$max_index][1]) {
                $max_index = $i;
            }
        }
        $ret = array();
        for ($i=0; $i<count($arr); $i++) {
            if ($i != $min_index && $i != $max_index) {
                $ret[] = $arr[$i];
            }
        }

        return $ret;
    }

    public static function percentGrowth($a, $b)
    {
        if ($b == 0) {
            return 0.0;
        } else {
            return 100 * ($a - $b) / ((float)$b);
        }
    }

    /**
     * Calculate the next point in the sequence using exponential smoothing
     *
     * F = Y[x]*a + (a * (1-a)^1 * Y[x-1]) + (a * (1-a)^2 * Y[x-2]) + (a * (1-a)^3 * Y[x-3]) ...
     *  F = Forecast
     *  Y[x] = Observered value at time x
     *  a = alpha smoothing factor
     */
    public static function expSmoothing($points, $alpha)
    {
        $points = array_reverse($points);
        $val = $points[0] * $alpha;
        for ($i=1; $i<count($points); $i++) {
            $next = $alpha * (pow(1-$alpha, $i)) * $points[$i];
            $val += $next;
        } 

        return $val;
    }

    /**
     * Calculate the next point in the sequence using exponential smoothing
     * This algorithm gives a slightly different results (difference of ~0.02%
     * over 25 observations)
     *
     * F[0] = Y[0]
     * F[x] = F[x-1] + (a * (Y[x] - F[x-1]))
     */
    public static function expSmoothing2($points, $alpha)
    {
        $forecast = $points[0];
        foreach ($points as $point) {
            $next = $forecast + ($alpha * ($point - $forecast));
            $forecast = $next;
        }

        return $forecast;
    }
}

