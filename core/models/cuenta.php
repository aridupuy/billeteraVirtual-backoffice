<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of cuenta
 *
 * @author ariel
 */
class Cuenta extends Model {

    public static $id_tabla = "id_cuenta";
    public $id_cuenta;
    public $titular;
    public $id_usuario_titular;
    public $id_authstat;
    public $comment;
    public $cvu;
    public $alias;
    public $documento;
    public $acepta_terminos;
    public $foto_frente_dni;
    public $foto_dorso_dni;
    public $selfie;
    public $fecha_nac;
    public $id_pais;
    public $genero;
    public $estado_civil;
    public $ocupacion;
    public $id_prov;
    public $id_loc;
    public $cod_postal;
    public $calle;
    public $numero;
    public $piso;
    public $depto;
    public $id_proceso_alta;
    
    public function get_id_proceso_alta() {
        return $this->id_proceso_alta;
    }

    public function set_id_proceso_alta($id_proceso_alta) {
        $this->id_proceso_alta = $id_proceso_alta;
        return $this;
    }

        public function get_alias() {
        return $this->alias;
    }

    public function set_alias($alias) {
        $this->alias = $alias;
        return $this;
    }

        public function get_id_cuenta() {
        return $this->id_cuenta;
    }

    public function get_titular() {
        return $this->titular;
    }

    public function get_id_usuario_titular() {
        return $this->id_usuario_titular;
    }

    public function get_id_authstat() {
        return $this->id_authstat;
    }

    public function get_comment() {
        return $this->comment;
    }

    public function get_cvu() {
        return $this->cvu;
    }

    public function get_documento() {
        return $this->documento;
    }

    public function get_acepta_terminos() {
        return $this->acepta_terminos;
    }

    public function get_foto_frente_dni() {
        return $this->foto_frente_dni;
    }

    public function get_foto_dorso_dni() {
        return $this->foto_dorso_dni;
    }

    public function get_selfie() {
        return $this->selfie;
    }

    public function get_fecha_nac() {
        return $this->fecha_nac;
    }

    public function get_id_pais() {
        return $this->id_pais;
    }

    public function get_genero() {
        return $this->genero;
    }

    public function get_estado_civil() {
        return $this->estado_civil;
    }

    public function get_ocupacion() {
        return $this->ocupacion;
    }

    public function get_id_prov() {
        return $this->id_prov;
    }

    public function get_id_loc() {
        return $this->id_loc;
    }

    public function get_cod_postal() {
        return $this->cod_postal;
    }

    public function get_calle() {
        return $this->calle;
    }

    public function get_numero() {
        return $this->numero;
    }

    public function get_piso() {
        return $this->piso;
    }

    public function get_depto() {
        return $this->depto;
    }

    public function set_id_cuenta($id_cuenta) {
        $this->id_cuenta = $id_cuenta;
        return $this;
    }

    public function set_titular($titular) {
        $this->titular = $titular;
        return $this;
    }

    public function set_id_usuario_titular($id_usuario_titular) {
        $this->id_usuario_titular = $id_usuario_titular;
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

    public function set_cvu($cvu) {
        $this->cvu = $cvu;
        return $this;
    }

    public function set_documento($documento) {
        $this->documento = $documento;
        return $this;
    }

    public function set_acepta_terminos($acepta_terminos) {
        $this->acepta_terminos = $acepta_terminos;
        return $this;
    }

    public function set_foto_frente_dni($foto_frente_dni) {
        $this->foto_frente_dni = $foto_frente_dni;
        return $this;
    }

    public function set_foto_dorso_dni($foto_dorso_dni) {
        $this->foto_dorso_dni = $foto_dorso_dni;
        return $this;
    }

    public function set_selfie($selfie) {
        $this->selfie = $selfie;
        return $this;
    }

    public function set_fecha_nac($fecha_nac) {
        $this->fecha_nac = $fecha_nac;
        return $this;
    }

    public function set_id_pais($id_pais) {
        $this->id_pais = $id_pais;
        return $this;
    }

    public function set_genero($genero) {
        $this->genero = $genero;
        return $this;
    }

    public function set_estado_civil($estado_civil) {
        $this->estado_civil = $estado_civil;
        return $this;
    }

    public function set_ocupacion($ocupacion) {
        $this->ocupacion = $ocupacion;
        return $this;
    }

    public function set_id_prov($id_prov) {
        $this->id_prov = $id_prov;
        return $this;
    }

    public function set_id_loc($id_loc) {
        $this->id_loc = $id_loc;
        return $this;
    }

    public function set_cod_postal($cod_postal) {
        $this->cod_postal = $cod_postal;
        return $this;
    }

    public function set_calle($calle) {
        $this->calle = $calle;
        return $this;
    }

    public function set_numero($numero) {
        $this->numero = $numero;
        return $this;
    }

    public function set_piso($piso) {
        $this->piso = $piso;
        return $this;
    }

    public function set_depto($depto) {
        $this->depto = $depto;
        return $this;
    }

    public function __set($property, $value) {
        if(property_exists($this, $property))
            $this->$property=$value;
    }
    
    public static function select_id_cuenta_from_cuit_cvu($cuit, $cvu) {
        $sql = "select * from ef_cuenta where documento = ? and cvu = ?";
        $variables = array($cuit, $cvu);
        return self::execute_select($sql, $variables);
    }
    
}
