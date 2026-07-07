<?php
require_once 'config.php';
redirecionar_se_nao_logado();
$usuario_id = $_SESSION['usuario_id'];

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php?erro=ID nao informado.');
    exit();
}

$id = (int)$_GET['id'];

$check = mysqli_query($conexao, "SELECT * FROM gastos WHERE id = $id AND usuario_id = $usuario_id");
if (!$check || mysqli_num_rows($check) == 0) {
    header('Location: index.php?erro=Gasto nao encontrado.');
    exit();
}
$gasto = mysqli_fetch_assoc($check);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
    $valor = str_replace(',', '.', $_POST['valor']);
    $categoria = mysqli_real_escape_string($conexao, $_POST['categoria']);
    $data = mysqli_real_escape_string($conexao, $_POST['data']);
    $conta = mysqli_real_escape_string($conexao, $_POST['conta']);
    $recorrente = isset($_POST['recorrente']) ? 1 : 0;
    $recorrencia_tipo = $recorrente ? mysqli_real_escape_string($conexao, $_POST['recorrencia_tipo']) : NULL;

    if (empty($descricao) || empty($valor) || empty($categoria) || empty($data)) {
        header("Location: editar.php?id=$id&erro=Todos os campos sao obrigatorios.");
        exit();
    }
    if (!is_numeric($valor) || $valor <= 0) {
        header("Location: editar.php?id=$id&erro=O valor deve ser um numero positivo.");
        exit();
    }

    $sql = "UPDATE gastos SET descricao='$descricao', valor='$valor', categoria='$categoria', data='$data', conta='$conta', recorrente=$recorrente, recorrencia_tipo=" . ($recorrencia_tipo ? "'$recorrencia_tipo'" : "NULL") . " WHERE id=$id AND usuario_id=$usuario_id";
    if (mysqli_query($conexao, $sql)) {
        header('Location: index.php?msg=Gasto atualizado com sucesso!');
    } else {
        header("Location: editar.php?id=$id&erro=Erro ao atualizar: " . mysqli_error($conexao));
    }
    exit();
}

mysqli_close($conexao);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Gasto</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #2d3748; padding: 24px; }
        .container { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 32px; }
        h1 { font-size: 20px; font-weight: 600; margin-bottom: 20px; color: #1a202c; }
        label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 5px; color: #4a5568; }
        input, select { width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
        input:focus, select:focus { outline: none; border-color: #5b7ffa; box-shadow: 0 0 0 3px rgba(91,127,250,0.1); }
        .campo { margin-bottom: 16px; }
        .checkbox-label { display: flex; align-items: center; gap: 8px; font-weight: 400; cursor: pointer; }
        .checkbox-label input { width: auto; }
        .actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn { padding: 9px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn-primary { background: #f7b731; color: #1a202c; }
        .btn-primary:hover { background: #e0a820; }
        .btn-secondary { background: #edf2f7; color: #4a5568; }
        .btn-secondary:hover { background: #e2e8f0; }
        .erro { background: #fff5f5; color: #e53e3e; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; border: 1px solid #fed7d7; }
        #recorrencia_div { margin-top: 8px; padding: 12px; background: #f7f8fc; border-radius: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Editar Gasto</h1>

    <?php if (isset($_GET['erro'])): ?>
        <div class="erro"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="campo">
            <label for="descricao">Descricao</label>
            <input type="text" name="descricao" id="descricao" value="<?php echo htmlspecialchars($gasto['descricao']); ?>" required>
        </div>
        <div class="campo">
            <label for="valor">Valor (R$)</label>
            <input type="text" name="valor" id="valor" value="<?php echo number_format($gasto['valor'], 2, ',', '.'); ?>" required>
        </div>
        <div class="campo">
            <label for="categoria">Categoria</label>
            <select name="categoria" id="categoria" required>
                <option value="Alimentacao" <?php echo $gasto['categoria'] == 'Alimentacao' ? 'selected' : ''; ?>>Alimentacao</option>
                <option value="Transporte" <?php echo $gasto['categoria'] == 'Transporte' ? 'selected' : ''; ?>>Transporte</option>
                <option value="Moradia" <?php echo $gasto['categoria'] == 'Moradia' ? 'selected' : ''; ?>>Moradia</option>
                <option value="Saude" <?php echo $gasto['categoria'] == 'Saude' ? 'selected' : ''; ?>>Saude</option>
                <option value="Educacao" <?php echo $gasto['categoria'] == 'Educacao' ? 'selected' : ''; ?>>Educacao</option>
                <option value="Lazer" <?php echo $gasto['categoria'] == 'Lazer' ? 'selected' : ''; ?>>Lazer</option>
                <option value="Vestuario" <?php echo $gasto['categoria'] == 'Vestuario' ? 'selected' : ''; ?>>Vestuario</option>
                <option value="Contas" <?php echo $gasto['categoria'] == 'Contas' ? 'selected' : ''; ?>>Contas</option>
                <option value="Outros" <?php echo $gasto['categoria'] == 'Outros' ? 'selected' : ''; ?>>Outros</option>
            </select>
        </div>
        <div class="campo">
            <label for="conta">Conta</label>
            <select name="conta" id="conta">
                <option value="Carteira" <?php echo $gasto['conta'] == 'Carteira' ? 'selected' : ''; ?>>Carteira</option>
                <option value="Credito" <?php echo $gasto['conta'] == 'Credito' ? 'selected' : ''; ?>>Credito</option>
                <option value="Debito" <?php echo $gasto['conta'] == 'Debito' ? 'selected' : ''; ?>>Debito</option>
            </select>
        </div>
        <div class="campo">
            <label for="data">Data</label>
            <input type="date" name="data" id="data" value="<?php echo $gasto['data']; ?>" required>
        </div>
        <div class="campo">
            <label class="checkbox-label">
                <input type="checkbox" name="recorrente" id="recorrente" value="1" <?php echo $gasto['recorrente'] ? 'checked' : ''; ?> onchange="document.getElementById('recorrencia_div').style.display = this.checked ? 'block' : 'none';">
                Gasto recorrente
            </label>
        </div>
        <div id="recorrencia_div" style="<?php echo $gasto['recorrente'] ? 'block' : 'none'; ?>">
            <label for="recorrencia_tipo">Repetir</label>
            <select name="recorrencia_tipo" id="recorrencia_tipo">
                <option value="mensal" <?php echo $gasto['recorrencia_tipo'] == 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                <option value="semanal" <?php echo $gasto['recorrencia_tipo'] == 'semanal' ? 'selected' : ''; ?>>Semanal</option>
                <option value="quinzenal" <?php echo $gasto['recorrencia_tipo'] == 'quinzenal' ? 'selected' : ''; ?>>Quinzenal</option>
                <option value="anual" <?php echo $gasto['recorrencia_tipo'] == 'anual' ? 'selected' : ''; ?>>Anual</option>
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="btn btn-primary">Atualizar</button>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>
    </form>

    <script>
    document.getElementById('recorrencia_div').style.display = document.getElementById('recorrente').checked ? 'block' : 'none';
    </script>
</div>
</body>
</html>
