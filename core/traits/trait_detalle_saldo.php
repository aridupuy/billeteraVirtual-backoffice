<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Trait_detalle_saldo
 *
 * @author Ariel_dupuy
 */
trait Trait_detalle_saldo {
    public function detallar($array) {
        $cliente=new Cliente();
        $registro=$cliente->obtener_estado_de_cuenta(self::$id_marchand);
        if(!$registro){
            $this->adjuntar_mensaje_para_usuario ("Ha ocurrido un error.");
            $this->respuesta_ejecucion= Webservice::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        $this->adjuntar_mensaje_para_usuario ("Consulta realizada correctamente.");
        $this->respuesta_ejecucion= Webservice::RESPUESTA_EJECUCION_CORRECTA;
        $this->adjuntar_dato_para_usuario($registro);
        return;
    }
}
