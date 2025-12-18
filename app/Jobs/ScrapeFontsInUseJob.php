<?php

namespace App\Jobs;

use App\Actions\Scraping\ScrapeFontsInUsePage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeFontsInUseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0;

    public function __construct(
        public int $pageCount
    ) {
        //
    }

    public function handle(ScrapeFontsInUsePage $action): void
    {
        Log::info("ScrapeFontsInUseJob::handle() | Iniciando scrape para {$this->pageCount} páginas.");

        $processedPages = 0;
        for ($i = 1; $i <= $this->pageCount; $i++) {

            Log::info("ScrapeFontsInUseJob::handle() | Procesando página {$i}/{$this->pageCount}...");

            try {
                $action->handle($i);
                $processedPages++;
            } catch (\Exception $e) {
                Log::error("ScrapeFontsInUseJob::handle() | Error al procesar página {$i}: " . $e->getMessage());
            }

            sleep(1);
        }

        Log::info("ScrapeFontsInUseJob::handle() | Finalizado scrape para {$this->pageCount} páginas. Procesadas correctamente: {$processedPages}");
        sleep(5);
    }
}
