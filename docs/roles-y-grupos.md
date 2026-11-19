# Roles y grupos de seguridad

SuiteCRM administra los usuarios, sus perfiles y el acceso por finca. El rol define las operaciones permitidas. El grupo vincula un perfil y sus usuarios al registro de una finca.

1. Instala el paquete AgroSync y ejecuta **Administración → Reparar → Reparación rápida y reconstrucción**. Si se ofrece SQL pendiente, ejecútalo desde la pantalla de reparación antes de configurar permisos.
2. Abre **Administración → Gestión de roles** y crea o actualiza los tres roles con estos nombres exactos. Aplica a cada rol los valores de la [matriz de permisos](permisos-agrosync.csv) para los módulos disponibles.

   | Rol | Permisos previstos |
   |---|---|
   | AgroSync Administrador de finca | Consultar y editar registros de su finca según la matriz. La creación y sincronización dependen de las operaciones que autorice Java. |
   | AgroSync Registrador | Consultar catálogos autorizados y registrar las operaciones habilitadas por Java. |
   | AgroSync Consulta | Consultar registros autorizados. |

3. En **Administración → Grupos de seguridad**, crea un grupo por combinación de finca y perfil. En el detalle del grupo, añade el rol correspondiente en **Roles**. Vincula el grupo con el registro de la finca mediante **Grupos de seguridad** en el detalle de Fincas o mediante la asignación masiva desde el listado.
4. En el subpanel **Usuarios** del grupo, añade las cuentas activas autorizadas. Conserva una sola combinación efectiva de perfil por usuario y finca. Si la misma persona pertenece a dos grupos de una finca con perfiles distintos, la API de AgroSync reporta un conflicto de configuración. Revisa también las asignaciones directas de roles y las opciones globales de Security Groups antes de habilitar accesos.
5. Comprueba desde React que cada persona ve su finca y que un perfil Consulta recibe una denegación al intentar registrar. Para verificar la separación de fincas, usa el identificador de otra finca con una sesión que carezca de su grupo y comprueba el rechazo del backend.

Cuando varias operaciones comparten el permiso **Editar** de SuiteCRM, Java y las rutas de AgroSync aplican las comprobaciones de escritura por finca y perfil. Revisa por separado los permisos en la interfaz nativa si los usuarios también trabajarán directamente en SuiteCRM.
