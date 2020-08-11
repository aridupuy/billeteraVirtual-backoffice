<?php

class Registro_galicia extends Registro {

    const ACTIVAR_DEBUG = true;
    const CARACTERES_POR_FILA = 350;
    const INICIO_CODIGO_ELECTRONICO = 4;
    const INICIO_MONTO = 82;
    const LONGITUD_MONTO = 7; # Debe ser 7 para coincidir con barcode
    const DECIMALES_MONTO = 2; # Debe ser 2 para coincidir con barcode
    const INICIO_FECHA_DE_PAGO = 67;
    const LONGITUD_FECHA_DE_PAGO = 8;
    const FORMATO_FECHA = '!Ymd';
    const FORMATO_FECHA_OUTPUT = 'Ymd';
    const TIPO_REGISTRO_DEBITO_EFECTUADO = '0370';
    const TIPO_REGISTRO_REVERSO_RECHAZADO = '0310';
    const TIPO_REGISTRO_DEBITO_RECHAZADO = '0360';
    const TIPO_REGISTRO_DEBITO_REVERSADO = '0371';
    const TIPO_REGISTRO_DEBITO_REVERSADO_DOS = '0320';

    #######identificacion de campos en la base de datos y configurador de filas
    const INICIO_CBU_PARTE_1=0;
    const FIN_CBU_PARTE_1=8;
    const INICIO_DIGITO_VERIFICADOR_1=8;
    const LONGITUD_DIGITO_VERIFICADOR_1=1;
    const INICIO_CBU_PARTE_2=9;
    const FIN_CBU_PARTE_2=15;
    const INICIO_DIGITO_VERIFICADOR_2=24;
    const LONGITUD_DIGITO_VERIFICADOR_2=1;
    const INICIO_REFERENCIA_UNIVOCA=0;
    const LONGITUD_FECHA_1 = "8";
    const RELLENO_FECHA_1 = "0";
    const LONGITUD_MONTO_1 = "14";
    const RELLENO_MONTO_1 = "0";
    const ALINEACION_MONTO_1 = "0"; #STR_PAD_LEFT
    const LONGITUD_FECHA_2 = "8";
    const RELLENO_FECHA_2 = "0";
    const LONGITUD_MONTO_2 = "14";
    const RELLENO_MONTO_2 = "0";
    const ALINEACION_MONTO_2 = "0"; #STR_PAD_LEFT
    const LONGITUD_FECHA_3 = "8";
    const RELLENO_FECHA_3 = "0";
    const RELLENO_MONTO_3 = "0";
    const LONGITUD_MONTO_3 = "14";
    const ALINEACION_MONTO_3 = "0"; #STR_PAD_LEFT
    const LONGITUD_IMPORTE_MINIMO=14;
    const DECIMALES=2;
    const LONGITUD_DNI = "15";
    const ALINEACION_DNI = 1;
    const LONGITUD_CONCEPTO = "10";
    const RELLENO_DNI = "0";
    const ALINEACION_CONCEPTO = 1;
    const ALINEACION_MONTO_MINIMO="0";
    const INICIO_MENSAJE_ATM=0;
    const ALINEACION_MENSAJE_ATM="1";
    const LONGITUD_MENSAJE_ATM="40";
    const MONEDA = '0'; #PESOS#;
    const LONGITUD_MOTIVO_RECHAZO=3;
    const INICIO_NUEVA_ID_CLIENTE=22;
    const LONGITUD_NUEVA_CBU=26;
    const LONGITUD_PROXIMO_VENCIMIENTO=8;
    const LONGITUD_IDENTIFICACION_CLIENTE_ANTERIOR=22;
    const LONGITUD_FECHA_DE_COBRO=8;
    const LONGITUD_IMPORTE_COBRADO=14;
    const LONGITUD_FECHA_DE_ACREDITACION=8;
    const CARACTERES_LIBRES=26;

    const SEG_VTO = true;
    const TERC_VTO = true;

    ############################################################################

    public function obtener_tipo_registro() {

        return substr($this->fila, 0, 4);
    }

    public function obtener_codigo_de_rechazo() {
        if(!in_array($this->obtener_tipo_registro(), array(self::TIPO_REGISTRO_REVERSO_RECHAZADO,self::TIPO_REGISTRO_DEBITO_RECHAZADO))) {
            return '';
        }
        return substr($this->fila, 134, 3);
    }

    public function obtener_descripcion_de_rechazo() {
        if(!in_array($this->obtener_tipo_registro(), array(self::TIPO_REGISTRO_REVERSO_RECHAZADO,self::TIPO_REGISTRO_DEBITO_RECHAZADO))) {
            return '';
        }
        if($this->obtener_codigo_de_rechazo()=='R90'){
            developer_log("OBTENIENDO EMAIL DE MARCHAND POR BARCODE");
            $codebar= $this->obtener_codigo_de_barras();
            $barcodes= Barcode::select(array("barcode"=>$codebar));
            if($barcodes  AND ($barcode=$barcodes->fetchRow())){
                $idm=$barcode['id_marchand'];
                $marchand=new Marchand();
                $marchand->get($idm);
                $email=$marchand->get_email();
            }
            else
                throw new Exception("No se pudo obtener el barcode para el mail");
            
            $asunto="Fallo de reverso";
            $fecha=$this->obtener_fecha_de_pago_datetime();
            $mensaje="El reverso para el día ".$fecha->format("d-m-Y")." No se pudo realizar ya que pasaron mas de 30 días desde la fecha de impacto. Le pedimos disculpas por las molestias ocasionadas.";
            developer_log($mensaje);
            //Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, $email, $asunto, $mensaje);
            throw new Exception("El reverso R90 fallo enviado el mail.");
        }
        switch ($this->obtener_codigo_de_rechazo()) {
            case 'R02': $descerror = 'Cuenta cerrada o suspendida';
                break;
            case 'R03': $descerror = 'Cuenta inexistente';
                break;
            case 'R04': $descerror = 'Número de cuenta inválido';
                break;
            case 'R05': $descerror = 'Orden de diferimiento';
                break;
            case 'R06': $descerror = 'Defectos formales';
                break;
            case 'R07': $descerror = 'Solicitud de la Entidad Originante';
                break;
            case 'R08': $descerror = 'Orden de no pagar';
                break;
            case 'R10': $descerror = 'Falta de fondos';
                break;
            case 'R13': $descerror = 'Entidad destino inexistente';
                break;
            case 'R14': $descerror = 'Identificación del Cliente de la Empresa errónea';
                break;
            case 'R15': $descerror = 'Baja del servicio';
                break;
            case 'R17': $descerror = 'Error de formato';
                break;
            case 'R19': $descerror = 'Importe erróneo';
                break;
            case 'R20': $descerror = 'Moneda distinta a la cuenta de débito';
                break;
            case 'R23': $descerror = 'Sucursal no habilitada';
                break;
            case 'R24': $descerror = 'Transacción duplicada';
                break;
            case 'R25': $descerror = 'Error en registro adicional';
                break;
            case 'R26': $descerror = 'Error por campo mandatario';
                break;
            case 'R28': $descerror = 'Rechazo primer vencimiento';
                break;
            case 'R29': $descerror = 'Reversión ya efectuada';
                break;
            case 'R75': $descerror = 'Fecha inválida';
                break;
            case 'R81': $descerror = 'Fuerza mayor';
                break;
            case 'R87': $descerror = 'Moneda inválida';
                break;
            case 'R89': $descerror = 'Errores en adhesiones';
                break;
            case 'R91': $descerror = 'Código de Banco incompatible con moneda de transacción';
                break;
            case 'R95': $descerror = 'Reversión receptora presentada fuera de término';
                break;
            default: $descerror = 'Error no documentado';
                break;
        }
        return $descerror . '. ';
    }

    public function obtener_codigo_de_barras() {
        
//        $codigo_de_barras = PREFIJO_CODIGO_DE_BARRAS;
        $id_debito=$this->obtener_codigo_electronico();
//        $codigo_de_barras.=$this->obtener_monto();
//        $codigo_de_barras.=Barcode::calcular_digito_verificador($codigo_de_barras);
        return $id_debito;
    }
    public function obtener_referencia_univoca(){
//	var_dump(substr($this->fila, 52, 15));
	
        return substr($this->fila, 52, 15);
    }
    # Sobreescribo el metodo

    public function obtener_monto_numerico_transaccionado() {
        # Usado en monto_pagador
        $monto = false;
        switch ($this->obtener_tipo_registro()) {
            case self::TIPO_REGISTRO_DEBITO_RECHAZADO: $monto = 0;
                break;
            default: $monto = $this->obtener_monto_numerico();
                break;
        }
        return $monto;
    }

    public function obtener_estado_a_insertar_sabana() {
        $estado = false;
        switch ($this->obtener_tipo_registro()) {
            case self::TIPO_REGISTRO_DEBITO_EFECTUADO: $estado = Authstat::SABANA_ENTRANDO;
                break;
            case self::TIPO_REGISTRO_DEBITO_RECHAZADO: $estado = Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO;
                break;
            case self::TIPO_REGISTRO_DEBITO_REVERSADO: $estado = Authstat::SABANA_DEBITO_A_REVERTIR;
                break;
            case self::TIPO_REGISTRO_DEBITO_REVERSADO_DOS: $estado = Authstat::SABANA_DEBITO_A_REVERTIR;
                break;
        }
        return $estado;
    }
    public function obtener_id_mp() {
        $id_mp = false;
        switch ($this->obtener_tipo_registro()) {
            # Incosisitencias
            case self::TIPO_REGISTRO_DEBITO_EFECTUADO: $id_mp = Mp::DEBITO_AUTOMATICO_CBU;
                break;
            case self::TIPO_REGISTRO_REVERSO_RECHAZADO: $id_mp = Mp::DEBITO_AUTOMATICO_CBU;
                break;
            case self::TIPO_REGISTRO_DEBITO_RECHAZADO: $id_mp = Mp::DEBITO_AUTOMATICO_COSTO_RECHAZO;
                break;
            case self::TIPO_REGISTRO_DEBITO_REVERSADO: $id_mp = Mp::DEBITO_AUTOMATICO_REVERSO;
                break;
            case self::TIPO_REGISTRO_DEBITO_REVERSADO_DOS: $id_mp = Mp::DEBITO_AUTOMATICO_REVERSO;
                break;
        }
        return $id_mp;
    }
    public function obtener_fecha_pago1_datetime($id_debito=false)
	{
            error_log("fecha_pago1_datetime");
//            $this->obtener_fecha_de_pago()
//		if(!id_debito)
//			return $this->obtener_fecha_pago();
            return Debito_cbu::obtener_fecha_de_vencimiento_datetime($id_debito);
	}
    public function generar_fila( $monto_1, $identificador_cliente, $cbu, $referencia_univoca, $fecha_1, $tipodoc = null, $dni = null, $concepto_factura = null,$importe_minimo = null,$mensaje_atm = null, $monto_2 = null, $fecha_2 = null, $monto_3 = null, $fecha_3 = null) {
        $salto_de_linea = "\n";
        $linea = "";
        #monto= numeric
        $monto_1 = str_replace(".", "", "" . number_format($monto_1, 2));
        $monto_1 = str_replace(",", "", $monto_1);
        $fecha_1 = new datetime($fecha_1);
        #mejor si esta seteado monto y fecha
        if ($monto_2!=null AND $fecha_2!=null) {
            $monto_2 = str_replace(".", "", "" . number_format($monto_2, self::DECIMALES));
            $monto_2 = str_replace(",", "", $monto_2);
            $fecha_2 = DateTime::createFromFormat(self::FORMATO_FECHA,$fecha_2);
        }
        if ($monto_3!=null AND $fecha_3!=null) {
            $monto_3 = str_replace(".", "", "" . number_format($monto_3, self::DECIMALES));
            $monto_3 = str_replace(",", "", $monto_3);
            $fecha_3 = DateTime::createFromFormat(self::FORMATO_FECHA,$fecha_3);
        }
        if ($importe_minimo!=null) {
            $importe_minimo= str_replace(".", "", "" . number_format($importe_minimo, self::DECIMALES));
            $importe_minimo= str_replace(",", "", $importe_minimo);
        }
        $linea.=self::TIPO_REGISTRO_DEBITO_EFECTUADO;
        //370 para nuevos debitos deveriamos mandar tambien 0360?
        $identificador_cliente= str_pad($identificador_cliente, 19,"0",STR_PAD_LEFT);
        $linea.=str_pad($identificador_cliente, ordenador_galicia::LONGITUD_IDENTIFICADOR_CLIENTE, ordenador_galicia::RELLENO_IDENTIFICADOR_CLIENTE, ordenador_galicia::ALINEACION_IDENTIFICADOR_CLIENTE);
        $parte_1=  substr($cbu, self::INICIO_CBU_PARTE_1, self::FIN_CBU_PARTE_1);
        $digito_verificador_1=  substr($cbu, self::INICIO_DIGITO_VERIFICADOR_1,self::LONGITUD_DIGITO_VERIFICADOR_1);
        $parte_2=  substr($cbu, self::INICIO_CBU_PARTE_2, self::FIN_CBU_PARTE_2);
        $digito_verificador_2=  substr($cbu, self::INICIO_DIGITO_VERIFICADOR_2, self::LONGITUD_DIGITO_VERIFICADOR_2);
        $parte_1=str_pad($parte_1, ordenador_galicia::LONGITUD_CBU_PARTE_1, ordenador_galicia::RELLENO_CBU, STR_PAD_LEFT);
        $digito_verificador_1=str_pad($digito_verificador_1, ordenador_galicia::LONGITUD_CBU_DIGITO_VERIFICADOR_1, ordenador_galicia::RELLENO_CBU, STR_PAD_LEFT);
        $parte_2=str_pad($parte_2, ordenador_galicia::LONGITUD_CBU_PARTE_2, ordenador_galicia::RELLENO_CBU, ordenador_galicia::ALINEACION_CBU);
        $digito_verificador_2=str_pad($digito_verificador_2, ordenador_galicia::LONGITUD_CBU_DIGITO_VERIFICADOR_2, ordenador_galicia::RELLENO_CBU, STR_PAD_LEFT);
        $cbu=$parte_1.$digito_verificador_1.$parte_2.$digito_verificador_2;
        $linea.=str_pad($cbu, ordenador_galicia::LONGITUD_REFERENCIA_CBU, ordenador_galicia::RELLENO_CBU, STR_PAD_RIGHT);
        $referencia_univoca=  str_replace("-", "", $referencia_univoca);
        $referencia_univoca=  str_replace(" ", "", $referencia_univoca);
        $referencia_univoca=  str_replace("/", "", $referencia_univoca);
        $referencia_univoca=  str_replace("*", "", $referencia_univoca);
        $referencia_univoca= quitar_acentos($referencia_univoca);
        
        $linea.=str_pad_utf8(substr($referencia_univoca, self::INICIO_REFERENCIA_UNIVOCA, ordenador_galicia::LONGITUD_REFERENCIA_UNIVOCA),ordenador_galicia::LONGITUD_REFERENCIA_UNIVOCA, ordenador_galicia::RELLENO_REFERENCIA_UNIVOCA, ordenador_galicia::ALINEACION_REFERENCIA_UNIVOCA);
        $linea.=$fecha_1->format(self::FORMATO_FECHA_OUTPUT);
        $linea.=str_pad($monto_1, self::LONGITUD_MONTO_1, self::RELLENO_MONTO_1, self::ALINEACION_MONTO_1);
        if ($monto_2!=null AND $fecha_2!=null) {
            $linea.=$fecha_2->format(self::FORMATO_FECHA_OUTPUT);
            $linea.=str_pad($monto_2, self::LONGITUD_MONTO_2, self::RELLENO_MONTO_2, self::ALINEACION_MONTO_2);
        } else {
            $linea.=str_pad("", self::LONGITUD_FECHA_2, self::RELLENO_FECHA_2);
            $linea.=str_pad("", self::LONGITUD_MONTO_2, self::RELLENO_MONTO_2, self::ALINEACION_MONTO_2);
        }
        if ($monto_3!=null AND $fecha_3!=null) {
            $linea.=$fecha_3->format(self::FORMATO_FECHA_OUTPUT);
            $linea.=str_pad($monto_3, self::LONGITUD_MONTO_3, self::RELLENO_MONTO_3, self::ALINEACION_MONTO_3);
        } else {
            $linea.=str_pad("", self::LONGITUD_FECHA_3, self::RELLENO_FECHA_3);
            $linea.=str_pad("", self::LONGITUD_MONTO_3, self::RELLENO_MONTO_3, self::ALINEACION_MONTO_3);
        }

        $linea.=self::MONEDA;
        $linea.=str_pad("", self::LONGITUD_MOTIVO_RECHAZO, ' '); #motivo del rechazo va vacio.
        $tipodoc = $this->obtener_codigo_tipodoc($tipodoc);
        if ($tipodoc != null AND $dni != null) {
            $linea.=str_pad($tipodoc . $dni, self::LONGITUD_DNI, self::RELLENO_DNI, self::ALINEACION_DNI);
        }
        else {
            if (self::ACTIVAR_DEBUG)
                developer_log("|| No informo Documento.");
            $linea.=str_pad("", self::LONGITUD_DNI, '0'); #Tipo y numero de dni no se informa podria informarce. 
        }
        $linea.=str_pad("", self::INICIO_NUEVA_ID_CLIENTE , ' ', STR_PAD_RIGHT); #Nueva id del cliente. 
        $linea.=str_pad("", self::LONGITUD_NUEVA_CBU, '0', STR_PAD_RIGHT); #nueva cbu. 
        if($importe_minimo!=null)
            $linea.=str_pad($importe_minimo, 14, '0', self::ALINEACION_MONTO_MINIMO); #importe minimo. 
        else
            $linea.=str_pad("", self::LONGITUD_IMPORTE_MINIMO, self::RELLENO_MONTO_1,self::ALINEACION_MONTO_MINIMO); #importe minimo. 
        $linea.=str_pad("", self::LONGITUD_PROXIMO_VENCIMIENTO, ' ', STR_PAD_RIGHT); #fecha proximo vencimineto. 
        $linea.=str_pad("", self::LONGITUD_IDENTIFICACION_CLIENTE_ANTERIOR, ' ', STR_PAD_RIGHT); #identificacion cliente anterior 
        if($mensaje_atm!=null){
            $mensaje_atm= substr($mensaje_atm, self::INICIO_MENSAJE_ATM,self::LONGITUD_MENSAJE_ATM);
            $linea.=str_pad_utf8($mensaje_atm, self::LONGITUD_MENSAJE_ATM, ' ', self::ALINEACION_MENSAJE_ATM); #MENSAJE ATM mensaje que llega al cajero automático
        }
        else
            $linea.=str_pad("", self::LONGITUD_MENSAJE_ATM, ' ', STR_PAD_RIGHT); #MENSAJE ATM
        if ($concepto_factura != null)
            if (strlen($concepto_factura) > self::LONGITUD_CONCEPTO) {
                $concepto_factura = substr($concepto_factura, 0, self::LONGITUD_CONCEPTO - 1);
                $linea.=str_pad_utf8($concepto_factura, self::LONGITUD_CONCEPTO, ' ', self::ALINEACION_CONCEPTO);
            } else
                $linea.=str_pad_utf8($concepto_factura, self::LONGITUD_CONCEPTO, ' ', self::ALINEACION_CONCEPTO);
        else
            $linea.=str_pad_utf8("", self::LONGITUD_CONCEPTO, ' ', self::ALINEACION_CONCEPTO);
        $linea.=str_pad("", self::LONGITUD_FECHA_DE_COBRO, ' ', STR_PAD_RIGHT); #fecha de cobro
        $linea.=str_pad("", self::LONGITUD_IMPORTE_COBRADO, ' ', STR_PAD_RIGHT); #importe cobrado. 
        $linea.=str_pad("", self::LONGITUD_FECHA_DE_ACREDITACION, ' ', STR_PAD_RIGHT); #fecha de  acreditacion. 
        $linea.=str_pad("", self::CARACTERES_LIBRES, ' ', STR_PAD_RIGHT); # caracteres libres.
        $linea.=$salto_de_linea;
        $this->fila = $linea;
        return $this;
    }

    private function obtener_codigo_tipodoc($tipodoc) {
        switch ($tipodoc) {
            case 1:
                return "0086"; #cuil #"0087" cuit
            case 2:
                return "0096"; #dni
            case 3:
                return "0000"; #Ci policia federal;
            case 4:
                developer_log("||Galicia no admite identificación por C.B.U.");
                return null;
            case 6:
                return "0094"; #Pasaporte
            case 7:
                return "0090"; #LC
            case 8:
                return "0089"; #LE
            case 10:
                return "0001"; #ci Buenos aires;
        }
    }
    protected function Procesar_cbu($cbu){
        
        return $cbu;
        
    }
}
