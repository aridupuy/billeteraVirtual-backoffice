<?php

class Trait_validar_decimales {
    /**
     * 
     * @param type $numero
     * @return numero formateado en la forma [PARTE ENTERA],[PARTE DECIMAL] donde la parte decimal tiene dos digitos
     * @throws Exception si el numero ingresado no tiene el formato correcto
     */

	public static function validar_decimal($numero = null){
            $numero = trim($numero);
	    $validacion = substr($numero, 0,1);
		$validacion_2 = substr($numero, -3,1);
		$validacion_3 = substr($numero, -2,1);
 //var_dump($numero);
// print_r("<br>");
		if ($validacion!="," and $validacion!=".") {
			if ($validacion_2=="," || $validacion_2==".") {
				if ($validacion_3 !="," and $validacion_3 !=".") {
					if ($validacion_2==",") {
						$monto = str_replace("$","", trim($numero));
						$monto2 =  str_replace(",","",$monto);
						// var_dump($monto2);
						$monto3 =  str_replace(".","",$monto2);
						// var_dump($monto3);
						$monto4 = floatval($monto3);
						// var_dump($monto4);
						$monto_final  = $monto4/100;
						// var_dump($monto_final);
						$monto_final2  = number_format($monto_final,2);
						// var_dump($monto_final2);
						$monto_final3 = str_replace(",","",$monto_final2);

						// var_dump($monto_final3);
						// exit();
						// print_r($monto_final);
						return $monto_final3;
					}else if ($validacion_2==".") {
						$monto = str_replace("$","", trim($numero));
						// var_dump($monto);
						$monto2 =  str_replace(".","",$monto);
						// var_dump($monto2);
						$monto3 =  str_replace(",","",$monto2);
						// var_dump($monto3);
						$monto4 = floatval($monto3);
						// var_dump($monto4);
						$monto_final  = $monto4/100;
						// var_dump($monto_final);
						$monto_final2  = number_format($monto_final,2);
						// var_dump($monto_final2);
						$monto_final3 = str_replace(",","",$monto_final2);
						
						// exit();
						return $monto_final3;
					}
				}else{
					// print_r("DEBE INGRESAR UN DECIMAL DE DOS DIGITOS VALIDOS");
					throw new Exception('Debe ingresar un decimal con dos digitos validos (NNNN,DD).');
				}

			}else{

				throw new Exception('Debe tener un numero con 2 decimales validos. Vuelva a subir este archivo (NNNN,DD).');
			}
		}else{
			// print_r("DEBE TENER UN NUMERO ANTES DE LOS DECIMALES");
			throw new Exception('Debe tener un numero entero antes de los decimales (NNNN,DD).');

		}
	}
}
