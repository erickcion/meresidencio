<?php defined('SYSPATH') or die('No se permite el acceso directo al script');?>
<h2>Agregar nuevo Estado</h2>
<?php echo form::open(NULL, array('method'=>'POST')) ?>
<table class="tabla_ext">
	<tr>
		<th colspan="4"><?php echo $mensaje ?></th>
	</tr>
	<tr>
		<td><?php echo form::label('estado','Estado') ?></td>
		<td><?php echo form::input('estado',$formulario['estado']) ?></td>
		<td><?php echo $errores['estado'] ?></td>
	</tr>
	<tr>
		<td colspan="2"><?php echo form::submit(array("class"=>"button"),'Guardar') ?></td>
		<?php if(isset($editar)){?>
		<td><input type="button"
			OnClick="window.location.href='<?php echo url::site('estado/agregar/') ?>'"
			class="button" value="Finalizar" /></td>
			<?php }?>
	</tr>
</table>
			<?php echo form::close() ?>
			<?php
			$lista = new View('estado/lista');
			$lista->estado = $estado;
			echo $lista;
?>
