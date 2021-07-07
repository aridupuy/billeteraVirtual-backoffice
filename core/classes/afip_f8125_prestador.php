<?php

/**
 * Clase que representa un registro tipo 02 - Prestador de Servicio del archivo para el Regimen de Información AFIP - F8125 (Resolucion Gral AFIP 4614/2019)
 *
 *  Nota: Cuando dice 'prestador' lease prestador o vendedor (ver documentación de la AFIP para f8125). Cuando dice ID lease CUIT/CUIL/CDI.
 * 
 * @author: Juampa [jpalegria@cobrodigital.com]
 * @date: 04/02/2020.
 */
class Afip_f8125_prestador {
    const TIPO_REGISTRO = '02';
    
    const TIPO_RUBRO_SUPERMERCADO = '01';
    const TIPO_RUBRO_TURISMO = '02';
    const TIPO_RUBRO_INDUMENTARIA = '03';
    const TIPO_RUBRO_RESTAURANT = '04';
    const TIPO_RUBRO_AUTOMOVIL = '05';
    const TIPO_RUBRO_HOGAR = '06';
    const TIPO_RUBRO_SERVICIOS = '07';
    const TIPO_RUBRO_OTROS = '08';
    
    const TIPO_ID_PRESTADOR_CUIT = '80';
    const TIPO_ID_PRESTADOR_CUIL = '86';
    const TIPO_ID_PRESTADOR_CDI = '87'; //"Las personas físicas y las sucesiones indivisas pueden solicitar la Clave de Identificación (CDI) cuando no posean CUIT ni CUIL"
    
    /**
     * Tipo De Registro.
     * 
     * @var string 
     */
    protected $tipo_registro = self::TIPO_REGISTRO;
    
    /**
     * Tipo Identificacion Vendedor o Prestador.
     * 
     * @var string numerico
     */
    protected $tipo_id_prestador;
    
    /**
     * Identificacion del vendedor o prestador (CUIT/CUIL/CDI).
     * 
     * @var string numerico
     */
    protected $id_prestador;
    
    /**
     * Codigo De Rubro.
     * 
     * @var string numerico
     */
    protected $codigo_rubro;
    
    /**
     * Signo Monto Total (0 - positivo o 1 - negativo).
     * 
     * @var string numerico
     */
    protected $signo_monto_total = '0';
    
    /**
     * Monto Total De Las Operaciones Del Mes En Pesos.
     * 
     * @var string/float/int
     */
    protected $monto_total = '0';
    
    /**
     * Importe total de las comisiones cobradas.
     * Nota: Sin decimales. La suma de los montos de los detalles de operaciones para un mismo prestador debe ser igual al monto total de las operaciones del mes.
     * 
     * @var string/float/int
     */
    protected $importe_comision = '0';
    
    /**
     * Listado De Operaciones (registros tipo 03).
     * 
     * @var array Afip_f8125_operacion
     */
    protected $operaciones = [];
    
    /**
     * 
     * @return string numerico
     */
    public function getTipo_id_prestador() {
        return $this->tipo_id_prestador;
    }
    
    /**
     * Obtiene un CUIT/CUIL/CDI que representa el nro de identificacion del prestador de servicio en AFIP.
     * @return string CUIT/CUIL/CDI
    */
    public function getId_prestador(){
        return $this->id_prestador;
    }
    
    /**
     * Obtiene el codigo de rubro. Ver Tabla Rubros en documentacion AFIP para F8125.
     * @return string numerico
     */
    public function getCodigo_rubro() {
        return $this->codigo_rubro;
    }
    
    /**
     * Obtiene el signo del monto total del registro.
     * @return string numerico
     */
    public function getSigno_monto_total() {
        return $this->signo_monto_total;
    }
    
    /**
     * Obtiene un entero de hasta 12 digitos que represanta el monto total de las operaciones.
     * @return string numerico
     */
    public function getMonto_total() {      
        return $this->monto_total;
    }
    
    /**
     * Obtiene un entero de hasta 12 digitos que represanta las comisiones cobradas de las operaciones.
     * @return type
     */
    public function getImporte_comision() {
        return $this->importe_comision;
    }
    
    /**
     * Obtiene el listado de operaciones del prestador. 
     * @return array
     */
    public function getOperaciones() {
        return $this->operaciones;
    }
    
    /**
     * Carga el codigo del tipo de documento del prestador (CUIT/CUIL/CDI).
     * @param string numerico $tipo_id_prestador Codigo de tipo de documento. Ver Tabla Documentos en la documentacion AFIP.
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setTipo_id_prestador($tipo_id_prestador) {
        if(in_array($tipo_id_prestador, [
            self::TIPO_ID_PRESTADOR_CUIT,
            self::TIPO_ID_PRESTADOR_CUIL,
            self::TIPO_ID_PRESTADOR_CDI
            ])
        ){
            $this->tipo_id_prestador = $tipo_id_prestador;
            return $this;
        }
        
        return NULL;
    }
    
    /**
     * Carga el nro de CUIT/CUIL/CDI del prestador. 
     * @param string numerico $id_prestador Representa el id del prestador (CUIT/CUIL/CDI).
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setId_prestador($id_prestador) {
        $id_prestador = preg_replace('/[^0-9]/', '', $id_prestador);

        if(strlen($id_prestador) ===  11 AND (new Utilidades_dom())->validarCUIT($id_prestador)){
            $this->id_prestador = $id_prestador;
            return $this;
        }
        
        return NULL;
    }

    /**
     * Carga el rubro de servicio del prestador. 
     * @param string numerico $codigo_rubro Codigo de rubro. Ver Tabla Rubros en la documentacion AFIP.
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setCodigo_rubro($codigo_rubro) {
        //NOTA: EL TIPO_CUENTA_RUBRO_NINGUNO NO ES VALIDO PARA EL REGISTRO TIPO 02.
        if(in_array($codigo_rubro, [
                self::TIPO_RUBRO_SUPERMERCADO,
                self::TIPO_RUBRO_TURISMO,
                self::TIPO_RUBRO_INDUMENTARIA,
                self::TIPO_RUBRO_RESTAURANT,
                self::TIPO_RUBRO_AUTOMOVIL,
                self::TIPO_RUBRO_HOGAR,
                self::TIPO_RUBRO_SERVICIOS,
                self::TIPO_RUBRO_OTROS,
            ])
        ){
            $this->codigo_rubro = $codigo_rubro;
            return $this;
        }
        
        return NULL;
    }
    
    /**
     * Carga el codigo del signo del monto total del prestador.
     * @param string numerico $signo_monto_total Codigo de signo del monto total. Positivo = 0. Negativo = 1.
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setSigno_monto_total($signo_monto_total) {
        if(in_array($signo_monto_total, [Afip_f8125_archivo::SIGNO_MONTO_NEGATIVO, Afip_f8125_archivo::SIGNO_MONTO_POSITIVO])){
            $this->signo_monto_total = $signo_monto_total;
            return $this;
        }
        
        return NULL;
    }
    
    /**
     * Carga el monto total del prestador. 
     * @param string numerico $monto_total Monto total sin decimales.
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setMonto_total($monto_total) {

        if(Afip_f8125_archivo::validarMonto($monto_total) >= 0){          
            $this->monto_total = $monto_total;
            
            return $this;  
        }
        
        return NULL;
    }

    /**
     * Carga el importe de comision del prestador. 
     * @param string numerico $importe_comision Importe comision sin decimales.
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setImporte_comision($importe_comision) {
        if(Afip_f8125_archivo::validarMonto($importe_comision) >= 0){
            $this->importe_comision = $importe_comision;
            
            return $this;  
        }
        
        return NULL;
    }
    
    /**
     * Constructor
     * Nota: La comision por movimiento se paso la responsabilidad al objeto de operacion.
     * @param string numerico $id_prestador Nro de documento del prestador (CUIT/CUIL/CDI)
     * @param string numerico $tipo_id_prestador Codigo de tipo de documento. Ver Tabla Documentos en la documentacion AFIP.
     * @param string numerico $codigo_rubro Codigo de rubro del prestador. Ver Tabla Rubros en la documentacion AFIP.
     * @return $this Instancia del objeto.
     */     
    public function __construct($id_prestador, $tipo_id_prestador = self::TIPO_ID_PRESTADOR_CUIT, $codigo_rubro= self::TIPO_RUBRO_OTROS) {
        try{
            if(empty($this->setId_prestador($id_prestador)) OR empty($this->setTipo_id_prestador($tipo_id_prestador)) OR empty($this->setCodigo_rubro($codigo_rubro)) ){
                error_log("(!)Afip_f8125_prestador->__construct()->No se validaron los datos de entrada.->Datos>>>". json_encode(['$id_prestador'=> $id_prestador, '$tipo_id_prestador' => $tipo_id_prestador, '$codigo_rubro'=>$codigo_rubro]));
                
                return NULL;
            }     
        
            return $this;
        }
        catch (Throwable $th) //Se ejecuta solo en PHP7, no va matchear en PHP5
        {
           error_log("(!!)Afip_f8125_prestador->__construct()->Error al crear el objeto.->Datos>>>". json_encode(['$id_prestador'=> $id_prestador, '$tipo_id_prestador' => $tipo_id_prestador, '$codigo_rubro'=>$codigo_rubro]));
           $this->tipo_registro = $this->id_prestador = $this->tipo_id_prestador = $this->codigo_rubro = $this->operaciones = $this->signo_monto_total = $this->monto_total = $this->importe_comision = NULL;
           
           return NULL;
        }
        catch (Exception $ex) //Se ejecuta solo en PHP5, no se alcanza en PHP7
        {
            error_log("(!!!)Afip_f8125_prestador->__construct()->Error al crear el objeto.->Datos>>>". json_encode(['$id_prestador'=> $id_prestador, '$tipo_id_prestador' => $tipo_id_prestador, '$codigo_rubro'=>$codigo_rubro]));
            $this->tipo_registro = $this->id_prestador = $this->tipo_id_prestador = $this->codigo_rubro = $this->operaciones = $this->signo_monto_total = $this->monto_total = $this->importe_comision = NULL;

            return NULL;
        }
   
    }
    
    /**
     * Agrega un objeto operacion.
     * 
     * @nota-1: Por solicitud del Area de Finanzas, el importe de comision esta fijado y corresponde a la sumatoria total de comisiones (incluidos los traslados).
     * @nota-2: Si se necesita volver a las comisiones calculadas, se debe cambiar la consulta a la BD en Moves::select_afip_f8125_operaciones() por la de comisiones variables.
     * 
     * @param Afip_f8125_operacion $operacion
     * @return boolean Devuelve TRUE en caso de agregar la operacion, y FALSE en caso de no poder validarse correctamente.
     */
    public function agregar_operacion($operacion) {
        
        if(!is_a($operacion, 'Afip_f8125_operacion') OR $operacion->esta_vacio()){
            error_log("(!)Afip_f8125_prestador->agregar_operacion()->No se puede agregar objeto Afip_f8125_operacion por error de validacion.>>>> Datos: " . json_encode(json_encode(["metodo_acreditacion" => $operacion->getMetodologia_acreditacion(), "monto" => $operacion->getMonto(), "comision" => $operacion->getComision(), "signo_monto" => $operacion->getSigno_monto(), "tipo_cuenta" => $operacion->getTipo_cuenta(), "nro_id" => $operacion->getNro_id()])) . ". CUIT Prestador: " . $this->getId_prestador() );
            return false;
        }
        
        if($this->operaciones[] = $operacion){
            $monto_operacion = ($operacion->getSigno_monto() === Afip_f8125_archivo::SIGNO_MONTO_NEGATIVO) ? -$operacion->getMonto() : $operacion->getMonto();
            $monto_total = ($this->getSigno_monto_total() === Afip_f8125_archivo::SIGNO_MONTO_NEGATIVO) ? -$this->getMonto_total() : $this->getMonto_total();
            $saldo = $monto_total + $monto_operacion;
            $signo = ($saldo >= 0) ? Afip_f8125_archivo::SIGNO_MONTO_POSITIVO : Afip_f8125_archivo::SIGNO_MONTO_NEGATIVO;
            $this->setSigno_monto_total($signo);        
            $this->setMonto_total(preg_replace('/[^0-9]/', '', $saldo));
            
            /* Comision Calculada */
            //$this->setImporte_comision($this->getImporte_comision()+$operacion->getComision());
            
            /* Comision Fija */
            $this->setImporte_comision($operacion->getComision()); //Si bien es redundate la asignacion, para simplificar los cambios a un solo lugar se define aqui.
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Borrar todas las operaciones cargadas en el prestador. 
     * @return array Listado de operaciones cargadas.
     */
    public function vaciar_operaciones(){
        $operaciones = $this->getOperaciones();
        $this->operaciones = [];
        $this->setMonto_total('0');
        $this->setImporte_comision('0');
        
        return $operaciones;
    }
    
    /**
     * Devuelve el registro tipo 02 listo para cargar en el archivo. 
     * @return string Registro tipo 02.
     */
    public function obtener_registro_prestador() {
        $registro = "";
        $registro .= $this->tipo_registro;
        $registro .= $this->getTipo_id_prestador();
        $registro .= $this->getId_prestador();
        $registro .= $this->getCodigo_rubro();
        $registro .= $this->getSigno_monto_total();
        $registro .= str_pad($this->getMonto_total(), 12, '0', STR_PAD_LEFT);
        $registro .= str_pad($this->getImporte_comision(), 12, '0', STR_PAD_LEFT);

        if (strlen($registro) !== 42) {
            error_log("(!)Afip_f8125_prestador->obtenerRegistro()->No se valido la longitud del registro>>>>Datos>>>Registro: $registro. Objeto: ". json_encode($this));
            
            return "";
        }

        return $registro;
    }
    
    /**
     * Devuelve una cadena con todos los registros de operaciones (tipo 03) listos para cargar a un archivo (incluyendo los saltos de linea para M$-Window$).
     * @return string Los registros encontrados para escribir en el archivo-formulario.
     */  
    public function obtener_registros_operaciones() {
        $registros = "";
        $monto_total_control = "0";
        
        foreach($this->getOperaciones() as $operacion){
            $registro_operacion = $operacion->obtener_registro_operacion();
            
            if(strlen($registro_operacion) === 41){
                $montoSinSigno = substr($registro_operacion, -12, 12);
                $monto = substr($registro_operacion, -13, 1) === Afip_f8125_archivo::SIGNO_MONTO_NEGATIVO ? -$montoSinSigno : $montoSinSigno;
                $monto_total_control += $monto;
                $registros .= $registro_operacion . "\r\n";    
            }else{
                error_log("(!)Afip_f8125_prestador->obtener_registro_operaciones()->No se valida la longitud el registro de operacion.->Datos>>>" . json_encode($operacion). ">>>CUIT Prestador:" . $this->getId_prestador());
            }
        }
        
        return [$registros, $monto_total_control];
    }
    
    /**
     * Obtiene el tipo de documento en base al nro de documento.
     * Nota #1: En las columnas de id_tipodoc y documento de la tabla cd_marchand se puede encontrar cargado con un tipo no coincidente al nro documento.
     * Nota #2: No se diferencia CUIT del CUIL. La salida en cuyo caso siempre es del tipo CUIT.
     * Nota #3: Al no haber un tipo de documento para el DNI en el SICORE actual, se procesa como CUIT Generico. Tener en cuenta el procesado del nro de doc. 
     * @param string $docDB Nro de documento
     * @return string Codigo para el tipo de documento
     */
    public static function obtenerCodTipoDoc($docDB) {
        if(!empty($docDB) AND is_string($docDB)){ //Para soportar PHP5.6 de tio-clon
            $documento = (int)$docDB;

            if(strlen($documento) === 11 & $documento > 0){ //CUIT-CUIL
                $codTipoDoc = self::TIPO_ID_PRESTADOR_CUIT;

            }elseif(strlen($documento) <= 10 & $documento > 0){//DNI
                $codTipoDoc = self::TIPO_ID_PRESTADOR_CUIT;

            }else{
                error_log("(!)Afip_f8125_prestador->obtenerCodTipoDoc()->No se validaron los datos de entrada.>>>>Datos>>>>$docDB");
                $codTipoDoc = NULL;
            }

            return $codTipoDoc;
            
        }
        
        return NULL;
    }
    
    /**
     * Retorna el codigo del rubro para el sistema afip f8125.
     * @param string $rubro Rubro del cuit segun la base de datos
     * @return string Codigo del rubro
     */
    public static function obtenerCodRubro($rubro) {
        
        if(!is_string($rubro)){
            error_log("(!)Afip_f8125_prestador->obtenerCodRubro()->No se validaron los datos de entrada.>>>>Datos>>>>$rubro");
            return NULL;
        }
        
        if(stristr($rubro,"supermercado")){
            return self::TIPO_RUBRO_SUPERMERCADO;
            
        }else if(stristr($rubro,"viajes") OR stristr($rubro,"turismo")){
            return self::TIPO_RUBRO_TURISMO;
            
        }else if(stristr($rubro,"indumentaria")){
            return self::TIPO_RUBRO_INDUMENTARIA;
            
        }else if(stristr($rubro,"restaurant")){
            return self::TIPO_RUBRO_RESTAURANT;
            
        }else if(stristr($rubro,"automovil")){
            return self::TIPO_RUBRO_AUTOMOVIL;
            
        }else if(stristr($rubro,"hogar")){
            return self::TIPO_RUBRO_HOGAR;
            
        }else if(stristr($rubro,"servicios")){
            return self::TIPO_RUBRO_SERVICIOS;
            
        }else{
            return self::TIPO_RUBRO_OTROS;
        }
    }
    
    /**
     * Verifica si el propio objeto tiene inicializadas las variables con el constructor.  
     * @return boolean
     */
    public function esta_vacio() {

        return empty($this->tipo_registro) OR empty($this->id_prestador) OR empty($this->tipo_id_prestador) OR empty($this->codigo_rubro);
    }
}
