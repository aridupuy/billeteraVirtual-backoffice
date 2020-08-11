<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validaciones
 *
 * @author arieldupuy
 */
class Validaciones  extends Validacion_sistema{
    //put your code here
    public function __construct() {
        $this->encadenar();
        
        return $this;
    }
    public function ejecutar() {
        try{
            return $this->next()->run();
        }catch (Exception $exce){
            developer_log($exce->getMessage());
            return true;
        }
        catch (Error $e){
            developer_log("Error ".$e->getMessage()." stacktrace ".$e->getTraceAsString());
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "sistemas@cobrodigital.com","Error en validacines ", $e->getMessage()." stacktrace ".$e->getTraceAsString());
            $this->setNext($this->next()->next());
            return $this->ejecutar();
        }
        
    }

}
