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

define("ME_NAME", "phpRdfStream");
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
$code_dependency[] = "phpRdfNode.php";
foreach($code_dependency as $code){
	require_once ($code);
}



/********************************************************************************
 Section 4  Source code - Main Function
*********************************************************************************/

die();

class RdfStream
{
    const RDF_SYNTAX_RDFXML = "rdfxml";
    const RDF_SYNTAX_NT = "nt";
    const RDF_SYNTAX_NQUADS = "nquads";


    const NS_RDF = "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
    const NS_RDFS = "http://www.w3.org/2000/01/rdf-schema#";
    const NS_OWL = "http://www.w3.org/2002/07/owl#";
    const NS_XSD = "http://www.w3.org/2001/XMLSchema#";
    const NS_RSS = "http://purl.org/rss/1.0/";
    const NS_DC = "http://purl.org/dc/elements/1.1/";
    const NS_DCTERMS = "http://purl.org/dc/terms/";
    const NS_FOAF = "http://xmlns.com/foaf/0.1/";
    const NS_DGTWC = "http://data-gov.tw.rpi.edu/2009/data-gov-twc.rdf#";
    const NS_VOID = "http://rdfs.org/ns/void#";


	// input
   	var $map_ns_prefix = array();
   	var $map_ns_prefix_replace = array();
	var $output = RdfStream::RDF_SYNTAX_RDFXML;
	var $xmlbase = null;

	// run time memory
	var $number_of_triples = 0;
	var $uri_prev_subject = null;
	var $subject_id = 1;


	static public function test(){

	}


	// print function	
	private function println($str, $encode=false){
		if ($encode)
			$str =utf8_encode($str);
		echo $str . "\n";
	}


	public function create_subject($url_ns_res){
		$uri = sprintf("%sthing_%06d", $url_ns_res, $this->subject_id);
		$this->subject_id++;	
		
		return $uri;
	}

	static function get_default_map_ns_prefix(){
		$map_ns_prefix = array();
		$map_ns_prefix [RdfStream::NS_RDF]="rdf";
		$map_ns_prefix [RdfStream::NS_RDFS]="rdfs";
		$map_ns_prefix [RdfStream::NS_FOAF] ="foaf" ;
		$map_ns_prefix [RdfStream::NS_RSS]="rss";
		$map_ns_prefix [RdfStream::NS_DCTERMS]="dcterms";
		$map_ns_prefix [RdfStream::NS_DGTWC]="dgtwc";
		$map_ns_prefix [RdfStream::NS_VOID]="void";
		
		return $map_ns_prefix;
	}

	// begin triple generation
	public function begin($map_ns_prefix, $output=RdfStream::RDF_SYNTAX_RDFXML, $xmlbase=null){
		$this->map_ns_prefix = $map_ns_prefix;
		$this->output = $output;
		$this->xmlbase = $xmlbase;

		foreach ($map_ns_prefix as $ns=>$prefix){
			if (strlen($prefix)>0)
				$this->map_ns_prefix_replace[$ns] = $prefix.":";
			else
				$this->map_ns_prefix_replace[$ns] = $prefix;
		}

		switch($this->output){
			case RdfStream::RDF_SYNTAX_NQUADS: 
			case RdfStream::RDF_SYNTAX_NT: 
				header ("Content-Type: text/plain");
				break;
			case RdfStream::RDF_SYNTAX_RDFXML: 
			default:
				header ("Content-Type: application/rdf+xml");

				$this->println( "<?xml version=\"1.0\" ?>");
				$this->println( "<rdf:RDF ");
				
				if (!empty($this->xmlbase))
					$this->println( "  xml:base=\"$xmlbase\"");
					
				foreach($map_ns_prefix as $ns =>$prefix){
	
					if (strlen($prefix)>0){
						$this->println( "  xmlns:$prefix = \"$ns\" ");
					}else
						$this->println( "  xmlns = \"$ns\" ");
				}
				$this->println( ">");
				break;
		}
	}
	
	public function end(){
		switch($this->output){
			case RdfStream::RDF_SYNTAX_NQUADS: 
			case RdfStream::RDF_SYNTAX_NT: 
				break;
			case RdfStream::RDF_SYNTAX_RDFXML: 
			default:
				$this->rdfxml_desc_end();
				$this->println( "</rdf:RDF>");		
		}
	}
		
	
    public function add_triple($s, $p, $o){
		//skip triple with empty subject, predicate or object
		if (!isset($s)|| !isset($p)|| !isset($o))
			return;
	
		$this->number_of_triples++;
			
		switch($this->output){
			case RdfStream::RDF_SYNTAX_NT: 
				$this->nt_add_triple($s,$p,$o);
				break;
			case RdfStream::RDF_SYNTAX_NQUADS: 
				$this->nquads_add_triple($s,$p,$o, $this->xmlbase);
				break;
			case RdfStream::RDF_SYNTAX_RDFXML: 
			default:
				$this->rdfxml_add_triple($s,$p,$o);
				break;
		}
	}
	

	private function rdfxml_add_triple($s, $p, $o){
		//add rdf:description
		$this->rdfxml_desc_begin($s);
		
		$pv = $p->get_as_rdfxml($this->map_ns_prefix_replace);
		$ov = $o->get_as_rdfxml();
		
		//add attributes about the resource
		switch($o->type){
			case RdfNode::RDF_URI: 
				$this->println( "  <$pv rdf:resource=\"$ov\"/>");
				break;
			case RdfNode::RDF_STRING:
				$this->println( "  <$pv>$ov</$pv>");
				break;
			default: //typed literal
				$this->println( "  <$pv rdf:datatype=\"".$o->get_xsdtype_uri()."\">$ov</$pv>");
				break;
		}
	}
	
	private function rdfxml_desc_begin($s){
		if ($s != $this->uri_prev_subject){
			$sv = $s->get_as_rdfxml();
			if (strpos ($sv,"#")>0)
				$sv = str_replace($this->xmlbase, "",$sv);

			$this->rdfxml_desc_end();
			$this->println( "<rdf:Description rdf:about=\"$sv\">");
			$this->uri_prev_subject = $s;
		}
	}
	private function rdfxml_desc_end(){
		if (isset($this->uri_prev_subject))
			$this->println( "</rdf:Description>");	
	}
	
	private function nt_add_triple($s,$p,$o){
		$ret = sprintf ("%s %s %s .", $s->get_as_nt(), $p->get_as_nt(), $o->get_as_nt());
		$this->println( $ret );
	}		
	
	private function nquads_add_triple($s,$p,$o, $graph){
		$ret = sprintf ("%s %s %s <%s> .", $s->get_as_nt(), $p->get_as_nt(), $o->get_as_nt(), $graph);
		$this->println( $ret );
	}		
	
}
?>