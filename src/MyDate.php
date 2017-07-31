<?php

  class MyDate {
      const DELIMITER = '/';
      const DAYS_IN_MONTH = ['1'=>31,28,31,30,31,30,31,31,30,31,30,31];
      const DAYS_PASSED = ['1'=>0,31,59,89,120,150,181,212,242,273,303,334];
      const DATE_REGEXP = '/^(\d{1,4})\/(\d{1,2})\/(\d{1,2})$/';
      
      public $year;     // int year {0.....} 
      public $month; // int month {1..12}
      public $day;      // int day {1..31}
      private $_leap_year;  // bool TRUE if a leap year , else FALSE
      private $_days_in_year;   // int number of days in this year {1..366}
      private $_days_in_month;  //  int number of days in this month {1..31}
      private  $_day_of_year;   // int index number of the day of the year {1..366}

      /**
       * Return MyDate object
       * @param int or string $date_string
       * @param int $month
       * @param int $day
       */
      function __construct($date_string, $month = 0, $day = 0) {
          if (is_string($date_string)) {
              preg_match(self::DATE_REGEXP, $date_string, $date_arr);
          } else {
              $date_arr[1] = $date_string;
              $date_arr[2] = $month;
              $date_arr[3] = $day;
          }
          self::sanitize($date_arr);
          $this->year = $date_arr[1];
          $this->month = ($date_arr[2] >= 1 && $date_arr[2] <=12) ? $date_arr[2] : 1;
          $this->day = ($date_arr[3] >=1 && $date_arr[3] <= self::days_in_month($this->year, $this->month)) ? $date_arr[3] : 1;
          $this->_leap_year = self::is_leap_year($this->year);
          $this->_days_in_year = self::days_in_year($this->year);
          $this->_days_in_month = self::days_in_month($this);
          $this->_day_of_year = self::day_of_year($this);
      }
      
      function __toString() {
          return sprintf('%04d/%02d/%02d', $this->year, $this->month, $this->day);
      }

      /**
       * Sanitize values, making them positive numbers
       * @param mixed $values
       */
      private static function sanitize(&$values) {
          if (is_array($values)) {
            foreach($values as $key=>$value) {
                $good_value = (int) $value;
                if ($good_value < 0) { $good_value = 0; }
                $values[$key] = $good_value;
            }
          } else {
            $good_value = (int) $values;
            if ($good_value < 0) { $good_value = 0; }
            $values = $good_value;
          }
      }
      
      /**
       * Detect if it's a leap-year 
       * @param int $year
       * @return boolean
       */
      public static function is_leap_year($year=0) {
          self::sanitize($year);
          return ($year % 400 == 0) || (($year % 4 == 0) && ($year % 100 != 0));
      }
      
      /**
       * Return number of days in the year
       * @param int $year
       * @return int
       */
      public static function days_in_year($year) {
          if (self::is_leap_year($year)) {
              return 366;
          } else {
              return 365;
          }
      }

        /**
       * Return days in month 
       * @param mixed $the_date OR ... int $the_date and int $month
       * @return int
       */
      public static function days_in_month($the_date, $month = 0) {
          $date_obj = self::get_instance($the_date, $month);
          if ($date_obj->month != 2) { return self::DAYS_IN_MONTH[$date_obj->month]; }
          if (!self::is_leap_year($date_obj->year)) {
              return self::DAYS_IN_MONTH[$date_obj->month];
          } else {
              return self::DAYS_IN_MONTH[$date_obj->month] + 1;
          }
      }
      
      /**
       * Return day number from the beginning of the year 
       * @param MyDate or string $the_date
       * @return int
       */
      public static function day_of_year($the_date) {
          $date_obj = self::get_instance($the_date);
          $days=self::DAYS_PASSED[$date_obj->month];
          if (($date_obj->month > 2) && $date_obj->_leap_year) { $days++; }
          $days += $date_obj->day;
          return $days;
      }

      /**
       * Return -1 when $start_date < $end_date, Return 0 when equal, Return 1 when $start_date > $end_date
       * @param mixed $start_date
       * @param mixed $end_date
       * @return boolean
       */
      public static function greater($start_date, $end_date) {
          $start = self::get_instance($start_date);
          $end = self::get_instance($end_date);
          if ($start->year == $end->year && $start->month == $end->month && $start->day == $end->day) {
              return 0;
          }          
          if ( ($start->year < $end->year) 
                  || ( ($start->year == $end->year) 
                          && ( ($start->month < $end->month) 
                                    || ( ($start->month == $end->month) && ($start->day < $end->day) )
                                ) 
                      )
              ) {
              return -1;
          } else {
              return 1;
          }
      }

      /**
       * Get  instance of MyDate
       * @param MyDate or string $param
       * @param int $month
       * @param int $day
       * @return MyDate
       */
      public static function get_instance($param, $month=0, $day=0) {
          if ($param instanceof MyDate) { 
              return $param;
          } else { 
              return new self($param, $month, $day); 
          }
      }

      /**
       * Return MyDate::greater, swap parameters if  $start > $end, make parameters MyDate type when $modify is TRUE
       * @param MyDate or string $start
       * @param MyDate or string $end
       * @param bool $modify
       * @return bool
       */
      private function get_period(&$start, &$end, $modify=false) {
          if ($modify) {
              $start = self::get_instance($start);
              $end = self::get_instance($end);
          }
          $greater = self::greater($start, $end);
          if ($greater == 1) {
              $start_new = $end;
              $end_new = $start;
              $start = $start_new;
              $end = $end_new;
          }
          return $greater;
      }

      /**
       * Return the number of years between the two dates
       * @param MyDate  or string $start
       * @param MyDate or string $end
       * @return int
       */
      public static function diff_years($start, $end) {
          $greater = self::get_period($start, $end, true);
          if ($greater == 0) { return 0; }
          
          $years = $end->year - $start->year;
          if ( ($start->month > $end->month) 
                  || ( ($start->month == $end->month) && ($start->day > $end->day) ) ) {
                  $years--;
          }
          return abs($years);
      }

      /**
       * Return the number of months between the two dates less the years
       * @param MyDate or string $start
       * @param MyDate or string $end
       * @return int
       */
      public static function diff_months($start, $end) {
          $greater = self::get_period($start, $end, true);
          if ($greater == 0) { return 0; }
          $months = $end->month - $start->month;
          if ($start->year < $end->year) {
              if ($months < 0) { $months = 12 + $months; }
              if ($months > 12)  { $months -= 12; }
          }
          if ($start->day > $end->day) {
              $months--;
          }
          return $months;
      }

      /**
       * Return the number of days between the two dates less the months and the years.
       * @param MyDate or string $start
       * @param MyDate or string $end
       * @return int
       */
      public static function diff_days($start, $end) {
          $greater = self::get_period($start, $end, true);
          if ($greater == 0) { return 0; }
          $days = $end->day - $start->day;
          if ($start->month != $end->month) {
              if ($days < 0) {
                  $days = $start->_days_in_month - $start->day + $end->day;
              }
          }
          return $days;
      }
      
      /**
       * Return the total days between the two dates including the months and years.
       * @param MyDate or string $start
       * @param MyDate or string $end
       * @return int
       */
      public static function diff_days_total($start, $end) {
          $greater = self::get_period($start, $end, true);
          if ($greater == 0) { return 0; }
          if ($start->year == $end->year) {
              $total_days = $end->_day_of_year - $start->_day_of_year;
          } else {
              $total_days = self::days_in_year($start->year) - $start->_day_of_year + $end->_day_of_year;
              $year = $start->year +1;
              while ($year < $end->year) {
                  $total_days += self::days_in_year($year++);
              }
          }
          return $total_days;
      }

      /**
       * Return the difference between two given dates
       * @param string $start
       * @param string $end
       * @return stdClass {
       * int   $years,        // The number of years between the two dates.
       * int   $months,       // The number of months between the two dates less the years.
       * int   $days,         // The number of days between the two dates less the months and the years.
       * int   $total_days,   // The total days between the two dates including the months and years.
       * bool  $invert        // true if the the difference is negative (i.e. $start > $end).
       * }
       */
      public static function diff($start, $end) {
          $greater = self::get_period($start, $end, true);
          if ($greater==0) { 
              return (object)array('years' => 0, 'months' => 0, 'days' => 0, 'total_days' => 0, 'invert' => false);
          }
          $invert = ($greater == 1) ? true : false;
          
          return (object)array(
              'years' => self::diff_years($start, $end),
              'months' => self::diff_months($start, $end),
              'days' => self::diff_days($start, $end),
              'total_days' => self::diff_days_total($start, $end),
              'invert' => $invert
          );
      }
      
  }
 