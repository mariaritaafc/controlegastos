<?php
require_once 'config.php';

if (esta_logado()) {
    header('Location: index.php');
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = mysqli_real_escape_string($conexao, $_POST['nome']);
    $email = mysqli_real_escape_string($conexao, $_POST['email']);
    $senha = $_POST['senha'];
    $senha2 = $_POST['senha2'];

    if (empty($nome) || empty($email) || empty($senha) || empty($senha2)) {
        $erro = 'Preencha todos os campos.';
    } elseif ($senha != $senha2) {
        $erro = 'As senhas nao conferem.';
    } elseif (strlen($senha) < 4) {
        $erro = 'A senha deve ter pelo menos 4 caracteres.';
    } else {
        $check = mysqli_query($conexao, "SELECT id FROM usuarios WHERE email = '$email'");
        if ($check && mysqli_num_rows($check) > 0) {
            $erro = 'Este email ja esta cadastrado.';
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nome, email, senha) VALUES ('$nome', '$email', '$senha_hash')";
            if (mysqli_query($conexao, $sql)) {
                $_SESSION['usuario_id'] = mysqli_insert_id($conexao);
                $_SESSION['usuario_nome'] = $nome;
                header('Location: index.php');
                exit();
            } else {
                $erro = 'Erro ao cadastrar: ' . mysqli_error($conexao);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #2d3748; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); width: 100%; max-width: 400px; }
        h1 { font-size: 24px; font-weight: 600; margin-bottom: 24px; color: #1a202c; text-align: center; }
        label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #4a5568; }
        input { width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
        input:focus { outline: none; border-color: #5b7ffa; box-shadow: 0 0 0 3px rgba(91,127,250,0.1); }
        .btn { width: 100%; padding: 10px; background: #5b7ffa; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #4a6fe0; }
        .erro { background: #fff5f5; color: #e53e3e; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; border: 1px solid #fed7d7; }
        .link { text-align: center; margin-top: 16px; font-size: 14px; }
        .link a { color: #5b7ffa; text-decoration: none; }
        .link a:hover { text-decoration: underline; }
        .campo { margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Criar Conta</h1>

    <?php if ($erro): ?>
        <div class="erro"><?php echo $erro; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="campo">
            <label for="nome">Nome</label>
            <input type="text" name="nome" id="nome" required>
        </div>
        <div class="campo">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="campo">
            <label for="senha">Senha</label>
            <input type="password" name="senha" id="senha" required>
        </div>
        <div class="campo">
            <label for="senha2">Confirmar senha</label>
            <input type="password" name="senha2" id="senha2" required>
        </div>
        <button type="submit" class="btn">Cadastrar</button>
    </form>

    <p class="link"><a href="login.php">Ja tem conta? Entrar</a></p>
</div>
</body>
</html>
