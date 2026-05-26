<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadCurrentMedicineStockRequest;
use App\Http\Requests\ImportMedicineStockCsvRequest;
use App\Services\Pharmacy\MedicineStockImportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicineStockImportController extends Controller
{
    public function __construct(private MedicineStockImportService $service)
    {
    }

    public function store(ImportMedicineStockCsvRequest $request): JsonResponse
    {
        $report = $this->service->import($request->file('file'), auth()->id());

        return response()->json($report);
    }

    public function downloadCurrentStock(DownloadCurrentMedicineStockRequest $request): StreamedResponse
    {
        $rows = $this->service->currentStockSnapshot();
        $fileName = 'backup-estoque-atual-'.now()->format('Y-m-d_H-i-s').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'medicine_item_id',
                'internal_code',
                'active_ingredient',
                'concentration',
                'pharmaceutical_form',
                'presentation',
                'reference_date',
                'availability_status',
                'available_quantity',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->medicine_item_id,
                    $row->internal_code,
                    $row->active_ingredient,
                    $row->concentration,
                    $row->pharmaceutical_form,
                    $row->presentation,
                    $row->reference_date,
                    $row->availability_status,
                    $row->available_quantity,
                ], ';');
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
