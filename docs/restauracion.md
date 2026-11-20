# Recuperación de la base de datos

El respaldo SQL comprimido se conserva en un directorio privado separado del repositorio. Puede contener datos personales, hashes de contraseñas, registros de OAuth y configuración de la instancia. Trata el archivo, su acceso y cualquier copia restaurada como información privada.

## Validar una copia

En el directorio privado del respaldo, comprueba el SHA-256 y la integridad de gzip.

```bash
sha256sum -c suitecrm810-AAAAmmdd-HHMMSS.sql.gz.sha256
gzip -t suitecrm810-AAAAmmdd-HHMMSS.sql.gz
```

## Restaurar de forma controlada

1. Prepara una instancia separada de SuiteCRM 8.10.2, con otro directorio de aplicación, puerto y nombre de proyecto Compose. Comprueba que su volumen de MariaDB no sea el de la instancia activa.
2. Sigue las instrucciones del repositorio para preparar la aplicación y los módulos. Las personalizaciones V8 y los archivos de configuración, claves y adjuntos que utilice la instancia deben prepararse en ese entorno. El respaldo de MariaDB no contiene los archivos del sistema.
3. Antes de importar, detén la aplicación, el scheduler y el worker de la **instancia de restauración** y mantén activo únicamente su servicio `db`. Usa los valores locales de ese servicio para conectarte. Una importación puede reemplazar tablas de la base de destino.

   ```bash
   set -o pipefail
   backup=/ruta/privada/suitecrm810-AAAAmmdd-HHMMSS.sql.gz
   proyecto=agrosync-restauracion
   docker compose --env-file .env.restauracion -p "$proyecto" ps
   docker compose --env-file .env.restauracion -p "$proyecto" --profile jobs stop app scheduler worker
   docker compose --env-file .env.restauracion -p "$proyecto" up -d --wait db
   gzip -dc "$backup" |
     docker compose --env-file .env.restauracion -p "$proyecto" exec -T db sh -ceu '
       exec mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"
     '
   ```

   Configura en `.env.restauracion` el directorio separado de SuiteCRM, el puerto local alternativo y las contraseñas locales. El valor de `-p` fija explícitamente el proyecto de destino. Comprueba el nombre indicado por `ps` antes de importar.
4. Vuelve a iniciar la aplicación con `docker compose --env-file .env.restauracion -p "$proyecto" up -d app`. Valida los conteos de fincas, cosechas, animales y eventos en el destino. Comprueba los usuarios, grupos y roles recuperados. Verifica las rutas de la API y prepara clientes OAuth con credenciales nuevas para el entorno restaurado. Las sesiones anteriores no deben darse por válidas.


## Copia de archivos y configuración

Las copias de cierre incluyen `base.sql.gz`, `suitecrm.tar.gz`, `configuracion-local.tar.gz` y `SHA256SUMS`. Conserva todo el directorio fuera de Git con permisos privados. Comprueba la integridad con `sha256sum -c SHA256SUMS` desde ese directorio.

Para recuperar la instalación completa, extrae `suitecrm.tar.gz` en un directorio **nuevo** de SuiteCRM e importa `base.sql.gz` con el proyecto de restauración indicado arriba. El archivo de configuración conserva los valores privados de la instalación original. Revisa los destinos, las URL, el nombre de proyecto, el directorio de montaje y las credenciales antes de usar una copia. Si ya recuperaste la aplicación desde el archivo, sus módulos y extensiones restaurados forman parte de esa copia. Conserva también su par OAuth. Para aplicar una versión posterior, sigue el README y ejecuta las migraciones correspondientes.
