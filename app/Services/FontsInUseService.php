<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FontsInUseService
{
    protected string $baseUrl = 'https://fontsinuse.com';

    public function __construct(
        protected string $username,
        protected string $password
    ) {
        if (empty($this->username) || empty($this->password)) {
            Log::warning("FontsInUseService::__construct() | El username o password está vacío. Por favor, verificar el archivo .env.");
        }
    }

    /**
     * Autenticarse en FontsInUse y cachear las cookies de sesión.
     */
    protected function authenticate(): array
    {
        Log::info("FontsInUseService::authenticate() | Iniciando autenticación en FontsInUse");

        // 1. Realizar petición GET a la página de login para extraer el token CSRF
        /** @var \Illuminate\Http\Client\Response $loginPageResponse */
        $loginPageResponse = Http::baseUrl($this->baseUrl)->get('/login');

        if (!$loginPageResponse->successful()) {
            $message = "FontsInUseService::authenticate() | Error al cargar la página de login. Estado: " . $loginPageResponse->status();
            Log::error($message);
            throw new \Exception($message);
        }

        $html = $loginPageResponse->body();

        // Extraer el token CSRF y la cookie de sesión inicial
        if (!preg_match('/name="_csrf_token"\s+value="([^"]+)"/', $html, $matches)) {
            $message = "FontsInUseService::authenticate() | No se pudo encontrar el token CSRF en la página de login.";
            Log::error($message);
            Log::debug("FontsInUseService::authenticate() | Contenido HTML: " . substr($html, 0, 1000));
            throw new \Exception($message);
        }
        $csrfToken = $matches[1];
        Log::info("FontsInUseService::authenticate() | Token CSRF extraído: " . $csrfToken);

        $initialCookies = $loginPageResponse->cookies();
        Log::info("FontsInUseService::authenticate() | Cookies iniciales: " . print_r($initialCookies->toArray(), true));

        $initialSessionCookie = $initialCookies->getCookieByName('PHPSESSID');

        if (!$initialSessionCookie) {
            $message = "FontsInUseService::authenticate() | No se pudo encontrar la cookie de sesión inicial.";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info("FontsInUseService::authenticate() | Token CSRF y Cookie de sesión inicial extraídos correctamente. Valor de la cookie: " . $initialSessionCookie->getValue());

        // 2. Realizar petición POST para iniciar sesión
        $postHeaders = [
            'Referer' => $this->baseUrl . '/login',
            'Origin' => $this->baseUrl,
            'Host' => 'fontsinuse.com',
            'User-Agent' => 'Mozilla/5.0 asdasdasd',
            'Upgrade-Insecure-Requests' => '1',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'es-AR,es;q=0.8,en-US;q=0.5,en;q=0.3',
            'Cache-Control' => 'max-age=0',
            'Connection' => 'keep-alive',
            'Cookie' => 'PHPSESSID=' . $initialSessionCookie->getValue(),
        ];

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::baseUrl($this->baseUrl)
            ->asForm()
            // ->withOptions([
            //     'allow_redirects' => false,
            // ])
            ->withHeaders($postHeaders)
            ->post('/login', [
                'username' => $this->username,
                'password' => $this->password,
                '_csrf_token' => $csrfToken,
            ]);

        $cookies = $response->cookies();

        $cookieData = [];
        foreach ($cookies as $cookie) {
            $cookieData[$cookie->getName()] = $cookie->getValue();
        }

        // Verificar si se obtuvo una cookie de usuario.
        if (!isset($cookieData['fiu_user'])) {
            $message = "FontsInUseService::authenticate() | Error al iniciar sesión. No se recibió cookie de usuario.";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info("FontsInUseService::authenticate() | Autenticación exitosa. Cookies obtenidas.");

        // 3. Cachear las cookies
        foreach ($cookieData as $name => $value) {
            $cookieObj = $cookies->getCookieByName($name);
            if ($cookieObj) {
                $expires = $cookieObj->getExpires();
                $ttl = $expires ? Carbon::createFromTimestamp($expires) : now()->addDay();
                Cache::put("fontsinuse_session_cookies.{$name}", $value, $ttl);
                Log::info("FontsInUseService::authenticate() | Caché de cookie {$name} exitosa. Valor: {$value}. Expira en: " . $ttl->format('Y-m-d H:i:s'));
            }
        }

        return $cookieData;
    }

    /**
     * Obtener las cookies de usuario y autenticarse si es necesario.
     */
    protected function getSessionCookies(): array
    {
        $userCookie     = Cache::get('fontsinuse_session_cookies.fiu_user');
        $sessionCookie  = Cache::get('fontsinuse_session_cookies.PHPSESSID');

        $cookies = [];

        if (!$userCookie) {
            $cookies = $this->authenticate();
        } else {
            $cookies = [
                'fiu_user'   => $userCookie,
                'PHPSESSID'  => $sessionCookie,
            ];
        }

        Log::info("FontsInUseService::getSessionCookies() | Cookies obtenidas: " . print_r($cookies, true));

        return $cookies;
    }

    /**
     * Realizar una petición GET a una ruta específica utilizando la sesión autenticada.
     */
    public function request(string $path, int $try = 1): string
    {
        // 1. Revisar si se ha excedido el número máximo de intentos
        if ($try > 2) {
            $message = "FontsInUseService::request() | Error al realizar la petición. {$try} intentos fallidos.";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info("FontsInUseService::request() | Realizando petición a {$path}. Intento {$try}");

        // 2. Asegurarse de que la ruta comienza con /
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // 3. Obtener las cookies de sesión y autenticarse si es necesario
        $cookies = $this->getSessionCookies();

        Log::info("FontsInUseService::request() | Cookies de sesión obtenidas");

        // 4. Construir la cadena de cookies
        $cookieHeader = collect($cookies)
            ->map(fn($val, $key) => "$key=$val")
            ->join('; ');

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'Cookie' => $cookieHeader,
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:145.0) Gecko/20100101 Firefox/145.0',
            ])
            ->get($path);

        // 5. Verificar si se ha redirigido a la página de login y re-autenticarse si es necesario
        if ($response->status() === 302 && str_contains($response->header('Location'), 'login')) {
            Cache::forget('fontsinuse_session_cookies.PHPSESSID');
            Cache::forget('fontsinuse_session_cookies.fiu_user');

            return $this->request($path, $try + 1);
        }

        if (!$response->successful()) {
            $message = "FontsInUseService::request() | Failed to fetch path {$path}. Status: {$response->status()}";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info("FontsInUseService::request() | Petición exitosa a {$path}");

        return $response->body();
    }

    /**
     * Realizar una petición GET a una ruta específica utilizando la sesión autenticada.
     */
    public function fetchPage(int $pageNumber): string
    {
        return $this->request("/?page={$pageNumber}");
    }
}
