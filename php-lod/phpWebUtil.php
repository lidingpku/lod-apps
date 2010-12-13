<?php

/********************************************************************************
 Section 1. general information
*********************************************************************************/

/*
author: Li Ding (http://www.cs.rpi.edu/~dingl)
created: Dec 12, 2010

MIT License

Copyright (c) 2010-2011

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

*/



/********************************************************************************
 Section 2.  Readme
*********************************************************************************/
/*
1. Installation

software stack
* php



2. Change Log
2010-12-12, version 0.1 (Li) 
* the second version

*/


/********************************************************************************
 Section 3  Source code - Configuration
*********************************************************************************/

////////////////////////////////
// configuration - version
////////////////////////////////

define("ME_NAME", "phpWebUtil");
define("ME_VERSION", "2010-12-12");
define("ME_AUTHOR", "Li Ding");
define("ME_CREATED", "2010-12-12");

// configuration - * customizable section

// configuration 1
define("ME_TITLE", ME_NAME ."");
define("ME_FILENAME", ME_NAME .".php");



/********************************************************************************
 Section 4  Source code - Main Function
*********************************************************************************/

die();

class WebUtil{
  public static function test(){
	echo "Test";
  }

  public static function is_uri($uri){
	if (empty($uri))
		return false;
    return preg_match ('/^https?:[^\s<>"\',]+$/', $uri);
  }

  public static function is_int($val){
       if (strlen($val) == 0) {
             return false;
       }
       if (!is_numeric($val) ) {
             return false;
       }

	if ((int)$val != $val){
		return false;
	}

	if (WebUtil::startsWith($val, "0") ){
		return false;
	}

	return true;
  }	

  public static function is_float($val){
       if (strlen($val) == 0) {
             return false;
       }
       if (!is_numeric($val) ) {
             return false;
       }

	if ((float)$val != $val){
		return false;
	}

	if (WebUtil::startsWith($val, "0") ){
		return false;
	}

	return true;
  }	

  public static function clean_number($val){
	if (empty($val))
		return $val;

    	if (!preg_match ('/^[0-9,\$\s]+$/', $val))
		return $val;
	
       $pString = str_replace('\s', '', $val);

	if (WebUtil::startsWith($pString, "\$") && substr_count($val, "\$") == 1 )
		$pString = substr($pString,1);

       $pString = str_replace(',', '', $val);

	return $pString;
  }


  public static function parseXsdDateTime($date='', $bkeep=false){
	
	if (empty($date)){
		//$datetime_lastmodified = date_create();
		//$datetime_lastmodified = date_format($datetime_lastmodified, "Y-m-d\TH:i:s\Z");
		return false; //date("Y-m-d\TH:i:s\Z"); 
	}else{
     //		date_default_timezone_set("GMT");
	
		$temp = date_parse($date);
		if (!empty($temp) && $temp['year']!==false && $temp['month']!==false && $temp['day']!==false){
			$ret = sprintf("%04d-%02d-%02d",$temp['year'],$temp['month'],$temp['day']);
			if ($temp['hour']!==false && $temp['minute']!==false && $temp['second']!==false){
				return $ret . sprintf("T%02d:%02d:%02dZ",$temp['hour'],$temp['minute'],$temp['second']);
			}else{
				return $ret;
			}
		}else{
			if ($bkeep)
				return $date;
			else
				return false;
		}
	}
  }

  static function parseFloat($ptString) {
            if (strlen($ptString) == 0) {
                    return false;
            }
           
            $pString = str_replace(" ", "", $ptString);
           
            if (substr_count($pString, ",") >= 1)
                $pString = str_replace(",", "", $pString);
           
            if (substr_count($pString, ".") > 1)
                $pString = str_replace(".", "", $pString);
           
            $pregResult = array();
       
            $commaset = strpos($pString,',');
            if ($commaset === false) {$commaset = -1;}
       
            $pointset = strpos($pString,'.');
            if ($pointset === false) {$pointset = -1;}
       
            $pregResultA = array();
            $pregResultB = array();
       
            if ($pointset < $commaset) {
                preg_match('#(([-]?[0-9]+(\.[0-9])?)+(,[0-9]+)?)#', $pString, $pregResultA);
            }
            preg_match('#(([-]?[0-9]+(,[0-9])?)+(\.[0-9]+)?)#', $pString, $pregResultB);
            if ((isset($pregResultA[0]) && (!isset($pregResultB[0])
                    || strstr($preResultA[0],$pregResultB[0]) == 0
                    || !$pointset))) {
                $numberString = $pregResultA[0];
                $numberString = str_replace('.','',$numberString);
                $numberString = str_replace(',','.',$numberString);
            }
            elseif (isset($pregResultB[0]) && (!isset($pregResultA[0])
                    || strstr($pregResultB[0],$preResultA[0]) == 0
                    || !$commaset)) {
                $numberString = $pregResultB[0];
                $numberString = str_replace(',','',$numberString);
            }
            else {
                return false;
            }
            $result = (float)$numberString;
            return $result;
    }   

 
  
   	static public function normalize_localname($value){
		$temp = $value;
		$temp = str_replace(' ', '_', trim(preg_replace('/\W+/',' ', $temp )));
		$temp = strtolower($temp);
		if (is_numeric(substr($temp,0,1))){
			$temp = "num_".$temp;
		}
		return $temp;
	}

	// get a the value of a key (mix)
	static function get_param($key, $default=false){
        if (is_array($key)){
                foreach ($key as $onekey){
                        $ret = get_param($onekey);
                        if ($ret)
                                return $ret;
                }
        }else{  
               
                if ($_GET)
                        if (array_key_exists($key,$_GET))
                                return $_GET[$key];
                if ($_POST)
                        if (array_key_exists($key,$_POST))
                                return $_POST[$key];    
        }
       
        return $default;
	}

	static function is_hash_uri($uri){
		return strpos ($uri, "#")>0;
	}

	static  function startsWith($haystack,$needle,$case=false) {
	    if($case){return (strcmp(substr($haystack, 0, strlen($needle)),$needle)===0);}
	    return (strcasecmp(substr($haystack, 0, strlen($needle)),$needle)===0);
	}

	static function endsWith($haystack,$needle,$case=false) {
	    if($case){return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);}
	    return (strcasecmp(substr($haystack, strlen($haystack) - strlen($needle)),$needle)===0);
	}

		
}  
?>