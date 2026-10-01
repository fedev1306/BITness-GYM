# BITness GYM

Proyecto de graduación 2024 para la gestión de una cadena de gimnasios.

## Demo segura / despliegue

La aplicación usa PHP y MySQL. Para ejecutarla en un entorno público:

1. Crea una base de datos MySQL exclusiva para la demo e importa `Database/BITnessGYM.sql`.
2. Configura `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD` como variables de entorno. Usa `.env.example` únicamente como referencia y nunca confirmes secretos al repositorio.
3. Regenera cualquier credencial que haya estado previamente publicada en el historial del repositorio.
4. Usa exclusivamente credenciales sandbox/test para pagos y datos ficticios para los usuarios de demostración.
5. Despliega mediante el `Dockerfile` incluido. El servidor debe exponerse únicamente mediante HTTPS.

### Desarrollo local

Puedes exportar las variables de entorno antes de iniciar PHP. Ejemplo:

```bash
export DB_HOST=localhost
export DB_PORT=3306
export DB_NAME=BITness_GYM
export DB_USER=bitness
export DB_PASSWORD='change-me'
php -S localhost:8080 -t Code
```

### Seguridad

- Las nuevas contraseñas se almacenan con `password_hash()`.
- Las cuentas históricas que aún tengan SHA-256 se migran automáticamente al iniciar sesión correctamente.
- Las sesiones regeneran su identificador al autenticar.
- Las cookies de sesión usan `HttpOnly` y `SameSite=Lax`, y `Secure` cuando la petición llega por HTTPS.
- Los errores internos de conexión no se muestran al usuario.
- El registro usa una transacción para mantener consistentes `USUARIO`, `PERFIL` y `CLIENTE`.

> Antes de publicar una demo, cambia todas las credenciales que hayan aparecido alguna vez en el repositorio y no uses datos personales reales.

## Funcionalidades

Gestión de usuarios y roles, suscripciones, productos, sucursales, actividades, facturación y sesiones. Las integraciones de pagos y correo deben configurarse con secretos externos al repositorio.
