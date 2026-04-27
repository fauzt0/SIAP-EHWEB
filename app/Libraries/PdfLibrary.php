<?php

namespace App\Libraries;

use Mpdf\Mpdf;

/**
 * PdfLibrary
 * ─────────────────────────────────────────────────────────────────────────────
 * Wrapper centralizado para la generación de documentos PDF utilizando Mpdf.
 * Esta clase estandariza la configuración (márgenes, formato, codificación)
 * para garantizar consistencia en contratos, facturas y recibos.
 */
class PdfLibrary
{
    protected Mpdf $mpdf;

    /**
     * Constructor
     * Configura la instancia base de Mpdf con soporte UTF-8 y formato A4.
     * 
     * @param array $customConfig Configuración opcional para sobreescribir los defaults.
     */
    public function __construct(array $customConfig = [])
    {
        $defaultConfig = [
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'orientation'   => 'P', // P: Portrait, L: Landscape
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 20,
            'margin_bottom' => 20,
            'margin_header' => 10,
            'margin_footer' => 10,
            'tempDir'       => WRITEPATH . 'tmp', // Asegurar que los temporales se guarden en una ruta con permisos
        ];

        $config = array_merge($defaultConfig, $customConfig);

        $this->mpdf = new Mpdf($config);

        // Meta defaults
        $this->mpdf->SetCreator('ERP / Sistema Integral');
        $this->mpdf->SetAuthor('Recursos Humanos');
    }

    /**
     * Carga el contenido HTML al documento PDF.
     * 
     * @param string $html Contenido HTML puro.
     */
    public function loadHtml(string $html): void
    {
        // Convertir base_url() a rutas físicas con prefijo file:// para que mPDF cargue imágenes locales
        $html = str_replace(base_url(), 'file://' . FCPATH, $html);
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Carga contenido HTML pero permitiendo interpretar CSS específico.
     * 
     * @param string $html Contenido a renderizar.
     * @param string $css Archivo CSS o string CSS.
     */
    public function loadHtmlWithCss(string $html, string $css = ''): void
    {
        // Reemplazar base_url por ruta física absoluta con prefijo file:// para mPDF
        $html = str_replace(base_url(), 'file://' . FCPATH, $html);
        
        if (!empty($css)) {
            // mode 1: escribe CSS
            $this->mpdf->WriteHTML($css, 1);
        }
        // mode 2: escribe el HTML usando el CSS previo
        $this->mpdf->WriteHTML($html, 2);
    }

    /**
     * Configura un encabezado para todas las páginas.
     * 
     * @param string $html Contenido HTML del encabezado.
     */
    public function setHeader(string $html): void
    {
        $this->mpdf->SetHTMLHeader($html);
    }

    /**
     * Configura un pie de página para todas las páginas.
     * 
     * @param string $html Contenido HTML del pie.
     */
    public function setFooter(string $html): void
    {
        $this->mpdf->SetHTMLFooter($html);
    }

    /**
     * Define una marca de agua de texto en diagonal.
     * 
     * @param string $text Texto de la marca de agua.
     * @param float $alpha Opacidad (0 a 1).
     */
    public function setWatermarkText(string $text, float $alpha = 0.1): void
    {
        $this->mpdf->SetWatermarkText($text, $alpha);
        $this->mpdf->showWatermarkText = true;
    }

    /**
     * Exporta el documento PDF al navegador (Download).
     * 
     * @param string $filename Nombre del archivo con el que se descargará (ej: 'contrato.pdf').
     */
    public function download(string $filename = 'documento.pdf'): void
    {
        $this->mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    }

    /**
     * Exporta el documento PDF al navegador (Visualización Inline).
     * 
     * @param string $filename Nombre del archivo.
     */
    public function stream(string $filename = 'documento.pdf'): void
    {
        $this->mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
    }

    /**
     * Guarda el documento PDF en el disco del servidor.
     * 
     * @param string $absolutePath Ruta absoluta donde se guardará (ej: FCPATH . 'uploads/docs/archivo.pdf').
     */
    public function saveToFile(string $absolutePath): void
    {
        $this->mpdf->Output($absolutePath, \Mpdf\Output\Destination::FILE);
    }

    /**
     * Devuelve el contenido del PDF como una cadena (string binario).
     * Útil si se quiere adjuntar en un email sin guardarlo físicamente.
     * 
     * @return string
     */
    public function getAsString(): string
    {
        return $this->mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
