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

    public function select_min($variables=false){
        unset($variables['dataTable_length']);
        unset($variables['checkbox_todo']);
        unset($variables['selector_']);
    
        $filtros = self::preparar_filtros($variables);
        $and="";

        $sql = "SELECT A.id_blacklist,A.fechahora,A.regla,A.comentario,D.authname,motivo,B.authstat FROM ef_blacklist A
                LEFT JOIN ho_authstat B ON A.id_authstat = B.id_authstat
                LEFT JOIN ef_motivos C ON A.id_motivo = C.id_motivo
                LEFT JOIN ho_auth D ON A.id_auth = D.id_auth $filtros $and
                ORDER BY id_blacklist DESC";
        
        // var_dump($variables);
        // exit;

        return self::execute_select($sql,$variables,10000);
    }

    /**
     * Procesa el JSON para convertirlo en una sola linea de string
     */
    public function procesarJSON($json){
        $array = json_decode($json,true);
        $result = array();
        array_walk_recursive($array, function($v) use (&$result) {
           $result[] = $v;
        });
        $result = implode(',',$result);
        return $result;
    }

    /**
     * Convierte string en json
     * Ejemplo: generarJSON('and','>,monto,50000,<,monto,150000,==,apellido,angeluk');
     */
    public function generarJSON($operacion,$valores){
        $array = array();
        if($operacion == 'and' || $operacion == 'or'){
            $array['operacion']=$operacion;
            $valores = explode(",", $valores);
            $operacion = 0;
            $indice = 0;
            foreach ($valores as $valor){
                if($operacion == 0){
                    $array['que'][$indice]['operacion'] = $valor;
                }else{
                    $array['que'][$indice]['que'][]['valor'] = (is_numeric($valor))?(float)$valor:$valor;
                }
                if($operacion==2){
                    $operacion=0;
                    $indice++;
                }else{
                    $operacion++;
                }
            }
            return json_encode($array);
        }else{
            $array['operacion']=$operacion;
            $valores = explode(',',$valores);
            foreach ($valores as $valor) {
                $array['que'][]['valor'] = $valor;
            }
            return json_encode($array);
        }
    }

}