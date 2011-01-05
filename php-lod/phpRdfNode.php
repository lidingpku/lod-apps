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

define("ME_NAME", "phpRdfNode");
define("ME_VERSION", "2010-12-12");
define("ME_AUTHOR", "Li Ding");
define("ME_CREATED", "2010-12-12");

// configuration - * customizable section

// configuration 1
define("ME_TITLE", ME_NAME ."");
define("ME_FILENAME", ME_NAME .".php");


/////////////////////////////////
// dependency
/////////////////////////////////
$code_dependency = array();
$code_dependency[] = "phpWebUtil.php";
foreach($code_dependency as $code){
	require_once ($code);
}


/********************************************************************************
 Section 4  Source code - Main Function
*********************************************************************************/


class RdfNode{
    const RDF_AUTO_TYPE = 0;
    const RDF_URI = 1;
    const RDF_BLANK = 2;
    const RDF_STRING = 3;
    const RDF_DATE = 4;
    const RDF_DATETIME = 5;
    const RDF_INTEGER = 6;
    const RDF_FLOAT = 7;


	var $value;
	var $type;
	
	public function RdfNode($value, $type=RdfNode::RDF_URI){
		$this->value=$value;
		$this->type=$type;
		
		if (RdfNode::RDF_AUTO_TYPE == $type){
			$this->do_auto_type();
		}
	}
	
	public function do_auto_type(){
		if (WebUtil::is_uri($this->value)){
			$this->type = RdfNode::RDF_URI;
		}else if (is_numeric($temp = WebUtil::clean_number($this->value) )){

			$this->value=$temp;

			if ( WebUtil::is_int($this->value) )
				$this->type=RdfNode::RDF_INTEGER;
			else if (WebUtil::is_float($this->value))
				$this->type=RdfNode::RDF_FLOAT;
			else
				$this->type=RdfNode::RDF_STRING;
/*
	there are major bugs in date parsing, do not use it
		}else if ( $date= WebUtil::parseXsdDateTime($this->value)){
			if (strlen($date)>10)
				$this->type=RdfNode::RDF_DATETIME;
			else
				$this->type=RdfNode::RDF_DATE;
				
			$this->value=$date;
*/		}else{
			$this->value=trim($this->value);
			$this->type=RdfNode::RDF_STRING;
		}		
	}

	public function get_as_rdfxml($map_ns_prefix=null){
		switch ($this->type){
		case RdfNode::RDF_URI:
			$temp = $this->get_value();
			if (null!= $map_ns_prefix){
				$temp = str_replace(array_keys($map_ns_prefix),array_values($map_ns_prefix), $temp);				
			}
			$temp = htmlspecialchars ( $temp);				
			return  $temp ;
		case RdfNode::RDF_STRING:
			return "<![CDATA[".$this->get_value()."]]>" ;
		default:
			break;
		}
		return $this->value;
	}

	public function get_xsdtype_uri(){
		switch ($this->type){
		case RdfNode::RDF_DATE:
			return "http://www.w3.org/2001/XMLSchema#date" ;
			break;
		case RdfNode::RDF_DATETIME:
			return "http://www.w3.org/2001/XMLSchema#dateTime" ;
			break;
		case RdfNode::RDF_INTEGER:
			return "http://www.w3.org/2001/XMLSchema#integer" ;
			break;
		case RdfNode::RDF_FLOAT:
			return "http://www.w3.org/2001/XMLSchema#float" ;
			break;
		case RdfNode::RDF_STRING:
		default:
			return "";
		}
	}

	
	public function get_as_nt(){
		switch ($this->type){
		case RdfNode::RDF_URI:
			return "<".$this->get_value().">";
		case RdfNode::RDF_STRING:
		default:
			return  $this->get_nt_literal($this->get_xsdtype_uri()) ;
		}
	}
	
	function get_nt_literal($xmltype=""){
		$node = str_replace('"', '\"', $this->get_value());
		$node ="\"".$node."\"";
		if (strlen($xmltype)>0){
			$node .="^^<".$xmltype.">";
		}
		return $node;
	}
	
	function get_value(){
		return utf8_encode($this->value);
	}
	
}


?>