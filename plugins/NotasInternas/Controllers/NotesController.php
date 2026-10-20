<?php

declare(strict_types=1);

namespace Plugins\NotasInternas\Controllers;

use App\Controllers\PluginSystem\PluginBaseController;
use App\Libraries\Breadcrumb;

class NotesController extends PluginBaseController
{
    public function index()
    {
        $this->assertPermission('notasinternas.access');
        $this->setViewSuccess('Notas internas');
        $this->setPageTittleAhead('Notas internas', 'Herramientas');

        $breadcrumb = new Breadcrumb();
        $this->viewData['breadcrumb'] = $breadcrumb->getBreadCrumbHtml([
            'Inicio'          => route_to('dashboard.index'),
            'Notas internas'  => '',
        ]);

        $this->viewData['layout'] = 'Layouts/user_loggedin_layout';

        return view(ROOTPATH . 'plugins/NotasInternas/Views/notes_index', $this->viewData);
    }
}
