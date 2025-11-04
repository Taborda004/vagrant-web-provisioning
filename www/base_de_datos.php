<?php
// Mostrar errores en desarrollo (no en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== Configuración de conexión =====
$dsn = "host=192.168.56.11 port=5432 dbname=taller user=vagrant password=vagrant";

// Conexión
$conn = @pg_connect($dsn);
if (!$conn) {
    http_response_code(500);
    die(" Error al conectar a la base de datos.");
}

// Consulta
$sql = "SELECT id, nombre FROM personas ORDER BY id";
$result = @pg_query($conn, $sql);
if (!$result) {
    http_response_code(500);
    die(" Error al ejecutar la consulta.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Personas - Taller Vagrant</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
        header { background: #b81d1d; color: #fff; text-align: center; padding: 15px 0; font-size: 1.3em; }
        main { display: flex; justify-content: center; margin-top: 40px; }
        table { border-collapse: collapse; width: 50%; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden; }
        th { background: #b81d1d; color: #fff; padding: 12px; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        h2 { text-align: center; margin-bottom: 20px; }
        footer { text-align: center; margin-top: 40px; font-size: 0.9em; color: #666; padding-bottom: 20px; }
    </style>
</head>
<body>
    <header>Base de datos - Taller de provisionamiento</header>
    <main>
        <div>
            <h2>Lista de personas registradas</h2>
            <table>
                <tr><th>ID</th><th>Nombre</th></tr>
                <?php while ($row = pg_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </main>
    <footer>© 2025 Universidad Autónoma de Occidente — Mauricio Taborda Góngora — 1012347613</footer>
</body>
</html>
<?php
pg_free_result($result);
pg_close($conn);
?>
