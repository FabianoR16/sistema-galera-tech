<?php
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verificar login
verificarLogin();

$conn = conectarBD();
$mensagem = '';

// Filtros
$escola_id = isset($_GET['escola_id']) ? intval($_GET['escola_id']) : 0;
$turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : 0;
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$tipo_relatorio = isset($_GET['tipo_relatorio']) ? $_GET['tipo_relatorio'] : 'presenca';

// Buscar escolas para o filtro
$sql_escolas = "SELECT id, nome FROM escolas ORDER BY nome";
$escolas = $conn->query($sql_escolas)->fetch_all(MYSQLI_ASSOC);

// Buscar turmas para o filtro (pode ser filtrado por escola)
$sql_turmas = "SELECT id, nome FROM turmas";
if ($escola_id > 0) {
    $sql_turmas .= " WHERE escola_id = $escola_id";
}
$sql_turmas .= " ORDER BY nome";
$turmas = $conn->query($sql_turmas)->fetch_all(MYSQLI_ASSOC);

// Inicializar arrays de resultados
$dados_relatorio = [];
$colunas = [];

// Processar relatório quando filtros são aplicados
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['filtrar'])) {
    
    // Construir condições SQL baseadas nos filtros
    $condicoes = [];
    $join_turma = "";
    $join_aluno = "";
    
    if ($escola_id > 0) {
        if ($tipo_relatorio == 'presenca') {
            $condicoes[] = "t.escola_id = $escola_id";
            $join_turma = " INNER JOIN turmas t ON a.turma_id = t.id";
        } else {
            $condicoes[] = "t.escola_id = $escola_id";
            $join_turma = " INNER JOIN turmas t ON adv.turma_id = t.id";
        }
    }
    
    if ($turma_id > 0) {
        if ($tipo_relatorio == 'presenca') {
            $condicoes[] = "p.turma_id = $turma_id";
        } else {
            $condicoes[] = "adv.turma_id = $turma_id";
        }
    }
    
    if (!empty($data_inicio)) {
        $data_inicio_formatada = formatarDataBanco($data_inicio);
        if ($tipo_relatorio == 'presenca') {
            $condicoes[] = "a.data >= '$data_inicio_formatada'";
        } else {
            $condicoes[] = "adv.data >= '$data_inicio_formatada'";
        }
    }
    
    if (!empty($data_fim)) {
        $data_fim_formatada = formatarDataBanco($data_fim);
        if ($tipo_relatorio == 'presenca') {
            $condicoes[] = "a.data <= '$data_fim_formatada'";
        } else {
            $condicoes[] = "adv.data <= '$data_fim_formatada'";
        }
    }
    
    $where = count($condicoes) > 0 ? " WHERE " . implode(" AND ", $condicoes) : "";
    
    // Relatório de Presença
    if ($tipo_relatorio == 'presenca') {
        $colunas = ['Aluno', 'Turma', 'Data', 'Aula', 'Presente'];
        
        $sql = "SELECT 
                al.nome as aluno_nome, 
                t.nome as turma_nome, 
                a.data, 
                a.descricao as aula_descricao,
                CASE WHEN p.presente = 1 THEN 'Sim' ELSE 'Não' END as presente
            FROM presencas p
            INNER JOIN alunos al ON p.aluno_id = al.id
            INNER JOIN aulas a ON p.aula_id = a.id
            INNER JOIN turmas t ON a.turma_id = t.id
            $where
            ORDER BY a.data DESC, t.nome, al.nome";
            
    } 
    // Relatório de Advertências
    else if ($tipo_relatorio == 'advertencia') {
        $colunas = ['Aluno', 'Turma', 'Data', 'Tipo', 'Descrição', 'Registrado por'];
        
        $sql = "SELECT 
                al.nome as aluno_nome, 
                t.nome as turma_nome, 
                adv.data, 
                ta.nome as tipo_advertencia,
                adv.descricao,
                u.nome as usuario_nome
            FROM advertencias adv
            INNER JOIN alunos al ON adv.aluno_id = al.id
            INNER JOIN turmas t ON adv.turma_id = t.id
            INNER JOIN tipos_advertencia ta ON adv.tipo_advertencia_id = ta.id
            INNER JOIN usuarios u ON adv.usuario_id = u.id
            $where
            ORDER BY adv.data DESC, t.nome, al.nome";
    }
    
    // Executar consulta
    $result = $conn->query($sql);
    if ($result) {
        $dados_relatorio = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $mensagem = gerarAlerta("Erro ao gerar relatório: " . $conn->error, "danger");
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Relatórios</h5>
    </div>
    <div class="card-body cardtwo">
        <form method="GET" action="relatorios.php" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="tipo_relatorio" class="form-label">Tipo de Relatório</label>
                    <select name="tipo_relatorio" id="tipo_relatorio" class="form-select">
                        <option value="presenca" <?php echo $tipo_relatorio == 'presenca' ? 'selected' : ''; ?>>Presença</option>
                        <option value="advertencia" <?php echo $tipo_relatorio == 'advertencia' ? 'selected' : ''; ?>>Advertências</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="escola_id" class="form-label">Escola</label>
                    <select name="escola_id" id="escola_id" class="form-select">
                        <option value="">Todas as escolas</option>
                        <?php foreach ($escolas as $escola): ?>
                            <option value="<?php echo $escola['id']; ?>" <?php echo $escola_id == $escola['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($escola['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="turma_id" class="form-label">Turma</label>
                    <select name="turma_id" id="turma_id" class="form-select">
                        <option value="">Todas as turmas</option>
                        <?php foreach ($turmas as $turma): ?>
                            <option value="<?php echo $turma['id']; ?>" <?php echo $turma_id == $turma['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($turma['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label">Data Início</label>
                    <input type="text" class="form-control date-mask" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>" placeholder="dd/mm/aaaa">
                </div>
                <div class="col-md-3">
                    <label for="data_fim" class="form-label">Data Fim</label>
                    <input type="text" class="form-control date-mask" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>" placeholder="dd/mm/aaaa">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" name="filtrar" value="1" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <?php if (!empty($dados_relatorio)): ?>
                    <a href="exportar.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Exportar Excel
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
        
        <?php if (!empty($mensagem)) echo $mensagem; ?>
        
        <?php if (!empty($dados_relatorio)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <?php foreach ($colunas as $coluna): ?>
                                <th><?php echo $coluna; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dados_relatorio as $linha): ?>
                            <tr>
                                <?php foreach ($linha as $valor): ?>
                                    <td><?php echo htmlspecialchars($valor); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-muted">Total de registros: <?php echo count($dados_relatorio); ?></p>
        <?php elseif (isset($_GET['filtrar'])): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Nenhum registro encontrado com os filtros selecionados.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Atualizar turmas quando a escola for alterada
document.getElementById('escola_id').addEventListener('change', function() {
    const escolaId = this.value;
    const turmaSelect = document.getElementById('turma_id');
    
    // Limpar turmas atuais
    turmaSelect.innerHTML = '<option value="">Todas as turmas</option>';
    
    if (escolaId) {
        // Fazer requisição AJAX para buscar turmas da escola
        fetch(`turmas_por_escola.php?escola_id=${escolaId}`)
            .then(response => response.json())
            .then(turmas => {
                turmas.forEach(turma => {
                    const option = document.createElement('option');
                    option.value = turma.id;
                    option.textContent = turma.nome;
                    turmaSelect.appendChild(option);
                });
            });
    }
});

// Inicializar máscaras para campos de data
document.addEventListener('DOMContentLoaded', function() {
    const dateMasks = document.querySelectorAll('.date-mask');
    dateMasks.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.substring(0, 8);
            
            if (value.length > 4) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4);
            } else if (value.length > 2) {
                value = value.substring(0, 2) + '/' + value.substring(2);
            }
            
            e.target.value = value;
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>