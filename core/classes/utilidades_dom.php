<?php

/**
 * Funciones utiles para trabajar con las vistas y el DOM
 *
 * @author juampa (jpalegria@cobrodigital.com)
 * @copyright Diciembre 2019.
 */
class Utilidades_dom {

    public function __construct() {
        
    }

    /**
     * Generar un mensaje de alerta desde un Node insertado en la vista del DOM.
     * La vista debe tener el siguiente nodo y clases:
     * 
     * DOMNode: <span id="mensaje" class=""></span>
     * 
     * Clases/Alias CSS Propuestas:
     * 
     * .alerta-error{
     *       padding: 10px;
     *      background-color: #f8d7da;
     *       border-radius: 5px;
     *       color: #721c24;
     *       border-color: #f5c6cb;
     *   }
     * 
     *  .alerta-correcto{
     *       padding: 10px;
     *       border-radius: 5px;
     *       color: #155724;
     *       background-color: #d4edda;
     *       border-color: #c3e6cb;
     *   }
     *
     *   .alerta-aviso{
     *       padding: 10px;
     *       border-radius: 5px;
     *       color: #856404;
     *       background-color: #fff3cd;
     *       border-color: #ffeeba;
     *   }
     * 
     * En caso de no querer agregar las clases al HTML, utilizar el alias del estilo. En caso de no haber coincidencia se agrega como estilo al objeto DOM.
     * 
     * @param string $texto Mensaje a mostrar.
     * @param DOMDocument $vista Vista del DOM para obtener los DOMNode.
     * @param string $estilo Estilo a aplicar al elemento. Se pueden utilizar los alias de estilos propuestos: 'alerta-error', 'alerta-correcto', 'alerta-aviso'.
     * @param string $clase Clase CSS especifica en la vista para formatear el mensaje o que necesite el elemento.
     *
     * @return DOMNode
     */
    public function crearAlerta($texto, &$vista, $estilo = NULL, $clase = NULL){
        $txt = $vista->createTextNode($texto);               
        $mensaje = $vista->getElementById("mensaje");
        $estilo = ($estilo === NULL) ? "" : $estilo; //Compatibilidad con PHP5.6
        $mensaje->setAttribute('class', $clase);
        $clase = ($clase === NULL) ? "" : $clase; //Compatibilidad con PHP5.6
        $mensaje->setAttribute('style', $this->obtenerAlertaCSS($estilo));
        $mensaje->appendChild($txt);
    }
    
    private function obtenerAlertaCSS($estiloNombre) {
        switch ($estiloNombre) {
            case 'alerta-error':
                $estilo = 'padding: 10px; background-color: #f8d7da; border-radius: 5px; color: #721c24; border-color: #f5c6cb; margin: 25px;';
                break;
            
            case 'alerta-correcto':
                $estilo = 'padding: 10px; border-radius: 5px; color: #155724; background-color: #d4edda; border-color: #c3e6cb; margin: 25px;';
                break;
            
            case 'alerta-aviso':
                $estilo = 'padding: 10px; border-radius: 5px; color: #856404; background-color: #fff3cd; border-color: #ffeeba; margin: 25px;';
                break;                

            default:
                $estilo = $estiloNombre;
                break;
        }
        
        return $estilo;
    }
    
    /**
     * Retorna un DOM-Element que representa un botón.
     * 
     * @param string $name El nombre del módulo y la accion que apunta del mismo.
     * @param string $value El nombre que va a tener asignado el botón.
     * @param string $style Un string con valores del atributo style igual a un Inline CSS.
     * @return DOMElement Retorna un DOMElemente para agregar a la vista. En caso de error retorna FALSE.
     */
    public function crearBoton($name, $value, $style = NULL) {
        $btn = false;
        $style = ($style === NULL) ? "" : $style; //Compatibilidad con PHP5.6

        if (!empty($name) && !empty($value) && is_string($name) && is_string($value)) {
            $btn = $this->view->createElement('input');
            $btn->setAttribute('type', 'button');
            $btn->setAttribute('name', $name);
            $btn->setAttribute('value', $value);
            $default_style = "color: #3c3c3c; border-radius: 3px; margin: 3px;";
            $estilo = (!empty($style)) ? $style . $default_style : $default_style;
            $btn->setAttribute('style', $estilo);
        }

        return $btn;
    }
    
    /**
     * Crea un objeto div del DOM con un Titulo
     * 
     * @param string $nombre Titulo de la seccion
     * @return DOMNode Retorna un <div></div> con el titulo
     */
    public function crearTitulo($nombre = "TITULO_DEFAULT", &$vista) {
        try{
            $titulo = false;
            if (!empty($nombre) AND is_string($nombre) AND is_a($vista, 'View')) {
                $titulo = $vista->createElement('div');
                $titulo->setAttribute('class', 'text-light text-uppercase');
                $titulo->appendChild($vista->createTextNode($nombre));
            }else{
                throw new Exception("Archivo utilidades_dom.php > Funcion crearTitulo() > No se validaron correctamente los argumentos de entrada.");
            }

            return $titulo;
            
        } catch (Exception $ex){
            return $ex;
        }
    }

    /**
     * Registra en un archivo .log los mensaje que se desean registrar.
     * 
     * @param string $archivo Nombre del archivo del cual se va a generar el log. Si no existe va a intentar crearlo.
     * @param string $mensaje Mensaje a registrar en el log
     * @return boolean Si la operacion se realizo correctamente
     */
    public function regitrarLog($archivo, $mensaje) {      
        $log = fopen($archivo.".log", "at");
 
        if($log){
           fwrite($log, $mensaje);
           fclose($log);
           $retorno = true;
        }else{
            error_log("(!)Error > util_lxxxiv.php > registrarLog() > No se pudo abrir el archivo de log.");
            echo "<br>(!)Error: No se pudo abrir el archivo de log. <br>";
            $retorno = false;
        }
        
        return $retorno;
    }
    
    /**
     * Obtiene un listado de los archivos generados y los agrega a la vista en elemento DOM.
     * 
     * @param string $idTabla Id de la tabla en el DOM de la vista.
     * @param string $rutaCarpeta Ruta a la carpeta donde buscar los archivos. Ejemplo: getenv('PATH_CDEXPORTS') . "Retenciones/"
     * @param string $enlaceDescarga Ruta para el enlace de donde descargar los archivos. Ejemplo: getenv('PATH_DOWNLOAD') . "Retenciones/"
     * @param string $patronArchivo Expresion regular para encontrar los archivos buscados.
     * @param object $vista Vista cargada que se va a mostrar.
     */
    public function obtenerListadoArchivosHistorico($idTabla, $rutaCarpeta, $patronArchivo, $enlaceDescarga, &$vista) {
        if(!empty($idTabla) AND is_string($idTabla)
           AND !empty($rutaCarpeta) AND is_string($rutaCarpeta)
           AND !empty($patronArchivo) AND is_string($patronArchivo)
           AND !empty($rutaCarpeta) AND is_string($rutaCarpeta)
           AND !empty($enlaceDescarga) AND is_string($enlaceDescarga)
           AND is_a($vista, 'View')
        ){ //Para soportar PHP5.6 de tio-clon
            
            $tabla = $vista->getElementById($idTabla);
            $tbody = $tabla->getElementsByTagName("tbody")[0];
            $directorio = scandir($rutaCarpeta, SCANDIR_SORT_NONE);
            $listado = [];
            
            //obtiene archivos
            foreach ($directorio as $valor) {
                if (!is_dir($valor) AND preg_match($patronArchivo, $valor)) {
                    $listado[] = $valor;
                }
            }
            
            //ordena el array por fecha de modificacion de los archivos de manera descendente
            for($i=0; $i < count($listado); $i++){
                $fecha1 = filemtime($rutaCarpeta . $listado[$i]);
                
                for($j=0; $j < count($listado); $j++){
                    $fecha2 = filemtime($rutaCarpeta . $listado[$j]);
                    
                    if($fecha1 > $fecha2){
                        $temp = $listado[$i];
                        $listado[$i] = $listado[$j];
                        $listado[$j] = $temp;
                    }
                }
            }
            
            $listado = array_slice($listado, 0, 20, true);
            
            //Carga las filas de la tabla
            for($i=0; $i < count($listado); $i++) {
                $tr = $vista->createElement("tr");

                $tdArchivo = $vista->createElement("td");
                $tdArchivo->appendChild($vista->createTextNode($listado[$i]));
                
                $tdTamanio = $vista->createElement("td");
                $tdTamanio->appendChild($vista->createTextNode($this->bytesToStr(filesize($rutaCarpeta . $listado[$i]))));

                $tdFecha = $vista->createElement("td");
                $tdFecha->appendChild($vista->createTextNode(date("d/m/Y - H:i:s", filemtime($rutaCarpeta . $listado[$i]))));


                $a = $vista->createElement("a");
                $a->setAttribute("href", $enlaceDescarga . $listado[$i]);
                $a->setAttribute("download", "");
                $a->appendChild($vista->createTextNode("Descargar"));
                $tdEnlace = $vista->createElement("td");
                $tdEnlace->appendChild($a);

                $tr->appendChild($tdArchivo);
                $tr->appendChild($tdTamanio);
                $tr->appendChild($tdFecha);
                $tr->appendChild($tdEnlace);

                $tbody->appendChild($tr);
            }
            
            return true;
            
        }else{
            return NULL;
        }

    }
    
    /**
     * Pasa bytes a una cadena de texto con el tamaño de unidades dada.
     * 
     * @param int $bytes El numero de bytes a convertir.
     * @param string/null $unidad El tipo de unidad a convertir: byte, kB, mB, gB, tB. Modo por defecto automatico (null).
     * @return string Cantidad de bytes con la unidad de conversion utilizada.
     */
    public function bytesToStr($bytes, $unidad = NULL) {
        //tabla de referencias
        $tblUnidades = [];
        $tblUnidades['byte'] = [pow(2, 0), 'bytes'];
        $tblUnidades['kb'] = [pow(2, 10), 'KiB'];
        $tblUnidades['mb'] = [pow(2, 20), 'MiB'];
        $tblUnidades['gb'] = [pow(2, 30), 'GiB'];
        $tblUnidades['tb'] = [pow(2, 40), 'TiB'];
        
        if(is_int($bytes) AND $bytes >= 0){
            if(!empty($unidad) AND array_key_exists(strtolower($unidad), $tblUnidades)){
               return round($bytes/$tblUnidades[$unidad][0], 2) . $tblUnidades[$unidad][1];
               
            } else {
                if($bytes >= $tblUnidades['tb'][0]){
                    return round($bytes/$tblUnidades['tb'][0], 2) . " " . $tblUnidades['tb'][1];
                    
                }else if($bytes >= $tblUnidades['gb'][0]){
                    return round($bytes/$tblUnidades['gb'][0], 2) . " " . $tblUnidades['gb'][1];
                    
                }else if($bytes >= $tblUnidades['mb'][0]){
                    return round($bytes/$tblUnidades['mb'][0], 2) . " " . $tblUnidades['mb'][1];
                    
                }else if($bytes >= $tblUnidades['kb'][0]){
                    return round($bytes/$tblUnidades['kb'][0], 2) . " " . $tblUnidades['kb'][1];
                    
                }else{
                    return round($bytes/$tblUnidades['byte'][0], 2) . " " . $tblUnidades['byte'][1];      
                }
            }
        }
        
        return NULL;
    }
    
    /**
     * Valida si una fecha del tipo string es válida y cumple con el formato proporcionado. Y la retorna en un objeto DateTime.
     * Nota: ver formatos admitidos en https://www.php.net/manual/es/datetime.createfromformat.php y en https://www.w3schools.com/php/func_date_create_from_format.asp
     * 
     * @param string $fecha Fecha a validar.
     * @param string $formato Formato con el cual validar la fecha. Se recomienda utilizar ! para reiniciar a la Época Unix. Sin !, todos los campos serán establecidos a la fecha y hora actuales. Valor por defecto: '!Y#m#d+'.
     * @param array $rango Un array asociativo con el rango de fechas con las claves 'desde' y 'hasta'.
     * @return DateTime/boolean Devuelve una nueva instancia de DateTime o FALSE si no se valida la fecha. En caso de error en la validación retorna NULL.
     */
    public function validarFecha($fecha, $formato='!Y#m#d+', $rango=null) {
        
        if( !(is_string($fecha)) OR !(is_string($formato)) ){
            //return -1; //Solo para depuracion
            return NULL;
        }
        
        $fechaArray = date_parse_from_format($formato, $fecha);
        
        //Validacion por formato
        if(
            $fechaArray['error_count'] > 0 
            OR !checkdate($fechaArray['month'], $fechaArray['day'], $fechaArray['year'])
        ){
            error_log($fechaArray['error_count']);
            error_log(print_r($fechaArray['errors']));
            return false;
            
        }else{
            $fechaSalida = DateTime::createFromFormat($formato, $fecha);
        }

        if(!empty($rango)){
            if(
                !is_array($rango) 
                OR !array_key_exists('desde', $rango) 
                OR !array_key_exists('hasta', $rango)
                OR empty($desde = $this->validarFecha($rango['desde'], $formato))
                OR empty($hasta = $this->validarFecha($rango['hasta'], $formato))
            ){
                //return -2; //Solo para depuracion
                return NULL;

            }elseif( !($desde <= $fechaSalida AND $hasta >= $fechaSalida) ){
                error_log("La fecha ingresada no se encuentra en el rango ingresado.");
                return false;
            }
        }
        
        return $fechaSalida;
    }
    
    /**
     * Crea una carpeta en la ruta especificada.
     * 
     * @param string $ruta Ruta completa de la carpeta
     * @return boolean Devuelve TRUE en caso de éxito o FALSE en caso de error. NULL si la validacion no fue correcta.
     */
    public function generarCarpeta($ruta) {
        if(!empty($ruta) AND is_string($ruta)){ //Solo para soportar PHP5.6 de tio-clon
            $retorno = false;

            if (!is_dir($ruta)) {
                if(mkdir($ruta)){
                    error_log("(!)Carpeta creada para retenciones: " . $ruta);
                    $retorno = true;
                }else{
                    error_log("(!)Fallo al crear carpeta en: " . $ruta);
                }  
            }

            return $retorno;            
        }
        
        return NULL;
    }
    
    /**
     * Valida si un string numerico es un CBU valido.
     * 
     * Nota: Basado el algoritmo de verificación de CBU en wikipedia.
     * 
     * @param string $cbu Numero de CBU(22 caracteres)
     * @return boolean
     */
    public function validarCBU($cbu){

        if(!is_numeric($cbu)){
            return NULL;
        }

        $cbu = preg_replace('/[^0-9]/', '', $cbu);

        if(strlen($cbu) !== 22){
            return NULL;
        }

        $cbu_array = str_split($cbu);
        $digitoVerificadorBlq1 = $cbu_array[7];
        $digitoVerificadorBlq2 = $cbu_array[21];

        //Validacion del 1er bloque
        $sumaBloque1 = $cbu_array[0]*7 + $cbu_array[1]*1 + $cbu_array[2]*3 + $cbu_array[3]*9 + $cbu_array[4]*7 + $cbu_array[5]*1 + $cbu_array[6]*3;
        $restaBloque1 = 10 - substr($sumaBloque1, -1, 1);

        if($digitoVerificadorBlq1 != 0){
            if( $restaBloque1 != $digitoVerificadorBlq1){
                //DEBUG: error_log("validacionCBU()->No valida blq 1!(1)");
                return false;
            }
        }elseif($digitoVerificadorBlq1 == 0){
            if( $restaBloque1 != 10){
                //DEBUG: error_log("validacionCBU()->No valida blq 1!(2)");
                return false;
            }
        }else{
            //DEBUG: error_log("validacionCBU()->No valida blq 1!(3)");
            return false;
        }

        //Validacion del 2do bloque
        $sumaBloque2 = $cbu_array[8]*3 + $cbu_array[9]*9 + $cbu_array[10]*7 + $cbu_array[11]*1 + $cbu_array[12]*3 + $cbu_array[13]*9 + $cbu_array[14]*7 + $cbu_array[15]*1 + $cbu_array[16]*3 + $cbu_array[17]*9 + $cbu_array[18]*7 + $cbu_array[19]*1 + $cbu_array[20]*3;
        $restaBloque2 = 10 - substr($sumaBloque2, -1, 1);

        if($digitoVerificadorBlq2 != 0){
            if( $restaBloque2 != $digitoVerificadorBlq2){
            //DEBUG: error_log("validacionCBU()->No valida blq 2!(1)");
                return false;
            }
        }elseif($digitoVerificadorBlq2 == 0){
            if( $restaBloque2 != 10){
            //DEBUG: error_log("validacionCBU()->No valida blq 2!(2)");
                return false;
            }
        }else{
            //DEBUG: error_log("validacionCBU()->No valida blq 2!(3)");
            return false;
        }

        return true;
    }
        
        
        /**
         * Valida si el string ingresado es un CUIT valido con la longitud y el nro de verifcador. Soporta cadenas con guiones.
         * 
         * @param string $cuit nro de CUIT a verificar.
         * @return boolean
         */
    public function validarCUIT($cuit) {
        $cuit = preg_replace('/[^0-9]/', '', $cuit);

        if(strlen($cuit) !== 11 OR !is_numeric($cuit)){
            //DEBUG: error_log("validacionCUIT()->No valida longitud o tipo.");
            return NULL;
        }

        //VALIDACION DE CABECERA TIPO
        /*
         *  20 - Hombre
         *  27 - Mujer
         *  24 - Repetido
         *  30 - Empresa
         *  34 - Repetida
         */
	$tipo_cabecera = substr($cuit, 0, 2);
        
	if(!in_array($tipo_cabecera, ['20', '23', '24', '27', '30', '33', '34'])){
            //DEBUG: error_log("validacionCUIT()->No valida tipo de cabecera);
            return FALSE;
	}
        
        $cuit_array = str_split($cuit);
        $sumatoria = $cuit_array[9]*2 + $cuit_array[8]*3 + $cuit_array[7]*4 + $cuit_array[6]*5 + $cuit_array[5]*6 + $cuit_array[4]*7 + $cuit_array[3]*2 + $cuit_array[2]*3 + $cuit_array[1]*4 + $cuit_array[0]*5;
        $resto = $sumatoria % 11;
        $diferencia = 11 - $resto;

        if($diferencia === 11){
            $digito_verificador = 0;

        }elseif($diferencia === 10){
            $digito_verificador = 9;

        }else{
            $digito_verificador = $diferencia;
        }

        //var_dump("verficador:", $digito_verificador); //DEBUG
        return $cuit_array[10] == $digito_verificador;
    }
}
