# AgroSync desde cero

Necesitas Git, Docker con Compose v2, Node.js 22.12 o posterior y un navegador. Los tres componentes se ejecutan en el mismo equipo. Usa `127.0.0.1` en el navegador para mantener el mismo origen durante el login y las pruebas offline.

```bash
git clone https://github.com/july27may/agrosync-suitecrm.git
git clone https://github.com/july27may/agrosync-backend.git
git clone https://github.com/july27may/agrosync-frontend.git
```

## 1. SuiteCRM y AgroSync

Desde `agrosync-suitecrm`, sigue el [README](../README.md#reinstalar-en-un-entorno-nuevo). Descarga SuiteCRM 8.10.2, crea el `.env` privado y arranca `db` y `app`. Para una demostración paralela usa otro directorio, otro nombre de proyecto Compose y puertos libres.

Instala el ZIP de AgroSync, ejecuta la reparación, **instala las extensiones V8 por separado** y genera el par de claves OAuth. Configura los roles según el [procedimiento de roles y grupos](roles-y-grupos.md).

## 2. Datos ficticios de demostración

Crea estos registros desde la interfaz de la instancia de demostración. Si ya existen, reutilízalos. Esta guía no requiere una copia de una finca real. Puedes dejar en blanco correo, teléfono, documento y dirección.

| Módulo | Valores de ejemplo |
|---|---|
| Fincas | `Finca Demo Horizonte`, código `DEMO-F01`. |
| Lotes | `Lote Demo Norte`, código `DEMO-L01`, vinculado a `Finca Demo Horizonte` en el campo Finca. |
| Contactos | Nombre `Recolector`, apellido `Demo 01`. |
| Grupos de seguridad | `AgroSync Demo Horizonte`, con el rol `AgroSync Administrador de finca`. Vincula el grupo a la finca, al lote y al contacto. |

Agrega al grupo una cuenta activa de la **instancia de demostración**. Si creas una cuenta dedicada, usa un identificador ficticio como `operador_demo` y define su contraseña al crearla. Asigna solo un perfil AgroSync a esa cuenta para la finca. Para probar el alcance del rol sin privilegios globales, usa una cuenta de tipo regular. La administración inicial de roles y OAuth se hace con la cuenta administradora de esa instancia.

En SuiteCRM crea un cliente técnico `client_credentials` con acceso a los catálogos de prueba y un cliente confidencial `authorization_code` para el login. Define sus secretos al crearlos y registra como redirección del segundo `http://127.0.0.1:8081/api/v1/auth/callback` si usas el puerto predeterminado de Java.

## 3. Backend Java

Desde `agrosync-backend`, crea el archivo local `.env` a partir de `.env.example` y limita sus permisos.

```bash
if [ ! -e .env ]; then
  (umask 077; cp .env.example .env)
fi
chmod 600 .env
```

Completa en ese archivo las credenciales de ambos clientes. Para los puertos predeterminados, usa estos valores. Si cambias los puertos, actualiza también la redirección de OAuth y el origen CORS.

| Variable en Java | Valor |
|---|---|
| `SUITECRM_CLIENT_ID`, `SUITECRM_CLIENT_SECRET` | Credenciales del cliente técnico de esa instancia. |
| `AGROSYNC_OAUTH_CLIENT_ID`, `AGROSYNC_OAUTH_CLIENT_SECRET` | Credenciales del cliente delegado de esa instancia. |
| `SUITECRM_DOCKER_NETWORK` | Red `<COMPOSE_PROJECT_NAME>_default` del proyecto SuiteCRM. |
| `SUITECRM_DOCKER_BASE_URL` | `http://app:80` dentro de la red compartida. |
| `AGROSYNC_CRM_PUBLIC_URL` | `http://127.0.0.1:8080`, URL de SuiteCRM para el navegador. |
| `SERVER_PORT` | `8081`. |
| `AGROSYNC_OAUTH_CALLBACK` | `http://127.0.0.1:8081/api/v1/auth/callback`. Debe coincidir con la URL registrada. |
| `AGROSYNC_FRONTEND_ORIGIN` | `http://127.0.0.1:5173`. |

```bash
docker compose --env-file .env config --quiet &&
  docker compose --env-file .env up -d --build backend
curl -fsS http://127.0.0.1:8081/actuator/health
```

Si el contenedor todavía está iniciando, vuelve a consultar Health. Debe mostrar `UP`.

## 4. Frontend y recorrido de demostración

Desde `agrosync-frontend`, instala las dependencias bloqueadas por el proyecto y compila con la URL pública de Java. Si cambias la URL, vuelve a compilar. El servidor de previsualización del proyecto usa el puerto 5173.

```bash
npm ci &&
  VITE_API_BASE_URL=http://127.0.0.1:8081 npm run build &&
  npm run start
```

1. Abre `http://127.0.0.1:5173/` en un perfil de navegador de prueba. Pulsa **Ingresar con SuiteCRM** y autoriza el cliente en el CRM. Comprueba que aparece **Finca Demo Horizonte**.
2. En **Café**, espera a que la preparación automática muestre **Listo para trabajar sin conexión**. Desconecta la red y selecciona **Finca Demo Horizonte**, **Lote Demo Norte** y **Recolector Demo 01**. Usa la fecha local de hoy, 12,5 kg, una tarifa de 2.000 COP/kg y un estado de café disponible.
3. Guarda y vuelve a abrir la página sin conexión en el mismo perfil. Busca la recolección pendiente. Reconecta con AgroSync abierto y revisa el acuse automático y la consulta semanal de esa finca. Con solo esa recolección, el subtotal esperado es 25.000 COP.
4. Abre **Ganadería** con conexión y espera que cargue la finca antes de trabajar offline. Registra `DEMO-A01` como `Animal Demo 01`, con sexo hembra y estado activo. Añade un evento de revisión con fecha de hoy. Al reconectar, espera el envío automático y comprueba que el evento quedó asociado al animal.

Revisa los [resultados de la demostración aislada](validacion-demo.md) para distinguir las comprobaciones ejecutadas de los casos pendientes.
