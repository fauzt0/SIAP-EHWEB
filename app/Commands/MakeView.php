<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MakeView extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'App';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'app:make-view';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Genera una nueva vista a partir de app/Views/Mockups/view_template.php';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'app:make-view [view_path]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
        'view_path' => 'Ruta y nombre de la vista (ej. Users/form_create)',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        // 1. Obtener la ruta
        $viewPath = $params[0] ?? CLI::prompt('Ruta y nombre de la vista (ej. Users/nuevo_index)', null, 'required');

        // Limpiar la ruta (quitar doble slash, asegurar que no empiece con slash)
        $viewPath = trim($viewPath, '/\\');

        // Asegurar la extensión .php
        if (!preg_match('/\.php$/i', $viewPath)) {
            $viewPath .= '.php';
        }

        // 2. Definir rutas reales
        $templatePath = APPPATH . 'Views/Mockups/view_template.php';
        $destinationPath = APPPATH . 'Views/' . $viewPath;

        // 3. Validaciones
        if (!is_file($templatePath)) {
            CLI::error("Error: El archivo base no existe en {$templatePath}");
            return;
        }

        if (is_file($destinationPath)) {
            CLI::error("Error: El archivo destino ya existe en {$destinationPath}");
            $overwrite = CLI::prompt('¿Deseas intentar sobreescribirlo?', ['y', 'n'], 'n');
            if ($overwrite !== 'y') {
                CLI::write('Operación cancelada.', 'yellow');
                return;
            }
        }

        // 4. Crear directorio si no existe
        $destinationDir = dirname($destinationPath);
        if (!is_dir($destinationDir)) {
            if (!mkdir($destinationDir, 0755, true)) {
                CLI::error("Error: No se pudo crear el directorio {$destinationDir}");
                return;
            }
        }

        // 5. Copiar archivo
        if (copy($templatePath, $destinationPath)) {
            CLI::write('¡Vista creada exitosamente!', 'green');
            CLI::write('Destino: ' . CLI::color($destinationPath, 'yellow'));
        } else {
            CLI::error('Ocurrió un error inesperado al copiar el archivo.');
        }
    }
}
