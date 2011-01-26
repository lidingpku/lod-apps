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
2011-01-25, version 0.2.1 (Li)
* move define code into const

2010-12-30, version 0.2 (Li) 
* the second version

2010-04-27, version 0.1 (Li) 
* the first version

*/


/********************************************************************************
 Section 3  Source code - dependency
*********************************************************************************/
$code_dependency = array();
$code_dependency[] = "phpWebUtil.php";
$code_dependency[] = "phpRdfStream.php";
foreach($code_dependency as $code){
	require_once ($code);
}



// use web page configuration if this class is set as a web page. 
if (WebUtil::is_in_web_page_mode()){
	Json2Rdf::main_web();
}

/********************************************************************************
 Section 4 Class Definition
*********************************************************************************/

class Json2Rdf
{

	////////////////////////////////
	// configuration - version
	////////////////////////////////
	
	const ME_NAME = "phpJson2Rdf";
	const ME_VERSION = "2011-01-25";
	const ME_AUTHOR = "Li Ding";
	const ME_CREATED = "2010-04-27";
	
	// configuration - * customizable section
	
	// configuration 1
	static public function getTitle(){		return Json2Rdf::ME_NAME ."";	}
	static public function getFilename(){	return Json2Rdf::ME_NAME .".php";	}
	static public function getHomepage(){	return "http://code.google.com/p/lod-apps/wiki/phpLod#" .Json2Rdf::ME_NAME;	}

	const INPUT_URL = "url";
	const INPUT_URL_CONFIG = "url_config";  //location of json config file
	const INPUT_URI_SAMPLE = "uri_sample";
	const INPUT_NS_PROPERTY = "ns_property";
	const INPUT_OUTPUT = "output";
	const INPUT_SMART_PARSE = "smart_parse";





	const INPUT_CASE_SENSITIVE = "case_sensitive";
	const INPUT_NS_RESOURCE = "ns_resource";
	const INPUT_URL_XMLBASE = "url_xmlbase";



	public static function test(){		
		// load JSON with header
		$params[Json2Rdf::INPUT_URL] = "http://onto.rpi.edu/php-lod-test/file-example2.js";
		$params[Json2Rdf::INPUT_NS_RESOURCE] = "http://example.org/phpJson2Rdf/";
		$params[Json2Rdf::INPUT_NS_PROPERTY] = "http://example.org/phpJson2Rdf/vocab/";
		
		$Json2Rdf = new Json2Rdf();
		$map_ns_prefix = $Json2Rdf->get_default_map_ns_prefix();
		$Json2Rdf->convert($params, $map_ns_prefix);
	}

/********************************************************************************
 Section 4  Entry point
*********************************************************************************/

	public static function main_web(){
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
	
	}

/********************************************************************************
 Section 5  Source code - key functions
*********************************************************************************/

	public function convert($params_input, $map_ns_prefix){
		//load json-based config file
		if (!empty($params_input[Json2Rdf::INPUT_URL_CONFIG])){
			$temp = file_get_contents($params_input[Json2Rdf::INPUT_URL_CONFIG]);
			$this->input_config = json_decode($temp);						
		}

		$params_input[Json2Rdf::INPUT_CASE_SENSITIVE] =false;

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


		//pre-process parameters
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
		foreach($this->propmap as $property => $predicates){
			foreach ($predicates as $subject){
				if ($this->get_predicate_values ($property, $params_input[Json2Rdf::INPUT_CASE_SENSITIVE]))
					continue;
			
				$predicate = new RdfNode( RdfStream::NS_RDF."type" ) ;
				$object = new RdfNode( RdfStream::NS_RDF."Property" ) ;
				$rdf->add_triple($subject, $predicate, $object);			
			
				$predicate = new RdfNode( RdfStream::NS_RDFS."label" ) ;
				$object = new RdfNode( $property , RdfNode::RDF_STRING ) ;
				$rdf->add_triple($subject, $predicate, $object);			
			}			
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
		$object = new RdfNode( "This RDF dataset is converted from json using phpJson2Rdf (http://code.google.com/p/lod-apps/wiki/phpJson2Rdf).",  RdfNode::RDF_STRING ) ;
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
	
	public function get_predicate($predicate, $casesensitive){
		// if we have not seen the predicate
		if (!array_key_exists($predicate, $this->propmap)){
			if ($properties = $this->get_predicate_values($predicate, $casesensitive)){
				// if the predicate has already been predefined
				$predicates = array();
				foreach ($properties as $property){		
					$predicates[] = new RdfNode( $property );
				}
				
				$this->propmap[$predicate]= $predicates;		
			}else{
				// create a new predicate on the fly using local namespace
				$subject = WebUtil::normalize_localname($predicate);
				$predicates = array();
				$predicates[] = new RdfNode( $params_input[Json2Rdf::INPUT_NS_PROPERTY] . $subject );
				$this->propmap[$predicate]= $predicates;		
			}
		}
		
		return 	 $this->propmap[$predicate];	
	}
	
	private function get_predicate_values($predicate, $casesensitive){
		if (!isset($this->input_config))
			return false;
		if (!isset($this->input_config->propmap))
			return false;
			
		foreach ($this->input_config->propmap as $mapping){
			if ($casesensitive){
				if (in_array( $predicate, $mapping->p)) return $mapping->v; 
			}else{
				if (WebUtil::in_arrayi( $predicate, $mapping->p)) return $mapping->v; 
			}
		}		
		return false;
	}
	
	public function convert_json ($rdf, $params_input, $subject, $predicate, $obj){
	    if (is_array($obj)){
		$prev = null;

		$object = new RdfNode( $rdf->create_subject($params_input[Json2Rdf::INPUT_NS_RESOURCE]) ) ;
		$rdf->add_triple($subject, $predicate, $object);

		foreach ($obj as $obj_item){
			$predicate = new RdfNode( RdfStream::NS_RDFS."member" );
			$o = $this->convert_json($rdf, $params_input, $object, $predicate, $obj_item);

			if (null!=$prev && null!=$o && is_object($obj_item) ){
				//assert sequence
				$predicate = new RdfNode( RdfStream::NS_DGTWC."next" );
				$rdf->add_triple($prev, $predicate, $o);
			}
			$prev = $o;
			$o = null;
		}		
		return $object;	

	    }else{
		 if (is_object($obj)){
			// the object is a complex structure, and it needs a URI
			
			//create uri for object
			if (!isset($subject) && !isset($predicate) )
				$object = new RdfNode( $rdf->create_subject($params_input[Json2Rdf::INPUT_NS_RESOURCE],"me") ) ;
			else
				$object = new RdfNode( $rdf->create_subject($params_input[Json2Rdf::INPUT_NS_RESOURCE]) ) ;

			foreach ($obj as $key =>$value){
				foreach ($this->get_predicate($key,$params_input[Json2Rdf::INPUT_CASE_SENSITIVE]) as $p){
					$this->convert_json($rdf, $params_input, $object, $p, $value);
				}
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
  <title><?php echo Json2Rdf::getTitle(); ?></title>
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
		<a class"=info" href="<?php echo Json2Rdf::getHomepage(); ?>">
		<img src="http://lod-apps.googlecode.com/svn/trunk/doc/lod-apps-info.png" class="menuimg" alt="information"/></a>
	</div>
</td>
<td>
	<div style="margin-left:20px">
		<font size="200%"><strong> <?php echo Json2Rdf::getTitle(); ?> </strong></font>	(version <?php echo Json2Rdf::ME_VERSION; ?>)
		<br/>
		A RESTful web service that convert JSON (a tree-ish complex object) into RDF.
	</div>
</td>
</tr>
</table>

<div style="margin:10px">
<form method="get" action="<?php echo Json2Rdf::getFilename(); ?>" border="1">

<fieldset>
<legend>JSON options</legend>
URL of JSON: <input name="<?php echo Json2Rdf::INPUT_URL; ?>" size="102" type="text">   required, e.g. http://lod-apps.googlecode.com/svn/trunk/php-lod/demo/example2.js <br/>

Optionally, URL of conversion configuration file (in JSON): <input name="<?php echo Json2Rdf::INPUT_URL_CONFIG; ?>" size="102" type="text"> e.g. http://lod-apps.googlecode.com/svn/trunk/php-lod/demo/json2rdf_config_face.js <br/>

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
Type a sample URI of an instance (mapped from a JSON object): <input name="<?php echo Json2Rdf::INPUT_URI_SAMPLE; ?>" size="102" type="text" >  e.g http://example.org/phpJson2Rdf/test#thing_000001<br/>

Run smart parse? <input name="<?php echo Json2Rdf::INPUT_SMART_PARSE; ?>" type="checkbox"> check this option will identify empty, typed literal during conversion; otherwise all values will be kept as plain literal. <br/>

Type the namespace of a property (mapped from the name of the name/value pairs): <input name="<?php echo Json2Rdf::INPUT_NS_PROPERTY; ?>" size="102" type="text">  <br/>
</fieldset>


</div>


</form>
</div>

<div style="margin:10px">
<h2>Online Resources</h2>
<ul>
<li>An example here: the <a href="<?php echo Json2Rdf::getFilename(); ?>?url=http://lod-apps.googlecode.com/svn/trunk/php-lod/demo/example2.js">conversion result</a> of a <a href="http://lod-apps.googlecode.com/svn/trunk/php-lod/demo/example2.js">JSON file</a></li>
<li>More information about this tool can be found at its <a href="<?php echo Json2Rdf::getHomepage(); ?>">homepage</a> </li>
<li>Discuss this tool on twitter using <font color="green"><u>#<?php echo Json2Rdf::ME_NAME; ?></u></font> , and check out <a href="http://twitter.com/#search?q=%23<?php echo Json2Rdf::ME_NAME; ?>">related tweets</a> </li>
<li>Report issues/bugs/enhancement/comments at <a href="http://code.google.com/p/lod-apps/issues">here</a> </li>
</ul>
</div>

</body>
</html><?php
	}
		
}
?>