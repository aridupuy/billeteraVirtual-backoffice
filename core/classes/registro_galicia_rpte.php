<?php

class Registro_galicia_rpte extends Registro {

    const ACTIVAR_DEBUG = true;
    
    const FORMATO_FECHA = '!Ymd';
    const FORMATO_FECHA_OUTPUT = 'Ymd';
    
    const IMPORTE="IMPORTE DEL VTO"; 
    const OBSERVACIONES="OBSERVACIONES"; 
    const RECHAZO="IMPORTE RECHAZADO"; 
    const DECIMALES_MONTO=2;
    const IDENTIFICADOR="IDENTIFICADOR";
    const REFERENCIA="REFERENCIA";
    const IMPORTE_RECHAZADO="IMPORTE RECHAZADO";
    const TIPO_REGISTRO_DEBITO_EFECTUADO = '0370';
    const TIPO_REGISTRO_REVERSO_RECHAZADO = '0310';
    const TIPO_REGISTRO_DEBITO_RECHAZADO = '0360';
    const TIPO_REGISTRO_DEBITO_REVERSADO = '0371';
    const TIPO_REGISTRO_DEBITO_REVERSADO_DOS = '0320';
    const FECHA_VTO ="FECHA VTO";
    ############################################################################
    public function obtener_fecha_de_pago()
    {
        return $this->fila[self::FECHA_VTO];	
    }
    public function obtener_tipo_registro() {
//        var_dump(trim($this->fila[self::IMPORTE_RECHAZADO]));
        if(trim($this->fila[self::IMPORTE_RECHAZADO])!="")
            return "0360";
        return "0370";
    }

    public function obtener_codigo_de_rechazo() {
        if(isset($this->fila[self::RECHAZO]) and $this->fila[self::RECHAZO]!="")
            return $this->fila[self::OBSERVACIONES];
        return "";
//        return substr($this->fila, 134, 3);
    }
    public function obtener_monto()
    {
        return $this->fila[self::IMPORTE];	
    }
    public function obtener_descripcion_de_rechazo() {
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
    public function obtener_codigo_electronico() //no es un codigo electronico pero para mantener la herencia sirve
    {
            return $this->fila[self::IDENTIFICADOR];
    }
    public function obtener_referencia_univoca(){
//	var_dump(substr($this->fila, 52, 15));
	
        return $this->fila[self::REFERENCIA];
    }
    # Sobreescribo el metodo

    public function obtener_monto_numerico_transaccionado() {
        # Usado en monto_pagador
        return $this->obtener_monto_numerico();
        
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
   

   /* private function obtener_codigo_tipodoc($tipodoc) {
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
    }*/
    protected function Procesar_cbu($cbu){
        
        return $cbu;
        
    }
}
