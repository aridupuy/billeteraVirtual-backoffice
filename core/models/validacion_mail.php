<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validacion_mail
 *
 * @author ariel
 */
class Validacion_mail extends Model{
    public static $id_tabla = "id_verificacion_mail";
    
    private $id_verificacion_mail;
    private $mail;
    private $url;
    private $id_proceso_alta;
    
    
    
    public function get_id_verificacion_mail() {
        return $this->id_verificacion_mail;
    }

    public function get_mail() {
        return $this->mail;
    }

    public function get_url() {
        return $this->url;
    }

    public function get_id_proceso_alta() {
        return $this->id_proceso_alta;
    }

    public function set_id_verificacion_mail($id_verificacion_mail) {
        $this->id_verificacion_mail = $id_verificacion_mail;
        return $this;
    }

    public function set_mail($mail) {
        $this->mail = $mail;
        return $this;
    }

    public function set_url($url) {
        $this->url = $url;
        return $this;
    }

    public function set_id_proceso_alta($id_proceso_alta) {
        $this->id_proceso_alta = $id_proceso_alta;
        return $this;
    }


    
    
    
}
