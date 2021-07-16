<?php

class Controlador_sitio extends Model{

	public static $id_tabla='id_sitetodo';
	public static $secuencia='paratodo';
	public static $prefijo_tabla='ho_';
        const MENU_0=10;
        const MENU_1=50;
        const MENU_2=40;
        const MENU_3=30;
        const MENU_4=20;
        const MENU_5=60;
	private $id_sitetodo;
	public function get_id_Controlador_sitio(){return $this->id_Controlador_sitio;}
	public function set_id_Controlador_sitio($variable){$this->id_sitetodo=$variable; return $this->id_sitetodo;}
	private $id_permiso;
	public function get_id_permiso(){return $this->id_permiso;}
	public function set_id_permiso($variable){$this->id_permiso=$variable; return $this->id_permiso;}
	private $id_authstat;
	public function get_id_authstat(){return $this->id_authstat;}
	public function set_id_authstat($variable){$this->id_authstat=$variable; return $this->id_authstat;}
	private $ordi;
	public function get_ordi(){return $this->ordi;}
	public function set_ordi($variable){$this->ordi=$variable; return $this->ordi;}
	private $sordi;
	public function get_sordi(){return $this->sordi;}
	public function set_sordi($variable){$this->sordi=$variable; return $this->sordi;}
	private $ds_descrip;
	public function get_ds_descrip(){return $this->ds_descrip;}
	public function set_ds_descrip($variable){$this->ds_descrip=$variable; return $this->ds_descrip;}
	private $ds_dothis;
	public function get_ds_dothis(){return $this->ds_dothis;}
	public function set_ds_dothis($variable){$this->ds_dothis=$variable; return $this->ds_dothis;}
	private $ds_dolink;
	public function get_ds_dolink(){return $this->ds_dolink;}
	public function set_ds_dolink($variable){$this->ds_dolink=$variable; return $this->ds_dolink;}
	private $ds_params;
	public function get_ds_params(){return $this->ds_params;}
	public function set_ds_params($variable){$this->ds_params=$variable; return $this->ds_params;}
	private $ds_isimportant;
	public function get_ds_isimportant(){return $this->ds_isimportant;}
	public function set_ds_isimportant($variable){$this->ds_isimportant=$variable; return $this->ds_isimportant;}
	private $ds_style;
	public function get_ds_style(){return $this->ds_style;}
	public function set_ds_style($variable){$this->ds_style=$variable; return $this->ds_style;}
	private $ds_icon;
	public function get_ds_icon(){return $this->ds_icon;}
	public function set_ds_icon($variable){$this->ds_icon=$variable; return $this->ds_icon;}
        private $submenu;
        public function get_submenu() {
            return $this->submenu;
        }

        public function set_submenu($submenu) {
            $this->submenu = $submenu;
            return $this;
        }

        public static function select_menu($ordi){
            $sql="select * from ho_controlador_sitio where ordi=? and id_permiso>=50000 and sordi!=0 order by sordi asc";
            $variables[]=$ordi;
            return self::execute_select($sql, $variables);
        }
        public static function select_menu_principal(){
            $sql="select * from ho_controlador_sitio where ordi >10 and sordi=0 order by ordi desc";
            return self::execute_select($sql);
        }
        
        public static function select_menu_permiso($variables){
            $sql="select * from ho_controlador_sitio where ordi in (select ordi from ho_Controlador_sitio WHERE id_permiso in ($variables)) and sordi = 0";
            return self::execute_select($sql);
        }
        
        public static function select_menu_bienvenida($modulo){
            $variables[]=$modulo;
            $sql="select coalesce(D.ds_descrip,'') as menu_padre ,coalesce(C.ds_descrip,'') as menu_hijo, coalesce(A.ds_descrip,'' ) as modulo   
                from ho_Controlador_sitio A 
                left join ho_permiso B on A.id_permiso = B.id_permiso
                left join ho_controlador_sitio C on (A.ordi = C.submenu) 
                left join ho_controlador_sitio D on (C.ordi = D.ordi and D.sordi = 0) or (A.ordi = D.ordi and D.sordi = 0) 
                where A.ds_dolink = ?
                AND D.ds_descrip is not null";
            return self::execute_select($sql,$variables);
        }
}
