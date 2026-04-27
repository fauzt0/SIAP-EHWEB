<?php

namespace App\Database\Seeds\HR;

use CodeIgniter\Database\Seeder;

class HrCatalogsSeeder extends Seeder
{
    public function run()
    {
        // 1. Departamentos
        $departments = [
            [
                'name'        => 'Ingeniería / Desarrollo (Producto)',
                'description' => 'Es el "corazón" de la empresa. Aquí se escribe el código y se crean las soluciones.',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'name'        => 'Sistemas y Operaciones (IT / DevOps)',
                'description' => 'Encargados de que el servidor esté siempre encendido y sea seguro.',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'name'        => 'Datos y Análisis',
                'description' => 'Encargados de interpretar la enorme cantidad de información que genera el software.',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'name'        => 'Diseño y Experiencia de Usuario (UX / UI)',
                'description' => 'Hacen que el producto sea bonito, pero sobre todo, fácil de usar.',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'name'        => 'Comercial y Crecimiento (Sales & Marketing)',
                'description' => 'Si nadie sabe que el producto existe, la empresa quiebra.',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
            [
                'name'        => 'Éxito del Cliente (Customer Success / Soporte)',
                'description' => 'Vital en empresas de Hosting o SaaS.',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('hr_cat_departments')->insertBatch($departments);

        // 2. Puestos
        $jobs = [
            // Ingeniería
            ['name' => 'CTO (Chief Technology Officer)', 'description' => 'El director de tecnología. Decide qué herramientas usar y hacia dónde va la innovación.'],
            ['name' => 'Software Engineer (Frontend / Backend / Fullstack)', 'description' => 'Programadores encargados de la lógica y visualización del producto.'],
            ['name' => 'QA Engineer (Quality Assurance)', 'description' => 'Encargados de realizar pruebas para encontrar errores antes que el cliente.'],
            ['name' => 'Product Manager (PM)', 'description' => 'Define funciones del producto según las necesidades del mercado.'],
            
            // Sistemas
            ['name' => 'DevOps Engineer', 'description' => 'Puente entre desarrollo y sistemas. Automatiza el despliegue y gestiona la nube.'],
            ['name' => 'SysAdmin (Administrador de Sistemas)', 'description' => 'Gestiona servidores, redes y seguridad.'],
            ['name' => 'Cybersecurity Analyst', 'description' => 'Protege a la empresa de ataques externos y gestiona vulnerabilidades.'],
            
            // Datos
            ['name' => 'Data Scientist', 'description' => 'Utiliza modelos matemáticos para predecir comportamientos de usuarios.'],
            ['name' => 'Data Engineer', 'description' => 'Construye tuberías de datos para que sean útiles.'],
            
            // Diseño
            ['name' => 'UX Designer (User Experience)', 'description' => 'Investiga cómo se siente el usuario y diseña el flujo lógico.'],
            ['name' => 'UI Designer (User Interface)', 'description' => 'Enfoque visual: colores, tipografías y botones.'],
            
            // Comercial
            ['name' => 'Growth Hacker', 'description' => 'Híbrido que usa marketing y programación para hacer crecer la base de usuarios.'],
            ['name' => 'Account Manager', 'description' => 'Contacto directo con clientes grandes; asegura satisfacción.'],
            ['name' => 'SDR (Sales Development Representative)', 'description' => 'Busca nuevos clientes potenciales (prospección).'],
            
            // Éxito
            ['name' => 'Support Engineer', 'description' => 'Técnico especializado que resuelve problemas complejos.'],
            ['name' => 'Customer Success Manager', 'description' => 'Ayuda al cliente a sacar provecho al servicio para evitar cancelaciones.'],
        ];

        // Añadir timestamps a puestos
        $jobs = array_map(function($job) {
            $job['created_at'] = date('Y-m-d H:i:s');
            $job['updated_at'] = date('Y-m-d H:i:s');
            return $job;
        }, $jobs);

        $this->db->table('hr_cat_jobs')->insertBatch($jobs);

        // 3. Tipos de Contrato (Extras por defecto)
        $contractTypes = [
            ['name' => 'Indeterminado', 'description' => 'Contrato de planta sin fecha de término.'],
            ['name' => 'Determinado', 'description' => 'Contrato por tiempo específico.'],
            ['name' => 'Por Obra', 'description' => 'Contrato vinculado a la duración de un proyecto.'],
            ['name' => 'Periodo de Prueba', 'description' => 'Contrato temporal de evaluación.'],
        ];
        
        $contractTypes = array_map(function($t) {
            $t['created_at'] = date('Y-m-d H:i:s');
            $t['updated_at'] = date('Y-m-d H:i:s');
            return $t;
        }, $contractTypes);
        
        $this->db->table('hr_cat_contract_types')->insertBatch($contractTypes);

        // 4. Tipos de Documento (Extras por defecto)
        $docTypes = [
            ['name' => 'Identificación Oficial', 'description' => 'INE, Pasaporte o Cédula Profesional.'],
            ['name' => 'CURP', 'description' => 'Clave Única de Registro de Población.'],
            ['name' => 'RFC', 'description' => 'Cédula de Identificación Fiscal.'],
            ['name' => 'NSS', 'description' => 'Número de Seguridad Social.'],
            ['name' => 'Comprobante de Domicilio', 'description' => 'Luz, Agua o Teléfono.'],
            ['name' => 'Contrato Firmado', 'description' => 'Copia del contrato laboral vigente.'],
        ];

        $docTypes = array_map(function($t) {
            $t['created_at'] = date('Y-m-d H:i:s');
            $t['updated_at'] = date('Y-m-d H:i:s');
            return $t;
        }, $docTypes);

        $this->db->table('hr_cat_document_types')->insertBatch($docTypes);
    }
}
