<?php
require_once 'config.php';
redirecionar_se_nao_logado();
$usuario_id = $_SESSION['usuario_id'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=gastos_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, ['Descricao', 'Valor', 'Categoria', 'Conta', 'Data', 'Recorrente']);

$resultado = mysqli_query($conexao, "SELECT descricao, valor, categoria, conta, data, recorrente FROM gastos WHERE usuario_id = $usuario_id ORDER BY data DESC");

if ($resultado) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        fputcsv($output, [
            $row['descricao'],
            number_format($row['valor'], 2, ',', '.'),
            $row['categoria'],
            $row['conta'],
            $row['data'],
            $row['recorrente'] ? 'Sim' : 'Nao'
        ]);
    }
}

fclose($output);
mysqli_close($conexao);
exit();
