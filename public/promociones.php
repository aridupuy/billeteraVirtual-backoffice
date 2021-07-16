<?php

$url='https://www.mercadopago.com.ar/cuotas';
$elemento_buscado='ul';
$clase_buscada='bank-description__list';
$link_favicon='';

$pagina= file_get_contents($url);
if($pagina===false) exit();
$dom=new DOMDocument('1.0', 'utf-8');
@$dom->loadHTML($pagina);
$mains=$dom->getElementsByTagName($elemento_buscado);
$head=$dom->getElementsByTagName('head')->item(0);
$links=$head->getElementsByTagName('link');
$divs=$dom->getElementsByTagName("div");

foreach ($links as $link) {
	if($link->hasAttribute('rel') AND $link->getAttribute('rel')=='shortcut icon')
		$link->setAttribute('href','');
}
foreach($divs as $div){
 if($div->hasAttribute('class') AND (( $div->getAttribute('class')=='bp-header mla') OR ($div->getAttribute('class')=='container-cobranded'))){

		$div->parentNode->removeChild($div);
		
	}
//   var_dump($div->getAttribute("class"));
 }
if($mains->length>0){
	foreach ($mains as $temp) {
		if($temp->hasAttribute('class') AND $temp->getAttribute('class')==$clase_buscada)
			$main=$temp;
	}
	if(isset($main)){
		$dom_resultado=new DOMDocument('1.0', 'utf-8');
		$dom_resultado->appendChild($dom_resultado->importNode($head,true));
		$dom_resultado->appendChild($dom_resultado->importNode($main,true));
		echo $dom_resultado->saveHTML();
	}
}
$divs=$dom->getElementsByTagName("div");
$divs->item(0)->parentNode->removeChild($divs->item(0));
exit();
