<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of verificacion_sms
 *
 * @author ariel
 */
class Verificacion_sms extends Model{
  public static $id_tabla="id_verificacion_sms";
  
  private $id_verificacion_sms;
  private $codigo;
  private $celular;
  private $validado;
  public function get_id_verificacion_sms() {
      return $this->id_verificacion_sms;
  }

  public function get_codigo() {
      return $this->codigo;
  }

  public function get_celular() {
      return $this->celular;
  }

  public function get_validado() {
      return $this->validado;
  }

  public function set_id_verificacion_sms($id_verificacion_sms) {
      $this->id_verificacion_sms = $id_verificacion_sms;
      return $this;
  }

  public function set_codigo($codigo) {
      $this->codigo = $codigo;
      return $this;
  }

  public function set_celular($celular) {
      $this->celular = $celular;
      return $this;
  }

  public function set_validado($validado) {
      $this->validado = $validado;
      return $this;
  }


  
  
}
