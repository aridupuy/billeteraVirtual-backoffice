<?php

class Instancias extends Model
{

    public static $id_tabla='id_instancias';
    public static $prefijo_tabla='cd_';
    public static $secuencia='sq_instancias';

    private $id_instancias;
    private $id_auth; 
    private $id_authcode; 
    private $xml;

    public function set_id_instancias($variable){$this->id_instancias=$variable; return $this->id_instancias;}
    public function get_id_instancias(){return $this->id_instancias;}
    public function set_id_auth($variable){$this->id_auth=$variable; return $this->id_auth;}
    public function get_id_auth(){return $this->id_auth;}
    public function set_id_authcode($variable){$this->id_authcode=$variable; return $this->id_authcode;}
    public function get_id_authcode(){return $this->id_authcode;}
    public function set_xml($variable){$this->xml=$variable; return $this->xml;}
    public function get_xml(){return $this->xml;}    

    # Solo puede ser llamada desde Gestor_de_instancias
    public static function eliminar_instancias($id_auth,$id_authcode)
    {   
        $variables=array();
        $variables['id_auth']=$id_auth;
        $variables['id_authcode']=$id_authcode;

        $sql="DELETE FROM cd_instancias WHERE id_auth=? AND id_authcode=? ";
        return self::execute_select($sql,$variables);
    }
}