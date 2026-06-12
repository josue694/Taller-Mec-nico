🚗 Taller Mecánico GT - Sistema de Gestión de Taller Mecánico
📖 Descripción del Proyecto

Taller Mecánico GT es un sistema web desarrollado como proyecto final del curso Análisis y Diseño de Sistemas, orientado a la administración y gestión integral de un taller mecánico.

El sistema fue desarrollado utilizando PHP Nativo, MySQL, HTML5, CSS3, JavaScript, Bootstrap 5 y arquitectura basada en módulos y roles de usuario. Su propósito es optimizar los procesos administrativos y operativos de un taller mecánico, permitiendo gestionar clientes, vehículos, órdenes de trabajo, empleados, inventario, facturación y reportes desde una única plataforma.

La aplicación implementa controles de acceso por roles, seguridad en autenticación, generación de comprobantes, historial técnico de vehículos y seguimiento del estado de las órdenes de servicio.

👨‍💻 Integrantes
Josué Alejandro Velásquez Tepe - 000151607
Lindsay Mijhal Álvarez Gaitán -	000147408
Moisés Castro Tzorin - 000101010

Curso: Análisis y Diseño de Sistemas

🎯 Objetivo General

Desarrollar un sistema de información que permita automatizar y controlar los procesos de un taller mecánico, facilitando la gestión de clientes, vehículos, servicios, empleados y facturación mediante una plataforma web segura y eficiente.

🛠 Tecnologías Utilizadas
PHP Nativo
MySQL
HTML5
CSS3
JavaScript
Bootstrap 5
PDO (PHP Data Objects)
XAMPP
Apache Server
🏗 Arquitectura del Sistema

El proyecto fue desarrollado siguiendo una arquitectura modular organizada en:

garage_gt/
│
├── config/
├── controllers/
├── views/
│   ├── recepcionista/
│   ├── mecanico/
│   └── gerente/
├── assets/
│   ├── css/
│   └── js/
├── includes/
├── uploads/
└── garage_gt.sql

Esta estructura permite una mejor organización del código, mantenimiento y escalabilidad del sistema.

🔐 Roles de Usuario

El sistema implementa un esquema de control de acceso basado en roles (RBAC).

Recepcionista

Puede:

Registrar clientes.
Gestionar vehículos.
Crear órdenes de servicio.
Programar turnos.
Generar facturas.
Consultar historial de vehículos.
Mecánico

Puede:

Visualizar órdenes asignadas.
Actualizar estados de trabajo.
Consultar historial técnico.
Registrar observaciones de reparación.
Gerente

Posee acceso total al sistema:

Gestión de empleados.
Gestión de servicios.
Administración de inventario.
Generación de reportes.
Control de usuarios.
Supervisión de órdenes de trabajo.
⚙ Funcionalidades Implementadas
👥 Gestión de Clientes

Permite:

Registrar clientes.
Editar información.
Eliminar registros.
Buscar clientes existentes.

Información almacenada:

DPI/DNI
Nombre
Dirección
Teléfono
Correo electrónico
🚘 Gestión de Vehículos

Cada vehículo queda asociado a un cliente.

Datos registrados:

Placa/Patente
Marca
Modelo
Año
Color
Motor
📅 Gestión de Turnos

El sistema permite:

Programar citas.
Asignar mecánicos.
Relacionar vehículos con servicios.
Controlar estados de atención.

Estados disponibles:

Pendiente
En Proceso
Finalizado
Cancelado
🔧 Órdenes de Trabajo

Las órdenes permiten:

Registrar trabajos realizados.
Controlar costos.
Dar seguimiento a reparaciones.
Consultar historial de servicios realizados.
📚 Historial Técnico

Se implementó un historial por vehículo donde se almacenan:

Servicios realizados.
Reparaciones anteriores.
Fechas de atención.
Observaciones técnicas.

Esto permite llevar trazabilidad completa del mantenimiento de cada vehículo.

👨‍🔧 Gestión de Empleados

El gerente puede:

Registrar empleados.
Asignar roles.
Habilitar o deshabilitar usuarios.
Controlar estados laborales.

Roles disponibles:

Recepcionista
Mecánico
Gerente
🧰 Gestión de Servicios

Permite administrar:

Servicios disponibles.
Descripciones.
Costos.
Disponibilidad.

Ejemplos:

Cambio de aceite.
Diagnóstico.
Alineación.
Balanceo.
Reparaciones generales.
📦 Gestión de Inventario

Control de productos utilizados en el taller:

Repuestos.
Lubricantes.
Herramientas.
Insumos de mantenimiento.

Incluye control de stock y disponibilidad.

🧾 Facturación

El sistema permite:

Generar comprobantes.
Calcular costos automáticamente.
Registrar servicios realizados.
Emitir documentos para impresión o PDF.
📊 Reportes

El módulo de reportes proporciona información para la toma de decisiones:

Servicios realizados.
Vehículos atendidos.
Órdenes completadas.
Inventario disponible.
Actividad de empleados.

Además, permite exportar información para análisis posteriores.

🗄 Base de Datos

La base de datos fue diseñada utilizando el modelo relacional e incluye las siguientes tablas principales:

clientes
empleados
vehiculos
turnos
ordenes
servicios
productos

Las relaciones entre tablas garantizan la integridad de la información mediante llaves primarias y foráneas.

🔒 Seguridad Implementada

Durante el desarrollo se implementaron diversas medidas de seguridad:

Autenticación mediante inicio de sesión.
Contraseñas cifradas con password_hash().
Verificación de contraseñas mediante password_verify().
Prepared Statements con PDO.
Protección contra SQL Injection.
Control de acceso por roles.
Regeneración de sesiones.
Cierre automático por inactividad.
Restricción de acceso a módulos no autorizados.
🚀 Instalación
Requisitos
PHP 8.0 o superior
MySQL
Apache
XAMPP
Pasos
1. Clonar el repositorio
git clone https://github.com/usuario/garage_gt.git
2. Mover el proyecto

Copiar la carpeta dentro de:

C:\xampp\htdocs\
3. Crear la base de datos
garage_gt
4. Importar
garage_gt.sql
5. Configurar conexión

Editar:

config/db.php
6. Ejecutar
http://localhost/garage_gt
📈 Resultados Obtenidos

Con la implementación de Garage GT se logró:

Digitalizar los procesos administrativos del taller.
Reducir el tiempo de gestión de órdenes.
Centralizar la información de clientes y vehículos.
Mejorar el control de inventario.
Facilitar la generación de reportes.
Incrementar la seguridad de la información mediante controles de acceso.
🎓 Conclusión

El proyecto Taller Mecánico GT permitió aplicar los conocimientos adquiridos durante el curso de Análisis y Diseño de Sistemas, abarcando las etapas de análisis, diseño, modelado de base de datos, implementación y pruebas de un sistema real.

La solución desarrollada proporciona una herramienta funcional para la administración de talleres mecánicos, mejorando la organización, eficiencia y control de los procesos operativos y administrativos mediante tecnologías web modernas.
