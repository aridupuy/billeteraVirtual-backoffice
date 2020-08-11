<?php

class Ordenador_cheques_sucursal extends Ordenador {
    const ID_MP = Mp::RETIROS_CHEQUE_POR_SUCURSAL;
    protected function obtener_recordset(){
            return Cheque_por_sucursal::registros_a_ordenar();
    }

    public function ordenar_cheques(){
        $hoy = new DateTime('now');
        $recordset = $this->obtener_recordset();
        $control = $this->insertar_control(null, $this->nombrar_archivo($hoy), null, null);

        $archivos[] = $this->generar_archivo($recordset, $control);
        $nombres[] = $this->nombrar_archivo($hoy);
        
        $url = $this->exportar_archivos($archivos, $nombres);
        
        return $url;
    }
    
    private function generar_archivo($recordset, $control){
        $nlinea = 1;
        $archivo = "";
        $cheque_por_sucursal = new Cheque_por_sucursal();
        
        foreach ($recordset as $row) {
            $registro = $this->obtener_registro($row);
            $valor = $this->insertar_orden($row, $nlinea, $registro, $control->get_revno());
            
            $cheque_por_sucursal->get($row['id_cheque_por_sucursal']);
            $cheque_por_sucursal->set_id_authstat(Authstat::DEBITO_ENVIADO);
            $cheque_por_sucursal->set();
            $archivo.= $registro->get_fila();
            $nlinea++;
        }
        
        return $archivo;
    }
    
    
    ///////////////////////////
    
    
    protected function consultar_bdd(DateTime $fecha_a_ordenar, $nro_archivo) {
        
    }
//        developer_log("| Ordenador Galicia. Consultando débitos. Espere...");
//        $hoy = new DateTime("now");
//        //$fecha_a_ordenar=$this->siguiente_dia_habil($fecha_a_ordenar,self::CANTIDAD_DE_DIAS_ANTICIPACION);
//        $recordset = Debito_cbu::select_debitos_a_procesar($hoy, (self::CANTIDAD_DE_ARCHIVOS_PROCESABLES * self::CANTIDAD_DE_REGISTROS_POR_ARCHIVO) + 1);
//        if ($recordset == false) {
//            if (self::ACTIVAR_DEBUG)
//                developer_log("|| Error en la consulta del archivo " . $nro_archivo . ".");
//            return false;
//        }
//
//        if ($recordset->RowCount() == 0) {
//            if (self::ACTIVAR_DEBUG)
//                developer_log("|| Ya no hay debitos que deban ser informados para el archivo " . $nro_archivo . ".");
//            return $recordset;
//        }
//
//        developer_log("| Se obtuvieron " . $recordset->rowCount() . " Débitos.");
//        return $recordset;
//    }

    protected function obtener_fila($row) {
        $registro_cheque = new Registro_cheque();

        //$registro_cheque = $registro_cheque->generar_fila($row[self::MONTO_1], $row[self::IDENTIFICACION_CLIENTE], $row[self::CBU], $row[self::REFERENCIA_UNIVOCA], $row[self::FECHA_1], $row[self::TIPO_DOC], $row[self::DOCUMENTO], $row[self::CONCEPTO_FACTURA], $importe_minimo, $mensaje_atm, $monto_2, $fecha_2, $monto_3, $fecha_3);
        $registro_cheque = $registro_cheque->generar_fila($row);
        if ($registro_cheque)
            return $registro_cheque;
        return false;
    }

    protected function actualizar_deuda($row) {
        if (self::DESACTIVAR_BDD)
            return true;
        $debito_cbu = new Debito_cbu();
        $debito_cbu->set_id_debito($row['id_debito']);
        $debito_cbu->set_id_authf1(Authstat::DEBITO_ENVIADO);
        $debito_cbu->set_id_authf2(Authstat::DEBITO_ENVIADO);
        $debito_cbu->set_id_authf3(Authstat::DEBITO_ENVIADO);
        if (!$debito_cbu->set()) {
            if (self::ACTIVAR_DEBUG)
                developer_log("||No se pudo actualizar Debito_cbu.");
            return false;
        }
        return true;
    }
//    
//    final function insertar_sabana($row, $nlinea, $registro, $revno) {
//         if (self::DESACTIVAR_BDD_SABANA)
//            return true;
//        $sabana = new Sabana();
//        $sabana->set_id_barcode(1);
//        $sabana->set_barcode($row[self::REFERENCIA_UNIVOCA]);
//        $sabana->set_fechagen('now');
//        $sabana->set_id_authstat(Authstat::DEBITO_ENVIADO);
//        $sabana->set_fecha_vto($row[STATIC::FECHA_1]);
//        $sabana->set_revno($revno);
//        $sabana->set_nlinea($nlinea);
//        $sabana->set_monto($row[STATIC::MONTO_1]);
//        $sabana->set_sc(2040);
//        $sabana->set_barrand(1);
//        $sabana->set_id_mp(static::ID_MP);
//        $sabana->set_xml_extra($registro->fila);
//        $sabana->set_id_formapago('1'); # TEMP PARA IDENTIFICAR NUEVOS PROCESADOR Y ORDENADOR
//        if ($sabana->set()) {
//            return true;
//        }
//        if (static::ACTIVAR_DEBUG)
//            developer_log("|| Error al insertar sabana.");
//        return false;
//    }
    protected function procesar_archivo_control(DateTime $fecha_a_ordenar, $archivos, $nombre) {
//        parent::procesar_archivo_control($fecha_a_ordenar, $archivos, $nombre);
    }
//
//    public function proximo_dia_habil($dias, DateTime $fecha_datetime) {
//        $fecha_datetime_2 = clone $fecha_datetime;
//        $fecha_datetime_2 = $this->obtener_fechas_habiles($fecha_datetime, $fecha_datetime_2, $dias);
//        return $fecha_datetime_2;
//    }
//
//    public function obtener_fechas_habiles(DateTime $fecha1, DateTime $fecha_objetivo, $dias, $dias_habiles = 0) {
//        if ($dias == $dias_habiles) {
//            if (Calendar::es_dia_habil($fecha_objetivo))
//                return $fecha_objetivo;
//        }
////    var_dump($dias);
////    var_dump($dias_habiles);
//        if (Calendar::es_dia_habil($fecha_objetivo)) {
//            $dias_habiles += 1;
//        }
//        $fecha_objetivo->add(new DateInterval("P1D"));
//        return $this->obtener_fechas_habiles($fecha1, $fecha_objetivo, $dias, $dias_habiles);
//    }
    
    
    public function nombrar_archivo(DateTime $hoy, $nro_archivo=1) {

        $nombre = "CHEQ_" . $hoy->format('Ymd') . "_" . ($nro_archivo) . ".TXT";
        if (!$nombre) {
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Error no se puede nombrar el archivo.");
            return false;
        }
        if (self::ACTIVAR_DEBUG)
            developer_log("Nombre asignado: " . $nombre);
        return $nombre;
    }
    
    private function obtener_registro($row){
        if (!($registro = $this->obtener_fila($row)) AND ! Model::HasFailedTrans()) {
            if (static::ACTIVAR_DEBUG)
                developer_log("|| Error, no se pudo obtener la fila correspondiente al registro " . $row['id_cheque_por_sucursal'] . " de cheque por sucursal.");
            Model::FailTrans();
        }
        return $registro;
    }
    
    protected function obtener_encabezado($nro_archivo) {
        
    }
    protected function obtener_pie($nro_archivo) {
        
    }
    
    public function insertar_orden($row, $nlinea, $registro, $revno) {
        $moves = new Moves();
        $moves->get($row['id_moves']);
        
        $ordenador = new Csv_ordenador();
        $ordenador->set_id_entidad(Entidad::ENTIDAD_CHEQUE_POR_SUCURSAL);
        $ordenador->set_id_referencia($row['id_cheque_por_sucursal']);
//        $ordenador->set_referencia_univoca($row[static::CODIGO_DE_BARRAS]);
        $ordenador->set_fechagen('now');
        $ordenador->set_id_authstat(Authstat::DEBITO_ENVIADO);
        $ordenador->set_fecha_vto($moves->get_fecha_liq());
        $ordenador->set_revno($revno);
        $ordenador->set_nlinea($nlinea);
        $ordenador->set_monto($moves->get_monto_marchand());
//        $ordenador->set_sc(Barcode::obtener_segmento_comercial($row[STATIC::CODIGO_DE_BARRAS]));
//        $ordenador->set_barrand(Barcode::obtener_barrand($row[STATIC::CODIGO_DE_BARRAS]));
        $ordenador->set_id_mp(self::ID_MP);
        $ordenador->set_id_marchand($moves->get_id_marchand());
        
        $ordenador ->set_linea($registro->fila);
        if ($ordenador ->set()) {
            return true;
        }
        if (static::ACTIVAR_DEBUG)
            developer_log("|| Error al insertar ordenador.");
        return false;
    }

}
