<?php
/*
<NPT, a web development framework.>
Copyright (C) <2009>  <NPT>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!defined('XFS')) exit;

$lang = array(
	'ENCODING' => 'iso-8859-1',
	'DATE_FORMAT' => 'd M Y',
	'DATE_FORMAT_FULL' => 'd F Y',
	'DATE_LONG' => 'j \d\e F \d\e Y',
	'YES' => 'Si',
	'NO' => 'No',
	'HOME' => 'Inicio',
	
	'PAGE_HEADER' => 'Peri&oacute;dico Subterr&aacute;neo',
	'SITE_DESC' => 'Peri&oacute;dico guatemalteco de cultura Rock',
	
	'LOGIN' => 'Iniciar sesi&oacute;n',
	'LOGOUT' => 'Salir del sistema',
	'LOGGED_OUT' => 'Ha cerrado su sesi&oacute;n del sistema correctamente.',
	'SIGN_LOGIN_ERROR' => 'La informaci&oacute;n es incorrecta.',
	
	'FATAL_ERROR' => 'Error cr&iacute;tico',
	'INVALID' => 'Inv&aacute;lido',
	'INFORMATION' => 'Informaci&oacute;n',
	'CONTROL' => 'Panel de control',
	'PREFERENCES' => 'Preferencias de usuario',
	'USERS' => 'Usuarios',
	'GROUPS' => 'Grupos',
	'EDIT' => 'Editar',
	'REMOVE' => 'Eliminar',
	'AGO' => 'hace ',
	'AGO_LESS_MIN' => '< 1 minuto',
	'CONTROL_PANEL' => 'Panel de control',
	'SAVED' => 'La informaci&oacute;n fue guardada.',
	'LOCATION' => 'Ubicaci&oacute;n',
	'ERROR' => 'Error',
	'HIDE' => 'Ocultar mensaje',
	'LOADING' => 'Cargando...',
	'SAVE' => 'Guardar',
	'OPTIONS' => 'Opciones',
	'GENDER' => 'G&eacute;nero',
	'MALE' => 'Masculino',
	'FEMALE' => 'Femenino',
	'NOT_AUTH' => 'No est&aacute; autorizado para esta operaci&oacute;n.',
	'ALL' => 'Todo',
	'MAIL_TO_UNKNOWN' => 'destinatarios-no-revelados:;',
	'BROWSER_UPGRADE' => 'Para utilizar esta aplicaci&oacute;n es necesario un navegador web reciente.',
	'BROWSER_UPGRADE_LEGEND' => '',
	'BROWSER_UPGRADE_RECOMMEND' => 'Se recomienda utilizar:',
	'PROCESING' => 'Por favor, espere un momento...',
	'NOT_FOUND' => 'La p&aacute;gina solicitada no es accesible en este momento.',
	'BUY' => '&iquest;D&oacute;nde comprar?',
	
	'USERNAME' => 'Nombre de usuario',
	'PASSWORD' => 'Contrase&ntilde;a',
	
	'CP_SUBJECT' => 'T&iacute;tulo',
	'CP_CONTENT' => 'Contenido',
	'CP_ALIAS' => 'Alias',
	'CP_CHILD_HIDE' => 'Ocultar sub-p&aacute;ginas',
	'CP_CHILD_ORDER' => 'Orden de sub-p&aacute;ginas',
	'CP_NAV' => 'En men&uacute; de navegaci&oacute;n',
	'CP_NAV_HIDE' => 'Ocultar en men&uacute; de navegaci&oacute;n',
	'CP_CSS_PARENT' => 'Usar CSS padre',
	'CP_CSS_VAR' => 'Usar variable CSS',
	'CP_QUICKLOAD' => 'Carga din&aacute;mica',
	'CP_DYNAMIC' => 'Men&uacute; carga din&aacute;mica',
	'CP_TAGS' => 'Etiquetas de p&aacute;gina',
	'CP_TEMPLATE' => 'Plantilla personalizada',
	'CP_REDIRECT' => 'Redirecci&oacute;n',
	'CP_DESCRIPTION' => 'Descripci&oacute;n',
	'CP_ALLOW_COMMENTS' => 'Permitir comentarios en p&aacute;gina',
	'CP_APPROVE_COMMENTS' => 'Aprobaci&oacute;n de mensaje a moderador',
	'CP_FORM' => 'Activar formulario',
	'CP_FORM_EMAIL' => 'Email para formulario',
	'CP_PUBLISHED' => 'Fecha de publicaci&oacute;n',
	
	'CP_AUTH_CREATE' => 'Crear',
	'CP_AUTH_MODIFY' => 'Modificar',
	'CP_AUTH_REMOVE' => 'Eliminar',
	
	'CP_PAGE_CREATE' => 'Crear en: %s',
	'CP_PAGE_MODIFY' => 'Modificando: %s',
	
	'LOGIN_WELCOME' => 'Bienvenido al panel de control de Subterr&aacute;neo',
	
	'datetime_chars' => array('%d a&ntilde;o', '%d mes', '%d d&iacute;a', '%d hora', '%d minuto'),
	
	'datetime' => array(
		'Sunday' => 'Domingo',
		'Monday' => 'Lunes',
		'Tuesday' => 'Martes',
		'Wednesday' => 'Mi&eacute;rcoles',
		'Thursday' => 'Jueves',
		'Friday' => 'Viernes',
		'Saturday' => 'Sabado',
		
		'days' => w('Domingo Lunes Martes Mi&eacute;rcoles Jueves Viernes Sabado'),

		'Sun' => 'Dom',
		'Mon' => 'Lun',
		'Tue' => 'Mar',
		'Wed' => 'Mie',
		'Thu' => 'Jue',
		'Fri' => 'Vie',
		'Sat' => 'Sab',

		'January' => 'enero',
		'February' => 'febrero',
		'March' => 'marzo',
		'April' => 'abril',
		'May' => 'mayo',
		'June' => 'junio',
		'July' => 'julio',
		'August' => 'agosto',
		'September' => 'septiembre',
		'October' => 'octubre',
		'November' => 'noviembre',
		'December' => 'diciembre',
		
		'Jan' => 'Ene',
		'Feb' => 'Feb',
		'Mar' => 'Mar',
		'Apr' => 'Abr',
		'May_short' => 'May',
		'Jun' => 'Jun',
		'Jul' => 'Jul',
		'Aug' => 'Ago',
		'Sep' => 'Sep',
		'Oct' => 'Oct',
		'Nov' => 'Nov',
		'Dec' => 'Dic',

		'TODAY' => 'Hoy',
		'YESTERDAY' => 'Ayer',
	)
);

?>