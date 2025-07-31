<?php
// Conexión a la base de datos
$host = "localhost";
$user = "root";
$pass = "";
$database = "db_test";
$connection = mysqli_connect($host, $user, $pass, $database);
if (!$connection) {
    die('Error de conexión: ' . mysqli_connect_error());
}
mysqli_set_charset($connection, "utf8");

// Funciones
function limpiar($valor, $conexion) {
    return mysqli_real_escape_string($conexion, trim($valor));
}

function generarNombreCifrado($nombreOriginal) {
    $ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
    return 'FILE-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 12)) . '.' . $ext;
}

$mensaje = "";

// SUBIR NUEVO ARCHIVO
if (isset($_POST['subir'])) {
    $descripcion = limpiar($_POST['descripcion'], $connection);
    $estatus = 'Activo';

    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['archivo'];
        $nombreOriginal = basename($archivo['name']);
        $formato = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $peso = $archivo['size'];
        $temporal = $archivo['tmp_name'];

        if (!preg_match('/^[a-z0-9]+$/i', $formato)) {
            $mensaje = "<div class='alert alert-danger'>Formato inválido.</div>";
        } elseif ($peso > 10485760) {
            $mensaje = "<div class='alert alert-danger'>El archivo supera los 10MB.</div>";
        } else {
            $nombreCifrado = generarNombreCifrado($nombreOriginal);
            $destino = "uploads/$nombreCifrado";
            if (!is_dir("uploads")) mkdir("uploads", 0777, true);
            if (move_uploaded_file($temporal, $destino)) {
                $stmt = $connection->prepare("INSERT INTO archivo (arc_arc, nom_arc, des_arc, est_arc, for_arc) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $nombreCifrado, $nombreOriginal, $descripcion, $estatus, $formato);
                if ($stmt->execute()) {
                    $mensaje = "<div class='alert alert-success'>Archivo subido correctamente.</div>";
                } else {
                    unlink($destino);
                    $mensaje = "<div class='alert alert-danger'>Error al guardar en la base de datos.</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al mover archivo.</div>";
            }
        }
    }
}

// ACTUALIZAR ARCHIVO
if (isset($_POST['actualizar'])) {
    $id = intval($_POST['id_arc']);
    $descripcion = limpiar($_POST['descripcion'], $connection);

    $stmt = $connection->prepare("SELECT arc_arc FROM archivo WHERE id_arc = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($archivoAntiguo);
    $stmt->fetch();
    $stmt->close();

    $nuevoArchivo = $archivoAntiguo;

    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['archivo'];
        $nombreOriginal = basename($archivo['name']);
        $formato = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $temporal = $archivo['tmp_name'];

        if (file_exists("uploads/$archivoAntiguo")) unlink("uploads/$archivoAntiguo");

        $nuevoArchivo = generarNombreCifrado($nombreOriginal);
        if (!is_dir("uploads")) mkdir("uploads", 0777, true);
        if (move_uploaded_file($temporal, "uploads/$nuevoArchivo")) {
            $stmt = $connection->prepare("UPDATE archivo SET arc_arc=?, nom_arc=?, des_arc=?, for_arc=? WHERE id_arc=?");
            $stmt->bind_param("ssssi", $nuevoArchivo, $nombreOriginal, $descripcion, $formato, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al mover archivo.</div>";
        }
    } else {
        $stmt = $connection->prepare("UPDATE archivo SET des_arc=? WHERE id_arc=?");
        $stmt->bind_param("si", $descripcion, $id);
        $stmt->execute();
        $stmt->close();
    }

    $mensaje = "<div class='alert alert-success'>Archivo actualizado correctamente.</div>";
}

// ELIMINAR ARCHIVO
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $res = mysqli_query($connection, "SELECT arc_arc FROM archivo WHERE id_arc = $id");
    if ($fila = mysqli_fetch_assoc($res)) {
        if (file_exists("uploads/" . $fila['arc_arc'])) unlink("uploads/" . $fila['arc_arc']);
        mysqli_query($connection, "DELETE FROM archivo WHERE id_arc = $id");
        $mensaje = "<div class='alert alert-warning'>Archivo eliminado correctamente.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestión de Archivos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .drop-zone {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            background-color: #f8f9fa;
            transition: background-color 0.2s ease-in-out;
        }
        .drop-zone.dragover {
            background-color: #e2e6ea;
        }
    </style>
</head>
<body class="container py-5">

<?= $mensaje ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0">Subir nuevo archivo</h4>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <div class="drop-zone" id="dropZone">
                    Arrastra y suelta un archivo aquí o haz clic para seleccionarlo.
                    <input type="file" name="archivo" id="archivo" class="form-control d-none" required>
                    <p id="nombreArchivo" class="mt-2 text-muted"></p>
                </div>
            </div>
            <div class="mb-3">
                <textarea name="descripcion" class="form-control" placeholder="Descripción..." rows="3"></textarea>
            </div>
            <button type="submit" name="subir" class="btn btn-success">Subir archivo</button>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-secondary text-white">
        <h4 class="mb-0">Archivos subidos</h4>
    </div>
    <div class="card-body">
        <table class="table table-bordered table-hover table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Archivo</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Formato</th>
                    <th>Estatus</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $archivos = mysqli_query($connection, "SELECT * FROM archivo ORDER BY id_arc DESC");
                while ($fila = mysqli_fetch_assoc($archivos)) {
                    echo "<tr>
                            <td>{$fila['id_arc']}</td>
                            <td>{$fila['fec_arc']}</td>
                            <td><a href='uploads/{$fila['arc_arc']}' target='_blank'>Ver</a> - <a href='uploads/{$fila['arc_arc']}' download>Descargar</a></td>
                            <td>{$fila['nom_arc']}</td>
                            <td>{$fila['des_arc']}</td>
                            <td>{$fila['for_arc']}</td>
                            <td>{$fila['est_arc']}</td>
                            <td>
                                <a href='?eliminar={$fila['id_arc']}' class='btn btn-sm btn-danger' onclick=\"return confirm('¿Eliminar archivo?')\">Eliminar</a>
                                <button class='btn btn-sm btn-warning' onclick='abrirModalEditar({$fila['id_arc']}, \"{$fila['des_arc']}\")'>Editar</button>
                            </td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de edición -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar archivo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id_arc" id="editId">
        <div class="mb-3">
            <label for="editDescripcion" class="form-label">Descripción</label>
            <textarea name="descripcion" id="editDescripcion" class="form-control" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Nuevo archivo (opcional)</label>
            <input type="file" name="archivo" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" name="actualizar" class="btn btn-primary">Actualizar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<script>
const dropZone = document.getElementById("dropZone");
const inputArchivo = document.getElementById("archivo");
const nombreArchivo = document.getElementById("nombreArchivo");

dropZone.addEventListener("click", () => inputArchivo.click());
dropZone.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZone.classList.add("dragover");
});
dropZone.addEventListener("dragleave", () => {
    dropZone.classList.remove("dragover");
});
dropZone.addEventListener("drop", (e) => {
    e.preventDefault();
    dropZone.classList.remove("dragover");
    const archivo = e.dataTransfer.files[0];
    if (archivo) {
        inputArchivo.files = e.dataTransfer.files;
        nombreArchivo.textContent = archivo.name;
    }
});
inputArchivo.addEventListener("change", () => {
    if (inputArchivo.files.length > 0) {
        nombreArchivo.textContent = inputArchivo.files[0].name;
    }
});

function abrirModalEditar(id, descripcion) {
    document.getElementById("editId").value = id;
    document.getElementById("editDescripcion").value = descripcion;
    new bootstrap.Modal(document.getElementById("modalEditar")).show();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
