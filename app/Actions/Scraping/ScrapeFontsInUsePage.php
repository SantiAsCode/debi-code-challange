<?php

namespace App\Actions\Scraping;

use App\Services\FontsInUseService;
use Illuminate\Support\Facades\Log;

class ScrapeFontsInUsePage
{
    public function __construct(
        protected FontsInUseService $service
    ) {
        //
    }

    public function handle(int $pageNumber = 1): void
    {
        Log::info("ScrapeFontsInUsePage::handle() | Iniciando scrape de página {$pageNumber}");

        try {
            $content = $this->service->fetchPage($pageNumber);
            $this->parseHtml($content, $pageNumber);
        } catch (\Exception $e) {
            $message = "ScrapeFontsInUsePage::handle() | Error al scrapear página {$pageNumber}: " . $e->getMessage();
            Log::error($message);
            throw new \Exception($message);
        }
    }

    public function parseHtml(string $content, int $pageNumber = 0): void
    {
        // Suprimir errores de HTML5
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        // Forzar UTF-8
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//div[contains(@class, "fiu-galleryItem")]');

        Log::info("ScrapeFontsInUsePage::parseHtml() | Encontrados " . $nodes->length . " nodos" . ($pageNumber > 0 ? " en página {$pageNumber}" : ""));
        
        $items = 0;
        foreach ($nodes as $node) {
            $items = $this->processNode($xpath, $node) ? $items + 1 : $items;
        }

        Log::info("ScrapeFontsInUsePage::parseHtml() | Items agregados: {$items}");

        if ($pageNumber > 0) {
            Log::info("ScrapeFontsInUsePage::handle() | Página {$pageNumber} procesada exitosamente.");
        }
    }

    private function processNode(\DOMXPath $xpath, \DOMNode $node): bool
    {
        $class = $node->attributes->getNamedItem('class')?->nodeValue ?? '';
        if (str_contains($class, 'fiu-galleryAd__wrapper')) {
            return false;
        }

        $id = $node->attributes->getNamedItem('data-id')?->nodeValue;
        if (!$id) return false;

        $titleNode       = $xpath->query('.//span[contains(@class, "fiu-galleryItem__headline")]', $node)->item(0);
        $dateNode        = $xpath->query('.//span[contains(@class, "fiu-galleryItem__date")]', $node)->item(0);
        $imgNode         = $xpath->query('.//div[contains(@class, "fiu-galleryItem__img-wrapper")]//img', $node)->item(0);
        $contributorNode = $xpath->query('.//p[contains(@class, "fiu-galleryItem__contributor")]', $node)->item(0);
        $designersNodes  = $xpath->query('.//ul[contains(@class, "fiu-galleryItem__designers")]/li', $node);

        $designers = [];
        foreach ($designersNodes as $designerNode) {
            $designers[] = trim($designerNode->textContent);
        }

        $galleryItem = \App\Models\GalleryItem::updateOrCreate(
            ['external_id' => $id],
            [
                'title'       => $titleNode?->textContent ?? 'Unknown',
                'year'        => $dateNode?->textContent,
                'image_url'   => $imgNode?->attributes->getNamedItem('src')?->nodeValue,
                'contributor' => $contributorNode ? trim(str_replace('Contributed by', '', $contributorNode->textContent)) : null,
                'designers'   => $designers,
            ]
        );

        $fontNodes = $xpath->query('.//ul[contains(@class, "fiu-sampleList")]/li', $node);

        $galleryItem->fonts()->delete();

        foreach ($fontNodes as $fontNode) {
            $linkNode = $xpath->query('.//a', $fontNode)->item(0);
            $imgFontNode = $xpath->query('.//img', $fontNode)->item(0);

            if (!$imgFontNode) continue;

            $fontName = $imgFontNode->attributes->getNamedItem('alt')?->nodeValue ?? $imgFontNode->attributes->getNamedItem('title')?->nodeValue;
            $fontImageUrl = $imgFontNode->attributes->getNamedItem('src')?->nodeValue;
            $foundryUrl = $imgFontNode->attributes->getNamedItem('data-src-href')?->nodeValue; // Often stored here
            $internalUrl = $linkNode?->attributes->getNamedItem('href')?->nodeValue;

            if ($fontName) {
                $galleryItem->fonts()->create([
                    'name' => $fontName,
                    'image_url' => $fontImageUrl,
                    'foundry_url' => $foundryUrl,
                    'internal_url' => $internalUrl,
                ]);
            }
        }

        return true;
    }
}
