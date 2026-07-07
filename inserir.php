<?php
require_once 'config.php';
redirecionar_se_nao_logado();
$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
    $valor = str_replace(',', '.', $_POST['valor']);
    $categoria = mysqli_real_escape_string($conexao, $_POST['categoria']);
    $data = mysqli_real_escape_string($conexao, $_POST['data']);
    $conta = mysqli_real_escape_string($conexao, $_POST['conta']);
    $recorrente = isset($_POST['recorrente']) ? 1 : 0;
    $recorrencia_tipo = $recorrente ? mysqli_real_escape_string($conexao, $_POST['recorrencia_tipo']) : NULL;

    if (empty($descricao) || empty($valor) || empty($categoria) || empty($data)) {
        header('Location: inserir.php?erro=Todos os campos sao obrigatorios.');
        exit();
    }
    if (!is_numeric($valor) || $valor <= 0) {
        header('Location: inserir.php?erro=O valor deve ser um numero positivo.');
        exit();
    }

    $sql = "INSERT INTO gastos (usuario_id, descricao, valor, categoria, data, conta, recorrente, recorrencia_tipo) VALUES ($usuario_id, '$descricao', '$valor', '$categoria', '$data', '$conta', $recorrente, " . ($recorrencia_tipo ? "'$recorrencia_tipo'" : "NULL") . ")";
    if (mysqli_query($conexao, $sql)) {
        header('Location: index.php?msg=Gasto cadastrado com sucesso!');
    } else {
        header('Location: inserir.php?erro=Erro ao cadastrar: ' . mysqli_error($conexao));
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Novo Gasto</title>
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
        .btn-primary { background: #5b7ffa; color: #fff; }
        .btn-primary:hover { background: #4a6fe0; }
        .btn-secondary { background: #edf2f7; color: #4a5568; }
        .btn-secondary:hover { background: #e2e8f0; }
        .erro { background: #fff5f5; color: #e53e3e; padding: 10px 14px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; border: 1px solid #fed7d7; }
        #recorrencia_div { margin-top: 8px; padding: 12px; background: #f7f8fc; border-radius: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Novo Gasto</h1>

    <?php if (isset($_GET['erro'])): ?>
        <div class="erro"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="campo">
            <label for="descricao">Descricao</label>
            <input type="text" name="descricao" id="descricao" required>
        </div>
        <div class="campo">
            <label for="valor">Valor (R$)</label>
            <input type="text" name="valor" id="valor" required>
        </div>
        <div class="campo">
            <label for="categoria">Categoria</label>
            <select name="categoria" id="categoria" required>
                <option value="">Selecione...</option>
                <option value="Alimentacao">Alimentacao</option>
                <option value="Transporte">Transporte</option>
                <option value="Moradia">Moradia</option>
                <option value="Saude">Saude</option>
                <option value="Educacao">Educacao</option>
                <option value="Lazer">Lazer</option>
                <option value="Vestuario">Vestuario</option>
                <option value="Contas">Contas</option>
                <option value="Outros">Outros</option>
            </select>
        </div>
        <div class="campo">
            <label for="conta">Conta</label>
            <select name="conta" id="conta">
                <option value="Carteira">Carteira</option>
                <option value="Credito">Credito</option>
                <option value="Debito">Debito</option>
            </select>
        </div>
        <div class="campo">
            <label for="data">Data</label>
            <input type="date" name="data" id="data" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="campo">
            <label class="checkbox-label">
                <input type="checkbox" name="recorrente" id="recorrente" onchange="document.getElementById('recorrencia_div').style.display = this.checked ? 'block' : 'none';">
                Gasto recorrente
            </label>
        </div>
        <div id="recorrencia_div" style="display:none;">
            <label for="recorrencia_tipo">Repetir</label>
            <select name="recorrencia_tipo" id="recorrencia_tipo">
                <option value="mensal">Mensal</option>
                <option value="semanal">Semanal</option>
                <option value="quinzenal">Quinzenal</option>
                <option value="anual">Anual</option>
            </select>
        </div>
        <div class="actions">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="index.php" class="btn btn-secondary">Voltar</a>
        </div>
    </form>
</div>
</body>
</html>
