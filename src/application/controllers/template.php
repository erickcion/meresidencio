<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Allows a template to be automatically loaded and displayed. Display can be
 * dynamically turned off in the controller methods, and the template file
 * can be overloaded.
 *
 * To use it, declare your controller to extend this class:
 * `class Your_Controller extends Template_Controller`
 *
 * $Id: template.php 3769 2008-12-15 00:48:56Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
abstract class Template_Controller extends Controller {

	// Template view name
	public $template = 'plantillas/basico';

	// Default to do auto-rendering
	public $auto_render = TRUE;

	/**
	 * Template loading and setup routine.
	 */
	public function __construct()
	{
		parent::__construct();

		// Load the template
		$this->template = new View($this->template);

		/***********************
		 * Datos Agregados por mi
		 */

		//Inicializa las sesiones
		$this->session = Session::instance();
		$usuario = $this->session->get('usuario');

		$this->template->historial = $this->session->get('historial');

		$this->template->intro_lateral = NULL/*INTRO_LATERAL*/;
		$this->template->panel_opciones = NULL/*PANEL_OPCIONES*/;
		$this->template->panel_sesion = new View('usuario/iniciar_sesion_lateral');
		$this->template->panel_sesion->sesion_usuario = $usuario;

		$v_notif = NULL;
		$usr_tipo = NULL;
		if($usuario){
			$usr_tipo = $usuario->tipo;
			$m_notif = new Notificacion_Model($usuario);
			$v_notif = new View('notificacion/notificacion_lateral');
			$v_notif->notificaciones = $m_notif->componer_notificaiones();
		}
		$this->template->notificaciones = $v_notif;
		$this->template->usr_tipo = $usr_tipo;
		/***********************/

		if ($this->auto_render == TRUE)
		{
			// Render the template immediately after the controller method
			Event::add('system.post_controller', array($this, '_render'));
		}
	}

	/**
	 * Render the loaded template.
	 */
	public function _render()
	{
		if ($this->auto_render == TRUE)
		{
			// Render the template when the class is destroyed
			$this->template->render(TRUE);
		}
	}

} // End Template_Controller