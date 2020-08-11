<?php

class Registro_cheque extends Registro {
    const VERSION_RP = "PPV4";
    const VERSION_PPRV = "PPRV4";
    const FORMA_PAGO = 0; // 0 = Cheque
    const MONEDA = "ARP";
    const ID_MP = 116;
    const INICIO_DOCUMENTO_ORDENANTE = 7;
    const LONGITUD_DOCUMENTO_ORDENANTE = 11;
    const INICIO_MONTO = 56;
    const LONGITUD_MONTO = 18;
    const INICIO_FECHA_PAGO = 82;
    const LONGITUD_FECHA_PAGO = 8;
    const INICIO_COMPROBANTE_PAGO = 90;
    const LONGITUD_COMPROBANTE_PAGO = 12;
    const INICIO_TIPO_DOCUMENTO = 177;
    const LONGITUD_TIPO_DOCUMENTO = 2;
    const INICIO_DOCUMENTO = 179;
    const LONGITUD_DOCUMENTO = 11;
    const INICIO_ESTADO_ITEM = 215;
    const LONGITUD_ESTADO_ITEM = 1;
    const INICIO_ESTADO_GENERAL_CHEQUE= 216;
    const LONGITUD_ESTADO_GENERAL_CHEQUE = 3;
    const INICIO_ESTADO_CHEQUE = 311;
    const LONGITUD_ESTADO_CHEQUE = 2;
    const INICIO_MARCA_ENTREGA = 313;
    const LONGITUD_MARCA_ENTREGA = 1;
    const INICIO_SUCURSAL = 321;
    const LONGITUD_SUCURSAL = 4;
    const INICIO_FECHA_ENTREGA = 325;
    const LONGITUD_FECHA_ENTREGA = 8;
    
    ############################################################################

    public function obtener_tipo_registro() {
        return substr($this->fila, 0, 5);
    }
    
    public function obtener_documento_ordenante(){
        return substr($this->fila, self::INICIO_DOCUMENTO_ORDENANTE, self::LONGITUD_DOCUMENTO_ORDENANTE);
    }
    
    public function obtener_monto(){
        $monto = substr($this->fila, self::INICIO_MONTO, self::LONGITUD_MONTO);
        return (int)$monto/100;
    }
    
    public function obtener_fecha_de_pago_cheque(){
        return substr($this->fila, self::INICIO_FECHA_PAGO, self::LONGITUD_FECHA_PAGO);
    }
    
    public function obtener_id_cheque_por_sucursal(){
        return substr($this->fila, self::INICIO_COMPROBANTE_PAGO, self::LONGITUD_COMPROBANTE_PAGO);
    }
    
    public function obtener_tipo_documento(){
        return substr($this->fila, self::INICIO_TIPO_DOCUMENTO, self::LONGITUD_TIPO_DOCUMENTO);
    }
    
    public function obtener_documento(){
        return substr($this->fila, self::INICIO_DOCUMENTO, self::LONGITUD_DOCUMENTO);
    }
    
    public function obtener_estado_item(){
        return substr($this->fila, self::INICIO_ESTADO_ITEM, self::LONGITUD_ESTADO_ITEM);
    }
    
    public function obtener_estado_general_cheque(){
        return substr($this->fila, self::INICIO_ESTADO_GENERAL_CHEQUE, self::LONGITUD_ESTADO_GENERAL_CHEQUE);
    }
    
    public function obtener_estado_cheque(){
        return substr($this->fila, self::INICIO_ESTADO_CHEQUE, self::LONGITUD_ESTADO_CHEQUE);
    }
    
    public function obtener_marca_entrega(){
        return substr($this->fila, self::INICIO_MARCA_ENTREGA, self::LONGITUD_MARCA_ENTREGA);
    }
    
    public function obtener_cod_sucursal(){
        return substr($this->fila, self::INICIO_SUCURSAL, self::LONGITUD_SUCURSAL);
    }
    
    public function obtener_fecha_entrega(){
        return substr($this->fila, self::INICIO_FECHA_ENTREGA, self::LONGITUD_FECHA_ENTREGA);
    }
    
    public function registro_valido(){
        $valido = true;
        $mensaje = "Registro válido";

        $id_cheque_por_sucursal = $this->obtener_id_cheque_por_sucursal();
        $cheque_sucursal = new Cheque_por_sucursal();
        $cheque_sucursal->get($id_cheque_por_sucursal);
        
        $moves = new Moves();

        $sucursal = new Sucursal();
        
        if(!$sucursal->get($cheque_sucursal->get_id_sucursal())){
            $mensaje = 'No se encuentra la sucursal';
            $valido = false;
        }
        
        if($valido AND !$moves->get($cheque_sucursal->get_id_moves())){
            $mensaje = 'No se encuentra la transacción';
            $valido = false;
        }
        
        if($valido AND $moves->get_id_entidad() == Entidad::ENTIDAD_MARCHAND){
            $marchand = new Marchand();
            $marchand->get($moves->get_id_referencia());
        }else{
            $mensaje = 'Error al intentar obtener la transferencia';
            $valido = false;
        }
        
        if($valido AND $this->obtener_tipo_registro() != self::VERSION_PPRV){
            $mensaje = 'Código erróneo de rendición de pagos';
            $valido = false;
        }
        
            //hay que ver que el monto este en el mismo formato
        if($valido AND $this->obtener_monto() != $moves->get_monto_pagador()){
            $mensaje = 'Monto del cheque incorrecto';
            $valido = false;
        }
        
//        if($valido AND $this->obtener_fecha_de_pago() != $moves->get_monto_pagador()){
//            $mensaje = 'Monto del cheque incorrecto';
//            $valido = false;
//        }
        
        if($valido AND $this->obtener_documento() != $marchand->get_documento()){
            $mensaje = 'Documento del beneficiario erróneo';
            $valido = false;
        }
        
        if($valido AND $this->obtener_cod_sucursal() != $sucursal->get_codigo()){
            $mensaje = 'Sucursal errónea';
            $valido = false;
        }
        
        developer_log($mensaje);
        return $valido;
    }

//    public function obtener_codigo_de_rechazo() {
//        if(!in_array($this->obtener_tipo_registro(), array(self::TIPO_REGISTRO_REVERSO_RECHAZADO,self::TIPO_REGISTRO_DEBITO_RECHAZADO))) {
//            return '';
//        }
//        return substr($this->fila, 134, 3);
//    }
//
//    public function obtener_descripcion_de_rechazo() {
//        if(!in_array($this->obtener_tipo_registro(), array(self::TIPO_REGISTRO_REVERSO_RECHAZADO,self::TIPO_REGISTRO_DEBITO_RECHAZADO))) {
//            return '';
//        }
//        if($this->obtener_codigo_de_rechazo()=='R90'){
//            developer_log("OBTENIENDO EMAIL DE MARCHAND POR BARCODE");
//            $codebar= $this->obtener_codigo_de_barras();
//            $barcodes= Barcode::select(array("barcode"=>$codebar));
//            if($barcodes  AND ($barcode=$barcodes->fetchRow())){
//                $idm=$barcode['id_marchand'];
//                $marchand=new Marchand();
//                $marchand->get($idm);
//                $email=$marchand->get_email();
//            }
//            else
//                throw new Exception("No se pudo obtener el barcode para el mail");
//            
//            $asunto="Fallo de reverso";
//            $fecha=$this->obtener_fecha_de_pago_datetime();
//            $mensaje="El reverso para el día ".$fecha->format("d-m-Y")." No se pudo realizar ya que pasaron mas de 30 días desde la fecha de impacto. Le pedimos disculpas por las molestias ocasionadas.";
//            developer_log($mensaje);
//            //Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, $email, $asunto, $mensaje);
//            throw new Exception("El reverso R90 fallo enviado el mail.");
//        }
//        switch ($this->obtener_codigo_de_rechazo()) {
//            case 'R02': $descerror = 'Cuenta cerrada o suspendida';
//                break;
//            case 'R03': $descerror = 'Cuenta inexistente';
//                break;
//            case 'R04': $descerror = 'Número de cuenta inválido';
//                break;
//            case 'R05': $descerror = 'Orden de diferimiento';
//                break;
//            case 'R06': $descerror = 'Defectos formales';
//                break;
//            case 'R07': $descerror = 'Solicitud de la Entidad Originante';
//                break;
//            case 'R08': $descerror = 'Orden de no pagar';
//                break;
//            case 'R10': $descerror = 'Falta de fondos';
//                break;
//            case 'R13': $descerror = 'Entidad destino inexistente';
//                break;
//            case 'R14': $descerror = 'Identificación del Cliente de la Empresa errónea';
//                break;
//            case 'R15': $descerror = 'Baja del servicio';
//                break;
//            case 'R17': $descerror = 'Error de formato';
//                break;
//            case 'R19': $descerror = 'Importe erróneo';
//                break;
//            case 'R20': $descerror = 'Moneda distinta a la cuenta de débito';
//                break;
//            case 'R23': $descerror = 'Sucursal no habilitada';
//                break;
//            case 'R24': $descerror = 'Transacción duplicada';
//                break;
//            case 'R25': $descerror = 'Error en registro adicional';
//                break;
//            case 'R26': $descerror = 'Error por campo mandatario';
//                break;
//            case 'R28': $descerror = 'Rechazo primer vencimiento';
//                break;
//            case 'R29': $descerror = 'Reversión ya efectuada';
//                break;
//            case 'R75': $descerror = 'Fecha inválida';
//                break;
//            case 'R81': $descerror = 'Fuerza mayor';
//                break;
//            case 'R87': $descerror = 'Moneda inválida';
//                break;
//            case 'R89': $descerror = 'Errores en adhesiones';
//                break;
//            case 'R91': $descerror = 'Código de Banco incompatible con moneda de transacción';
//                break;
//            case 'R95': $descerror = 'Reversión receptora presentada fuera de término';
//                break;
//            default: $descerror = 'Error no documentado';
//                break;
//        }
//        return $descerror . '. ';
//    }

    public function generar_fila($row) {
        $salto_de_linea = "\n";
        $linea = "";
        
        $move = new Moves();
        $move->get($row['id_moves']);
        $marchand = new Marchand();
        $marchand->get($move->get_id_marchand());

        $linea = $linea . $this::VERSION_RP;
        $linea = $linea . $this::FORMA_PAGO;
        $linea = $linea . $this::MONEDA;
        $linea = $linea . $this->monto($move);
        $linea = $linea . $this->fecha($move);                  //Fecha pago
        $linea = $linea . $this->id_cheque_por_sucursal($row['id_cheque_por_sucursal']);                //Nro de conciliacion
        $linea = $linea . str_pad("", 1, " ", STR_PAD_RIGHT);   //Forma de cuenta
        $linea = $linea . str_pad("", 2, " ", STR_PAD_RIGHT);   //Tipo de cuenta
        $linea = $linea . str_pad("", 22, " ", STR_PAD_RIGHT);  // Nro de cuenta
        $linea = $linea . $this->nombre($marchand);
        $linea = $linea . $this->tipo_doc($marchand);
        $linea = $linea . $this->numero_doc($marchand);
        $linea = $linea . $this->calle($marchand);
        $linea = $linea . $this->altura($marchand);
        $linea = $linea . $this->localidad($marchand);
        $linea = $linea . $this->cod_post($marchand);
        $linea = $linea . $this->provincia($marchand);
        $linea = $linea . $this->numero_tel($marchand);
        $linea = $linea . $this->numero_beneficiario($move);    //Id_marchand
        $linea = $linea . str_pad("", 2 + 11 + 50, " ", STR_PAD_RIGHT);
        $linea = $linea . str_pad("", 2 + 11 + 30 + 43 + 43, " ", STR_PAD_RIGHT);
        $linea = $linea . str_pad("", 80, " ", STR_PAD_RIGHT);
        $linea = $linea . str_pad("", 5600, " ", STR_PAD_RIGHT);
        $linea = $linea . $this->xml($row['id_sucursal'], $marchand);
        $linea = $linea . $salto_de_linea;
        
        $this->fila = $linea;
        return $this;
    }
    
    private function monto(Moves $move){
        $value = str_replace(".", "", $move->get_monto_pagador());
        $value = str_pad($value, 18, "0", STR_PAD_LEFT);

        return $value;
    }
    
    private function fecha(Moves $move){
        $value = Datetime::createFromFormat(Cheque_por_sucursal::FORMATO_FECHA_FECHA_PAGO, $move->get_fecha());
        $value = $value->format('Ymd');
        
        return $value;
    }
      
    private function id_cheque_por_sucursal($id){
        $value = str_pad($id, 12, "0", STR_PAD_LEFT);
        return $value;
    }
//    private function id_move(Moves $move){
//        $value = str_pad($move->get_id(), 12, "0", STR_PAD_LEFT);
//        return $value;
//    }
//    
//    private function nro_cuenta(Moves $move){
//        $value = str_pad($move->get_id_marchand(), 22, "0", STR_PAD_LEFT);
//        return $value;
//    }
    
    private function nombre(Marchand $marchand){
        $value = str_pad($marchand->get_apellido_rs(), 50, " ", STR_PAD_RIGHT);
        return $value;
    }
    
    private function tipo_doc(Marchand $marchand){
        $tipo_doc = $marchand->get_id_tipodoc();
        
        switch ($tipo_doc){
            case Tipodoc::CI:
                $value = "01";
                break;
            case Tipodoc::LC:
                $value = "02";
                break;
            case Tipodoc::LE:
                $value = "03";
                break;
            case Tipodoc::DNI:
                $value = "04";
                break;
            case Tipodoc::CUIT_CUIL:
                $value = "06";
                break;
            default:
                $value = "05";
        }
        
        return $value;
    }
    
    private function numero_doc(Marchand $marchand){
        $value = str_pad($marchand->get_documento(), 11, "0", STR_PAD_LEFT);
        return $value;
    }
    
    private function calle(Marchand $marchand){
        $value = str_pad($marchand->get_gr_calle(), 30, " ", STR_PAD_RIGHT);
        return $value;
    }
    
    private function altura(Marchand $marchand){
        $value = str_pad($marchand->get_gr_numero(), 5, "0", STR_PAD_LEFT);
        return $value;
    }
    
    private function localidad(Marchand $marchand){
        $value = str_pad($marchand->get_gr_localidad(), 19, " ", STR_PAD_RIGHT);
        return $value;
    }
    
    private function cod_post(Marchand $marchand){
        $value = str_pad($marchand->get_gr_ncpo(), 8, "0", STR_PAD_LEFT);
        return $value;
    }
    
    private function provincia(Marchand $marchand){
        $id_provincia = $marchand->get_gr_id_provincia();
        $provincia = new Provincia();
        $provincia->get($id_provincia);

        return $provincia->get_letraprov();
    }
    
    private function numero_tel(Marchand $marchand){
        $value = str_pad($marchand->get_telefonos(), 20, "0", STR_PAD_LEFT);
        return $value;
    }
    
    private function numero_beneficiario(Moves $move){
        $value = str_pad($move->get_id_marchand(), 14, "0", STR_PAD_LEFT);
        return $value;
    }
    
    private function xml($id, Marchand $marchand){
        $recordset = Cheque_por_sucursal::obtener_sucursal($id);
        $recordset = $recordset->FetchRow();
        $value = '<?xml version="1.0" encoding="ISO-8859-1"?><param><suc>' .str_pad($recordset['codigo'], 4, '0', STR_PAD_LEFT) .'</suc><mail>'.
                $marchand->get_email() . '</mail></param>';
        return $value;
    }
//
//    private function obtener_codigo_tipodoc($tipodoc) {
//        switch ($tipodoc) {
//            case 1:
//                return "0086"; #cuil #"0087" cuit
//            case 2:
//                return "0096"; #dni
//            case 3:
//                return "0000"; #Ci policia federal;
//            case 4:
//                developer_log("||Galicia no admite identificación por C.B.U.");
//                return null;
//            case 6:
//                return "0094"; #Pasaporte
//            case 7:
//                return "0090"; #LC
//            case 8:
//                return "0089"; #LE
//            case 10:
//                return "0001"; #ci Buenos aires;
//        }
//    }
    protected function Procesar_cbu($cbu){
        
        return $cbu;
        
    }
}
