<?php defined('SYSPATH') or die('No se permite el acceso directo al script');

class Publicacion_Controller extends Template_Controller {

	protected $formulario;
	protected $errores;
	protected $mensaje;

	public function __construct()
	{
		parent::__construct();
		$this->template->titulo = html::specialchars("Administracion de Publicaciones");
		$this->limpiar_formulario();
		$this->errores = $this->formulario;
		$this->mensaje = '';
	}

	/**
	 * Pone todos los campos en blanco, listo para ser utilizado
	 */
	public function limpiar_formulario(){
		$this->formulario = array(
			'tipoinmueble' => '',
			'sexo' => '',
			'estado' => '',
			'ciudad' => '',
			'zona' => '',
			'direccion' => '',
			'habitaciones' => '',
			'mts' => '',
			'precio' => '',
			'deposito' => '',
			'descripcion' => '',
			'imagen1' => '',
			'imagen2' => '',
			'imagen3' => '',
		);
	}

	public function index(){
		$contenido = "<br>";
		$contenido .= html_Core::anchor('publicacion/agregar','Agregar Publicacion');
		$contenido .= '<br>';
		$contenido .=  html_Core::anchor('publicacion/lista','Buscar Publicaciones');

		$this->template->contenido = $contenido;
	}

	/**
	 * Controla la salida en pantalla de el formulario
	 * para agregar estados.
	 */
	public function agregar(){

		$this->template->titulo = html::specialchars("Agregar una Publicacion Nueva");

		$vista = new View("publicacion/agregar");
		if($_POST){
			if($this->_agregar($_POST)){
				$this->mensaje = "Se guardo con &eacute;xito.";
				$this->limpiar_formulario();
			}
		}
		$usuario = ORM::factory('usuario', 1);
		$vista->usuario_id = $usuario->id;
		$vista->mensaje = $this->mensaje;
		$vista->formulario = $this->formulario;
		$vista->errores = $this->errores;

		$this->template->contenido = $vista;
	}

	/**
	 * Realiza todos los procesos relacionados a la insersion
	 * de los estados en la base de datos
	 *
	 */
	public function _agregar(){
		$exito = false;
		$datos = $_POST;
		$publicacion = new Publicacion_Model();

		//echo Kohana::debug($_FILES);
		//echo Kohana::debug($_POST);
		//$image = Image::factory($_FILES['imagenes']['tmp_name'][0]);
		//echo $image->__get("type");

		if($this->_validar()){

			$publicacion->usuario_id = $datos['usuario'];
			$publicacion->tipoinmueble_id = $datos['tipoinmueble'];
			$publicacion->zona_id = $datos['zona'];
			$publicacion->direccion = $datos['direccion'];
			$publicacion->mts = $datos['mts'];
			$publicacion->descripcion = $datos['descripcion'];
			$publicacion->precio = $datos['precio'];
			$publicacion->deposito = $datos['deposito'];
			$publicacion->activo = 1;

			if(isset($datos['servicio']))	$publicacion->servicios = $datos['servicio'];
			if(isset($datos['cercania']))	$publicacion->cercanias = $datos['cercania'];

			//Guarda la publicacion
			$publicacion->save();

			Imagen_Model::guardar_imagen($_FILES['imagen1'], $publicacion->id);
			Imagen_Model::guardar_imagen($_FILES['imagen2'], $publicacion->id);
			Imagen_Model::guardar_imagen($_FILES['imagen3'], $publicacion->id);

			$exito = TRUE;
		}

		return $exito;
	}

	/**
	 * Validacion de los datos obtenidos a traves del metodo post
	 */
	public function _validar(){

		$post = new Validation_Core($_POST);
		$post->pre_filter('trim');
		$post->add_rules('tipoinmueble','required');
		$post->add_rules('estado','required');
		$post->add_rules('ciudad','required');
		$post->add_rules('zona','required');
		$post->add_rules('direccion','standard_text', 'length[0,200]');
		$post->add_rules('habitaciones','numeric', 'required');
		$post->add_rules('mts','numeric');
		$post->add_rules('precio', 'numeric', 'required');
		$post->add_rules('deposito','numeric');

		$post->add_callbacks('tipoinmueble', array($this, '_no_cero'));
		$post->add_callbacks('estado', array($this, '_no_cero'));
		$post->add_callbacks('ciudad', array($this, '_no_cero'));
		$post->add_callbacks('zona', array($this, '_no_cero'));
		$post->add_callbacks('imagen1', array($this, '_mime_valido'));
		$post->add_callbacks('imagen2', array($this, '_mime_valido'));
		$post->add_callbacks('imagen3', array($this, '_mime_valido'));

		$exito = $post->validate();

		$this->mensaje = "Problema al Guardar";
		$this->formulario = arr::overwrite($this->formulario, $post->as_array());
		$this->errores = arr::overwrite($this->errores, $post->errors('publicacion_errores'));

		return $exito;
	}

	public function _no_cero(Validation_Core  $array, $campo){
		if($array[$campo] == 0){
			$array->add_error($campo, 'required');
		}
	}

	public function _mime_valido(Validation_Core  $array, $campo){
		$nombre = $_FILES[$campo]['name'];
		if(strlen($nombre) > 0){
			$ext = strtolower(substr($nombre, -4, 4));
			if($ext != ".jpg" && $ext != ".png" && $ext != ".gif"){
				$ext = strtolower(substr($nombre, -5, 5));
				if($ext != ".jpeg"){
					$array->add_error($campo, 'extension');
				}
			}
		}
	}

	public function imagenes($id = NULL){
		$this->template->contenido = NULL;
		if($id){
			$this->template->auto_render = FALSE;
			$this->auto_render = FALSE;
			Imagen_Model::generar_imagen($id, TRUE);
		}else{
			$imagenes = ORM::factory('imagen')->find_all();
			foreach($imagenes as $imagen){
				echo html_Core::anchor("publicacion/imagenes/$imagen->id", "Imagen $imagen->id");
				echo "<br>";
			}
		}
	
	}
	
	public function lista($pag_num = 1){
		$this->template->titulo = "Lista de Publicaciones";
		$vista = new View('publicacion/buscar');
		
		//$publicaciones = new Publicacion_Model();
		$publicaciones = $this->_buscar($_POST);
		
		$paginacion = new Pagination(
			array(
				'uri_segment' => 'pagina',
				'total_items' => $publicaciones->count_all(),
				'items_per_page' => ITEMS_POR_PAGINA,
				'style' => 'classic',
			)
		);
		
		$vista->publicacion = $publicaciones->limit(ITEMS_POR_PAGINA)->offset($paginacion->sql_offset)->find_all();
		$vista->paginacion = $paginacion; 
		$this->template->contenido = $vista;
	}
	
	public function _buscar($post){
		$datos = $this->_limpiar_datos_busqueda($post);
		$condicion = array(
			'estado.id' => $datos['estado'],
			'ciudad.id' => $datos['ciudad'],
			'zona' => $datos['zona'],
			'tipoinmueble_id' => $datos['tipoinmueble'],
			'sexo' => $datos['sexo'],
		);
		return ORM::factory('publicacion');
	}
	
	public function _limpiar_datos_busqueda($post){
		$array = array(
			'estado' => '',
			'ciudad' => '',
			'zona' => '',
			'tipoinmueble' => '',
			'sexo' => '',
		);
		foreach ($post as $key => $value) {
			if ($value != 0) {
				$array[$key] = $value;
			};
		}
		return $array;
	}
}
?>
