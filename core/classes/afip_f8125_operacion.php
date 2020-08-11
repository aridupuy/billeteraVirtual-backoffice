<?php

/**
 * Clase que representa al registro tipo 03 -Detalles de operaciones del archivo para el Regimen de Información AFIP - F8125 (Resolucion Gral AFIP 4614/2019)
 *
 * Nota: Para la AFIP el nro de ID es el CUIT/CUIL/CDI de la persona juridica/fisica.
 * 
 * @author: Juampa [jpalegria@cobrodigital.com]
 * @date: 04/02/2020.
 */
class Afip_f8125_operacion {

    const TIPO_REGISTRO = '03';
    const TIPO_CUENTA_CAJA_AHORRO = '01';
    const TIPO_CUENTA_CAJA_AHORRO_REMUNERACIONES = '02';
    const TIPO_CUENTA_CTA_FONDO_DESEMPLEO = '03';
    const TIPO_CUENTA_CAJA_AHORRO_CIRC_CERRADO = '04';
    const TIPO_CUENTA_CAJA_USURAS_PUPILARES = '05';
    const TIPO_CUENTA_CTACTE_JURIDICAS_CON_INTERES = '06';
    const TIPO_CUENTA_CTACTE_JURIDICAS_SIN_INTERES = '07';
    const TIPO_CUENTA_VISTA_MONEDA_EXTRANJERA = '08';
    const TIPO_CUENTA_CAJA_AHORRO_ESPECIAL_GARANTIAS = '09';
    const TIPO_CUENTA_CTACTE_CON_INTERES = '10';
    const TIPO_CUENTA_CTACTE_SIN_INTERES = '11';
    const TIPO_CUENTA_CTACTE_PAGO_REMUNERACIONES = '12';
    const TIPO_CUENTA_CTA_CTE = '13';
    const TIPO_CUENTA_CTA_BASICA = '14';
    const TIPO_CUENTA_NINGUNA = '00';

    /**
     * Metodologia de Acreditacion: 01 - CBU.
     */
    const METODO_ACREDITACION_CBU = '01';

    /**
     * Metodologia de Acreditacion: 02 - CVU.
     */
    const METODO_ACREDITACION_CVU = '02';

    /**
     * Metodologia de Acreditacion: 03 - Efectivo.
     */
    const METODO_ACREDITACION_EFVO = '03';

    /**
     * Metodologia de Acreditacion: 04 - Cheque.
     */
    const METODO_ACREDITACION_CHQ = '04';

    /**
     * Metodologia de Acreditacion: 05 - Otra modalidad de pago.
     */
    const METODO_ACREDITACION_OTRA = '05';

    /**
     * Tipo de registro.
     * @var string numerico 
     */
    protected $tipo_registro = self::TIPO_REGISTRO;

    /**
     * Metodologia de acreditaciones.
     * @var string numerico
     */
    protected $metodologia_acreditacion;

    /**
     * Tipo de cuenta.
     * Nota: ver tabla de cuenta si es CBU sino toma su valor: 00.
     * @var string numerico
     */
    protected $tipo_cuenta;

    /**
     * Nro de identifiacion.
     * Nota: Tomara el valor de CBU/CVU si la metodologia de acreditacion indica lo mismo.
     * @var string numerico 
     */
    protected $nro_id;

    /**
     * Signo Monto Total (0 - positivo o 1 - negativo).
     * 
     * @var string numerico
     */
    protected $signo_monto;

    /**
     * Monto de la operacion.
     * Nota: Sin decimales. La suma de los montos de los detalles de operaciones para un mismo prestador debe ser igual al monto total de las operaciones del mes.
     * @var string/float/int
     */
    protected $monto; //en Pesos

    /**
     * Comision cobrada por la operacion.
     * @var string numerico 
     */
    protected $comision;

    /**
     * Devuelve el codigo del tipo de registro.
     * @return string numerico
     */
    public function getTipo_registro() {
        return $this->tipo_registro;
    }

    /**
     * Devuelve el codigo de la metodologia de acreditacion de la operacion.
     * @return string numerico
     */
    public function getMetodologia_acreditacion() {
        return $this->metodologia_acreditacion;
    }

    /**
     * Devuelve el codigo del tipo de cuenta de la operacion.
     * @return string
     */
    public function getTipo_cuenta() {
        return $this->tipo_cuenta;
    }

    /**
     * Devuelve el numero de CBU/CVU de la operacion si la metodologia de acreditacion es 01 o 02, o cero en caso contrario.
     * @return string numerico
     */
    public function getNro_id() {
        return $this->nro_id;
    }

    /**
     * Devuelve el signo del monto de la operacion.
     * @return string numerico
     */
    public function getSigno_monto() {
        return $this->signo_monto;
    }

    /**
     * Devuelve el monto total de la operacion.
     * @return string numerico
     */
    public function getMonto() {
        return $this->monto;
    }

    /**
     * Devuelve el monto de la comision de la operacion
     * @return string numerico
     */
    public function getComision() {
        return $this->comision;
    }

    /**
     * Carga una metodologia de acreditacion a la operacion.
     * @param string $metodologia_acreditacion
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setMetodologia_acreditacion($metodologia_acreditacion) {
        if (in_array($metodologia_acreditacion, [
                    self::METODO_ACREDITACION_CBU,
                    self::METODO_ACREDITACION_CVU,
                    self::METODO_ACREDITACION_EFVO,
                    self::METODO_ACREDITACION_CHQ,
                    self::METODO_ACREDITACION_OTRA], true)
        ) {
            $this->metodologia_acreditacion = $metodologia_acreditacion;
            return $this;
        }

        return NULL;
    }

    /**
     * Carga el tipo de cuenta de la operacion.
     * @param string numerico $tipo_cuenta
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setTipo_cuenta($tipo_cuenta) {

        if ($this->metodologia_acreditacion === self::METODO_ACREDITACION_CBU AND in_array($tipo_cuenta, [
                    self::TIPO_CUENTA_CAJA_AHORRO,
                    self::TIPO_CUENTA_CAJA_AHORRO_REMUNERACIONES,
                    self::TIPO_CUENTA_CTA_FONDO_DESEMPLEO,
                    self::TIPO_CUENTA_CAJA_AHORRO_CIRC_CERRADO,
                    self::TIPO_CUENTA_CAJA_USURAS_PUPILARES,
                    self::TIPO_CUENTA_CTACTE_JURIDICAS_CON_INTERES,
                    self::TIPO_CUENTA_CTACTE_JURIDICAS_SIN_INTERES,
                    self::TIPO_CUENTA_VISTA_MONEDA_EXTRANJERA,
                    self::TIPO_CUENTA_CAJA_AHORRO_ESPECIAL_GARANTIAS,
                    self::TIPO_CUENTA_CTACTE_CON_INTERES,
                    self::TIPO_CUENTA_CTACTE_SIN_INTERES,
                    self::TIPO_CUENTA_CTACTE_PAGO_REMUNERACIONES,
                    self::TIPO_CUENTA_CTA_CTE,
                    self::TIPO_CUENTA_CTA_BASICA,
                        ]
                )) {
            $this->tipo_cuenta = $tipo_cuenta;
        } else if ($tipo_cuenta === self::TIPO_CUENTA_NINGUNA) {
            $this->tipo_cuenta = $tipo_cuenta;
        } else {
            return NULL;
        }

        return $this;
    }
    
    /**
     * Carga el nro de identificacion de la operacion.
     * Nota: Debe cargarse el CBU/CVU si la metodologia de acreditacion indica lo mismo, sino se debe cargar con ceros.
     * @param string numerico $nro_id
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setNro_id($nro_id) {

        if (in_array($this->getMetodologia_acreditacion(), [self::METODO_ACREDITACION_CBU, self::METODO_ACREDITACION_CVU])) {
            $nro_id = preg_replace('/[^0-9]/', '', $nro_id);

            if (strlen($nro_id) === 22) {
                $this->nro_id = $nro_id;
            } else {
                return NULL;
            }
        } else {
            $this->nro_id = str_pad('', 22, '0');
        }

        return $this;
    }

    /**
     * Carga el codigo del signo del monto total de la operacion.
     * @param string numerico $signo_monto_total Codigo de signo del monto total. Positivo = 0. Negativo = 1.
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setSigno_monto($signo_monto) {
        if (in_array($signo_monto, [Afip_f8125_archivo::SIGNO_MONTO_NEGATIVO, Afip_f8125_archivo::SIGNO_MONTO_POSITIVO])) {
            $this->signo_monto = $signo_monto;
            return $this;
        }

        return NULL;
    }

    /**
     * Carga el monto de la operacion sin decimales.
     * @param string numerico $monto
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setMonto($monto) {
        $monto_sin_decimales = Afip_f8125_archivo::validarMonto($monto);

        if (!empty($monto_sin_decimales)) {
            $this->monto = $monto_sin_decimales;
            return $this;
        }

        return NULL;
    }

    /**
     * Carga la comision de la operacion sin decimales.
     * @param string numerico $comision 
     * @return $this Devuelve una nueva referencia al objeto o NULL en caso de error en la validación.
     */
    public function setComision($comision) {
        $comision_sin_decimales = Afip_f8125_archivo::validarMonto($comision);

        if (is_numeric($comision_sin_decimales)) {
            $this->comision = $comision_sin_decimales;
            return $this;
        }

        return NULL;
    }

    /**
     * Constructor
     * @param string numerico $metodo_acreditacion Metodologia de acreditacion
     * @param string numerico $monto Monto de la operacion
     * @param string numerico $comision Comision cobrada de la operacion
     * @param string numerico $signo_monto Signo del monto de la operacion
     * @param string numerico $tipo_cuenta Tipo de cuenta segun la tabla de cuentas
     * @param string numerico $nro_id Nro de CBU/CVU de la operacion
     * @return $this Instancia del objeto
     */
    public function __construct($metodo_acreditacion, $monto, $comision, $signo_monto = Afip_f8125_archivo::SIGNO_MONTO_POSITIVO, $tipo_cuenta = self::TIPO_RUBRO_NINGUNO, $nro_id = NULL) {
        try{
            $this->setMetodologia_acreditacion($metodo_acreditacion)->setMonto($monto)->setComision($comision)->setSigno_monto($signo_monto)->setTipo_cuenta($tipo_cuenta)->setNro_id($nro_id);

            return $this;
        }    
        catch (Throwable $th) //Se ejecuta solo en PHP7, no va matchear en PHP5
        {
            $this->metodologia_acreditacion = $this->tipo_cuenta = $this->nro_id = $this->signo_monto = $this->monto = $this->comision = NULL;
            error_log("(!!!)Afip_f8125_operacion->__construct()->Error al crear el objeto >>>> " . json_encode(["metodo_acreditacion" => $metodo_acreditacion, "monto" => $monto, "comision" => $comision, "signo_monto" => $signo_monto, "tipo_cuenta" => $tipo_cuenta, "nro_id" => $nro_id]));
            
            return NULL;
           
        }
        catch (Exception $ex) //Se ejecuta solo en PHP5, no se alcanza en PHP7
        {
            $this->metodologia_acreditacion = $this->tipo_cuenta = $this->nro_id = $this->signo_monto = $this->monto = $this->comision = NULL;
            error_log("(!!!)Afip_f8125_operacion->__construct()->Error al crear el objeto >>>> " . json_encode(["metodo_acreditacion" => $metodo_acreditacion, "monto" => $monto, "comision" => $comision, "signo_monto" => $signo_monto, "tipo_cuenta" => $tipo_cuenta, "nro_id" => $nro_id]));
            
            return NULL;
        }
    }
    
    /**
     * Devuelve una cadena con el registro de operacion (tipo 03) para cargar en el archivo
     * @return string
     */
    public function obtener_registro_operacion() {
        $registro = "";
        $registro .= $this->getTipo_registro();
        $registro .= $this->getMetodologia_acreditacion();
        $registro .= $this->tipo_cuenta;
        $registro .= $this->getNro_id();
        $registro .= $this->getSigno_monto();
        $registro .= str_pad($this->getMonto(), 12, '0', STR_PAD_LEFT);

        if (strlen($registro) !== 41) {
            error_log("(!)Afip_f8125_operacion->obtener_registro()->No se valida la longitud el registro de operacion.->Datos>>>$registro.");
            return "";
        }

        return $registro;
    }

    /**
     * Devuelve el codigo de metodo de acreditacion correspondiente al id_mp.
     * @param int $id_metodo Valor del campo id_mp de la consulta.
     * @return string numerico
     */
    public static function obtenerMetodo_acreditacion($id_metodo) {

        if (!is_int($id_metodo) OR $id_metodo <= 0) {
            error_log("(!)Afip_f8125_operacion->obtenerMetodo_acreditacion()->No se pudo validar.");
            return NULL;
        }

        //(!)Nota Importante: No se esta procesando nada como codigo cvu.
        if (in_array($id_metodo, [50, 51, 87, 88, 110, 114, 1005, 3006])) {
            return self::METODO_ACREDITACION_CBU;
        } elseif (in_array($id_metodo, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 20, 21, 118, 500, 1011, 1012, 1013, 1014, 1015, 1016, 1018, 1019])) {
            return self::METODO_ACREDITACION_EFVO;
        } elseif (in_array($id_metodo, [111, 115, 116, 117, 3007])) {
            return self::METODO_ACREDITACION_CHQ;
        } else {
            return self::METODO_ACREDITACION_OTRA;
        }
    }

    /**
     * Devuelve codigo del tipo de cuenta correspondiente al id_tipocuenta.
     * @param type $id_tipo_cuenta Valor del campo id_tipocuenta de la consulta.
     * @return boolean
     */
    public static function obtenerTipo_cuenta($id_tipo_cuenta) {
        if (!is_int($id_tipo_cuenta) AND $id_tipo_cuenta <= 0) {
            error_log("(!)Afip_f8125_operacion->obtenerTipo_cuenta()->No se pudo validar. Datos>>>> $id_tipo_cuenta");
            return NULL;
        }

        switch ($id_tipo_cuenta) {
            case 1:
                return self::TIPO_CUENTA_CAJA_AHORRO;

            case 2:
                return self::TIPO_CUENTA_CTA_CTE;

            case 3:
                return self::TIPO_CUENTA_CAJA_AHORRO_ESPECIAL_GARANTIAS; //AVERIGUAR SI ES CORRECTO

            case 9:
                return self::TIPO_CUENTA_CTA_BASICA; //AVERIGUAR SI ES CORRECTO

            case 99:
                return self::TIPO_CUENTA_CTA_BASICA; //AVERIGUAR SI ES CORRECTO

            default:
                return false;
        }
    }
    
    /**
     * Verifica si el propio objeto tiene inicializadas las variables con el constructor.  
     * @return boolean
     */
    public function esta_vacio() {
        return empty($this->metodologia_acreditacion) OR  is_null($this->tipo_cuenta) OR  is_null($this->nro_id) OR is_null($this->signo_monto) OR  empty($this->monto) OR  is_null($this->comision);
    }

}
//Fin de la clase
