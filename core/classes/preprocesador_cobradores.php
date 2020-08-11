<?php

class Preprocesador_cobradores {
    const ID_MP = "91";
    const SABANA_AUTHSTAT = "1";
    private $cobrador;
    private $marchand;
    private $control;
    public function __construct() {
        return $this;
    }

    public function consultar_bdd() {
        $recordSet = Cobros_cobrador::select_lotes_a_preprocesar();
        return $recordSet;
    }

    public function ejecutar($archivo) {
        $archivo = $this->consultar_bdd();
        $tiempo_inicio = microtime(true);
        $this->developer_log('*************************INICIO*********************************');
        $this->developer_log('Medio de Pago: ' . Mp::COBRO_COBRADORES);
        $this->developer_log('Cantidad de lineas: ' . $archivo->rowCount());
        $this->developer_log('Día:' . date('d/m/Y') . ' Hora:' . date('H:i'));
        $this->developer_log('*****************************************************************');
        foreach ($archivo as $row) {
            $this->optimizar_marchand($row);
            $this->optimizar_operador($row);
            if (!Model::hasFailedTrans()) {
                if (!($control = $this->insertar_control())) {
                    $this->developer_log('Ha ocurrido un error al insertar el registro de Control.');
                    Model::FailTrans();
                }
                $lote=new Lote_cobrador();
                $lote->get_id($row["id_lote_cobrador"]);
                $lote->set_revno($control->get_revno());
                $lote->set_id_authstat(Authstat::INACTIVO);
                $lote->set();
                if(!($barcode=$this->generar_boleta($row)))
                    Model::FailTrans ();
                if(!($sabana=$this->insertar_sabana($barcode, $control))){
                    Model::FailTrans();
                }
                if(!$this->desactivar_registro($row)){//eliminar para mejor rendimiento?;
                    Model::FailTrans();
                }
                
            }
        }
        if(!Model::HasFailedTrans() and Model::CompleteTrans()){
            return true;
        }
        return false;
    }
    protected function generar_boleta($row){
        //ver el sap para la poliza de victoria seguros
        if(!($cliente=$this->verificar_existencia_pagador($row['poliza']))){
            $pagador=new Pagador();
            $array=array("sap_identificador"=>$row['poliza']);
            $pagador->crear($row['id_marchand'],$array );
            $cliente=$pagador::$climarchand;
            
        }
        $fecha= DateTime::createFromFormat("Y-m-d", $row['vencimiento']);
        $importe=$row['importe'];
        $boleta=new Boleta_pagador();
        if(!$boleta->crear($cliente->get_id_climarchand(),"init", array($fecha->format("d/m/Y")), array($importe), $this->cobrador->get_nro_operador())){
            return false;
        }
        $barcode=$boleta->barcode_1;
        return $barcode;
    }
    protected function verificar_existencia_pagador($poliza){ //cambiar cuando no sea necesario hacer mas general
        $recordset=Climarchand::select_clientes($this->marchand->get_id_marchand(), array("sap_identificador"), array("sap_identificador"=>$poliza));
        if($recordset AND $recordset->rowCount()==0){
            return false;
        }
        else{
            $row=$recordset->fetchRow();
            return new Climarchand($row);
        }
    }
    protected function desactivar_registro($row){
        $cobro=new Cobros_cobrador($row);
        $cobro->set_id_authstat(Authstat::INACTIVO);
        if($cobro->set())
            return true;
        return false;
    }

    










    protected function optimizar_operador($row){
        if($this->cobrador==null){
            $this->cobrador=new Cobrador ();
            $this->cobrador->get($row['id_cobrador']);
            $this->inicializar_transaccion();
        }
        else {
            $this->inicializar_transaccion();
            if($this->cobrador->get_id()!==$row['id_cobrador']);
                $this->cobrador->get($row['id_cobrador']);
        }
    }
    protected function inicializar_transaccion(){
        Model::CompleteTrans();
        Model::StartTrans();
    }

    protected function optimizar_marchand($row){
        if($this->marchand==null){
            $this->marchand=new Marchand();
            $this->marchand->get($row['id_marchand']);
        }
        else {
            if($this->marchand->get_id()!==$row['id_marchand']);
                $this->marchand->get($row['id_marchand']);
        }
    }


    protected function insertar_sabana(Barcode $barcode, Control $control) {

        $sabana = new Sabana();
        $linea = $this->get_ultima_linea($control);
        $this->developer_log($linea . " | Insertando sabana. ");

        $sabana->set_id_authstat(self::SABANA_AUTHSTAT);
        $sabana->set_id_barcode($barcode->get_id());
        $sabana->set_id_mp(self::ID_MP);
        $sabana->set_barcode($barcode->get_barcode());
        $fecha_vto=$barcode->get_fecha_vto();
        $fecha_vto = DateTime::createFromFormat("Y-m-d", $fecha_vto);
        if (!$fecha_vto) {
            $this->developer_log($linea . " | La fecha de vencimiento no es válida. ");
            return false;
        }
        $sabana->set_fecha_vto($fecha_vto->format('Y-m-d'));
        $sabana->set_monto($barcode->get_monto());
        $sabana->set_fechagen('now');
        $sabana->set_sc($barcode->get_id_sc());
        $sabana->set_barrand($barcode->get_barrand());
        $fecha_pago = new DateTime("now");
        if (!$fecha_pago) {
            $this->developer_log($linea . " | La fecha de pago no es válida. [1] ");
            return false;
        }
        $sabana->set_fecha_pago($fecha_pago->format('Y-m-d'));
        $sabana->set_id_formapago('1');
        $sabana->set_revno($control->get_revno());
        $sabana->set_nlinea($linea);
        try {
            $sabana->set_xml_extra("Sabana insertada a travez del micrositio de Cobradores");
        } catch (Exception $e) {
            developer_log($e->getMessage());
            return $sabana;
        }

        if ($sabana->set()) {
            return $sabana;
        }
        return false;
    }

    protected function insertar_control() {
        $fecha = new DateTime("now");
        $control = $this->obtener_control_actual($fecha);
        $nro_cobrador = $this->cobrador->get_id_cobrador();
        $csv_file = "LOTE_" . $nro_cobrador . "_" . $fecha->format("Y-m-d");
        $tpl_file = $nro_cobrador;
        $control->set_id_mp(self::ID_MP);
        $control->set_script("Cobradores");
        $control->set_tplfile($tpl_file);
        $control->set_csvfile($csv_file);
        $control->set_date_run($fecha->format("Y-m-d"));
        if ($control->set()){
            $this->control=$control;
            return $control;
        }
            
        else
            return false;
    }
    private function obtener_control_actual(DateTime $fecha) {
        $recorset = Control::select(array("date_run" => $fecha->format("Y-m-d"), "id_mp" => self::ID_MP, "tplfile" => $this->cobrador->get_id_cobrador(), "success" => Control::SUCCESS_PENDIENTE));
        if ($recorset->rowCount() == 0)
            return new Control();
        else {
            return new Control($recorset->fetchRow());
        }
    }
    private function get_ultima_linea(Control $control) {
        $recordSet = Sabana::obtener_ultima_linea($control->get_revno());
        if ($recordSet->rowCount() == 1) {
            $row = $recordSet->fetchRow();
            return $row["max"] + 1;
        }
        return 1;
    }
    protected function developer_log($mensaje) {
        error_log("PREPROCESADOR-COBRADORES:" . $mensaje);
    }
    
}
