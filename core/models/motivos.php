<?php

// namespace Models;
class Motivos extends Model {
  public static $id_tabla = "id_motivo";
    public static $prefijo_tabla="ef_";
    private $id_motivo;
    private $motivo;
    private $tipo_motivo;
    private $id_authstat;
    
    function get_id_motivo() {
        return $this->id_motivo;
    }

    function get_motivo() {
        return $this->motivo;
    }

    function get_tipo_motivo() {
        return $this->tipo_motivo;
    }

    function get_id_authstat() {
        return $this->id_authstat;
    }

    function set_id_motivo($id_motivo) {
        $this->id_motivo = $id_motivo;
    }

    function set_motivo($motivo) {
        $this->motivo = $motivo;
    }

    function set_tipo_motivo($tipo_motivo) {
        $this->tipo_motivo = $tipo_motivo;
    }

    function set_id_authstat($id_authstat) {
        $this->id_authstat = $id_authstat;
    }

    public function select_concepto(){
        $sql = "SELECT id_motivo,motivo FROM ef_motivos WHERE tipo_motivo = '3'";
        return self::execute_select($sql);
    }

    public function select_blacklist(){
        $sql = "SELECT id_motivo,motivo FROM ef_motivos WHERE tipo_motivo = '2'";
        return self::execute_select($sql);
    }

    public function select_usuario(){
        $sql = "SELECT id_motivo,motivo FROM ef_motivos WHERE tipo_motivo = '1'";
        return self::execute_select($sql);
    }
}
