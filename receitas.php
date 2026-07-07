<?php
require_once 'config.php';
redirecionar_se_nao_logado();
$usuario_id = $_SESSION['usuario_id'];

if (isset($_GET['deletar'])) {
    $id = (int)$_GET['deletar'];
    mysqli_query($conexao, "DELETE FROM receitas WHERE id = $id AND usuario_id = $usuario_id");
    header('Location: receitas.php?msg=Receita excluida com sucesso!');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $descricao = mysqli_real_escape_string($conexao, $_POST['descricao']);
    $valor = str_replace(',', '.', $_POST['valor']);
    $data = mysqli_real_escape_string($conexao, $_POST['data']);

    if (empty($descricao) || empty($valor) || empty($data)) {
        header('Location: receitas.php?erro=Todos os campos sao obrigatorios.');
        exit();
    }
    if (!is_numeric($valor) || $valor <= 0) {
        header('Location: receitas.php?erro=O valor deve ser um numero positivo.');
        exit();
    }

    $sql = "INSERT INTO receitas (usuario_id, descricao, valor, data) VALUES ($usuario_id, '$descricao', '$valor', '$data')";
    if (mysqli_query($conexao, $sql)) {
        header('Location: receitas.php?msg=Receita cadastrada com sucesso!');
    } else {
        header('Location: receitas.php?erro=Erro ao cadastrar: ' . mysqli_error($conexao));
    }
    exit();
}

$mes_atual = date('m');
$ano_atual = date('Y');

$total_receitas_mes = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM receitas WHERE usuario_id = $usuario_id AND MONTH(data) = $mes_atual AND YEAR(data) = $ano_atual"))['total'];
$total_gastos_mes = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM gastos WHERE usuario_id = $usuario_id AND MONTH(data) = $mes_atual AND YEAR(data) = $ano_atual"))['total'];

$resultado = mysqli_query($conexao, "SELECT * FROM receitas WHERE usuario_id = $usuario_id ORDER BY data DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Receitas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #2d3748; padding: 24px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 22px; font-weight: 600; color: #1a202c; }
        .header a { font-size: 14px; color: #5b7ffa; text-decoration: none; }
        .header a:hover { text-decoration: underline; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px; }
        .summary-item { background: #f7f8fc; border-radius: 10px; padding: 14px 18px; border: 1px solid #e2e8f0; }
        .summary-item .label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; font-weight: 600; }
        .summary-item .value { font-size: 20px; font-weight: 700; margin-top: 4px; }
        .summary-item .value.positive { color: #3ecf8e; }
        .summary-item .value.negative { color: #e53e3e; }
        .card { background: #f7f8fc; border-radius: 10px; padding: 20px; margin-bottom: 24px; border: 1px solid #e2e8f0; }
        .card h2 { font-size: 15px; font-weight: 600; margin-bottom: 16px; color: #4a5568; }
        label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 5px; color: #4a5568; }
        input { width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
        input:focus { outline: none; border-color: #5b7ffa; box-shadow: 0 0 0 3px rgba(91,127,250,0.1); }
        .campo { margin-bottom: 14px; }
        .btn { padding: 9px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        .btn-primary { background: #3ecf8e; color: #fff; }
        .btn-primary:hover { background: #34b87d; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; font-weight: 600; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f2f5; font-size: 14px; }
        tr:hover td { background: #f7f8fc; }
        .acao a { color: #e53e3e; text-decoration: none; font-size: 13px; font-weight: 500; }
        .acao a:hover { text-decoration: underline; }
        .msg { padding: 10px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
        .msg.success { background: #f0fff4; border: 1px solid #c6f6d5; color: #276749; }
        .msg.error { background: #fff5f5; border: 1px solid #fed7d7; color: #c53030; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Receitas</h1>
        <a href="index.php">&larr; Voltar ao painel</a>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="label">Receitas do mes</div>
            <div class="value positive">R$ <?php echo number_format($total_receitas_mes, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Gastos do mes</div>
            <div class="value negative">R$ <?php echo number_format($total_gastos_mes, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Saldo</div>
            <div class="value <?php echo ($total_receitas_mes - $total_gastos_mes) >= 0 ? 'positive' : 'negative'; ?>">R$ <?php echo number_format($total_receitas_mes - $total_gastos_mes, 2, ',', '.'); ?></div>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="msg success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
        <div class="msg error"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Nova Receita</h2>
        <form method="post">
            <div class="campo">
                <label for="descricao">Descricao</label>
                <input type="text" name="descricao" id="descricao" required>
            </div>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <div class="campo" style="flex:1;">
                    <label for="valor">Valor (R$)</label>
                    <input type="text" name="valor" id="valor" required>
                </div>
                <div class="campo" style="flex:1;">
                    <label for="data">Data</label>
                    <input type="date" name="data" id="data" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </form>
    </div>

    <h2 style="font-size:15px;font-weight:600;margin-bottom:12px;color:#4a5568;">Historico de Receitas</h2>
    <table>
        <tr>
            <th>Descricao</th>
            <th>Valor</th>
            <th>Data</th>
            <th>Acao</th>
        </tr>
        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                    <td>R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
                    <td class="acao"><a href="receitas.php?deletar=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza?')">Excluir</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center;padding:40px;color:#a0aec0;">Nenhuma receita cadastrada.</td></tr>
        <?php endif; ?>
    </table>

    <?php mysqli_close($conexao); ?>
</div>
</body>
</html>
