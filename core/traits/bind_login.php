<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_login
 *
 * @author ariel
 */
abstract class Bind_login extends Bind{
    public $token;
    public function __construct() {
        $this->login();
    }
    public abstract function consultar();
    public function login(){
        error_log("LOGUEANDO...");
        $this->token = "";
        $response = $this->llamado_api(self::URL_LOGIN, array("username"=>"pbernardo@cobrodigital.com","password"=>"c0qRDF3M4uHBDof"));
        $resp  = json_decode($response,true);
        $this->token = $resp["token"];
    }
    
  
   //eventos dentro de bind, sirve para que te envien notificaciones de cambios online.
    
    
    
    //put your code here
}
