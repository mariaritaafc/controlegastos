<?php
require_once 'config.php';
redirecionar_se_nao_logado();
$usuario_id = $_SESSION['usuario_id'];

$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

$mes_anterior = $mes == 1 ? 12 : $mes - 1;
$ano_anterior = $mes == 1 ? $ano - 1 : $ano;

$total_gastos = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM gastos WHERE usuario_id = $usuario_id AND MONTH(data) = $mes AND YEAR(data) = $ano"))['total'];
$total_receitas = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM receitas WHERE usuario_id = $usuario_id AND MONTH(data) = $mes AND YEAR(data) = $ano"))['total'];

$total_gastos_ant = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM gastos WHERE usuario_id = $usuario_id AND MONTH(data) = $mes_anterior AND YEAR(data) = $ano_anterior"))['total'];
$total_receitas_ant = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COALESCE(SUM(valor),0) as total FROM receitas WHERE usuario_id = $usuario_id AND MONTH(data) = $mes_anterior AND YEAR(data) = $ano_anterior"))['total'];

$gastos_categoria = mysqli_query($conexao, "SELECT categoria, SUM(valor) as total FROM gastos WHERE usuario_id = $usuario_id AND MONTH(data) = $mes AND YEAR(data) = $ano GROUP BY categoria ORDER BY total DESC");

$dados_grafico = [];
$dados_grafico_categorias = [];
if ($gastos_categoria) {
    while ($g = mysqli_fetch_assoc($gastos_categoria)) {
        $dados_grafico[] = $g['total'];
        $dados_grafico_categorias[] = $g['categoria'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatorio Mensal</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #2d3748; padding: 24px; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 22px; font-weight: 600; color: #1a202c; }
        .header a { font-size: 14px; color: #5b7ffa; text-decoration: none; }
        .header a:hover { text-decoration: underline; }
        .seletor { background: #f7f8fc; border-radius: 10px; padding: 14px 18px; margin-bottom: 24px; border: 1px solid #e2e8f0; display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
        .seletor label { font-size: 13px; color: #4a5568; display: flex; flex-direction: column; gap: 4px; }
        .seletor select { padding: 7px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; }
        .seletor select:focus { outline: none; border-color: #5b7ffa; }
        .btn { padding: 7px 16px; border: none; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; background: #5b7ffa; color: #fff; transition: background 0.2s; }
        .btn:hover { background: #4a6fe0; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-item { background: #f7f8fc; border-radius: 10px; padding: 16px 20px; border: 1px solid #e2e8f0; }
        .summary-item .label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; font-weight: 600; }
        .summary-item .value { font-size: 22px; font-weight: 700; margin-top: 4px; }
        .summary-item .value.positive { color: #3ecf8e; }
        .summary-item .value.negative { color: #e53e3e; }
        h2 { font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #4a5568; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #a0aec0; font-weight: 600; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f0f2f5; font-size: 14px; }
        tr:hover td { background: #f7f8fc; }
        .grafico { margin-bottom: 24px; }
        .grafico canvas { max-width: 100%; max-height: 280px; margin: 0 auto; display: block; }
        .section { margin-bottom: 28px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Relatorio Mensal</h1>
        <a href="index.php">&larr; Voltar ao painel</a>
    </div>

    <div class="seletor">
        <form method="get" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <label>Mes
                <select name="mes">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $m == $mes ? 'selected' : ''; ?>><?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label>Ano
                <select name="ano">
                    <?php for ($a = date('Y') - 3; $a <= date('Y') + 1; $a++): ?>
                        <option value="<?php echo $a; ?>" <?php echo $a == $ano ? 'selected' : ''; ?>><?php echo $a; ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <button type="submit" class="btn">Ver</button>
        </form>
    </div>

    <div class="summary">
        <div class="summary-item">
            <div class="label">Receitas</div>
            <div class="value positive">R$ <?php echo number_format($total_receitas, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Despesas</div>
            <div class="value negative">R$ <?php echo number_format($total_gastos, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item">
            <div class="label">Saldo</div>
            <div class="value <?php echo ($total_receitas - $total_gastos) >= 0 ? 'positive' : 'negative'; ?>">R$ <?php echo number_format($total_receitas - $total_gastos, 2, ',', '.'); ?></div>
        </div>
    </div>

    <div class="section">
        <h2>Comparacao com mes anterior (<?php echo str_pad($mes_anterior, 2, '0', STR_PAD_LEFT); ?>/<?php echo $ano_anterior; ?>)</h2>
        <table>
            <tr>
                <th></th>
                <th>Mes Atual</th>
                <th>Mes Anterior</th>
                <th>Diferenca</th>
            </tr>
            <tr>
                <td><b>Receitas</b></td>
                <td>R$ <?php echo number_format($total_receitas, 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($total_receitas_ant, 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($total_receitas - $total_receitas_ant, 2, ',', '.'); ?></td>
            </tr>
            <tr>
                <td><b>Despesas</b></td>
                <td>R$ <?php echo number_format($total_gastos, 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($total_gastos_ant, 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($total_gastos - $total_gastos_ant, 2, ',', '.'); ?></td>
            </tr>
            <tr>
                <td><b>Saldo</b></td>
                <td>R$ <?php echo number_format($total_receitas - $total_gastos, 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format($total_receitas_ant - $total_gastos_ant, 2, ',', '.'); ?></td>
                <td>R$ <?php echo number_format(($total_receitas - $total_gastos) - ($total_receitas_ant - $total_gastos_ant), 2, ',', '.'); ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Gastos por Categoria</h2>
        <?php if (!empty($dados_grafico)): ?>
            <div class="grafico">
                <canvas id="graficoPizza" width="400" height="280"></canvas>
            </div>
            <script>
            var ctx = document.getElementById('graficoPizza').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: [<?php foreach ($dados_grafico_categorias as $c) { echo "'$c',"; } ?>],
                    datasets: [{
                        data: [<?php echo implode(',', $dados_grafico); ?>],
                        backgroundColor: ['#5b7ffa','#3ecf8e','#f7b731','#f46a6a','#a78bfa','#fb923c','#94a3b8','#34d399','#c084fc']
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { padding: 16, font: { size: 12 } } } } }
            });
            </script>
        <?php endif; ?>
        <table>
            <tr>
                <th>Categoria</th>
                <th>Total</th>
            </tr>
            <?php
            $gastos_categoria->data_seek(0);
            if ($gastos_categoria && mysqli_num_rows($gastos_categoria) > 0):
                while ($g = mysqli_fetch_assoc($gastos_categoria)):
            ?>
                    <tr>
                        <td><?php echo htmlspecialchars($g['categoria']); ?></td>
                        <td>R$ <?php echo number_format($g['total'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="2" style="text-align:center;padding:30px;color:#a0aec0;">Nenhum gasto neste mes.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="section">
        <h2>Gastos do Mes</h2>
        <table>
            <tr>
                <th>Descricao</th>
                <th>Valor</th>
                <th>Categoria</th>
                <th>Conta</th>
                <th>Data</th>
            </tr>
            <?php
            $gastos_mes = mysqli_query($conexao, "SELECT * FROM gastos WHERE usuario_id = $usuario_id AND MONTH(data) = $mes AND YEAR(data) = $ano ORDER BY data DESC");
            if ($gastos_mes && mysqli_num_rows($gastos_mes) > 0):
                while ($row = mysqli_fetch_assoc($gastos_mes)):
            ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                        <td>R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                        <td><?php echo htmlspecialchars($row['conta']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($row['data'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;padding:30px;color:#a0aec0;">Nenhum gasto neste mes.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <?php mysqli_close($conexao); ?>
</div>
</body>
</html>
