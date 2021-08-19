<?php

// namespace Models;
class Bancos extends Model {
  public static $id_tabla = "id_banco";
    public static $prefijo_tabla="ef_";
    private $id_banco;
    private $codigo;
    private $nombre;
    private $id_authstat;
    private $tipo;

    
    function get_id_banco() {
        return $this->id_banco;
    }

    function get_codigo() {
        return $this->codigo;
    }

    function get_nombre() {
        return $this->nombre;
    }

    function get_id_authstat() {
        return $this->id_authstat;
    }

    function get_tipo() {
        return $this->tipo;
    }

    function set_id_banco($id_banco) {
        $this->id_banco = $id_banco;
    }

    function set_codigo($codigo) {
        $this->codigo = $codigo;
    }

    function set_nombre($nombre) {
        $this->nombre= $nombre;
    }

    function set_id_authstat($id_authstat) {
        $this->id_authstat = $id_authstat;
    }

    function set_tipo($tipo) {
        $this->tipo = $tipo;
    }

    public function select_min(){
        $sql = "SELECT id_banco,codigo,nombre,id_authstat,tipo FROM ef_bancos";
        return self::execute_select($sql);
    }
}
