# Validación de una instalación aislada

La prueba usó el ZIP oficial limpio de SuiteCRM 8.10.2, una base vacía en MariaDB 10.11.18 y PHP 8.3.36. Se reutilizó una imagen previamente construida con el Dockerfile de la instalación actual y se comprobó que el Dockerfile y los archivos de configuración son iguales a los del repositorio. El proyecto temporal usó el puerto local 18082 y una red, un volumen y un directorio separados. No se importó el respaldo de la instalación actual.

| Comprobación | Resultado |
|---|---|
| Configuración Docker y arranque de SuiteCRM | Compose validó la configuración. MariaDB quedó saludable y el instalador oficial por consola guardó la versión 8.10.2. |
| ZIP y reparación | El gestor nativo de paquetes aceptó el manifiesto, preparó e instaló el ZIP. Después de la reparación se verificaron los once módulos y sus tablas. Se usaron las funciones del Cargador desde consola. |
| Alcance del ZIP | El ZIP no contiene las rutas V8. Después de instalarlo y reparar, el archivo que las registra seguía ausente. La comparación con el ZIP limpio tampoco encontró modificaciones en los PHP distribuidos del núcleo V8. |
| Personalizaciones separadas | El script instaló los cinco PHP de la API y confirmó una segunda ejecución sin cambios. También instaló el paquete editable del Constructor. PHP validó los cinco archivos de API. |
| OAuth y API técnica | Se generaron claves nuevas y se obtuvo un token temporal. Los endpoints de metadatos y listado de `ags_Fincas` devolvieron HTTP 200. Las rutas personalizadas de acceso, cosechas y ganadería devolvieron HTTP 403 con ese token técnico. |
| Roles y fincas | Con objetos nativos de SuiteCRM se crearon los tres roles y tres grupos de prueba. La función de permisos resolvió la finca con un perfil y devolvió un conflicto al añadir un segundo perfil para la misma finca. |
| Java | Un contenedor Java independiente arrancó en la misma red con la URL interna `http://app:80`. Su endpoint `/actuator/health` devolvió `UP`. |

Quedaron pendientes la instalación del ZIP desde la pantalla del Cargador, la comprobación visual del Constructor, la verificación individual de todas las casillas de la matriz ACL y el flujo de login delegado completo desde el navegador a Java. Se usaron cuentas y datos de prueba únicamente en el proyecto temporal.

## Restauración de la base

Se comprobó el SHA-256 y la integridad gzip del respaldo privado. La importación se repitió con el servicio MariaDB de este Compose, un proyecto separado y su propio volumen. Se recuperaron 255 tablas y se comprobaron los módulos con registros y los tres roles de AgroSync. La prueba de restauración abarcó la base. Los archivos y las rutas V8 se comprobaron en la instalación limpia descrita arriba.
