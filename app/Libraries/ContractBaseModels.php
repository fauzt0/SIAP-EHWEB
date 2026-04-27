<?php

namespace App\Libraries;

/**
 * ContractBaseModels
 * ─────────────────────────────────────────────────────────────────────────────
 * Clase que almacena los textos legales predefinidos para los contratos.
 * Estos modelos sirven como punto de partida para que el usuario no tenga
 * que redactar desde cero.
 */
class ContractBaseModels
{
    /**
     * Obtiene el contenido HTML de un modelo base específico.
     * 
     * @param string $type lft, modern, classic, corporate
     * @return string
     */
    public static function getModel(string $type): string
    {
        switch ($type) {
            case 'lft':
                return self::getLftMexico();
            case 'modern':
                return self::getModern();
            case 'classic':
                return self::getClassic();
            case 'corporate':
                return self::getCorporate();
            default:
                return '';
        }
    }

    private static function getSignatureBlock(): string
    {
        return '
            <br><br>
            <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                <tr>
                    <td style="text-align: center; padding: 20px; width: 50%;">
                        <div style="width: 80%; border-bottom: 1px solid #000; margin: 40px auto 10px auto;"></div>
                        <strong>EL PATRÓN</strong><br>
                        {{nombre_empresa}}
                    </td>
                    <td style="text-align: center; padding: 20px; width: 50%;">
                        <div style="width: 80%; border-bottom: 1px solid #000; margin: 40px auto 10px auto;"></div>
                        <strong>EL TRABAJADOR</strong><br>
                        {{nombre_trabajador}}
                    </td>
                </tr>
            </table>
            <br>
        ';
    }

    private static function getLftMexico(): string
    {
        return '
            <h2 style="text-align: center;">CONTRATO INDIVIDUAL DE TRABAJO</h2>
            <p>CONTRATO INDIVIDUAL DE TRABAJO POR TIEMPO INDETERMINADO QUE CELEBRAN POR UNA PARTE LA EMPRESA <strong>{{nombre_empresa}}</strong> REPRESENTADA POR <strong>{{nombre_representante}}</strong>, A QUIEN EN LO SUCESIVO SE LE DENOMINARÁ COMO EL "PATRÓN", Y POR LA OTRA PARTE EL C. <strong>{{nombre_trabajador}}</strong>, A QUIEN EN LO SUCESIVO SE LE DENOMINARÁ COMO EL "TRABAJADOR", AL TENOR DE LAS SIGUIENTES:</p>
            
            <h3>DECLARACIONES</h3>
            <p>I.- EL PATRÓN declara ser una sociedad legalmente constituida conforme a las leyes mexicanas, con domicilio en {{domicilio_patron}} y con RFC {{rfc_patron}}.</p>
            <p>II.- EL TRABAJADOR declara ser de nacionalidad {{nacionalidad}}, con fecha de nacimiento {{fecha_nacimiento}}, CURP {{curp}}, RFC {{rfc}} y con domicilio en {{domicilio}}.</p>
            
            <h3>CLÁUSULAS</h3>
            <p><strong>PRIMERA.-</strong> Este contrato se celebra por tiempo indeterminado conforme a lo dispuesto por el artículo 35 de la Ley Federal del Trabajo, iniciando labores a partir del día <strong>{{fecha_ingreso}}</strong>.</p>
            <p><strong>SEGUNDA.-</strong> El TRABAJADOR se obliga a prestar sus servicios personales al PATRÓN con la categoría de <strong>{{puesto}}</strong> en el departamento de <strong>{{departamento}}</strong>.</p>
            <p><strong>TERCERA.-</strong> El TRABAJADOR percibirá por la prestación de sus servicios un sueldo mensual de <strong>{{sueldo_mensual}}</strong>, el cual será cubierto los días {{dias_pago}} de cada mes.</p>
            <p><strong>CUARTA.-</strong> La jornada de trabajo será de {{horas_semanales}} horas semanales, distribuidas de lunes a viernes en un horario de {{horario}}.</p>
            
            ' . self::getSignatureBlock();
    }

    private static function getModern(): string
    {
        return '
            <h2 style="text-align: center; color: #3b7ddd;">ACUERDO DE COLABORACIÓN PROFESIONAL</h2>
            <p>Este Acuerdo de Colaboración Profesional entra en vigor a partir del <strong>{{fecha_ingreso}}</strong>, y se celebra entre <strong>{{nombre_empresa}}</strong> (en adelante, "La Empresa") y <strong>{{nombre_trabajador}}</strong> (en adelante, "El Colaborador").</p>
            
            <h3 style="color: #3b7ddd;">1. Datos del Colaborador</h3>
            <p>El Colaborador confirma que sus datos legales son los siguientes:</p>
            <ul>
                <li><strong>Domicilio:</strong> {{domicilio}}</li>
                <li><strong>Nacionalidad:</strong> {{nacionalidad}}</li>
                <li><strong>RFC / Identificación Fiscal:</strong> {{rfc}}</li>
                <li><strong>CURP / ID Personal:</strong> {{curp}}</li>
            </ul>

            <h3 style="color: #3b7ddd;">2. Rol y Responsabilidades</h3>
            <p>El Colaborador desempeñará el cargo de <strong>{{puesto}}</strong> dentro del área de <strong>{{departamento}}</strong>. Sus tareas principales se detallarán en el anexo correspondiente, comprometiéndose a cumplir con los objetivos trazados por La Empresa.</p>

            <h3 style="color: #3b7ddd;">3. Compensación</h3>
            <p>La Empresa acuerda otorgar una remuneración económica mensual de <strong>{{sueldo_mensual}}</strong> a cambio de la prestación exclusiva de los servicios del Colaborador. El pago se realizará vía {{metodo_pago}}.</p>

            ' . self::getSignatureBlock();
    }

    private static function getClassic(): string
    {
        return '
            <h2 style="text-align: center;">CONTRATO DE PRESTACIÓN DE SERVICIOS INDEPENDIENTES</h2>
            <p>En la ciudad de {{ciudad}}, a fecha de {{fecha_actual}}, se reúnen por una parte <strong>{{nombre_empresa}}</strong>, y por la otra <strong>{{nombre_trabajador}}</strong>, con domicilio en {{domicilio}}, a quien en lo sucesivo se le denominará "El Prestador".</p>
            
            <h3>ANTECEDENTES</h3>
            <p>El Prestador cuenta con la experiencia, capacidad técnica y elementos propios necesarios para llevar a cabo los servicios de <strong>{{puesto}}</strong> que requiere la empresa.</p>

            <h3>CLÁUSULAS</h3>
            <p><strong>PRIMERA. Objeto.</strong> El Prestador se obliga a prestar sus servicios de manera independiente a favor de la empresa, iniciando sus actividades el <strong>{{fecha_ingreso}}</strong>.</p>
            <p><strong>SEGUNDA. Honorarios.</strong> Como contraprestación por los servicios prestados, la empresa pagará la cantidad de <strong>{{sueldo_mensual}}</strong> brutos al mes, previa entrega del comprobante fiscal (RFC: {{rfc}}).</p>
            <p><strong>TERCERA. Independencia.</strong> Las partes reconocen que el presente instrumento no genera una relación de subordinación laboral, actuando El Prestador con sus propios recursos y bajo su propia dirección.</p>
            
            ' . self::getSignatureBlock();
    }

    private static function getCorporate(): string
    {
        return '
            <h2 style="text-align: center;">EXECUTIVE EMPLOYMENT AGREEMENT</h2>
            <p>This Executive Employment Agreement (the "Agreement") is made and entered into as of <strong>{{fecha_ingreso}}</strong>, by and between <strong>{{nombre_empresa}}</strong> (the "Company") and <strong>{{nombre_trabajador}}</strong> (the "Executive"), residing at {{domicilio}}.</p>
            
            <h3>1. Position and Duties</h3>
            <p>The Company hereby employs the Executive in the capacity of <strong>{{puesto}}</strong>. The Executive shall report directly to the Board of Directors or its designated representative within the <strong>{{departamento}}</strong> department.</p>

            <h3>2. Compensation</h3>
            <p>As full compensation for all services provided, the Company shall pay the Executive a base salary of <strong>{{sueldo_mensual}}</strong> per month, payable in accordance with the Company\'s standard payroll practices.</p>

            <h3>3. Personal Information</h3>
            <p>The Executive confirms the following identification details for tax and administrative purposes:</p>
            <ul>
                <li><strong>Tax ID (RFC):</strong> {{rfc}}</li>
                <li><strong>Personal ID (CURP):</strong> {{curp}}</li>
                <li><strong>Nationality:</strong> {{nacionalidad}}</li>
            </ul>
            
            ' . self::getSignatureBlock();
    }
}
