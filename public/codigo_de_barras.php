<?php
error_reporting(E_ALL);
#################################################
############ CONFIGURACION ######################
if(!isset($_GET['codigo_de_barras'])) exit();
define('PATH_FONTS','../public/fonts/');
define('TAMANIO_BARCODE', 40);
define('PADDING',20);
#################################################
#################################################




$codigo_de_barras=$_GET['codigo_de_barras'];
$imagen=generar_imagen_barcode($codigo_de_barras);
ob_clean();
header('Content-Type: image/png');
imagepng($imagen);
imagedestroy($imagen);
exit();

function generar_imagen_barcode($barcode)
{
    $ANGULO=0;
    
    $TAMANIO_FUENTE=TAMANIO_BARCODE;
    $TAMANIO_FUENTE_NUMEROS=$TAMANIO_FUENTE*0.35;
    $ANCHO_DE_CARACTER=(32.6/91)*$TAMANIO_FUENTE;
    $RECORTE_INICIAL=(29/91)*$TAMANIO_FUENTE;
    $RECORTE_FINAL=$RECORTE_INICIAL*2;
    $POSICION_X=-$RECORTE_INICIAL+PADDING;
    $POSICION_Y=$TAMANIO_FUENTE+PADDING;
    
    $DIRECCION_FUENTE_CODEBAR=PATH_FONTS.'code128.ttf';
    $DIRECCION_FUENTE_MONOSPACE=PATH_FONTS.'monospaced_b.ttf';
    
    $TAMANIO_HORIZONTAL=ceil((strlen($barcode)+4)*$ANCHO_DE_CARACTER-$RECORTE_FINAL)+PADDING*2;
    $TAMANIO_VERTICAL=ceil($TAMANIO_FUENTE+$TAMANIO_FUENTE_NUMEROS)+PADDING*2;

    $POSICION_X_NUMEROS=($TAMANIO_HORIZONTAL-2*PADDING)*(1/38)+PADDING; 
    $POSICION_Y_NUMEROS=$POSICION_Y+$TAMANIO_FUENTE_NUMEROS; 
    $POSICION_X1_RECTANGULO=$TAMANIO_HORIZONTAL*(3/8);
    $POSICION_X2_RECTANGULO= $TAMANIO_HORIZONTAL *(5/8);
    $POSICION_Y1_RECTANGULO=$TAMANIO_VERTICAL*(4/6);
    $POSICION_Y2_RECTANGULO=$TAMANIO_VERTICAL*(1/6);
    $TAMANIO_FUENTE_LETRAS=($POSICION_X2_RECTANGULO-$POSICION_X1_RECTANGULO)*(2/16);
    $imagen=  imagecreate($TAMANIO_HORIZONTAL, $TAMANIO_VERTICAL);
    $COLOR_DE_TEXTO=imagecolorallocate($imagen, 0, 0, 0);
    $COLOR_DE_FONDO=imagecolorallocate($imagen, 255, 255, 255);
    imagefill($imagen, 0, 0, $COLOR_DE_FONDO);

    imagecolorallocate($imagen, 255,255,255);  
    imagettftext($imagen,$TAMANIO_FUENTE,$ANGULO,$POSICION_X,$POSICION_Y, $COLOR_DE_TEXTO,$DIRECCION_FUENTE_CODEBAR ,obtener_texto($barcode));
    putenv('GDFONTPATH='.realpath('.'));
    imagettftext($imagen,$TAMANIO_FUENTE_NUMEROS,0,$POSICION_X_NUMEROS ,$POSICION_Y_NUMEROS,  $COLOR_DE_TEXTO,$DIRECCION_FUENTE_MONOSPACE ,separar_barcode($barcode));    
    if(!validar_codigo_de_barras($barcode)){
        imagefilledrectangle($imagen,  $POSICION_X1_RECTANGULO, $POSICION_Y1_RECTANGULO,$POSICION_X2_RECTANGULO, $POSICION_Y2_RECTANGULO, imagecolorallocatealpha($imagen, 240, 2, 0,50));
        imagettftext($imagen,$TAMANIO_FUENTE_LETRAS,0,$POSICION_X2_RECTANGULO*(4.6/7),($POSICION_Y1_RECTANGULO)*(3/4),  $COLOR_DE_TEXTO,$DIRECCION_FUENTE_MONOSPACE ,"Inválido");
                   
    }
    return $imagen;
}
function separar_barcode($barcode)
{
        
    $string= substr($barcode, 0,3)."  ";
    $string.=substr($barcode, 3,4)."  ";
    $string.=substr($barcode, 7,4)."  ";
    $string.=substr($barcode, 11,4)."  ";
    $string.=substr($barcode, 15,6)."  ";
    $string.=substr($barcode, 21,7)."  ";
    $string.=substr($barcode, 28)."  ";
    return $string;
}
function validar_codigo_de_barras($barcode)
{
	$ultimo_digito=substr($barcode, -1);
	$barcode=substr($barcode, 0, -1);
	return (calcular_digito_verificador($barcode)==$ultimo_digito AND $ultimo_digito!==false);
}
function calcular_digito_verificador($barcode)
{
    if(strlen($barcode)!=28) return false;
    $total=0;
    $strlen=strlen($barcode);
    for ($i=1; $i <= $strlen; $i++) { 
        $total=$total+$barcode[$i-1]*3+$barcode[$i];
        $i++;
    }
    $resto=$total % 10;
    if(!$resto) return 0;
    return 10-$resto;
}
function obtener_texto($data){
    $start=chr(204);
    $end=chr(206);
    $suma=104;
    $aux=str_split($data);
    $longitud=count($aux);
    for ($i=0; $i < $longitud; $i++) {
        $suma+=(ord($aux[$i])-32)*($i+1);
    }
    $temp=$suma%103;
    if($temp>=95){
        $temp+=68;
    }
    $checksum=chr($temp+32);
    error_log($start.$data.$checksum.$end);
    return $start.$data.$checksum.$end;
    
}
 function getNumCode($index,$barcode) {
        $retval = $barcode[$index];
        return $retval;
    }
