<?php

class Gestor_de_permisos{
	const SEPARADOR_PERMISOS='*';
	public static function puede($puede)
	{
		if(!ACTIVAR_PERMISOS) return true;
		if(Application::$usuario)
			$id_usuario=Application::$usuario->get_id();
		else return self::prohibir_acceso();
		$tipo_usuario=false;
		if(get_class(Application::$usuario)=='Auth') $tipo_usuario='Auth';
		if(get_class(Application::$usuario)=='Usumarchand') $tipo_usuario='Usumarchand';

		if(!$tipo_usuario) return self::prohibir_acceso();

		
		$respuesta=false;
		$permiso=Permiso::select_puede($puede);
		if(!$permiso OR !$permiso=$permiso->FetchRow()) return self::prohibir_acceso();
		if($tipo_usuario=='Auth'){
			$authpermiso=Authpermiso::puede(Application::$usuario->get_id(),$permiso['id_permiso']);
			$permitir=$authpermiso->FetchRow(0);
			if($permitir[0]>=1)	$respuesta=true; 
		}
		elseif($tipo_usuario=='Usumarchand'){
			$usupermiso=Usupermiso::puede(Application::$usuario->get_id(),$permiso['id_permiso']);
			$permitir=$usupermiso->FetchRow(0);
			if($permitir[0]>=1)	$respuesta=true; 
		}
			
		if($respuesta AND ACTIVAR_LOG_APACHE_DE_PERMISOS) 
			developer_log('Permiso aceptado | id_'.strtolower($tipo_usuario).': '.Application::$usuario->get_id().' | puede: '.$puede);

		if(!$respuesta AND ACTIVAR_LOG_APACHE_DE_PERMISOS) 
			developer_log('Permiso denegado | id_'.strtolower($tipo_usuario).': '.Application::$usuario->get_id().' | puede: '.$puede);
		if($respuesta) return $respuesta;
		return self::prohibir_acceso();
	}
	public static function cadena_de_permisos()
	{
		$id_usuario=false;
		if(Application::$usuario){
			$id_usuario=Application::$usuario->get_id();
		}

		$cadena_de_permisos='';
		if($id_usuario){
			$tipo_usuario=false;
			if(get_class(Application::$usuario)=='Auth') $tipo_usuario='Auth';
			elseif(get_class(Application::$usuario)=='Usumarchand') $tipo_usuario='Usumarchand';
			if($tipo_usuario){
				if($tipo_usuario=='Auth'){
					$recordset=Authpermiso::select_cadena_de_permisos(Application::$usuario->get_id()); 
				}
				elseif($tipo_usuario=='Usumarchand'){
					$recordset=Usupermiso::select_cadena_de_permisos(Application::$usuario->get_id());
				}
				if($recordset AND $recordset->RowCount()>0){
					foreach ($recordset as $row) {
						$cadena_de_permisos.=$row['puede'].self::SEPARADOR_PERMISOS;
					}
				}
			}
		}
		return $cadena_de_permisos;		
	}
	private static function prohibir_acceso()
	{
		return false;
	}
        public static function tiene_permiso($puede){
            $rs_perm= Permiso::select(array("puede"=>$puede));
            $row=$rs_perm->fetchRow();
            $usumarchand= Application::$usuario;
            $rs_usup= Usupermiso::select(array("id_usumarchand"=>$usumarchand->get_id_usumarchand(),"id_permiso"=>$row["id_permiso"]));
            if($rs_usup->rowCount()>0)
                return true;
            return false;
        }
}