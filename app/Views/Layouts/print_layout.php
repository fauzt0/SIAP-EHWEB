<?php
/**
 * Layout de impresión: sin sidebar ni topbar.
 * Usado por recibos de nómina y documentos similares.
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="<?= base_url('bootstrap/css/app.css') ?>" rel="stylesheet">
    <?= $this->renderSection('pageStyles') ?>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-sheet { box-shadow: none !important; margin: 0 !important; }
        }
        body.print-body { background: #e9ecef; }
        .print-toolbar { position: sticky; top: 0; z-index: 10; }
    </style>
</head>
<body class="print-body">
    <?= $this->renderSection('main') ?>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
