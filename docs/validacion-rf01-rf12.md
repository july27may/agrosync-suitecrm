# AgroSync · Verificación RF01–RF12

Revisión del documento de Fase 4 y del código de React, Java y SuiteCRM. La prueba de integración se hizo en un proyecto Compose desechable con SuiteCRM 8.10.2 de distribución oficial, MariaDB 10.11, PHP 8.3, Java 21 y Chromium. El proyecto usó otra red, otro volumen y registros ficticios. Las pruebas de regresión con simulación se indican por separado.

| Requisito | Estado al revisar | Resultado del incremento y evidencia | Pendiente o alcance |
|---|---|---|---|
| RF01 · Fincas, lotes y códigos | Parcial. Los catálogos eran de consulta. | Alta de finca por administrador global con grupo dedicado, corrección de ficha básica y alta y edición de lote. SuiteCRM rechazó códigos duplicados en la prueba real. El reenvío devolvió el registro existente. La creación con grupo se verificó por HTTP en Java y SuiteCRM. | Alta de finca en línea. La ficha de AgroSync cubre nombre y código. Ubicación geográfica y propietario se completan en SuiteCRM. |
| RF02 · Labores, recolección y liquidación | Parcial. Café y liquidación funcionaban. Labores carecía de flujo en AgroSync. | Alta real de una labor con fecha, tipo y lote. El filtro semanal independiente por recolector mantuvo el total y el desglose de la finca en Playwright. Se conservan los controles de cantidades y tarifas. | Revisar con la finca las reglas de liquidación. |
| RF03 · Ganadería, edad e historial | Parcial. Captura y sincronización existentes. | La regresión de Ganadería pasó con referencias madre/cría y animal/evento, y hay pruebas independientes de edad. Java rechazó con HTTP 400 un nacimiento sin fecha ni madre. Para un nacimiento válido, SuiteCRM conservó la madre y el evento sanitario asociado. | La ficha general sigue admitiendo fecha o madre desconocidas. En **Registrar nacimiento**, ambos campos son obligatorios. |
| RF04 · Entradas y salidas | Pendiente. Existían módulos sin flujo AgroSync. | Insumos, movimientos, saldos, historial y compensación. Con 10 kg confirmados, dos salidas concurrentes de 7 kg devolvieron una confirmación y un HTTP 409. La compensación restauró 10 kg con el original conservado y se rechazó una segunda compensación. La cantidad cero devolvió HTTP 400. | La serialización se aplica a las escrituras realizadas desde AgroSync. |
| RF05 · Captura, consulta y corrección sin conexión | Parcial. Café y Ganadería tenían captura offline. | El inventario distinguió saldo confirmado y saldo provisional. Chromium recuperó una entrada pendiente al reabrir offline y la sincronizó al volver a conectar. También se corrigió offline una recolección confirmada de 12,5 a 13,75 kg. Se recuperó al reabrir y el nuevo peso apareció en SuiteCRM y en la consulta semanal al sincronizar. En el cierre se editaron offline un animal y un evento, se reabrió Chromium y se sincronizaron ambos con el vínculo intacto. | El alta de finca requiere conexión. Los catálogos y registros que se necesiten deben descargarse primero. |
| RF06 · Estado operativo | Parcial. Pendiente y confirmado eran visibles y los errores aparecían en avisos. | Se muestran pendiente, confirmado, conflicto y error por operación de inventario y registros. Un acuse válido cambia la operación a confirmada y conserva las incidencias previas. | Café y Ganadería muestran los envíos fallidos en Incidencias. El listado productivo de esos módulos muestra pendiente o confirmado. |
| RF07 · Confirmación en SuiteCRM 8 | Parcial. Café y Ganadería sincronizaban. | Login OAuth delegado real y confirmación central de movimientos, compensación, finca, lotes, labor, insumos y la corrección de Café en el proyecto aislado. | Despliegue de cierre verificado. La sesión personal en la instancia activa queda para el usuario. |
| RF08 · Idempotencia | Parcial. Existían pruebas de reintento en Café y Ganadería. | Los reenvíos de altas y movimientos ya verificados conservaron un único efecto. En el cierre, el reenvío de la edición A después de B devolvió el JSON original de A en animales, eventos y lotes. La lectura central siguió devolviendo B. El mismo identificador con otro contenido recibió HTTP 409. | Se guardan los acuses de ediciones confirmadas a partir de esta versión. Los acuses anteriores al despliegue no se pueden reconstruir. |
| RF09 · Modificaciones concurrentes | Pendiente en registros de datos. | Dos ediciones diferentes usaron la misma versión de un lote. La primera se confirmó y la otra recibió HTTP 409. React presentó la versión central y el borrador. El usuario guardó y sincronizó una revisión aceptada. | Las ediciones directas por otros canales de SuiteCRM no usan el bloqueo de AgroSync. |
| RF10 · Roles y fincas | Parcial. La autorización existía. | Login real con cuentas temporales regulares. Consulta pudo leer su inventario (HTTP 200) y recibió HTTP 403 al intentar escribir. Una cuenta sin finca recibió HTTP 403 tanto al leer como al escribir. Ambas recibieron HTTP 403 al intentar dar de alta una finca. | Se probaron los permisos de las funciones nuevas en AgroSync. La matriz nativa completa de SuiteCRM y la política provisional offline requieren validación operativa con la finca. |
| RF11 · Historial y exportación | Parcial. Había historial sin exportación integrada. | Descarga CSV de inventario verificada en Chromium. Café y Sanidad también se descargaron contra SuiteCRM real. Café incluyó 12,500 kg del recolector ficticio. Sanidad incluyó el evento y el animal corregidos con estado Confirmado. | Exportaciones comprobadas para la selección autorizada. |
| RF12 · Incidencias y auditoría | Parcial. Las operaciones ya tenían identificador. | **Implementado y verificado.** La bitácora central registra intentos, resultados, causas y resoluciones por operación, usuario, finca y fecha. Una cuenta Consulta accedió desde un navegador limpio y una cuenta ajena recibió HTTP 403. Se verificaron reenvíos de edición, resolución e inventario sin alterar los acuses ni repetir efectos. | Los eventos centrales comienzan con este despliegue. La consulta central requiere conexión. |

## Datos observados en el entorno de prueba

| Caso | Resultado |
|---|---|
| Entrada de 10 kg registrada offline | Confirmación en SuiteCRM después de reabrir Chromium y volver a conectar. Antes del envío, el saldo confirmado era 0 kg y el provisional 10 kg. |
| Dos salidas de 7 kg sobre los mismos 10 kg | HTTP 200 y HTTP 409. Reenviar la entrada no repitió su efecto. Tras compensar la única salida confirmada, el saldo volvió a 10 kg y quedaron tres movimientos en el historial. |
| Modificación simultánea del mismo lote | Dos solicitudes con la misma versión recibieron HTTP 200 y HTTP 409. Desde React se revisaron el valor central y el borrador, y se confirmó la corrección. |
| Corrección de Café | De 12,5 a 13,75 kg para el mismo identificador central. El backend devolvió 13.750 gramos en la consulta semanal. |
| Cuentas regulares de prueba | Consulta obtuvo 200 al leer y 403 al escribir. La cuenta ajena obtuvo 403 en ambos casos. |
| Nacimiento | Un envío incompleto recibió HTTP 400. El envío válido y su revisión sanitaria se confirmaron con las referencias intactas. |
| CSV de inventario | El archivo descargado por Chromium contenía los 10 kg y la referencia de la compensación. |

## Operación y comprobaciones

- En **Registros**, el administrador global puede dar de alta una finca en línea. El alta crea un grupo de la finca para la cuenta que realizó la operación. Debe existir un solo rol **AgroSync Administrador de finca**. El administrador de finca edita únicamente las fincas asignadas. Las fichas de los registros se guardan por usuario, finca y módulo. Abre los registros que vas a usar antes de desconectarte.
- En **Inventario**, el saldo confirmado viene de SuiteCRM. El provisional suma las entradas pendientes y resta las salidas pendientes sobre esa última descarga. Un saldo provisional puede resultar negativo con datos desactualizados. SuiteCRM valida el saldo real al sincronizar y conserva el pendiente si corresponde revisarlo.
- Para corregir un movimiento confirmado, pulsa **Compensar** con perfil administrador de finca. Se encola el movimiento contrario por la misma cantidad y se mantiene la referencia al original. Si es necesario registrar un valor nuevo, hazlo como un movimiento adicional. Para conservar la garantía de concurrencia, configura la interfaz nativa y las integraciones para que no escriban ni eliminen movimientos fuera de AgroSync.
- Ante un conflicto de edición, conserva la versión central o abre **Revisar cambios**. El borrador se guarda sobre la versión central que se está revisando, con un nuevo identificador. Si el registro cambia otra vez, vuelve a requerir revisión.
- Java 21 compiló y ejecutó 21 pruebas con Maven. React compiló y ejecutó seis pruebas con Playwright. Los PHP nuevos pasaron el análisis sintáctico. El instalador de extensiones se comprobó en instalación limpia, sobre el registro de rutas anterior, en reejecución y frente a una ruta personalizada distinta.

La instalación original y sus datos no se usaron para las escrituras de prueba.


## Cierre y permisos verificados

| Perfil | Lectura de los siete tipos de registro | Operaciones |
|---|---|---|
| Administrador de finca | Autorizada en su finca. | Corrección de animales y eventos confirmada. Alta y corrección de catálogos según el módulo. Compensación de inventario. |
| Registrador | HTTP 200 en la finca asignada. | Creación de labor, recolección, evento sanitario y movimiento simple confirmada. Correcciones, alta de finca, alta de animal, alta de lotes o insumos y compensación recibieron HTTP 403. |
| Consulta | HTTP 200 en la finca asignada. | Las escrituras de la prueba recibieron HTTP 403. |
| Usuario sin grupo de la finca | HTTP 403. | HTTP 403 en todas las escrituras de la prueba. |

El alta de finca requiere un administrador global de SuiteCRM, conexión y un rol **AgroSync Administrador de finca** configurado. Ese alta inicia las asignaciones centrales de una nueva finca y crea su grupo de seguridad. Las ediciones offline aplican a registros descargados y confirmados. Abre **Registros → Animales** y **Registros → Eventos sanitarios** con conexión antes de salir a campo y espera que se carguen sus datos. Guarda las correcciones y vuelve a conectar con la aplicación abierta. El envío se intenta automáticamente y **Sincronizar ahora** sigue disponible.

Las modificaciones por Studio, la interfaz nativa, las importaciones o integraciones ajenas a AgroSync se rigen por las ACL de SuiteCRM. Esos canales no participan en el bloqueo, control de versiones ni acuses de AgroSync. Para preservar esas garantías, las correcciones y movimientos operativos deben pasar por AgroSync. Un cambio nativo previo que altere la ficha descargada puede detectarse al sincronizar, pero una escritura nativa simultánea no comparte el bloqueo.

El despliegue conserva los módulos y personalizaciones existentes. Se aplicó una migración aditiva de acuses y se sustituyó solo la versión reconocida de la extensión de Registros. Se verificó el respaldo de los archivos y la configuración con SHA-256. La restauración de prueba de la base recuperó 255 tablas, una finca, cuatro cosechas y dos animales.

En la instancia activa, Java respondió `UP` y React respondió HTTP 200. La API nativa devolvió HTTP 200 para Fincas, Cosechas, Animales, Eventos sanitarios, Insumos y Movimientos. Se verificaron La Quiebra, cuatro cosechas y dos animales. Los módulos sanitarios y de inventario respondieron con colecciones vacías. Las rutas delegadas de Inventario y de edición de Ganadería rechazaron el token técnico con HTTP 403. La comprobación funcional con una cuenta personal se ejecutó en la instancia aislada.


## Bitácora central RF12

La tabla `agrosync_bitacora` almacena únicamente metadatos y causas de una lista permitida. Cada intento tiene su propio UUID y como máximo un resultado. Se reutiliza el `operationId` de negocio en cada reintento. Las resoluciones tienen un UUID propio, conservan la operación de origen y, si se propone una corrección, permiten identificar su operación nueva. La bitácora y los acuses de edición viven en tablas distintas.

En SuiteCRM, Java y Chromium aislados se verificó lo siguiente.

- Una salida con un insumo inexistente devolvió HTTP 403. La bitácora registró `INTENTO` y `ERROR` con causa `ACCESO_DENEGADO`. El evento incluyó el usuario autenticado, la finca y una fecha registrada por el servidor.
- Un borrador con versión anterior generó `VERSION_INCOMPATIBLE`. Se eligió conservar la ficha central sin conexión. Al reconectar apareció la resolución en SuiteCRM y reenviarla produjo una sola fila.
- Una edición A reenviada después de B devolvió el acuse original de A y la ficha central siguió en B. La resolución anterior y los reenvíos no cambiaron ese acuse.
- Dos envíos con el mismo identificador de una entrada de 10 kg generaron dos intentos, un solo movimiento y un incremento de 10 kg.
- Consulta vio el conflicto y su resolución en Chromium con un perfil nuevo. Su intento de registrar una resolución recibió HTTP 403. Una cuenta ajena recibió HTTP 403 al consultar.
- Pasaron 21 pruebas de Java, 6 de Playwright y la validación de sintaxis de la extensión PHP.

Las incidencias de un envío que nunca llegó a Java quedan en el dispositivo. Si SuiteCRM no puede guardar el inicio, Java detiene ese envío antes de ejecutar el cambio. Si la conexión cae al registrar el resultado, puede quedar un intento sin resultado y el reenvío usa el mismo identificador de negocio. El historial previo al despliegue se conserva donde se creó. Los cambios directos en SuiteCRM quedan fuera de la bitácora de sincronización de AgroSync.

### Verificación del despliegue existente

- React en `127.0.0.1:5173` sirvió `index-D54VucqB.js`. Su SHA-256 coincidió con la compilación local y el archivo incluyó la vista **Bitácora central**.
- Java en `127.0.0.1:8081` devolvió `UP`. El SHA-256 del JAR dentro del contenedor activo coincidió con el del artefacto candidato `fb7c9d5d4f0350cc82c58b43becf447267b7e966f1f5e91460d6be22a84440ae`.
- Los dos PHP activos de ruta y bitácora coincidieron con el repositorio. La tabla quedó creada. Una petición técnica a la ruta activa devolvió HTTP 403, según la política de identidad delegada.
- Se conservaron una finca, cuatro cosechas y dos animales. Se respaldaron la base y las extensiones V8 antes de la migración. Las pruebas que generaron operaciones ficticias se hicieron exclusivamente en la instancia aislada.

La restauración de prueba del respaldo previo a RF12 recuperó 256 tablas, una finca, cuatro cosechas y dos animales en una MariaDB temporal sin red.


## Preparación y sincronización automática · RF05, RF07 y RF08

En la versión anterior, **Actualizar catálogos** recorría las páginas de fincas, lotes y contactos y las almacenaba en IndexedDB. **Preparar dispositivo offline** solicitaba la autorización firmada a Java. El service worker preparaba el caché de la aplicación por separado, al abrirla. La barra actual informa la preparación automática inicial y la vigencia. **Actualizar datos offline** reúne la descarga y la renovación explícita. Los datos de cada módulo y las semanas de Café se descargan al abrirlos con conexión.

La sincronización se intenta al abrir en línea con pendientes y al reconectar, mientras AgroSync permanece abierto. **Sincronizar ahora** usa el mismo coordinador. Con sesión válida, Java verifica los permisos por finca antes de cada escritura. Un error, conflicto o sesión vencida conserva la operación local y su identificador. Abrir sin red conserva la autorización hasta su vencimiento, sin renovarla.

En Chromium y SuiteCRM 8.10.2 aislados, el dispositivo mostró **Listo para trabajar sin conexión** tras el login sin pulsar un botón. Se guardó una recolección de 12,5 kg sin red, se abrió una segunda pestaña y se reconectó. Hubo un solo POST y una sola recolección en SuiteCRM. Se guardó otra de 4,25 kg sin red, se cerraron las páginas y se abrió AgroSync en línea. La segunda recolección llegó sola a SuiteCRM mediante un solo POST. La autorización original se conservó en ambos recorridos. Las seis regresiones de Playwright también pasaron, incluido un fallo de envío seguido de un reintento manual.
