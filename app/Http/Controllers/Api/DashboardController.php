<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Font;
use App\Models\GalleryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function stats()
    {
        try {
            return response()->json([
                'gallery_items_count' => GalleryItem::count(),
                'fonts_count' => Font::count(),
            ]);
        } catch (\Exception $e) {
            Log::error("DashboardController::stats() | Error al obtener stats: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Error al obtener stats. Por favor, vuelva a sincronizar.',
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $fontName = $request->input('font_name');

            if (!$fontName) {
                return response()->json([]);
            }

            $fonts = Font::where('name', 'like', "%{$fontName}%")
                ->take(20)
                ->get(['name', 'image_url', 'foundry_url']);

            return response()->json([
                'fonts' => $fonts,
            ]);
        } catch (\Exception $e) {
            Log::error("DashboardController::search() | Error al buscar fonts: " . $e->getMessage());
            
            return response()->json([
                'error' => 'Error al buscar fonts. Por favor, vuelva a sincronizar.',
            ], 500);
        }
    }
}
