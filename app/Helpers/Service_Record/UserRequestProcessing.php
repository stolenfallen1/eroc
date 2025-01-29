<?php

namespace App\Helpers\Service_Record;

class UserRequestProcessing {
    public function extractRequestDate($userRequest) {
        $year           = $userRequest['year'];
        $monthName      = $userRequest['month'];
        $empNum         = $userRequest['empnum'];
        $monthNo        = 0;
        $numDays        = 0;
        switch (strtoupper($monthName)) {
            case 'JANUARY':
                $monthNo = 1;
                $numDays = 31;
                break;
            case 'FEBRUARY':
                $monthNo = 2;
                $numDays = $this->isLeapYear($year) ? 29 : 28;
                break;
            case 'MARCH':
                $monthNo = 3;
                $numDays = 31;
                break;
            case 'APRIL':
                $monthNo = 4;
                $numDays = 30;
                break;
            case 'MAY':
                $monthNo = 5;
                $numDays = 31;
                break;
            case 'JUNE':
                $monthNo = 6;
                $numDays = 30;
                break;
            case 'JULY':
                $monthNo = 7;
                $numDays = 31;
                break;
            case 'AUGUST':
                $monthNo = 8;
                $numDays = 31;
                break;
            case 'SEPTEMBER':
                $monthNo = 9;
                $numDays = 30;
                break;
            case 'OCTOBER':
                $monthNo = 10;
                $numDays = 31;
                break;
            case 'NOVEMBER':
                $monthNo = 11;
                $numDays = 30;
                break;
            case 'DECEMBER':
                $monthNo = 12;
                $numDays = 31;
                break;
            default:
                $monthNo = null;
                $numDays = -1;
                break;
        }
        return [
            'year'      => $year,
            'month'     => $monthName,
            'monthNo'   => $monthNo,
            'numDays'   => $numDays,
            'empNum'    => $empNum
        ];
    }

    public function isLeapYear($year){
        return ($year % 4 == 0 && $year % 100 != 0) || ($year % 400 == 0);
    }
}