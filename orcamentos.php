<?php
require_once 'config.php';
redirecionar_se_nao_logado();
$usuario_id = $_SESSION['usuario_id'];

if (isset($_GET['deletar'])) {
    $id = (int)$_GET['deletar'];
    mysqli_query($conexao, "DELETE FROM orcamentos WHERE id = $id AND usuario_id = $usuario_id");
    header('Location: orcamentos.php?msg=Orcamento excluido.');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $categoria = mysqli_real_escape_string($conexao, $_POST['categoria']);
    $limite = str_replace(',', '.', $_POST['limite']);
    $mes = (int)$_POST['mes'];
    $ano = (int)$_POST['ano'];

    if (empty($categoria) || empty($limite) || !$mes || !$ano) {
        header('Location: orcamentos.php?erro=Preencha todos os campos.');
        exit();
    }
    if (!is_numeric($limite) || $limite <= 0) {
        header('Location: orcamentos.php?erro=Limite deve ser um numero positivo.');
        exit();
    }

    $sql = "INSERT INTO orcamentos (usuario_id, categoria, limite, mes, ano) VALUES ($usuario_id, '$categoria', '$limite', $mes, $ano) ON DUPLICATE KEY UPDATE limite = '$limite'";
    if (mysqli_query($conexao, $sql)) {
        header('Location: orcamentos.php?msg=Orcamento salvo com sucesso!');
    } else {
        header('Location: orcamentos.php?erro=Erro: ' . mysqli_error($conexao));
    }
    exit();
}

$mes_atual = date('m');
$ano_atual = date('Y');

$orcamentos = mysqli_query($conexao, "SELECT * FROM orcamentos WHERE usuario_id = $usuario_id AND mes = $mes_atual AND ano = $ano_atual ORDER BY categoria");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orcamentos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #2d3748; padding: 24px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h1 { font-size: 22px; font-weight: 600; color: #1a202c; }
        .header a { font-size: 14px; color: #5b7ffa; text-decoration: none; }
        .header a:hover { text-decoration: underline; }
        .card { background: #f7f8fc; border-radius: 10px; padding: 20px; margin-bottom: 24px; border: 1px solid #e2e8f0; }
        .card h2 { font-size: 15px; font-weight: 600; margin-bottom: 16px; color: #4a5568; }
        label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 5px; color: #4a5568; }
        input, select { width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #5b7ffa; box-shadow: 0 0 0 3px rgba(91,127,250,0.1); }
        .campo { margin-bottom: 14px; }
        .row { display: flex; gap: 12px; }
        .row .campo { flex: 1; }
        .btn { padding: 9px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: background 0.2s; }
        .btn-primary { background: #5b7ffa; color: #fff; }
        .btn-primary:hover { background: #4a6fe0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; font-weight: 600; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f2f5; font-size: 14px; }
        tr:hover td { background: #f7f8fc; }
        .status { font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 12px; display: inline-block; }
        .status-ok { background: #f0fff4; color: #276749; }
        .status-atencao { background: #fffff0; color: #975a16; }
        .status-estouro { background: #fff5f5; color: #c53030; }
        .acao a { color: #e53e3e; text-decoration: none; font-size: 13px; font-weight: 500; }
        .acao a:hover { text-decoration: underline; }
        .msg { padding: 10px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
        .msg.success { background: #f0fff4; border: 1px solid #c6f6d5; color: #276749; }
        .msg.error { background: #fff5f5; border: 1px solid #fed7d7; color: #c53030; }
        .desc { font-size: 14px; color: #718096; margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Orcamentos Mensais</h1>
        <a href="index.php">&larr; Voltar ao painel</a>
    </div>

    <p class="desc">Defina limites de gastos por categoria para <?php echo $mes_atual; ?>/<?php echo $ano_atual; ?>.</p>

    <?php if (isset($_GET['msg'])): ?>
        <div class="msg success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
        <div class="msg error"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Novo Orcamento</h2>
        <form method="post">
            <div class="campo">
                <label for="categoria">Categoria</label>
                <select name="categoria" id="categoria" required>
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
            <div class="row">
                <div class="campo">
                    <label for="limite">Limite (R$)</label>
                    <input type="text" name="limite" id="limite" required>
                </div>
                <div class="campo">
                    <label for="mes">Mes</label>
                    <select name="mes" id="mes">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $mes_atual ? 'selected' : ''; ?>><?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="campo">
                    <label for="ano">Ano</label>
                    <select name="ano" id="ano">
                        <?php for ($a = $ano_atual - 1; $a <= $ano_atual + 2; $a++): ?>
                            <option value="<?php echo $a; ?>" <?php echo $a == $ano_atual ? 'selected' : ''; ?>><?php echo $a; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>

    <h2 style="font-size:15px;font-weight:600;margin-bottom:12px;color:#4a5568;">Orcamentos de <?php echo $mes_atual; ?>/<?php echo $ano_atual; ?></h2>
    <table>
        <tr>
            <th>Categoria</th>
            <th>Limite</th>
            <th>Gasto</th>
            <th>Restante</th>
            <th>Status</th>
            <th>Acao</th>
        </tr>
        <?php if ($orcamentos && mysqli_num_rows($orcamentos) > 0): ?>
            <?php while ($orc = mysqli_fetch_assoc($orcamentos)): ?>
                <?php
                $cat = $orc['categoria'];
                $gasto_cat = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM gastos WHERE usuario_id = $usuario_id AND categoria = '$cat' AND MONTH(data) = $mes_atual AND YEAR(data) = $ano_atual"))['total'];
                $restante = $orc['limite'] - $gasto_cat;
                $percentual = $orc['limite'] > 0 ? ($gasto_cat / $orc['limite']) * 100 : 0;
                $classe_status = $percentual > 100 ? 'status-estouro' : ($percentual > 80 ? 'status-atencao' : 'status-ok');
                $texto_status = $percentual > 100 ? 'Estourado' : ($percentual > 80 ? 'Atencao' : 'OK');
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($orc['categoria']); ?></td>
                    <td>R$ <?php echo number_format($orc['limite'], 2, ',', '.'); ?></td>
                    <td>R$ <?php echo number_format($gasto_cat, 2, ',', '.'); ?></td>
                    <td>R$ <?php echo number_format($restante, 2, ',', '.'); ?></td>
                    <td><span class="status <?php echo $classe_status; ?>"><?php echo $texto_status; ?> (<?php echo number_format($percentual, 0); ?>%)</span></td>
                    <td class="acao"><a href="orcamentos.php?deletar=<?php echo $orc['id']; ?>" onclick="return confirm('Tem certeza?')">Remover</a></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#a0aec0;">Nenhum orcamento definido para este mes.</td></tr>
        <?php endif; ?>
    </table>

    <?php mysqli_close($conexao); ?>
</div>
</body>
</html>
