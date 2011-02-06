<?php

/********************************************************************************
 Section 1. general information
*********************************************************************************/

/*
author: Li Ding (http://www.cs.rpi.edu/~dingl)
created: Feb 3, 2011

MIT License

Copyright (c) 2011

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
2011-02-03, version 0.1 (Li) 
* migrated from http://logd.tw.rpi.edu/service/uri_resolver

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
	SparqlExplorer::main_web();
}



/********************************************************************************
 Section 4  class definition
*********************************************************************************/


class SparqlExplorer
{


	////////////////////////////////
	// configuration - version
	////////////////////////////////
	
	const ME_NAME = "phpSparqlExplorer";
	const ME_VERSION = "2011-02-03";
	const ME_AUTHOR = "Li Ding";
	const ME_CREATED = "2011-02-03";
	
	// configuration - * customizable section
	
	// configuration 1
	static public function getTitle(){		return SparqlExplorer::ME_NAME ."";	}
	static public function getFilename(){	return SparqlExplorer::ME_NAME .".php";	}
	static public function getHomepage(){	return "http://code.google.com/p/lod-apps/wiki/phpLod#" .SparqlExplorer::ME_NAME;	}




	////////////////////////////////
	// constants
	////////////////////////////////
	const INPUT_URI= "uri";
	const INPUT_URI_SPARQL_ENDPOINT = "sparqlendpoint";
	const INPUT_URI_SPARQL_PROXY= "sparqlproxy";
	const INPUT_OUTPUT = "output";
	const INPUT_DEBUG = "debug";	
	const INPUT_SHOW_SOURCE = "show_source";	
	const INPUT_SHOW_MAX_ROW = "show_max_row";	


	const WORK_URI_CURRENT_PAGE= "uri-current-page";
	const WORK_URI_DATA= "uri-data";


	const MIME_RDFXML="application/rdf+xml";
	const MIME_TURTLE="text/turtle";
	const MIME_N3="text/n3+rdf";
	const MIME_JSON="application/rdf+json";
	const MIME_RDFA="application/xhtml+xml"; 


	const OUTPUT_RDFXML ="rdfxml"; 
	const OUTPUT_TURTLE ="turtle"; 
	const OUTPUT_N3 ="n3"; 
	const OUTPUT_JSON ="json"; 

	const OUTPUT_RDFA_TABLE="rdfa_table_embed"; //fragment
	const OUTPUT_RDFA_HIDE="rdfa_hide_embed";
	const OUTPUT_RDFA_TABLE_XHTML="rdfa_table_xhtml";	//complete xhtml
	const OUTPUT_RDFA_HIDE_XHTML="rdfa_hide_xhtml";	
	


	

/********************************************************************************
 Section 5  Entry point
*********************************************************************************/


	static public  function main_web(){
		
		$params_input= array();
		$params_input[SparqlExplorer::INPUT_URI] = WebUtil::get_param(SparqlExplorer::INPUT_URI);
		$params_input[SparqlExplorer::INPUT_URI_SPARQL_ENDPOINT] = WebUtil::get_param(SparqlExplorer::INPUT_URI_SPARQL_ENDPOINT);
		$params_input[SparqlExplorer::INPUT_URI_SPARQL_PROXY] = WebUtil::get_param(SparqlExplorer::INPUT_URI_SPARQL_PROXY, "http://logd.tw.rpi.edu/ws/sparqlproxy.php");
		$params_input[SparqlExplorer::INPUT_OUTPUT] = WebUtil::get_param(SparqlExplorer::INPUT_OUTPUT, RdfStream::RDF_SYNTAX_RDFXML);
		$params_input[SparqlExplorer::INPUT_DEBUG] = WebUtil::get_param(SparqlExplorer::INPUT_DEBUG, FALSE);
		$params_input[SparqlExplorer::INPUT_SHOW_SOURCE] = WebUtil::get_param(SparqlExplorer::INPUT_SHOW_SOURCE);
		$params_input[SparqlExplorer::INPUT_SHOW_MAX_ROW] = WebUtil::get_param(SparqlExplorer::INPUT_SHOW_MAX_ROW, 100);



		$params_input[SparqlExplorer::WORK_URI_CURRENT_PAGE]= WebUtil::get_current_page_uri();
				
		//debug
		//print_r($params_input);


		//display content	
		if (empty($params_input[SparqlExplorer::INPUT_URI])){
			SparqlExplorer::show_html($params_input, false);
		}else{
			SparqlExplorer::show_html($params_input, SparqlExplorer::get_rdfa($params_input));		
		}
	}


	private static function is_show_source($params_input){
		return strcmp($params_input[SparqlExplorer::INPUT_SHOW_SOURCE], "on")==0;
	}
	public static function get_rdfa($params_input){
		  $data = "";
		  $data .= '<hr/>';
		  $data .= '<div> index:';
		  $data .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		  $data .= '<a href="#description">Description</a>';
		  $data .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		  $data .= '<a href="#is_referenced_by">Is referenced by</a>';
		  $data .= '</div>';
		  $data .= '<hr/>';


		//  $data .= '<div style="background:#EEF; width:100%">';
		  $data .= '<h2><a name="description">Description</a> </h2>';
		  $data .= SparqlExplorer::get_rdfa_data_s($params_input,false, true);
		//  $data .= '</div>';

		 // $data .= '<div style="background:#FEE; width:100%">';
		  $data .= '<h2><a name="is_referenced_by">Is referenced by</a></h2>';
		  $data .= SparqlExplorer::get_rdfa_data_o($params_input,false, true);
		//  $data .= '</div>';

		  return $data;
	}
	
	
	static function  get_rdfa_data_s($params_input, $xhtml=false, $visible=true){
		  $params_desc = array();
		    $ret ="";
	  
		  if (SparqlExplorer::is_show_source($params_input)){
		      $params_desc["query"]= sprintf("
				SELECT distinct ?p ?o ?g  
				WHERE{ 
				   {GRAPH ?g { <%s> ?p ?o . } }
				   UNION
				   { { <%s> ?p ?o . } }
				} order by ?p ?o limit %d
				",$params_input[SparqlExplorer::INPUT_URI]
				 ,$params_input[SparqlExplorer::INPUT_URI]
				 ,$params_input[SparqlExplorer::INPUT_SHOW_MAX_ROW]);
		  }else{
		      $params_desc["query"]= sprintf("
				SELECT distinct ?p ?o 
				WHERE{ 
				   {GRAPH ?g { <%s> ?p ?o . } }
				   UNION 
				   { { <%s> ?p ?o . } }
				} order by ?p ?o limit %d
				",$params_input[SparqlExplorer::INPUT_URI]
				 ,$params_input[SparqlExplorer::INPUT_URI]
				,$params_input[SparqlExplorer::INPUT_SHOW_MAX_ROW]);
		  }			

		  $params_desc["service-uri"]=  $params_input[SparqlExplorer::INPUT_URI_SPARQL_ENDPOINT];		
		  $params_desc["output"]=  "sparqljson";		
		  $params_input[SparqlExplorer::WORK_URI_DATA]= WebUtil::build_restful_url( $params_input[SparqlExplorer::INPUT_URI_SPARQL_PROXY], $params_desc) ;

		  if (!empty($params_input[SparqlExplorer::INPUT_DEBUG])){
			print_r($params_input);
		   }
	  
		$remote = get_headers($params_input[SparqlExplorer::WORK_URI_DATA]);
		$responsecode = substr($remote [0], 9, 3);
		if ( strcmp("200", $responsecode) !=0){
		  	$ret .= ("Encounter errors <br/>" );

			$ret .=  "<pre>";
		  	$ret .= (htmlentities(implode($remote,"\n")));
			$ret .=  "</pre>";

			if ( strcmp("400", $responsecode) ==0){
				//$f = WebUtil::fopen_read($params_input[SparqlExplorer::WORK_URI_DATA]);
				//$data = stream_get_contents($f, 1000);
			  	//$ret .= $data;
				$ret .= sprintf("<iframe style=\"width:800; height:200\" src=\"%s\"></iframe>", $params_input[SparqlExplorer::WORK_URI_DATA]);
			}
		}else{

	  $data = file_get_contents($params_input[SparqlExplorer::WORK_URI_DATA]);
	  $json = json_decode ($data);


	  //print_r($json->results->bindings[0]->p);  
	  if(isset($json->results->bindings[0])){
		$params_input["map_ns_prefix"] =array(
			"http://xmlns.com/foaf/0.1/"=>"foaf", 
			"http://purl.org/twc/vocab/conversion/"=>"conversion",
			"http://rdfs.org/ns/void#"=>"void",
			"http://purl.org/dc/terms/"=>"dcterms",
			"http://www.w3.org/2002/07/owl#"=>"owl",
			"http://www.w3.org/1999/02/22-rdf-syntax-ns#"=>"rdf"
		);
		$params_input["prefixid"] =1;
	    
		
	    if ($visible){
	      $ret .= sprintf("subject: %s \n", SparqlExplorer::create_link($params_input[SparqlExplorer::INPUT_URI], null,  $params_input ) );
	      $ret .= "<table >\n";
	  if (SparqlExplorer::is_show_source($params_input)){
		      $ret .= "<tr><td>predicate</td><td>object</td><td>graph(source)</td></tr>\n";
	 }else{
		      $ret .= "<tr style=\"background:#DDD\"><td>predicate</td><td>object</td></tr>\n";
	}


		$p_prev=null;
		$o_prev=null;
		$row_cnt = 0;
	      foreach($json->results->bindings as $triple){
		$row_cnt++;
		if ($row_cnt%2==1){
			 $ret .= "<tr style=\"background:#EFE;\">\n";
		}else{
			 $ret .= "<tr style=\"background:#EEE;\">\n";
		}

		 $ret.="<td>";
               if ( strcmp( $triple->p->value, $p_prev)!=0){
			$p_prev = $triple->p->value;
			$o_prev = null;

			$qname_p = SparqlExplorer::get_qname($params_input, $triple->p->value);
			$ret .= SparqlExplorer::create_link ($triple->p->value, $qname_p,  $params_input ); 
		 }
		 $ret.="</td>\n";

		 $ret.="<td>";
  		 if ( strcmp( $triple->o->value, $o_prev)!=0){
			$o_prev = $triple->o->value;

			switch ($triple->o->type){
			case "uri":
			case "bnode":
				$ret .=SparqlExplorer::create_link ($triple->o->value, null,  $params_input ); 
				break;
			case "literal":
			case "typed-literal":
				$ret .= htmlentities($triple->o->value, ENT_QUOTES, "UTF-8"); 
			      break;
			default:
			 //print_r( $triple->o);
			}
		 }
		 $ret.="</td>\n";

		  if (SparqlExplorer::is_show_source($params_input)){
			 $ret.="<td>";
			 if (empty($triple->g->value))
				$ret .= "default graph"; 
			 else
				$ret .= SparqlExplorer::create_link ($triple->g->value, null,  $params_input ); 
			 $ret.="</td>\n";
		  }
		$ret .= "</tr>\n";
	      }
	      $ret .= "</table>\n";
	    }	
	
	
		$ns_prefix_map ="";
		foreach ($params_input["map_ns_prefix"] as $ns=>$prefix){
			$ns_prefix_map .= sprintf("  xmlns:%s = \"%s\"\n", $prefix, $ns); 
		}
	    
	
	    $ret .= sprintf ("<div about=\"%s\" %s>\n", $params_input[SparqlExplorer::INPUT_URI], $ns_prefix_map );
	    foreach($json->results->bindings as $triple){
		$qname_p = SparqlExplorer::get_qname($params_input, $triple->p->value);
		switch ($triple->o->type){
			case "uri":
				$ret .= sprintf("<div rel=\"%s\" resource=\"%s\" ></div>\n",$qname_p, $triple->o->value); 
				break;
			case "literal":
				$ret .= sprintf("<div  property=\"%s\" content=\"%s\"></div>\n",$qname_p, htmlentities($triple->o->value, ENT_QUOTES, "UTF-8")); 
				break;
			case "typed-literal":
				$ret .= sprintf("<div  property=\"%s\" datatype=\"%s\" content=\"%s\"></div>\n",$qname_p, $triple->o->datatype, htmlentities($triple->o->value, ENT_QUOTES, "UTF-8")); 
				break;
			default:
			 //print_r( $triple->o);
		}
	    }
	    $ret .= "</div>\n";
	  
	    }	

		}
	  	// add sparql query here
	    $ret .= "<div style=\"background:#FEE\">";
	    $ret .=sprintf("<a href=\"#sparql_query_description\" onclick=\"toggle_visibility('sparql_query_description');\">Show/Hide SPARQL query</a>");	
	    $ret .= "<a name=\"sparql_query_description\"></a>";
	    $ret .= sprintf("<div id=\"sparql_query_description\" style=\"display:none\">SPARQL Query\n<pre>%s</pre>\n</div>\n", htmlentities($params_desc["query"], ENT_QUOTES, "UTF-8"));
	    $ret .="</div>";
		    
	    $ret = "<div>\n$ret\n</div>\n";
	
	    if ($xhtml){
			$ret = '<?xml version="1.0" encoding="UTF-8"?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	  <title>RDFa+XHTML document</title>
	</head>
	<body>
	'.$ret. '
	</body>
	</html>';
	
	  }
	 return $ret;
	}
	

	static function  get_rdfa_data_o($params_input, $xhtml=false, $visible=true){
	    $params_desc = array();
	    $ret ="";
		  
		  if (SparqlExplorer::is_show_source($params_input)){
		      $params_desc["query"]= sprintf("
				SELECT distinct ?s ?p  ?g  
				WHERE{
				  { GRAPH ?g { ?s ?p <%s> . } }
				  UNION
				  { { ?s ?p <%s> . } }
				} order by ?s ?p limit %d
				",$params_input[SparqlExplorer::INPUT_URI]
				  ,$params_input[SparqlExplorer::INPUT_URI]

				 ,$params_input[SparqlExplorer::INPUT_SHOW_MAX_ROW]);
		  }else{
		      $params_desc["query"]= sprintf("
				SELECT distinct ?s ?p 
				WHERE{
				  { GRAPH ?g { ?s ?p <%s> . } }
				  UNION
				  { { ?s ?p <%s> . } }
				} order by ?s ?p limit %d
				",$params_input[SparqlExplorer::INPUT_URI]
				 ,$params_input[SparqlExplorer::INPUT_URI]
				,$params_input[SparqlExplorer::INPUT_SHOW_MAX_ROW]);
		  }			

		  $params_desc["service-uri"]=  $params_input[SparqlExplorer::INPUT_URI_SPARQL_ENDPOINT];		
		  $params_desc["output"]=  "sparqljson";		
		  $params_input[SparqlExplorer::WORK_URI_DATA]= WebUtil::build_restful_url( $params_input[SparqlExplorer::INPUT_URI_SPARQL_PROXY], $params_desc) ;

		  if (!empty($params_input[SparqlExplorer::INPUT_DEBUG])){
			print_r($params_input);
		   }


		$remote = get_headers($params_input[SparqlExplorer::WORK_URI_DATA]);
		$responsecode = substr($remote [0], 9, 3);
		if ( strcmp("200", $responsecode) !=0){
		  	$ret .= ("Encounter errors <br/>" );

			$ret .=  "<pre>";
		  	$ret .= (htmlentities(implode($remote,"\n")));
			$ret .=  "</pre>";

			if ( strcmp("400", $responsecode) ==0){
				//$f = WebUtil::fopen_read($params_input[SparqlExplorer::WORK_URI_DATA]);
				//$data = stream_get_contents($f, 1000);
			  	//$ret .= $data;
				$ret .= sprintf("<iframe style=\"width:800; height:200\" src=\"%s\"></iframe>", $params_input[SparqlExplorer::WORK_URI_DATA]);
			}
		}else{

	  $data = file_get_contents($params_input[SparqlExplorer::WORK_URI_DATA]);
	  $json = json_decode ($data);

	  //print_r($json->results->bindings[0]->p);  
	  if(isset($json->results->bindings[0])){
		$params_input["map_ns_prefix"] =array(
			"http://xmlns.com/foaf/0.1/"=>"foaf", 
			"http://purl.org/twc/vocab/conversion/"=>"conversion",
			"http://rdfs.org/ns/void#"=>"void",
			"http://purl.org/dc/terms/"=>"dcterms",
			"http://www.w3.org/2002/07/owl#"=>"owl",
			"http://www.w3.org/1999/02/22-rdf-syntax-ns#"=>"rdf"
		);
		$params_input["prefixid"] =1;
	    
		
	    if ($visible){
	      $ret .= sprintf("object: %s \n", SparqlExplorer::create_link($params_input[SparqlExplorer::INPUT_URI], null,  $params_input ) );
	      $ret .= "<table >\n";
	  if (SparqlExplorer::is_show_source($params_input)){
		      $ret .= "<tr><td>subject</td><td>predicate</td><td>graph(source)</td></tr>\n";
	 }else{
		      $ret .= "<tr style=\"background:#DDD\"><td>subject</td><td>predicate</td></tr>\n";
	 }


		$p_prev=null;
		$s_prev=null;
		$row_cnt = 0;
	      foreach($json->results->bindings as $triple){
		$row_cnt++;
		if ($row_cnt%2==1){
			 $ret .= "<tr style=\"background:#EFE;\">\n";
		}else{
			 $ret .= "<tr style=\"background:#EEE;\">\n";
		}

		 $ret.="<td>";
               if ( strcmp( $triple->s->value, $s_prev)!=0){
			$s_prev = $triple->s->value;
			$p_prev =null;

			$qname_s = SparqlExplorer::get_qname($params_input, $triple->s->value);
			$ret .= SparqlExplorer::create_link ($triple->s->value, $qname_s,  $params_input ); 
		 }
		 $ret.="</td>\n";


		 $ret.="<td>";
               if ( strcmp( $triple->p->value, $p_prev)!=0){
			$p_prev = $triple->p->value;

			$qname_p = SparqlExplorer::get_qname($params_input, $triple->p->value);
			$ret .= SparqlExplorer::create_link ($triple->p->value, $qname_p,  $params_input ); 
		 }
		 $ret.="</td>\n";

		  if (SparqlExplorer::is_show_source($params_input)){
			 $ret.="<td>";
			 if (empty($triple->g->value))
				$ret .= "default graph"; 
			 else
				$ret .= SparqlExplorer::create_link ($triple->g->value, null,  $params_input ); 
			 $ret.="</td>\n";
		  }
		$ret .= "</tr>\n";
	      }
	      $ret .= "</table>\n";
	    }	
	
	
		$ns_prefix_map ="";
		foreach ($params_input["map_ns_prefix"] as $ns=>$prefix){
			$ns_prefix_map .= sprintf("  xmlns:%s = \"%s\"\n", $prefix, $ns); 
		}
	
	
  	    $qname_o = SparqlExplorer::get_qname($params_input, $params_input[SparqlExplorer::INPUT_URI]);
	    foreach($json->results->bindings as $triple){
		$qname_p = SparqlExplorer::get_qname($params_input, $triple->p->value);
	       $ret .= sprintf ("<div about=\"%s\" %s>\n",  $triple->s->value, $ns_prefix_map );
		$ret .= sprintf("<div rel=\"%s\" resource=\"%s\" ></div>\n",$qname_p, $params_input[SparqlExplorer::INPUT_URI]); 
	       $ret .= "</div>\n";
	    }
	    

	    }	
		}
	  	// add sparql query here
	    $ret .= "<div style=\"background:#FEE\">";
	    $ret .=sprintf("<a href=\"#sparql_query_ref\" onclick=\"toggle_visibility('sparql_query_ref');\">Show/Hide SPARQL query</a>");	
	    $ret .= "<a name=\"sparql_query_ref\"></a>";
	    $ret .= sprintf("<div id=\"sparql_query_ref\" style=\"display:none\">SPARQL Query\n<pre>%s</pre>\n</div>\n", htmlentities($params_desc["query"], ENT_QUOTES, "UTF-8"));
	    $ret .="</div>";
	    
	    $ret = "<div>\n$ret\n</div>\n";
	
	    if ($xhtml){
			$ret = '<?xml version="1.0" encoding="UTF-8"?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	  <title>RDFa+XHTML document</title>
	</head>
	<body>
	'.$ret. '
	</body>
	</html>';	
	  }
	 return $ret;
	}

	
	private static function  get_qname(&$params_input, $uri){
		$index = strrpos($uri,"#");
		if (!$index){
			$index = strrpos($uri,"/");
		}
		$index++;
		$ns = substr($uri, 0, $index);
		$localname = substr($uri, $index);
		if (array_key_exists($ns,  $params_input["map_ns_prefix"])){
			$prefix = $params_input["map_ns_prefix"][$ns];
	
		}else{
			$prefix = "ns".$params_input["prefixid"];
			$params_input["map_ns_prefix"]["$ns"] = $prefix;
			$params_input["prefixid"] ++;
		}	
		return $prefix.":".$localname;
	
	}
	

	private static function report_error($level, $key, $message, $use_header = true){
		if (WebUtil::is_in_web_page_mode() && $use_header){
			header ("Content-Type: text/plain");
		}

		echo "[$level:$key] $message\n";
	}	

	// construct a hyper link

	private static function create_link($link, $text,  $params_input){
		//no anchor text, disply link itself
	   if (!$text)
	      $text = $link;
	      
	   //build local link
	   if (!empty($params_input)){
	   		$params[SparqlExplorer::INPUT_URI] = $link;
	   		$params[SparqlExplorer::INPUT_URI_SPARQL_ENDPOINT] = $params_input[SparqlExplorer::INPUT_URI_SPARQL_ENDPOINT];
	   		$params[SparqlExplorer::INPUT_URI_SPARQL_PROXY] = $params_input[SparqlExplorer::INPUT_URI_SPARQL_PROXY];
	   		$link = WebUtil::build_restful_url(SparqlExplorer::getFilename() , $params);
	   }
	   
	   return "<a href=\"$link\">$text</a>";
	}
	
	public static function show_html($params_input, $text){	

	
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title><?php echo SparqlExplorer::getTitle(); ?></title>
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
		<a class"=info" href="<?php echo SparqlExplorer::getHomepage(); ?>">
		<img src="http://lod-apps.googlecode.com/svn/trunk/doc/lod-apps-info.png" class="menuimg" alt="information"/></a>
	</div>
</td>
<td>
	<div style="margin-left:20px">
		<font size="200%"><strong> <?php echo SparqlExplorer::getTitle(); ?> </strong></font>	(version <?php echo SparqlExplorer::ME_VERSION; ?>)
		<br/>
		A RESTful web service that explores URI description in a given SPARQL Endpoint.
	</div>
</td>
</tr>
</table>

<div style="margin:10px">
<form method="get" action="<?php echo SparqlExplorer::getFilename(); ?>" border="1">

<fieldset>
<legend>options</legend>
URI: <input name="<?php echo SparqlExplorer::INPUT_URI; ?>" size="102" type="text" value="<?php echo $params_input[SparqlExplorer::INPUT_URI]; ?>" />   required, e.g. http://dbpedia.org/resource/Rensselaer_Polytechnic_Institute <br/>

URL of SPARQL Endpoint:<input name="<?php echo SparqlExplorer::INPUT_URI_SPARQL_ENDPOINT; ?>" size="102" type="text" value="<?php echo $params_input[SparqlExplorer::INPUT_URI_SPARQL_ENDPOINT]; ?>" />      required, e.g. http://dbpedia.org/sparql <br/>

URL of SparqlProxy:<input name="<?php echo SparqlExplorer::INPUT_URI_SPARQL_PROXY; ?>" size="102" type="text" value="<?php echo $params_input[SparqlExplorer::INPUT_URI_SPARQL_PROXY]; ?>" />      required <br/>

<input value="Explore"  type="submit"> <br/>

<a href="#" onclick="toggle_visibility('extra_params');">Show/Hide Experimental Parameters</a>

<!-- other options -->
<div id="extra_params" style="display:none">

show source column? <input name="<?php echo SparqlExplorer::INPUT_SHOW_SOURCE; ?>" type="checkbox"> <br/>

max distinct row to return:
	   <SELECT name="<?php echo SparqlExplorer::INPUT_SHOW_MAX_ROW; ?>">
		 <OPTION VALUE="100" >100 (default)</OPTION>
		 <OPTION VALUE="10" >10</OPTION>
		 <OPTION VALUE="1000" >1000</OPTION>
	   </SELECT> <br/>
</div>


</form>
</div>


<?php
if (!empty($params_input[SparqlExplorer::INPUT_URI])){
	echo $text;
}else{

?>
<div style="margin:10px">
<h2>Online Resources</h2>
<ul>
<li>A simple example here: <a href="<?php echo SparqlExplorer::getFilename(); ?>?uri=http%3A%2F%2Fdbpedia.org%2Fresource%2FRensselaer_Polytechnic_Institute&sparqlendpoint=http%3A%2F%2Fdbpedia.org%2Fsparql&sparqlproxy=http%3A%2F%2Flogd.tw.rpi.edu%2Fws%2Fsparqlproxy.php">Browse DBpedia's description about RPI</a></li>
<li>More information about this tool can be found at its <a href="<?php echo SparqlExplorer::getHomepage(); ?>">homepage</a> </li>
<li>Discuss this tool on twitter using <font color="green"><u>#<?php echo SparqlExplorer::ME_NAME; ?></u></font> , and check out <a href="http://twitter.com/#search?q=%23<?php echo SparqlExplorer::ME_NAME; ?>">related tweets</a> </li>
<li>Report issues/bugs/enhancement/comments at <a href="http://code.google.com/p/lod-apps/issues">here</a> </li>
</ul>
</div>

<?php

}
?>




</body>
</html><?php
	}
		
}
?>