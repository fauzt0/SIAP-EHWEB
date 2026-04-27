<?php

/**
 * Validation language strings.
 *
 * @package    CodeIgniter
 * @author     CodeIgniter Dev Team
 * @copyright  2019-2020 CodeIgniter Foundation
 * @license    https://opensource.org/licenses/MIT	MIT License
 * @link       https://codeigniter.com
 * @since      Version 4.0.0
 * @filesource
 *
 * @codeCoverageIgnore
 */

return [
	// Core Messages
	'noRule'                => 'No se ha definido ninguna regla para el campo {field}.',
	'noPlaceholder'         => 'No se ha definido el marcador de posición {placeholder} en las reglas de validación.',
	'fieldNotFound'         => 'No se pudo encontrar el campo {field}.',
	'string'                => 'El campo {field} debe ser una cadena válida.',
	'unknownError'          => 'Se ha producido un error desconocido al validar el campo {field}.',

	// Rule Messages
	'alpha'                 => 'El campo {field} solo puede contener caracteres alfabéticos.',
	'alpha_dash'            => 'El campo {field} solo puede contener caracteres alfanuméricos, guiones bajos y guiones.',
	'alpha_numeric'         => 'El campo {field} solo puede contener caracteres alfanuméricos.',
	'alpha_numeric_punct'   => 'El campo {field} solo puede contener caracteres alfanuméricos, espacios y los caracteres ~ ! # $ % & * - _ + = | : .',
	'alpha_numeric_space'   => 'El campo {field} solo puede contener caracteres alfanuméricos y espacios.',
	'alpha_space'           => 'El campo {field} solo puede contener caracteres alfabéticos y espacios.',
	'decimal'               => 'El campo {field} debe contener un número decimal.',
	'differs'               => 'El campo {field} debe ser diferente al campo {param}.',
	'equals'                => 'El campo {field} debe ser exactamente igual a: {param}.',
	'exact_length'          => 'El campo {field} debe tener exactamente {param} caracteres de longitud.',
	'greater_than'          => 'El campo {field} debe contener un número mayor que {param}.',
	'greater_than_equal_to' => 'El campo {field} debe contener un número mayor o igual a {param}.',
	'hex'                   => 'El campo {field} solo puede contener caracteres hexadecimales.',
	'in_list'               => 'El campo {field} debe ser uno de los siguientes: {param}.',
	'integer'               => 'El campo {field} debe contener un número entero.',
	'is_natural'            => 'El campo {field} solo debe contener dígitos.',
	'is_natural_no_zero'    => 'El campo {field} solo debe contener dígitos y debe ser mayor que cero.',
	'is_not_unique'         => 'El campo {field} debe contener un valor que ya exista en la base de datos.',
	'is_unique'             => 'El campo {field} debe contener un valor único.',
	'less_than'             => 'El campo {field} debe contener un número menor que {param}.',
	'less_than_equal_to'    => 'El campo {field} debe contener un número menor o igual a {param}.',
	'matches'               => 'El campo {field} no coincide con el campo {param}.',
	'max_length'            => 'El campo {field} no puede exceder los {param} caracteres de longitud.',
	'min_length'            => 'El campo {field} debe tener al menos {param} caracteres de longitud.',
	'not_equals'            => 'El campo {field} no puede ser: {param}.',
	'not_in_list'           => 'El campo {field} no debe ser uno de los siguientes: {param}.',
	'numeric'               => 'El campo {field} debe contener solo números.',
	'regex_match'           => 'El campo {field} no tiene el formato correcto.',
	'required'              => 'El campo {field} es obligatorio.',
	'required_with'         => 'El campo {field} es obligatorio cuando {param} está presente.',
	'required_without'      => 'El campo {field} es obligatorio cuando {param} no está presente.',
	'timezone'              => 'El campo {field} debe ser una zona horaria válida.',
	'valid_base64'          => 'El campo {field} debe ser una cadena base64 válida.',
	'valid_email'           => 'El campo {field} debe contener una dirección de correo electrónico válida.',
	'valid_emails'          => 'El campo {field} debe contener todas las direcciones de correo electrónico válidas.',
	'valid_ip'              => 'El campo {field} debe contener una IP válida.',
	'valid_url'             => 'El campo {field} debe contener una URL válida.',
	'valid_date'            => 'El campo {field} debe contener una fecha válida.',

	// Credit Cards
	'valid_cc_num'          => '{field} no parece ser un número de tarjeta de crédito válido.',

	// Files
	'uploaded'              => '{field} no es un archivo subido válido.',
	'max_size'              => '{field} es un archivo demasiado grande.',
	'is_image'              => '{field} no es un archivo de imagen subido válido.',
	'mime_in'               => '{field} no tiene un tipo mime válido.',
	'ext_in'                => '{field} no tiene una extensión de archivo válida.',
	'max_dims'              => '{field} no es una imagen o es demasiado ancha o alta.',
];
