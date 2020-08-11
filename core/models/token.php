<?php
class Token extends Model{
    public static $id_tabla="id_token";
    public static $secuencia=GENERAR_ID_MAXIMO;
    private $id_token;
    private $id_usuario;
    private $token;
    private $fecha_gen;
    private $ultimo_uso;
    public function get_ultimo_uso() {
        return $this->ultimo_uso;
    }

    public function set_ultimo_uso($ultimo_uso) {
        $this->ultimo_uso = $ultimo_uso;
        return $this;
    }

        
    public function get_id_token() {
        return $this->id_token;
    }

    public function get_id_usuario() {
        return $this->id_usuario;
    }

    public function get_token() {
        return $this->token;
    }

    public function get_fecha_gen() {
        return $this->fecha_gen;
    }

    public function set_id_token($id_token) {
        $this->id_token = $id_token;
        return $this;
    }

    public function set_id_usuario($id_usuario) {
        $this->id_usuario = $id_usuario;
        return $this;
    }

    public function set_token($token) {
        $this->token = $token;
        return $this;
    }

    public function set_fecha_gen($fecha_gen) {
        $this->fecha_gen = $fecha_gen;
        return $this;
    }
    public static function select_token_activo($id){
        $sql="select * from ef_token where id_usuario=? and ultimo_uso>=(now() - interval '".INTERVALO_SESION." ".TIEMPO."')";
        $variables=array($id);
        return self::execute_select($sql, $variables);
        
    }
    public static function checktoken($token){
       $sql="select * from ef_token where token=? and ultimo_uso>=(now() - interval '".INTERVALO_SESION." ".TIEMPO."')";
       $variables=array($token);
       $rs=self::execute_select($sql, $variables);
       if($rs->rowCount()<=0){
           return false;
       }
       return true;
    }
    public static function select_token($token){
       $sql="select * from ef_token where token=? and ultimo_uso>=(now() - interval '".INTERVALO_SESION." ".TIEMPO."')";
       $variables=array($token);
       $rs=self::execute_select($sql, $variables);
       if($rs->rowCount()<=0){
           return false;
       }
       return new Token($rs->fetchRow());
    }
}
