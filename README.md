# Wava – Web App de Seguimiento de Hábitos

MVP – Ingeniería Web I

---

## Descripción

Wava es una web app de seguimiento personal (wellness) que permite a cada usuario registrar y visualizar sus hábitos diarios: hidratación, nutrición (proteína), descanso y actividad física.

El proyecto corresponde a un **MVP funcional** desarrollado para la materia **Ingeniería Web I**.

---

## Funcionalidades principales

- Registro e inicio de sesión de usuarios.
- Gestión de sesión (acceso restringido).
- Configuración de perfil y objetivos diarios.
- Registro diario editable de hábitos.
- Registro de múltiples ejercicios por día.
- Dashboard con tarjetas de progreso.
- Historial de días registrados.
- Funcionalidad AJAX usando Fetch API y JSON.

---

## Tecnologías utilizadas

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 8.x
- **Base de datos:** MySQL
- **Comunicación asíncrona:** Fetch API (AJAX) + JSON
- **Control de versiones:** Git / GitHub

---

## Requisitos

- Apache
- PHP 8.x
- MySQL
- MAMP / XAMPP (o entorno similar)

---

## Instalación y ejecución

## Repositorio

https://github.com/c-spinelli/wava.git

### 1. Clonar o copiar el proyecto

Colocar el proyecto dentro de la carpeta del servidor local (por ejemplo `htdocs` o `Applications/MAMP/htdocs`).

### 2. Crear la base de datos

Crear una base de datos MySQL (por ejemplo `wava`).

### 3. Importar el SQL

Importar el archivo:
sql/wava.sql

### 4. Configurar conexión a la base de datos

Editar el archivo:
app/config/db.php

y completar los datos de conexión (host, usuario, contraseña, base de datos).

### 5. Acceder a la aplicación

Desde el navegador:
http://localhost:8888/wava/public/index.php

---

## Rutas principales

- Landing: `/public/index.php`
- Login: `/public/login.php`
- Registro: `/public/register.php`
- Dashboard: `/public/dashboard.php`
- Perfil: `/public/profile.php`
- Historial: `/public/history.php`

---

## Usuario

No se incluyen usuarios precargados.  
Se debe crear un usuario desde la pantalla de **registro**.

---

## Estado del proyecto

Proyecto MVP funcional.  
El diseño visual se encuentra en una etapa de mejora progresiva.

Este proyecto se desarrolló como un MVP para la materia Ingeniería Web I y continúa en desarrollo de forma personal.

---
