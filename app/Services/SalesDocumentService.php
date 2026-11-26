<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\PdfLibrary;
use App\Libraries\PluginSystem\PluginHookRegistry;
use App\Models\Organization\OrgBranchModel;
use App\Models\Organization\OrgCompanyModel;
use App\Models\Sales\SalesDocumentTemplateModel;

/**
 * PDF de cotización, venta y recibo con logo de sucursal o empresa.
 */
final class SalesDocumentService
{
    /**
     * @return array{company:?object,branch:?object,logo_path:?string,logo_abs:?string}
     */
    public function issuer(?int $branchId = null): array
    {
        $company = (new OrgCompanyModel())->getProfile();
        $branch  = null;
        if ($branchId) {
            $branch = (new OrgBranchModel())->find($branchId);
        }

        $logo = null;
        if ($branch && ! empty($branch->logo_path)) {
            $logo = $branch->logo_path;
        } elseif ($company && ! empty($company->logo_path)) {
            $logo = $company->logo_path;
        }

        $abs = null;
        if ($logo) {
            $candidate = FCPATH . ltrim((string) $logo, '/');
            if (is_file($candidate)) {
                $abs = $candidate;
            }
        }

        return [
            'company'   => $company,
            'branch'    => $branch,
            'logo_path' => $logo,
            'logo_abs'  => $abs,
        ];
    }

    /**
     * @param array<string,mixed> $payload quote|order|receipt + items
     */
    public function buildHtml(string $kind, array $payload, ?int $branchId = null): string
    {
        $template = (new SalesDocumentTemplateModel())->forKind($kind)
            ?: (object) ['title' => $kind, 'show_logo' => 1, 'paper' => 'Letter', 'legal_note' => null, 'header_extra_html' => null, 'footer_extra_html' => null, 'default_branch_id' => null];
        $issuer   = $this->issuer($branchId ?: (int) ($template->default_branch_id ?? 0) ?: null);
        $html     = view('Sales/document_pdf', [
            'kind'     => $kind,
            'template' => $template,
            'issuer'   => $issuer,
            'doc'      => $payload,
        ]);

        return PluginHookRegistry::applyFilter('sales.document.html', $html, $kind, $payload);
    }

    /**
     * @return array{html:string,binary:string,filename:string,paper:string}
     */
    public function renderPdf(string $kind, array $payload, ?int $branchId = null): array
    {
        $template = (new SalesDocumentTemplateModel())->forKind($kind)
            ?: (object) ['title' => $kind, 'show_logo' => 1, 'paper' => 'Letter', 'watermark_text' => null];
        $paper    = $template->paper ?? 'Letter';
        $html     = $this->buildHtml($kind, $payload, $branchId);
        $number   = (string) ($payload['number'] ?? $kind);
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $kind . '-' . $number) . '.pdf';

        $pdf = new PdfLibrary(['format' => $paper]);
        if (! empty($template->watermark_text)) {
            $pdf->setWatermarkText((string) $template->watermark_text, 0.08);
        }
        $pdf->loadHtml($html);

        return [
            'html'     => $html,
            'binary'   => $pdf->getAsString(),
            'filename' => $filename,
            'paper'    => $paper,
        ];
    }
}
