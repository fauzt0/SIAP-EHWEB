<?php

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Guarda un archivo en public/uploads/{subdir}/ y devuelve la ruta relativa (§18).
 * Mismo criterio que HR (ContractTemplateController) y catálogo: getRandomName() + ruta en BD.
 *
 * @param string $subdir Subcarpeta bajo uploads/ (ej. organization, hr/contracts/logos)
 */
if (! function_exists('store_public_upload')) {
    function store_public_upload(?UploadedFile $file, string $subdir, ?string $previousRelativePath = null): ?string
    {
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }

        $subdir = trim($subdir, '/');
        $uploadDir = FCPATH . 'uploads/' . $subdir . '/';

        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $file->getRandomName();
        if (! $file->move($uploadDir, $newName)) {
            return null;
        }

        if ($previousRelativePath) {
            $oldFile = FCPATH . ltrim($previousRelativePath, '/');
            if (is_file($oldFile)) {
                @unlink($oldFile);
            }
        }

        return 'uploads/' . $subdir . '/' . $newName;
    }
}
