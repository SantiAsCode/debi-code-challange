# Debi - Code Challange

Code Challange para puesto Desarrollador backend/full-stack en [Debi](https://debi.pro)

Se puede ejecutar el proyecto usando Docker (recomendado) o localmente sin Docker.
En ambos casos, el archivo `database/database.sqlite` se creará automáticamente si no existe.

### Opción 1: Docker (Recomendado)

1. **Configuración de entorno**:
```bash
git clone <repository_url>
cd <repository_name>
cp .env.example .env
```

2. **Iniciar la aplicación**:
```bash
docker compose up -d
```

> [!NOTE]  
> Inicia el Backend y Frontend (Vite) automáticamente.

3. **Instalar dependencias y migraciones** ():
```bash
docker compose exec fontsinuse-scraper composer install
docker compose exec fontsinuse-scraper npm install
docker compose exec fontsinuse-scraper php artisan key:generate
docker compose exec fontsinuse-scraper php artisan migrate
```

> [!NOTE]  
> Solamente necesarios la primera vez.

4. **Acceso a la aplicación**:
*   **Vite**: http://localhost:5173
*   **Aplicación**: http://localhost:8008

### Opción 2: Desarrollo local sin Docker

> [!IMPORTANT]  
> Requisitos: PHP 8.2+, Composer, Node.js, SQLite (la base de datos de sqlite se crea automáticamente en composer.json).

1.  **Clone the repository**
```bash
git clone <repository_url>
cd <repository_name>
cp .env.example .env
```

2.  **Install las dependencies**
```bash
composer install
npm install
```

3. **Generar key y correr migraciones**
```bash
php artisan key:generate
php artisan migrate
```

> [!NOTE]  
> Solamente necesarios la primera vez.

4.  **Iniciar la aplicación**
```bash
composer run dev
```

5. **Acceso a la aplicación**:
*   **Vite**: http://localhost:5173
*   **Aplicación**: http://localhost:8008
