# Guía de Despliegue en Render

## Requisitos Previos
- Cuenta en [Render](https://render.com) (gratis)
- Repositorio Git (GitHub, GitLab, o Bitbucket)
- Código subido al repositorio

## Paso 1: Preparar el Proyecto

### 1.1 Asegurar que estos archivos existen:
- `render.yaml` - Configuración de Render
- `build.sh` - Script de construcción
- `.env.example` - Variables de entorno de ejemplo

### 1.2 Hacer el script ejecutable:
```bash
chmod +x build.sh
```

### 1.3 Commit y push al repositorio:
```bash
git add .
git commit -m "Configure Render deployment"
git push origin master
```

## Paso 2: Crear Cuenta en Render

1. Ir a https://render.com
2. Hacer clic en **Get Started**
3. Registrarse con GitHub/GitLab (recomendado)
4. Autorizar acceso al repositorio

## Paso 3: Crear Base de Datos MySQL

1. En el Dashboard de Render, hacer clic en **New +**
2. Seleccionar **MySQL**
3. Configurar:
   - **Name**: `pawnshop-db`
   - **Database**: `pawnshop`
   - **User**: `pawnshop_user`
   - **Region**: Seleccionar el más cercano (Ohio, Oregon, Frankfurt)
   - **Plan**: **Free** (para pruebas) o **Starter $7/mes**

4. Hacer clic en **Create Database**
5. Esperar a que se cree (2-3 minutos)
6. **IMPORTANTE**: Guardar las credenciales que aparecen:
   - Internal Database URL
   - Host
   - Port
   - Database
   - Username
   - Password

## Paso 4: Crear Web Service

1. En el Dashboard, hacer clic en **New +**
2. Seleccionar **Blueprint**
3. Conectar tu repositorio
4. Render detectará automáticamente el archivo `render.yaml`
5. Hacer clic en **Apply**

Render creará automáticamente:
- El web service con PHP
- Las variables de entorno conectadas a la base de datos
- El proceso de build

## Paso 5: Configurar Variables de Entorno Adicionales

1. Ir a tu web service creado
2. Click en **Environment**
3. Agregar/Verificar estas variables:

```
APP_NAME=Pawnshop Management System
APP_ENV=production
APP_KEY=base64:XXXXXXX (se genera automáticamente)
APP_DEBUG=false
APP_URL=https://tu-app.onrender.com
APP_TIMEZONE=America/Guatemala

DB_CONNECTION=mysql
(Las demás DB_* se configuran automáticamente desde render.yaml)

FILAMENT_TIMEZONE=America/Guatemala

SESSION_DRIVER=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

4. Hacer clic en **Save Changes**

## Paso 6: Generar APP_KEY

Si APP_KEY no se generó automáticamente:

1. Ir a **Shell** en tu web service
2. Ejecutar:
```bash
php artisan key:generate --show
```
3. Copiar el resultado
4. Ir a **Environment** → Buscar `APP_KEY` → Pegar el valor
5. Save Changes

## Paso 7: Primera Ejecución

Render iniciará el build automáticamente:

1. Ver los logs en **Logs** tab
2. El proceso tomará 5-10 minutos la primera vez:
   - Instalar Composer dependencies
   - Instalar NPM dependencies
   - Build assets (Vite)
   - Correr migraciones
   - Seed roles y permisos
   - Generar Shield permissions

3. Cuando veas **"Build completed successfully!"**, continúa

## Paso 8: Crear Usuario Admin

1. Ir a **Shell** en tu web service
2. Ejecutar:
```bash
php artisan make:filament-user
```

3. Seguir las instrucciones:
   - Name: Admin User
   - Email: admin@pawnshop.com
   - Password: (tu contraseña segura)

4. Asignar rol Admin:
```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'admin@pawnshop.com')->first();
$user->assignRole('Admin');
$user->branch_id = 1; // Asignar a la primera sucursal
$user->save();
exit
```

## Paso 9: Crear Sucursal Inicial

En **Shell**:
```bash
php artisan tinker
```

```php
App\Models\Branch::create([
    'name' => 'Sucursal Central',
    'code' => 'CENTRAL',
    'address' => 'Dirección de tu sucursal',
    'phone' => '1234567890',
    'email' => 'central@pawnshop.com',
    'is_active' => true,
]);
exit
```

## Paso 10: Acceder a la Aplicación

1. Tu aplicación estará en: `https://tu-app.onrender.com`
2. Ir a: `https://tu-app.onrender.com/admin`
3. Iniciar sesión con las credenciales del usuario Admin
4. ¡Listo!

## Configuración de Cron Jobs (Opcional)

Para el comando de préstamos vencidos:

1. En Render Dashboard → **New +** → **Cron Job**
2. Configurar:
   - **Name**: `update-overdue-loans`
   - **Build Command**: `composer install --no-dev`
   - **Command**: `php artisan loans:update-overdue`
   - **Schedule**: `0 0 * * *` (diariamente a medianoche)
   - **Environment Variables**: Usar las mismas del web service

## Actualizar la Aplicación

Cada vez que hagas push a tu repositorio, Render:
1. Detectará los cambios automáticamente
2. Ejecutará el build script
3. Desplegará la nueva versión
4. Sin downtime (zero-downtime deployment)

## Costos

### Plan Gratuito (Free)
- **Web Service**: Gratis (con limitaciones)
  - Se apaga después de 15 minutos de inactividad
  - Se reactiva al recibir una request (30-60 segundos delay)
  - 750 horas/mes

- **MySQL Database**: $0
  - 1GB storage
  - Expires after 90 days (necesita recrearse)

### Plan Recomendado para Producción
- **Web Service Starter**: $7/mes
  - Siempre activo
  - Sin downtime
  - 512MB RAM

- **MySQL Starter**: $7/mes
  - 1GB storage
  - No expira
  - Backups incluidos

**Total: $14/mes para producción real**

## Troubleshooting

### Build Falla
1. Revisar logs en **Logs** tab
2. Verificar que `build.sh` tiene permisos de ejecución
3. Verificar que todas las dependencias están en `composer.json`

### "500 Internal Server Error"
1. Ir a **Environment** → Cambiar `APP_DEBUG=true` temporalmente
2. Refrescar la app para ver el error detallado
3. Revisar logs en **Logs** tab
4. Después de arreglar, volver a `APP_DEBUG=false`

### Base de Datos No Conecta
1. Verificar que las variables `DB_*` están correctas
2. Verificar que la base de datos está en **Running** status
3. En **Shell**, probar:
```bash
php artisan migrate:status
```

### Assets No Cargan (CSS/JS)
1. Verificar que `npm run build` corrió exitosamente en los logs
2. Verificar `APP_URL` en variables de entorno
3. Limpiar cache:
```bash
php artisan config:clear
php artisan cache:clear
```

## Seguridad

1. **Siempre** usar `APP_DEBUG=false` en producción
2. Usar contraseñas fuertes para usuarios
3. Cambiar `APP_KEY` regularmente
4. Mantener dependencias actualizadas
5. Habilitar backups automáticos de base de datos (en plan pago)

## Backups

### Manual (Free Plan):
1. Ir a tu base de datos en Render
2. Click en **Backups**
3. Click en **Create Backup**

### Automático (Starter Plan):
- Backups diarios automáticos
- Retención de 7 días

## Siguiente Paso: Dominio Personalizado

1. Comprar dominio (ej: mipawnshop.com)
2. En Render → Tu web service → **Settings** → **Custom Domain**
3. Agregar tu dominio
4. Configurar DNS según instrucciones de Render
5. SSL se configura automáticamente

## Soporte

- Documentación: https://render.com/docs
- Community: https://community.render.com
- Status: https://status.render.com
