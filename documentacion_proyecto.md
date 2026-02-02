# Documentación del Proyecto – MVP

## Ingeniería Web I

---

## 1. Nombre del proyecto

**Wava** (nombre provisional)

---

## 2. Descripción del proyecto

Wava es una web app de seguimiento personal orientada al bienestar (wellness), donde cada usuario puede registrarse e iniciar sesión para llevar un registro diario editable de hábitos básicos: hidratación, nutrición (proteína), descanso/energía y actividad física.

La aplicación permite definir objetivos diarios personalizados y visualizar el progreso de cada día mediante tarjetas de resumen, así como consultar y editar el historial de días registrados.

---

## 3. Objetivo del proyecto

El objetivo principal del proyecto es facilitar un registro simple, ordenado y accesible de hábitos diarios, permitiendo al usuario:

- establecer objetivos personales,
- visualizar su progreso diario,
- registrar múltiples actividades físicas por día,
- y consultar el historial de registros anteriores.

---

## 4. Alcance del MVP

### 4.1 Autenticación

- Registro de usuario con email y contraseña.
- Inicio de sesión y cierre de sesión.
- Gestión de sesión para restringir el acceso a usuarios autenticados.
- Inicio de sesión automático luego del registro.

### 4.2 Perfil y objetivos

- Página de perfil con datos personales editables:
  - nombre
  - edad (opcional)
  - altura (opcional)
  - peso (opcional)
- Configuración de objetivos diarios:
  - objetivo de agua (ml)
  - objetivo de proteína (g)
  - objetivo de ejercicio (minutos)
  - objetivo de sueño (horas)

### 4.3 Registro diario

Para cada fecha, el usuario puede crear o actualizar un registro diario con:

- agua consumida (ml)
- proteína consumida (g)
- horas de sueño (opcional)
- nivel de energía (1–10, opcional)
- notas del día (opcional)

Cada día se representa como un único registro editable durante la jornada.

### 4.4 Ejercicios múltiples por día

Un mismo día puede contener múltiples ejercicios (por ejemplo: running, fuerza, yoga).
Cada ejercicio incluye:

- tipo de ejercicio
- duración en minutos
- nota opcional

### 4.5 Dashboard

Pantalla principal de la aplicación que incluye:

- selección de fecha (por defecto el día actual),
- tarjetas de progreso del día en relación a los objetivos,
- formulario para editar los datos del día,
- listado de ejercicios del día con opción de agregar y eliminar ejercicios.

### 4.6 Historial

- Listado de días registrados por fecha.
- Posibilidad de navegar entre días y editar registros anteriores.

### 4.7 Funcionalidad AJAX (Fetch)

Se implementa comunicación asíncrona utilizando Fetch API y JSON:

- Alta y baja de ejercicios sin recargar la página.
- Actualización dinámica de totales y progreso diario.

---

## 5. Tecnologías utilizadas

### Frontend

- HTML5
- CSS3 (responsive)
- JavaScript (DOM, eventos, validaciones, Fetch API)

### Backend

- PHP 8.x
- Programación orientada a objetos (parcial, modelos de dominio)
- Manejo de sesiones

### Base de datos

- MySQL

### Comunicación asíncrona

- Fetch API
- JSON

### Control de versiones

- Git
- GitHub

---

## 6. Modelo de datos (resumen)

### users

- id
- name
- email (unique)
- password_hash
- age
- height_cm
- weight_kg
- lifestyle (opcional)
- goal_water_ml
- goal_protein_g
- goal_exercise_minutes
- goal_sleep_hours
- created_at

### day_logs

- id
- user_id
- log_date (unique por usuario)
- water_ml
- protein_g
- sleep_hours
- energy_level
- notes
- created_at

### workouts

- id
- day_log_id
- workout_type
- minutes
- notes
- created_at

---

## 7. Reglas principales de negocio

- Cada usuario tiene un único registro diario (`day_log`) por fecha.
- Un registro diario puede contener cero o más ejercicios.
- El progreso diario se calcula en función de los objetivos del usuario:
  - agua: `water_ml / goal_water_ml`
  - proteína: `protein_g / goal_protein_g`
  - ejercicio: `SUM(workouts.minutes) / goal_exercise_minutes`
  - sueño: `sleep_hours / goal_sleep_hours`

---

## 8. Seguridad y validaciones

- Contraseñas almacenadas utilizando `password_hash()` y verificadas con `password_verify()`.
- Consultas SQL realizadas mediante prepared statements para prevenir SQL Injection.
- Validaciones en cliente (JavaScript) y servidor (PHP).
- Sanitización de salidas con `htmlspecialchars()` para evitar XSS.

---

## 9. Instrucciones de instalación y ejecución

### Requisitos

- Apache
- PHP 8.x
- MySQL
- MAMP / XAMPP o entorno similar

### Pasos

1. Clonar o copiar el proyecto en la carpeta del servidor local.
2. Crear una base de datos MySQL.
3. Importar el archivo `sql/wava.sql`.
4. Configurar la conexión a la base de datos en `app/config/db.php`.
5. Acceder desde el navegador a:
   http://localhost:8888/wava/public/index.php

## 10. Proyección futura (fuera del alcance de la materia)

El proyecto fue desarrollado como un MVP funcional en el marco de la materia Ingeniería Web I.
De forma personal, se decidió continuar su desarrollo luego de la entrega, con el objetivo de profundizar aprendizajes en áreas como:

- análisis de datos,
- visualización de métricas,
- posibles modelos de predicción,
- despliegue en la nube y arquitectura de aplicaciones.

Estas mejoras quedan explícitamente fuera del alcance de la presente entrega académica.
