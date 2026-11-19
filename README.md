# AgroSync para SuiteCRM 8.10.2

Este repositorio contiene el ZIP de instalación de los 11 módulos AgroSync, el paquete editable del Constructor de módulos, las extensiones de API usadas por el backend y la configuración Docker de desarrollo. SuiteCRM se descarga por separado.

**El ZIP instala los módulos. Instala las extensiones de API V8 por separado** para habilitar los permisos, las cosechas y la ganadería que usa Java.

| Ruta | Uso |
|---|---|
| [AgroSync-SuiteCRM-8.10.2.zip](AgroSync-SuiteCRM-8.10.2.zip) | Instalar los módulos desde el Cargador de módulos. |
| [module-builder/AgroSync](module-builder/AgroSync) | Editar o exportar las definiciones desde el Constructor de módulos. |
| [suitecrm-custom](suitecrm-custom) | Instalar la API de permisos, cosechas, consulta semanal y ganadería. |
| [compose.yaml](compose.yaml), [Dockerfile](Dockerfile) y [docker/](docker/) | Ejecutar SuiteCRM con Apache, PHP 8.3 y MariaDB 10.11. |
| [docs/permisos-agrosync.csv](docs/permisos-agrosync.csv) | Matriz de referencia para configurar los roles. |
| [docs/restauracion.md](docs/restauracion.md) | Restaurar una copia privada de la base en una instancia separada. |
| [docs/validacion.md](docs/validacion.md) | Resultados de la instalación aislada. |

Para configurar SuiteCRM, Java y React en conjunto, sigue la [guía de AgroSync desde cero](docs/desde-cero.md).

## Requisitos y red

- Docker Engine con el complemento Compose v2, Bash y una herramienta para descomprimir el ZIP oficial de SuiteCRM 8.10.2.
- El Dockerfile instala Apache y PHP 8.3 con las extensiones que usa SuiteCRM. MariaDB usa la rama 10.11. La base persiste en el volumen `suitecrm_db_data` del proyecto. Los archivos de SuiteCRM, sus claves y sus personalizaciones permanecen en el directorio configurado con `SUITECRM_DIR`.
- Compose crea la red `<COMPOSE_PROJECT_NAME>_default`. SuiteCRM responde como `http://app:80` en esa red y en `127.0.0.1:8080` desde el equipo anfitrión por defecto. MariaDB responde como `db:3306` dentro de la red. Solo Apache publica un puerto en el anfitrión.

## Reinstalar en un entorno nuevo

### 1. Arrancar SuiteCRM

Descarga el [ZIP oficial de SuiteCRM 8.10.2](https://suitecrm.com/wpfd_file/suitecrm-8-10-2/) y extráelo en `./suitecrm`. En ese directorio deben quedar `public/`, `bin/console` y `VERSION`. Ejecuta los bloques de esta sección desde la raíz de `agrosync-suitecrm`.

```bash
if [ ! -e .env ]; then
  (umask 077; cp .env.example .env)
fi
chmod 600 .env
```

Edita `.env` y completa `DB_PASSWORD` y `DB_ROOT_PASSWORD`. Para mantener una instancia existente en paralelo, asigna otro `COMPOSE_PROJECT_NAME`, otro `SUITECRM_HOST_PORT` y un `SUITECRM_DIR` separado. `SUITECRM_IMAGE_NAME` permite usar una etiqueta distinta para la imagen.

```bash
docker compose --env-file .env config --quiet &&
  docker compose --env-file .env up -d --build db app &&
  docker compose --env-file .env ps
```

Abre `http://127.0.0.1:8080/` o el puerto de `SUITECRM_HOST_PORT`. Completa el asistente con el host `db`, puerto `3306`, base y usuario `suitecrm`, y la contraseña `DB_PASSWORD`. Crea la cuenta administradora de esa instancia. Si Apache necesita permisos en una extracción nueva en Linux, ejecuta en el proyecto nuevo `docker compose --env-file .env exec app chown -R www-data:www-data /var/www/html`.

### 2. Instalar el ZIP de AgroSync

En **Administración → Cargador de módulos**, sube `AgroSync-SuiteCRM-8.10.2.zip` sin descomprimir y selecciona **Instalar**. Ejecuta **Reparación rápida y reconstrucción**. Si la pantalla ofrece SQL pendiente, aplícalo desde la opción de reparación.

### 3. Instalar las extensiones V8 por separado

El ZIP no instala los cinco archivos PHP de la API de AgroSync, incluido el que registra las rutas. El siguiente bloque resuelve el directorio de SuiteCRM desde el montaje real del servicio `app`. Requiere permisos de escritura en `public/legacy/custom`.

```bash
app_id=$(docker compose --env-file .env ps -q app) &&
  test -n "$app_id" &&
  crm_dir=$(docker inspect --format '{{range .Mounts}}{{if eq .Destination "/var/www/html"}}{{.Source}}{{end}}{{end}}' "$app_id") &&
  test -n "$crm_dir" &&
  ./scripts/instalar-personalizaciones.sh "$crm_dir" api
```

En Linux con Docker local, si Apache es propietario de `public/legacy/custom`, puedes cambiar temporalmente la propiedad de esa carpeta **en la instalación nueva** con `docker compose --env-file .env exec app chown -R "$(id -u):$(id -g)" /var/www/html/public/legacy/custom`. Tras ejecutar el instalador, devuelve la propiedad a Apache con `docker compose --env-file .env exec app chown -R www-data:www-data /var/www/html/public/legacy/custom`.

Para disponer del paquete editable en el Constructor, puedes ejecutar también `./scripts/instalar-personalizaciones.sh "$crm_dir" constructor` en la misma terminal, con los mismos permisos. El instalador admite ejecuciones repetidas y se detiene ante archivos distintos. Si ya existe un archivo compartido de rutas V8, integra las cuatro instrucciones `require` en ese archivo y conserva las rutas previas. [Plantilla de registro de rutas](suitecrm-custom/custom/application/Ext/Api/V8/Config/routes.php)

### 4. Configurar acceso por finca

Aplica el [procedimiento de roles y grupos](docs/roles-y-grupos.md) y la [matriz de permisos](docs/permisos-agrosync.csv). Si necesitas los servicios en segundo plano, arráncalos tras instalar SuiteCRM.

```bash
docker compose --env-file .env --profile jobs up -d scheduler worker
```

## Integrar el backend Java

En una instancia nueva, genera el par de firma OAuth dentro del contenedor. El comando comprueba que no haya claves previas antes de escribir. Si la instancia ya tiene ambos archivos, conserva los existentes. [Documentación de OAuth2 V8](https://docs.suitecrm.com/developer/integration-api/v8/installation/)

```bash
docker compose --env-file .env exec --user www-data app sh -ec '
  cd public/legacy/Api/V8/OAuth2
  test ! -e private.key && test ! -e public.key
  umask 077
  openssl genrsa -out private.key 2048
  openssl rsa -in private.key -pubout -out public.key
'
```

En **Administración → OAuth2 Clients and Tokens**, crea dos clientes. Para la integración técnica, crea un cliente `client_credentials` con el usuario autorizado para las lecturas de catálogos y las operaciones requeridas. Para el login personal, crea un cliente confidencial `authorization_code` con redirección exacta a `http://127.0.0.1:8081/api/v1/auth/callback` o a la URL de retorno que configure Java.

Configura las variables en el entorno de Java o en el `.env` privado del proyecto [agrosync-backend](https://github.com/july27may/agrosync-backend) si usas su Compose.

| Variable | Valor |
|---|---|
| `SERVER_PORT` | Puerto local de Java, por defecto `8081`. Si cambia, ajusta también la URL de retorno. |
| `SUITECRM_CLIENT_ID`, `SUITECRM_CLIENT_SECRET` | Credenciales del cliente técnico. |
| `AGROSYNC_OAUTH_CLIENT_ID`, `AGROSYNC_OAUTH_CLIENT_SECRET` | Credenciales del cliente de login delegado. |
| `SUITECRM_BASE_URL` | Para Java ejecutado en el anfitrión, `http://127.0.0.1:8080` o el puerto que publicaste. |
| `SUITECRM_DOCKER_NETWORK`, `SUITECRM_DOCKER_BASE_URL` | Para el Compose de Java, `suitecrm810_default` o la red del proyecto que elegiste, y `http://app:80`. |
| `AGROSYNC_CRM_PUBLIC_URL` | URL de SuiteCRM accesible desde el navegador, por defecto `http://127.0.0.1:8080`. |
| `AGROSYNC_OAUTH_CALLBACK` | URL exacta registrada en el cliente delegado. |
| `AGROSYNC_FRONTEND_ORIGIN` | Origen exacto de React, por defecto `http://127.0.0.1:5173`. |

Desde la raíz de `agrosync-backend`, con su propio `.env` ya configurado, arranca el servicio `backend`. Sustituye el puerto del comando `curl` si cambiaste `SERVER_PORT`.

```bash
docker compose --env-file .env config --quiet &&
  docker compose --env-file .env up -d --build backend
curl -fsS http://127.0.0.1:8081/actuator/health
```

Comprueba el login desde React con un usuario activo de SuiteCRM y confirma que aparecen sus fincas autorizadas. Con el perfil Consulta, verifica el rechazo de una operación de registro. La ruta de permisos delegados es `GET /Api/V8/custom/agrosync/access`.

## Resultados de la reinstalación aislada

La instalación usó SuiteCRM 8.10.2, PHP 8.3.36 y una base vacía en MariaDB 10.11.18, con red y volumen propios. Se reutilizó una imagen PHP ya construida.

| Comprobación ejecutada | Resultado observado |
|---|---|
| Instalador oficial y gestor nativo de paquetes, por consola | SuiteCRM guardó la versión 8.10.2. Tras instalar el ZIP y reparar, se verificaron los 11 módulos y sus tablas. |
| Alcance del ZIP y script de API | Tras instalar el ZIP, el registro de rutas V8 seguía ausente. El script añadió los cinco PHP, que pasaron la comprobación de sintaxis. Repetir el script no introdujo cambios. |
| Peticiones HTTP con token técnico | Metadatos y listado de `ags_Fincas` devolvieron HTTP 200. Las rutas personalizadas de acceso, cosechas y ganadería devolvieron HTTP 403. |
| Permisos con grupos de prueba | Se resolvió una finca con un perfil y se detectó el conflicto al asignar dos perfiles para esa misma finca. |
| Arranque de Java | Un contenedor independiente arrancó en la red de prueba y `/actuator/health` devolvió `UP`. |

Quedan pendientes la instalación desde la pantalla del Cargador, la comprobación visual del Constructor, la revisión de cada permiso de la matriz ACL y el login delegado completo entre navegador y Java. Consulta también los [resultados de la restauración de la base](docs/validacion.md#restauración-de-la-base).

El repositorio excluye el núcleo de SuiteCRM, archivos de configuración de la instancia, claves OAuth, tokens, datos y respaldos. [Guía oficial de instalación de SuiteCRM](https://docs.suitecrm.com/8.x/admin/installation-guide/downloading-installing/)
