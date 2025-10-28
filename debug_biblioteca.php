<?php
// debug_biblioteca.php - ELIMINA DESPUÉS DE USAR

require_once 'config/database.php';
require_once 'config/app.php';

App::init();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_create'])) {
    echo "<h2>🔍 PRUEBA DE CREACIÓN</h2>";
    
    // Simular datos de biblioteca
    $type = $_POST['type'] ?? 'dias';
    $titulo = $_POST['titulo'] ?? 'Test Día';
    $descripcion = $_POST['descripcion'] ?? 'Descripción de prueba';
    
    echo "<h3>📊 Datos Recibidos:</h3>";
    echo "Type: " . $type . "<br>";
    echo "Título: " . $titulo . "<br>";
    echo "POST: <pre>" . print_r($_POST, true) . "</pre>";
    echo "FILES: <pre>" . print_r($_FILES, true) . "</pre>";
    
    try {
        $db = Database::getInstance();
        $table = "biblioteca_" . $type;
        
        echo "<h3>🗄️ Verificación de Tabla:</h3>";
        
        // Verificar que la tabla existe
        $tableExists = $db->fetch("SHOW TABLES LIKE ?", [$table]);
        if ($tableExists) {
            echo "✅ Tabla '$table' existe<br>";
            
            // Mostrar estructura
            $columns = $db->fetchAll("DESCRIBE $table");
            echo "<strong>Columnas de la tabla:</strong><br>";
            foreach ($columns as $col) {
                echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
            }
        } else {
            echo "❌ Tabla '$table' NO existe<br>";
        }
        
        echo "<h3>💾 Intento de Inserción:</h3>";
        
        // Datos básicos para insertar
        $data = [
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'ubicacion' => 'Prueba, Test',
            'user_id' => $_SESSION['user_id'] ?? 1,
            'idioma' => 'es',
            'activo' => 1
        ];
        
        // Agregar campos específicos según el tipo
        if ($type === 'dias') {
            $data['latitud'] = 40.7128;
            $data['longitud'] = -74.0060;
        }
        
        echo "Datos a insertar: <pre>" . print_r($data, true) . "</pre>";
        
        // Intentar insertar
        $id = $db->insert($table, $data);
        
        if ($id) {
            echo "✅ <strong>INSERCIÓN EXITOSA! ID: $id</strong><br>";
            
            // Verificar que se insertó
            $inserted = $db->fetch("SELECT * FROM $table WHERE id = ?", [$id]);
            echo "Registro insertado: <pre>" . print_r($inserted, true) . "</pre>";
            
            // Ahora probar subida de imagen si hay archivo
            if (isset($_FILES['imagen1']) && $_FILES['imagen1']['error'] === UPLOAD_ERR_OK) {
                echo "<h3>🖼️ Procesando Imagen:</h3>";
                
                $file = $_FILES['imagen1'];
                echo "Archivo: " . $file['name'] . " (" . $file['size'] . " bytes)<br>";
                
                // Crear directorio
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/biblioteca/' . $type . '/' . date('Y/m') . '/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                    echo "Directorio creado: $uploadDir<br>";
                }
                
                // Mover archivo
                $fileName = $type . '_' . $id . '_imagen1_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $filePath)) {
                    $url = APP_URL . '/assets/uploads/biblioteca/' . $type . '/' . date('Y/m') . '/' . $fileName;
                    echo "✅ Archivo subido: $url<br>";
                    
                    // Actualizar base de datos con la URL
                    $updateResult = $db->update($table, ['imagen1' => $url], 'id = ?', [$id]);
                    echo "Actualización de imagen: " . ($updateResult ? 'exitosa' : 'fallida') . "<br>";
                    
                    // Verificar actualización
                    $updated = $db->fetch("SELECT imagen1 FROM $table WHERE id = ?", [$id]);
                    echo "URL guardada: " . $updated['imagen1'] . "<br>";
                    
                } else {
                    echo "❌ Error moviendo archivo<br>";
                }
            } else {
                echo "ℹ️ No se envió imagen o hubo error en upload<br>";
            }
            
        } else {
            echo "❌ <strong>ERROR EN INSERCIÓN</strong><br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>EXCEPCIÓN:</strong> " . $e->getMessage() . "<br>";
        echo "<strong>Stack trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Biblioteca</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; }
        h2 { color: #333; }
        h3 { color: #666; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🔧 Debug Biblioteca</h1>
    
    <h2>📋 Información de Sesión:</h2>
    <p><strong>Usuario ID:</strong> <?= $_SESSION['user_id'] ?? 'NO DEFINIDO' ?></p>
    <p><strong>Username:</strong> <?= $_SESSION['username'] ?? 'NO DEFINIDO' ?></p>
    <p><strong>Role:</strong> <?= $_SESSION['user_role'] ?? 'NO DEFINIDO' ?></p>
    <p><strong>APP_URL:</strong> <?= APP_URL ?></p>
    
    <h2>🧪 Formulario de Prueba:</h2>
    <form method="POST" enctype="multipart/form-data">
        <p>
            <label>Tipo:</label><br>
            <select name="type">
                <option value="dias">Días</option>
                <option value="alojamientos">Alojamientos</option>
                <option value="actividades">Actividades</option>
                <option value="transportes">Transportes</option>
            </select>
        </p>
        
        <p>
            <label>Título:</label><br>
            <input type="text" name="titulo" value="Test Día <?= date('H:i:s') ?>" required>
        </p>
        
        <p>
            <label>Descripción:</label><br>
            <textarea name="descripcion">Descripción de prueba para debug</textarea>
        </p>
        
        <p>
            <label>Imagen 1:</label><br>
            <input type="file" name="imagen1" accept="image/*">
        </p>
        
        <p>
            <input type="submit" name="test_create" value="🚀 Probar Creación">
        </p>
    </form>
    
    <hr>
    <p><strong>⚠️ Elimina este archivo después de usar.</strong></p>
</body>
</html>