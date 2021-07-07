<?php
// namespace Classes;
class Gestor_de_cookies{
	const ACTIVAR_PARCHE_TEMPORAL=false;
        public static function set_cookie($name, $value){
            setcookie($name, $value, time()+60*60*24*60);
        }

        public static function set($id_usuario,$tipo_usuario,$cookie_real=true){

		
		if($tipo_usuario==LOGIN_DE_USUARIO_INTERNO) {
			$authcode=1;
			$value=$id_usuario;
		}
		$value=htmlentities($value);
		if($cookie_real){
			$sesion=88;
			$resultado=setcookie($GLOBALS['COOKIE_NAME'], $value, time() + $GLOBALS['COOKIE_EXP_TIME'], COOKIE_PATH,COOKIE_DOMAIN,COOKIE_SECURE,COOKIE_HTTPONLY);
		}
		else{
			if(!CONSOLA){
				return false;
			}
			# Meta_application puede necesitar crear cookies falsas
			$resultado=true;
			$_SERVER['REMOTE_ADDR']='fake';
			$sesion=99;
		}
		if(!$resultado) return false;

		if(ACTIVAR_LOG_APACHE_DE_COOKIES) developer_log('Cookie escrita: '.$value);	
		date_default_timezone_set('UTC');		
		$useris=new Useris();
		$useris->set_id_auth($id_usuario);
		$useris->set_fecha(date('Y-m-d h:i:s'));
		$useris->set_sesion($sesion); #WTF
		$useris->set_machine($_SERVER['REMOTE_ADDR']);
		$useris->set_id_authcode($authcode); # 2 Si es un usuario externo. 1 Si es Interno.
		$useris->set_cstr($value);
		$useris->set_punch($sesion);
		if($useris->update_insert()) return $value;
		return false;
	}
	private static function pre_cifrar($value){
		return self::pre_cifrado_heredado($value);

		$gestor_de_hash=new Gestor_de_hash(md5(microtime(true)));
		return $gestor_de_hash->cifrar($value);
	}
	public static function get($value,$tipo_usuario){
		$value=html_entity_decode($value);
                $authcode=1;
		$recordset=Useris::select(array('cstr'=>$value,'id_authcode'=>$authcode));
		if(!$recordset) return false;
		if($recordset->RowCount()==0 OR $recordset->RowCount()>1) return false;
		$row=$recordset->FetchRow(0);
		if(!CONSOLA){
			if($row['machine']!=$_SERVER['REMOTE_ADDR']){
				developer_log('El usuario ingresa desde nueva Ip.');
				if(self::ACTIVAR_PARCHE_TEMPORAL){
					if($GLOBALS['SISTEMA']=='INTERNO'){
						developer_log('Utilizand Parche Temporal');
						return $row['id_auth'];
					}
				}	
				return false;
			}
		}

		if(ACTIVAR_LOG_APACHE_DE_COOKIES) developer_log('Cookie leida: '.$value);
		return $row['id_auth'];
	}
	public static function destruir(){
		# No destruyo el registro useris
		unset($_COOKIE[$GLOBALS['COOKIE_NAME']]);
		return setcookie($GLOBALS['COOKIE_NAME'], null, -1, '/');
	}
	# Funcion heredada del viejo desarrollo usada para generar las cookies
	private static function pre_cifrado_heredado($t){
	    $t=preg_replace("/0/ms",'s',$t);
	    $t=preg_replace("/9/ms",'e',$t);
	    $t=preg_replace("/8/ms",'R',$t);
	    $t=preg_replace("/7/ms",'a',$t);
	    $t=preg_replace("/6/ms",'y',$t);
	    $t=preg_replace("/5/ms",'k',$t);
	    $t=preg_replace("/4/ms",'l',$t);
	    $t=preg_replace("/3/ms",'L',$t);
	    $t=preg_replace("/2/ms",'A',$t);
	    $t=preg_replace("/1/ms",'u',$t);
	    return self::genNRand(8).$t.self::genNRand(13);
	}
	# Funcion heredada del viejo desarrollo usada para generar las cookies
	private static function genNRand($q) {
	    $i=$rac='';
	    for ($i=0; $i<$q ;$i++) {
	      $er=rand(0,2);
	      switch ($er) {
	        case 0:  $rac.=rand(0,9);   break;
	        case 1:  $rac.=chr(rand(65,90));   break;
	        case 2:  $rac.=chr(rand(98,122));   break;
	      }
	    }
	    return ($rac);
	}
}