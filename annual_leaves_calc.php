<?php

/*
    樂屋特休計算機 使用方式
    
    建立（constructor）：
    $alac = new annual_leaves_calc( $arrive_date, $suspend_days );
      - $arrive_date: 到職日期，字串。 eg. "2012/07/07"、"2012-07-07" 等等。 
      - $suspend_days: 正整數，留停、育嬰假天數，要從年資中扣除的天數。 （可選，預設為 0）
      
    回傳值：
      - 成功： 回傳物件本身。
      - 失敗： 若是日期錯誤（例如錯誤的格式、未來員工等），則拋出 DateErrorException 。
      
    --------------------------------------------------------------------
    
    方法（methods）：
    
    $alac->get_leaves( $time );   // 取得此人在 $time 時間點的特休假、福利假數量，以及年資級距。
     - $time: 時間點，可以為字串形式的日期（eg. "2016-08-09" 或 "2018/11/12" 等）或 Unix Time 。 （可選，預設為當前時間）
    
    回傳值：
     - 成功： 回傳陣列，包含：
       - return_value['annual']: 特休天數。 （整數）
       - return_value['welfare']: 福利假天數。 (整數)       
       - return_value['step']: 所適用之年資級距，按照 $leaves_map 定義上限為 24 。 （浮點數）
     - 失敗： 若是給定日期錯誤（例如錯誤的格式、未來員工等），則拋出 DateErrorException 。
    
    
    $alac->get_seniority( $until, $suspend_days );  // 取得此人到 $until 時間點的年資（年）
     - $until: 時間點，可以為字串形式的日期（eg. "2016-08-09" 或 "2018/11/12" 等）或 Unix Time 。 （可選，預設為當前時間）
     - $suspend_days: 留停天數，正整數。 （可選，預設採用建構時給的設定。）
     
    回傳值：
     - 成功： 回傳年資（不是年資級距），浮點數。
     - 失敗： 若是給定日期錯誤（例如錯誤的格式、未來員工等），則拋出 DateErrorException 。
     
     
    $alac->leap_years( $start, $end );  // 取得 $start 到 $end 之間有幾個閏年。
     - $start: 整數，起始年份。 例如： 2017
     - $end: 整數，結束年份。 例如： 1991
     
     // 請注意 $start 跟 $end 都必須大於 1970 ，因 unix time 起始於 1970 年。
     回傳值：
     - 從 $start 到 $end 之間經過的閏年數量（包含 $start 跟 $end），整數。
     
     
    $alac->is_leap_year( $year );  // 判斷 $year 是不是閏年。
     - $year: 整數，年份。 例如 2012 。
     
     回傳值： 閏年回傳 true ，否則回傳 false 。
*/

class DateErrorException extends Exception {}

class annual_leaves_calc {

    // 年資與特休＆福利假對照表
    private $leaves_map = array (
        0  => array( "annual" =>  0, "welfare" => 10 ),
        0.5=> array( "annual" =>  3, "welfare" =>  7 ),
        1  => array( "annual" =>  7, "welfare" =>  4 ),
        2  => array( "annual" => 10, "welfare" =>  2 ),
        3  => array( "annual" => 14, "welfare" =>  0 ),
        4  => array( "annual" => 14, "welfare" =>  0 ),
        5  => array( "annual" => 15, "welfare" =>  0 ),
        6  => array( "annual" => 15, "welfare" =>  1 ),
        7  => array( "annual" => 15, "welfare" =>  2 ),
        8  => array( "annual" => 15, "welfare" =>  3 ),
        9  => array( "annual" => 15, "welfare" =>  4 ),
        10 => array( "annual" => 16, "welfare" =>  4 ),
        11 => array( "annual" => 17, "welfare" =>  4 ),
        12 => array( "annual" => 18, "welfare" =>  4 ),
        13 => array( "annual" => 19, "welfare" =>  4 ),
        14 => array( "annual" => 20, "welfare" =>  4 ),
        15 => array( "annual" => 21, "welfare" =>  4 ),
        16 => array( "annual" => 22, "welfare" =>  4 ),
        17 => array( "annual" => 23, "welfare" =>  4 ),
        18 => array( "annual" => 24, "welfare" =>  4 ),
        19 => array( "annual" => 25, "welfare" =>  4 ),
        20 => array( "annual" => 26, "welfare" =>  4 ),
        21 => array( "annual" => 27, "welfare" =>  3 ),
        22 => array( "annual" => 28, "welfare" =>  2 ),
        23 => array( "annual" => 29, "welfare" =>  1 ),
        24 => array( "annual" => 30, "welfare" =>  0 )
    );
    
    // 到職日， Unix Time
    private $arrive_date;
    
    // 留職停薪天數
    private $suspend_days;

    // 建構式
    function __construct( $arrive_date_string, $suspend_days = 0 ) {
//         date_default_timezone_set("UTC");
        
        $this->arrive_date = strtotime( "$arrive_date_string UTC" );
        $this->suspend_days = intval( $suspend_days>0 ? $suspend_days : 0 );
        
        // 排除報到日期在未來以及日期錯誤的狀況
        if( $this->arrive_date+$suspend_days*86400 >= time() or date_create( $arrive_date_string ) === false )
            throw new DateErrorException();

    }
    
    // 計算指定時間點的特休和福利假數量
    public function get_leaves( $time = NULL ) {
        // 沒指定時間點就當成現在。
        if( $time === NULL )
            $time = time();
        else if( !is_int( $time ) ) 
            $time = strtotime( "$time UTC" );
        
        if($time === false) throw new DateErrorException();
        
        // 取得年資
        $seniority = $this->get_seniority($time);
        
        // 根據年資取得假別
        $last_value = $leaves_map[0];
        
        $max_key = max(array_keys($this->leaves_map));
        
        foreach( $this->leaves_map as $year_step => $value ) {
            if(!isset($last_step)) $last_step = $year_step;
            
            if ($year_step === $max_key) {
                $ret = $value;
                $ret['step'] = $year_step;
                return $ret;
            } else if( $seniority < $year_step ) {
                $ret = $last_value;
                $ret['step'] = $last_step;
                return $ret;
            }
            else {
                $last_value = $value;
                $last_step = $year_step;
            }
        }
    }
    
    // 計算年資
    public function get_seniority( $until = NULL, $suspend_days = NULL ) {
        if( $suspend_days === NULL ) $suspend_days = $this->suspend_days;
        $suspend_days = $suspend_days>0 ? $suspend_days : 0 ;
        
        $suspend_days = intval($suspend_days);
    
        // 沒指定 $until 即當成截至目前。
        if( $until === NULL ) 
            $until = time();
        else if( !is_int( $until ) )
            $until = strtotime( "$until UTC" );
            
        if($until === false) throw new DateErrorException();
            
        $arrive_date = $this->arrive_date;
        
        $interval = $until - $arrive_date;
        
        // 計算中間經過幾個閏年，超過三月的話，連當年度也列入計算。
        if( intval( gmdate("m",$until) ) >= 3 ) 
            $end = intval( gmdate("Y",$until) );
        else
            $end = intval( gmdate("Y",$until) ) - 1;
            
        // 取得起算年度，若早於三月，當年度不列入計算。
        if( intval( gmdate("m",$arrive_date) ) >= 3 )
            $start = intval( gmdate("Y",$arrive_date) ) + 1;
        else
            $start = intval( gmdate("Y",$arrive_date) );
        
        // 如果不到一年，又前後都不列入計算，就不管閏年，否則計算閏年數量。
        if ( $start > $end ) $leap_years_count = 0;
        else $leap_years_count = $this->leap_years( $start, $end );
        
        // 扣除閏年和留停
        $interval = $this->days_calc( $interval, -$leap_years_count-$suspend_days );
        
        // 計算並回傳年資（小數）
        return $interval / ( 365.0 * 86400.0 );
    }
    
    // 計算年份區間的閏年數
    public function leap_years( $start, $end ) {
        $start = intval( $start );
        $end = intval( $end );
        
        // 排除小於 1970 的年份，也就是 Unix Time 的最小值。
        if( $start < 1970 or $end < 1970 ) return false;
        
        // 消除先後順序問題，若引數相等，直接判別該年份。
        if( $start > $end ) {
            $min = $end;
            $max = $start;
        } else if ( $end > $start ) {
            $min = $start;
            $max = $end;
        } else 
            return ( $this->is_leap_year( $start ) ? 1 : 0 );
        
        // 起始數量
        $count = 0;
        
        // 計算給定區間的閏年數
        for( $i = $min ; $i <= $max ; $i++ ) {
            if( $this->is_leap_year($i) ) {
                $count += 1;
                $i += 3;  // 最接近的閏年是四年後。
                continue;
            }
        }
        
        return $count;
    }
    
    // 判別是否為閏年
    public function is_leap_year( $year ) {
        $year = intval( $year );
    
        /*
            1. 西元年分除以400可整除，為閏年。
            2. 西元年分除以4可整除但除以100不可整除，為閏年。
            3. 否則為平年。
        */
        
        if( ( $year % 400 ) === 0 ) return true;
        if( ( $year % 4 ) === 0 && ( $year % 100 ) !== 0 ) return true;
        
        return false;
    }
    
    // 日期計算。
    protected function days_calc( $interval, $days ) {
        return $interval + ( $days * 86400 );
    }


}
    
    
?>
