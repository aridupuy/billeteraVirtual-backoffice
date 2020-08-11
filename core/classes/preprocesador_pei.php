<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of preprocesador_decidir
 *
 * @author ariel
 */
class Preprocesador_pei extends Preprocesador_decidir {

    public $payments;
    private $procesados = 0;
    private $cantidad = 0;
    private $errores = 0;
    const ESTADO_APROBADO ="approved";
    const ESTADO_RECHAZADO ="rejected";
    const ESTADO_DEVUELTO ="refounded";
    const ESTADO_DEVUELTO_PARCIAL ="parcial_refounded";
    protected function pre_ejecucion() {
        $pago = new Pei();
        $this->payments = $pago->obtener_pagos();
        
    }

    protected function ejecutar() {
        $i = 0;
        $this->cantidad = count($this->payments);

        foreach ($this->payments as  $pay ) {
            developer_log("procesando " . ++$i . "de " . count($this->payments));
            $pei= new Pei_conciliacion();
            $response_pago = json_decode($pay->get_response(),true);
            $estado= $pei->consultar_pagos($response_pago["idOperacion"]);
            switch ($estado ){
                case $pei::RESPUESTA_ACEPTADO:
                    if ($pay->get_status() != self::ESTADO_APROBADO) {
                        $pay->set_status(self::ESTADO_APROBADO);
                        $this->procesados++;
                        $pay->set();
                    }
                    break;
                case $pei::RESPUESTA_RECHAZO:
                    if ($pay->get_status() != self::ESTADO_RECHAZADO) {
                        $pay->set_status(self::ESTADO_RECHAZADO);
                        $this->procesados++;
                        $pay->set();
                    }
                    break;
                case $pei::RESPUESTA_DEVUELTO:
                    if ($pay->get_status() != self::ESTADO_DEVUELTO) {
                        $pay->set_status(self::ESTADO_DEVUELTO);
                        $this->procesados++;
                        $pay->set();
                    }
                    break;
            }
            
        }
    }

    protected function post_ejecucion($archivo) {
        
    }

    protected function obtener_barcode(\Registro $registro) {
        
    }

    public function preprocesar() {
        $this->pre_ejecucion();
        $this->ejecutar();
        return array($this->procesados, $this->cantidad, $this->errores);
    }

}
