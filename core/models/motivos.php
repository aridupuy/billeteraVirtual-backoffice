<?php

// namespace Models;
class Motivos extends Model {
  public static $id_tabla = "id_motivo";
    public static $prefijo_tabla="ef_";
    private $id_motivo;
    private $motivo;
    
    function get_id_motivo() {
        return $this->id_motivo;
    }

    function get_motivo() {
        return $this->motivo;
    }

    function set_id_motivo($id_motivo) {
        $this->id_motivo = $id_motivo;
    }

    function set_motivo($motivo) {
        $this->motivo = $motivo;
    }



}
