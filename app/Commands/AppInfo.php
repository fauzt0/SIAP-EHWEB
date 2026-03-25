<?php
declare(strict_types=1);

/**
 * Comandos con información y detalles de las funciones del controlador BaseController personalizadas y de las librerias personalizadas 
 */

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;    
use ReflectionClass;
use ReflectionMethod;

class Appinfo extends BaseCommand{

    protected $group  = 'App';   
    protected $name   = 'app:info'; 
    protected $description = 'Información sobre el proyecto y las funciones del controlador BaseController personalizadas y de las librerias personalizadas';   
    protected $usage = 'app:info [component]';    
    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [
      'component' => 'Nombre del componente a mostrar (viewData o outputData)',
    ];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];    

    /**
     * Actually executes the command.
     * @param array $params The parameters provided to the command.
     * @return void
     *  */
    public function run(array $params): void
    {
      $component = $params[0] ?? null;

  
      if ($component === 'viewData') {
        $this->displayViewDataInfo();
        return;
      } 

      if ($component === 'methods') {
        $this->displayMethods();
        return;
      }           

      if ($component === 'commands') {
        $this->displayCustomCommands();
        return;
      }

      //mosramos una lista de las opciones disponibles como "viewData", "outputData" o "methods"  
      CLI::write('Opciones disponibles: viewData, outputData, methods, commands');  
      CLI::write('Por ejemplo: '. CLI::color('app:info viewData', 'green'));  
      CLI::write('Por ejemplo: '. CLI::color('app:info methods', 'green'));
      CLI::write('Por ejemplo: '. CLI::color('app:info commands', 'green'));


    }

  /**
   * Display the BaseController personalized methods and Library methods like Breadcrumb
   * 
   */
    private function displayMethods(): void
    {
      CLI::write('Información sobre el proyecto y las funciones del controlador BaseController personalizadas y de las librerias personalizadas');  
      
      $classesToInspect = [
          \App\Controllers\BaseController::class,
          \App\Libraries\Breadcrumb::class,
      ];

      foreach ($classesToInspect as $className) {
          $this->displayClassMethods($className);
      }

      CLI::write('Scan complete.', 'green');
    }


  /**
   * Displays the methods for a given class name.
   *
   * @param string $className
   */    
  private function displayClassMethods(string $className)
  {
    if (!class_exists($className)) {
        CLI::write("Class '{$className}' not found.", 'red');
        return;
    }

    CLI::newLine();
    CLI::write("Methods for: " . CLI::color($className, 'yellow'));

    $reflection = new ReflectionClass($className);
    // Obtenemos solo métodos públicos y protegidos
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);

    $tableData = [];
    $header = ['Method', 'Parameters', 'Description'];

    foreach ($methods as $method) {
      // Filtramos para mostrar solo los métodos definidos en nuestra clase (no heredados de padres lejanos)
      if ($method->getDeclaringClass()->getName() !== $className) {
          continue;
      }

      // Ignoramos métodos "mágicos" o del ciclo de vida de CI
      if (in_array($method->getName(), ['initController', '__construct'])) {
          continue;
      }

      $params = [];
      foreach ($method->getParameters() as $param) {
          $paramType = $param->getType() ? $param->getType()->getName() . ' ' : '';
          $paramDefault = $param->isDefaultValueAvailable() ? ' = ...' : '';
          $params[] = $paramType . '$' . $param->getName() . $paramDefault;
      }

      $docComment = $method->getDocComment();
      $description = $this->parseDocComment($docComment);

      $tableData[] = [
          CLI::color($method->getName(), 'green'),
          implode(', ', $params),
          $description,
      ];
    }

    if (empty($tableData)) {
        CLI::write('No custom methods found in this class.', 'yellow');
    } else {
        CLI::table($tableData, $header);
    }
  }

  /**
   * Parses the doc comment to get a clean description.
   *
   * @param string|false $comment
   * @return string
  */
  private function parseDocComment($comment): string
  {
      if (empty($comment)) {
          return 'No description available.';
      }

      // Limpiamos el comentario
      $comment = preg_replace('/^\s*\/\*\*|\s*\*\/|\s*\*\s?|\s*@.*/m', '', $comment);
      $comment = trim(preg_replace('/\s+/', ' ', $comment));

      return $comment ?: 'No description available.';
  }

  private function displayViewDataInfo(): void
  {
    CLI::write('Estructura de la propiedad $viewData en BaseController', 'yellow');
    CLI::write('Esta propiedad se inicializa en `initController` y se usa para pasar datos a las vistas.');
    CLI::newLine();  
    
    $data = [
      ['success', 'bool', 'Indica si la respuesta ha sido exitosa.'],
      ['statusCode', 'int', 'Código de estado HTTP (ej: 200, 404).'],
      ['message', 'string', 'Mensaje descriptivo del estado.'],
      ['error', 'string|array', 'Mensaje o array de errores. Usado en fallos.'],
      ['pageTitle', 'string', 'Título para la etiqueta <title> de la página.'],
      ['headTitle', 'string', 'Título principal mostrado en la cabecera (ej: H1).'],
      ['validate', 'string', 'Maneja alertas y validaciones hacia la vista.'],
      ['pageView', 'string', 'Ruta de la vista principal renderizada por renderLayout().'],
      ['layout', 'string', 'Ruta del layout principal renderizado por renderLayout().'],
      ['breadcrumb', 'string|array', 'Breadcrumbs para la navegación.'],
      ['response', 'array|object', 'Contenedor para los datos principales de la respuesta.'],
    ];

    $header = ['Clave', 'Tipo', 'Descripción'];
    CLI::table($data, $header);
    CLI::newLine();
    CLI::write('Claves adicionales oppcionales recomendadas para anidar en la clave response. Cada vista puede recibir parámetros personalizados', 'yellow');
    $extraData = [
      ['responseMessage', 'string', 'Mensaje de resultado para mostrar en la vista HTML'],
      ['isEmpty', 'bool', 'Información para tablas, cards o modals. Determina respuestas vacías o no.'],        
      ['%option%List', 'mixed', 'Contiene la lista de opciones a mostrar en la vista HTML Ej; dataList, userList'],      
      [' -Ej: dataList', 'mixed', ' -Ej: Contiene la lista de datos a mostrar en la vista HTML'],
      [' -Ej: userList', 'mixed', ' -Ej: Contiene la lista de usuarios a mostrar en la vista HTML'],
    ];

    $extraHeader = ['Clave', 'Tipo', 'Descripción'];
    CLI::table($extraData, $extraHeader);
  }

  private function displayCustomCommands(): void
  {
    CLI::write('Comandos Personalizados Adicionales de Spark', 'yellow');
    CLI::newLine();

    $commandsData = [
      ['app:make-view', 'app:make-view [ruta]', 'Genera rápido una vista copiando el Mockup "view_template.php" hacia App/Views/...'],
    ];

    $header = ['Comando', 'Uso', 'Descripción'];
    CLI::table($commandsData, $header);
  }

}