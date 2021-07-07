<?php
// namespace Models;
class Authpermiso extends Model{

	public static $id_tabla='id_authpermiso';
	public static $prefijo_tabla='ho_';

	private $id_authpermiso;
	private $id_auth;
	private $id_permiso;

	public function get_id_authpermiso(){return $this->id_authpermiso;}
	public function get_id_auth(){return $this->id_auth;}
	public function get_id_permiso(){return $this->id_permiso;}

	public function set_id_authpermiso($variable){ $this->id_authpermiso=$variable; return $this->id_authpermiso;}
	public function set_id_auth($variable){ $this->id_auth=$variable; return $this->id_auth;}
	public function set_id_permiso($variable){ $this->id_permiso=$variable; return $this->id_permiso;}

	# Solo llamar desde Gestor_de_permisos
	public static function puede($id_auth,$id_permiso)
	{

		# Solo soporta tres niveles de permisos.
		# Analoga a Usupermiso::puede

		$variables=array();
		$variables['id_auth']=$id_auth;
		$variables['A.id_permiso']=$id_permiso;
		$variables['A.id_authstat']=Authstat::ACTIVO;
		$variables['B.id_permiso']=$id_permiso;
		$variables['B.id_authstat']=Authstat::ACTIVO;
		$variables['C.id_permiso']=$id_permiso;
		$variables['C.id_authstat']=Authstat::ACTIVO;
		
		$sql="	SELECT count(id_authpermiso)
						FROM ho_authpermiso
						WHERE id_auth=?
						AND id_permiso 
						IN (SELECT A.id_permiso
							FROM ho_permiso A
							WHERE A.id_permiso IN 
								(SELECT B.permipapi FROM ho_permiso B WHERE B.id_permiso IN 
									(SELECT C.permipapi FROM ho_permiso C WHERE C.id_permiso=? AND C.id_authstat=?)
								OR B.id_permiso=?  AND B.id_authstat=?)
							OR A.id_permiso=? AND A.id_authstat=?)";
		
		return self::execute_select($sql,$variables);
	}
	# Solo llamar desde Gestor_de_permisos
	public static function select_cadena_de_permisos($id_auth)
	{
		$sql="SELECT B.puede FROM ho_authpermiso A LEFT JOIN ho_permiso B ON A.id_permiso=B.id_permiso WHERE A.id_auth=?";
		return self::execute_select($sql,array('id_auth'=>$id_auth));
	}
        
        public function select_authpermiso_sitetodo($id_usumarchand){
            $sql="SELECT * FROM cd_authpermiso A "
            . "left join  ho_Controlador_sitio B  on A.id_permiso=B.id_permiso "
            . "WHERE A.id_auth= ? and B.id_permiso > ?";
            $variables[]=$id_usumarchand;
            $variables[]=50000;
            return self::execute_select($sql,$variables);
        }
}