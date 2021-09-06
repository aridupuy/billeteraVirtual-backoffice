<?php

class Blacklist extends Model {
    public static $id_tabla = 'id_blacklist';

    const IDM_DEFAULT = 2;
    const SEMAFORO_OCUPADO = '1';
    const SEMAFORO_LIBRE = '0';
    const EVATEST_EXCLUIDO = 1;
    const EVATEST_INCLUIDO = 2;

    private $id_blacklist;
    private $regla;
    private $id_authstat;
    private $id_auth;
    private $comentario;
    private $fechahora;
    private $id_motivo;

    public function get_id_blacklist(){
        return $this->id_blacklist;
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
        $this->id_blacklist= $variable;
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

    /**
     * Filtro de busqueda
     */
    public function select_min($variables=false){
        unset($variables['dataTable_length']);
        unset($variables['checkbox_todo']);
        unset($variables['selector_']);
        unset($variables['motivo_popup']);

        if (isset($variables['id_blacklist'])) {
            $variables['A.id_blacklist'] = $variables['id_blacklist'];
            unset($variables['id_blacklist']);
        }else{
            $and = "WHERE true ";
        }

        if (isset($variables['status'])) {
            $and .= "AND A.id_authstat in (". (int)$variables['status'] .")";
            unset($variables['status']);
        }

        if (isset($variables['motivo'])) {
            $and .= "AND A.id_motivo in (". (int)$variables['motivo'] .")";
            unset($variables['motivo']);
        }

        if (isset($variables['analista'])) {
            $and .= "AND A.id_auth in (". (int)$variables['analista'] .")";
            unset($variables['analista']);
        }

        if (isset($variables['regla'])) {
            $and .= "AND (A.regla ilike '%" . $variables['regla'] . "%') ";
            unset($variables['regla']);
        }

        if (isset($variables['comentario'])) {
            $and .= "AND (A.comentario ilike '%" . $variables['comentario'] . "%') ";
            unset($variables['comentario']);
        }

        $filtros = self::preparar_filtros($variables);

        $sql = "SELECT A.id_blacklist,A.fechahora,A.regla,A.comentario,D.authname,C.motivo,B.authstat FROM ef_blacklist A
                LEFT JOIN ho_authstat B ON A.id_authstat = B.id_authstat
                LEFT JOIN ef_motivos C ON A.id_motivo = C.id_motivo
                LEFT JOIN ho_auth D ON A.id_auth = D.id_auth $filtros $and
                ORDER BY id_blacklist DESC";
        
        return self::execute_select($sql,$variables,10000);
    }

    /**
     * Borrar Regla
     */
    public function borrar_regla($id_blacklist){
        $sql = "DELETE FROM ef_blacklist WHERE id_blacklist='$id_blacklist'";
        return self::execute($sql);
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
     * Ejemplo: generarJSON('and,>,monto,50000,<,monto,150000,==,apellido,angeluk');
     */
    public function generarJSON($string){
        $arrays = explode(",", $string);
        $operacion = $arrays[0];
        $valores = array_splice($arrays,1);
        $array = array();
        if($operacion == 'and' || $operacion == 'or'){
            $array['operacion']=$operacion;
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
            foreach ($valores as $valor) {
                $array['que'][]['valor'] = $valor;
            }
            return json_encode($array);
        }
    }

    
}