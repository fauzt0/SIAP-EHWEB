<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, array<int, string>|string> [filter_name => classname]
     *                                               or [filter_name => [classname1, classname2, ...]]
     * @phpstan-var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        //'AdminFilter'   => \App\Filters\AdminFilter::class, //protected by shield
        //'PermissionFilter' => \App\Filters\PermissionFilter::class,//protected by shield 
        'csrfTokenFilter'     => \App\Filters\CsrfTokenFilter::class, //filtro para actualizar y devolver el csrf token
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array<string, array<string, array<string, string>>>|array<string, array<string>>
     * @phpstan-var array<string, list<string>>|array<string, array<string, array<string, string>>>
     */
    public array $globals = [
        'before' => [
            // 'honeypot',
            'csrf' => [
              'except' => ['nat/user/updater/*'], //excluimos la ruta nat/user/update/* del filtro CSRF en entorno de desarrollo
            ],
            // 'invalidchars',
        ],
        'after' => [
            'toolbar',
            'csrfTokenFilter', //filtro para actualizar y devolver el csrf token
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'post' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     */

    //aplicaremos los filtros de autenticacion a todas las secciones internas
    public array $filters = [      
      
    ];
    

    //listado con las excepciones para entorno de desarrollo del filtro CSRF
    public array $developmentExceptions = [
        'nat/user/update/*',        
    ];

    public function __construct()
    {
        parent::__construct();

        // Si estamos en desarrollo, agregamos excepciones al filtro CSRF.
        if (ENVIRONMENT === 'development') {          
            // Aquí puedes agregar todas las rutas que quieres excluir durante el desarrollo.
                        
            $valoresActuales =  $this->globals['before']['csrf']['except'];
            //echo var_dump($valoresActuales); //for debug purposes 
            //echo "<hr>"; ////for debug purposes 
            ///agregamos los valores nuevos //for debug purposes 
            $this->globals['before']['csrf']['except'] = array_merge($valoresActuales, $this->developmentExceptions);
             //var_dump( $this->globals['before']['csrf']['except']); //for debug purposes 
        }
    }

} //end of class Filters
