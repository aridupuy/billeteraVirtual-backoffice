<?php
// namespace Models;
class Auth extends Usuario{

    public static $prefijo_tabla='ho_';
    public static $id_tabla='id_auth';

    private $id_auth;
    private $authname;
    private $authpass;
    private $authmail;
    private $id_authcode;
    private $completo;
    private $id_authstat;
    private $id_area;

    public function get_id_auth(){ return $this->id_auth;}
    public function get_authname(){ return $this->authname;}
    public function get_authpass(){ return $this->authpass;}
    public function get_authmail(){ return $this->authmail;}
    public function get_id_authcode(){ return $this->id_authcode;}
    public function get_completo(){ return $this->completo;}
    public function get_id_authstat(){ return $this->id_authstat;}
    public function get_id_area(){ return $this->id_area;}

    public function set_id_auth($variable){ $this->id_auth=$variable; return $this->id_auth;}
    public function set_authname($variable){ $this->authname=$variable; return $this->authname;}
    public function set_authpass($variable){ $this->authpass=$variable; return $this->authpass;}
    public function set_authmail($variable){ $this->authmail=$variable; return $this->authmail;}
    public function set_id_authcode($variable){ $this->id_authcode=$variable; return $this->id_authcode;}
    public function set_completo($variable){ $this->completo=$variable; return $this->completo;}
    public function set_id_authstat($variable){ $this->id_authstat=$variable; return $this->id_authstat;}
    public function set_id_area($variable){ $this->id_area=$variable; return $this->id_area;}
    
    public static function select_min($variables=null){
        unset($variables['dataTable_length']);
        if (isset($variables['authname'])) {
            $variables['A.authname']  =  $variables['authname'];
            unset($variables['authname']);     
        }
        if (isset($variables['authmail'])) {
            $variables['A.authmail']  =  $variables['authmail'];
            unset($variables['authmail']);     
        }
        if (isset($variables['completo'])) {
            $variables['A.completo']  =  $variables['completo'];
            unset($variables['completo']);     
        }
        if (isset($variables['area'])) {
            $variables['B.nombre']  =  $variables['area'];
            unset($variables['area']);     
        }
        $filtros=self::preparar_filtros($variables);
        $sql="   SELECT A.*, B.nombre as area from ho_auth A left join ho_area B on A.id_area = B.id_area

                        $filtros
                        ORDER BY id_auth DESC";
        return self::execute_select($sql,$variables,10000);
    }
    
     public function calcular_passw2($password_sc) {

        $sql = "select crypt( ?,'MA') as resultado ";
        $p = array($password_sc);
        $record = self::execute_select($sql, $p);
        if ($record) {
            $row = $record->fetchRow();
            return utf8_decode($row['resultado']);
        }
        return '';
    }
    
    public static function select_auth_area($id_area=null){
        
        $sql="select * from ho_auth where id_area  = $id_area";
        return self::execute_select($sql,null,10000);
    }
    

}

