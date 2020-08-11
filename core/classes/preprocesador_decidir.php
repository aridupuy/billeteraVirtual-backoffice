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
class Preprocesador_decidir {

    public $payments;
    public $keys;
    public $ambient;
    private $procesados = 0;
    private $cantidad = 0;
    private $errores = 0;

    protected function pre_ejecucion() {
        $pago = new Pago_tc_decidir();
        $this->payments = $pago->obtener_pagos();
        $this->keys = $pago->get_keys();
        $this->ambient = $pago->get_ambiente();
    }

    protected function ejecutar() {
        $i = 0;
        $this->cantidad = count($this->payments);

        foreach ($this->payments as $pay) {
            $connector = new \Decidir\Connector($this->keys, $this->ambient);
            developer_log("procesando " . ++$i . "de " . count($this->payments));
            $response = json_decode($pay->get_response(), true);
            $data = array();
            try {
                $response_pago = $connector->payment()->PaymentInfo($data, $response["id"]);
                if ($pay->get_status() != $response_pago->getStatus()) {
                    $pay->set_status($response_pago->getStatus());
                    $this->procesados++;
                }
                $pay->set();
            } catch (Exception $e) {
                $pay->set_status("Inexistente");
                $pay->set();
                $this->errores++;
                developer_log($e->getMessage());
                developer_log("inexistente");
            }
            unset($connector);
            unset($response);
        }
    }

    

    public function preprocesar() {
        $this->pre_ejecucion();
        $this->ejecutar();
        return array($this->procesados, $this->cantidad, $this->errores);
    }

}
