<?php

/**
 * Clase que representa un archivo del Regimen de Información AFIP - F8125 (Resolucion Gral AFIP 4614/2019)
 *
 * @author: Juampa [jpalegria@cobrodigital.com]
 * @date: 04/02/2020.
 */
class Afip_f8125_archivo {
    const TIPO_REGISTRO = '01';
    const DENOMINACION_COBRO_DIGITAL= 'COBRO DIGITAL SRL';
    const CUIT_INFORMANTE_COBRO_DIGITAL = '33711566959';
    const SECUENCIA_ORIGINAL = 0;
    
    const SIGNO_MONTO_POSITIVO = '0';
    const SIGNO_MONTO_NEGATIVO = '1';
       
    const CODIGO_IMPUESTO = '0103';
    const CODIGO_CONCEPTO = '830';
    const NRO_FORMULARIO = '8125';
    const NRO_VERSION_APLICATIVO = '00100';
    const ESTABLECIMIENTO = '00';
    
    /**
     * Tipo De Registro
     * @var string 
     */
    protected $tipo_registro = self::TIPO_REGISTRO;
    /**
     * CUIT Informante
     * @var string numerico 
     */
    protected $cuit_informante;
    /**
     * Periodo Informado
     * @var DateTime
     */
    protected $periodo_informado;
    /**
     * Secuencia
     * @var int
     */
    protected $secuencia;
    /**
     * Denominacion
     * @var string
     */
    protected $denominacion;
    /**
     * Hora
     * @var DateTime
     */
    protected $hora;
    /**
     * Numero Verificador
     * @var int
     */
    protected $nro_verificador;
    /**
     * Listado De Prestadores(registros tipo 02)
     * @var array Afip_f8125_Prestador
     */
    protected $prestadores = [];
  
    /**
     * @return DateTime
     */
    public function getPeriodo_informado() {
        return $this->periodo_informado;
    }
    
    /**
     * @return int
     */
    public function getSecuencia() {
        return $this->secuencia;
    }
    
    /**
     * @return string
     */
    public function getDenominacion() {
        return $this->denominacion;
    }
    
    /**
     * @return DateTime
     */
    public function getHora() {
        return $this->hora;
    }
    
    /**
     * @return int
     */
    public function getNro_verificador() {
        return $this->nro_verificador;
    }
    
    /**
     * Cuenta la cantidad de registros que tiene el archivo (incluido cabecera, prestadores y sus operaciones).
     * @return int
     */
    public function obtener_cant_registros_detalle() {
        $prestadores = $this->getPrestadores();
        $cantidad = 1;
        $cantidad += count($prestadores);
        
        foreach ($prestadores as $prestador) {
            $cantidad += count($prestador->getOperaciones());
        }

        return $cantidad;
    }
    
    /**
     * Obtiene el listado de objetos Afip_f8125_prestador.php cargados.
     * @return array 
     */
    public function getPrestadores() {
        return $this->prestadores;
    }
    
    /**
     * Retorna cadena numerica que represanta un CUIT.
     * @return string 
     */
    public function getCuit_informante() {
        return $this->cuit_informante;
    }
    /**
     * Carga el periodo informado. Minimo: 201911. Maximo: 999912.
     * @param DateTime $periodo_informado
     * @return $this
     */
    public function setPeriodo_informado($periodo_informado) {
        
        if(!is_a($periodo_informado, 'DateTime')
           OR $periodo_informado < (new DateTime('2019-11'))
        ){
            error_log("(!)Afip_f8125_archivo->setPeriodo()->No se validaron los datos de entrada.");
            return NULL;
        }
        
        $this->periodo_informado = $periodo_informado;
        return $this;
    }

    /**
     * Asigna un nro de secuencia. Menor a 100. Original = 00. Rectificativa = 01 o superior.
     * Nota: En caso de error en la conversion va a tomar el valor 0, que sería secuencia "original".
     * @param int/string $secuencia
     * @return $this Devuelve una nueva referencia al objeto o FALSE si no se valida la secuencia. En caso de error en la validación retorna NULL.
     */
    public function setSecuencia($secuencia) {
        $secuencia = intval($secuencia, 10);
        
        if($secuencia < 100){
            $this->secuencia = $secuencia;
            
            return $this;
        }
        
        return false;
    }
    
    /**
     * Asigna una denominacion. Longitud máxima: 200.
     * @param string $denominacion
     * @return $this Devuelve una nueva al objeto o NULL si no se valida.
     */
    public function setDenominacion($denominacion) {
        if(!is_string($denominacion) OR strlen($denominacion) > 200 OR strlen($denominacion) == 0){
            error_log("(!)Afip_f8125_archivo->setDenominacion()->No se validaron los datos de entrada.");
            return NULL;
        }
        
        $this->denominacion = $denominacion;       
        return $this;
    }
    
    /**
     * Asigna el cuit del informante sin guiones.
     * @param string $cuit
     * @return boolean|$this Devuelve una nueva referencia al objeto o FALSE si no se valida el nro de CUIT. En caso de error por longitud de la validacion retorna NULL.
     */
    public function setCuit_informante($cuit) {
        $cuit = preg_replace('/[^0-9]/', '', $cuit);
        
        if(strlen($cuit) !== 11){
            error_log("(!)Afip_f8125_archivo->setCUIT_informante->No se validaron los datos de entrada.");
            
            return NULL;
            
        }else if(validar_cuit($cuit)){
            $this->cuit_informante = $cuit;
     
            return $this;
            
        }else{
            return false;
        }
    }

    /**
     * Asigna una hora al archivo. Formato: HHMMSS. Minimo: 000000. Maximo: 235959.
     * Nota: Soporta unicamente la cadena 'now' para asignar la fecha actual.
     * @param DateTime $hora
     * @return $this Devuelve una nueva referencia al objeto. En caso de error en la validacion retorna NULL.
     */
    public function setHora($hora) {
        $hora_ingresada = $hora === 'now' ? new DateTime() : $hora;
        
        if(!is_a($hora_ingresada, 'DateTime')){
            error_log("(!)Afip_f8125_archivo->setHora()->No se validaron los datos de entrada.");
            return NULL;
        }
        
        $this->hora = $hora_ingresada;     
        return $this;
    }

    /**
     * Asigna un nro verificador al archivo. Minimo: 000000. Maximo: 999999.
     * 
     * @param int $nro_verificador
     * @return $this Devuelve una nueva referencia al objeto. En caso de error en la validacion retorna NULL.
     */
    public function setNro_verificador($nro_verificador) {
        
        if(!is_int($nro_verificador) OR $nro_verificador < 0 OR $nro_verificador >999999){
            error_log("(!)Afip_f8125_archivo->setNro_verificador()->No se validaron los datos de entrada.");
            return NULL;
        }
        
        $this->nro_verificador = $nro_verificador;
        return $this;
    }
    
    /**
     * Constructor
     * 
     * @param DateTime $periodo_informado Minimo: 201911. Maximo: 999912.
     * @param int $secuencia Menor a 100. Original = 00. Rectificativa = 01 o superior.
     * @param DateTime/string $hora Soporta el string 'now' unicamente, en caso contrario utilizar un objeto DateTime.
     * @param int $nro_verificador Minimo: 000000. Maximo: 999999.
     * @param string numerico $cuit_informante Soporta guiones, se verifica que el nro sea un cuit valido.
     * @param string $denominacion Longitud maxima: 200.
     * @return $this Instancia del objeto.
     */
    public function __construct($periodo_informado, $secuencia=self::SECUENCIA_ORIGINAL, $hora='now', $nro_verificador=0, $cuit_informante = self::CUIT_INFORMANTE_COBRO_DIGITAL, $denominacion = self::DENOMINACION_COBRO_DIGITAL) {
        
        if(empty($this->setPeriodo_informado($periodo_informado)->setSecuencia($secuencia)->setHora($hora)->setNro_verificador($nro_verificador)->setCuit_informante($cuit_informante)->setDenominacion($denominacion))){
            error_log("(!)Afip_f8125_archivos->__construct()->No se validaron los datos de entrada.");
            return NULL;
        }
        
        return $this;
    }
    
    /**
     * Obtiene el registro cabecera (tipo 01) para cargar en el archivo entregable.
     * @return string Devuelve un string de longitud fija (261). En caso de fallar retorna una cadena vacia.
     */
    public function obtener_registro_cabecera(){
        $registro = "";
        
        $registro .= $this->tipo_registro;
        $registro .= $this->getCuit_informante();
        $registro .= $this->getPeriodo_informado()->format('Ym');
        $registro .= str_pad($this->getSecuencia(), 2, '0', STR_PAD_LEFT);
        $registro .= str_pad($this->getDenominacion(), 200, ' ', STR_PAD_RIGHT);
        $registro .= $this->getHora()->format('His');
        $registro .= self::CODIGO_IMPUESTO;
        $registro .= self::CODIGO_CONCEPTO;
        $registro .= str_pad($this->getNro_verificador(), 6, '0', STR_PAD_LEFT);
        $registro .= self::NRO_FORMULARIO;
        $registro .= self::NRO_VERSION_APLICATIVO;
        $registro .= self::ESTABLECIMIENTO;
        $registro .= str_pad($this->obtener_cant_registros_detalle(), 10, '0', STR_PAD_LEFT);
        
        if(strlen($registro) !== 261){
            error_log("(!)Afip_f8125_archivo->obtener_registro()->No se pudo generar la cadena de cebcera correctamente.>>>>$registro");
            return "";
        }
        
        return $registro;
    }
    
    /**
     * Devuelve un string con todos los registros tipo 02 y 03 listos para cargar al archivo entregable.
     * 
     * Nota 1: Este metodo emitira una alerta en el log de error por cada caso que no consiga un registro de manera correcta. Estos no se cargan al string de salida. 
     * Nota 2: Verificacion adicional, controla que la sumatoria de los montos de registros del tipo 3 coincida con el monto total del registro del tipo 02.
     */
    public function obtener_registros_prestadores() {
        $registros = "";
        
        foreach ($this->getPrestadores() as $prestador) {
            $registro_prestador = $prestador->obtener_registro_prestador();
            list($operaciones, $monto_total_control) = $prestador->obtener_registros_operaciones();
            
            $signo = substr($registro_prestador, -25, 1);
            $monto_total_prestador = ($signo === Afip_f8125_archivo::SIGNO_MONTO_NEGATIVO) ? -ltrim(substr($registro_prestador,-24,12), '0'): ltrim(substr($registro_prestador,-24,12), '0');
            
            $monto_total_control = ltrim($monto_total_control, '0');
            
            //Verifica la integridad del registro prestador, si el registro del tipo 02 tiene el mismo monto que la sumatoria de registros de operaciones que obtuvo incluyendo el signo.
            if(strlen($registro_prestador) === 42 AND $monto_total_prestador == $monto_total_control){
                $registros .= $registro_prestador . "\r\n";
                $registros .= $operaciones;
               
            }else{
                //SALIDA DE ERROR:
                $error = "Error no determinado.";
                
                if(strlen($registro_prestador) !== 42){
                    $error = "La longitud no es correcta.";
                }else if($monto_total_prestador != $monto_total_control){
                    $error = "El monto total del registro tipo 02 (prestador) no coincide con la sumatoria de los montos de las operaciones. monto_prestador: $monto_total_prestador, monto_control: $monto_total_control";
                }
                //var_dump($prestador);
                error_log("(!)Afip_f8125_archivo->obtener_registros_prestadores()->No se pudo generar el registro de prestador (tipo 02) y sus operaciones (tipo 03) correctamente. Error: $error ->Datos>>>>Prestador:$registro_prestador Operaciones: $operaciones.");
            }
        }
        
        return $registros;
    }
    
    /**
     * Agrega un objeto prestador.
     * 
     * @param Afip_f8125_prestador $prestador
     * @return boolean Devuelve TRUE en caso de agregar el prestador, y FALSE en caso de no poder validarse correctamente.
     */
    public function agregar_prestador($prestador) {
        //Controlar la cantidad de operaciones que tiene el prestador, no tiene ninguna no permitir agregar
        if(!is_a($prestador, 'Afip_f8125_prestador') OR count($prestador->getOperaciones()) == 0 ){
            error_log("(!)Afip_f8125_archivo->agregar_prestador()->No se validaron los datos de entrada o el prestador no tiene operaciones cargadas. >>>> Datos: " . json_encode($prestador));
            return false;
        }
        
        $this->prestadores[] = $prestador;
        
        return true;
    }
    
    /**
     * Formatea y valida un numero con el formato admitido para el archivo de f8125.
     * 
     * @param string/float $monto Longitud maxima de la parte entera: 12.
     * @return boolean/string Retorna una cadena numerica que representa a un entero sin separador de miles ni de decimales. Devuelve false en caso de error en la conversion.
     */
    public static function validarMonto($monto) {
        $monto = preg_replace('/[^0-9]/', '', intval(round($monto), 10));
        
        if (is_numeric($monto) and strlen($monto) <= 12 and $monto >= 0) {
            return $monto;
        }else{
            error_log("(!)Afip_f8125_archivo->validarMonto()->No se valida el monto ingresado >>>> Datos: $monto");
        }
        
        return false;
    }
}
