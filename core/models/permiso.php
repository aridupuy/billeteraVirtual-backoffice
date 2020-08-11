<?php
// namespace Models;
class Permiso extends Model{

	public static $id_tabla='id_permiso';
	public static $prefijo_tabla='ho_';
	const PERMISO_DE_ADMINISTRADORES=258; # El id del permiso necesario para considerar a un Usumarchand como Administrador del Marchand.
	const ID_MINIMO_NUEVOS_PERMISOS=50000; #Permisos con id superiores a 50000 son nuevos
	const ID_MINIMO_NUEVOS_PERMISOS_EXTERNO=60000;
        const PUEDE_MENU_HERRAMIENTAS_COBRANZAS='site_servicio_menu';
        const PUEDE_MENU_DATOS_CUENTA='site_cuenta_menu';
        const PUEDE_MENU_MOVIMIENTOS="site_historial_menu";
        const PUEDE_MENU_RETIROS="site_retiro_menu";
	private $id_permiso;
	private $permiso;
	private $puede;
	private $g10;
	private $aplicapeu;
	private $aplicacd;
	private $pordi;
	private $sordi;
	private $permipapi;
	private $aplica_clima;
	private $id_authstat;

	public function get_id_permiso(){return $this->id_permiso;}
	public function get_permiso(){return $this->permiso;}
	public function get_puede(){return $this->puede;}
	public function get_g10(){return $this->g10;}
	public function get_aplicapeu(){return $this->aplicapeu;}
	public function get_aplicacd(){return $this->aplicacd;}
	public function get_pordi(){return $this->pordi;}
	public function get_sordi(){return $this->sordi;}
	public function get_permipapi(){return $this->permipapi;}
	public function get_aplica_clima(){return $this->aplica_clima;}
	public function get_id_authstat(){return $this->id_authstat;}

	public function set_id_permiso($variable){ $this->id_permiso=$variable; return $this->id_permiso;}
	public function set_permiso($variable){ $this->permiso=$variable; return $this->permiso;}
	public function set_puede($variable){ $this->puede=$variable; return $this->puede;}
	public function set_g10($variable){ $this->g10=$variable; return $this->g10;}
	public function set_aplicapeu($variable){ $this->aplicapeu=$variable; return $this->aplicapeu;}
	public function set_aplicacd($variable){ $this->aplicacd=$variable; return $this->aplicacd;}
	public function set_pordi($variable){ $this->pordi=$variable; return $this->pordi;}
	public function set_sordi($variable){ $this->sordi=$variable; return $this->sordi;}
	public function set_permipapi($variable){ $this->permipapi=$variable; return $this->permipapi;}
	public function set_aplica_clima($variable){ $this->aplica_clima=$variable; return $this->aplica_clima;}
	public function set_id_authstat($variable){ $this->id_authstat=$variable; return $this->id_authstat;}


	public static function select_puede($puede){
		$variables=array();
		$variables['puede']=$puede;
		$sql="SELECT * FROM ho_permiso WHERE puede= ? ";
		return self::execute_select($sql,$variables);
	}
    public static function select_permisos_sistema_externo(){
        
        $sql="SELECT * FROM ho_permiso WHERE id_permiso>=? ORDER BY id_permiso ASC";
        $variables=array();
        $variables[]=self::ID_MINIMO_NUEVOS_PERMISOS_EXTERNO;
        return self::execute_select($sql, $variables);
        
    }
	public static function select_permisos_de_usuario($id_auth){
		$variables=array();
		$variables['B.id_auth']=$id_auth;
		$variables['A.id_permiso']=self::ID_MINIMO_NUEVOS_PERMISOS;
		$variables[]=self::ID_MINIMO_NUEVOS_PERMISOS_EXTERNO;
		$variables['id_authstat']=Authstat::ACTIVO;
		$sql=" 	SELECT DISTINCT(A.id_permiso), A.puede,A.permiso,B.id_authpermiso, CASE WHEN B.id_authpermiso IS NULL THEN 0 ELSE 1 END as habilitado
						FROM ho_permiso A left join ho_authpermiso B on (A.id_permiso =B.id_permiso AND B.id_auth= ? )
						WHERE A.id_permiso>= ?
						AND A.id_permiso<?
						AND A.id_authstat=?
						ORDER BY id_permiso ASC ";

		return self::execute_select($sql,$variables);
	}
	public static function select_id_usumarchand($id_usumarchand){
		$variables=array();
		$variables['B.id_usumarchand']=$id_usumarchand;
		$variables['A.id_permiso']= 1;
		$variables['id_authstat']= 1;
		$sql="	SELECT A.id_permiso, A.permiso, A.puede, count(B.id_usumarchand) as estado
						FROM ho_permiso A
						LEFT JOIN cd_usupermiso B ON (A.id_permiso=B.id_permiso AND B.id_usumarchand=?)
						WHERE A.id_permiso>?
						AND A.id_authstat=?
                                                AND (A.puede LIKE 'site_%' OR A.puede LIKE 'mod_%' OR A.puede LIKE 'adaut_%') 
						AND A.aplicacd=1
						GROUP BY 1,2
						ORDER BY 1 ASC";
                
		return self::execute_select($sql,$variables);
	}
	public static function select_viejos_id_usumarchand($id_usumarchand){
		$variables=array();
		$variables['B.id_usumarchand']=$id_usumarchand;
		$variables['A.id_permiso']=self::ID_MINIMO_NUEVOS_PERMISOS;
		$variables['id_authstat']=Authstat::ACTIVO;
		$sql="	SELECT A.id_permiso, A.permiso, count(B.id_usumarchand) as estado
						FROM ho_permiso A
						LEFT JOIN cd_usupermiso B ON (A.id_permiso=B.id_permiso AND B.id_usumarchand=?)
						WHERE A.id_permiso<? 
						AND A.id_authstat=?
						AND A.aplicacd=1
						GROUP BY 1,2
						ORDER BY 1 ASC";
		return self::execute_select($sql,$variables);
	}
	public static function eliminar_permiso_auth($id_authpermiso){
		$sql="DELETE FROM ho_authpermiso WHERE id_authpermiso= ?";
		return self::execute_select($sql,$id_authpermiso);
	}
	public static function eliminar_permiso_usumarchand($id_usupermiso){
		$sql="DELETE FROM cd_usupermiso WHERE id_usupermiso= ?";
		return self::execute_select($sql,$id_usupermiso);
	}
}