<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Models\CsvUpload;
use App\Models\Olympiad;

class CsvUploadController extends Controller
{
    /**
     * Get all CSV uploads for a specific olympiad
     */
    public function index(Request $request, $olympiadId): JsonResponse
    {
        $olympiad = Olympiad::find($olympiadId);

        if (!$olympiad) {
            return response()->json([
                'success' => false,
                'message' => 'Olympiad not found'
            ], 404);
        }

        $csvUploads = CsvUpload::where('olympiad_id', $olympiadId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($upload) {
                return [
                    'id' => $upload->id,
                    'original_file_name' => $upload->original_file_name,
                    'successful_records' => $upload->successful_records,
                    'failed_records' => $upload->failed_records,
                    'header_errors' => $upload->header_errors,
                    'competitor_errors' => $upload->competitor_errors,
                    'total_records' => $upload->total_records,
                    'success_rate' => $upload->success_rate,
                    'has_errors' => $upload->hasErrors(),
                    'has_header_errors' => $upload->hasHeaderErrors(),
                    'has_competitor_errors' => $upload->hasCompetitorErrors(),
                    'has_error_file' => $upload->hasErrorFile(),
                    'file_size' => $upload->file_size,
                    'uploaded_at' => $upload->created_at->format('Y-m-d H:i:s'),
                    'uploaded_at_human' => $upload->created_at->locale('es')->diffForHumans()
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'olympiad' => [
                    'id' => $olympiad->id,
                    'name' => $olympiad->name
                ],
                'csv_uploads' => $csvUploads,
                'total_uploads' => $csvUploads->count(),
                'total_records_processed' => $csvUploads->sum('total_records'),
                'total_successful_records' => $csvUploads->sum('successful_records'),
                'total_failed_records' => $csvUploads->sum('failed_records')
            ]
        ]);
    }

    /**
     * Get details of a specific CSV upload
     */
    public function show($id): JsonResponse
    {
        $csvUpload = CsvUpload::with('olympiad')->find($id);

        if (!$csvUpload) {
            return response()->json([
                'success' => false,
                'message' => 'CSV upload not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $csvUpload->id,
                'olympiad' => [
                    'id' => $csvUpload->olympiad->id,
                    'name' => $csvUpload->olympiad->name
                ],
                'original_file_name' => $csvUpload->original_file_name,
                'successful_records' => $csvUpload->successful_records,
                'failed_records' => $csvUpload->failed_records,
                'header_errors' => $csvUpload->header_errors,
                'competitor_errors' => $csvUpload->competitor_errors,
                'total_records' => $csvUpload->total_records,
                'success_rate' => $csvUpload->success_rate,
                'has_errors' => $csvUpload->hasErrors(),
                'has_header_errors' => $csvUpload->hasHeaderErrors(),
                'has_competitor_errors' => $csvUpload->hasCompetitorErrors(),
                'has_error_file' => $csvUpload->hasErrorFile(),
                'file_size' => $csvUpload->file_size,
                'uploaded_at' => $csvUpload->created_at->format('Y-m-d H:i:s'),
                'uploaded_at_human' => $csvUpload->created_at->locale('es')->diffForHumans()
            ]
        ]);
    }

    /**
     * Download the original CSV file
     */
    public function download($id)
    {
        $csvUpload = CsvUpload::find($id);

        if (!$csvUpload) {
            return response()->json([
                'success' => false,
                'message' => 'CSV upload not found'
            ], 404);
        }

        if (!Storage::disk('public')->exists($csvUpload->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found on storage'
            ], 404);
        }

        return Storage::disk('public')->download(
            $csvUpload->file_path,
            $csvUpload->original_file_name
        );
    }

    /**
     * Download the error CSV file
     */
    public function downloadErrors($id)
    {
        $csvUpload = CsvUpload::find($id);

        if (!$csvUpload) {
            return response()->json([
                'success' => false,
                'message' => 'CSV upload not found'
            ], 404);
        }

        if (!$csvUpload->hasErrorFile()) {
            return response()->json([
                'success' => false,
                'message' => 'No error file available for this upload'
            ], 404);
        }

        if (!Storage::disk('public')->exists($csvUpload->error_file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Error file not found on storage'
            ], 404);
        }

        $errorFileName = pathinfo($csvUpload->original_file_name, PATHINFO_FILENAME) . '-errores.csv';

        return Storage::disk('public')->download(
            $csvUpload->error_file_path,
            $errorFileName
        );
    }
}
