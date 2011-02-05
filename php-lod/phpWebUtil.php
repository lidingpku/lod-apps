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
2011-01-25, version 0.3 (Li)
* calculate URL redirection
* normalize URL (white space to %20)
* test if a php file isn running on a web server or in local system
* move define code into const

2010-12-12, version 0.1 (Li) 
* the second version

*/





/********************************************************************************
 Section 4  Source code - Main Function
*********************************************************************************/


class WebUtil{

	////////////////////////////////
	// configuration - version
	////////////////////////////////
	const ME_NAME = "phpWebUtil";
	const ME_VERSION = "2011-01-25";
	const ME_AUTHOR = "Li Ding";
	const ME_CREATED = "2010-12-12";


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

  static function normalize_url($url){
  		$replace = array();
  		$replace[" "]="%20";
  		return str_replace(array_keys($replace),array_values($replace), $url);
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

 	//http://www.regular-expressions.info/unicode.html
  
   	static public function normalize_localname($value){
		$temp = $value;
		//$temp = str_replace(' ', '_', trim(preg_replace('/[\p{P}|\p{S}|\p{C}]/',' ', $temp )));
		$temp = trim(preg_replace('/[\p{P}|\p{Sm}|\p{Sc}]/',' ', $temp ));
		$temp = preg_replace('/\\s+/', '_', $temp);
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
                                return trim($ret);
                }
        }else{  
               
                if ($_GET)
                        if (array_key_exists($key,$_GET))
                                return trim($_GET[$key]);
                if ($_POST)
                        if (array_key_exists($key,$_POST))
                                return trim($_POST[$key]);    
        }
       
        return trim($default);
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

    static function in_arrayi($needle, $haystack) {
        return in_array(strtolower($needle), array_map('strtolower', $haystack));
    }	
		



	/**
	 * source http://w-shadow.com/blog/2008/07/05/how-to-get-redirect-url-in-php/
	 * 
	 * get_redirect_url()
	 * Gets the address that the provided URL redirects to,
	 * or FALSE if there's no redirect. 
	 *
	 * @param string $url
	 * @return string
	 */
	static function get_redirect_url($url){
		$redirect_url = null; 
	 
		$url_parts = @parse_url($url);
		if (!$url_parts) return false;
		if (!isset($url_parts['host'])) return false; //can't process relative URLs
		if (!isset($url_parts['path'])) $url_parts['path'] = '/';
	 
		$sock = fsockopen($url_parts['host'], (isset($url_parts['port']) ? (int)$url_parts['port'] : 80), $errno, $errstr, 30);
		if (!$sock) return false;
	 
		$request = "HEAD " . $url_parts['path'] . (isset($url_parts['query']) ? '?'.$url_parts['query'] : '') . " HTTP/1.1\r\n"; 
		$request .= 'Host: ' . $url_parts['host'] . "\r\n"; 
		$request .= "Connection: Close\r\n\r\n"; 
		fwrite($sock, $request);
		$response = '';
		while(!feof($sock)) $response .= fread($sock, 8192);
		fclose($sock);
	 
		if (preg_match('/^Location: (.+?)$/m', $response, $matches)){
			if ( substr($matches[1], 0, 1) == "/" )
				return $url_parts['scheme'] . "://" . $url_parts['host'] . trim($matches[1]);
			else
				return trim($matches[1]);
	 
		} else {
			return false;
		}
	 
	}
 
	/**
	 * source http://w-shadow.com/blog/2008/07/05/how-to-get-redirect-url-in-php/
	 * 
	 * get_all_redirects()
	 * Follows and collects all redirects, in order, for the given URL. 
	 *
	 * @param string $url
	 * @return array
	 */
	static function get_all_redirects($url){
		$redirects = array();
		while ($newurl = WebUtil::get_redirect_url($url)){
			if (in_array($newurl, $redirects)){
				break;
			}
			$redirects[] = $newurl;
			$url = $newurl;
		}
		return $redirects;
	}
	 
	/**
	 * source http://w-shadow.com/blog/2008/07/05/how-to-get-redirect-url-in-php/
	 * 
	 * get_final_url()
	 * Gets the address that the URL ultimately leads to. 
	 * Returns $url itself if it isn't a redirect.
	 *
	 * @param string $url
	 * @return string
	 */
	static function get_final_url($url){
		$redirects = WebUtil::get_all_redirects($url);
		if (count($redirects)>0){
			return array_pop($redirects);
		} else {
			return $url;
		}
	}

	static function is_in_web_page_mode(){
		return array_key_exists("HTTP_HOST", $_SERVER); 
	}
	
	
	// open a file in utf8
	// source: http://www.practicalweb.co.uk/blog/08/05/18/reading-unicode-excel-file-php
	// http://us3.php.net/manual/en/function.mb-detect-encoding.php
	static public function detect_encoding($filename){
	    $encoding = FALSE;
        $ret = array();
	    
	    // read sample data
	    $handle = WebUtil::fopen_read($filename);
	    	    
	    $file_sample = fread($handle, 1000); //+ 'e'; //read first 1000 bytes
           fclose($handle);

	    
	    if (strlen($file_sample)>=2){
		    $bom2 = substr($file_sample,0,2);	    
	    	if($bom2 === chr(0xff).chr(0xfe)  || $bom === chr(0xfe).chr(0xff)){
	            // UTF16 Byte Order Mark present
	            $ret["encoding"] = 'UTF-16';
	            $ret["skip"] = 2;
	            return $ret;
		    } 
	    }
	    
	    
	    if (strlen($file_sample)>=3){
		    $bom3 = substr($file_sample,0,3);	    
			if ($bom3 === chr(0xef).chr(0xbb).chr(0xbf)) {
	            $ret["encoding"] = 'UTF-8';
	            $ret["skip"] = 2;
	            return $ret;
			}
		}
		
       	$encoding = mb_detect_encoding($file_sample+'e' , 'UTF-8, UTF-7, ASCII, EUC-JP,SJIS, eucJP-win, SJIS-win, JIS, ISO-2022-JP');
	       // + e is a workaround for mb_string bug

        $ret["encoding"] = $encoding;
        $ret["skip"] = 0;
        return $ret;
	}

	public static function fopen_read($filename, $encoding=FALSE, $skip=0){
	      	$handle = fopen($filename, 'r');
	      	if ($skip>0){
      			fread($handle, $skip);
      		}
	    
	    if ($encoding){
	        stream_filter_append($handle, 'convert.iconv.'.$encoding.'/UTF-8');
	    }
		
	    return  $handle;
	} 

	public static function encode_utf8($x){
	  if(strcmp(mb_detect_encoding($x,'UTF-8,ASCII'),'UTF-8')==0){
	    return $x;
	  }else{
echo mb_detect_encoding($x,'UTF-8,ASCII');
	    return utf8_encode($x);
	  }
	} 
	
	
	////////////////////////////////////////
	// functions - compose restful url
	////////////////////////////////////////
	
	// compose url
	public static function build_restful_url($url, $params, $debug=false){
		$url .="?";
		foreach ($params as $key=>$value){
			$url .=  "$key=".WebUtil::encode_my_url($value)."&";
		}
		
		if ($debug){
			echo $url;
			if (array_key_exists("query",$params)){
				echo "<pre>";
				echo $params["query"];
				echo "</pre>";
			}
		}
		
		return $url;
	}
	
	public static function  encode_my_url($url){
	        $url = urlencode($url);
	        $pattern = array("%7B","%7D");
	        $value = array("{","}");
	        str_replace($pattern, $value, $url);
	        return $url;
	}
	
	

	// get current page's URL
	public static function get_current_page_url()
	{	
		$pageURL = array_key_exists('HTTPS',$_SERVER) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
		$pageURL .= $_SERVER['SERVER_PORT'] != '80' ? $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["PHP_SELF"] : $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
		return $pageURL;
	}
	
	
	// get current page's URI
	// source http://www.webcheatsheet.com/PHP/get_current_page_url.php
	public static function get_current_page_uri()
	{
		$pageURI = $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
		$pageURI .= $_SERVER['SERVER_PORT'] != '80' ? $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"] : $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		return $pageURI;
	}	
} 
 
?>