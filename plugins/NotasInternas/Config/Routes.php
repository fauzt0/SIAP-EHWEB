<?php

declare(strict_types=1);

/**
 * Rutas del plugin Notas internas.
 * Invocado por NotasInternasPlugin::loadRoutes().
 *
 * La función queda definida aunque este archivo se incluya más de una vez
 * en la misma petición: la segunda inclusión no puede redeclararla.
 */
if (! function_exists('notas_internas_declare_routes')) {
    function notas_internas_declare_routes($routes): void
    {
        $routes->group('nat/plugins/notas-internas', [
            'namespace' => 'Plugins\NotasInternas\Controllers',
            'filter'    => 'session',
        ], static function ($routes): void {
            $perm = 'permission:notasinternas.access';

            $routes->get('/', 'NotesController::index', [
                'as'     => 'notasinternas.index',
                'filter' => $perm,
            ]);
            $routes->post('list_ajax', 'NotesController::list_ajax', [
                'as'     => 'notasinternas.list_ajax',
                'filter' => $perm,
            ]);
            $routes->post('save', 'NotesController::save', [
                'as'     => 'notasinternas.save',
                'filter' => $perm,
            ]);
            $routes->post('delete/(:num)', 'NotesController::delete/$1', [
                'as'     => 'notasinternas.delete',
                'filter' => $perm,
            ]);
        });
    }
}

return static function ($routes): void {
    notas_internas_declare_routes($routes);
};
