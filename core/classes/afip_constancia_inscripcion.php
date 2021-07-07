<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of afip_padronA4
 *
 * @author ariel
 */
class Afip_constancia_inscripcion extends Afip_login{
    CONST METODOLOGIN="ws_sr_constancia_inscripcion";
    CONST METODO="getPersona_v2"; 
    CONST URL="https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA5?wsdl";
    CONST PARAMETROS=["idPersona"];
    private $documento;
    protected function parsear() {
        
    }
    
    public function get_documento() {
        return $this->documento;
    }

    public function set_documento($documento) {
        $this->documento = $documento;
    }

        /**

     * @category  decorator     */
    public function getParametros(){
        //return parent::get_parametros();
        $params= $this->obtener_parametros();
        //$parametros["a5:".$this->getMetodo()]=$params;
        $parametros=$params;
        return $parametros;
    }
    /**

     * @category  decorator     */
    public function obtener_parametros() {
        $params=parent::obtener_parametros();
        $params["idPersona"]=$this->documento;
        return $params;
    }
    
}
