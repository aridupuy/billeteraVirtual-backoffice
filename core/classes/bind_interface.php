<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_interface
 *
 * @author ariel
 */
interface Bind_interface {
    
    //put your code here
    public function login();
    public function llamado_api($url,$parametros,$method="POST");
    

//    /*se implementa en bind_movimientos*/
//    public   function consulta_de_movimientos();
//    /*se implementa en bind_extras*/
//    public   function consulta_de_cuit();
//    /*se implementa en bind_transferencias_cbu*/
//    public   function realizar_transferencia();
//    public   function consultar_transferencias();
//    public   function consultar_transferencia();
//    public   function eliminar_transferencia();
//    /*se implementa en bind_cvu*/
//    public   function alta_cvu_cliente();
//    public   function asignar_alias_cvu();
//    /*se implementa en bind_transferencias_cvu*/
//    public   function realizar_transferencia_desde_cvu();
//    public   function obtener_transferencias_de_cvu();
//    public   function obtener_transferencia_de_cvu();
//    public   function baja_de_cvu();
//    /*se implementa en webhook_bind*/
//    public   function crear_webhook();
//    Bind_interface
    
}
