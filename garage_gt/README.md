# ⚙️ Garage GT — Sistema de Gestión de Taller Mecánico

Sistema web desarrollado en **PHP nativo + MySQL** sobre stack XAMPP.

---

## 🚀 Instalación rápida

### 1. Requisitos
- XAMPP (Apache + MySQL + PHP 8.0+)
- Navegador moderno

### 2. Copiar archivos
```
Copiar la carpeta `garage_gt` dentro de:
  Windows: C:\xampp\htdocs\
  Linux:   /opt/lampp/htdocs/
```

### 3. Crear la base de datos
1. Abre **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Crea una base de datos llamada `garage_gt` (UTF8mb4)
3. Importa el archivo `garage_gt.sql`

### 4. Configurar conexión (si cambias usuario/contraseña de MySQL)
Editar `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // Tu usuario MySQL
define('DB_PASS', '');          // Tu contraseña MySQL
define('DB_NAME', 'garage_gt');
```

### 5. Acceder al sistema
```
http://localhost/garage_gt/
```

---

## 👤 Credenciales iniciales

| DNI      | Contraseña | Rol     |
|----------|------------|---------|
| 00000001 | Admin1234  | Gerente |

> ⚠️ Cambia la contraseña al primer acceso desde el módulo de empleados.

---

## 📁 Estructura del proyecto

```
garage_gt/
├── config/
│   ├── config.php          # Configuración general
│   └── db.php              # Conexión PDO a MySQL
├── controllers/
│   ├── logout.php          # Cierre de sesión
│   ├── redirigir_rol.php   # Redirige según rol
│   ├── ajax_vehiculos.php  # AJAX: vehículos por cliente
│   └── generar_pdf.php     # Genera comprobante PDF/HTML
├── includes/
│   ├── auth.php            # Autenticación y RBAC
│   ├── header.php          # Layout: sidebar + topbar
│   └── footer.php          # Scripts y cierre HTML
├── views/
│   ├── recepcionista/
│   │   ├── dashboard.php
│   │   ├── clientes.php    # RF-01 CRUD Clientes
│   │   ├── vehiculos.php   # RF-02 CRUD Vehículos
│   │   ├── turnos.php      # RF-05 Órdenes de servicio
│   │   └── facturacion.php # RF-07 Facturación PDF
│   ├── mecanico/
│   │   ├── dashboard.php
│   │   ├── ordenes_pendientes.php  # RF-06 Gestión estados
│   │   └── historial_tecnico.php  # RF-03 Historial por patente
│   └── gerente/
│       ├── dashboard.php
│       ├── empleados.php   # RF-04 Control de personal
│       ├── servicios.php   # Catálogo de servicios
│       ├── productos.php   # Inventario y stock
│       └── reportes.php    # Reportes + exportar CSV
├── assets/
│   ├── css/style.css       # Estilos principales
│   └── js/
│       ├── app.js                  # JS general
│       └── control_inactividad.js # RNF-04 Timeout sesión
├── uploads/facturas/       # PDFs generados
├── garage_gt.sql           # Script SQL completo
├── login.php               # Punto de entrada
├── index.php               # Redirige a login
├── acceso_denegado.php
└── .htaccess
```

---

## 🔐 Roles y permisos (RBAC)

| Módulo                  | Recepcionista | Mecánico | Gerente |
|-------------------------|:---:|:---:|:---:|
| Gestión de clientes     | ✅  | ❌  | ✅  |
| Gestión de vehículos    | ✅  | ❌  | ✅  |
| Crear turnos/órdenes    | ✅  | ❌  | ✅  |
| Facturación / PDF       | ✅  | ❌  | ✅  |
| Órdenes pendientes      | ❌  | ✅  | ✅  |
| Historial técnico       | ✅  | ✅  | ✅  |
| Gestión de empleados    | ❌  | ❌  | ✅  |
| Servicios y productos   | ❌  | ❌  | ✅  |
| Reportes                | ❌  | ❌  | ✅  |

---

## 📄 Requerimientos implementados

- **RF-01** ✅ CRUD Clientes
- **RF-02** ✅ Registro vehículos vinculados a cliente
- **RF-03** ✅ Historial técnico por patente
- **RF-04** ✅ Alta y gestión de mecánicos
- **RF-05** ✅ Crear órdenes de trabajo (turno + mecánico + vehículo)
- **RF-06** ✅ Transición Pendiente → Finalizado
- **RF-07** ✅ Cálculo de costo total + comprobante PDF
- **RNF-01** ✅ PHP nativo + Apache + MySQL (XAMPP)
- **RNF-02** ✅ Contraseñas con bcrypt (password_hash)
- **RNF-03** ✅ RBAC por rol de sesión
- **RNF-04** ✅ Script control_inactividad.js (30 min timeout)

---

## 📦 PDF con mPDF (opcional)

Para generar PDFs reales descargables instala mPDF:

```bash
# En la carpeta garage_gt/
composer require mpdf/mpdf
```

Si no hay Composer, el sistema genera automáticamente un HTML imprimible (Ctrl+P → Guardar como PDF).

---

## 🔒 Seguridad implementada
- PDO con prepared statements (prevención SQL Injection)
- `password_hash()` bcrypt en todas las contraseñas
- Validación de rol en cada página (RBAC)
- `session_regenerate_id()` al autenticar
- Cierre automático de sesión por inactividad (30 min)
- Headers de seguridad en `.htaccess`
- Bloqueo de ejecución PHP en `/uploads/`
