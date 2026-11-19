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

3. Abre `http://127.0.0.1:8080/` o el puerto elegido. Instala SuiteCRM desde el navegador. Usa `db` como host de MariaDB, `3306` como puerto, y `suitecrm` como nombre de base y usuario. Introduce el valor local de `DB_PASSWORD`. Crea tu cuenta administradora en el formulario.
4. En **Administración → Cargador de módulos**, sube el ZIP de AgroSync sin descomprimir y pulsa **Instalar**. Ejecuta **Reparación rápida y reconstrucción**, y comprueba los módulos de AgroSync. Los archivos compilados de Studio y las relaciones se generan desde el paquete y la reconstrucción.
5. Añade las extensiones de API con el usuario que tenga permisos de escritura en el directorio de SuiteCRM. Si necesitas editar el paquete en el Constructor, instala también sus fuentes por separado.

   ```bash
   ./scripts/instalar-personalizaciones.sh ./suitecrm api
   ./scripts/instalar-personalizaciones.sh ./suitecrm constructor
   ```

   El instalador puede ejecutarse varias veces. Si encuentra archivos distintos, se detiene para conservar la personalización existente. Si la otra instalación ya usa rutas V8 personalizadas, integra en su `Config/routes.php` las cuatro instrucciones `require` de la [plantilla del repositorio](suitecrm-custom/custom/application/Ext/Api/V8/Config/routes.php) y conserva el resto de su archivo. Revisa los archivos PHP de AgroSync antes de copiarlos a las rutas que indica la plantilla.

6. En Administración, configura los tres roles según la [matriz](docs/permisos-agrosync.csv). Crea grupos por finca y perfil, vincúlalos al registro de Finca, asigna el rol a cada grupo y agrega a los usuarios activos correspondientes. AgroSync permite un solo perfil por persona y finca. Los grupos, personas, vínculos y registros se configuran en la nueva instancia o se recuperan de una copia privada.
7. En **Administración → OAuth2**, crea un cliente técnico `client_credentials` para la integración y un cliente confidencial `authorization_code` para el login de usuarios. Registra la URL de retorno del backend, por defecto `http://127.0.0.1:8081/api/v1/auth/callback`. Inyecta los valores en el `.env` privado del backend. La API de permisos personales exige el token delegado.
8. Verifica el login delegado, el acceso a una finca autorizada y el rechazo a una finca fuera de alcance. Si necesitas el scheduler y el worker en esta instancia, levántalos con `docker compose --profile jobs up -d`.

El repositorio excluye el núcleo de SuiteCRM, archivos de configuración de la instancia, claves OAuth, tokens, datos y respaldos. Si haces una instalación nueva, sigue la [guía oficial de SuiteCRM](https://docs.suitecrm.com/8.x/admin/installation-guide/downloading-installing/).
