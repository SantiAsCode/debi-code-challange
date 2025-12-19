import AppLayout from '@/layouts/app-layout';
import { useEffect, useState } from 'react';

interface Font {
    name: string;
    image_url: string;
    foundry_url: string;
}

interface Stats {
    gallery_items_count: number;
    fonts_count: number;
}

export default function Dashboard() {
    const [stats, setStats] = useState<Stats>({ gallery_items_count: 0, fonts_count: 0 });
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<{ fonts: Font[] }>({ fonts: [] });
    const [loadingStats, setLoadingStats] = useState(true);
    const [searching, setSearching] = useState(false);

    useEffect(() => {
        fetch('/api/dashboard/stats')
            .then((res) => res.json())
            .then((data) => {
                setStats(data);
                setLoadingStats(false);
            })
            .catch((error) => {
                console.error(error.message);
                setLoadingStats(false);
            });
    }, []);

    const handleSearch = (fontName: string) => {
        setSearchQuery(fontName);
        if (!fontName) {
            setSearchResults({ fonts: [] });
            return;
        }

        setSearching(true);
        fetch(`/api/dashboard/search?font_name=${encodeURIComponent(fontName)}`)
            .then((res) => res.json())
            .then((data) => {
                setSearchResults(data);
                setSearching(false);
            })
            .catch((error) => {
                console.error(error.message);
                setSearching(false);
            });
    };

    const handleScrape = () => {
        setLoadingStats(true);
        fetch('/api/scrape', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ page_count: 5 }),
        })
            .then((res) => {
                if (!res.ok) throw new Error('Scraping fallido');
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            })
            .catch((err) => {
                console.error(err.message);
                setLoadingStats(false);
            });
    };

    return (
        <AppLayout>
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight text-gray-700">Dashboard</h1>
                    <button
                        onClick={handleScrape}
                        disabled={loadingStats}
                        className="inline-flex items-center justify-center rounded-md bg-gray-500 px-4 py-2 text-sm font-medium text-gray-50 shadow focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-gray-600 disabled:pointer-events-none disabled:opacity-50"
                    >
                        {loadingStats ? (
                            <svg className="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        ) : (
                            'Scrapear'
                        )}
                    </button>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-xl border border-gray-600 bg-gray-500 p-6 shadow-sm">
                        <h3 className="text-sm font-medium text-gray-50">Items de galeria scrapeados</h3>
                        <div className="mt-2 text-3xl font-bold text-gray-50">
                            {loadingStats ? '...' : stats.gallery_items_count}
                        </div>
                    </div>
                    <div className="rounded-xl border border-gray-600 bg-gray-500 p-6 shadow-sm">
                        <h3 className="text-sm font-medium text-gray-50">Fonts scrapeados</h3>
                        <div className="mt-2 text-3xl font-bold text-gray-50">
                            {loadingStats ? '...' : stats.fonts_count}
                        </div>
                    </div>
                </div>

                <div className="flex flex-col gap-4">
                    <div className="flex flex-col gap-2">
                        <h2 className="text-lg text-gray-700 font-semibold">Buscar fonts</h2>
                        <input
                            type="text"
                            placeholder="Buscar por nombre de font..."
                            className="w-full rounded-md border border-gray-600 bg-gray-500 px-3 py-2 text-sm ring-offset-gray-600 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-600 focus-visible:ring-offset-1 disabled:cursor-not-allowed disabled:opacity-50"
                            value={searchQuery}
                            onChange={(e) => handleSearch(e.target.value)}
                        />
                    </div>

                    <div className="grid gap-4 md:grid-cols-3 lg:grid-cols-4">
                        {searching && <p className="text-sm text-gray-50">Buscando...</p>}
                        {!searching && searchResults.fonts.length === 0 && searchQuery && (
                            <p className="text-sm text-gray-50">No se encotraron fonts. Por favor vuelva a sincronizarlos.</p>
                        )}
                        {searchResults.fonts.map((font, index) => (
                            <div key={index} className="overflow-hidden rounded-xl border border-gray-600 bg-gray-500 shadow-sm transition-all hover:shadow-md">
                                <div className="aspect-square w-full overflow-hidden bg-gray-400">
                                    <img
                                        src={font.image_url}
                                        alt={font.name}
                                        className="h-full w-full object-contain object-center"
                                        onError={(e) => {
                                            (e.target as HTMLImageElement).src = 'https://placehold.co/400x400?text=No+Image';
                                        }}
                                    />
                                </div>
                                <div className="p-4">
                                    <h4 className="font-semibold">{font.name}</h4>
                                    {font.foundry_url ? (
                                        <a
                                            href={font.foundry_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="mt-2 inline-block text-sm text-gray-50 underline"
                                        >
                                            Visitar origen
                                        </a>
                                    ) : (
                                        <span className="mt-2 text-sm text-gray-50">No se encontro link de origen</span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
