# AgroSync para SuiteCRM 8.10.2

Este repositorio contiene el ZIP de instalación de los 11 módulos AgroSync, el paquete editable del Constructor de módulos, las extensiones de API usadas por el backend y la configuración Docker de desarrollo. SuiteCRM se descarga por separado.

| Ruta | Uso |
|---|---|
| [AgroSync-SuiteCRM-8.10.2.zip](AgroSync-SuiteCRM-8.10.2.zip) | Instalar los módulos desde el Cargador de módulos. |
| [module-builder/AgroSync](module-builder/AgroSync) | Editar o exportar las definiciones desde el Constructor de módulos. |
| [suitecrm-custom](suitecrm-custom) | Instalar la API de permisos, cosechas, consulta semanal y ganadería. |
| [compose.yaml](compose.yaml), [Dockerfile](Dockerfile) y [docker/](docker/) | Ejecutar SuiteCRM con Apache, PHP 8.3 y MariaDB 10.11. |
| [docs/permisos-agrosync.csv](docs/permisos-agrosync.csv) | Matriz de referencia para configurar los roles. |
| [docs/restauracion.md](docs/restauracion.md) | Restaurar una copia privada de la base en una instancia separada. |
| [docs/validacion.md](docs/validacion.md) | Resultados de la instalación aislada. |

## Requisitos y red

- Docker Engine con el complemento Compose v2, Bash y una herramienta para descomprimir el ZIP oficial de SuiteCRM 8.10.2.
- El Dockerfile instala Apache y PHP 8.3 con las extensiones que usa SuiteCRM. MariaDB usa la rama 10.11. La base persiste en el volumen `suitecrm_db_data` del proyecto. Los archivos de SuiteCRM, sus claves y sus personalizaciones permanecen en el directorio configurado con `SUITECRM_DIR`.
- Compose crea la red `<COMPOSE_PROJECT_NAME>_default`. SuiteCRM responde como `http://app:80` en esa red y en `127.0.0.1:8080` desde el equipo anfitrión por defecto. MariaDB responde como `db:3306` dentro de la red. Solo Apache publica un puerto en el anfitrión.

## Reinstalar en un entorno nuevo

1. Descarga el ZIP instalable oficial [SuiteCRM 8.10.2](https://suitecrm.com/wpfd_file/suitecrm-8-10-2/). Extráelo en `suitecrm/` de modo que existan `suitecrm/public/`, `suitecrm/bin/console` y `suitecrm/VERSION`.
2. Copia `.env.example` como `.env`, dale permisos `600` y completa `DB_PASSWORD` y `DB_ROOT_PASSWORD` con valores propios. Para ejecutar la nueva instancia junto a la existente, usa otro `COMPOSE_PROJECT_NAME`, otro `SUITECRM_HOST_PORT` y otro directorio de SuiteCRM. Comprueba la configuración sin volcar las variables al terminal.

   ```bash
   cp .env.example .env
   chmod 600 .env
   # Completa el archivo privado con tu editor.
   docker compose config --quiet
   docker compose up -d --build
   docker compose ps
   ```

   En Linux, si el instalador indica que el directorio recién extraído no es escribible por Apache, ejecuta `docker compose exec app chown -R www-data:www-data /var/www/html` en el **proyecto nuevo**.

3. Abre `http://127.0.0.1:8080/` o el puerto elegido. Instala SuiteCRM desde el navegador. Usa `db` como host de MariaDB, `3306` como puerto, y `suitecrm` como nombre de base y usuario. Introduce el valor local de `DB_PASSWORD`. Crea tu cuenta administradora en el formulario.
4. En **Administración → Cargador de módulos**, sube el ZIP de AgroSync sin descomprimir y pulsa **Instalar**. Ejecuta **Reparación rápida y reconstrucción**, y comprueba los módulos de AgroSync. Los archivos compilados de Studio y las relaciones se generan desde el paquete y la reconstrucción.
5. Instala las extensiones V8 aparte. El manifiesto del ZIP no instala las cuatro rutas de AgroSync ni el archivo que las registra. La carpeta `suitecrm-custom` contiene esos cinco PHP. Añade las extensiones de API con el usuario que tenga permisos de escritura en el directorio de SuiteCRM. Si necesitas editar el paquete en el Constructor, instala también sus fuentes por separado.

   ```bash
   ./scripts/instalar-personalizaciones.sh ./suitecrm api
   ./scripts/instalar-personalizaciones.sh ./suitecrm constructor
   ```

   Si Apache es propietario de `public/legacy/custom` en la instalación nueva y tu usuario no puede escribir ahí, cambia temporalmente el propietario de esa carpeta antes de ejecutar los scripts y devuelve la propiedad a Apache al terminar.

   ```bash
   docker compose exec app chown -R "$(id -u):$(id -g)" /var/www/html/public/legacy/custom
   ./scripts/instalar-personalizaciones.sh ./suitecrm api
   ./scripts/instalar-personalizaciones.sh ./suitecrm constructor  # Opcional
   docker compose exec app chown -R www-data:www-data /var/www/html/public/legacy/custom
   ```

   Usa el directorio que configuraste en `SUITECRM_DIR` si es distinto de `./suitecrm`. El instalador puede ejecutarse varias veces. Si encuentra archivos distintos, se detiene para conservar la personalización existente. Si la otra instalación ya usa rutas V8 personalizadas, integra en su `Config/routes.php` las cuatro instrucciones `require` de la [plantilla del repositorio](suitecrm-custom/custom/application/Ext/Api/V8/Config/routes.php) y conserva el resto de su archivo. Revisa los archivos PHP de AgroSync antes de copiarlos a las rutas que indica la plantilla.

6. Aplica el [procedimiento de roles y grupos](docs/roles-y-grupos.md) y su [matriz](docs/permisos-agrosync.csv).
7. Si necesitas el scheduler y el worker, actívalos después de instalar SuiteCRM con `docker compose --profile jobs up -d`.

## Integrar el backend Java

En una instancia nueva, genera el par de firma OAuth dentro del contenedor. El comando comprueba que no haya claves previas antes de escribir. Si la instancia ya tiene ambos archivos, conserva los existentes. [Documentación de OAuth2 V8](https://docs.suitecrm.com/developer/integration-api/v8/installation/)

```bash
docker compose exec --user www-data app sh -ec '
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
| `SUITECRM_CLIENT_ID`, `SUITECRM_CLIENT_SECRET` | Credenciales del cliente técnico. |
| `AGROSYNC_OAUTH_CLIENT_ID`, `AGROSYNC_OAUTH_CLIENT_SECRET` | Credenciales del cliente de login delegado. |
| `SUITECRM_BASE_URL` | Para Java ejecutado en el anfitrión, `http://127.0.0.1:8080` o el puerto que publicaste. |
| `SUITECRM_DOCKER_NETWORK`, `SUITECRM_DOCKER_BASE_URL` | Para el Compose de Java, `suitecrm810_default` o la red del proyecto que elegiste, y `http://app:80`. |
| `AGROSYNC_CRM_PUBLIC_URL` | URL de SuiteCRM accesible desde el navegador, por defecto `http://127.0.0.1:8080`. |
| `AGROSYNC_OAUTH_CALLBACK` | URL exacta registrada en el cliente delegado. |
| `AGROSYNC_FRONTEND_ORIGIN` | Origen exacto de React, por defecto `http://127.0.0.1:5173`. |

Comprueba `GET http://127.0.0.1:8081/actuator/health` tras arrancar el backend. Desde React, inicia sesión con un usuario activo de SuiteCRM y comprueba que solo aparecen sus fincas autorizadas. Con el perfil Consulta, verifica que Java rechaza el registro de una operación. La ruta `GET /Api/V8/custom/agrosync/access` resuelve los permisos del usuario delegado y rechaza el token técnico.

El repositorio excluye el núcleo de SuiteCRM, archivos de configuración de la instancia, claves OAuth, tokens, datos y respaldos. Si haces una instalación nueva, sigue la [guía oficial de SuiteCRM](https://docs.suitecrm.com/8.x/admin/installation-guide/downloading-installing/).
