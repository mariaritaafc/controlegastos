<?php
session_start();

$servidor = 'localhost';
$usuario = 'root';
$senha = '';
$bd = 'controle_gastos';

$conexao = mysqli_connect($servidor, $usuario, $senha, $bd);

if (mysqli_connect_errno()) {
    printf("Conexao falhou: %s\n", mysqli_connect_error());
    exit();
}

function esta_logado() {
    return isset($_SESSION['usuario_id']);
}

function redirecionar_se_nao_logado() {
    if (!esta_logado()) {
        header('Location: login.php');
        exit();
    }
}
