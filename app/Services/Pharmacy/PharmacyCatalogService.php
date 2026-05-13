<?php

namespace App\Services\Pharmacy;

use App\Models\PharmacyAcquisitionSource;
use App\Models\PharmacyPharmaceuticalForm;
use App\Models\PharmacyPresentation;
use App\Models\PharmacyUnit;
use App\Services\AuditService;

class PharmacyCatalogService
{
    public function all(): array
    {
        $units = PharmacyUnit::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $forms = PharmacyPharmaceuticalForm::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $presentations = PharmacyPresentation::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $acquisitionSources = PharmacyAcquisitionSource::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        AuditService::record('VIEW_PHARMACY_CATALOGS', null, null, [
            'units_count' => $units->count(),
            'forms_count' => $forms->count(),
            'presentations_count' => $presentations->count(),
            'acquisition_sources_count' => $acquisitionSources->count(),
        ]);

        return [
            'units' => $units,
            'pharmaceutical_forms' => $forms,
            'presentations' => $presentations,
            'acquisition_sources' => $acquisitionSources,
        ];
    }
}
