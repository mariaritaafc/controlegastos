<?php
require_once 'config.php';
redirecionar_se_nao_logado();
$usuario_id = $_SESSION['usuario_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?erro=ID nao informado.');
    exit();
}

$id = (int)$_GET['id'];

$sql = "DELETE FROM gastos WHERE id = $id AND usuario_id = $usuario_id";
if (mysqli_query($conexao, $sql)) {
    header('Location: index.php?msg=Gasto excluido com sucesso!');
} else {
    header('Location: index.php?erro=Erro ao excluir: ' . mysqli_error($conexao));
}

mysqli_close($conexao);
exit();
