<?php

class Blacklist extends Model {
    public static $id_tabla = 'id_blacklist';

    const IDM_DEFAULT = 2;
    const SEMAFORO_OCUPADO = '1';
    const SEMAFORO_LIBRE = '0';
    const EVATEST_EXCLUIDO = 1;
    const EVATEST_INCLUIDO = 2;

    private $id_blackist;
    private $regla;
    private $id_authstat;
    private $id_auth;
    private $comentario;
    private $fechahora;
    private $id_motivo;

    public function get_id_blacklist(){
        return $this->id_blackist;
    }

    public function get_regla(){
        return $this->regla;
    }

    public function get_id_authstat(){
        return $this->id_authstat;
    }

    public function get_id_auth(){
        return $this->id_auth;
    }

    public function get_comentario(){
        return $this->comentario;
    }

    public function get_fechahora(){
        return $this->fechahora;
    }

    public function get_id_motivo(){
        return $this->id_motivo;
    }

    public function set_id_blacklist($variable){
        $this->id_blacklist = $variable;
        return $this->id_blacklist;
    }

    public function set_regla($variable){
        $this->regla = $variable;
        return $this->regla;
    }

    public function set_id_authstat($variable){
        $this->id_authstat = $variable;
        return $this->id_authstat;
    }

    public function set_id_auth($variable){
        $this->id_auth = $variable;
        return $this->id_auth;
    }

    public function set_comentario($variable){
        $this->comentario = $variable;
        return $this->comentario;
    }

    public function set_fechahora($variable){
        $this->fechahora = $variable;
        return $this->fechahora;
    }

    public function set_id_motivo($variable){
        $this->id_motivo = $variable;
        return $this->id_motivo;
    }

    public function select_blacklist($variables=false){
        $filtros = self::preparar_filtros($variables);

        $sql = "SELECT * FROM ef_blacklist $filtros";

        return self::execute_select($sql,$variables,10000);
    }

}