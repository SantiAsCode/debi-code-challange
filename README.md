# Debi - Code Challange

    Code Challange para puesto Desarrollador backend/full-stack en [Debi](https://debi.pro)

## Setup local con Docker

1. **Configuración de entorno**:
   ```bash
   cp .env.example .env
   ```

2. **Iniciar la aplicación**:
   ```bash
   docker compose up -d
   ```
   *Inicia el Backend y Frontend (Vite) automáticamente.*

3. **Instalar dependencias y migraciones** (Primera ejecución solo):
   ```bash
   docker compose exec fontsinuse-scraper composer install
   docker compose exec fontsinuse-scraper php artisan key:generate
   docker compose exec fontsinuse-scraper php artisan migrate
   ```

4. **Acceso a la aplicación**:

*   **Frontend**: http://localhost:5173
*   **Backend**: http://localhost:8008

## Setup local sin Docker

**Requisitos**: PHP 8.2+, Composer, Node.js, SQLite (la base de datos de sqlite se crea automáticamente en composer.json).

1. **Configuración de entorno**:
   ```bash
   cp .env.example .env
   composer install
   npm install
   php artisan key:generate
   php artisan migrate
   ```

2. **Iniciar la aplicación**:
   ```bash
   composer run dev
   ```

3. **Acceso a la aplicación**:
   *Access at http://localhost:8000*