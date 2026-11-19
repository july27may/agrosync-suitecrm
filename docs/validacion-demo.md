# Verificación de AgroSync desde cero

Se ejecutó el recorrido con SuiteCRM 8.10.2 recién instalado, MariaDB 10.11 con un volumen nuevo, un contenedor Java en la misma red y una compilación de React con Node.js 22.19.0. Se usaron los puertos locales 18182 para SuiteCRM, 18181 para Java y 15173 para React. No se importó ningún respaldo. La cuenta y los clientes OAuth de la prueba usaron credenciales generadas para ese entorno.

| Paso ejecutado | Resultado observado |
|---|---|
| SuiteCRM y paquete AgroSync | El instalador oficial por consola preparó la instancia. El gestor nativo instaló el ZIP, se verificaron las tablas de Fincas, Lotes, Cosechas, Animales y Eventos sanitarios, y el script añadió las extensiones V8. |
| Java y React | Java devolvió `UP` en `/actuator/health`. Se compiló React con la URL aislada de Java y se abrió en Chromium. |
| Login delegado | Desde React se ingresó en SuiteCRM y se aprobó el cliente. Java respondió HTTP 200 en `/api/v1/auth/me` y devolvió el perfil `ADMINISTRADOR_FINCA` para la finca de demostración. |
| Catálogos | React mostró `Finca Demo Horizonte`, `Lote Demo Norte` y `Recolector Demo 01` desde SuiteCRM. |
| Café sin conexión | Se preparó el dispositivo, se desactivó la red en el contexto del navegador y se guardó una cosecha. Al cerrar y reabrir la pestaña en el mismo contexto, seguía pendiente. |
| Café al reconectar | Apareció la recolección sincronizada. En la base aislada se comprobó una cosecha de 12,500 kg a 2.000 COP/kg vinculada a la finca, el lote y el recolector ficticios, con subtotal de 25.000 COP. |
| Ganadería sin conexión y envío | Se guardaron un animal y una revisión desde el formulario sin red. Al reconectar, la revisión apareció como confirmada en SuiteCRM. En la base aislada se verificó el vínculo entre `Revisión Demo 01` y `Animal Demo 01`. |

Para acelerar esta prueba se reutilizaron imágenes Docker y dependencias de Node ya disponibles. La carga del paquete y la creación de los catálogos de prueba se hicieron por consola con los mecanismos nativos de SuiteCRM. Quedaron pendientes una ejecución con descarga de todas las dependencias en un equipo nuevo, la carga del ZIP desde la pantalla del Cargador, la verificación de la matriz ACL con una cuenta regular y las pruebas de vencimiento, reintentos e idempotencia en esta demostración. El login se comprobó con la cuenta administradora temporal asignada al grupo, por lo que su resultado no valida el alcance de una cuenta regular.
