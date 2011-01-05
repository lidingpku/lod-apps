<?php

/********************************************************************************
 Section 1. general information
*********************************************************************************/

/*
author: Li Ding (http://www.cs.rpi.edu/~dingl)
created: April 27, 2010

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
2010-12-30, version 0.2 (Li) 
* the second version

2010-04-27, version 0.1 (Li) 
* the first version

*/


/********************************************************************************
 Section 3  Source code - Configuration
*********************************************************************************/

////////////////////////////////
// configuration - version
////////////////////////////////

define("ME_NAME", "phpJson2Rdf");
define("ME_VERSION", "2010-12-30");
define("ME_AUTHOR", "Li Ding");
define("ME_CREATED", "2010-04-27");
define("ME_HOMEPAGE", "http://code.google.com/p/lod-apps/wiki/phpLod#".ME_NAME);

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
$params_input[Json2Rdf::INPUT_URL] = WebUtil::get_param(Json2Rdf::INPUT_URL);
$params_input[Json2Rdf::INPUT_URL_CONFIG] = WebUtil::get_param(Json2Rdf::INPUT_URL_CONFIG);
$params_input[Json2Rdf::INPUT_URI_SAMPLE] = WebUtil::get_param(Json2Rdf::INPUT_URI_SAMPLE);
$params_input[Json2Rdf::INPUT_NS_PROPERTY] = WebUtil::get_param(Json2Rdf::INPUT_NS_PROPERTY);
$params_input[Json2Rdf::INPUT_OUTPUT] = WebUtil::get_param(Json2Rdf::INPUT_OUTPUT, RdfStream::RDF_SYNTAX_RDFXML);
$params_input[Json2Rdf::INPUT_SMART_PARSE] = WebUtil::get_param(Json2Rdf::INPUT_SMART_PARSE, false);






if (empty($params_input[Json2Rdf::INPUT_URL])){
	Json2Rdf::show_html($params_input);
}else{

	$Json2Rdf = new Json2Rdf();
	$map_ns_prefix = RdfStream::get_default_map_ns_prefix();
	$Json2Rdf->convert($params_input, $map_ns_prefix);
}


/********************************************************************************
 Section 5  Source code - Class Definition
*********************************************************************************/

class Json2Rdf
{

	const INPUT_URL = "url";
	const INPUT_URL_CONFIG = "url_config";  //location of json config file
	const INPUT_URI_SAMPLE = "uri_sample";
	const INPUT_NS_PROPERTY = "ns_property";
	const INPUT_OUTPUT = "output";
	const INPUT_SMART_PARSE = "smart_parse";





	const INPUT_NS_RESOURCE = "ns_resource";
	const INPUT_URL_XMLBASE = "url_xmlbase";



	public static function test(){		
		// load CSV with header
		$params[Json2Rdf::INPUT_URL] = "http://onto.rpi.edu/php-lod-test/file-example2.js";
		$params[Json2Rdf::INPUT_NS_RESOURCE] = "http://example.org/phpJson2Rdf/";
		$params[Json2Rdf::INPUT_NS_PROPERTY] = "http://example.org/phpJson2Rdf/vocab/";
		
		$Json2Rdf = new Json2Rdf();
		$map_ns_prefix = $Json2Rdf->get_default_map_ns_prefix();
		$Json2Rdf->convert($params, $map_ns_prefix);
	}

	public function convert($params_input, $map_ns_prefix){
		//load json-based config file
		if (!empty($params_input[Json2Rdf::INPUT_URL_CONFIG])){
			$temp = file_get_contents($params_input[Json2Rdf::INPUT_URL_CONFIG]);
			$this->input_config = json_decode($temp);						
		}

		if (isset($this->input_config)){
			if(isset($this->input_config->namespacemap) ){
				foreach ($this->input_config->namespacemap as $mapping){
					$map_ns_prefix[$mapping->ns]= $mapping->prefix;
				}			
			}

			if(property_exists($this->input_config, Json2Rdf::INPUT_SMART_PARSE) ){
				$params_input[Json2Rdf::INPUT_SMART_PARSE] = $this->input_config->{Json2Rdf::INPUT_SMART_PARSE};
			}

			if(property_exists($this->input_config, Json2Rdf::INPUT_NS_PROPERTY) ){
				$params_input[Json2Rdf::INPUT_NS_PROPERTY] = $this->input_config->{Json2Rdf::INPUT_NS_PROPERTY};
			}

			if(property_exists($this->input_config, Json2Rdf::INPUT_NS_PROPERTY) ){
				$params_input[Json2Rdf::INPUT_NS_PROPERTY] = $this->input_config->{Json2Rdf::INPUT_NS_PROPERTY};
			}

		}


		//preprocess params
		if (empty($params_input[Json2Rdf::INPUT_URI_SAMPLE]))
			$params_input[Json2Rdf::INPUT_URI_SAMPLE] = sprintf("http://example.org/phpJson2Rdf/run%d#thing_1", time() );

		if (WebUtil::is_hash_uri( $params_input[Json2Rdf::INPUT_URI_SAMPLE]) ){
			$pos = strrpos($params_input[Json2Rdf::INPUT_URI_SAMPLE], "#");
		}else{
			$pos = strrpos($params_input[Json2Rdf::INPUT_URI_SAMPLE], "/");
		}
		$params_input[Json2Rdf::INPUT_NS_RESOURCE] = substr($params_input[Json2Rdf::INPUT_URI_SAMPLE], 0, $pos+1);
		$params_input[Json2Rdf::INPUT_URL_XMLBASE] = substr($params_input[Json2Rdf::INPUT_URI_SAMPLE], 0, $pos);

		if (empty($params_input[Json2Rdf::INPUT_NS_PROPERTY]))
			$params_input[Json2Rdf::INPUT_NS_PROPERTY] = $params_input[Json2Rdf::INPUT_NS_RESOURCE];

		if (!empty($params_input[Json2Rdf::INPUT_NS_PROPERTY]))
			$map_ns_prefix[$params_input[Json2Rdf::INPUT_NS_PROPERTY]]="";



		// load json
		$data = file_get_contents($params_input[Json2Rdf::INPUT_URL]);

		// parse json into array
		$data = json_decode ($data);
	
	
		if (!$data){
			header("HTTP/1.0 204 No Content");
			die();
		}



		// generate RDF
		$rdf = new RdfStream();

		$rdf->begin($map_ns_prefix,  $params_input[Json2Rdf::INPUT_OUTPUT], $params_input[Json2Rdf::INPUT_URL_XMLBASE]);
		
		$this->convert_json($rdf, $params_input, null, null, $data);
		
		//add property metadata
		foreach($this->propmap as $property => $subject){
			if (isset($this->input_config) && isset($this->input_config->propmap) && property_exists( $this->input_config->propmap, $property))
				continue;

			$predicate = new RdfNode( RdfStream::NS_RDF."type" ) ;
			$object = new RdfNode( RdfStream::NS_RDF."Property" ) ;
			$rdf->add_triple($subject, $predicate, $object);			
			
			$predicate = new RdfNode( RdfStream::NS_RDFS."label" ) ;
			$object = new RdfNode( $property , RdfNode::RDF_STRING ) ;
			$rdf->add_triple($subject, $predicate, $object);			
		}

		//add more metadata
		$subject = new RdfNode( $params_input[Json2Rdf::INPUT_URL_XMLBASE] ) ;
		$predicate = new RdfNode( RdfStream::NS_RDF."type" ) ;
		$object = new RdfNode( RdfStream::NS_DGTWC."Dataset" ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		$predicate = new RdfNode( RdfStream::NS_DCTERMS."source" ) ;
		$object = new RdfNode( $params_input[Json2Rdf::INPUT_URL] ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		$predicate = new RdfNode( RdfStream::NS_FOAF."primaryTopic" ) ;
		$object = new RdfNode( $rdf->create_subject($params_input[Json2Rdf::INPUT_NS_RESOURCE],"me")  ) ;
		$rdf->add_triple($subject, $predicate, $object) ;


		$predicate = new RdfNode( RdfStream::NS_RDFS."comment" ) ;
		$object = new RdfNode( "This RDF dataset is converted from csv using phpJson2Rdf (http://code.google.com/p/lod-apps/wiki/phpJson2Rdf).",  RdfNode::RDF_STRING ) ;
		$rdf->add_triple($subject, $predicate, $object) ;


		$predicate = new RdfNode( RdfStream::NS_DGTWC."number_of_properties" ) ;
		$object = new RdfNode( sizeof($this->propmap), RdfNode::RDF_INTEGER ) ;
		$rdf->add_triple($subject, $predicate, $object) ;

		// this must be the last triple asserted
		$predicate = new RdfNode( RdfStream::NS_DGTWC."number_of_triples" ) ;
		$object = new RdfNode( $rdf->number_of_triples +1, RdfNode::RDF_INTEGER ) ;
		$rdf->add_triple($subject, $predicate, $object) ;
		
		//footer
		$rdf->end();
	}

	var $propmap = array();
	var $input_config = null;
	
	public function get_predicate($predicate){
		// if we have not seen the predicate
		if (!array_key_exists($predicate, $this->propmap)){
			if (isset($this->input_config) && isset($this->input_config->propmap) && property_exists( $this->input_config->propmap, $predicate)){
				// if the predicate has already been predefined		
				$this->propmap[$predicate]= new RdfNode( $this->input_config->propmap->{$predicate}->v );
		
			}else{
				// create a new predicate on the fly using local namespace
				$subject = WebUtil::normalize_localname($predicate);
				$this->propmap[$predicate]= new RdfNode( $params_input[Json2Rdf::INPUT_NS_PROPERTY] . $subject );
			}
		}
		
		return 	 $this->propmap[$predicate];	
	}
	
	
	
	public function convert_json ($rdf, $params_input, $subject, $predicate, $obj){
	    if (is_array($obj)){
		$prev = null;
		foreach ($obj as $obj_item){
			$object = $this->convert_json($rdf, $params_input, $subject, $predicate, $obj_item);

			if (null!=$prev && null!=$object && is_object($obj_item) ){
				//assert sequence
				$predicate = new RdfNode( RdfStream::NS_DGTWC."next" );
				$rdf->add_triple($prev, $predicate, $object);
			}
			$prev = $object;
			$object = null;
		}			

	    }else{
		 if (is_object($obj)){
			// the object is a complex structure, and it needs a URI
			
			//create uri for object
			if (!isset($subject) && !isset($predicate) )
				$object = new RdfNode( $rdf->create_subject($params_input[Json2Rdf::INPUT_NS_RESOURCE],"me") ) ;
			else
				$object = new RdfNode( $rdf->create_subject($params_input[Json2Rdf::INPUT_NS_RESOURCE]) ) ;

			foreach ($obj as $key =>$value){
				$this->convert_json($rdf, $params_input, $object, $this->get_predicate($key), $value);
			}			
		}else{
			// the object is a string or a number, it is not a URI
			
			// determine object type
			if (strcmp($params_input[Json2Rdf::INPUT_SMART_PARSE],"on")==0){
				$object_type= RdfNode::RDF_AUTO_TYPE;

				//skip generate empty value
				if (strlen($obj)==0){
					continue;
				}
			}else{
				$object_type= RdfNode::RDF_STRING;
			}			
			
			// create the object
			$object = new RdfNode( $obj, $object_type );
		
		}
		if (null!=$subject){
			$rdf->add_triple($subject, $predicate, $object);
		}
		
	    }
	    return $object;

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
CSV URL: <input name="<?php echo Json2Rdf::INPUT_URL; ?>" size="102" type="text">   required, e.g. http://www.census.gov/epcd/naics02/naics02index.csv <br/>

Optionally, conversion configuration URL: <input name="<?php echo Json2Rdf::INPUT_URL_CONFIG; ?>" size="102" type="text">  <br/>

Optionally, choose the output format: 
	   <SELECT name="<?php echo Json2Rdf::INPUT_OUTPUT; ?>">
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
<legend>RDF conversion options</legend>
Show me a sample URI of a row: <input name="<?php echo Json2Rdf::INPUT_URI_SAMPLE; ?>" size="102" type="text" >  e.g http://example.org/phpJson2Rdf/test/thing_000001<br/>

Shall we do smart parse? <input name="<?php echo Json2Rdf::INPUT_SMART_PARSE; ?>" type="checkbox"> check it will identify empty, integer, datatime cells in conversion; otherwise all cells will be kept as string. <br/>

Show me the namespace of a property for a column header: <input name="<?php echo Json2Rdf::INPUT_NS_PROPERTY; ?>" size="102" type="text">  <br/>
</fieldset>


</div>


</form>
</div>

<div style="margin:10px">
<h2>Online Resources</h2>
<ul>
<li>A simple example here: <a href="<?php echo ME_FILENAME; ?>?url=http%3A%2F%2Fwww.census.gov%2Fepcd%2Fnaics02%2Fnaics02index.csv+&row_total=10&output=rdfxml&row_begin=&uri_sample=&ns_property=">convert first 10 rows</a> of a <a href="http://www.census.gov/epcd/naics02/naics02index.csv">Census' CSV file</a></li>
<li>More information about this tool can be found at its <a href="<?php echo ME_HOMEPAGE; ?>">homepage</a> </li>
<li>Discuss this tool on twitter using <font color="green"><u>#<?php echo ME_NAME; ?></u></font> , and check out <a href="http://twitter.com/#search?q=%23<?php echo ME_NAME; ?>">related tweets</a> </li>
<li>Report issues/bugs/ehancement/comments at <a href="http://code.google.com/p/lod-apps/issues">here</a> </li>
</ul>
</div>

</body>
</html><?php
	}
		
}
?>