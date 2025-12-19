<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapeFontsInUseJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ContentController extends Controller
{
    /**
     * Iniciar el proceso de scraping. Si no se especifica un número de páginas, se usará 5 por defecto.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'page_count' => 'integer|min:1|max:5',
            ]);

            $pageCount = $request->input('page_count', 5);

            ScrapeFontsInUseJob::dispatch($pageCount);

            return response()->json([
                'message' => "Proceso de scraping iniciado para {$pageCount} páginas.",
            ]);
        } catch (\Exception $e) {
            Log::error("ContentController::update() | Error al iniciar el proceso de scraping: " . $e->getMessage());

            return response()->json([
                'error' => 'Error al iniciar el proceso de scraping. Por favor, intente nuevamente.',
            ], 500);
        }
    }
}
