<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validador_reintegrado
 *
 * @author ariel
 */
class Validador_reintegrado extends Validador_mp {
    
    public function obtener_barcodes_a_procesar($barcodes) {
        $reintegrado = $barcodes[Validacion_mercado_pago::MERCADO_PAGO_REINTEGRADO];
        return $reintegrado;
    }

    public function procesar() {
        Gestor_de_log::set("No proceso esto aun!");
        developer_log("No proceso esto aun!");
        return array(0,0,0);
    }
    public function obtener_estado($cobranza){
        return "Reintegrado";
    }
}
