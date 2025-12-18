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
        //
    }

    /**
     * Autenticarse en FontsInUse y cachear las cookies de sesión.
     */
    protected function authenticate(): array
    {
        Log::info("FontsInUse::authenticate() | Iniciando autenticación en FontsInUse");

        // 1. Realizar petición GET a la página de login para extraer el token CSRF
        /** @var \Illuminate\Http\Client\Response $loginPageResponse */
        $loginPageResponse = Http::baseUrl($this->baseUrl)->get('/login');

        if (!$loginPageResponse->successful()) {
            $message = "Error al cargar la página de login. Estado: " . $loginPageResponse->status();
            Log::error($message);
            throw new \Exception($message);
        }

        $html = $loginPageResponse->body();

        // Extraer el token CSRF y la cookie de sesión inicial
        if (!preg_match('/<input type="hidden" name="_csrf_token" value="(.*?)">/', $html, $matches)) {
            $message = "No se pudo encontrar el token CSRF en la página de login.";
            Log::error($message);
            throw new \Exception($message);
        }
        $csrfToken = $matches[1];
        $initialCookies = $loginPageResponse->cookies();
        $initialSessionCookie = $initialCookies->getCookieByName('PHPSESSID');

        if (!$initialSessionCookie) {
            $message = "No se pudo encontrar la cookie de sesión inicial.";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info("FontsInUse::authenticate() | CSRF Token y Cookie de sesión inicial extraídos correctamente");

        // 2. Realizar petición POST para iniciar sesión
        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::baseUrl($this->baseUrl)
            ->asForm()
            ->withCookies(['PHPSESSID' => $initialSessionCookie->getValue()], $this->baseUrl)
            ->post('/login', [
                'username' => $this->username,
                'password' => $this->password,
                '_csrf_token' => $csrfToken,
                '_remember_me' => 'on',
            ]);

        $cookies = $response->cookies();

        $cookieData = [];
        foreach ($cookies as $cookie) {
            $cookieData[$cookie->getName()] = $cookie->getValue();
        }

        if (!$cookieData['fiu_user'] || !$cookieData['PHPSESSID']) {
            $message = "Error al iniciar sesión. No se recibió una cookie de sesión.";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info("FontsInUse::authenticate() | Cookies de usuario y sesión obtenidas correctamente");

        // 3. Cachear las cookies
        foreach ($cookieData as $name => $value) {
            $expires = $cookies->getCookieByName($name)->getExpires();
            $ttl = $expires ? Carbon::createFromTimestamp($expires) : now()->addDay();
            Cache::put("fontsinuse_session_cookies.{$name}", $value, $ttl);
        }

        Log::info("FontsInUse::authenticate() | Cookies de usuario y sesión cacheadas. Cookie de usuario expira en: " . Carbon::createFromTimestamp($cookies->getCookieByName('fiu_user')->getExpires())->diffForHumans());

        return $cookieData;
    }

    /**
     * Obtener las cookies de sesión y autenticarse si es necesario.
     */
    protected function getSessionCookies(): array
    {
        $sessionCookie  = Cache::get('fontsinuse_session_cookies.PHPSESSID');
        $userCookie     = Cache::get('fontsinuse_session_cookies.fiu_user');
        $rememberCookie = Cache::get('fontsinuse_session_cookies.REMEMBERME');

        $cookies = [];
        if (!$userCookie || !$sessionCookie) {
            $cookies = $this->authenticate();
        } else {
            $cookies = [
                'PHPSESSID'  => $sessionCookie,
                'fiu_user'   => $userCookie,
                'REMEMBERME' => $rememberCookie,
            ];
        }

        return $cookies;
    }

    /**
     * Realizar una petición GET a una ruta específica utilizando la sesión autenticada.
     */
    public function request(string $path, int $try = 1): string
    {
        // 1. Revisar si se ha excedido el número máximo de intentos
        if ($try > 2) {
            $message = "Error al realizar la petición. {$try} intentos fallidos.";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info("FontsInUse::request() | Realizando petición a {$path}. Intento {$try}");

        // 2. Asegurarse de que la ruta comienza con /
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        // 3. Obtener las cookies de sesión y autenticarse si es necesario
        $cookies = $this->getSessionCookies();

        Log::info("FontsInUse::request() | Cookies de sesión obtenidas");

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
            $message = "Failed to fetch path {$path}. Status: {$response->status()}";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info("FontsInUse::request() | Petición exitosa a {$path}");

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
