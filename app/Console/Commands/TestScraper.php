<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestScraper extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-scraper {page=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the FontsInUse scraper for a specific page or local file';

    /**
     * Execute the console command.
     */
    public function handle(\App\Actions\Scraping\ScrapeFontsInUsePage $scraper)
    {
        try {
            $page = (int) $this->argument('page');
            $this->info("Empezando scrape para página {$page}...");

            $scraper->handle($page);

            $this->info("Scrape completado exitosamente.");

            $items = \App\Models\GalleryItem::count();
            $this->info("Total Items en DB: " . $items);

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}
