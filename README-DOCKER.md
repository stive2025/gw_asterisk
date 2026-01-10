# Despliegue con Docker

## Requisitos
- Docker
- Docker Compose

## Configuración Inicial

1. **Copiar archivo de configuración**
   ```bash
   cp asterisk/.env.example asterisk/.env
   ```

2. **Configurar variables de entorno en `.env`**
   ```env
   APP_NAME="Laravel Asterisk"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://localhost:8000

   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=laravel
   DB_PASSWORD=laravel_password

   CACHE_DRIVER=redis
   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis

   REDIS_HOST=redis
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

## Construcción y Despliegue

### Opción 1: Docker Compose (Recomendado)

```bash
# Construir e iniciar los contenedores
docker-compose up -d --build

# Ver logs
docker-compose logs -f app

# Ejecutar migraciones
docker-compose exec app php artisan migrate --force

# Generar key de aplicación
docker-compose exec app php artisan key:generate

# Optimizar aplicación
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Detener contenedores
docker-compose down

# Detener y eliminar volúmenes
docker-compose down -v
```

### Opción 2: Docker solo

```bash
# Construir imagen
docker build -t laravel-asterisk:latest .

# Ejecutar contenedor
docker run -d \
  --name laravel-asterisk \
  -p 8000:80 \
  -v $(pwd)/asterisk:/var/www/html \
  -e APP_ENV=production \
  laravel-asterisk:latest
```

## Acceso a la Aplicación

- **URL**: http://localhost:8000
- **Base de datos**: localhost:3306
- **Redis**: localhost:6379

## Comandos Útiles

```bash
# Acceder al contenedor
docker-compose exec app bash

# Ver logs de PHP-FPM
docker-compose exec app tail -f /var/log/supervisor/php-fpm.log

# Ver logs de Nginx
docker-compose exec app tail -f /var/log/supervisor/nginx.log

# Ver logs de Queue
docker-compose exec app tail -f /var/log/supervisor/laravel-queue.log

# Ejecutar comandos artisan
docker-compose exec app php artisan [comando]

# Limpiar cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear

# Reiniciar workers de queue
docker-compose exec app php artisan queue:restart
```

## Configuración de Asterisk

Asegúrate de configurar las variables de entorno de Asterisk en el archivo `.env`:

```env
ASTERISK_SERVER_IP=tu_servidor_asterisk
ASTERISK_USERNAME=tu_usuario
ASTERISK_PASSWORD=tu_contraseña
```

## Producción

Para despliegue en producción:

1. Asegúrate de que `APP_DEBUG=false`
2. Configura `APP_URL` con tu dominio
3. Usa contraseñas seguras para la base de datos
4. Configura SSL/TLS con Nginx o un proxy reverso
5. Considera usar un servicio de base de datos administrado
6. Configura backups automáticos

## Solución de Problemas

### Error de permisos
```bash
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage
```

### Reconstruir contenedores
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Ver estado de servicios
```bash
docker-compose ps
docker-compose exec app supervisorctl status
```
