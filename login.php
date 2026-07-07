<?php
require_once 'config.php';

if (esta_logado()) {
    header('Location: index.php');
    exit();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conexao, $_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $erro = 'Preencha todos os campos.';
    } else {
        $sql = "SELECT * FROM usuarios WHERE email = '$email'";
        $result = mysqli_query($conexao, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $usuario = mysqli_fetch_assoc($result);
            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                header('Location: index.php');
                exit();
            } else {
                $erro = 'Senha incorreta.';
            }
        } else {
            $erro = 'Email nao encontrado.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
    <h1>Login</h1>

    <?php if ($erro): ?>
        <div class="erro"><?php echo $erro; ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="campo">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="campo">
            <label for="senha">Senha</label>
            <input type="password" name="senha" id="senha" required>
        </div>
        <button type="submit" class="btn">Entrar</button>
    </form>

    <p class="link"><a href="cadastro.php">Criar conta</a></p>
</div>
</body>
</html>
