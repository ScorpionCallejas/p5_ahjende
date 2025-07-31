# 📁 Sistema de Gestión de Archivos en PHP

Este proyecto permite subir, visualizar, actualizar y eliminar archivos mediante una interfaz web amigable utilizando PHP, MySQL y Bootstrap 5. Incluye un sistema de nombres cifrados para los archivos, validaciones de formato y tamaño, y una funcionalidad "drag & drop" para facilitar la carga de documentos.

<img width="1343" height="955" alt="image" src="https://github.com/user-attachments/assets/2c2aa1d3-33ca-469e-9883-24d0e4d5de71" />

## 🧰 Tecnologías utilizadas

- **PHP 5.6+**
- **MySQL**
- **Bootstrap 5**
- **HTML5 / JavaScript**
- **AJAX básico (JS nativo)**

---

## 🚀 Funcionalidades principales

### 🔼 Subida de archivos

- Subida mediante formulario o área *drag & drop*
- Tamaño máximo permitido: **10MB**
- Validación de formato de archivo (alfanumérico en la extensión)
- Cifrado automático del nombre del archivo (`FILE-XXXXXX.ext`)
- Registro en base de datos con:
  - Nombre original
  - Nombre cifrado
  - Descripción
  - Estatus
  - Formato
  - Fecha de carga

### 📝 Edición de archivos

- Permite actualizar la descripción de un archivo
- Permite reemplazar el archivo (opcional)
- El archivo anterior se elimina del servidor si se actualiza

### ❌ Eliminación de archivos

- Borra el archivo del servidor
- Elimina el registro en la base de datos

### 📄 Listado de archivos

- Muestra en una tabla todos los archivos subidos
- Incluye acciones para:
  - Ver el archivo
  - Descargar el archivo
  - Eliminar
  - Editar (mediante modal Bootstrap)

---

## 📦 Estructura de la base de datos

Tabla: `archivo`

```sql
CREATE TABLE archivo (
  id_arc INT(11) AUTO_INCREMENT PRIMARY KEY,
  fec_arc DATETIME DEFAULT CURRENT_TIMESTAMP,
  arc_arc VARCHAR(500) NOT NULL, -- Nombre cifrado
  nom_arc VARCHAR(500) NOT NULL, -- Nombre original
  des_arc TEXT,                  -- Descripción
  est_arc VARCHAR(50),          -- Estatus (ej. Activo)
  for_arc VARCHAR(100)          -- Formato del archivo
);
