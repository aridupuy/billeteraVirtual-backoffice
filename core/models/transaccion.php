<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of transaccion
 *
 * @author ariel
 */
//namespace Models;

class Transaccion extends \Model{
  public static $id_tabla = "id_transaccion";
  
  public $id_transaccion;
  public $monto;
  public $id_tipo_trans;
  public $fecha_pago;
  public $fecha_gen ;
  public $id_mp;
  public $id_authstat;
  public $comment ;
  public $id_cuenta;
  public $concepto ;
  public $id_entidad;
  public $id_referencia;
  
  public function get_id_entidad() {
      return $this->id_entidad;
  }

  public function get_id_referencia() {
      return $this->id_referencia;
  }

  public function set_id_entidad($id_entidad) {
      $this->id_entidad = $id_entidad;
      return $this;
  }

  public function set_id_referencia($id_referencia) {
      $this->id_referencia = $id_referencia;
      return $this;
  }

    public function get_id_transaccion() {
      return $this->id_transaccion;
  }

  public function get_monto() {
      return $this->monto;
  }

  public function get_id_tipo_trans() {
      return $this->id_tipo_trans;
  }

  public function get_fecha_pago() {
      return $this->fecha_pago;
  }

  public function get_fecha_gen() {
      return $this->fecha_gen;
  }

  public function get_id_mp() {
      return $this->id_mp;
  }

  public function get_id_authstat() {
      return $this->id_authstat;
  }

  public function get_comment() {
      return $this->comment;
  }

  public function get_id_cuenta() {
      return $this->id_cuenta;
  }

  public function get_concepto() {
      return $this->concepto;
  }

  public function set_id_transaccion($id_transaccion) {
      $this->id_transaccion = $id_transaccion;
      return $this;
  }

  public function set_monto($monto) {
      $this->monto = $monto;
      return $this;
  }

  public function set_id_tipo_trans($id_tipo_trans) {
      $this->id_tipo_trans = $id_tipo_trans;
      return $this;
  }

  public function set_fecha_pago($fecha_pago) {
      $this->fecha_pago = $fecha_pago;
      return $this;
  }

  public function set_fecha_gen($fecha_gen) {
      $this->fecha_gen = $fecha_gen;
      return $this;
  }

  public function set_id_mp($id_mp) {
      $this->id_mp = $id_mp;
      return $this;
  }

  public function set_id_authstat($id_authstat) {
      $this->id_authstat = $id_authstat;
      return $this;
  }

  public function set_comment($comment) {
      $this->comment = $comment;
      return $this;
  }

  public function set_id_cuenta($id_cuenta) {
      $this->id_cuenta = $id_cuenta;
      return $this;
  }

  public function set_concepto($concepto) {
      $this->concepto = $concepto;
      return $this;
  }

  public function select_min($variables = false,$tabla){
    
    if($tabla == 'ef_transferencia_enviada'){
      $sql = "SELECT * FROM ef_transaccion A 
LEFT JOIN ho_authstat B on A.id_authstat = B.id_authstat
LEFT JOIN ef_mp C on A.id_mp = C.id_mp
LEFT JOIN ef_transferencia_enviada D on A.id_referencia = D.id_transferencia
LEFT JOIN ho_entidad E on A.id_entidad = E.id_entidad
LEFT JOIN ef_cuenta F on A.id_cuenta = F.id_cuenta
LEFT JOIN ef_usuario G on F.id_usuario_titular = G.id_usuario
LEFT JOIN ef_destinatario H on D.id_destinatario = H.id_destinatario
left join ef_gateway_transaccion I on A.id_transaccion_gateway = I.id_transaccion";
    }else{
      $sql = "SELECT * FROM ef_transaccion A 
LEFT JOIN ho_authstat B on A.id_authstat = B.id_authstat
LEFT JOIN ef_mp C on A.id_mp = C.id_mp
LEFT JOIN ef_transferencia_recibida D on A.id_referencia = D.id_transferencia
LEFT JOIN ho_entidad E on A.id_entidad = E.id_entidad
LEFT JOIN ef_cuenta F on A.id_cuenta = F.id_cuenta
LEFT JOIN ef_usuario G on F.id_usuario_titular = G.id_usuario
LEFT JOIN ef_destinatario H on D.id_destinatario = H.id_destinatario
left join ef_gateway_transaccion I on A.id_transaccion_gateway = I.id_transaccion";
    }
  }

//   public static function select_min($id_cuenta,$filtros=null){
//     unset($variables['motivo']);
//     unset($variables['dataTable_length']);
//     unset($variables['checkbox_todo']);
//     unset($variables['selector_']);


//       $where = " id_cuenta = ? ";
//       $variables=array($id_cuenta);
//       if(isset($filtros["desde"])){
//           $where.=" and fecha_gen>=?";
//           $desde = DateTime::createFromFormat("Ymd", $filtros["desde"]);
//           if(!$desde){
//             $formato = explode("T",  $filtros["desde"]);
// //            var_dump($formato);
//             $desde = DateTime::createFromFormat("Y-m-d",$formato[0]);
// //            var_dump($desde);
//           }
//           $variables[]=$desde->format("Y-m-d");
//       }
//       if(isset($filtros["hasta"])){
//           $where.=" and fecha_gen<=?";
//           $hasta= DateTime::createFromFormat("Ymd", $filtros["hasta"]);
//           if(!$hasta){
//             $formato = explode("T", $filtros["hasta"]);
//             $hasta= DateTime::createFromFormat("Y-m-d", $formato[0]);
//           }
//           $variables[]=$hasta->format("Y-m-d");
//       }

//       /* SELECT * from ef_transaccion A 
// left join ho_authstat B on A.id_authstat = B.id_authstat
// left join ef_mp C on A.id_mp = C.id_mp
// left join ef_transferencia_recibida D on A.id_referencia = D.id_transferencia
// left join ho_entidad E on A.id_entidad = E.id_entidad */

//       $sql = "select * from ef_transaccion A left join ho_authstat B on A.id_authstat = B.id_authstat
//                left join ef_mp C on A.id_mp = C.id_mp
//                where $where order by 1 desc ";
//       return self::execute_select($sql,$variables);
//   }
}
