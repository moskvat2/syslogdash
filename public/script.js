let lastId = 0;
let currentPage = 1;
let totalPages = 1;

const logBody = document.getElementById('logBody');
const searchInput = document.getElementById('searchInput');
const clearSearchBtn = document.getElementById('clearSearchBtn');
const statusBadge = document.getElementById('status');
const refreshIntervalSelect = document.getElementById('refreshInterval');
const limitSelect = document.getElementById('limitSelect');
const pauseButton = document.getElementById('pauseButton');
const firstPageBtn = document.getElementById('firstPageBtn');
const prevPageBtn = document.getElementById('prevPageBtn');
const nextPageBtn = document.getElementById('nextPageBtn');
const pageInfo = document.getElementById('pageInfo');

let isPaused = false;

async function fetchLogs() {
    const search = searchInput.value;
    const isSearching = search.trim() !== "";

    // Se mudou o termo de busca, reseta tudo para a primeira página
    if (search !== searchInput.dataset.lastSearch) {
        lastId = 0;
        currentPage = 1;
        logBody.innerHTML = '';
        searchInput.dataset.lastSearch = search;
    }

    try {
        const limit = parseInt(limitSelect.value, 10) || 50;
        const response = await fetch(`api.php?last_id=${lastId}&search=${encodeURIComponent(search)}&limit=${limit}&page=${currentPage}`);
        const data = await response.json();

        if (data.error) {
            console.error(data.error);
            document.getElementById('statusText').textContent = 'Erro: ' + data.error;
            statusBadge.style.color = '#ef4444';
            statusBadge.querySelector('.pulse-dot').style.backgroundColor = '#ef4444';
            return;
        }

        const logs = data.logs;
        totalPages = data.total_pages;
        currentPage = data.current_page;

        // Atualiza UI da paginação
        pageInfo.textContent = `Página ${currentPage} de ${totalPages}`;
        firstPageBtn.disabled = currentPage <= 1;
        prevPageBtn.disabled = currentPage <= 1;
        nextPageBtn.disabled = currentPage >= totalPages;

        if (!isPaused) {
            document.getElementById('statusText').textContent = isSearching ? 'FILTRADO' : 'LIVE';
            statusBadge.querySelector('.pulse-dot').style.backgroundColor = isSearching ? '#38bdf8' : '#10b981';
            statusBadge.style.color = 'var(--success)';
        }

        if (currentPage > 1) {
            // Histórico: renderiza estaticamente sem animação/reversão
            logBody.innerHTML = '';
            logs.forEach(log => renderLogRow(log, true));
        } else {
            // Tempo real: adiciona novos logs no topo
            if (logs.length > 0) {
                logs.reverse().forEach(log => {
                    if (!document.getElementById(`row-${log.ID}`)) {
                        renderLogRow(log, false);
                    }
                    if (log.ID > lastId) lastId = log.ID;
                });
            }
        }
    } catch (e) {
        console.error('Fetch error:', e);
        document.getElementById('statusText').textContent = 'Erro: Falha no script (' + e.message + ')';
        statusBadge.style.color = '#ef4444';
        statusBadge.querySelector('.pulse-dot').style.backgroundColor = '#ef4444';
    }
}

function renderLogRow(log, isHistorical = false) {
    const row = document.createElement('tr');
    row.id = `row-${log.ID}`;
    if (!isHistorical) row.className = 'new-row';

    const date = new Date(log.ReceivedAt.replace(' ', 'T'));
    const dateStr = isNaN(date.getTime()) ? log.ReceivedAt : date.toLocaleString('pt-BR');

    row.innerHTML = `
        <td class="timestamp">${dateStr}</td>
        <td class="host">${log.FromHost}</td>
        <td><span class="prefix" style="${getPrefixStyle(log.LogPrefix)}">${log.LogPrefix || '-'}</span></td>
        <td class="message">${escapeHtml(log.Message)}</td>
    `;

    if (isHistorical) {
        logBody.appendChild(row);
    } else {
        logBody.insertBefore(row, logBody.firstChild);
        setTimeout(() => row.classList.remove('new-row'), 2000);

        const limit = parseInt(limitSelect.value, 10) || 50;
        // Mantém visualmente apenas o limite atual para performance (vezes 2 de margem para scroll, mas aqui vamos deixar estrito)
        if (logBody.children.length > limit) {
            logBody.removeChild(logBody.lastChild);
        }
    }
}

function getPrefixStyle(prefix) {
    if (!prefix) return '';
    
    const p = prefix.toLowerCase();
    if (p.includes('critical')) {
        return `color: #f97316; background: rgba(249, 115, 22, 0.15); border: 1px solid rgba(249, 115, 22, 0.3);`; // Laranja
    }
    if (p.includes('error') || p.includes('erro')) {
        return `color: #ef4444; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3);`; // Vermelho
    }
    if (p.includes('warning') || p.includes('aviso')) {
        return `color: #eab308; background: rgba(234, 179, 8, 0.15); border: 1px solid rgba(234, 179, 8, 0.3);`; // Amarelo
    }
    if (p.includes('info')) {
        return `color: #10b981; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3);`; // Verde
    }

    let hash = 0;
    for (let i = 0; i < prefix.length; i++) {
        hash = prefix.charCodeAt(i) + ((hash << 5) - hash);
    }
    const h = Math.abs(hash % 360);
    return `color: hsl(${h}, 70%, 70%); background: hsl(${h}, 70%, 15%); border: 1px solid hsl(${h}, 70%, 25%);`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function changePage(delta) {
    let newPage = currentPage + delta;
    if (newPage < 1) newPage = 1;
    if (newPage > totalPages) newPage = totalPages;
    
    if (newPage !== currentPage) {
        currentPage = newPage;
        lastId = 0; 
        logBody.innerHTML = '';
        
        // Pausa automaticamente se sair da página 1
        if (currentPage > 1 && !isPaused) {
            pauseButton.click();
        }
        
        fetchLogs();
    }
}

firstPageBtn.addEventListener('click', () => changePage(1 - currentPage));
prevPageBtn.addEventListener('click', () => changePage(-1));
nextPageBtn.addEventListener('click', () => changePage(1));

let searchTimeout;
searchInput.addEventListener('input', () => {
    // Exibe ou oculta o botão de limpar, se existir
    if (clearSearchBtn) {
        clearSearchBtn.style.display = searchInput.value.length > 0 ? 'block' : 'none';
    }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        lastId = 0;
        currentPage = 1;
        fetchLogs();
    }, 500);
});

if (clearSearchBtn) {
    clearSearchBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearSearchBtn.style.display = 'none';
        lastId = 0;
        currentPage = 1;
        fetchLogs();
        searchInput.focus();
    });
}

limitSelect.addEventListener('change', () => {
    lastId = 0;
    currentPage = 1;
    logBody.innerHTML = '';
    fetchLogs();
});

let fetchIntervalId;

function startInterval() {
    if (fetchIntervalId) clearInterval(fetchIntervalId);
    if (isPaused) return; 
    const ms = parseInt(refreshIntervalSelect.value, 10);
    fetchIntervalId = setInterval(fetchLogs, ms);
}

refreshIntervalSelect.addEventListener('change', startInterval);

pauseButton.addEventListener('click', () => {
    isPaused = !isPaused;
    if (isPaused) {
        if (fetchIntervalId) clearInterval(fetchIntervalId);
        pauseButton.textContent = 'Retomar';
        pauseButton.style.background = 'rgba(239, 68, 68, 0.2)';
        pauseButton.style.borderColor = '#ef4444';
        document.getElementById('statusText').textContent = 'PAUSADO';
        statusBadge.querySelector('.pulse-dot').style.backgroundColor = '#ef4444';
        statusBadge.style.color = '#ef4444';
    } else {
        pauseButton.textContent = 'Pausar';
        pauseButton.style.background = 'rgba(255,255,255,0.1)';
        pauseButton.style.borderColor = 'var(--border-color)';
        statusBadge.style.color = 'var(--success)';
        
        // Se retomar a atualização, força a volta para a página 1 (Tempo Real)
        if (currentPage !== 1) {
            currentPage = 1;
            lastId = 0;
            logBody.innerHTML = '';
        }
        
        fetchLogs(); 
        startInterval(); 
    }
});

startInterval();
fetchLogs();

// Botão Flutuante: Rolar para o topo
const scrollTopBtn = document.getElementById("scrollTopBtn");

window.addEventListener('scroll', () => {
    // Mostra o botão se rolar mais de 200px para baixo
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
        scrollTopBtn.style.opacity = "1";
        scrollTopBtn.style.pointerEvents = "auto";
        scrollTopBtn.style.transform = "translateY(0)";
    } else {
        scrollTopBtn.style.opacity = "0";
        scrollTopBtn.style.pointerEvents = "none";
        scrollTopBtn.style.transform = "translateY(20px)";
    }
});

scrollTopBtn.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});
