<?php

/**
 * Clase que representa un registro del archivo Retencion de IVA-Ganancia (Resolucion Gral AFIP 4622, 4636)
 *
 * @author juampa (jpalegria@cobrodigital.com)
 */
class Registro_retencion {
    
    /**
     * Tabla de indices de ho_iva_ganancia (id_iva_ganancia => id_alicouta)
     * 
     * id_iva_ganancia TIPO COD_IMPUESTO  COD_REGIMEN
     *          1       IVA     767         16
     *          2       IVA     767         17
     *          3       IVA     767         15
     *          4       IVA     767         18
     *          5       GAN     217         70
     *          6       GAN     217         69
     *          7       GAN     217         71
     *          8       IVA     767         15
     */
    
    /** @var string Valor: 05. Descripcion: Otro Comprobante.*/
    public static $COD_COMPROBANTE_OTRO = '05';
    /** @var string Valor: 03. Descripcion: Nota de Credito.*/
    public static $COD_COMPROBANTE_NOTA_CRED = '03';
    /** @var string Valor: 1*/
    public static $COD_OPERACION_RETENCION = '1';
    /** @var string Valor: 2*/
    public static $COD_OPERACION_PERCEPCION = '2';
    /** @var string Valor: 01*/
    public static $COD_CONDICION_INSCRIPTO = '01';
    /** @var string Valor: 02*/
    public static $COD_CONDICION_NOINSCRIPTO = '02';
    /** @var string Valor: 00*/
    public static $COD_CONDICION_NINGUNA = '00';
    /** @var string Valor: 0*/
    public static $RETENCION_PRACT_SUSP_NINGUNO = '0'; //Retención prácticada a sujetos suspendidos: "Ninguno"
    /** @var string Valor: 000,00*/
    public static $PORCENTAJE_EXCLUSION = '000,00';
    /** @var string Valor: 217*/
    public static $COD_IMPUESTO_GANANCIA = '217';
    /** @var string Valor: 767*/
    public static $COD_IMPUESTO_IVA = '767';
    /** @var string Valor: 015*/
    public static $COD_REGIMEN_IVA_INSCRIPTO = '015';
    /** @var string Valor: 016 */
    public static $COD_REGIMEN_IVA_INSCRIPTO_TARJETA_LISTA = '016';
    /** @var string Valor: 017*/
    public static $COD_REGIMEN_IVA_INSCRIPTO_TARJETA = '017';
    /** @var string Valor: 018*/
    public static $COD_REGIMEN_IVA_NO_INSCRIPTO = '018';
    /** @var string Valor: 069*/
    public static $COD_REGIMEN_GANANCIA_INSCRIPTO = '069';
    /** @var string Valor: 070*/
    public static $COD_REGIMEN_GANANCIA_INSCRIPTO_TARJETA = '070';
    /** @var string Valor: 071*/
    public static $COD_REGIMEN_GANANCIA_NO_INSCRIPTO = '071';
    /** @var string Valor: 80*/
    public static $COD_TIPO_DOCUMENTO_CUIT = '80';
    /** @var string Valor: 86*/
    public static $COD_TIPO_DOCUMENTO_CUIL = '86';
    /** @var string Valor: 87*/
    public static $COD_TIPO_DOCUMENTO_CDI = '87';
    /** @var string Valor: 20111111112*/
    public static $CUIT_GENERICO = '20111111112';
    
    public static $LONGITUD_COD_COMPROBANTE = 2;
    public static $LONGITUD_FECHA_COMPROBANTE = 10;
    public static $LONGITUD_NRO_COMPROBANTE = 16;
    public static $LONGITUD_IMPORTE_COMPROBANTE = 16;
    public static $LONGITUD_COD_IMPUESTO = 3;
    public static $LONGITUD_COD_REGIMEN = 3;
    public static $LONGITUD_COD_OPERACION = 1;
    public static $LONGITUD_BASE_CALCULO = 14;
    public static $LONGITUD_FECHA_RETENCION = 10;
    public static $LONGITUD_COD_CONDICION = 2;
    public static $LONGITUD_RETENCION_PRACT_SUJETO = 1;
    public static $LONGITUD_IMPORTE_RETENCION = 14;
    public static $LONGITUD_PORCENTAJE_EXCLUSION = 6;
    public static $LONGITUD_FECHA_BOLETIN = 10;
    public static $LONGITUD_TIPO_DOC = 2;
    public static $LONGITUD_NRO_DOC = 20;
    public static $LONGITUD_NRO_CERTIFICADO = 14;
    public static $LONGITUD_DENOMINACION_ORDENANTE = 30;
    public static $LONGITUD_ACRECENTAMIENTO = 1;
    public static $LONGITUD_CUIT_PAIS_RETENIDO = 11;
    public static $LONGITUD_CUIT_ORDENANTE = 11;
    
    private $codComprobante;
    private $fechaEmisionComprobante;
    private $nroComprobante;
    private $importeComprobante;
    private $codImpuesto;
    private $codRegimen;
    private $codOperacion;
    private $baseCalculo;
    private $fechaEmisionRetencion;
    private $codCondicion;
    private $retencionPractSujSusp;
    private $importeRetencion;
    private $porcentajeExclusion;
    private $fechaEmisionBoletin;
    private $tipoDocRetenido;
    private $nroDocRetenido;
    private $nroCertificadoOriginal;
    private $denominacionOrdenante;
    private $acrecentamiento;
    private $cuitPaisRetenido;
    private $cuitOrdenante;
    
    
    //GETTERS
    
    public function getCodComprobante() {
        return $this->codComprobante;
    }

    public function getFechaEmisionComprobante() {
        return $this->fechaEmisionComprobante;
    }

    public function getNroComprobante() {
        return $this->nroComprobante;
    }

    public function getImporteComprobante() {
        return $this->importeComprobante;
    }

    public function getCodImpuesto() {
        return $this->codImpuesto;
    }

    public function getCodRegimen() {
        return $this->codRegimen;
    }

    public function getCodOperacion() {
        return $this->codOperacion;
    }

    public function getBaseCalculo() {
        return $this->baseCalculo;
    }

    public function getFechaEmisionRetencion() {
        return $this->fechaEmisionRetencion;
    }

    public function getCodCondicion() {
        return $this->codCondicion;
    }

    public function getRetencionPractSujSusp() {
        return $this->retencionPractSujSusp;
    }

    public function getImporteRetencion() {
        return $this->importeRetencion;
    }

    public function getPorcentajeExclusion() {
        return $this->porcentajeExclusion;
    }

    public function getFechaEmisionBoletin() {
        return $this->fechaEmisionBoletin;
    }

    public function getTipoDocRetenido() {
        return $this->tipoDocRetenido;
    }

    public function getNroDocRetenido() {
        return $this->nroDocRetenido;
    }

    public function getNroCertificadoOriginal() {
        return $this->nroCertificadoOriginal;
    }

    public function getDenominacionOrdenante() {
        return $this->denominacionOrdenante;
    }

    public function getAcrecentamiento() {
        return $this->acrecentamiento;
    }

    public function getCuitPaisRetenido() {
        return $this->cuitPaisRetenido;
    }

    public function getCuitOrdenante() {
        return $this->cuitOrdenante;
    }
    
    //SETTERS

    public function setCodComprobante($codComprobante) {
        if(!empty($codComprobante) AND is_string($codComprobante)){ //Para soportar PHP5.6 de tio-clon
            $this->codComprobante = $codComprobante; 
        }else{
            error_log("(!)Error > registro_retencion.php > setCodComprobante() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setFechaEmisionComprobante($fecha) {
        if(!empty($fecha) AND is_string($fecha)){ //Para soportar PHP5.6 de tio-clon
            $this->fechaEmisionComprobante = (new DateTime($fecha))->format('d/m/Y');            
        }else{
            error_log("(!)Error > registro_retencion.php > setFechaEmisionComprobante() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setNroComprobante($nroComprobante) {
        if(!empty($nroComprobante) AND is_string($nroComprobante)){ //Para soportar PHP5.6 de tio-clon
            $this->nroComprobante = str_pad($nroComprobante, 16, ' ', STR_PAD_RIGHT); //Número de comprobante
        }else{
            error_log("(!)Error > registro_retencion.php > setNroComprobante() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setImporteComprobante($importe) {
        if(!empty($importe) AND is_string($importe)){ //Para soportar PHP5.6 de tio-clon
            $montoRetenido = str_replace(['-', '+'], '', (string)number_format((float)$importe, 2, ',', ''));//Ej. de como viene de la BD: "Monto Retenido"> 39.35540
            $this->importeComprobante = str_pad($montoRetenido, 16, '0', STR_PAD_LEFT);//Importe del comprobante
        }else{
            error_log("(!)Error > registro_retencion.php > setImporteComprobante() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setCodImpuesto($id_alicuota) {
        if(!empty($id_alicuota) AND is_int($id_alicuota)){ //Para soportar PHP5.6 de tio-clon
            $this->codImpuesto = $this->obtenerCodImpuesto($id_alicuota); //Código de impuesto
        }else{
            error_log("(!)Error > registro_retencion.php > setCodImpuesto() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setCodRegimen($id_alicuota) {
        if(!empty($id_alicuota) AND is_int($id_alicuota)){ //Para soportar PHP5.6 de tio-clon
            $this->codRegimen = $this->obtenerCodRegimen($id_alicuota);
        }else{
            error_log("(!)Error > registro_retencion.php > setCodRegimen() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setCodOperacion($codOperacion) {
        if(!empty($codOperacion) AND is_int($codOperacion)){ //Para soportar PHP5.6 de tio-clon
            $this->codOperacion = $codOperacion;
        }else{
            error_log("(!)Error > registro_retencion.php > setCodOperacion() > No se valida el dato recibido.");
        }
        
        return $this;
    }
    
    public function setBaseCalculo($baseCalculo) {
        if(!empty($baseCalculo) AND is_string($baseCalculo)){ //Para soportar PHP5.6 de tio-clon
            $montoTotal = str_replace(['-', '+'], '',(string)number_format((float)$baseCalculo, 2, ',', ''));//Ej. de como viene de la BD: "Monto Total"> 7871.08
            $this->baseCalculo = str_pad($montoTotal, 14, '0', STR_PAD_LEFT);
        }else{
            error_log("(!)Error > registro_retencion.php > setBaseCalculo() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setFechaEmisionRetencion($fecha) {
        if(!empty($fecha) AND is_string($fecha)){ //Para soportar PHP5.6 de tio-clon
            $this->fechaEmisionRetencion = (new DateTime($fecha))->format('d/m/Y');
        }else{
            error_log("(!)Error > registro_retencion.php > setFechaEmisionRetencion() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setCodCondicion($id_alicuota) {
        if(is_int($id_alicuota) AND $id_alicuota >= 0){ //Para soportar PHP5.6 de tio-clon
            $this->codCondicion = $this->obtenerCodCondicion($id_alicuota);
        }else{
            error_log("(!)Error > registro_retencion.php > setCodCondicion() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setRetencionPractSujSusp($retencionPractSujSusp) {
        if(is_string($retencionPractSujSusp)){ //Para soportar PHP5.6 de tio-clon
            $this->retencionPractSujSusp = $retencionPractSujSusp;
        }else{
            error_log("(!)Error > registro_retencion.php > setRetencionPractSujSusp() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setImporteRetencion($importe) {
        if(is_numeric($importe)){ //Para soportar PHP5.6 de tio-clon
            $montoRetenido = str_replace(['-', '+'], '', (string)number_format((float)$importe, 2, ',', ''));//Ej. de como viene de la BD: "Monto Retenido"> 39.35540
            $this->importeRetencion = str_pad($montoRetenido, 14, '0', STR_PAD_LEFT);
        }else{
            error_log("(!)Error > registro_retencion.php > setImporteRetencion() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setPorcentajeExclusion($porcentaje) {
        if(!empty($porcentaje) AND is_string($porcentaje)){ //Para soportar PHP5.6 de tio-clon
            $this->porcentajeExclusion = $porcentaje;
        }else{
            error_log("(!)Error > registro_retencion.php > setPorcentajeExclusion() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setFechaEmisionBoletin($fecha) {
        if(!empty($fecha) AND is_string($fecha)){ //Para soportar PHP5.6 de tio-clon
            $this->fechaEmisionBoletin = (new DateTime($fecha))->format('d/m/Y');
        }else{
            error_log("(!)Error > registro_retencion.php > setFechaEmisionBoletin() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setTipoDocRetenido($id_tipodoc) {
        if(!empty($id_tipodoc) AND in_array($id_tipodoc, [self::$COD_TIPO_DOCUMENTO_CDI, self::$COD_TIPO_DOCUMENTO_CUIL, self::$COD_TIPO_DOCUMENTO_CUIT], true)){ //Para soportar PHP5.6 de tio-clon
            $this->tipoDocRetenido = $id_tipodoc;//Tipo de documento del retenido
        }else{
            error_log("(!)Error > registro_retencion.php > setTipoDocRetenido() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setNroDocRetenido($nroDocumento) {
        if(!empty($nroDocumento) AND is_string($nroDocumento)){ //Para soportar PHP5.6 de tio-clon
            if(!$this->docToCuit($nroDocumento)){
                $this->nroDocRetenido = NULL;  
            }else{
                $this->nroDocRetenido = str_pad($this->docToCuit($nroDocumento), 20, " ", STR_PAD_RIGHT);//Número de documento del retenido
            }
        }else{
            error_log("(!)Error > registro_retencion.php > setNroDocRetenido() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setNroCertOriginal($nroCertificadoOriginal) {
        if(!empty($nroCertificadoOriginal) AND is_string($nroCertificadoOriginal)){ //Para soportar PHP5.6 de tio-clon
            $this->nroCertificadoOriginal = str_pad($nroCertificadoOriginal, 14, '0', STR_PAD_LEFT);//Número de certificado original;
        }else{
            error_log("(!)Error > registro_retencion.php > setNroCertOriginal() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setDenominacionOrdenante($denominacionOrdenante) {
        if(is_string($denominacionOrdenante)){ //Para soportar PHP5.6 de tio-clon
            $this->denominacionOrdenante = str_pad($denominacionOrdenante, 30, ' ', STR_PAD_RIGHT);
        }else{
            error_log("(!)Error > registro_retencion.php > setDenominacionOrdenante() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setAcrecentamiento($acrecentamiento) {
        if(is_int($acrecentamiento)){ //Para soportar PHP5.6 de tio-clon
            $this->acrecentamiento = ($acrecentamiento === 0) ? ' ' : $acrecentamiento; //Al no poseer informacion relleno con espacio o cero. Es indistinto para datos numericos.
        }else{
            error_log("(!)Error > registro_retencion.php > setAcrecentamiento() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setCuitPaisRetenido($cuitPaisRetenido) {
        if(is_string($cuitPaisRetenido)){ //Para soportar PHP5.6 de tio-clon
            $this->cuitPaisRetenido = substr( str_pad($cuitPaisRetenido,11, ' ', STR_PAD_RIGHT), 0, 11 );
        }else{
            error_log("(!)Error > registro_retencion.php > setCuitPaisRetenido() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    public function setCuitOrdenante($cuitOrdenante) {
        if(is_string($cuitOrdenante)){ //Para soportar PHP5.6 de tio-clon
            $this->cuitOrdenante = substr(str_pad($cuitOrdenante, 11, ' ', STR_PAD_RIGHT), 0, 11);
        }else{
            error_log("(!)Error > registro_retencion.php > setCuitOrdenante() > No se valida el dato recibido.");
        }
        
        return $this;
    }

    /**
     * Constructor - Ver formato de importacion de sistema AFIP SICORE
     * 
     * @param string $codComprobante Codigo de comprobante
     * @param string $fechaEmision Fecha de emisión del comprobante. Soporta 'now'.
     * @param string $nroComprobante Número de comprobante
     * @param string $importeComprobante Importe del comprobante
     * @param int $impuesto Código de impuesto
     * @param int $regimen Código de regimen
     * @param int $operacion Código de operación
     * @param string $montoBase Base de cálculo
     * @param string $fechaRetencion Fecha de emisión de la retención Soporta 'now'.
     * @param int $condicion Codigo de condición
     * @param string $retPractSujSusp Retencion practicada a sujeto suspendido
     * @param string $importeRetencion Importe de la retención
     * @param string $porcentajeExclusion Porcentaje de exclusión
     * @param string $fechaBoletin Fecha de emisión del boletín.  Soporta 'now'.
     * @param int $tipoDocRetenido Tipo de documento del retenido
     * @param string $docRetenido Número de documento del retenido
     * @param string $certOriginal Número de certificado original
     * @param string $ordenante Denominacion del ordenante
     * @param int $acrecentamiento Acrecentamiento
     * @param string $cuitPaisRetenido Cuit del pais del retenido
     * @param string $cuitOrdenante Cuit del ordenante
     */
    public function __construct($codComprobante, 
                                $fechaEmision, 
                                $nroComprobante, 
                                $importeComprobante, 
                                $impuesto,
                                $regimen, 
                                $operacion,
                                $montoBase,
                                $fechaRetencion,
                                $condicion,
                                $retPractSujSusp,
                                $importeRetencion,
                                $porcentajeExclusion,
                                $fechaBoletin,
                                $tipoDocRetenido,
                                $docRetenido,
                                $certOriginal,
                                $ordenante = '',
                                $acrecentamiento = 0,
                                $cuitPaisRetenido = '',
                                $cuitOrdenante = ''
    ) {
        $this->setCodComprobante($codComprobante);
        $this->setFechaEmisionComprobante($fechaEmision);
        $this->setNroComprobante($nroComprobante);
        $this->setImporteComprobante($importeComprobante);
        $this->setCodImpuesto($impuesto);
        $this->setCodRegimen($regimen);
        $this->setCodOperacion($operacion);
        $this->setBaseCalculo($montoBase);
        $this->setFechaEmisionRetencion($fechaRetencion);
        $this->setCodCondicion($condicion);
        $this->setRetencionPractSujSusp($retPractSujSusp);
        $this->setImporteRetencion($importeRetencion);
        $this->setPorcentajeExclusion($porcentajeExclusion);
        $this->setFechaEmisionBoletin($fechaBoletin);
        $this->setTipoDocRetenido($tipoDocRetenido);
        $this->setNroDocRetenido($docRetenido);
        $this->setNroCertOriginal($certOriginal);
        $this->setDenominacionOrdenante($ordenante);
        $this->setAcrecentamiento($acrecentamiento);
        $this->setCuitPaisRetenido($cuitPaisRetenido);
        $this->setCuitOrdenante($cuitOrdenante);
    }
    
    
    
    /**
     * Genera un registro de retencion.
     * 
     * @return string Un registro con el formato de importacion para AFIP.
     */
    public function exportarRegistro() {
        try {
            $registro = "";
            
            $registro .= $this->getCodComprobante();
            $registro .= $this->getFechaEmisionComprobante();
            $registro .= $this->getNroComprobante();
            $registro .= $this->getImporteComprobante();
            $registro .= $this->getCodImpuesto();
            $registro .= $this->getCodRegimen();
            $registro .= $this->getCodOperacion();
            $registro .= $this->getBaseCalculo();
            $registro .= $this->getFechaEmisionRetencion();
            $registro .= $this->getCodCondicion();
            $registro .= $this->getRetencionPractSujSusp();
            $registro .= $this->getImporteRetencion();
            $registro .= $this->getPorcentajeExclusion();
            $registro .= $this->getFechaEmisionBoletin();
            $registro .= $this->getTipoDocRetenido();
            $registro .= $this->getNroDocRetenido();
            $registro .= $this->getNroCertificadoOriginal();
            $registro .= $this->getDenominacionOrdenante();
            $registro .= $this->getAcrecentamiento();
            $registro .= $this->getCuitPaisRetenido();
            $registro .= $this->getCuitOrdenante();
            
            return $registro;
            
        } catch (\Throwable $error) {
            error_log("(!)Excepción capturada >Error > util_lxxxiv.php > function crearRegistro() >" . $error->getMessage() . ">Linea: ". (string)$error->getLine() . ".");
            echo "Excepción capturada: " . $error->getMessage() . "\n";
            return NULL;
        }
    }
    
    /**
     * Obtiene el codigo de impuesto en base al id de la alicuota.
     * 
     * @param int $codigo ID de la alicuota en la base de datos. 
     * @return string Codigo del impuesto
     */
    protected function obtenerCodImpuesto($codigo) {
        
        switch ($codigo) {
            case 5:
            case 6:
            case 7:
                $impuesto = self::$COD_IMPUESTO_GANANCIA;
                break;

            case 1:
            case 2:
            case 3:
            case 4:
            case 8:
                $impuesto = self::$COD_IMPUESTO_IVA;
                break;
            
            default:
                $impuesto = NULL;
                break;
        }
        
        return $impuesto;
    }  
      
    /**
     * Obtiene el codigo de regimen en base al id de la alicuota.
     * 
     * @param int $codigo ID de la alicuota en la base de datos. 
     * @return string Codigo del regimen
     */
    protected function obtenerCodRegimen($codigo) {

        switch ($codigo) {
            case 1:
                $regimen = self::$COD_REGIMEN_IVA_INSCRIPTO_TARJETA_LISTA;
                break;
            
            case 2:
                $regimen = self::$COD_REGIMEN_IVA_INSCRIPTO_TARJETA;
                break;
            
            case 3: //Inscriptos en el iva en operaciones que no son de tarjeta y no estan en lista
            case 8: //Inscriptos en el iva en operaciones que no son de tarjeta y estan en lista
                $regimen = self::$COD_REGIMEN_IVA_INSCRIPTO;
                break;
            
            case 4:
                $regimen = self::$COD_REGIMEN_IVA_NO_INSCRIPTO;
                break;
            
            case 5:
                $regimen = self::$COD_REGIMEN_GANANCIA_INSCRIPTO_TARJETA;
                break;
            
            case 6:
                $regimen = self::$COD_REGIMEN_GANANCIA_INSCRIPTO;
                break;
            
            case 7:
                $regimen = self::$COD_REGIMEN_GANANCIA_NO_INSCRIPTO;
                break;          
            
            default:
                $regimen = NULL;
                break;
        }
        
        return $regimen;
    }
    
    /**
     * Obtiene el codigo de la condición en referencia a la alicuota.
     * 
     * @param int $codigo ID de la alicuota en la base de datos.
     * @return string Codigo de condicion
     */
    protected function obtenerCodCondicion($codigo) {
        if(is_int($codigo) AND $codigo >= 0){ //Para soportar PHP5.6 de tio-clon

            if($codigo == 7 || $codigo == 4 ){ //self::$COD_REGIMEN_GANANCIA_NO_INSCRIPTO || self::$COD_REGIMEN_IVA_NO_INSCRIPTO
                $condicion = self::$COD_CONDICION_NOINSCRIPTO;
            }elseif(in_array($codigo, [1,2,3,5,6,8])){
                $condicion = self::$COD_CONDICION_INSCRIPTO;
            }else{
                $condicion = self::$COD_CONDICION_NINGUNA;
            }
            
            return $condicion;
            
        }else{
            return NULL;
        }

    }
    
    
    
     /**
     * Obtiene el nro de documento correcto, si es un DNI lo transforma a CUIT generico.
     * 
     * Nota #1: En las columnas de id_tipodoc y documento de la tabla cd_marchand se puede encontrar cargado con un tipo no coincidente al nro documento.
     * Nota #2: No se diferencia CUIT del CUIL. La salida en cuyo caso siempre es del tipo CUIT.
     * Nota #3: Al no haber un tipo de documento para el DNI en el SICORE actual, se procesa como CUIT Generico.
     * 
     * @param string $docDB Documento que viene de la DB.
     * @return string Nro de CUIT.
     */
    protected function docToCuit($docDB) {
        if(!empty($docDB) AND is_string($docDB)){ //Para soportar PHP5.6 de tio-clon
            $documento = (int)$docDB;

            if(strlen($documento) === 11 & $documento > 0){
                $documento = (string) $documento;

            }elseif(strlen($documento) <= 10 & $documento > 0){
                $documento = self::$CUIT_GENERICO;

            }else{
                $documento = NULL;
            }

            return $documento;
            
        }else{
            return $docDB;
        }
    }
    
    /**
     * Obtiene el tipo de documento en base al nro de documento.
     * 
     * Nota #1: En las columnas de id_tipodoc y documento de la tabla cd_marchand se puede encontrar cargado con un tipo no coincidente al nro documento.
     * Nota #2: No se diferencia CUIT del CUIL. La salida en cuyo caso siempre es del tipo CUIT.
     * Nota #3: Al no haber un tipo de documento para el DNI en el SICORE actual, se procesa como CUIT Generico.
     * 
     * @param string $docDB Nro de documento
     * @return string Codigo para el tipo de documento
     */
    public static function obtenerCodTipoDoc($docDB) {
        if(!empty($docDB) AND is_string($docDB)){ //Para soportar PHP5.6 de tio-clon
            $documento = (int)$docDB;

            if(strlen($documento) === 11 & $documento > 0){ //CUIT-CUIL
                $codTipoDoc = self::$COD_TIPO_DOCUMENTO_CUIT;

            }elseif(strlen($documento) <= 10 & $documento > 0){//DNI
                $codTipoDoc = self::$COD_TIPO_DOCUMENTO_CUIT;

            }else{
                $codTipoDoc = NULL;
            }

            return $codTipoDoc;
        }else{
            return NULL;
        }
    }
    
    /**
     * Convierte el monto(importe) obtenido de algun atributo del objeto a numero valido PHP. El proceso de conversion retira los ceros de relleno (lado izq) y cambia la notacion decimal de coma por la de punto.
     * 
     * @param string $monto Importe desde el objeto.
     * @return string Importe normalizado
     */
    public static function normalizarMonto($monto) {
        if(!empty($monto) AND is_string($monto)){ //Para soportar PHP5.6 de tio-clon
            return str_replace(',', '.', ltrim($monto, '0'));
        }else{
            return NULL;
        }
    }
}
