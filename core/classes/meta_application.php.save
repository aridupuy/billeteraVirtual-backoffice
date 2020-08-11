<?php

$meta_application = new Meta_application();
exit();

class Meta_application {

    const BLOQUEAR_EJECUCION_USUVIP = false;
    const ACTIVAR_DEBUG = true;
    const ACTIVAR_SLEEP = false;
    const PERMITIR_COOKIES_FALSAS = true;
    const TRUE = 'TRUE';
    const FALSE = 'FALSE';
    const PATH_ENVVARS =
    '/etc/apache2/envvars_tareas';
    private $application = false;
    private $errores = 0;
    private $fecha_inicio_meta_application;
    private $hoy_es_feriado = null;
    private $usuario = null;
    public function __construct
            
    ()
    {
    $this-> fecha_inicio_meta_application =

    new Datetime('now');

    if(!defined('CONSOLA
        ')) 
        define('CONSOLA', true);

    $GLOBALS['SISTEMA'] = 'EXTERNO';
    $authcode_anterior = false;
    $directorio = dirname  (__FILE__) . '/';
    $this->cargar_variables_de_entorno(self::PATH_ENVVARS);
    require_once $directorio . '../config.ini';
    $recordset = Tarea::tarea_siguiente($this->fecha_inicio_meta_application);
    while ($recordset->RowCount() == 1) {
        $row = $recordset->FetchRow();
        $tarea = new Tarea($row);
        if ($this->hoy_es_feriado() AND $tarea->get_saltear_feriados() === 't') {

            error_log('Hoy es feriado, se evita ejecución de la tarea con id: ' . $tarea->get_id() . '. ');
        } else {
            if ($authcode_anterior != $tarea->get_id_authcode()) {
                if ($tarea->get_id_authcode() == Authcode::USUARIO_EXTERNO) {
                    $GLOBALS['SISTEMA'] = 'EXTERNO';
                    $this->usuario = new Usumarchand();
                    $this->usuario->get($tarea->get_id_usuario());
                    developer_log("Usuario logueado: " . $this->usuario->get_username());
                } elseif ($tarea->get_id_authcode() == Authcode::USUARIO_INTERNO) {
                    $GLOBALS['SISTEMA'] = 'INTERNO';
                    $this->usuario = new Auth();
                    $this->usuario->get($tarea->get_id_usuario());
                    developer_log("Usuario logueado: " . $this->usuario->get_authname());
                } else
                    return false;
                error_log('Cambio al sistema ' . $GLOBALS['SISTEMA']);
            }
            else {
                if ($tarea->get_id_authcode() == Authcode::USUARIO_EXTERNO) {
                    $GLOBALS['SISTEMA'] = 'EXTERNO';
                    $this->usuario = new Usumarchand();
                    $this->usuario->get($tarea->get_id_usuario());
                    developer_log("Usuario logueado: " . $this->usuario->get_username());
                } elseif ($tarea->get_id_authcode() == Authcode::USUARIO_INTERNO) {
                    $GLOBALS['SISTEMA'] = 'INTERNO';
                    $this->usuario = new Auth();
                    $this->usuario->get($tarea->get_id_usuario());
                    developer_log("Usuario logueado: " . $this->usuario->get_authname());
                } else
                    return false;
                developer_log('Cambio al sistema ' . $GLOBALS['SISTEMA']);
            }
            # Falta eliminar cookie anterior !!!!!!!!!!! #BUG
//				$_COOKIE[$GLOBALS['COOKIE_NAME']]=$this->obtener_ultima_cookie($tarea);

            if ($this->usuario !== null) {
                if (!($fecha = Datetime::createFromFormat(Tarea::FORMATO_FECHA_SOLICITADA, $tarea->get_fecha_solicitada()))) {
                    $fecha = '--/--/--';
                } else
                    $fecha = $fecha->format('d/m/Y');
            }
            try {
                if (!$this->run($tarea)) {
                    $this->errores++;
                    if (self::ACTIVAR_DEBUG)
                        error_log('Ha ocurrido un error al completar la tarea.');
                    Gestor_de_log::set('Su tarea programada el día ' . $fecha . ' ha sido ejecutada de manera incorrecta. ', 0);
                }
                else {
                    if (self::ACTIVAR_DEBUG)
                        error_log('Tarea completada correctamente.');
                    Gestor_de_log::set('Su tarea programada el día ' . $fecha . ' ha sido completada satisfactoriamente. ', 1);
                }
            } catch (Throwable $e) {
                error_log("Error fatal " . $e->getMessage()." continuo con la tarea siguiente");
                $tarea->set_ocupado("false");
                if($tarea->set()){
                
                }
            }
        }
//				else {
//					if(self::ACTIVAR_DEBUG) error_log('No hay sesion disponible para el usuario.');
//					$this->errores++;
//				}
//			}
        $this->usuario = null;
        if (Model::hasFailedTrans()) {
            Gestor_de_correo::enviar("info@cobrodigital.com", "adupuy@cobrodigital.com", "transacciones fallidas", json_encode($tarea), false);
            $this->fallar_transacciones_pendientes();
        }
        $recordset = Tarea::tarea_siguiente($this->fecha_inicio_meta_application, $tarea->get_id());
        $authcode_anterior = $tarea->get_id_authcode();
        unset($tarea);
        if (!$recordset) {
            if (self::ACTIVAR_DEBUG)
                error_log("Existen problemas con transacciones no completadas. Fin de ejecucion.");
            exit();
        }
    }

    $recordset = Tarea::select(array('pendiente' => self::TRUE));
    $tareas_pendientes = $recordset->RowCount();
    if ($this->errores > 0) {
        if ($this->errores == 1)
            $mensaje = 'Ha ocurrido 1 error. Queda/n ' . $tareas_pendientes . ' tarea/s pendiente/s.';
        else
            $mensaje = 'Han ocurrido ' . $this->errores . ' errores. Quedan ' . $tareas_pendientes . ' tareas pendientes.';
        error_log($mensaje);
    }
    else {
        error_log('La ejecucion fue completada correctamente y sin errores. Quedan ' . $tareas_pendientes . ' tareas pendientes.');
    }
    return $this->errores;
}

private function obtener_ultima_cookie(Tarea $tarea) {
    $id_usuario = $tarea->get_id_usuario();
    $recordset = Useris::select(array('id_auth' => $id_usuario, 'id_authcode' => $tarea->get_id_authcode()));
    if ($recordset AND $recordset->RowCount() == 1) {
        $row = $recordset->FetchRow();
        $cookie = $row['cstr'];
        if (self::ACTIVAR_DEBUG)
            error_log('Cookie leida: ' . $cookie);
        return $cookie;
    }
    if (self::PERMITIR_COOKIES_FALSAS) {
        return $this->insertar_cookie_falsa($tarea);
    }
    return false;
}

private function insertar_cookie_falsa(Tarea $tarea) {
    # Si el usuario inicia sesion con otra ip en este preciso
    # momento, al insertarse el registro con la cookie se deslogeara.
    # Es poco pobable que suceda, se corregira con el nuevo login
    if ($tarea->get_id_authcode() == Authcode::USUARIO_INTERNO) {
        $tipo_login = LOGIN_DE_USUARIO_INTERNO;
    } elseif ($tarea->get_id_authcode() == Authcode::USUARIO_EXTERNO) {
        $tipo_login = LOGIN_DE_USUARIO_EXTERNO;
    } else {
        return false;
    }
    $cookie_real = false;
    return Gestor_de_cookies::set($tarea->get_id_usuario(), $tipo_login, $cookie_real);
}

private function run(Tarea $tarea) {
    if ($tarea->get_id_authcode() == Authcode::USUARIO_EXTERNO) {
        $usumarchand = new Usumarchand();
        $usumarchand->get($tarea->get_id_usuario());
        if (self::BLOQUEAR_EJECUCION_USUVIP AND $usumarchand->get_usuvip()) {
            if (self::ACTIVAR_DEBUG)
                error_log('No puede ejecutar tareas del Sistema-Externo agendadas por un Usuario VIP.');
            return false;
        }
    }
    $comando = 'meta_application.php ' . $tarea->get_comando();
    if (($parametros = separar_palabras($comando)) === false) {
        if (self::ACTIVAR_DEBUG)
            error_log('Ha ocurrido un error al separar las palabras. Verifique que las comillas sean correctas.');
        return false;
    }
    foreach ($parametros as &$parametro) {
        $parametro = trim($parametro);
    }
    if (!$this->validar_parametros($parametros))
        return false;
    list($parametros_post, $_FILES) = $this->separar_parametros($parametros);
    if (($variables = $this->preparar_post($parametros_post)) === false)
        return false;
    $nav = $variables['nav'];
    unset($variables['nav']);

    if ($GLOBALS['SISTEMA'] == 'INTERNO')
        $aplicacion = 'Back_office';
    elseif ($GLOBALS['SISTEMA'] == 'EXTERNO')
        $aplicacion = 'Cobro_digital';
    else
        return false;
    $resultado = $this->ejecutar_aplicacion($aplicacion, $tarea, $nav, $variables);
    if ($resultado)
        return true;
    return false;
}

private function ejecutar_aplicacion($aplicacion, Tarea $tarea, $nav, $variables) {
    if (!defined('CONSOLA'))
        define('CONSOLA', true);
    if (self::ACTIVAR_DEBUG) {
        error_log('Fecha de ejecución: ' . date('d/m/Y H:i'));
        error_log("(Id tarea:" . $tarea->get_id() . ") Ejecutando comando [ " . $tarea->get_comando() . " ]. ");
    }
    $tarea->set_ocupado(self::TRUE);
    $tarea->set_pid(getmypid());
    $tarea->set_mensaje("procesando");
    $tarea->set_fecha_ejecutada(date('Y-m-d H:i'));
    if (!$tarea->set()) {
        if (self::ACTIVAR_DEBUG)
            error_log('Ha ocurrido un error al bloquear la Tarea.');
        return false;
    }
    if (self::ACTIVAR_DEBUG)
        error_log('Registro bloqueado.');
    if (self::ACTIVAR_SLEEP)
        sleep(5);

    try {
//			$this->application=new $aplicacion($_COOKIE[$GLOBALS['COOKIE_NAME']]);
        $aplication = new $aplicacion();
        $this->application = $aplication->fabrica($this->usuario);
        $resultado = $this->application->navigate($nav, $variables);
    } catch (Exception $e) {
        $resultado = false;
    }

    developer_log(Model::hasFailedTrans());
    if (!$this->validar_resultado($nav, $resultado) OR Model::hasFailedTrans()) {
        # Procesamiento invalido
        if (self::ACTIVAR_DEBUG)
            error_log('Procesamiento invalido.');
        if (self::ACTIVAR_DEBUG AND Model::hasFailedTrans())
            error_log('Hay transacciones fallidas.');

        $this->fallar_transacciones_pendientes();
        if ($tarea->es_tarea_periodica()) {
            $tarea->set_pendiente(self::FALSE);
        }
        # $tarea->set_ejecucio_correcta(self::FALSE); No hace falta, ya estaba en False
        # Si falla el set_ocupado tenemos problemas
        $tarea->set_ocupado(self::FALSE);
        # Y si falla al setear el mensaje no cambia nada
        $tarea->set_mensaje('Ha ocurrido un error.');
        $tarea->set_fecha_ejecutada(date('Y-m-d H:i'));

        if ($tarea->set()) {
            if (self::ACTIVAR_DEBUG)
                error_log('Registro desbloqueado.');
            if (self::ACTIVAR_SLEEP)
                sleep(5);

            return false;
        }
        if (self::ACTIVAR_DEBUG)
            error_log('Ha ocurrido un error al desbloquear el registro.');
        return false;
    }
    else {
        # Procesamiento valido
        if (self::ACTIVAR_DEBUG)
            error_log('Procesamiento valido.');
        if ($tarea->es_tarea_periodica()) {
            $tarea->set_pendiente(self::FALSE);
        }
        $tarea->set_ocupado(self::FALSE);
        $tarea->set_ejecucion_correcta(self::TRUE);
        $tarea->set_mensaje('Completado correctamente.');
        $tarea->set_fecha_ejecutada(date('Y-m-d H:i'));
        if (!$tarea->set()) {
            if (self::ACTIVAR_DEBUG)
                error_log('Ha ocurrido un error al cambiar el estado del registro.');
        }
        $this->fallar_transacciones_pendientes();

        if (self::ACTIVAR_DEBUG)
            error_log('Registro desbloqueado.');
        if (self::ACTIVAR_DEBUG)
            error_log('Registro no pendiente.');
        if (self::ACTIVAR_SLEEP)
            sleep(5);
        return true;
    }
    return false;
}

private function validar_parametros($parametros) {

    return true;
}

private function separar_parametros($parametros) {
    # Recibe un array retorna dos
    if (($posicion_opcion = array_search('-f', $parametros)) !== false) {
        # Hay un archivo
        $i = 0;
        foreach ($parametros as $key => $value) {
            if ($key == $posicion_opcion OR $key == $posicion_opcion + 1)
                $ruta_archivo = $parametros[$posicion_opcion + 1];
            else {
                $post[$i] = $value;
                $i++;
            }
        }
        developer_log("buscando archivo en :" . PATH_CDEXPORTS . $ruta_archivo);
        if (!is_file(PATH_CDEXPORTS . $ruta_archivo)) {
            error_log('El archivo no existe.');
            return false;
        }
        $tipo_archivo = '';
        $tamanio_archivo = 100;
        $files['archivo']['error'] = false;
        $files['archivo']['size'] = $tamanio_archivo;
        $files['archivo']['type'] = $tipo_archivo;
        $files['archivo']['name'] = basename($ruta_archivo);
        $files['archivo']['tmp_name'] = PATH_CDEXPORTS . $ruta_archivo;
    } else {
        $files = false;
        $post = $parametros;
    }
    return array($post, $files);
}

private function preparar_post($parametros) {
    unset($parametros[0]);
    $return = array();
    if (!isset($parametros[1]))
        return false;
    $return['nav'] = $parametros[1];
    unset($parametros[1]);

    $cant_parametros = count($parametros);
    for ($i = 2; $i <= $cant_parametros; $i++) {
        $i++;
        $return[$parametros[$i - 1]] = $parametros[$i];
    }

    $otro = array();
    if (isset($return['id']))
        $otro['id'] = $return['id'];
    else
        $otro['id'] = '';
    unset($return['id']);
    $otro['nav'] = $return['nav'];
    unset($return['nav']);
    $otro['data'] = '';
    foreach ($return as $key => $value) {
        $otro['data'] .= $key . '=' . $value . '&';
    }
    $otro['data'] = substr($otro['data'], 0, -1);
    return $otro;
}

private function como_se_usa() {

    return 'El llamado no es correcto. Siga las siguientes instrucciones.';
}

private function validar_resultado($nav, $resultado) {
    $nav = strtolower(trim($nav));
    $resultado = str_replace('::', '.', $resultado);
    $resultado = strtolower(trim($resultado));
    if (self::ACTIVAR_DEBUG)
        error_log('Comparando [ ' . $nav . ' = ' . $resultado . ' ]');
    if ($nav == $resultado)
        return true;
    return false;
}

private function fallar_transacciones_pendientes() {
    Model::fallar_transacciones_pendientes();
}

private function cargar_variables_de_entorno($path) {
    $handle = @fopen($path, "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            $variable = $valor = false;

            $line = trim(preg_replace('/\s\s+/', ' ', $line));

            $a = explode(' ', $line);
            if ($a[0] === 'export' AND count($a) == 2) {
                $b = explode('=', $a[1]);
                if (count($b) == 2) {
                    $variable = $b[0];
                    if (strpos($b[1], '$') !== false) {
                        $b[1] = str_replace('$', '', $b[1]);
                        $c = explode("'", $b[1]);
                        if (count($c) == 3 AND getenv($c[0]) !== null) {
                            $valor = getenv($c[0]) . $c[1];
                        }
                    } else {
                        $valor = $b[1];
                    }
                }
            }
            if ($variable !== false AND $valor !== false) {
                $setting = strtoupper($variable) . '=' . $valor;
                // error_log($setting);
                putenv($setting);
            }
        }

        fclose($handle);
    } else {
        error_log('Imposible cargar variables de entorno de apache desde: ' . $path);
        exit();
    }
}

private function hoy_es_feriado() {
    if ($this->hoy_es_feriado === null) {
        if (($recordset_calendar = Calendar::select_datetime($this->fecha_inicio_meta_application))) {
            $aux = $recordset_calendar->FetchRow();
            if ($aux['islaborable'] === '0') {

                $this->hoy_es_feriado = true;
                error_log('Hoy es feriado: Se evitará la ejecución de las tareas que tengan el flag saltear_feriados activado.  ');
            } else
                $this->hoy_es_feriado = false;
        }
    }
    return $this->hoy_es_feriado;
}

}
