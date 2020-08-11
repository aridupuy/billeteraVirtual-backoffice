<?php

/**
 * Clase que representa y genera el header, footer y registros del archivo de Retiro del Banco Galicia para Transferencias-Pago a Proveedores.
 *
 * @author juampa (jpalegria@cobrodigital.com)
 * 
 * @copyright Diciembre 2019.
 */
class Registro_galicia_retiros extends Registro{
       
    //Constantes header
    /** @var string Tipo Archivo - Pago Proveedores.*/
    const HEADER_TIPO_ARCHIVO_PAGO_PROVEEDORES = '*CP';
    /** @var string Tipo Archivo - Pago Haberes.*/
    const HEADER_TIPO_ARCHIVO_PAGO_HABERES = '*CH';
    /** @var string Código de prestación de la empresa.*/
    const HEADER_FOOTER_COD_EMPRESA = '020691';
    /** @var string Cuit de la empresa Cobro Digital.*/
    const HEADER_CUIT_EMPRESA = '33711566959';
    /** @var string Tipo cuenta: Cta Cte.*/
    const TIPO_CUENTA_CTA_CTE = 'C';
    /** @var string Tipo cuenta: Caja Ahorro.*/
    const TIPO_CUENTA_CAJA_AHORRO = 'A';
    /** @var string Moneda en pesos.*/
    const TIPO_MONEDA_PESOS = '1';
    /** @var string Moneda en dolar.*/
    const TIPO_MONEDA_DOLAR = '2';
    /** @var string Folio de la cuenta de débito.*/
    const HEADER_FOLIO= '9750124';
    /** @var string Dígito 1 de la cuenta de débito.*/
    const HEADER_DIGITO1 = '2';
    /** @var string Sucursal de la cuenta de débito.*/
    const HEADER_SUCURSAL = '047';
    /** @var string Dígito 2 de la cuenta de débito.*/
    const HEADER_DIGITO2 = '6';
    /** @var string CBU Débito. Valor cereado.*/
    const HEADER_CBU = '00000000000000000000000000';
    /** @var Relleno de 27 espacios.*/
    const HEADER_RELLENO = "                                                                        ";
    
    /** @var Por documentacion es siempre 32.*/
    const REGISTRO_COD_TRANSACCION = "32";
    /** @var Tipo de transaccion: Acreditamiento de sueldo.*/
    const REGISTRO_TIPO_TRANSACCION_ACRED_SUELDO = "1";
    /** @var Tipo de transaccion: Pago a proveedores.*/
    const REGISTRO_TIPO_TRANSACCION_PAGO_PROVEEDORES = "2";
    /** @var Folio de la cuenta de crédito.*/
    const REGISTRO_FOLIO= '0000000';
    /** @var Dígito 1 de la cuenta de crédito.*/
    const REGISTRO_DIGITO1 = '0';
    /** @var string Sucursal de la cuenta de crédito.*/
    const REGISTRO_SUCURSAL = '000';
    /** @var string Dígito 2 de la cuenta de crédito.*/
    const REGISTRO_DIGITO2 = '0';
    /** @var Relleno de 17 espacios*/
    const REGISTRO_RELLENO= "                 ";
    
    //Constantes footer
    /**@var string Tipo de registro para el footer.*/
    const FOOTER_TIPO_REGISTRO = '*F';
    /** @var string Relleno de 135 espacios.*/
    const FOOTER_RELLENO = '                                                                                                                                       ';
    
    /**
     * Genera el header del archivo de retiro para Transferencia-Pago a Proveedores.
     * @param type $importe_total
     * @return string
     */
    
    /**
     * Genera string correspondiente al header del archivo de retiro para Transferencia - Pago a Proveedores.
     * @param float $importe_total Sumatoria de todos los montos(monto_cd) de los registros del archivo.
     * @param DateTime $fecha_acreditacion Indica la fecha de la acreditacion de los registros de detalle.
     * @param string $tipo_archivo Indica si es un archivo de pago de haberes o pago de proveedores.
     * @param string $moneda Indica el tipo de moneda de la cuenta debito que se va usar.
     * @param string $tipo_cuenta Indica si la cuenta de debito es una Cta Cte o Caja de Ahorro.
     * @return string/boolean Retorna false en caso de error, null en caso que falle la validacion.
     */
    public function generar_header(
            $importe_total,
            $fecha_acreditacion,
            $tipo_archivo=self::HEADER_TIPO_ARCHIVO_PAGO_PROVEEDORES,
            $moneda= self::TIPO_MONEDA_PESOS,
            $tipo_cuenta= self::TIPO_CUENTA_CTA_CTE
    ){
        //Validacion
        if(
            !in_array($tipo_archivo, [self::HEADER_TIPO_ARCHIVO_PAGO_HABERES, self::HEADER_TIPO_ARCHIVO_PAGO_PROVEEDORES], true)
            OR
            !$this->validarTipoCuenta($tipo_cuenta)
            OR
            !is_float($importe_total) OR !($importe_total <= 99999999.99) OR !($importe_total > 0.0)
            OR
            !$this->validarMoneda($moneda)
            OR
            !$this->validarFechaAcreditacion($fecha_acreditacion)
        ){
            $debug = debug_backtrace();
            error_log("(!)Registro_retiros_galicia->generar_header()->No se puede construir el registro por error de validacion. Se retorna NULL!>>>>Datos Entrada: " . json_encode($debug[0]['args']));
            return NULL;
        }
        
        //construccion header
        $header = "";
        $header .= $tipo_archivo;
        $header .= self::HEADER_FOOTER_COD_EMPRESA;
        $header .= self::HEADER_CUIT_EMPRESA;
        $header .= $tipo_cuenta;
        $header .= $moneda;
        $header .= self::HEADER_FOLIO;
        $header .= self::HEADER_DIGITO1;
        $header .= self::HEADER_SUCURSAL;
        $header .= self::HEADER_DIGITO2;
        $header .= self::HEADER_CBU;
        $header .= str_pad( str_replace(".", "", (string)round($importe_total, 2)), 10, "0", STR_PAD_LEFT ); //Convertir numeros altos a solo numeros: var_dump( str_pad( str_replace(".", "", (string)round(45797979798797111.2354145689, 2)), 10, "0", STR_PAD_LEFT ) ); =>>>> string(18) "45797979798797E+16"
        $header .= $fecha_acreditacion->format('Ymd');
        $header .= self::HEADER_RELLENO;
        
        
        return strlen($header) === 150 ? $header : false;
    }
    
    /**
     * Genera string correspondiente al footer del archivo de retiro para Transferencia - Pago a Proveedores.
     * @param int $cant_registros La cantidad de registros que va contener el archivo.
     * @return string/boolean Retorna false en caso de error, null en caso que falle la validacion.
     */
    public function generar_footer($cant_registros){
        //validacion
        if(!($cant_registros > 0) OR !($cant_registros<= 9999999)){
            return NULL;
        }
        
        //construccion footer
        $footer = "";
        $footer .= self::FOOTER_TIPO_REGISTRO;
        $footer .= self::HEADER_FOOTER_COD_EMPRESA;
        $footer .= str_pad((string)$cant_registros, 7, '0', STR_PAD_LEFT);
        $footer .= self::FOOTER_RELLENO;
        
        return (strlen($footer) === 150) ? $footer : false;
       /* 
//      $importe_total= $this->calcular_importe_total($recordset);
        $linea.=self::TIPO_REGISTRO_FOOTER.self::COD_EMPRESA_FOOTER. str_pad($cantidad, self::LONGITUD_CANT_REGISTROS, self::RELLENO_CANT_REGISTROS);
        $linea.= str_pad("", self::LONGITUD_FILLER_FOOTER, self::RELLENO_FILLER_FOOTER);
        return $linea;*/
    }
    
    /**
     * Genera string correspondiente a un registro de detalle del archivo de retiro para Transferencia - Pago a Proveedores.
     * @param string $razon_social Nombre o Razón Social.
     * @param string $cuit Cuit del responsable de la cuenta de crédito.
     * @param DateTime $fecha_acred Fecha en la que se deberían acreditar los fondos.
     * @param string $tipo_cuenta C (Cuenta corriente) y A (Caja de Ahorro).
     * @param string $cbu CBU de la cuenta de crédito.
     * @param double $importe Importe del crédito.
     * @param string $referencia Referencia unívoca. Hasta 15 caracteres. Uso gral es el mercalpha.
     * @param string $id_cliente Identificación del cliente. Hasta 22 caracteres. Uso gral es id_move.
     * @param string $fecha_mov Fecha en que se remitió el movimiento.
     * @param string $moneda Moneda de la cuenta de crédito (1 $ y 2 U$S). Valor por defecto Pesos.
     * @param string $tipo_transac Tipo de transacción. 1 - Acreditamiento de Sueldo 2 - Pago a Proveedores. Valor por defecto 2.
     * @return string/bool Retorna false en caso de error, null en caso que falle la validacion.
     */
    public function generar_registro_detalle(
        $razon_social,
        $cuit,
        $fecha_acred,
        $tipo_cuenta,
        $cbu,
        $importe,
        $referencia,
        $id_cliente,
        $fecha_mov,
        $moneda=self::TIPO_MONEDA_PESOS,
        $tipo_transac=self::REGISTRO_TIPO_TRANSACCION_PAGO_PROVEEDORES 
    ){
        //validacion
        /*if(!$this->validarRazonSocial($razon_social) 
               OR !$this->validarCUIT($cuit)
               OR !$this->validarFechaAcreditacion($fecha_acred)
               OR !$this->validarTipoCuenta($tipo_cuenta)
               OR !$this->validarCBU($cbu)
               OR !$this->validarImporte($importe)
               OR !$this->validarReferencia($referencia)
               OR !$this->validarIdCliente($id_cliente)
               OR !$this->validarFechaMov($fecha_mov)
               OR !$this->validarMoneda($moneda)
               OR !$this->validarTipoTransaccion($tipo_transac)*/
        if(!$this->validarEntradaParaGenerarRegistroTranf($razon_social, $cuit, $fecha_acred, $tipo_cuenta, $cbu, $importe, $referencia, $id_cliente, $fecha_mov, $moneda, $tipo_transac)){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->No se puede construir el registro por error de validacion. Se retorna NULL!");
            return NULL;
        }

        //construccion registro
        $registro = "";
        $razon_social = trim(preg_replace('/[^A-Za-z ]/', '', quitar_acentos($razon_social)));
        $registro .= str_pad(substr($razon_social, 0, 16), 16, ' ', STR_PAD_RIGHT);
        $registro .= $cuit;
        $registro .= $fecha_acred->format('Ymd');
        $registro .= $tipo_cuenta;
        $registro .= $moneda;
        $registro .= self::REGISTRO_FOLIO;
        $registro .= self::REGISTRO_DIGITO1;
        $registro .= self::REGISTRO_SUCURSAL;
        $registro .= self::REGISTRO_DIGITO2;
        $registro .= $this->formatearCBUGaliciaRetiro($cbu);
        $registro .= self::REGISTRO_COD_TRANSACCION;
        $registro .= $tipo_transac;
        $registro .= $this->formatearImporte((float)$importe);
        $registro .= str_pad(substr($referencia, 0, 15), 15, ' ', STR_PAD_RIGHT);
        $registro .= str_pad(substr($id_cliente, 0, 22), 22, ' ', STR_PAD_RIGHT);
        $registro .= $fecha_mov->format('Ymd');
        $registro .= self::REGISTRO_RELLENO;
        
        
        if(strlen($registro) === 150){
            return $registro;
            
        }else{
            error_log("(!)Error > registro_galicia_retiros.php > generar_registro_detalle() > Registro no validado>>>>" . json_encode($registro));
            return false;
        }
    }
    
    protected function formatearImporte($importe) {
        if(is_float($importe) AND $importe <= 99999999.99 AND $importe > 0.00){
            $nro = preg_replace('/[^0-9]+/','', money_format('%!=0#8.2n', $importe)); //Formatea el numero a 2 decimales y con 8 espacios para la parte entera. Luego quita todo caracter que no sea numero.
            
            return strlen($nro) === 10 ? $nro : false;
        }
        
        return NULL;
    }
    
    protected function validarEntradaParaGenerarRegistroTranf(
        $razon_social,
        $cuit,
        $fecha_acred,
        $tipo_cuenta,
        $cbu,
        $importe,
        $referencia,
        $id_cliente,
        $fecha_mov,
        $moneda,
        $tipo_transac
    ){      
        
        return $this->validarRazonSocial($razon_social) 
               AND $this->validarCUIT($cuit)
               AND $this->validarFechaAcreditacion($fecha_acred)
               AND $this->validarTipoCuenta($tipo_cuenta)
               AND $this->validarCBU($cbu)
               AND $this->validarImporte($importe)
               AND $this->validarReferencia($referencia)
               AND $this->validarIdCliente($id_cliente)
               AND $this->validarFechaMov($fecha_mov)
               AND $this->validarMoneda($moneda)
               AND $this->validarTipoTransaccion($tipo_transac);
    }
    
    public function validarTipoCuenta($tipo_cuenta) {
        if(empty($tipo_cuenta) OR !in_array($tipo_cuenta, [self::TIPO_CUENTA_CAJA_AHORRO, self::TIPO_CUENTA_CTA_CTE])){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->validarTipoCuenta()->No se valida tipo cuenta.>>>> Datos: " . json_encode($tipo_cuenta));
            
            return false;
        }
        
        return true;
    }
    
    public function validarRazonSocial($razon_social) {
        if(empty($razon_social) OR !is_string($razon_social)){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->ValidarRazonSocial()->No se valida razon social.>>>> Datos: " . json_encode($razon_social));
            
            return false;
        }
        
        return true;
    }
    
    public function validarCUIT($cuit) {
        if(empty($cuit) OR !((new Utilidades_dom())->validarCUIT($cuit)) ){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->validarCUIT->No se valida CUIT.>>>> Datos: " . json_encode($cuit));
            
            return false;
        }
        
        return  true;
    }
    
    public function validarCBU($cbu) {
        $utilDom = new Utilidades_dom();
        
        if(empty($cbu) OR !$utilDom->validarCBU($cbu)){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->validarCBU()->No se valida CBU.>>>> Datos: " . json_encode($cbu));
            
            return false;
        }
        
        return true;
    }
    
    public function validarImporte($importe) {
        if($importe > 99999999.99 OR $importe < 0.0){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->validarImporte()->No se valida importe.>>>> Datos: " . json_encode($importe));
            
            return false;
        }
        
        return true;
    }
    
    public function validarReferencia($referencia) {
        if(!is_string($referencia)){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->validarReferencia()->No se valida referencia.>>>> Datos: " . json_encode($referencia));
            
            return false;
        }
        
        return true;
    }    
    
    public function validarIdCliente($id_cliente) {
        //$id = preg_replace('/[^0-9]/', '', $id_cliente);
        if(empty($id_cliente) OR preg_match('/^[0-9]+$/', $id_cliente) == false){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->validarIdCliente()->No se valida ID.>>>> Datos: " . json_encode($id_cliente));
            
            return false;
        }
        
        return true;
    }
    
    public function validarFechaMov($fecha_mov) {       
        if(!is_a($fecha_mov, 'DateTime')){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->validarFechaMov->No se valida fecha movimiento.>>>> Datos: " . json_encode($fecha_mov));
            
            return false;
        }
        
        return true;
    }
    
    public function validarFechaAcreditacion($fecha_acred) {
        if(!is_a($fecha_acred, 'DateTime')){
            $debug = debug_backtrace();
            error_log("(!)".$debug[1]['file']."->".$debug[1]['function']."->".$debug[0]['function']."->No se valida fecha acreditacion.>>>> Datos: " . json_encode($debug[0]['args']));
            
            return false;
        }
        
        return true;
    }
    
    public function validarMoneda($moneda) {
        if(empty($moneda) OR !in_array($moneda, [self::TIPO_MONEDA_PESOS, self::TIPO_MONEDA_DOLAR], true)){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->validarMoneda()->No se valida el monto.>>>> Datos: " . json_encode($moneda));
            
            return false;
        }
       
        return true;
    }
    
    public function validarTipoTransaccion($tipo_transac) {
        if(empty($tipo_transac) OR !in_array($tipo_transac, [self::REGISTRO_TIPO_TRANSACCION_ACRED_SUELDO, self::REGISTRO_TIPO_TRANSACCION_PAGO_PROVEEDORES])){
            error_log("(!)Registro_retiros_galicia->generar_registro_tranf()->validarTipoTransaccion()->No se valida tipo transaccion.>>>> Datos: " . json_encode($tipo_transac));
            
            return false;
        }
       
        return true;
    }
    
    public function formatearCBUGaliciaRetiro($cbu) {

        if(is_string($cbu) AND strlen($cbu)=== 22 ){
            $bloque1 = str_pad(substr($cbu, 0, 8), 9, '0', STR_PAD_LEFT);
            $bloque2 = str_pad(substr($cbu, 8, 14), 17, '0', STR_PAD_LEFT);
            
            return strlen($bloque1 . $bloque2) === 26 ? $bloque1.$bloque2 : FALSE;
        }
        
        return NULL;
    }
}
    
