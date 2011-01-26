<?php

/********************************************************************************
 Section 1. general information
*********************************************************************************/

/*
author: Li Ding (http://www.cs.rpi.edu/~dingl)
created: May 16, 2010

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
* add functions to detect invalid csv input
* add fucntions to parse input data smartly (now support csv, tsv, and tab-deliminated document)(also allow extra fields in header)
* moved the define code into class constant, and this class can be used as both a offline tool as well as a web service

2010-12-04, version 0.2 (Li) 
* the second version

2010-05-16, version 0.1 (Li) 
* the first version

*/


/********************************************************************************
 Section 3  Source code - dependency
*********************************************************************************/


/////////////////////////////////
// dependency
/////////////////////////////////
$code_dependency = array();
$code_dependency[] = "phpWebUtil.php";
$code_dependency[] = "phpRdfStream.php";
foreach($code_dependency as $code){
	require_once ($code);
}

// use web page configuration if this class is set as a web page. 
if (WebUtil::is_in_web_page_mode()){
	Csv2Rdf::main_web();
}



/********************************************************************************
 Section 4  class definition
*********************************************************************************/


class Csv2Rdf
{


	////////////////////////////////
	// configuration - version
	////////////////////////////////
	
	const ME_NAME = "phpCsv2Rdf";
	const ME_VERSION = "2011-01-25";
	const ME_AUTHOR = "Li Ding";
	const ME_CREATED = "2010-05-16";
	
	// configuration - * customizable section
	
	// configuration 1
	static public function getTitle(){		return Csv2Rdf::ME_NAME ."";	}
	static public function getFilename(){	return Csv2Rdf::ME_NAME .".php";	}
	static public function getHomepage(){	return "http://code.google.com/p/lod-apps/wiki/phpLod#" .Csv2Rdf::ME_NAME;	}




	////////////////////////////////
	// constants
	////////////////////////////////
	const INPUT_URL = "url";
	const INPUT_URI_SAMPLE = "uri_sample";
	const INPUT_NS_PROPERTY = "ns_property";
	const INPUT_COLUMN_FOR_URI = "column_for_uri";
	const INPUT_OUTPUT = "output";
	const INPUT_NO_HEADER = "no_header";
	const INPUT_ROW_BEGIN = "row_begin";
	const INPUT_ROW_TOTAL = "row_total";
	const INPUT_SMART_PARSE = "smart_parse";

	const INPUT_DELIM = "delim";
	const INPUT_NS_RESOURCE = "ns_resource";
	const INPUT_URL_XMLBASE = "url_xmlbase";

	const WORK_ROW_INDEX = "row_index";
	const WORK_DELIM = "delim";


	const SMART_NONE ="0";
	const SMART_EXTRA_HEADER_CELL = "1";
	const SMART_NO_HEADER = "2";
	const SMART_DELIM ="3";
	const SMART_CELL ="4";

	const DELIM_COMMA =",";
	const DELIM_BAR ="|";
	const DELIM_TAB ="\t";

	const MAX_CSV_LINE_LENGTH = 0;

	private $params_work;

/********************************************************************************
 Section 5  Entry point
*********************************************************************************/

	public static function main_test(){		
		// load CSV with header
		$params[Csv2Rdf::INPUT_URL] = "http://tw.rpi.edu/ws/example/ex1.csv";
		$params[Csv2Rdf::INPUT_NS_RESOURCE] = "http://example.org/phpCsv2Rdf/";
		$params[Csv2Rdf::INPUT_NS_PROPERTY] = "http://example.org/phpCsv2Rdf/vocab/";
		
		$csv2rdf = new Csv2Rdf();
		$map_ns_prefix = $csv2rdf->get_default_map_ns_prefix();
		$csv2rdf->convert($params, $map_ns_prefix);
	}


	public static function main_web(){
		$params_input= array();
		$params_input[Csv2Rdf::INPUT_URL] = WebUtil::get_param(Csv2Rdf::INPUT_URL);
		$params_input[Csv2Rdf::INPUT_URI_SAMPLE] = WebUtil::get_param(Csv2Rdf::INPUT_URI_SAMPLE);
		$params_input[Csv2Rdf::INPUT_NS_PROPERTY] = WebUtil::get_param(Csv2Rdf::INPUT_NS_PROPERTY);
		$params_input[Csv2Rdf::INPUT_COLUMN_FOR_URI] = WebUtil::get_param(Csv2Rdf::INPUT_COLUMN_FOR_URI);
		$params_input[Csv2Rdf::INPUT_OUTPUT] = WebUtil::get_param(Csv2Rdf::INPUT_OUTPUT, RdfStream::RDF_SYNTAX_RDFXML);
		$params_input[Csv2Rdf::INPUT_NO_HEADER] = WebUtil::get_param(Csv2Rdf::INPUT_NO_HEADER, false);
		$params_input[Csv2Rdf::INPUT_ROW_BEGIN] = intval(WebUtil::get_param(Csv2Rdf::INPUT_ROW_BEGIN, 1));
		$params_input[Csv2Rdf::INPUT_ROW_TOTAL] = intval(WebUtil::get_param(Csv2Rdf::INPUT_ROW_TOTAL, -1));
		$params_input[Csv2Rdf::INPUT_SMART_PARSE] = WebUtil::get_param(Csv2Rdf::INPUT_SMART_PARSE, SMART_NONE );
		$params_input[Csv2Rdf::INPUT_DELIM] = WebUtil::get_param(Csv2Rdf::INPUT_DELIM );
		
		if (empty($params_input[Csv2Rdf::INPUT_URL])){
			Csv2Rdf::show_html($params_input);
		}else{
		
			$csv2rdf = new Csv2Rdf();
			$map_ns_prefix = RdfStream::get_default_map_ns_prefix();
			$csv2rdf->convert($params_input, $map_ns_prefix);
		}
	}

	public function convert($params_input, $map_ns_prefix){
		//preprocess params
		if (empty($params_input[Csv2Rdf::INPUT_URI_SAMPLE]))
			$params_input[Csv2Rdf::INPUT_URI_SAMPLE] = sprintf("http://example.org/phpCsv2Rdf/run%d#thing_1", time() );

		if (WebUtil::is_hash_uri( $params_input[Csv2Rdf::INPUT_URI_SAMPLE]) ){
			$pos = strrpos($params_input[Csv2Rdf::INPUT_URI_SAMPLE], "#");
		}else{
			$pos = strrpos($params_input[Csv2Rdf::INPUT_URI_SAMPLE], "/");
		}
		$params_input[Csv2Rdf::INPUT_NS_RESOURCE] = substr($params_input[Csv2Rdf::INPUT_URI_SAMPLE], 0, $pos+1);
		$params_input[Csv2Rdf::INPUT_URL_XMLBASE] = substr($params_input[Csv2Rdf::INPUT_URI_SAMPLE], 0, $pos);

		if (empty($params_input[Csv2Rdf::INPUT_NS_PROPERTY]))
			$params_input[Csv2Rdf::INPUT_NS_PROPERTY] = $params_input[Csv2Rdf::INPUT_NS_RESOURCE];

		if (!empty($params_input[Csv2Rdf::INPUT_NS_PROPERTY]))
			$map_ns_prefix[$params_input[Csv2Rdf::INPUT_NS_PROPERTY]]="";

		if (!is_numeric($params_input[Csv2Rdf::INPUT_ROW_BEGIN])){
			$params_input[Csv2Rdf::INPUT_ROW_BEGIN] =1;
		}
		$params_input[Csv2Rdf::INPUT_ROW_BEGIN] = max(1, $params_input[Csv2Rdf::INPUT_ROW_BEGIN]);

		if (!is_numeric($params_input[Csv2Rdf::INPUT_ROW_TOTAL])){
			$params_input[Csv2Rdf::INPUT_ROW_TOTAL] = -1;
		}



		$this->params_work = array();
		$this->params_work[Csv2Rdf::WORK_ROW_INDEX] =0;
		$this->params_work[Csv2Rdf::WORK_DELIM] = urldecode($params_input[Csv2Rdf::INPUT_DELIM]);

		error_reporting(E_ERROR);	
		
		$tempurl = $params_input[Csv2Rdf::INPUT_URL];
		$tempurl = WebUtil::normalize_url($tempurl);

		// if this is a http or ftp url, validate it first
		if (	strncasecmp($params_input[Csv2Rdf::INPUT_URL],"http",4)==0
			||strncasecmp($params_input[Csv2Rdf::INPUT_URL],"ftp",3)==0 ){

			// first round validation on URL
			$tempurl = Csv2Rdf::redirectAndValidateUrl($tempurl);
			if (empty($tempurl)){
				return;
			}
		}

		//load csv
		$handle = fopen($tempurl, "r");
		$messages = array();

		error_reporting(E_ERROR | E_WARNING | E_PARSE);	
		
		if (FALSE==$handle){
			Csv2Rdf::report_error("Error","FILE NOT FOUND","cannot connect to the URL (open failed)");
			echo ("\n");
			echo($tempurl);
			echo ("\n");
			print_r(error_get_last());
			return;
		}

		
		//skip csv rows
		for ($i=0; $i <$params_input[Csv2Rdf::INPUT_ROW_BEGIN]; $i ++){
			$values =$this->parse_file_row($handle, $params_input, false);
		}

/*
		//$cnt_skipped_emptyrows=0;
		//skip empty rows
		while ( Csv2Rdf::isEmptyRow($values) ){
			$values = Csv2Rdf::parse_file_row($handle, $params_input);
			$cnt_skipped_emptyrows++;
		}
		//if ($cnt_skipped_emptyrows>0){
		//	$messages[] ="total empty rows skipped: ". $cnt_skipped_emptyrows;
		//}
*/


		//create/extract headers
		$values_next =  Csv2Rdf::parse_file_row($handle, $params_input);
		$bNoHeader = (strcmp($params_input[Csv2Rdf::INPUT_NO_HEADER],"on")==0);
		if (strcmp($params_input[Csv2Rdf::INPUT_SMART_PARSE],Csv2Rdf::SMART_NO_HEADER)>=0 && Csv2Rdf::isRowsOverlap($values, $values_next)){
			$messages[] = "[SMART NO HEADER ROW] - because the first two rows share common cells." ;
			$bNoHeader=true;
		}			

		if ($bNoHeader){
			for($i=0; $i<sizeof($values); $i++){
				$props[] = sprintf("col_%03d",$i);
			}

		}else{
			for($i=0; $i<sizeof($values); $i++){
				if ( null!=$values[$i] && strlen($values[$i])>0)
					$props[] = $values[$i];
				else
					$props[] = sprintf("col_%03d",$i);	//create a default column header if the header cell is empty
			}

			$values =$values_next;
			$values_next = FALSE;
		}


		//smart operation, reduce extra header
		if (strcmp($params_input[Csv2Rdf::INPUT_SMART_PARSE],Csv2Rdf::SMART_EXTRA_HEADER_CELL)>=0){			
			if (!Csv2Rdf::isEmptyRow($values) && (sizeof($props) > sizeof($values))){
				$messages[] = sprintf("[SMART EXTRA HEADER CELL]- there are more cells in header rows (%d) than data row (%d). Real column is:%d", sizeof($props), sizeof($values), sizeof($values)) ;
				$props = array_slice($props, 0, sizeof($values));
			}
		}

		//valdiate
		if (!Csv2Rdf::validateHeaders($props)){
			echo ("\nheader");
			print_r($props);
			echo ("\nvalue");
			print_r($values);
			return;
		}

		// validate
		if (FALSE===$values || Csv2Rdf::isEmptyRow($values)){
			Csv2Rdf::report_error("Error","CSV NO DATA ROW","cannot find data row in this file");
			echo ("\nheader");
			print_r($props);
			return;
		}


		//valdiate
		if (!Csv2Rdf::validateHeaderValues($props,$values, true)){
			echo ("\nheader");
			print_r($props);
			echo ("\nvalue");
			print_r($values);
			return;
		}


		// generate RDF
		$rdf = new RdfStream();
		$rdf->begin($map_ns_prefix,  $params_input[Csv2Rdf::INPUT_OUTPUT], $params_input[Csv2Rdf::INPUT_URL_XMLBASE]);

		// content
		$row_count =0;
		while ( $values !== FALSE) {

			$this->add_row_pair($rdf, $params_input, $props, $values);
			$row_count ++;

			if (FALSE!==$values_next){
				$values = $values_next;
				$values_next =FALSE;
			}else{
				$values =$this->parse_file_row($handle, $params_input);
			}

			// if the row is empty
			if (FALSE===$values){
				break;
			}

			if ( (-1 != $params_input[Csv2Rdf::INPUT_ROW_TOTAL]) && ($params_input[Csv2Rdf::INPUT_ROW_TOTAL] <= $row_count) ){
				break; //terminate conversion
			}

			//check if the number of header fields is the same as the number of cells in the first row
			if (!Csv2Rdf::validateHeaderValues($props,$values, false)){
				echo ("\nheader");
				print_r($props);
				echo ("\nvalue");
				print_r($values);
				return;
			}
		}
		fclose($handle);

		// record process information
		if (!array_key_exists(Csv2Rdf::INPUT_DELIM, $params_input) || strlen($params_input[Csv2Rdf::INPUT_DELIM])==0){
			$messages[]= "[SMART DELIM] - the detected delimiter is '".$this->params_work[Csv2Rdf::WORK_DELIM]
						. "' and its encoded form is:" .urlencode($this->params_work[Csv2Rdf::WORK_DELIM]);	
		}

		//add property metadata
		foreach($props as $property){
			$subject = WebUtil::normalize_localname($property);
			$subject = new RdfNode( $params_input[Csv2Rdf::INPUT_NS_PROPERTY] . $subject );

			$predicate = new RdfNode( RdfStream::NS_RDF."type" ) ;
			$object = new RdfNode( RdfStream::NS_RDF."Property" ) ;
			$rdf->add_triple($subject, $predicate, $object);			
			
			$predicate = new RdfNode( RdfStream::NS_RDFS."label" ) ;
			$object = new RdfNode( $property , RdfNode::RDF_STRING ) ;
			$rdf->add_triple($subject, $predicate, $object);			
		}

		//add more metadata
		$subject = new RdfNode( $params_input[Csv2Rdf::INPUT_URL_XMLBASE] ) ;
		$predicate = new RdfNode( RdfStream::NS_RDF."type" ) ;
		$object = new RdfNode( RdfStream::NS_DGTWC."Dataset" ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		$predicate = new RdfNode( RdfStream::NS_DCTERMS."source" ) ;
		$object = new RdfNode( $params_input[Csv2Rdf::INPUT_URL] ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		// get last modified date time
		$predicate = new RdfNode( RdfStream::NS_DCTERMS."modified" ) ;
		$remote = get_headers($url,1);
		if (!empty($remote["Last-Modified"])){
			$object = new RdfNode( $remote["Last-Modified"] , RdfNode::RDF_STRING ) ; 
			$rdf->add_triple($subject, $predicate, $object) ;
		}

		$predicate = new RdfNode( RdfStream::NS_RDFS."comment" ) ;
		$object = new RdfNode( "This RDF dataset is converted from csv using phpCsv2Rdf (http://code.google.com/p/lod-apps/wiki/phpCsv2Rdf).",  RdfNode::RDF_STRING ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		foreach($messages as $msg){
			$predicate = new RdfNode( RdfStream::NS_RDFS."comment" ) ;
			$object = new RdfNode( $msg,  RdfNode::RDF_STRING ) ;
			$rdf->add_triple($subject, $predicate, $object) ;
		}

		$predicate = new RdfNode( RdfStream::NS_DGTWC."number_of_entries" ) ;
		$object = new RdfNode( $row_count, RdfNode::RDF_INTEGER ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		$predicate = new RdfNode( RdfStream::NS_DGTWC."number_of_properties" ) ;
		$object = new RdfNode( sizeof($props), RdfNode::RDF_INTEGER ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		// this must be the last triple asserted
		$predicate = new RdfNode( RdfStream::NS_DGTWC."number_of_triples" ) ;
		$object = new RdfNode( $rdf->number_of_triples +1, RdfNode::RDF_INTEGER ) ;
		$rdf->add_triple($subject, $predicate, $object) ;
		
		//footer
		$rdf->end();
	}

	
	// skip empty rows here
    private function parse_file_row($handle, $params_input, $skipemptyrow=true){
		if (strlen($this->params_work[Csv2Rdf::WORK_DELIM])>0){
			do{
				$values = fgetcsv($handle, Csv2Rdf::MAX_CSV_LINE_LENGTH, $this->params_work[Csv2Rdf::WORK_DELIM]);
				$this->params_work[Csv2Rdf::WORK_ROW_INDEX]++;
				if (FALSE === $values )
					return FALSE ;	
			}while  ( $skipemptyrow && Csv2Rdf::isEmptyRow($values) );
		}else{
			do{
				$values =  fgetcsv($handle);
				$this->params_work[Csv2Rdf::WORK_ROW_INDEX]++;
				if (FALSE === $values )
					return FALSE ;	
			}while ( $skipemptyrow && Csv2Rdf::isEmptyRow($values) );

			if (strcmp($params_input[Csv2Rdf::INPUT_SMART_PARSE] ,Csv2Rdf::SMART_DELIM)>=0){
				if (sizeof($values)==1){
					$values = explode(Csv2Rdf::DELIM_BAR,$values[0]);
				}else{
					$this->params_work[Csv2Rdf::WORK_DELIM]=Csv2Rdf::DELIM_COMMA;
					return $values;
				}

				if (sizeof($values)==1){
					$values = explode(Csv2Rdf::DELIM_TAB,$values[0]);
				}else{
					$this->params_work[Csv2Rdf::WORK_DELIM]=Csv2Rdf::DELIM_BAR;
					return $values;
				}

				if (sizeof($values)>1){
					$this->params_work[Csv2Rdf::WORK_DELIM]=Csv2Rdf::DELIM_TAB;
					return $values;
				}
			}

		}
		return $values;
	}


    public function add_row_pair($rdf, $params_input, $row_keys, $row_values){
		$this->add_row($rdf, $params_input, array_combine($row_keys, $row_values));
	}
	
    public function add_row($rdf, $params_input, $row){
		if (array_key_exists( Csv2Rdf::INPUT_COLUMN_FOR_URI, $params_input)&& !empty($params_input[Csv2Rdf::INPUT_COLUMN_FOR_URI]) ){
			$key_property = $params_input[Csv2Rdf::INPUT_COLUMN_FOR_URI];
			$subject = $row[ $key_subject ];
		}
		
		if ( !WebUtil::is_uri($subject) ){
			$subject = new RdfNode( $rdf->create_subject($params_input[Csv2Rdf::INPUT_NS_RESOURCE]) ) ;
		}

		//declare this row as a data entry
		$predicate = new RdfNode( RdfStream::NS_RDF."type" ) ;
		$object = new RdfNode( RdfStream::NS_DGTWC."DataEntry" ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		//create properties
		foreach($row as $property=>$value){
			//skip generate a triple for key subject
			if (!empty($key_property ) && strcmp($property, $key_property )===0){
				continue;
			}
			
			$predicate = WebUtil::normalize_localname($property);

			$predicate = new RdfNode( $params_input[Csv2Rdf::INPUT_NS_PROPERTY] . $predicate );

			
			if (strcmp($params_input[Csv2Rdf::INPUT_SMART_PARSE],Csv2Rdf::SMART_CELL)>=0){
				$object_type= RdfNode::RDF_AUTO_TYPE;

				//skip generate empty value
				if (strlen($value)==0){
					continue;
				}
			}else{
				$object_type= RdfNode::RDF_STRING;
			}

			if (is_array($value)){
				//a list of values
				foreach($value as $object){
			
					$object = new RdfNode( $object, $object_type );
					$rdf->add_triple($subject, $predicate, $object);			
				}
			}else{
				$object =$value;
				$object = new RdfNode( $object, $object_type );
				$rdf->add_triple($subject, $predicate, $object);
			}
		}
	}

	private static function removeTailEmptyCells($arr){
		for ($i=sizeof($arr)-1; $i>=0; $i--){
			if (null!=$arr[$i] && sizeof($arr[$i])>0){
				return array_slice($arr,0,$i+1);
			}
		}
	}

	private static function report_error($level, $key, $message, $use_header = true){
		if (WebUtil::is_in_web_page_mode() && $use_header){
			header ("Content-Type: text/plain");
		}

		echo "[$level:$key] $message\n";
	}	

	private static function validateHeaders($props){
		//check if the header is empty
		if (sizeof($props)==0){
			Csv2Rdf::report_error("Error","CSV NO HEADER","no header specified, expect one.");
			return false;			
		}

		//check if the header is single column
		if (sizeof($props)==1){
			Csv2Rdf::report_error("Error","CSV ONE COLUMN","only found one column, assume not right.");
			return false;			
		}


		// avoid html input
		if (strncasecmp($props[0],"<!DOCTYPE", 9)==0){
			Csv2Rdf::report_error("Error","ENCOUNTER HTML","reading html file");
			return false;			
		}

		
		
		//check header
		for ($i=0; $i< sizeof($props); $i++){
			if (empty($props[$i])){
				Csv2Rdf::report_error("Error","CSV EMPTY FIELD","empty header field");
				return false;				
			}
		} 

		return true;
	}

	private static function isEmptyRow($row){
		if (sizeof($row)>1){
			return false;
		}
		return strlen(trim($row[0]))==0;
	}

	private static function isRowSameSize($row1, $row2){
		return sizeof($row1)==sizeof($row2);
	}

	private static function isRowsOverlap($row1, $row2){
		for ($i=0; $i< sizeof($row2); $i++){
			if (strcmp($row1[$i],$row2[$i])==0){
				return true;
			}
		}
		return false;
	}

	private static function validateHeaderValues($props, $values, $use_header){
		//check if the number of header fields is the same as the number of cells in the first row
		if (!Csv2Rdf::isRowSameSize($props,$values)){
			Csv2Rdf::report_error("Error","CSV MISMATCH HEADER-VALUE COLUMN", "the number of header fields is different from the number of cells in the first row", $use_header);
			return false;
		}


		//check if the assumed header is actually the real header
		if (Csv2Rdf::isRowsOverlap($props, $values)){
			Csv2Rdf::report_error("Error","CSV NO HEADER","found same value in header cell and value cell, it seems the csv does not have header row", $use_header);
			return false;
		}
		return true;
	}


	private static function redirectAndValidateUrl($url){
		//validate URL
		$tempurl = WebUtil::get_final_url($url);
		if (!empty($tempurl) && WebUtil::endsWith($tempurl,".zip")){
			header ("Content-Type: text/plain");
			Csv2Rdf::report_error("Error","URL END WITH ZIP","cannot load csv from a zip file ");
			echo ("\n");
			echo($tempurl);
			return null;
		}
		

		// Report simple running errors
		//check http header
		$response = get_headers($tempurl);
		foreach ($response as $item){
			if (strncasecmp($item, "Content-Type",11)!=0){
				continue;
			}
			//print_r($response);

			$exts = array("zip","html","excel");
			foreach ($exts as  $ext){
				if (stripos($item, $ext)>0){
					Csv2Rdf::report_error("Error","HTTP HEADER $ext","content-type mismatch ($ext).");
					echo ($item);
					echo ("\n");
					echo($tempurl);
					return null;
				}
			}
						
		}


		if (empty($response)){
			Csv2Rdf::report_error("Error","HTTP NO RESPONSE","cannot connect to the URL (no response)");
			echo ("\n");
			echo($tempurl);
			echo ("\n");
			return null;
		}

		return $tempurl ;
	}
	
	
	public static function show_html($input_params){		
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title><?php echo Csv2Rdf::getTitle(); ?></title>
  <style type="text/css">
     img.menuimg { border:0; }
     input {margin:5px}
  </style>

  <script type="text/javascript">
    function toggle_visibility(id) {
       var e = document.getElementById(id);
       if(e.style.display == 'block')
          e.style.display = 'none';
       else
          e.style.display = 'block';
    }
  </script>
</head>
<body>
<table style="margin:10px">
<tr>
<td>
	<div>
	       <!-- link to home page -->		
		<a class"=info" href="http://code.google.com/p/lod-apps">
		<img src="http://lod-apps.googlecode.com/svn/trunk/doc/lod-apps-logo.png" alt="lod apps" class="menuimg"/></a>
	</div>
</td>
<td>
	<div>
       	<!-- link to demo's wiki page -->		
		<a class"=info" href="<?php echo Csv2Rdf::getHomepage(); ?>">
		<img src="http://lod-apps.googlecode.com/svn/trunk/doc/lod-apps-info.png" class="menuimg" alt="information"/></a>
	</div>
</td>
<td>
	<div style="margin-left:20px">
		<font size="200%"><strong> <?php echo Csv2Rdf::getTitle(); ?> </strong></font>	(version <?php echo Csv2Rdf::ME_VERSION; ?>)
		<br/>
		A RESTful web service that convert tabular CSV (a table with header) into RDF.
	</div>
</td>
</tr>
</table>

<div style="margin:10px">
<form method="get" action="<?php echo Csv2Rdf::getFilename(); ?>" border="1">

<fieldset>
<legend>CSV options</legend>
URL of CSV: <input name="<?php echo Csv2Rdf::INPUT_URL; ?>" size="102" type="text">   required, e.g. http://www.census.gov/epcd/naics02/naics02index.csv <br/>

Optionally, choose the total number of rows to be converted: 
	   <SELECT name="<?php echo Csv2Rdf::INPUT_ROW_TOTAL; ?>">
		 <OPTION VALUE="-1" SELECTED>All Rows (default)</OPTION>
		 <OPTION VALUE="10">first 10 rows</OPTION>
		 <OPTION VALUE="100">first 100 rows</OPTION>
		 <OPTION VALUE="1000">first 1000 rows</OPTION>
	   </SELECT>  <br/>

Optionally, choose the output format: 
	   <SELECT name="<?php echo Csv2Rdf::INPUT_OUTPUT; ?>">
		 <OPTION VALUE="<?php echo RdfStream::RDF_SYNTAX_RDFXML; ?>" SELECTED>RDF/XML (default)</OPTION>
		 <OPTION VALUE="<?php echo RdfStream::RDF_SYNTAX_NT; ?>">N-Triple</OPTION>
		 <OPTION VALUE="<?php echo RdfStream::RDF_SYNTAX_NQUADS; ?>">N-Quads</OPTION>
	   </SELECT> <br/>
</fieldset>


<input value="convert"  type="submit"> <br/>

<a href="#" onclick="toggle_visibility('extra_params');">Show/Hide Experimental Parameters</a>

<!-- other options -->
<div id="extra_params" style="display:none">

<fieldset>
<legend>Additional CSV options</legend>
Which row in CSV file is the first table row (default 1)? <input name="<?php echo Csv2Rdf::INPUT_ROW_BEGIN; ?>" size="30" type="text"> <br/>

Is the header row missing? <input name="<?php echo Csv2Rdf::INPUT_NO_HEADER; ?>" size="102" type="checkbox"> <br/>

Do you know the delimiter for cells (we can guess/parse tsv,csv,bar-sv)? 
	   <SELECT name="<?php echo Csv2Rdf::INPUT_DELIM; ?>">
		 <OPTION VALUE="" >  (n/a, default)</OPTION>
		 <OPTION VALUE="<?php echo urlencode(Csv2Rdf::DELIM_COMMA); ?>" > , (comma,csv)</OPTION>
		 <OPTION VALUE="<?php echo urlencode(Csv2Rdf::DELIM_BAR); ?>" > | (bar)</OPTION>
		 <OPTION VALUE="<?php echo urlencode(Csv2Rdf::DELIM_TAB); ?>" > TAB (tab, tsv)</OPTION>
	   </SELECT> <br/>

</fieldset>

<fieldset>
<legend>RDF conversion options</legend>
Show me a sample URI of an instance (mapped from a CSV row): <input name="<?php echo Csv2Rdf::INPUT_URI_SAMPLE; ?>" size="102" type="text" >  e.g http://example.org/phpCsv2Rdf/test/thing_000001<br/>

Run smart parse? 
	   <SELECT name="<?php echo Csv2Rdf::INPUT_SMART_PARSE; ?>">
		 <OPTION VALUE="<?php echo Csv2Rdf::SMART_NONE; ?>" SELECTED> default, no smart</OPTION>
		 <OPTION VALUE="<?php echo Csv2Rdf::SMART_EXTRA_HEADER_CELL; ?>" >remove extra header cells</OPTION>
		 <OPTION VALUE="<?php echo Csv2Rdf::SMART_NO_HEADER; ?>" >above, plus guessing if the table has no header row</OPTION>
		 <OPTION VALUE="<?php echo Csv2Rdf::SMART_DELIM; ?>" >above, plus guessing delimiter of cells</OPTION>
		 <OPTION VALUE="<?php echo Csv2Rdf::SMART_CELL; ?>" >above, plus guessing data type of cell</OPTION>
	   </SELECT> <br/>
<br/>

Type the namespace of a property (mapped from a column header): <input name="<?php echo Csv2Rdf::INPUT_NS_PROPERTY; ?>" size="102" type="text">  <br/>
</fieldset>



<!--
Column for URI: <input name="<?php echo Csv2Rdf::INPUT_COLUMN_FOR_URI; ?>" size="102" type="text"> (string, optional) <br/>
-->   
</div>


</form>
</div>

<div style="margin:10px">
<h2>Online Resources</h2>
<ul>
<li>A simple example here: <a href="<?php echo Csv2Rdf::getFilename(); ?>?url=http%3A%2F%2Fwww.census.gov%2Fepcd%2Fnaics02%2Fnaics02index.csv&row_total=10&output=rdfxml&row_begin=&uri_sample=&ns_property=">convert first 10 rows</a> of a <a href="http://www.census.gov/epcd/naics02/naics02index.csv">Census' CSV file</a></li>
<li>More information about this tool can be found at its <a href="<?php echo Csv2Rdf::getHomepage(); ?>">homepage</a> </li>
<li>Discuss this tool on twitter using <font color="green"><u>#<?php echo Csv2Rdf::ME_NAME; ?></u></font> , and check out <a href="http://twitter.com/#search?q=%23<?php echo Csv2Rdf::ME_NAME; ?>">related tweets</a> </li>
<li>Report issues/bugs/enhancement/comments at <a href="http://code.google.com/p/lod-apps/issues">here</a> </li>
</ul>
</div>

</body>
</html><?php
	}
		
}
?>