<?php

// namespace Models;
class Usuario extends Model {
  public static $id_tabla = "id_usuario";
    public static $prefijo_tabla="ef_";
    private $id_usuario;
    private $nombre_completo;
    private $id_authstat;
    private $id_cuenta;
    private $id_tarjeta_usuario;
    private $nombre_usuario;
    private $password;
    private $email;
    private $celular;
    private $cod_area;
    private $valida_cel;
    private $valida_mail;
    
    public function get_valida_cel() {
        return $this->valida_cel;
    }

    public function get_valida_mail() {
        return $this->valida_mail;
    }

    public function set_valida_cel($valida_cel) {
        $this->valida_cel = $valida_cel;
        return $this;
    }

    public function set_valida_mail($valida_mail) {
        $this->valida_mail = $valida_mail;
        return $this;
    }

        public function get_celular() {
        return $this->celular;
    }

    public function get_cod_area() {
        return $this->cod_area;
    }

    public function set_celular($celular) {
        $this->celular = $celular;
        return $this;
    }

    public function set_cod_area($cod_area) {
        $this->cod_area = $cod_area;
        return $this;
    }

        
    public function get_email() {
        return $this->email;
    }

    public function set_email($email) {
        $this->email = $email;
        return $this;
    }

        public function get_id_usuario() {
        return $this->id_usuario;
    }

    public function get_nombre_completo() {
        return $this->nombre_completo;
    }
    public function get_nombre_usuario() {
        return $this->nombre_usuario;
    }

    public function set_nombre_usuario($nombre_usuario) {
        $this->nombre_usuario = $nombre_usuario;
        return $this;
    }

        public function get_id_authstat() {
        return $this->id_authstat;
    }

    public function get_id_cuenta() {
        return $this->id_cuenta;
    }

    public function get_id_tarjeta_usuario() {
        return $this->id_tarjeta_usuario;
    }


    public function get_password() {
        return $this->password;
    }

    public function set_id_usuario($id_usuario) {
        $this->id_usuario = $id_usuario;
        return $this;
    }

    public function set_nombre_completo($nombre_completo) {
        $this->nombre_completo = $nombre_completo;
        return $this;
    }

    public function set_id_authstat($id_authstat) {
        $this->id_authstat = $id_authstat;
        return $this;
    }

    public function set_id_cuenta($id_cuenta) {
        $this->id_cuenta = $id_cuenta;
        return $this;
    }

    public function set_id_tarjeta_usuario($id_tarjeta_usuario) {
        $this->id_tarjeta_usuario = $id_tarjeta_usuario;
        return $this;
    }

    

    public function set_password($password) {
        $this->password = $password;
        return $this;
    }
    public function __set($property, $value) {
        if(property_exists($this, $property)){
            if($property=="password"){
                $this->$property= $this->calcular_passw2($value);
            }
            else
                $this->$property=$value;
        }
    }
    public static function select_login($usuario, $password) {
        $array["usuario"] = $usuario;
        $array["password"] = $password;
        $sql = "select * from ef_usuario where nombre_usuario=? and crypt(?, password) = password";
//      print_r($sql);
        return self::execute_select($sql, $array);
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
    public static function select_cel_mail($cel="",$mail=""){
        $sql = "select * from ef_usuario where celular= ? or email= ?";
        $variables=array($cel,$mail);
        return self::execute_select($sql,$variables);
    }
    public function login($usuario, $password, $comercio = false) {

        if (is_numeric($usuario) OR strlen($usuario) > 16)
            return false;
        if (is_numeric($password) OR strlen($password) > 32)
            return false;
        $variables = array();

        if (get_called_class() == 'Auth') {
            $variables['authname'] = $usuario;
            $variables['id_authstat'] = Authstat::ACTIVO;
            $variables['authpass'] = $password;
            $sql = "	SELECT id_auth FROM ho_auth WHERE authname= ? AND id_authstat=? AND crypt(?, authpass)=authpass";
        }
        if (get_called_class() == 'Usumarchand') {
            if (!$comercio) {
                return false;
            }
            $variables['mercalpha'] = strtoupper($comercio);
            $variables['username'] = $usuario;
            $variables[] = Authstat::ACTIVO;
            $variables[] = Authstat::ACTIVO;
            $variables['userpass'] = $password;
            $sql = "	SELECT A.id_usumarchand FROM cd_usumarchand A 
					LEFT JOIN cd_marchand B ON A.id_marchand=B.id_marchand 
					WHERE B.mercalpha=?
					AND A.username= ? AND A.id_authstat=? 
					AND B.id_authstat=? 

					AND crypt(?, A.userpass)=A.userpass";
        }
        
        if (!isset($sql))
            return false;

        $recordset = self::execute_select($sql, $variables);
        if (!$recordset)
            return false;

        if ($recordset->RowCount() != 1)
            $resultado = false;
        else {
            $result = $recordset->FetchRow(0);
            $resultado = $result[0];
        }

        return $resultado;
    }

//	public function setPassword($password){
//		$salt='$2a$06$';
//	      for ($i=0; $i < 22; $i++) {
//	          $salt.=chr(rand(ord('A'), ord('Z')));
//	      }
//		if(get_called_class()=='Auth') 
//			return $this->set_authpass(crypt($password,$salt));
//		elseif(get_called_class()=='Usumarchand') 
//			return $this->set_userpass(crypt($password,$salt));
//		return false;
//	}

    public function updatePassword_usumarchand($password) {
        $sql = " update cd_usumarchand set userpass=crypt('" . $password . "','MA') where 
               id_usumarchand=" . $this->get_id();
        return self::execute($sql);
    }

    /* para ser llamada cueando es auth */

    private function calcular_passw($password_sc) {

        $sql = "select crypt( ?,'MA') as resultado ";
        $p = array($password_sc);
        $record = self::execute_select($sql, $p);
        if ($record) {
            $row = $record->fetchRow();
            return utf8_decode($row['resultado']);
        }
        return '';
    }

    public function getName() {
        if (get_called_class() == 'Auth')
            return $this->get_authname();
        if (get_called_class() == 'Usumarchand')
            return $this->get_username();
    }

    public function setName($variable) {
        if (get_called_class() == 'Auth')
            return $this->set_authname($variable);
        if (get_called_class() == 'Usumarchand')
            return $this->set_username($variable);
    }

    public function get_id_marchand() {
        if (get_called_class() == 'Auth')
            return false;
        if (get_called_class() == 'Usumarchand')
            return $this->get_id_marchand();
    }

    public function getPassword() {
        if (get_called_class() == 'Auth')
            return $this->get_authpass();
        if (get_called_class() == 'Usumarchand')
            return $this->get_userpass();
    }

    /* para ser llamada cueando es auth */

    public function select_usuarios() {
        $sql = " select A.*, b.authstat as estado from ef_usuario a left join ho_authstat b on  a.id_authstat = b.id_authstat ";
        return self::execute($sql);
    }
    public function updatePassword_auth($password) {
        $sql = " update ho_auth set authpass=crypt('" . $password . "','MA') where 
               id_auth=" . $this->get_id();
        return self::execute($sql);
    }

    public function setPassword($password) {
        // $salt='$2a$06$';
        //   $salt='MAldITaiNTernET';
        $salt = 'MA';
        $result = '';

        // for ($i=0; $i < 22; $i++) {
        //   $salt.=chr(rand(ord('A'), ord('Z')));
        //}
        if (get_called_class() == 'Auth') {
            $result = $this->calcular_passw($password);

            return $this->set_authpass($result);
        } elseif (get_called_class() == 'Usumarchand') {

            $result = $this->calcular_passw($password);
            return $this->set_userpass($result);
        }
        return false;
    }
    
//    public function calcular_passw2($password_sc) {
//
//        $sql = "select crypt( ?,'MA') as resultado ";
//        $p = array($password_sc);
//        $record = self::execute_select($sql, $p);
//        if ($record) {
//            $row = $record->fetchRow();
//            return utf8_decode($row['resultado']);
//        }
//        return '';
//    }

}
