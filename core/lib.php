<?php

# Funciones de uso general de no mas de 3 lineas

function developer_log($string)
{
	$fecha=new DateTime("now");
	if(is_array($string)) $string=json_encode($string);
	if(DEVELOPER AND ACTIVAR_LOG_APACHE) {
		$string=quitar_acentos(quitar_saltos_de_linea($string));
		$nombre='';
		if(Application::$usuario){
			$nombre=Application::$usuario->getName().' | ';
		}
		error_log($fecha->format("Y-m-d H:i:s.u")." | ".$nombre.$string);
	}
//        var_dump(DEVELOPER);
//        var_dump($GLOBALS['ACTIVAR_LOG_NAVEGADOR']!="0");
//        var_dump(!Application::$no_log_navegador);
//        exit();
	if(DEVELOPER AND $GLOBALS['ACTIVAR_LOG_NAVEGADOR']!="0" and !Application::$no_log_navegador){
            
            echo "<div class='debug'>".$string."</div>";
        }
        
	if(DEVELOPER AND ACTIVAR_LOG_CONSOLA_NAVEGADOR and $GLOBALS['ACTIVAR_LOG_NAVEGADOR']!="0") return log_consola_navegador($string);
}

function log_consola_navegador($string)
{
    if(is_array($string) || is_object($string))
		echo("<script>console.log('PHP: ".json_encode($string)."');</script>");
	else {
		$string=str_replace("'", "-", $string);
		echo("<script>console.log('PHP: ".$string."');</script>");
	}
}

function comparar_cadenas($string1, $string2)
{

	return strcasecmp(trim($string1),trim($string2));
}

function formato_plata($float)
{

	return number_format($float,2,',','.');
}

function formato_fecha($string)
{

        return date('d/m/y',strtotime($string));
}

function llave_aleatoria()
{

	return strtoupper(substr( "ABCDEFGHIJKLMNOPQRSTUVWXYZ" ,mt_rand( 0 ,50 ) ,1 ) .substr( md5( time() ), 1));
}

function letras_aleatorias($numerodeletras)
{
	$caracteres = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$cadena = "";
	for($i=0;$i<$numerodeletras;$i++)
	    $cadena .= substr($caracteres,rand(0,strlen($caracteres)-1),1);
	return $cadena;
}

function numeros_aleatorios($numerodeletras)
{
	$caracteres = "1234567890";
	$cadena = "";
	for($i=0;$i<$numerodeletras;$i++)
	    $cadena .= substr($caracteres,rand(0,strlen($caracteres)-1),1);
	return $cadena;
}
function quitar_acentos($string)
{    

    return utf8_encode(strtr(utf8_decode($string), utf8_decode("ÀÁÂÄÅàáâäÒÓÔÖòóôöÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ°"), "AAAAAaaaaOOOOooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn "));
}    

function quitar_saltos_de_linea($string)
{

	return trim(preg_replace('/\s\s+/', ' ', $string));
}

function validar_fecha($date)
{
    $date = date_parse($date); 
    if ($date["error_count"] == 0 && checkdate($date["month"], $date["day"], $date["year"])) 
        return true;
    return false;
}

function convertir_microtime_a_datetime($microtime)
{
	
//	date_default_timezone_set('utc');	
	# Solo debe recibir microtime(true)
	if(count(explode('.', $microtime))!=2)
		return false;
	list($hour_min_sec, $usec) = explode('.', $microtime);
	$hour_min=floor($hour_min_sec/60);
	$hour=floor($hour_min/60);
	$hour=str_pad($hour, 2,'0',STR_PAD_LEFT);
	$min=$hour_min % 60;
	$min=str_pad($min, 2,'0',STR_PAD_LEFT);
	$sec=$hour_min_sec % 60;
	$sec=str_pad($sec, 2,'0',STR_PAD_LEFT);
	$usec=substr($usec, 0,6);		
	$duracion=$hour.':'.$min.':'.$sec.'.'.$usec;
	if($duracion=Datetime::createFromFormat('H:i:s.u', $duracion))
		return $duracion;
	return false;
}

# Dado un string con palabras, las separa considerando comillas
function separar_palabras($string)
{
	# Sacar espacios dobles
	$comilla_simple="'";
	$comilla_doble="\"";
	$espacio=" ";
	$espacio_doble="  ";
	$string=trim($string);
	while(strpos($string,$espacio_doble)!==false){
		$string=str_replace($espacio_doble,$espacio, $string);
	}
	while(strpos($string,$comilla_doble)!==false){
		$string=str_replace($comilla_doble,$comilla_simple, $string);
	}
	$longitud=strlen($string);
	
	$palabras=array();
	$palabra='';
	for ($i=0; $i < $longitud; $i++) { 
		$letra=$string[$i];
		if($letra==$comilla_simple){
			$palabra='';
			$i++;
			while($string[$i]!=$comilla_simple AND $i<$longitud){
				$palabra.=$string[$i];
				$i++;
			}
			$i++;
			$palabras[]=$palabra;
			$palabra='';
		}
		elseif($letra==$espacio OR $i==$longitud-1)
		{
			if($letra!=$comilla_simple)
				$palabra.=$letra;
			$palabras[]=$palabra;
			$palabra='';
		}
		else{
			$palabra.=$letra;
		}
	}

	return $palabras;
}

# Recibe un importe leido con el Gestor_de_disco->importar_xls()
function convertir_importe_excel($importe)
{
	$importe=str_replace('$', '', $importe);
    return str_replace(',','',$importe);
}

# Guarda un importe para ser grabado en Gestor_de_disco->exportar_xls()
function revertir_importe_excel($importe)
{

    return number_format(convertir_importe_excel($importe), 2, '.',',');
}

function convertir_bytes($bytes,$precision)
{
    $unidades = array('b', 'Kb', 'Mb', 'Gb');
    $base=log($bytes)/log(1024);
    return round(pow(1024, $base - floor($base)), $precision)." ".$unidades[floor($base)];
}
function presentar_fecha(Datetime $fecha, $horas_relativas=4, $dias_relativos=7)
{
	$ahora=new Datetime('now');
	$intervalo = $fecha->diff($ahora);
	if($fecha<$ahora){
		# Fechas pasadas
		if($fecha->format('Y')==$ahora->format('Y')){
			# Mismo anio
			if($fecha->format('m')==$ahora->format('m')){
				# Mismo mes
				if($fecha->format('d')==$ahora->format('d')){
					# Mismo dia
					if($fecha->format('H')==$ahora->format('H')){
						# Misma hora
						if($fecha->format('i')==$ahora->format('i')){
							# Mismos minutos
							$segundos=$intervalo->format('%s');
							if($segundos==1){
								$mensaje='Hace '.$segundos.' segundo';
							}
							else $mensaje='Hace '.$segundos.' segundos';
						}
						else{
							# Diferentes minutos
							$minutos=$intervalo->format('%i');
							if($minutos==0){
								$segundos=$intervalo->format('%s');
								$mensaje='Hace '.$segundos.' segundos';
							}
							elseif($minutos==1){
								$mensaje='Hace '.$minutos.' minuto';
							}
							else $mensaje='Hace '.$minutos.' minutos';
						}
					}
					else{
						# Diferente hora
						$horas=$intervalo->format('%h');
						if($horas==0){
							$minutos=$intervalo->format('%i');
							$mensaje='Hace '.$minutos.' minutos';
						}
						elseif($horas==1){
							$mensaje='Hace '.$horas.' hora';
						}
						elseif($horas<$horas_relativas)
							$mensaje='Hace '.$horas.' horas';
						else $mensaje='Hoy a las '.$fecha->format('H:i');
					}
				}
				else{
					# Diferente dia
					$dias=$intervalo->format('%d');
					if($dias==0){
						$horas=$intervalo->format('%h');
						if($horas<$horas_relativas)
							$mensaje='Hace '.$horas.' horas';
						else $mensaje='Ayer a las '.$fecha->format('H:i');
					}
					elseif($dias<$dias_relativos)
						$mensaje='Hace '.$dias.' días';
					else $mensaje=$fecha->format('j \d\e M');
				}
			}
			else{
				# Diferente mes
				$mensaje=$fecha->format('j \d\e M');
			}
		}
		else{
			# Diferente anio
			$mensaje=$fecha->format('j \d\e M \d\e Y');
		}
	}
	else {
		# Fechas futuras
		$mensaje=$fecha->format('j \d\e M \d\e Y');
	}
	$mensaje=str_replace('Jan','Ene',$mensaje);
	$mensaje=str_replace('Apr','Abr',$mensaje);
	$mensaje=str_replace('Aug','Ago',$mensaje);
	$mensaje=str_replace('Dec','Dic',$mensaje);
	return $mensaje;
}

function es_archivo_de_sistema($nombre_archivo)
{
	$nombre_archivo=explode('.',$nombre_archivo);
	if(strtolower('.'.$nombre_archivo[count($nombre_archivo)-1])==EXTENSION_ARCHIVO_DE_SISTEMA)
		return true;
	return false;
}

function convertir_mime($mime)
{
    switch ($mime) {
        case 'application/zip':
        case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
            $return='Hoja de cálculo'; break;
        case 'application/pdf':
        	$return='Documento PDF'; break;
        case 'text/plain':
            $return='Archivo de texto'; break;
        default:
            $return='Archivo'; break;
    }
    return $return;
}

function validar_cbu($cbu)
{
    $cbu=trim($cbu);
    if(strlen($cbu)!=22)
            return false;
    $cbu="$cbu";
    $pontificador = array(7, 1, 3);
    $banco = 0;
    $suc = 0;
    $cuenta = 0;
    $acumulador = 0;
    $fallo=false;
    for ($j=0;$j<strlen($cbu);$j++){
        if(!preg_match("{[0-9]}", $cbu{$j})){
            return false;
        }
    }
    /*Bloque 1*/
    for ($i = 0; $i < 3; $i++) {
        $banco=$banco+(intval($cbu{$i})*$pontificador[$i]);
    }
    $digito_verificador1 = $cbu{3};
    $acumulador = intval($banco) + (intval($digito_verificador1) * 9);
    
    for ($i = 4; $i < 7; $i++) {
        $suc+=intval($cbu{$i})*$pontificador[$i-4];
    }
    $acumulador = $acumulador + $suc;
    $acumulador="$acumulador";
    $ultimo_digito=$acumulador{strlen($acumulador)-1};
    if(intval($ultimo_digito)===0){
        $diferencia=0;
    }
    else $diferencia = 10 - intval($ultimo_digito);
    $digito_verificador2 = $cbu{7};
    #developer_log('Primer Bloque');
    #developer_log('La diferencia es: '.$diferencia);
    #developer_log('El digito verificador es: '.$digito_verificador2);
    if ($diferencia != $digito_verificador2) {
        $fallo=true;
    }
    $digito_verificador3 = $cbu{21};
    /*Bloque 2*/
    $pontificador=array(3,9,7,1,3,9,7,1,3,9,7,1,3);
    for ($i = 8; $i < 21; $i++) {
       $cuenta+=intval($cbu{$i}) * $pontificador[$i-8];
    }
    $string_aux="$cuenta";
    $ultimo_digito=$string_aux{strlen($string_aux)-1};
    if(intval($ultimo_digito)===0){
        $diferencia=0;
    }
    else $diferencia=10 - intval($ultimo_digito);

    #developer_log('Segundo Bloque');
    #developer_log('La diferencia es: '.$diferencia);
    #developer_log('El digito verificador es: '.$digito_verificador3);
    if($diferencia!=$digito_verificador3){
        $fallo=true;
        $mensaje="El Cbu no es valido. El numero de cuenta no es valido(Segundo Bloque).";
    }
    if($fallo){
        return false;
    
    }
    else{
        return true;
    }
}
function validar_tco($tco)
{
	# Preg match no funcioan. Mejorar.
	if(is_numeric($tco)){
		if((strlen($tco)==16 OR strlen($tco)==15) OR strlen($tco)==14){
			return true;
		}
	}
	return false;
	#return preg_match('/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$/', $tco);
}
function truncar_tco($tco){
	# No valida el tco
	$truncado=substr($tco, 0,4).' XXXX XXXX '.substr($tco, -4,4);
	return $truncado;
}
function validar_cuit( $cuit )
{
	$cuit = preg_replace( '/[^\d]/', '', (string) $cuit );
	if( strlen( $cuit ) != 11 ){
		return false;
	}
	$acumulado = 0;
	$digitos = str_split( $cuit );
	$digito = array_pop( $digitos );

	for( $i = 0; $i < count( $digitos ); $i++ ){
		$acumulado += $digitos[ 9 - $i ] * ( 2 + ( $i % 6 ) );
	}
	$verif = 11 - ( $acumulado % 11 );
	$verif = $verif == 11? 0 : $verif;
	$verif = $verif == 10? 9 : $verif;

	return $digito == $verif;
}
function formatear_cuit($cuit){
    $primero= substr($cuit, 0,2);
    $medio=substr($cuit, 2, -1);
    $ultimo=substr($cuit,strlen($cuit)-1,1);
    $cuit=$primero."-".$medio."-".$ultimo;
    return $cuit;
}
function validar_correo($correo)
{
	if(filter_var($correo, FILTER_VALIDATE_EMAIL)===false)
		return false;
	return true;
}
function validar_dni($dni)
{
	if(is_numeric($dni)){
			return true;
	}
	return false;
}
function validar_mercalpha($mercalpha)
{   
    return preg_match("^[a-zA-Z]{2}[0-9]{6}$^", $mercalpha);
}
function comparar_cadenas_relativas($uno, $dos, $porcentaje)
{
 	if((!is_numeric($porcentaje) OR $porcentaje>100) OR $porcentaje<0)
 		return false;
	$uno=preparar_cadena_relativa($uno);
	$dos=preparar_cadena_relativa($dos);
	$longitud_uno=strlen($uno);
	$longitud_porcentual_uno=floor($porcentaje/100*$longitud_uno);
	for ($i=0; $i <= $longitud_uno-$longitud_porcentual_uno; $i++) { 
		$aguja=substr($uno, $i,$longitud_porcentual_uno);
		if(strrpos($dos, $aguja)!==false){
			return true;
		}
	}
	return false;
}
# Usada en comparar_cadenas_relativas()
function preparar_cadena_relativa($string)
{
	$string=strtolower(trim($string));
	$string=quitar_acentos($string);
	$string=str_replace('ñ', 'n', $string);
	$espacio=" ";
	$espacio_doble="  ";
	while(strpos($string,$espacio_doble)!==false){
		$string=str_replace($espacio_doble,$espacio, $string);
	}
	return $string;
}
function interpretar_fecha_relativa($wildcard)
{
	$cantidad=substr($wildcard, 0,-1);
	$tipo=strtolower(substr($wildcard, -1));
	if(!is_numeric($cantidad)) return false;
	switch ($tipo) {
		case 'h':
                        $habiles=true;
                        $hoy=new Datetime('now');
                        $nueva_fecha=Calendar::siguiente_dia_habil($hoy, $cantidad);
			break;
		case 'd': 
		case 'c':
			$intervalo='P'.$cantidad.'D';
			break;
		case 's': 
			$intervalo='P'.(7*$cantidad).'D';
			break;
		case 'm': 
			$intervalo='P'.$cantidad.'M';
			break;
		default:
			return false;
			break;
	}
        if(!isset($habiles) OR !$habiles){
            $hoy=new Datetime('now');
            $intervalo=new DateInterval($intervalo);
            $nueva_fecha=$hoy->add($intervalo);
        }
	return $nueva_fecha;
}
function enmascarar_cbu($cbu){
	if(validar_cbu($cbu)){
		$enmascarado=substr($cbu, 0,3);
		$enmascarado.='X XXXX XXXX XXXX';
		$enmascarado.=substr($cbu, -4,4);
		return $enmascarado;
	}
	return false;
}
function str_pad_utf8($texto,$longitud,$relleno=null,$alineacion=null){
    if($texto==null){
        $texto="";
    }
    if($longitud==null)
        return $texto;
    $texto= utf8_decode($texto);
    $texto= str_pad($texto, $longitud,$relleno,$alineacion);
    $texto=  utf8_encode($texto);
    return $texto;
}
function es_vector_asociativo($array)
{	
    return array_keys($array) !== range(0, count($array) - 1);
}
function password_aleatorio($palabras,$separador='.'){
    $array_palabras=array('Alfa',
                    'Bravo',
                    'Charlie',
                    'Delta',
                    'Echo',
                    'Foxtrot',
                    'Golf',
                    'Hotel',
                    'India',
                    'Juliett',
                    'Kilo',
                    'Lima',
                    'Mike',
                    'November',
                    'Oscar',
                    'Papa',
                    'Quebec',
                    'Romeo',
                    'Sierra',
                    'Tango',
                    'Uniform',
                    'Victor',
                    'Whiskey',
                    'Xray',
                    'Yankee',
                    'Zulu');
    $password="";
    for ($i=0;$i<$palabras;$i++){
        $aleatorio=rand(0, 25);
        $password.= strtolower($array_palabras[$aleatorio].$separador);
    }
    $password= substr($password,0,-1);
    return $password;
}
function obtener_variables_de_filtro($model,$json=false){
	if (session_status() != PHP_SESSION_ACTIVE) {
        	session_start();
    	}
    if($json==false and isset($_SESSION[Application::$usuario->get_id().$model."vars"]))
        $json=$_SESSION[Application::$usuario->get_id().$model."vars"];
	
    return json_decode($json,true);
}
function guardar_variables_filtro($model,$variables){
	if (session_status() != PHP_SESSION_ACTIVE) {
        	session_start();
    	}
	if(empty($variables))
          session_destroy();
        else 
          $_SESSION[Application::$usuario->get_id().$model."vars"]= json_encode($variables);
}
function crear_titulo_controller(View $view,$text,$vista_varios=null){
    $header=$view->createElement("div");
    $header->setAttribute("class", "header col xs-12");
    $div=$view->createElement("div");
    $div->setAttribute("class", "row flex-middle");
    $h1=$view->createElement("h1",$text);
    $h1->setAttribute("class", "text-light text-uppercase");
    $div->appendChild($h1);
    $div2=$view->createElement("div");
    $div2->setAttribute("class", "row");
    $div3=$view->createElement("div");
    $div3->setAttribute("class", "col xs-12 xs-offset-7 right");
    if($vista_varios!=null)
        foreach ($vista_varios as $varios){
            $div3->appendChild($varios);
        }
    $div2->appendChild($div3);
    $div->appendChild($div2);
    $header->appendChild($div);
    $view->appendChild($header);
    return $header;
}
function validar_barcode($barcode){
    $digito= substr($barcode,-1);
    $barcode_1= substr($barcode, 0, strlen($barcode)-1);
    $digito_val=Barcode::calcular_digito_verificador($barcode_1);
    if($digito_val==$digito )
        return true;
    return false;
}

function numeric_comma($number){
    $valid_chars = '0123456789,.';
    //$b_decimal = false;
    $start = 0;
    
    if(substr($number, $start, 1) == '-')
        $start = 1;
    
    if(strlen($number) == 0)
        return false;
    
    for($i = $start; $i<strlen($number);$i++){
        $char = substr($number, $i, 1);
        if(strpos($valid_chars, $char)=== false){
            return false;
        }
    }
    return true;
}

function basic_num_order($numero){
    $numero = str_replace('.', '', $numero);
    $numero = str_replace(',', '', $numero);
    return $numero;
}
