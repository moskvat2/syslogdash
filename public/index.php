<?php require_once 'db.php'; checkAuth(); ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MikroTik Syslog Dashboard</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>📊 Syslog Dashboard</h1>
                <p style="color: var(--text-secondary); font-size: 0.875rem;">Monitoramento em tempo real de logs MikroTik</p>
            </div>
            <div id="status" class="status-badge">
                <span class="pulse-dot"></span>
                <span id="statusText">LIVE</span>
                <a href="logout.php" style="color: inherit; margin-left: 10px; opacity: 0.6; text-decoration: none;">Sair</a>
            </div>
        </header>

        <div class="controls" style="align-items: center; flex-wrap: wrap;">
            <div style="position: relative; flex: 1; min-width: 250px;">
                <input type="text" id="searchInput" placeholder="Filtrar por mensagem, host ou prefixo..." style="width: 100%; padding-right: 40px; box-sizing: border-box;">
                <button id="clearSearchBtn" title="Limpar pesquisa" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--danger); cursor: pointer; font-size: 1.2rem; width: 28px; height: 28px; border-radius: 50%; display: none; text-align: center; line-height: 26px; padding: 0; outline: none; transition: all 0.2s; font-weight: bold;">&times;</button>
            </div>
            
            <div id="pagination" style="display: flex; align-items: center; gap: 0.5rem; background: var(--card-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.5rem; border-radius: 0.5rem;">
                <button id="firstPageBtn" class="btn-page" disabled title="Primeira Página">&laquo;&laquo;</button>
                <button id="prevPageBtn" class="btn-page" disabled title="Página Anterior">&laquo;</button>
                <span id="pageInfo" style="color: var(--text-secondary); font-size: 0.875rem; font-weight: 600; padding: 0 0.5rem; white-space: nowrap;">Página 1 de 1</span>
                <button id="nextPageBtn" class="btn-page" disabled title="Próxima Página">&raquo;</button>
            </div>


            <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--card-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.5rem; border-radius: 0.5rem;">
                <span style="color: var(--text-secondary); font-size: 0.875rem; white-space: nowrap; padding-left: 0.5rem;">Exibir:</span>
                <select id="limitSelect" style="background: transparent; border: none; color: white; padding: 0.5rem; outline: none; cursor: pointer;">
                    <option value="10" style="color: black;">10 logs</option>
                    <option value="25" style="color: black;">25 logs</option>
                    <option value="50" style="color: black;" selected>50 logs</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; background: var(--card-bg); border: 1px solid var(--border-color); padding: 0.25rem 0.5rem; border-radius: 0.5rem;">
                <span style="color: var(--text-secondary); font-size: 0.875rem; white-space: nowrap; padding-left: 0.5rem;">Atualização:</span>
                <select id="refreshInterval" style="background: transparent; border: none; color: white; padding: 0.5rem; outline: none; cursor: pointer;">
                    <option value="1000" style="color: black;">1 segundo</option>
                    <option value="3000" style="color: black;" selected>3 segundos</option>
                    <option value="5000" style="color: black;">5 segundos</option>
                    <option value="10000" style="color: black;">10 segundos</option>
                </select>
                <button id="pauseButton" style="background: rgba(255,255,255,0.1); border: 1px solid var(--border-color); color: white; padding: 0.5rem 1rem; border-radius: 0.25rem; cursor: pointer; font-weight: 600; font-size: 0.75rem; transition: all 0.2s;">Pausar</button>
            </div>
        </div>

        <div class="log-container">

            <table>
                <thead>
                    <tr>
                        <th width="15%">Data/Hora</th>
                        <th width="12%">Origem</th>
                        <th width="12%">Prefixo</th>
                        <th>Mensagem</th>
                    </tr>
                </thead>
                <tbody id="logBody">
                    <!-- Logs serão inseridos aqui via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Botão flutuante de Voltar ao Topo -->
    <button id="scrollTopBtn" title="Voltar ao Topo" style="display: none; position: fixed; bottom: 30px; right: 30px; z-index: 100; background: var(--accent-color); color: white; border: none; outline: none; cursor: pointer; width: 50px; height: 50px; border-radius: 50%; font-size: 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.5); transition: background 0.3s, transform 0.2s; display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none;">
        &#8593;
    </button>

    <script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>
