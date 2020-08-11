<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of proceso_alta
 *
 * @author ariel
 */
class Proceso_alta extends Model {

    public static $id_tabla = "id_proceso_alta";
    private $id_proceso_alta;
    private $celular;
    private $email;
    private $selfie;
    private $dni;
    private $valida_mail;
    private $valida_ident;
    private $valida_celular;

    public function get_id_proceso_alta() {
        return $this->id_proceso_alta;
    }

    public function get_celular() {
        return $this->celular;
    }

    public function get_email() {
        return $this->email;
    }

    public function get_selfie() {
        return $this->selfie;
    }

    public function get_dni() {
        return $this->dni;
    }

    public function get_valida_mail() {
        return $this->valida_mail;
    }

    public function get_valida_ident() {
        return $this->valida_ident;
    }

    public function get_valida_celular() {
        return $this->valida_celular;
    }

    public function set_id_proceso_alta($id_proceso_alta) {
        $this->id_proceso_alta = $id_proceso_alta;
        return $this;
    }

    public function set_celular($celular) {
        $this->celular = $celular;
        return $this;
    }

    public function set_email($email) {
        $this->email = $email;
        return $this;
    }

    public function set_selfie($selfie) {
        $this->selfie = $selfie;
        return $this;
    }

    public function set_dni($dni) {
        $this->dni = $dni;
        return $this;
    }

    public function set_valida_mail($valida_mail) {
        $this->valida_mail = $valida_mail;
        return $this;
    }

    public function set_valida_ident($valida_ident) {
        $this->valida_ident = $valida_ident;
        return $this;
    }

    public function set_valida_celular($valida_celular) {
        $this->valida_celular = $valida_celular;
        return $this;
    }

    public function set() {
        $this->event_activate($this);
        return parent::set();
    }

    public function event_activate(Proceso_alta $proceso) {
       
        if ($proceso->get_valida_celular() == 't' or $proceso->get_valida_celular()== 'true')
            if ($proceso->get_valida_mail() == 't' or $proceso->get_valida_mail()== 'true')
                if ($proceso->get_valida_ident() == 't' or $proceso->get_valida_ident() == 'true') {
//                    var_dump("aca"); 
                    $rs = \Cuenta::select(["id_proceso_alta" => $proceso->get_id()]);
                    if ($rs->rowCount() == 0)
                        return false;
                    $cuenta = new \Cuenta($rs->fetchRow());
                    $usuario = new \Usuario();
                    $usuario->get($cuenta->get_id_usuario_titular());
                    $usuario->set_id_authstat(\Authstat::ACTIVO);
                    $cuenta->set_id_authstat(\Authstat::ACTIVO);
                    
                    if(!$cuenta->get_cvu()){
                        $bind = new \Bind_crear_cvu();
                        $cvu = $bind->crear_cvu($cuenta->get_titular(), $cuenta->get_documento(), "ARS");
                        var_dump($cvu);
                        if(isset($cvu["cvu"])){
                            $cuenta->set_cvu($cvu["cvu"]);
                            $cuenta->set_alias($cvu["label"]);
                        }
                    }
                    //mandar mail de activacion de cuenta;
                    if ($usuario->set()) {
                        if ($cuenta->set()) {
                            return true;
                        }
                    }
                    return false;
                }
        return false;
    }

}
