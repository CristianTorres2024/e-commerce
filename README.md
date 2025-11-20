---

# Proyecto E-commerce

## Descripción

Este es un sistema de comercio electrónico desarrollado como proyecto académico. Las funcionalidades principales incluyen gestión de usuarios (registro, login/logout, roles), carrito de compras, lista de deseos, página “Sobre la empresa”, integración con API de pago (PayPal), y panel de administración.


## Tecnologías utilizadas

* Back-end: PHP.
* Front-end: JavaScript y CSS.
* Base de datos: MySQL (o archivos SQL de estructura: `oceantrade.sql`, `permisos.sql`).
* Control de versiones: Git + GitHub.
* Desarrollo: Visual Studio Code, entorno local (XAMPP/VirtualBox).

## Funcionalidades clave

### Usuario & autenticación

* Registro de nuevos usuarios y login/logout.
* Roles de usuario (administrador, cliente) para diferenciar accesos.
* Gestión de permisos (estructura `permisos.sql`).
* Panel de administración para ver/editar usuarios.

### Productos, carrito y lista de deseos

* Visualización de catálogo de productos.
* Carrito de compras: agregar, modificar cantidad, eliminar.
* Lista de deseos (“wishlist”): posibilidad para el usuario de marcar productos que le interesan para más tarde.
* Proceso de checkout integrado con API de pago.

### Integración de pago

* Uso de la API de PayPal para procesar pagos seguros.
* Verificación de transacción, actualización de estado de pedido.

### Página “Sobre la empresa”

* Una página estática que describe la empresa, misión, visión, equipo, etc.
* Diseño responsivo y adaptado para buen uso en dispositivos móviles.

### Administración & mantenimiento

* SQL Scripts (`oceantrade.sql`, `permisos.sql`) para crear/llenar la base de datos.
* Estructura modular para backend/frontend que facilita mantenimiento, escalabilidad.
* Logs básicos de actividad (por ejemplo, historial de pedidos, actividades de usuario).

## Instalación y puesta en marcha

1. Clonar el repositorio:

   ```bash
   git clone https://github.com/CristianTorres2024/e-commerce.git
   ```
2. Configurar servidor local en GNU/Linux (o equivalente): PHP, MySQL, Apache/Nginx.
3. Crear base de datos MySQL y ejecutar los scripts `oceantrade.sql` y `permisos.sql` para cargar la estructura y los datos iniciales.
4. Configurar archivo de conexión a base de datos (por ejemplo, `config.php`) con host, usuario, contraseña, nombre de base de datos.
5. Configurar credenciales de la API de PayPal (sandbox para pruebas, luego producción).
6. Acceder desde navegador al directorio del proyecto, registrarse como usuario o acceder como administrador.
7. Probar funcionalidades: navegación catálogo, añadir al carrito, lista de deseos, realizar pago, administración.

## Estructura del proyecto

```
/e-commerce
│
├── /css/          ← estilos CSS  
├── /js/           ← scripts JavaScript  
├── /includes/     ← bibliotecas, configuración, funciones comunes  
├── /admin/        ← panel de administración  
├── /user/         ← funciones específicas para usuario  
├── /payment/      ← integración con PayPal  
├── /sql/          ← scripts SQL (oceantrade.sql, permisos.sql)  
├── index.php      ← página principal  
├── about.php      ← página “Sobre la empresa”  
└── …
```

## Lo que aprendí / retos que resolví

* Implementación de roles y permisos en la gestión de usuarios.
* Integración de un sistema de pagos real (PayPal) en un entorno de estudio, entendiendo los flujos de autorización, transacción, verificación.
* Manejo de estado de carrito/lista de deseos, persistencia en base de datos, sincronización con sesión del usuario.
* Diseño de una experiencia bilingüe (Español / Inglés) y modularización del código que facilita la internacionalización.
* Trabajo con entorno de desarrollo local (GNU/Linux) y base de datos MySQL, scripts de inicialización, migraciones básicas.

## Posibles mejoras futuras

* Añadir registro vía OAuth (Google, Facebook).
* Implementar sistema de cupones/descuentos.
* Añadir panel de análisis (reportes de ventas, productos más deseados).
* Migrar a arquitectura de microservicios (REST API) con front-end en React (ya que estás aprendiendo React) y backend en Node.js.
* Mejorar interfaz de usuario/responsividad, aplicar diseño móvil primero (mobile-first) y mejorar accesibilidad.

## Contacto

Para más información, puedes contactarme:

* Nombre: Cristian Torres
* GitHub: [CristianTorres2024](https://github.com/CristianTorres2024)
* Correo: torrescristian661@gmail.com
* LinkedIn: https://www.linkedin.com/in/cristian-torres-3aab782b9


[1]: https://github.com/CristianTorres2024/e-commerce "GitHub - CristianTorres2024/e-commerce"
