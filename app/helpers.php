<?php

/**
 * Global helpers file with misc functions
 *
 */

use App\User;
use App\Models\Currency;

if (! function_exists('appName')) {
    /**
     * Helper to grab the application name
     *
     * @return mixed
     */
    function appName()
    {
        return config('app.name');
    }
}

if (! function_exists('t')) {
    /**
     * short form for the trans function
     * @param $string
     * @param array $placeHolder
     * @return \Illuminate\Contracts\Translation\Translator|string
     */
    function t($string, $placeHolder = [])
    {
        return trans($string, $placeHolder);
    }
}

if (! function_exists('cc')) {
    /**
     * CC - custom config
     * Lazy short form to get my settings config
     * @param $config_name
     * @return mixed
     */
    function cc($config_name)
    {
        return config('options.'.$config_name);
    }
}

if (! function_exists('isActiveRoute')) {
    /*
    |--------------------------------------------------------------------------
    | Detect Active Route
    |--------------------------------------------------------------------------
    |
    | Compare given route with current route and return output if they match.
    | Very useful for navigation, marking if the link is active.
    |
    */
    function isActiveRoute($route, $output = 'class=active')
    {
        if (Route::currentRouteName() == $route) {
            return $output;
        }
    }
}

if (! function_exists('formatPhoneNo')) {
    function formatPhoneNo($phoneNo)
    {
        $phoneNo = preg_replace('/\s+/', '', $phoneNo);
        
        if(starts_with($phoneNo, "234")){
            return $phoneNo;
        }
        return substr_replace($phoneNo, "234", 0, 1);
    }
}

if (! function_exists('isActiveSubPage')) {
    /*
    |--------------------------------------------------------------------------
    | Detect Active Route
    |--------------------------------------------------------------------------
    |
    | Compare given route with current route and return output if they match.
    | Very useful for navigation, marking if the link is active.
    |
    */
    function isActiveSubPage($page, $output = 'class=active')
    {
        $route = Route::current()->parameter('page');
        if ($route == $page) {
            return $output;
        }
    }
}

if (! function_exists('areActiveRoutes')) {
    /*
    |--------------------------------------------------------------------------
    | Detect Active Routes
    |--------------------------------------------------------------------------
    |
    | Compare given routes with current route and return output if they match.
    | Very useful for navigation, marking if the link is active.
    |
    | Ex. <li {{ areActiveRoutes(['client.index', 'client.create', 'client.show']) }}>
    |
    */
    function areActiveRoutes(Array $routes, $output = 'class=active')
    {
        foreach ($routes as $route) {
            if (Route::currentRouteName() == $route) {
                return $output;
            }
        }
    }
}


if (! function_exists('getTweetLink')) {

    function getTweetLink($id)
    {
        return "https://twitter.com/i/web/status/".$id;
    }
}



if (! function_exists('getHumanDate')) {

    function getHumanDate($stringDate)
    {
        $real_date = date('Y-m-d H:i:s', strtotime($stringDate));

        return \Carbon\Carbon::parse($real_date)->diffForHumans();
    }
}

if (! function_exists('simpleDate')) {

    function simpleDate($date)
    {

        return date_format($date,"M d");
    }
}

if (! function_exists('humanDate')) {

    function humanDate($date)
    {
        //$real_date = date('Y-m-d H:i:s', strtotime($stringDate));

        return $date->diffForHumans();
    }
}


if (! function_exists('generateUniqueID')) {
    function generateUniqueID($email)
    {
        if (!is_null($email)) {
            $arr = explode("@", $email);
            return $arr[0] .'-'.time();
        }
        return '-'.time();
    }
}

if (! function_exists('generateUniqueCode')) {
    function generateUniqueCode($l = 8)
    {
        return substr(md5(uniqid(mt_rand(), true)), 0, $l);
    }
}

if (! function_exists('generateProductRef')) {
    function generateProductRef($id, $l = 5)
    {
        return 'INT-PR-'.$id.'-'. substr(md5(uniqid(mt_rand(), true)), 0, $l);
    }
}

if (! function_exists('generateProductGroupRef')) {
    function generateProductGroupRef($id, $l = 5)
    {
        return 'INT-PR-GR-'.$id.'-'. substr(md5(uniqid(mt_rand(), true)), 0, $l);
    }
}



if (! function_exists('generateSlug')) {
    function generateSlug($string)
    {
        return str_slug($string, '-').'-'.time();
    }
}

if (! function_exists('getUserStatus')) {
    function getUserStatus($string)
    {
        if($string == 1)
            return 'Active';
        return 'Inactive';
    }
}

if (! function_exists('getUserName')) {
    function getUserName($id)
    {
        if (!is_null($id)) {
            $user = User::find($id);
            if (!is_null($user)) {
                return $user->name;
            }
            return "Null";
        }
        return "Null";
    }
}

if (! function_exists('getCurrencyID')) {
    function getCurrencyID($currency)
    {
        if (!is_null($currency)) {
            $cur = Currency::where('currency', $currency)->first();
            if (!is_null($cur)) {
                return $cur->id;
            }
            return 0;
        }
        return 0;
    }
}


if (! function_exists('Words_1_999')) {
    function Words_1_999($num)
         {
                $result='';
                $hundreds = Int($num,100);
                $remainder = $num - $hundreds * 100;
              
                if ($hundreds > 0)
                {
                    $result = Words_1_19($hundreds) . " hundred and ";
                }

                if ($remainder > 0)
                {
                    $result = $result . Words_1_99($remainder);
                }

                return trim($result);
        }
}

if (! function_exists('Words_1_19')) {
        //Return a word for this value between 1 and 19.
        function Words_1_19($num)
        {
                switch ($num):
                    case(1):
                        return "one";
                    case(2):
                        return "two";
                    case (3):
                        return "three";
                    case (4):
                        return "four";
                    case (5):
                        return "five";
                    case (6):
                        return "six";
                    case (7):
                        return "seven";
                    case (8):
                        return "eight";
                    case (9):
                        return "nine";
                    case (10):
                        return "ten";
                    case (11):
                        return "eleven";
                    case (12):
                        return "twelve";
                    case (13):
                        return "thirteen";
                    case (14):
                        return "fourteen";
                    case (15):
                        return "fifteen";
                    case (16):
                        return "sixteen";
                    case (17):
                        return "seventeen";
                    case (18):
                        return "eightteen";
                    case (19):
                        return "nineteen";
                endswitch;
        }
}

if (! function_exists('Words_1_99')) {
        //Return a word for this value between 1 and 99.
        function Words_1_99($num)
        {
                //result As String
                //$tens As Integer

                $tens = Int($num,10);
                
                if ($tens <= 1)
                {
                    // 1 <= $num <= 19
                    $result = $result . " " . Words_1_19($num);
                }
                else
                {
                    // 20 <= $num
                    // Get the $tens digit word.
                    switch ($tens):
                        case (2):
                            $result = "twenty";
                            break;
                        case (3):
                            $result = "thirty";
                            break;
                        case (4):
                            $result = "forty";
                            break;
                        case (5):
                            $result = "fifty";
                            break;
                        case (6):
                            $result = "sixty";
                            break;
                        case (7):
                            $result = "seventy";
                            break;
                        case (8):
                            $result = "eighty";
                            break;
                        case (9):
                            $result = "ninety";
                            break;
                   endswitch;

                    // Add the ones digit number.
                    $result = $result . " " . Words_1_19($num - $tens * 10);
                }

                return trim($result);
        }
}

if (! function_exists('Words_1_all')) {
        //Return a string of words to represent the
        //integer part of this value.
        function Words_1_all($num)
        {
                //Initialize the power names and values.
                $power_name[] = "trillion" ; $power_value[] = 1000000000000;
                $power_name[] = "billion" ; $power_value[] = 1000000000;
                $power_name[] = "million" ; $power_value[] = 1000000;
                $power_name[] = "thousand" ; $power_value[] = 1000;
                $power_name[] = "" ; $power_value[] = 1;
                $result='';

                For ($i = 0;$i<count($power_name);$i++)
                {
                    //See if we have digits in this range.
                    if ($num >= $power_value[$i])
                    {
                        //Get the digits.
                        $digits = Int($num,$power_value[$i]);

                        // Add the digits to the result.
                        if (strlen($result) > 0) $result = $result . ", ";
                        $result = $result . Words_1_999($digits) . " " . $power_name[$i];

                        //Get the number without these digits.
                        $num = $num - $digits * $power_value[$i];
                    }
                }

               return trim($result);
        }
}


if (! function_exists('MoneyInWords')) {
        //Return a string of words to represent this
        //Decimal value in Naira and kobo.
        function MoneyInWords($num)
        {
                //Naira As Decimal
                //kobo As Integer
                //Naira_result As String
                //kobo_result As String
                $Naira_result='';
                $kobo_result='';
                $num=str_replace(",","",$num);
                //Naira.
                $Naira = GetNaira($num);
                
                $Naira_result = Words_1_all($Naira);
                if (strlen($Naira_result) == 0) $Naira_result = "zero";

                if ($Naira_result == "One") 
                {
                    $Naira_result = $Naira_result . " Naira";
                }
                else
                {
                    $Naira_result = $Naira_result . " Naira";
                }

                //kobo.
                $kobo = GetKobo($num);
                $kobo_result = Words_1_all($kobo);
                if (strlen($kobo_result) == 0) $kobo_result = "zero";

                if ($kobo_result == "One")
                {
                    $kobo_result = $kobo_result . " Kobo";
                }
                else
                {
                    $kobo_result = $kobo_result . " Kobo";
                }
                
                if (getLastStr($Naira_result," and Naira")==' and Naira') 
                {
                    for ($a = 0;$a<=9;$a++)
                    {
                        //$Naira_result = Mid($Naira_result, 1, Len(Naira_result) - 1)
                        $Naira_result=substr($Naira_result,0,strlen($Naira_result)-1);
                    }
                }
                
                $Naira_result = $Naira_result . " Naira";       
                $Naira_result=str_replace("Naira Naira","Naira",$Naira_result);

                $res = ucfirst($Naira_result . ", " . $kobo_result);        
                $res=str_replace(", zero Kobo"," Only.",$res);     

                return $res;
        }
}

if (! function_exists('getLastStr')) {
        function getLastStr($str,$substr)
        {
            return substr($str,strlen($str)-strlen($substr));
        }
}



if (! function_exists('GetNaira')) {
        function GetNaira($num){
            $ret=explode(".",$num);
            $result=trim($ret[0]);
            
            return $result;
        }
}


if (! function_exists('GetKobo')) {
        function GetKobo($num)
        {
            if ($num)
            {
                $result='';
                $ret=explode(".",$num);
                if (isset($ret[1])) $result=trim($ret[1]);
                
                if (strlen($result)==1) $result=$result.'0';    
                
                return $result;
            }
        }
}

if (! function_exists('Int')) {
        function Int($num,$divisor)
        {
            $remainder=$num % $divisor;
            $result=($num-$remainder)/$divisor;
            
            return $result;
        }
}







         
        

        