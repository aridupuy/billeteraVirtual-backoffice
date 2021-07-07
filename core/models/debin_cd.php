<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of debin_cd
 *
 * @author ariel
 */
class Debin_cd extends Model{
    //put your code here
    
  public static $id_tabla="id_debin";
  private $id_debin;
  private $fecha_gen;
  private $monto;
  private $respuesta_servicio;
  private $id_cuenta;
  private $procesado;
  
  
  public function get_procesado() {
      return $this->procesado;
  }

  public function set_procesado($procesado) {
      $this->procesado = $procesado;
      return $this;
  }

    public function get_id_debin() {
      return $this->id_debin;
  }

  public function get_fecha_gen() {
      return $this->fecha_gen;
  }

  public function get_monto() {
      return $this->monto;
  }

  public function get_respuesta_servicio() {
      return $this->respuesta_servicio;
  }

  public function get_id_cuenta() {
      return $this->id_cuenta;
  }

  public function set_id_debin($id_debin) {
      $this->id_debin = $id_debin;
      return $this;
  }

  public function set_fecha_gen($fecha_gen) {
      $this->fecha_gen = $fecha_gen;
      return $this;
  }

  public function set_monto($monto) {
      $this->monto = $monto;
      return $this;
  }

  public function set_respuesta_servicio($respuesta_servicio) {
      $this->respuesta_servicio = $respuesta_servicio;
      return $this;
  }

  public function set_id_cuenta($id_cuenta) {
      $this->id_cuenta = $id_cuenta;
      return $this;
  }


  public  static function select_debin_creado($id){
    $sql = "select *  from ef_debin_cd where respuesta_servicio::json ->> 'id' = ?";
    developer_log($sql);
    $variables[]=$id;
    developer_log($id);
    return self::execute_select($sql, $variables);
  }
}
