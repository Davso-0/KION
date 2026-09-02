<div align="center">

# KION - Sistema de Punto de Venta e Inventario Multisede

<img width="851" height="315" alt="KION Preview" src="https://github.com/user-attachments/assets/dcced36d-0a03-4866-ab3f-0c236e9bc4f0" />

**KION** es una aplicación web centralizada de punto de venta e inventario multisede diseñada para optimizar la administración operativa, el control de inventarios y el registro de ventas en establecimientos comerciales del sector veterinario y agropecuario en el estado de Colima.

---

**Universidad de Colima** | **Facultad de Ingeniería Electromecánica (FIE)**  
*Ingeniería en Software — 3°E*

</div>

---

##  Descripción del Proyecto

En el sector veterinario y agropecuario, los negocios suelen combinar la comercialización de productos con servicios de salud e insumos con requerimientos especiales (lotes, caducidades, medicamentos controlados). 

**KION** resuelve la falta de centralización y la inconsistencia en el control de stock derivado del uso de registros manuales o plataformas no interconectadas. La plataforma permite la administración unificada de múltiples sucursales manteniendo una base de datos unificada en la nube, ofreciendo sincronización automática de datos, gestión de permisos basada en roles (RBAC) y soporte multiidioma (español e inglés).

---

##  Integrantes del Equipo

* **Barba Castillo Ricardo Jafet**
* **Hernández Campos Itzel Aranzazu**
* **Ibarra Cortés Mía Mariana**
* **Juárez Lara Fernando Franco**
* **Salgado Zepeda David** *(Jefe de Proyecto)*

---

##  Objetivos

### Objetivo General
Desarrollar e implementar una aplicación web centralizada de punto de venta e inventario multisede, denominada **KION**, para optimizar la administración operativa de establecimientos comerciales del sector veterinario y agropecuario en el estado de Colima, durante el periodo comprendido entre agosto de 2026 y noviembre de 2026.

### Objetivos Específicos
* Crear interfaces claras e intuitivas para los módulos de administración, inventario y punto de venta (con soporte para dispositivos móviles y escritorio).
* Diseñar una base de datos relacional normalizada (hasta 3FN) para productos, sucursales, usuarios, inventario y ventas.
* Implementar un sistema de permisos basado en roles de usuario.
* Desarrollar el punto de venta digital con descuento de inventario automatizado tras cada transacción y generación de tickets.
* Centralizar la administración de sucursales e inventarios diferenciados por sede.
* Probar y desplegar el prototipo funcional en servidores web (IONOS).

---

##  Arquitectura del Sistema

El sistema sigue un patrón de arquitectura **Cliente-Servidor distribuido en tres capas**:

1. **Capa de Presentación (Frontend):**
   * HTML, CSS, JavaScript
2. **Capa de Lógica de Negocio (Backend):**
   * PHP *
3. **Capa de Datos (Database):**
   * MySQL / MariaDB 

---

##  Control de Acceso Basado en Roles (RBAC)

* **Administrador General:** Supervisión global de la red, gestión de sucursales y administración de usuarios/roles.
* **Gerente de Sede:** Control de inventario, catálogo de productos y reportes de la sucursal asignada.
* **Cajero:** Acceso exclusivo a la interfaz de cobro, búsqueda de productos y emisión de comprobantes de venta.

---

##  Tecnologías y Herramientas

### Frontend
* **HTML:** Estructura y organización semántica de la aplicación.
* **CSS:** Diseño responsivo, estilos visuales y adaptación de la interfaz.
* **JavaScript:** Interactividad, validaciones en el cliente y manipulación del DOM.

### Backend & Base de Datos
* **PHP :** Lógica de negocio, controladores y comunicación con el motor relacional.
* **MySQL / MariaDB:** Almacenamiento relacional, transacciones y consultas SQL optimizadas.
* **phpMyAdmin:** Administración y visualización de la base de datos local.

### Control de Versiones, Entorno y Despliegue
* **Git & GitHub:** Control de versiones distribuido y colaboración en equipo.
* **XAMPP / VS Code:** Entorno de desarrollo local (Apache, PHP, MySQL) y editor de código.
* **IONOS (.mx):** Infraestructura y hosting para pruebas y despliegue.

---

##  Estructura del Directorio

```text
proyecto-integrador-kion/
│
├── README.md           # Documentación principal del repositorio
├── docs/               # Documentación del proyecto (Anteproyecto, minutas, manuales)
├── src/                # Código fuente de la aplicación (PHP, JS, CSS, HTML)
├── tests/              # Scripts y capturas de pruebas de funcionamiento
└── .gitignore          # Archivo de exclusiones para Git
