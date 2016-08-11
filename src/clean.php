<?php
require_once(dirname(dirname(__FILE__))."/forceutf8/src/ForceUTF8/Encoding.php");

use \ForceUTF8\Encoding; // https://github.com/neitanod/forceutf8


ini_set("default_charset", 'utf-8');

$dir = 'html';

foreach (scandir($dir) as $f) if (substr($f,-5,5)=='.html') {
	echo "\n- $f";
	$htm2 = superClen("$dir/$f",false);
	file_put_contents("html_clean/$f",$htm2);
}


// // // // // // // // //

function superClen($file,$forceUTF8=false) {
	$htm = file_get_contents($file);
	$htm = XMLpretty($htm); // do pretty and clean
	$htm = preg_replace('/\s*&#13;\s*/us',"\n",$htm);

	$tidy = new tidy();  // nao presta mas foi necessário
	$clean = $tidy->repairString($htm, array(
		'output-bom'	=>false,  // utf8 padrao nao usa
		'output-xhtml'	 => true,
		'hide-comments'  => true,
		'indent'         => true,
		'output-html'    => true,
		'wrap'           => 900,
		'show-body-only' => true,
		'clean'          => true,

		'input-encoding' => 'utf8',
		'output-encoding'  => 'utf8',
		'logical-emphasis' => false,
		'bare'           => true,
	));

	$clean = preg_replace(
		'/<table\s[^><]*topo_materia[^><]*>/si', 
		"\n###topo_materia###\n", 
		$clean
	);

	$clean = strip_tags($clean, '<html><p><b><i><u><br>');
	$clean = preg_replace('/^.+?###topo_materia###\s*/s', '', $clean);
	$clean = preg_replace('/\n\s*\n\s*/s', "\n\n", $clean);
	$clean = preg_replace('/\s*imprimir\s*$/si', "\n", $clean);

	return $forceUTF8? Encoding::toUTF8($clean): $clean;  // nao deveria mas foi necessário
}

function x2dom($xml) {
	$dom = new DOMDocument('1.0', 'UTF-8');
	$dom->formatOutput = true;
	$dom->preserveWhiteSpace = false;	
	$dom->resolveExternals = false; // external entities from a (HTML) doctype declaration
	$dom->recover = true; // Libxml2 proprietary behaviour. Enables recovery mode, i.e. trying to parse non-well formed documents
	$ok = @$dom->loadHTML($xml, LIBXML_NOENT | LIBXML_NOCDATA);
	if ($ok) return $dom; else return NULL;
}

function XMLpretty($xml) {
	$dom = x2dom($xml);
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	return $dom->saveXML($dom->documentElement);
}

