<?php defined('SYSPATH') or die('No se permite el acceso directo al script');

class Alerta_Controller extends Template_Controller {

	protected $mensaje;

	public function __construct()
	{
		parent::__construct();
		$this->template->titulo = html::specialchars("Administracion de Alertas");
		$this->mensaje = '';
	}

	public function index(){

		//Control de acceso
		Usuario_Model::otorgar_acceso($this->session->get('usuario'), array(USUARIO_ADMIN, USUARIO_VENDE, USUARIO_COMUN), MSJ_INICIAR_SESION);

		$this->template->contenido = NULL;
	}

	/**
	 * Controla la salida en pantalla de el formulario
	 * para agregar estados.
	 */
	public function agregar(){
		
		// Estadisticas WEBOSCOPE
		$this->template->web_zone=WEBO_Z_ALERTA;
		$this->template->web_page=WEBO_P_ALERTA_AGREGAR;

		//Control de acceso
		Usuario_Model::otorgar_acceso($this->session->get('usuario'), array(USUARIO_ADMIN, USUARIO_VENDE, USUARIO_COMUN), MSJ_INICIAR_SESION);

		$usuario = $this->session->get('usuario');

		$this->template->titulo = html::specialchars("Suscribir Alerta");

		$vista = new View("alerta/agregar");
		if($_POST){
			if($this->_agregar($usuario->id)){
				$this->mensaje = "<div class='msg_exito'>Tu alerta se guard&oacute; con &eacute;xito.</div>";
				$this->limpiar_formulario();
			}
		}
		$this->mis_alertas();
	}

	/**
	 * Realiza todos los procesos relacionados a la insersion
	 * de los estados en la base de datos
	 */
	public function _agregar($usuario_id){
		$exito = false;
		$datos = $_POST;
		$estado = new Alerta_Model();
		if($this->_validar($usuario_id)){
			if($datos['estado']>0)	$estado->estado_id = $datos['estado'];
			if($datos['ciudad']>0)$estado->ciudad_id = $datos['ciudad'];
			if($datos['zona']>0)$estado->zona_id = $datos['zona'];
			if($datos['tipoinmueble']>0)$estado->tipoinmueble_id = $datos['tipoinmueble'];
			$estado->usuario_id = $usuario_id;
			$estado->save();
			$exito = true;
		}
		return $exito;
	}

	public function mis_alertas(){

		//Control de acceso
		Usuario_Model::otorgar_acceso($this->session->get('usuario'), array(USUARIO_ADMIN, USUARIO_VENDE, USUARIO_COMUN), MSJ_INICIAR_SESION);

		$usuario = $this->session->get('usuario');
		$alertas = ORM::factory('alerta')
		->where('usuario_id',$usuario->id)->orderby('id','DESC')->find_all();
		$this->template->titulo = "Mis Alertas";
		$vista = new View('alerta/mis_alertas');
		$vista->alertas = $alertas;
		$vista->mensaje = $this->mensaje;
		$this->template->contenido = $vista;


	}

	public function eliminar($alerta_id){

		//Control de acceso
		Usuario_Model::otorgar_acceso($this->session->get('usuario'), array(USUARIO_ADMIN, USUARIO_VENDE, USUARIO_COMUN), MSJ_INICIAR_SESION);

		$this->auto_render = FALSE;
		$usuario = $this->session->get('usuario');
		$alerta = ORM::factory('alerta', $alerta_id);

		if($alerta->usuario_id == $usuario->id){
			$alerta->delete();
			header("Location: ".url::site('alerta/mis_alertas'));
		}
	}

	public function consultar($alerta_id){
		
		// Estadisticas WEBOSCOPE
		$this->template->web_zone=WEBO_Z_ALERTA;
		$this->template->web_page=WEBO_P_ALERTA_CONSULTAR;
		
		//Control de acceso
		Usuario_Model::otorgar_acceso($this->session->get('usuario'), array(USUARIO_ADMIN, USUARIO_VENDE, USUARIO_COMUN), MSJ_INICIAR_SESION);

		$usuario = $this->session->get('usuario');
		$alerta = ORM::factory('alerta', $alerta_id);
		$publicaciones = ORM::factory('publicacion');
		$join_tbl = array(
			'zonas',
			'ciudades',
			'estados',
		);
		$join_cond = array(
			'publicaciones.zona_id' => 'zonas.id',
			'zonas.ciudad_id' => 'ciudades.id',
			'ciudades.estado_id' => 'estados.id',
		);
		//Elaboramos la condicion
		$where_cond['activo'] = 1;
		foreach ($alerta->as_array() as $clave => $valor){
			if($valor != NULL){
				switch ($clave){
					case "estado_id":
					case "ciudad_id":
					case "zona_id":
					case "tipoinmueble_id":
						//en todos estos casos concatenamos '_id' para coincidir con la BD
						$where_cond[$clave] = $valor;
						break;
				}
			}
		}

		$publicaciones
		->select('*')
		->join($join_tbl, $join_cond)
		->where($where_cond)
		->orderby('publicaciones.id', 'DESC');

		//Comienza a prepararse la Paginacion
		$paginacion = new Pagination(
		array(
					'uri_segment' => 'pagina',
					'total_items' => $publicaciones->count_all(),
					'items_per_page' => ITEMS_POR_PAGINA,
					'style' => 'classic',
		)
		);

		$limit = ITEMS_POR_PAGINA;
		$offset = $paginacion->sql_offset;

		$publicaciones = $publicaciones
		->select('publicaciones.*, zonas.ciudad_id, ciudades.estado_id')//Necesario porque sino selecciona solo 'publicaciones.*' y no los demas campos
		->join($join_tbl, $join_cond)
		->where($where_cond)
		->orderby('publicaciones.id', 'DESC')
		->limit($limit)
		->offset($offset)
		->find_all();

		$this->template->titulo = "Consultar Alerta";
		$vista = new View('alerta/consultar');

		$vista->alerta = $alerta;
		$vista->paginacion = $paginacion;
		$vista->publicaciones = $publicaciones;

		$this->template->contenido = $vista;
	}
}
?>