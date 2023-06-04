<?php

namespace App\Models;

class NationalHoliday
{
    public function isWorkDay($date)
    {
        $date = empty($date) ? date('Ymd') : $date;
        $week = \date('w', \strtotime($date));
        if ($week == 0 || $week == 6) {
            return false;
        }

        return true;
    }
}
