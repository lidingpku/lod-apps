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
2010-12-04, version 0.2 (Li) 
* the second version

2010-05-16, version 0.1 (Li) 
* the first version

*/


/********************************************************************************
 Section 3  Source code - Configuration
*********************************************************************************/

////////////////////////////////
// configuration - version
////////////////////////////////

define("ME_NAME", "phpCsv2Rdf");
define("ME_VERSION", "2010-12-04");
define("ME_AUTHOR", "Li Ding");
define("ME_CREATED", "2010-05-16");
define("ME_HOMEPAGE", "http://code.google.com/p/lod-apps/wiki/phpCsv2Rdf");

// configuration - * customizable section

// configuration 1
define("ME_TITLE", ME_NAME ."");
define("ME_FILENAME", ME_NAME .".php");


////////////////////////////////
// constants
////////////////////////////////


/////////////////////////////////
// dependency
/////////////////////////////////
$code_dependency = array();
$code_dependency[] = "phpWebUtil.php";
$code_dependency[] = "phpRdfStream.php";
foreach($code_dependency as $code){
	require_once ($code);
}


/********************************************************************************
 Section 4  Source code - Main Function
*********************************************************************************/

$params_input= array();
$params_input[Csv2Rdf::INPUT_URL_CSV] = WebUtil::get_param(Csv2Rdf::INPUT_URL_CSV);
$params_input[Csv2Rdf::INPUT_URI_SAMPLE] = WebUtil::get_param(Csv2Rdf::INPUT_URI_SAMPLE);
$params_input[Csv2Rdf::INPUT_NS_PROPERTY] = WebUtil::get_param(Csv2Rdf::INPUT_NS_PROPERTY);
$params_input[Csv2Rdf::INPUT_COLUMN_FOR_URI] = WebUtil::get_param(Csv2Rdf::INPUT_COLUMN_FOR_URI);
$params_input[Csv2Rdf::INPUT_OUTPUT] = WebUtil::get_param(Csv2Rdf::INPUT_OUTPUT, RdfStream::RDF_SYNTAX_RDFXML);
$params_input[Csv2Rdf::INPUT_NO_HEADER] = WebUtil::get_param(Csv2Rdf::INPUT_NO_HEADER, false);
$params_input[Csv2Rdf::INPUT_ROW_BEGIN] = intval(WebUtil::get_param(Csv2Rdf::INPUT_ROW_BEGIN, 1));
$params_input[Csv2Rdf::INPUT_ROW_TOTAL] = intval(WebUtil::get_param(Csv2Rdf::INPUT_ROW_TOTAL, -1));
$params_input[Csv2Rdf::INPUT_SMART_PARSE] = WebUtil::get_param(Csv2Rdf::INPUT_SMART_PARSE, false);






if (empty($params_input[Csv2Rdf::INPUT_URL_CSV])){
	Csv2Rdf::show_html($params_input);
}else{

	$csv2rdf = new Csv2Rdf();
	$map_ns_prefix = RdfStream::get_default_map_ns_prefix();
	$csv2rdf->convert($params_input, $map_ns_prefix);
}


/********************************************************************************
 Section 5  Source code - Class Definition
*********************************************************************************/

class Csv2Rdf
{

	const INPUT_URL_CSV = "url_csv";
	const INPUT_URI_SAMPLE = "uri_sample";
	const INPUT_NS_PROPERTY = "ns_property";
	const INPUT_COLUMN_FOR_URI = "column_for_uri";
	const INPUT_OUTPUT = "output";
	const INPUT_NO_HEADER = "no_header";
	const INPUT_ROW_BEGIN = "row_begin";
	const INPUT_ROW_TOTAL = "row_total";
	const INPUT_SMART_PARSE = "smart_parse";





	const INPUT_NS_RESOURCE = "INPUT_NS_RESOURCE";
	const INPUT_URL_XMLBASE = "INPUT_URL_XMLBASE";



	public static function test(){		
		// load CSV with header
		$params[Csv2Rdf::INPUT_URL_CSV] = "http://tw.rpi.edu/ws/example/ex1.csv";
		$params[Csv2Rdf::INPUT_NS_RESOURCE] = "http://example.org/phpCsv2Rdf/";
		$params[Csv2Rdf::INPUT_NS_PROPERTY] = "http://example.org/phpCsv2Rdf/vocab/";
		
		$csv2rdf = new Csv2Rdf();
		$map_ns_prefix = $csv2rdf->get_default_map_ns_prefix();
		$csv2rdf->convert($params, $map_ns_prefix);
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

		// generate RDF
		$rdf = new RdfStream();

		$rdf->begin($map_ns_prefix,  $params_input[Csv2Rdf::INPUT_OUTPUT], $params_input[Csv2Rdf::INPUT_URL_XMLBASE]);
		
		//load csv
		$handle = fopen($params_input[Csv2Rdf::INPUT_URL_CSV], "r");
		$row_index =0;

		for ($i=0; $i <$params_input[Csv2Rdf::INPUT_ROW_BEGIN]; $i ++){
			$row_index++;
			$values = fgetcsv($handle);
		}

		if (strcmp($params_input[Csv2Rdf::INPUT_NO_HEADER],"on")==0){
			for($i=0; $i<sizeof($values); $i++){
				$props[] = sprintf("col_%03d",$i);
			}
		}else{
			for($i=0; $i<sizeof($values); $i++){
				$props[] = $values[$i];
			}

			$row_index++;
			$values = fgetcsv($handle);
		}

		// content
		$row_count =0;
		while ( $values !== FALSE) {
			$this->add_row_pair($rdf, $params_input, $props, $values);
			$row_count ++;

			$row_index++;
			$values = fgetcsv($handle);

			if ( (-1 != $params_input[Csv2Rdf::INPUT_ROW_TOTAL]) && ($params_input[Csv2Rdf::INPUT_ROW_TOTAL] <= $row_count) ){
				break; //terminate conversion
			}
		}
		fclose($handle);
		
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
		$object = new RdfNode( $params_input[Csv2Rdf::INPUT_URL_CSV] ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		$predicate = new RdfNode( RdfStream::NS_RDFS."comment" ) ;
		$object = new RdfNode( "This RDF dataset is converted from csv using phpCsv2Rdf (http://code.google.com/p/lod-apps/wiki/phpCsv2Rdf).",  RdfNode::RDF_STRING ) ;
		$rdf->add_triple($subject, $predicate, $object) ;


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

		foreach($row as $property=>$value){
			//skip generate a triple for key subject
			if (!empty($key_property ) && strcmp($property, $key_property )===0){
				continue;
			}
			
			$predicate = WebUtil::normalize_localname($property);

			$predicate = new RdfNode( $params_input[Csv2Rdf::INPUT_NS_PROPERTY] . $predicate );

			
			if (strcmp($params_input[Csv2Rdf::INPUT_SMART_PARSE],"on")==0){
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

	
	
	public static function show_html($input_params){		
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title><?php echo ME_TITLE; ?></title>
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
		<a class"=info" href="<?php echo ME_HOMEPAGE; ?>">
		<img src="http://lod-apps.googlecode.com/svn/trunk/doc/lod-apps-info.png" class="menuimg" alt="information"/></a>
	</div>
</td>
<td>
	<div style="margin-left:20px">
		<font size="200%"><strong> <?php echo ME_TITLE; ?> </strong></font>	(version <?php echo ME_VERSION; ?>)
		<br/>
		A RESTful web service that convert tabular CSV (a table with header) into RDF.
	</div>
</td>
</tr>
</table>

<div style="margin:10px">
<form method="get" action="<?php echo ME_FILENAME; ?>" border="1">

<fieldset>
<legend>CSV options</legend>
CSV URL: <input name="<?php echo Csv2Rdf::INPUT_URL_CSV; ?>" size="102" type="text">   required, e.g. http://www.census.gov/epcd/naics02/naics02index.csv <br/>

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
Which row is the first row (default row 1)? <input name="<?php echo Csv2Rdf::INPUT_ROW_BEGIN; ?>" size="30" type="text"> <br/>

Is the header row missing? <input name="<?php echo Csv2Rdf::INPUT_NO_HEADER; ?>" size="102" type="checkbox"> <br/>
</fieldset>

<fieldset>
<legend>RDF conversion options</legend>
Show me a sample URI of a row: <input name="<?php echo Csv2Rdf::INPUT_URI_SAMPLE; ?>" size="102" type="text" >  e.g http://example.org/phpCsv2Rdf/test/thing_000001<br/>

Shall we do smart parse? <input name="<?php echo Csv2Rdf::INPUT_SMART_PARSE; ?>" type="checkbox"> check it will identify empty, integer, datatime cells in conversion; otherwise all cells will be kept as string. <br/>

Show me the namespace of a property for a column header: <input name="<?php echo Csv2Rdf::INPUT_NS_PROPERTY; ?>" size="102" type="text">  <br/>
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
<li>A simple example here: <a href="<?php echo ME_FILENAME; ?>?url_csv=http%3A%2F%2Fwww.census.gov%2Fepcd%2Fnaics02%2Fnaics02index.csv+&row_total=10&output=rdfxml&row_begin=&uri_sample=&ns_property=">convert first 10 rows</a> of a <a href="http://www.census.gov/epcd/naics02/naics02index.csv">Census' CSV file</a></li>
<li>More information about this tool can be found at its <a href="<?php echo ME_HOMEPAGE; ?>">homepage</a> </li>
<li>Discuss this tool on twitter using <font color="green"><u>#<?php echo ME_NAME; ?></u></font> , and check out <a href="http://twitter.com/#search?q=%23<?php echo ME_NAME; ?>">related tweets</a> </li>
</ul>
</div>

</body>
</html><?php
	}
		
}
?>