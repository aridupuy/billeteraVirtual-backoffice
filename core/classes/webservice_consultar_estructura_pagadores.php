<?php
class Webservice_consultar_estructura_pagadores extends Webservice{
    public function ejecutar($array) {
        $xml = Xml::estructura(self::$id_marchand, Entidad::ESTRUCTURA_CLIENTES);
        if(!$xml)
            $this->adjuntar_mensaje_para_usuario ("No existe una estructura de clientes definida.");
        if(($array = Pagador::obtener_nombres_y_labels($xml))!=false){
            foreach ($array as $key => $dato) {
                $this->adjuntar_dato_para_usuario($dato['label']);
            }
        }
        else
            $this->adjuntar_mensaje_para_usuario ("La estructura de pagadores no es correcta.");
    }
}
