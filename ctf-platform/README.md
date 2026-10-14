# CTF IPSS — Hacking Game · Guía de instalación y configuración

Plataforma educativa Capture The Flag para el Instituto Profesional San Sebastián.
Stack: PHP nativo + MySQL/MariaDB + HTML5 + CSS3 (Custom Properties, sin frameworks) + JavaScript Vanilla.
Apta para hosting compartido cPanel/Apache (sin Docker, sin `exec`/`shell_exec`).

---

## 1. Credenciales iniciales (demo — cambiar en producción)

| Rol         | Email               | Contraseña | Destino tras login   |
|-------------|---------------------|------------|----------------------|
| Admin       | `admin@ipss.cl`     | `admin123` | `admin/dashboard.php`|
| Docente     | `docente@ipss.cl`   | `admin123` | `admin/dashboard.php`|
| Estudiante  | `estudiante@ipss.cl`| `admin123` | `dashboard.php`      |

> Los hashes están en `schema.sql` (tabla `usuarios`, bcrypt). Para cambiar una
> contraseña: `UPDATE usuarios SET password_hash='<hash>' WHERE email='...';`
> generando el hash con `password_hash('nueva', PASSWORD_DEFAULT)`.

---

## 2. Instalación local con XAMPP (Windows)

### 2.1. Requisitos
- XAMPP con PHP ≥ 8.1, MySQL/MariaDB y Apache (probado con PHP 8.2.12).
- Extensión PDO MySQL activa (viene por defecto en XAMPP).

### 2.2. Arrancar servicios
1. Abre **XAMPP Control Panel**.
2. Inicia **Apache** (puerto 80) y **MySQL** (puerto 3306).

### 2.3. Desplegar el código
Opción A — dentro de XAMPP (ruta con subcarpeta, solo para pruebas rápidas):
```
C:\xampp\htdocs\CLB\ctf-platform\
```
Opción B — **recomendada**: como raíz de un host, porque el código usa rutas
absolutas (`/assets/...`, `/login.php`, `/api/...`), igual que en el hosting
real. Con el servidor embebido de PHP:
```bat
C:\xampp\php\php.exe -S 127.0.0.1:8080 -t C:\xampp\htdocs\CLB\ctf-platform
```
y abre `http://127.0.0.1:8080/login.php`.

### 2.4. Crear e importar la base de datos ⚠️ (paso crítico)
El cliente `mysql.exe` de Windows usa charset **cp850**; si importas sin más,
los acentos se corrompen (`ó` → `├│`). Importa **forzando utf8mb4**:

```bat
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -uroot -e "CREATE DATABASE ctf_ipss CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe --default-character-set=utf8mb4 -uroot ctf_ipss -e "SOURCE C:/ruta/a/ctf-platform/schema.sql"
```

Verificación (el título debe mostrar `C3B3` = ó en UTF-8):
```sql
USE ctf_ipss;
SHOW TABLES;                       -- 6 tablas + vista ranking_estudiantes
SELECT COUNT(*) FROM desafios;      -- 8
SELECT COUNT(*) FROM usuarios;      -- 3
SELECT HEX(SUBSTRING(titulo,1,12)) FROM desafios WHERE id=1;  -- ...C3B3...
```

Alternativa sin consola: phpMyAdmin → crear BD `ctf_ipss` con cotejamiento
`utf8mb4_unicode_ci` → pestaña **Importar** → `schema.sql`.

### 2.5. Configurar la conexión
`includes/config.php` lee variables de entorno con valores por defecto
válidos para XAMPP local:

| Variable  | Defecto       |
|-----------|---------------|
| `DB_HOST` | `localhost`   |
| `DB_NAME` | `ctf_ipss`    |
| `DB_USER` | `root`        |
| `DB_PASS` | *(vacía)*     |
| `APP_URL` | `http://localhost/ctf-platform` |

En local no hay que tocar nada. En hosting, define estas variables o edita
los `define()` (ver §3).

---

## 3. Despliegue en hosting compartido cPanel

1. **Subir archivos**: todo el contenido de `ctf-platform/` a `public_html/`
   (raíz, para que `/assets`, `/api`, `/login.php` resuelvan).
2. **Crear BD**: cPanel → *Bases de datos MySQL* → crea BD (ej.
   `usuario_ctf`), usuario y asígnalo con todos los privilegios.
3. **Importar**: phpMyAdmin del cPanel → selecciona la BD →
   **Importar** → `schema.sql` (verifica cotejamiento `utf8mb4_unicode_ci`).
4. **Credenciales**: ajusta `DB_HOST` (normalmente `localhost`),
   `DB_NAME`, `DB_USER`, `DB_PASS` en el entorno o en
   `includes/config.php:23-27`.
5. **HTTPS**: el código activa cookies `Secure` solo si detecta HTTPS;
   contrata/activa el certificado (AutoSSL/LetsEncrypt) y fuerza HTTPS.
6. **Seguridad inmediata**:
   - Cambia las 3 contraseñas demo.
   - El `.htaccess` incluido ya bloquea acceso web a `config.php` y
     `schema.sql`, desactiva listado de directorios y añade cabeceras
     (`nosniff`, `SAMEORIGIN`, `Referrer-Policy`).

---

## 4. Estructura del proyecto

```
ctf-platform/
├── index.php                  # Router: redirige según rol
├── login.php / logout.php / dashboard.php   # Auth + dashboard estudiante
├── admin/dashboard.php        # Panel docente/admin
├── includes/config.php        # Conexión PDO, auth, flags, ranking
├── api/                       # JSON: submit_flag, get_hint, dashboard_data,
│                              #        challenge_detail, download_file, ranking
├── assets/css/styles.css      # Tema claro/oscuro, responsive
├── assets/js/                 # login.js, dashboard.js, admin.js
├── challenges/files/          # Evidencias: vulnerable_code.php, nginx.conf,
│                              # hashes.txt, access.log, campus_ipss.svg
└── schema.sql                 # Tablas, 8 desafíos, 16 pistas, 3 usuarios demo
```

Los 8 desafíos (2 Análisis Web simulado · 2 Análisis de Vulnerabilidades ·
2 Criptografía · 2 Forense) se validan en servidor con
`hash_equals(hash('sha256', ...))`, límite de 10 intentos/hora y penalización
por pista. Las "vulnerabilidades" son **simulaciones o archivos ficticios**;
nunca se expone el servidor real.

---

## 5. Prueba funcional mínima

1. Entra como **estudiante** → debes ver 8 tarjetas, score 0 y ranking.
2. Reto 1: simulador con `user_id=2`, cambia a `1` → obtén la flag,
   envíala con evidencia (≥ 20 caracteres) → +100 pts y tarjeta en verde.
3. Reto 2: usuario `' OR '1'='1` → bypass simulado.
4. Pide una **pista** → verifica la penalización de puntos.
5. Reenvía la misma flag → debe rechazarse (sin duplicar puntos).
6. Entra como **docente** → stats, intentos recientes, evidencias y ranking.
7. Descarga una evidencia (`api/download_file.php?id=3`).

## 6. Problemas conocidos y solución

| Síntoma | Causa | Solución |
|---------|-------|----------|
| Acentos como `├│` o `Ã³` | Importación con charset cp850/latin1 | Reimportar con `--default-character-set=utf8mb4` (§2.4) |
| `Warning: ini_set(): Session ini settings...` | `session_start()` antes de `ini_set()` | Ya corregido en `config.php` (ini primero, sesión después) |
| Login demo no entra | Hash no corresponde a la contraseña | Regenerar hash bcrypt y actualizar `usuarios` + `schema.sql` |
| CSS/JS no cargan (404) | App desplegada en subcarpeta | Desplegar en raíz del host o usar host virtual con docroot en la plataforma |
| `Headers already sent` | Warnings previos a `header()` | Revisar `error_log`; en prod. `display_errors=Off` |
