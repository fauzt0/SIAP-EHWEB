<?php

declare(strict_types=1);

namespace Plugins\NotasInternas\Controllers;

use App\Controllers\PluginSystem\PluginBaseController;
use App\Libraries\Breadcrumb;
use Plugins\NotasInternas\Models\NoteModel;
use Plugins\NotasInternas\NoteContent;
use Throwable;

class NotesController extends PluginBaseController
{
    private const PERMISSION = 'notasinternas.access';

    public function index()
    {
        $this->assertPermission(self::PERMISSION);
        $this->setViewSuccess('Notas internas');
        $this->setPageTittleAhead('Notas internas', 'Herramientas');

        $breadcrumb = new Breadcrumb();
        $this->viewData['breadcrumb'] = $breadcrumb->getBreadCrumbHtml([
            'Inicio'         => route_to('dashboard.index'),
            'Notas internas' => '',
        ]);

        $this->viewData['layout'] = 'Layouts/user_loggedin_layout';
        $this->viewData['noteRoutes'] = [
            'list'   => route_to('notasinternas.list_ajax'),
            'save'   => route_to('notasinternas.save'),
            'delete' => route_to('notasinternas.delete', 0),
        ];
        $this->viewData['noteColors'] = NoteContent::COLORS;

        return view(ROOTPATH . 'plugins/NotasInternas/Views/notes_index', $this->viewData);
    }

    public function list_ajax()
    {
        $this->assertAjaxPost();
        $this->assertPermission(self::PERMISSION);

        try {
            $notes = (new NoteModel())->forUser($this->userId());
        } catch (Throwable $e) {
            log_message('error', '[NotasInternas] listado: {message}', ['message' => $e->getMessage()]);

            return $this->jsonWithCsrf($this->setOutputError($this->schemaMessage()));
        }

        $payload = [];
        foreach ($notes as $row) {
            $payload[] = $this->present($row);
        }

        return $this->jsonWithCsrf($this->setOutputSuccess('Notas cargadas.', $payload));
    }

    public function save()
    {
        $this->assertAjaxPost();
        $this->assertPermission(self::PERMISSION);

        $userId = $this->userId();
        $id     = (int) $this->request->getPost('id');
        $body   = NoteContent::sanitizeBody($this->postString('body'));
        $title  = NoteContent::resolveTitle($this->postString('title'), $body);
        $color  = NoteContent::normalizeColor($this->postString('color'));
        $pinned = $this->postFlag('is_pinned') ? 1 : 0;

        if ($title === null) {
            return $this->jsonWithCsrf($this->setOutputError('Escribe un título o el contenido de la nota.'));
        }

        $model = new NoteModel();
        $data  = [
            'user_id'   => $userId,
            'title'     => $title,
            'body'      => $body,
            'color'     => $color,
            'is_pinned' => $pinned,
        ];

        try {
            if ($id > 0) {
                if ($model->ownedBy($id, $userId) === null) {
                    return $this->jsonWithCsrf($this->setOutputError('No se encontró la nota.', null, 404));
                }

                (new NoteModel())->where('id', $id)->where('user_id', $userId)->set($data)->update();
                $savedId = $id;
                $this->activityLogModel->logActivity('notasinternas', 'Editó una nota interna #' . $savedId . '.');
            } else {
                $savedId = (int) $model->insert($data);
                if ($savedId < 1) {
                    return $this->jsonWithCsrf($this->setOutputError('No se pudo guardar la nota.'));
                }
                $this->activityLogModel->logActivity('notasinternas', 'Creó una nota interna #' . $savedId . '.');
            }

            $row = (new NoteModel())->ownedBy($savedId, $userId);
        } catch (Throwable $e) {
            log_message('error', '[NotasInternas] guardado: {message}', ['message' => $e->getMessage()]);

            return $this->jsonWithCsrf($this->setOutputError($this->schemaMessage()));
        }

        if ($row === null) {
            return $this->jsonWithCsrf($this->setOutputError('La nota no quedó registrada.'));
        }

        return $this->jsonWithCsrf($this->setOutputSuccess('Nota guardada.', $this->present($row)));
    }

    public function delete($id = null)
    {
        $this->assertAjaxPost();
        $this->assertPermission(self::PERMISSION);

        $noteId = (int) $id;
        $userId = $this->userId();
        try {
            if ($noteId < 1 || (new NoteModel())->ownedBy($noteId, $userId) === null) {
                return $this->jsonWithCsrf($this->setOutputError('No se encontró la nota.', null, 404));
            }

            (new NoteModel())->where('id', $noteId)->where('user_id', $userId)->delete();
        } catch (Throwable $e) {
            log_message('error', '[NotasInternas] borrado: {message}', ['message' => $e->getMessage()]);

            return $this->jsonWithCsrf($this->setOutputError($this->schemaMessage()));
        }

        $this->activityLogModel->logActivity('notasinternas', 'Eliminó la nota interna #' . $noteId . '.');

        return $this->jsonWithCsrf($this->setOutputSuccess('Nota eliminada.', ['id' => $noteId]));
    }

    private function userId(): int
    {
        $user = auth()->user();

        return (int) ($user->id ?? 0);
    }

    private function postString(string $key): string
    {
        $value = $this->request->getPost($key);

        return is_string($value) ? $value : '';
    }

    private function postFlag(string $key): bool
    {
        $value = $this->request->getPost($key);
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on'], true);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function present(array $row): array
    {
        return [
            'id'         => (int) ($row['id'] ?? 0),
            'title'      => (string) ($row['title'] ?? ''),
            'body'       => (string) ($row['body'] ?? ''),
            'color'      => NoteContent::normalizeColor((string) ($row['color'] ?? '')),
            'is_pinned'  => ! empty($row['is_pinned']),
            'created_at' => $this->formatDate($row['created_at'] ?? null),
            'updated_at' => $this->formatDate($row['updated_at'] ?? null),
        ];
    }

    private function formatDate(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        $time = strtotime($value);

        return $time === false ? '' : date('d/m/Y H:i', $time);
    }

    private function schemaMessage(): string
    {
        return 'No se pudo usar la tabla de notas. En el servidor corre: php spark plugin:action update notas-internas';
    }
}
