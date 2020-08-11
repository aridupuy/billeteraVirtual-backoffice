<?php

class Gestor_de_tareas
{
	public static function agendar($nombre_controller, $nombre_metodo, $usuario, $variables, $archivo=false)
	{
        $tarea=new Tarea();
        if(get_class($usuario)==='Auth')
        	$id_authcode=Authcode::USUARIO_INTERNO;
        elseif(get_class($usuario)==='Usumarchand')
        	$id_authcode=Authcode::USUARIO_EXTERNO;
        else return false;
        if($id_authcode==Authcode::USUARIO_INTERNO OR !$usuario->get_usuvip())
            $id_usuario=$usuario->get_id();
        else{
            $recordset=Usumarchand::select_administradores($usuario->get_id_marchand());
            if(!$recordset OR $recordset->RowCount()===0)
                return false;
            $row=$recordset->FetchRow();
            $id_usuario=$row['id_usumarchand'];
        }
        $tarea->set_id_usuario($id_usuario);
        $tarea->set_id_authcode($id_authcode);
        $tarea->set_comando($tarea->generar_comando($nombre_controller,$nombre_metodo, $variables,$archivo, $id_authcode));
        return $tarea->set();
	}
}