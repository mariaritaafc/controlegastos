<?php
require_once 'config.php';
redirecionar_se_nao_logado();

$usuario_id = $_SESSION['usuario_id'];

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$ordenar = isset($_GET['ordenar']) ? $_GET['ordenar'] : 'data';
$direcao = isset($_GET['direcao']) && strtoupper($_GET['direcao']) == 'ASC' ? 'ASC' : 'DESC';
$busca = isset($_GET['busca']) ? mysqli_real_escape_string($conexao, $_GET['busca']) : '';
$categoria_filtro = isset($_GET['categoria_filtro']) ? mysqli_real_escape_string($conexao, $_GET['categoria_filtro']) : '';
$data_inicio = isset($_GET['data_inicio']) ? mysqli_real_escape_string($conexao, $_GET['data_inicio']) : '';
$data_fim = isset($_GET['data_fim']) ? mysqli_real_escape_string($conexao, $_GET['data_fim']) : '';
$conta_filtro = isset($_GET['conta_filtro']) ? mysqli_real_escape_string($conexao, $_GET['conta_filtro']) : '';
$recorrente_filtro = isset($_GET['recorrente_filtro']) ? (int)$_GET['recorrente_filtro'] : 0;

$where = "WHERE usuario_id = $usuario_id";
if ($busca) $where .= " AND descricao LIKE '%$busca%'";
if ($categoria_filtro) $where .= " AND categoria = '$categoria_filtro'";
if ($data_inicio) $where .= " AND data >= '$data_inicio'";
if ($data_fim) $where .= " AND data <= '$data_fim'";
if ($conta_filtro) $where .= " AND conta = '$conta_filtro'";
if ($recorrente_filtro) $where .= " AND recorrente = 1";

$colunas_validas = ['descricao', 'valor', 'categoria', 'data', 'conta'];
if (!in_array($ordenar, $colunas_validas)) $ordenar = 'data';

$por_pagina = 10;
$offset = ($pagina - 1) * $por_pagina;

$total_result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM gastos $where");
$total_rows = $total_result ? mysqli_fetch_assoc($total_result)['total'] : 0;
$total_paginas = ceil($total_rows / $por_pagina);

$sql = "SELECT * FROM gastos $where ORDER BY $ordenar $direcao LIMIT $offset, $por_pagina";
$resultado = mysqli_query($conexao, $sql);

$mes_atual = date('m');
$ano_atual = date('Y');

$total_gastos_mes = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM gastos WHERE usuario_id = $usuario_id AND MONTH(data) = $mes_atual AND YEAR(data) = $ano_atual"))['total'];
$total_receitas_mes = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM receitas WHERE usuario_id = $usuario_id AND MONTH(data) = $mes_atual AND YEAR(data) = $ano_atual"))['total'];
$saldo = $total_receitas_mes - $total_gastos_mes;

$orcamentos = mysqli_query($conexao, "SELECT * FROM orcamentos WHERE usuario_id = $usuario_id AND mes = $mes_atual AND ano = $ano_atual");

$alertas = [];
if ($orcamentos) {
    while ($orc = mysqli_fetch_assoc($orcamentos)) {
        $cat = $orc['categoria'];
        $gasto_cat = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM gastos WHERE usuario_id = $usuario_id AND categoria = '$cat' AND MONTH(data) = $mes_atual AND YEAR(data) = $ano_atual"))['total'];
        if ($gasto_cat > $orc['limite']) {
            $alertas[] = $cat;
        } elseif ($gasto_cat > $orc['limite'] * 0.8) {
            $alertas[] = $cat;
        }
    }
}

$dados_grafico = [];
$grafico_result = mysqli_query($conexao, "SELECT categoria, SUM(valor) as total FROM gastos WHERE usuario_id = $usuario_id AND MONTH(data) = $mes_atual AND YEAR(data) = $ano_atual GROUP BY categoria ORDER BY total DESC");
if ($grafico_result) {
    while ($g = mysqli_fetch_assoc($grafico_result)) {
        $dados_grafico[] = $g;
    }
}

function link_ordenar($coluna, $label, $ordenar_atual, $direcao_atual) {
    $nova_direcao = ($ordenar_atual == $coluna && $direcao_atual == 'ASC') ? 'DESC' : 'ASC';
    $seta = '';
    if ($ordenar_atual == $coluna) {
        $seta = $direcao_atual == 'ASC' ? ' &#9650;' : ' &#9660;';
    }
    $params = $_GET;
    $params['ordenar'] = $coluna;
    $params['direcao'] = $nova_direcao;
    $params['pagina'] = 1;
    $qs = http_build_query($params);
    return "<a href=\"index.php?$qs\" class=\"sort-link\">$label$seta</a>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Controle de Gastos</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #2d3748; padding: 24px; }
        .container { max-width: 1100px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .header h1 { font-size: 22px; font-weight: 600; color: #1a202c; }
        .user-info { font-size: 14px; color: #718096; }
        .user-info a { color: #e53e3e; text-decoration: none; margin-left: 8px; }
        .nav { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 24px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
        .nav a { padding: 8px 16px; background: #f7f8fc; color: #4a5568; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 500; transition: all 0.2s; }
        .nav a:hover { background: #5b7ffa; color: #fff; }
        .nav a.primary { background: #5b7ffa; color: #fff; }
        .nav a.primary:hover { background: #4a6fe0; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-item { background: #f7f8fc; border-radius: 10px; padding: 16px 20px; border: 1px solid #e2e8f0; }
        .summary-item .label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; font-weight: 600; }
        .summary-item .value { font-size: 22px; font-weight: 700; margin-top: 4px; }
        .summary-item .value.positive { color: #3ecf8e; }
        .summary-item .value.negative { color: #e53e3e; }
        .alertas { margin-bottom: 20px; }
        .alert-item { padding: 10px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 6px; }
        .alert-item.warning { background: #fffff0; border: 1px solid #f6e05e; color: #975a16; }
        .alert-item.danger { background: #fff5f5; border: 1px solid #fed7d7; color: #c53030; }
        .grafico { margin-bottom: 24px; }
        .grafico canvas { max-width: 100%; max-height: 300px; margin: 0 auto; display: block; }
        .filter-form { background: #f7f8fc; border-radius: 10px; padding: 16px 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        .filter-form h3 { font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #4a5568; }
        .filter-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
        .filter-row label { font-size: 13px; color: #4a5568; display: flex; flex-direction: column; gap: 4px; }
        .filter-row input, .filter-row select { padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; }
        .filter-row input:focus, .filter-row select:focus { outline: none; border-color: #5b7ffa; }
        .filter-actions { display: flex; gap: 8px; align-items: flex-end; }
        .btn { padding: 8px 16px; border: none; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn-primary { background: #5b7ffa; color: #fff; }
        .btn-primary:hover { background: #4a6fe0; }
        .btn-secondary { background: #edf2f7; color: #4a5568; }
        .btn-secondary:hover { background: #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; font-weight: 600; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
        th a.sort-link { color: #a0aec0; text-decoration: none; }
        th a.sort-link:hover { color: #5b7ffa; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f2f5; font-size: 14px; }
        tr:hover td { background: #f7f8fc; }
        .acao { white-space: nowrap; }
        .acao a { text-decoration: none; font-size: 13px; font-weight: 500; margin-right: 8px; }
        .acao .editar { color: #5b7ffa; }
        .acao .editar:hover { color: #4a6fe0; text-decoration: underline; }
        .acao .deletar { color: #e53e3e; }
        .acao .deletar:hover { color: #c53030; text-decoration: underline; }
        .paginacao { margin-top: 16px; display: flex; gap: 8px; align-items: center; font-size: 14px; color: #718096; }
        .paginacao a { padding: 6px 12px; background: #f7f8fc; border: 1px solid #e2e8f0; border-radius: 6px; color: #4a5568; text-decoration: none; font-size: 13px; }
        .paginacao a:hover { background: #5b7ffa; color: #fff; border-color: #5b7ffa; }
        .paginacao .atual { padding: 6px 12px; background: #5b7ffa; color: #fff; border-radius: 6px; font-size: 13px; }
        .recorrente-tag { display: inline-block; font-size: 11px; background: #ebf8ff; color: #2b6cb0; padding: 2px 8px; border-radius: 4px; margin-left: 4px; }
        .msg { padding: 10px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; }
        .msg.success { background: #f0fff4; border: 1px solid #c6f6d5; color: #276749; }
        .msg.error { background: #fff5f5; border: 1px solid #fed7d7; color: #c53030; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Controle de Gastos</h1>
        <div class="user-info">
            <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
            <a href="logout.php">Sair</a>
        </div>
    </div>

    <div class="nav">
        <a href="inserir.php" class="primary">+ Novo Gasto</a>
        <a href="receitas.php">Receitas</a>
        <a href="orcamentos.php">Orcamentos</a>
        <a href="relatorio.php">Relatorio</a>
        <a href="exportar.php">Exportar CSV</a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="msg success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
        <div class="msg error"><?php echo htmlspecialchars($_GET['erro']); ?></div>
    <?php endif; ?>

    <div class="summary">
        <div class="summary-item">
            <div class="label">Receitas</div>
            <div class="value positive">R$ <?php echo number_format($total_receitas_mes, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Despesas</div>
            <div class="value negative">R$ <?php echo number_format($total_gastos_mes, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Saldo</div>
            <div class="value <?php echo $saldo >= 0 ? 'positive' : 'negative'; ?>">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></div>
        </div>
    </div>

    <?php if ($saldo < 0): ?>
        <div class="alertas"><div class="alert-item danger">Saldo negativo! Suas despesas superaram as receitas neste mes.</div></div>
    <?php endif; ?>

    <?php if (!empty($alertas)): ?>
        <div class="alertas">
            <?php foreach ($alertas as $cat): ?>
                <div class="alert-item warning">Alerta: orcamento da categoria "<?php echo $cat; ?>" esta no limite.</div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($dados_grafico)): ?>
        <div class="grafico">
            <h3 style="font-size:15px;font-weight:600;margin-bottom:12px;color:#4a5568;">Gastos por Categoria (<?php echo $mes_atual; ?>/<?php echo $ano_atual; ?>)</h3>
            <canvas id="graficoPizza" width="400" height="280"></canvas>
        </div>
        <script>
        var ctx = document.getElementById('graficoPizza').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [<?php foreach ($dados_grafico as $d) { echo "'" . $d['categoria'] . "',"; } ?>],
                datasets: [{
                    data: [<?php foreach ($dados_grafico as $d) { echo $d['total'] . ","; } ?>],
                    backgroundColor: ['#5b7ffa','#3ecf8e','#f7b731','#f46a6a','#a78bfa','#fb923c','#94a3b8','#34d399','#c084fc']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } } } }
        });
        </script>
    <?php endif; ?>

    <div class="filter-form">
        <h3>Filtrar Gastos</h3>
        <form method="get">
            <div class="filter-row">
                <label>Busca <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="descricao..."></label>
                <label>Categoria
                    <select name="categoria_filtro">
                        <option value="">Todas</option>
                        <option value="Alimentacao" <?php echo $categoria_filtro == 'Alimentacao' ? 'selected' : ''; ?>>Alimentacao</option>
                        <option value="Transporte" <?php echo $categoria_filtro == 'Transporte' ? 'selected' : ''; ?>>Transporte</option>
                        <option value="Moradia" <?php echo $categoria_filtro == 'Moradia' ? 'selected' : ''; ?>>Moradia</option>
                        <option value="Saude" <?php echo $categoria_filtro == 'Saude' ? 'selected' : ''; ?>>Saude</option>
                        <option value="Educacao" <?php echo $categoria_filtro == 'Educacao' ? 'selected' : ''; ?>>Educacao</option>
                        <option value="Lazer" <?php echo $categoria_filtro == 'Lazer' ? 'selected' : ''; ?>>Lazer</option>
                        <option value="Vestuario" <?php echo $categoria_filtro == 'Vestuario' ? 'selected' : ''; ?>>Vestuario</option>
                        <option value="Contas" <?php echo $categoria_filtro == 'Contas' ? 'selected' : ''; ?>>Contas</option>
                        <option value="Outros" <?php echo $categoria_filtro == 'Outros' ? 'selected' : ''; ?>>Outros</option>
                    </select>
                </label>
                <label>Conta
                    <select name="conta_filtro">
                        <option value="">Todas</option>
                        <option value="Carteira" <?php echo $conta_filtro == 'Carteira' ? 'selected' : ''; ?>>Carteira</option>
                        <option value="Credito" <?php echo $conta_filtro == 'Credito' ? 'selected' : ''; ?>>Credito</option>
                        <option value="Debito" <?php echo $conta_filtro == 'Debito' ? 'selected' : ''; ?>>Debito</option>
                    </select>
                </label>
                <label>De <input type="date" name="data_inicio" value="<?php echo $data_inicio; ?>"></label>
                <label>Ate <input type="date" name="data_fim" value="<?php echo $data_fim; ?>"></label>
                <label><input type="checkbox" name="recorrente_filtro" value="1" <?php echo $recorrente_filtro ? 'checked' : ''; ?>> So recorrentes</label>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="index.php" class="btn btn-secondary">Limpar</a>
                </div>
            </div>
        </form>
    </div>

    <table>
        <tr>
            <th><?php echo link_ordenar('descricao', 'Descricao', $ordenar, $direcao); ?></th>
            <th><?php echo link_ordenar('valor', 'Valor', $ordenar, $direcao); ?></th>
            <th><?php echo link_ordenar('categoria', 'Categoria', $ordenar, $direcao); ?></th>
            <th><?php echo link_ordenar('conta', 'Conta', $ordenar, $direcao); ?></th>
            <th><?php echo link_ordenar('data', 'Data', $ordenar, $direcao); ?></th>
            <th>Acoes</th>
        </tr>
        <?php if ($resultado && mysqli_num_rows($resultado) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($resultado)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['descricao']); ?><?php echo $row['recorrente'] ? '<span class="recorrente-tag">Recorrente</span>' : ''; ?></td>
                    <td>R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?></td>
                    <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                    <td><?php echo htmlspecialchars($row['conta']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
                    <td class="acao">
                        <a class="editar" href="editar.php?id=<?php echo $row['id']; ?>">Editar</a>
                        <a class="deletar" href="deletar.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza?')">Excluir</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;padding:40px;color:#a0aec0;">Nenhum gasto encontrado.</td></tr>
        <?php endif; ?>
    </table>

    <?php if ($total_paginas > 1): ?>
        <div class="paginacao">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <?php if ($i == $pagina): ?>
                    <span class="atual"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?pagina=<?php echo $i; ?>&ordenar=<?php echo $ordenar; ?>&direcao=<?php echo $direcao; ?>&busca=<?php echo urlencode($busca); ?>&categoria_filtro=<?php echo urlencode($categoria_filtro); ?>&data_inicio=<?php echo urlencode($data_inicio); ?>&data_fim=<?php echo urlencode($data_fim); ?>&conta_filtro=<?php echo urlencode($conta_filtro); ?>&recorrente_filtro=<?php echo $recorrente_filtro; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <span style="margin-left:8px;"><?php echo $total_rows; ?> registro(s)</span>
        </div>
    <?php endif; ?>

    <?php mysqli_close($conexao); ?>
</div>
</body>
</html>
