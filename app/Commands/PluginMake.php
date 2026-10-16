<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\PluginSystem\PluginManager;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * Esqueleto de un plugin. No instala ni toca la base de datos.
 */
class PluginMake extends BaseCommand
{
    protected $group = 'Plugin';

    protected $name = 'plugin:make';

    protected $description = 'Crea la carpeta, el manifiesto, la clase y una migración de un plugin nuevo.';

    protected $usage = 'plugin:make [nombre] [descripcion]';

    protected $arguments = [
        'nombre'      => 'Nombre visible. Ejemplo: Notas internas',
        'descripcion' => 'Una frase de qué hace el plugin',
    ];

    public function run(array $params)
    {
        $name = trim((string) ($params[0] ?? ''));
        if ($name === '') {
            $name = trim((string) CLI::prompt('Nombre del plugin', null, 'required'));
        }

        $description = trim((string) ($params[1] ?? ''));
        if ($description === '') {
            $description = trim((string) CLI::prompt('Descripción breve', null, 'required'));
        }

        $slug = self::slugFromName($name);
        $directory = self::directoryFromSlug($slug);
        $ident = str_replace('-', '', $slug);
        if (strlen($ident) > 27) {
            $ident = substr($ident, 0, 27);
        }
        $prefix = 'plg_' . $ident . '_';
        $classShort = $directory . 'Plugin';
        $class = 'Plugins\\' . $directory . '\\' . $classShort;
        $permission = $ident . '.access';

        $error = self::identityError($name, $description, $slug, $directory, $prefix, $class, $permission);
        if ($error !== null) {
            CLI::error($error);

            return EXIT_ERROR;
        }

        $root = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . 'plugins';
        $pluginPath = $root . DIRECTORY_SEPARATOR . $directory;
        if (is_dir($pluginPath)) {
            CLI::error('Ya existe plugins/' . $directory . '. No se sobreescribe.');

            return EXIT_ERROR;
        }

        if (self::slugTaken($slug, $prefix, $permission)) {
            return EXIT_ERROR;
        }

        CLI::newLine();
        CLI::write('Se creará plugins/' . $directory . '/', 'yellow');
        CLI::write('  Nombre:      ' . $name);
        CLI::write('  Descripción: ' . $description);
        CLI::write('  Slug:        ' . $slug);
        CLI::write('  Prefijo:     ' . $prefix);
        CLI::write('  Clase:       ' . $class);
        CLI::write('  Permiso:     ' . $permission);
        CLI::write('  Tabla:       ' . $prefix . 'items');
        CLI::newLine();

        $confirm = CLI::prompt('¿Crear este plugin?', ['y', 'n']);
        if ($confirm !== 'y') {
            CLI::write('Cancelado.', 'yellow');

            return EXIT_SUCCESS;
        }

        $migrations = $pluginPath . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';
        if (! mkdir($migrations, 0755, true) && ! is_dir($migrations)) {
            CLI::error('No se pudo crear la carpeta del plugin.');

            return EXIT_ERROR;
        }

        $stamp = date('Y-m-d-His');
        $migrationClass = 'Create' . $directory . 'Items';
        $table = $prefix . 'items';

        $manifest = [
            'slug'         => $slug,
            'directory'    => $directory,
            'class'        => $class,
            'name'         => $name,
            'version'      => '1.0.0',
            'description'  => $description,
            'icon'         => 'fa-plug',
            'table_prefix' => $prefix,
            'permissions'  => [
                $permission => 'Usar ' . $name,
            ],
            'settings'     => [
                'note' => [
                    'type'    => 'text',
                    'label'   => 'Nota',
                    'default' => '',
                ],
            ],
        ];

        $written = file_put_contents(
            $pluginPath . DIRECTORY_SEPARATOR . 'plugin.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        );
        $written = $written !== false && file_put_contents(
            $pluginPath . DIRECTORY_SEPARATOR . $classShort . '.php',
            self::classSource($directory, $classShort)
        ) !== false;
        $written = $written && file_put_contents(
            $migrations . DIRECTORY_SEPARATOR . $stamp . '_' . $migrationClass . '.php',
            self::migrationSource($directory, $migrationClass, $table)
        ) !== false;

        if (! $written) {
            CLI::error('No se pudieron escribir todos los archivos.');

            return EXIT_ERROR;
        }

        CLI::write('Plugin creado en plugins/' . $directory . '/', 'green');
        CLI::write('Siguiente paso, cuando quieras probarlo:', 'yellow');
        CLI::write('  php spark plugin:doctor');
        CLI::write('  php spark plugin:action install ' . $slug);

        return EXIT_SUCCESS;
    }

    private static function slugFromName(string $name): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $ascii = is_string($ascii) && $ascii !== '' ? $ascii : $name;
        $ascii = strtolower($ascii);
        $ascii = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';

        return trim($ascii, '-');
    }

    private static function directoryFromSlug(string $slug): string
    {
        $directory = '';
        foreach (explode('-', $slug) as $part) {
            if ($part === '') {
                continue;
            }
            $directory .= ucfirst($part);
        }

        return $directory;
    }

    private static function identityError(
        string $name,
        string $description,
        string $slug,
        string $directory,
        string $prefix,
        string $class,
        string $permission
    ): ?string {
        if (strlen($name) > 150) {
            return 'El nombre no puede pasar de 150 caracteres.';
        }
        if ($description === '' || strlen($description) > 1000) {
            return 'La descripción es obligatoria y de máximo 1000 caracteres.';
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) !== 1 || strlen($slug) > 100) {
            return 'Con ese nombre no sale un slug válido (solo letras y números).';
        }
        if (preg_match('/^[A-Z][A-Za-z0-9]+$/', $directory) !== 1 || strlen($directory) > 80) {
            return 'Con ese nombre no sale una carpeta PascalCase válida.';
        }
        if (preg_match('/^plg_[a-z0-9]+_$/', $prefix) !== 1 || strlen($prefix) > 32) {
            return 'No se pudo armar un table_prefix válido.';
        }
        if (strlen($class) > 255) {
            return 'La clase principal quedaría demasiado larga.';
        }
        if (preg_match('/^[a-z][a-z0-9]*(\.[a-z0-9][a-z0-9_-]*)+$/', $permission) !== 1) {
            return 'No se pudo armar un permiso válido.';
        }
        if (strlen($prefix . 'items') > 43) {
            return 'El nombre de la tabla de ejemplo superaría 43 caracteres.';
        }

        $reserved = ['admin', 'superadmin', 'users', 'finance', 'inventory', 'sales', 'purchasing', 'hr', 'catalog'];
        $permPrefix = strstr($permission, '.', true);
        if (is_string($permPrefix) && in_array($permPrefix, $reserved, true)) {
            return "El permiso '{$permission}' usa un prefijo que el ERP ya concede por comodín. Elige otro nombre.";
        }

        return null;
    }

    private static function slugTaken(string $slug, string $prefix, string $permission): bool
    {
        try {
            foreach (PluginManager::onDiskManifests() as $entry) {
                $otherSlug = (string) ($entry['manifest']['slug'] ?? '');
                $otherPrefix = (string) ($entry['manifest']['table_prefix'] ?? '');
                if ($otherSlug === $slug) {
                    CLI::error("Ya hay un plugin en disco con el slug '{$slug}'.");

                    return true;
                }
                if ($otherPrefix === $prefix) {
                    CLI::error("Ya hay un plugin en disco con el prefijo '{$prefix}'.");

                    return true;
                }
                $perms = $entry['manifest']['permissions'] ?? [];
                if (is_array($perms) && array_key_exists($permission, $perms)) {
                    CLI::error("El permiso '{$permission}' ya lo declara otro plugin.");

                    return true;
                }
            }
        } catch (Throwable $e) {
            CLI::error('No se pudo revisar los plugins que ya están en disco.');

            return true;
        }

        return false;
    }

    private static function classSource(string $directory, string $classShort): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$directory};

use App\\Libraries\\PluginSystem\\BasePlugin;

class {$classShort} extends BasePlugin
{
    public function registerHooks(): void
    {
    }
}

PHP;
    }

    private static function migrationSource(string $directory, string $class, string $table): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace Plugins\\{$directory}\\Database\\Migrations;

use CodeIgniter\\Database\\Migration;

class {$class} extends Migration
{
    public function up(): void
    {
        \$this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        \$this->forge->addKey('id', true);
        \$this->forge->createTable('{$table}', true);
    }

    public function down(): void
    {
        \$this->forge->dropTable('{$table}', true);
    }
}

PHP;
    }
}
