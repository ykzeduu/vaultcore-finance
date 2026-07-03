<?php 
require 'config.php';

// --- 1. NAVEGAÇÃO ---
$mes_selecionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
$timestamp = strtotime($mes_selecionado . "-01");
$mes_anterior = date('Y-m', strtotime("-1 month", $timestamp));
$mes_proximo = date('Y-m', strtotime("+1 month", $timestamp));
$ultimo_dia_mes = date('Y-m-t', $timestamp);

// --- 2. EXCLUIR ---
if (isset($_GET['excluir'])) {
    $id_excluir = $_GET['excluir'];
    $stmt = $pdo->prepare("DELETE FROM lancamentos WHERE id = ?");
    $stmt->execute([$id_excluir]);

    // Redireciona mantendo o mês que estava selecionado
    header("Location: index.php?mes=$mes_selecionado"); 
    exit();
}

// --- 3. SALVAR ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['valor'])) {
    $conta = $_POST['conta'];
    $valor_input = (float)$_POST['valor'];
    $parcelas = ($conta == 'cartao') ? (int)$_POST['parcelas'] : 1;
    $modo_valor = $_POST['modo_valor'] ?? 'total';
    $data_base = $_POST['data'];
    
    $valor_cada_parcela = ($modo_valor == 'total') ? ($valor_input / $parcelas) : $valor_input;
    $tipo = ($conta == 'cartao') ? 'saida' : $_POST['tipo'];

    for ($i = 0; $i < $parcelas; $i++) {
        $fatura_mes = date('Y-m', strtotime("+$i month", strtotime($data_base)));
        $data_registro = date('Y-m-d', strtotime("+$i month", strtotime($data_base)));
        
        $desc_parcela = ($parcelas > 1) ? $_POST['categoria'] . " (" . ($i+1) . "/$parcelas)" : $_POST['categoria'];

        $stmt = $pdo->prepare("INSERT INTO lancamentos (tipo, categoria, valor, data_lancamento, conta, fatura_mes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tipo, $desc_parcela, $valor_cada_parcela, $data_registro, $conta, $fatura_mes]);
    }
    header("Location: index.php?mes=$mes_selecionado"); exit();
}

// --- 4. CÁLCULOS ---
function calcEntradaAte($pdo, $conta, $data_limite) {
    $stmt = $pdo->prepare("SELECT SUM(valor) FROM lancamentos WHERE conta = ? AND tipo = 'entrada' AND data_lancamento <= ?");
    $stmt->execute([$conta, $data_limite]);
    return $stmt->fetchColumn() ?: 0;
}

function calcSaidaAte($pdo, $conta, $data_limite) {
    $stmt = $pdo->prepare("SELECT SUM(valor) FROM lancamentos WHERE conta = ? AND tipo = 'saida' AND data_lancamento <= ?");
    $stmt->execute([$conta, $data_limite]);
    return $stmt->fetchColumn() ?: 0;
}

function calcCartaoAcumuladoAte($pdo, $mes_limite) {
    $stmt = $pdo->prepare("SELECT SUM(valor) FROM lancamentos WHERE conta = 'cartao' AND fatura_mes <= ?");
    $stmt->execute([$mes_limite]);
    return $stmt->fetchColumn() ?: 0;
}

function calcBrutoMes($pdo, $conta, $mes) {
    $stmt = $pdo->prepare("SELECT SUM(valor) FROM lancamentos WHERE conta = ? AND tipo = 'entrada' AND data_lancamento LIKE ?");
    $stmt->execute([$conta, "$mes%"]);
    return $stmt->fetchColumn() ?: 0;
}

$bruto_salario = calcBrutoMes($pdo, 'salario', $mes_selecionado);
$bruto_vale = calcBrutoMes($pdo, 'vale', $mes_selecionado);

$cartao_ate_agora = calcCartaoAcumuladoAte($pdo, $mes_selecionado);

$saldo_salario = calcEntradaAte($pdo, 'salario', $ultimo_dia_mes) 
                 - calcSaidaAte($pdo, 'salario', $ultimo_dia_mes) 
                 - $cartao_ate_agora;

$saldo_vale = calcEntradaAte($pdo, 'vale', $ultimo_dia_mes) 
              - calcSaidaAte($pdo, 'vale', $ultimo_dia_mes);

$saldo_total_mes = $saldo_salario + $saldo_vale;

$stmt = $pdo->prepare("SELECT SUM(valor) FROM lancamentos WHERE conta = 'cartao' AND fatura_mes = ?");
$stmt->execute([$mes_selecionado]);
$fatura_mes_atual = $stmt->fetchColumn() ?: 0;

// --- 5. LISTAGEM ---
$lista = $pdo->prepare("SELECT * FROM lancamentos WHERE (data_lancamento LIKE ? OR fatura_mes = ?) ORDER BY data_lancamento DESC");
$lista->execute(["$mes_selecionado%", $mes_selecionado]);
$resultados = $lista->fetchAll();

$meses_pt = ['January'=>'JANEIRO','February'=>'FEVEREIRO','March'=>'MARÇO','April'=>'ABRIL','May'=>'MAIO','June'=>'JUNHO','July'=>'JULHO','August'=>'AGOSTO','September'=>'SETEMBRO','October'=>'OUTUBRO','November'=>'NOVEMBRO','December'=>'DEZEMBRO'];
$mes_nome = $meses_pt[date('F', $timestamp)];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanças VaultCore 💎✨</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap'); body { font-family: 'Plus Jakarta Sans', sans-serif; } </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

    <div class="max-w-6xl mx-auto p-4 md:p-8">
        
        <header class="mb-10 flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h1 class="text-3xl font-extrabold text-indigo-600 tracking-tight">Finanças VaultCore 💎✨</h1>
                <p class="text-slate-400 font-medium italic">Controle de finanças rápido e fácil</p>
            </div>
            
            <div class="flex items-center bg-white shadow-xl shadow-slate-200/50 rounded-2xl p-1.5 border border-slate-100">
                <a href="?mes=<?= $mes_anterior ?>" class="w-10 h-10 flex items-center justify-center hover:bg-slate-50 rounded-xl transition text-indigo-600"><i class="fa-solid fa-chevron-left"></i></a>
                <span class="px-6 font-bold text-slate-700 min-w-[180px] text-center uppercase tracking-wide"><?= $mes_nome ?> / <?= date('Y', $timestamp) ?></span>
                <a href="?mes=<?= $mes_proximo ?>" class="w-10 h-10 flex items-center justify-center hover:bg-slate-50 rounded-xl transition text-indigo-600"><i class="fa-solid fa-chevron-right"></i></a>
            </div>
        </header>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-12">
            <div class="bg-white p-5 rounded-[28px] shadow-sm border-b-4 border-slate-200 opacity-60">
                <p class="text-[9px] uppercase font-extrabold text-slate-400 tracking-widest mb-1">💰 Salário Bruto</p>
                <p class="text-xl font-black text-slate-400">R$ <?= number_format($bruto_salario, 2, ',', '.') ?></p>
            </div>
            <div class="bg-white p-5 rounded-[28px] shadow-sm border-b-4 border-blue-500">
                <p class="text-[9px] uppercase font-extrabold text-slate-400 tracking-widest mb-1">🏦 Salário Resta</p>
                <p class="text-xl font-black text-slate-800">R$ <?= number_format($saldo_salario, 2, ',', '.') ?></p>
            </div>
            <div class="bg-white p-5 rounded-[28px] shadow-sm border-b-4 border-slate-200 opacity-60">
                <p class="text-[9px] uppercase font-extrabold text-slate-400 tracking-widest mb-1">🥗 Vale Bruto</p>
                <p class="text-xl font-black text-slate-400">R$ <?= number_format($bruto_vale, 2, ',', '.') ?></p>
            </div>
            <div class="bg-white p-5 rounded-[28px] shadow-sm border-b-4 border-orange-400">
                <p class="text-[9px] uppercase font-extrabold text-slate-400 tracking-widest mb-1">🍱 Vale Resta</p>
                <p class="text-xl font-black text-slate-800">R$ <?= number_format($saldo_vale, 2, ',', '.') ?></p>
            </div>
            <div class="bg-white p-5 rounded-[28px] shadow-sm border-b-4 border-rose-500">
                <p class="text-[9px] uppercase font-extrabold text-slate-400 tracking-widest mb-1">💳 Cartão (Mês)</p>
                <p class="text-xl font-black text-rose-600">R$ <?= number_format($fatura_mes_atual, 2, ',', '.') ?></p>
            </div>
            <div class="bg-indigo-600 p-5 rounded-[28px] shadow-xl shadow-indigo-200 border-b-4 border-indigo-900">
                <p class="text-[9px] uppercase font-extrabold text-indigo-200 tracking-widest mb-1">💎 Saldo Total</p>
                <p class="text-xl font-black text-white">R$ <?= number_format($saldo_total_mes, 2, ',', '.') ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="bg-white p-8 rounded-[40px] shadow-sm border border-slate-100 h-fit">
                <h2 class="text-xl font-extrabold mb-6 flex items-center gap-3 text-indigo-600">
                    <i class="fa-solid fa-circle-plus"></i> Novo Lançamento
                </h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Onde foi?</label>
                        <select name="conta" id="conta_select" onchange="toggleCartaoLógica()" class="w-full p-3 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-100 outline-none font-bold text-sm">
                            <option value="salario">💰 Salário</option>
                            <option value="vale">🥗 Vale</option>
                            <option value="cartao">💳 Cartão de Crédito</option>
                        </select>
                    </div>

                    <div id="div_tipo">
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Tipo</label>
                        <select name="tipo" class="w-full p-3 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-100 outline-none font-bold text-sm">
                            <option value="entrada">Entrada ✅</option>
                            <option value="saida">Saída ❌</option>  
                        </select>
                    </div>

                    <div id="campos_cartao" class="hidden space-y-4 p-4 bg-rose-50/50 rounded-3xl border border-rose-100">
                        <div>
                            <label class="text-[10px] font-bold text-rose-400 uppercase ml-2">O valor digitado é:</label>
                            <select name="modo_valor" class="w-full p-2 bg-white rounded-xl border border-rose-100 outline-none font-bold text-xs text-rose-600">
                                <option value="total">O valor Total (Dividir)</option>
                                <option value="unitario">O valor de Cada Parcela</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-rose-400 uppercase ml-2">Número de Parcelas</label>
                            <input type="number" name="parcelas" value="1" min="1" class="w-full p-3 bg-white rounded-2xl border-2 border-rose-100 outline-none font-bold text-rose-600">
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Descrição</label>
                        <input type="text" name="categoria" placeholder="Ex: Mercado..." class="w-full p-3 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-100 outline-none" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Valor (R$)</label>
                        <input type="number" step="0.01" name="valor" placeholder="0,00" class="w-full p-3 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-100 outline-none font-black text-xl" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase ml-2">Data / Início</label>
                        <input type="date" name="data" value="<?= date('Y-m-d') ?>" class="w-full p-3 bg-slate-50 rounded-2xl border-2 border-transparent focus:border-indigo-100 outline-none font-bold text-slate-500" required>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white p-4 rounded-2xl font-extrabold hover:bg-indigo-700 transition-all">Salvar</button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-[40px] shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-50 flex justify-between items-center">
                    <h3 class="font-extrabold text-slate-700 uppercase tracking-tighter italic">Extrato</h3>
                </div>
                <table class="w-full text-left">
                    <tbody class="divide-y divide-slate-50">
                        <?php foreach($resultados as $item): ?>
                        <tr class="hover:bg-slate-50 transition-all">
                            <td class="p-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $item['tipo'] == 'entrada' ? 'bg-emerald-50 text-emerald-500' : 'bg-rose-50 text-rose-500' ?>">
                                        <i class="fa-solid <?= $item['tipo'] == 'entrada' ? 'fa-plus' : 'fa-minus' ?>"></i>
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-700 block"><?= $item['categoria'] ?></span>
                                        <span class="text-[9px] px-2 py-0.5 rounded <?= $item['conta'] == 'cartao' ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-400' ?> font-black uppercase"><?= $item['conta'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-5 text-right">
                                <p class="font-black <?= $item['tipo'] == 'entrada' ? 'text-emerald-500' : 'text-slate-700' ?>">
                                    R$ <?= number_format($item['valor'], 2, ',', '.') ?>
                                </p>
                                <span class="text-[10px] text-slate-300 font-bold"><?= date('d/m/Y', strtotime($item['data_lancamento'])) ?></span>
                            </td>
                            <td class="p-5 text-center w-16">
                                <a href="?mes=<?= $mes_selecionado ?>&excluir=<?= $item['id'] ?>" onclick="return confirm('Apagar?')" class="text-slate-200 hover:text-rose-500"><i class="fa-solid fa-trash-can"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function toggleCartaoLógica() {
        const conta = document.getElementById('conta_select').value;
        const camposCartao = document.getElementById('campos_cartao');
        const divTipo = document.getElementById('div_tipo');
        if (conta === 'cartao') {
            camposCartao.classList.remove('hidden');
            divTipo.classList.add('hidden');
        } else {
            camposCartao.classList.add('hidden');
            divTipo.classList.remove('hidden');
        }
    }
    toggleCartaoLógica();
    </script>
</body>
</html>