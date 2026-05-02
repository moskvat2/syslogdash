# MikroTik Syslog Dashboard

Um painel profissional, rápido e containerizado para monitoramento em tempo real de logs (syslog) gerados por roteadores MikroTik. 

O sistema é dividido em duas partes principais:
1. **Receptor Python (Backend):** Um servidor UDP super leve que escuta na porta 514, recebe os logs dos roteadores e os armazena no banco de dados.
2. **Dashboard PHP/JS (Frontend):** Uma interface web responsiva e moderna para visualização, filtragem, paginação e monitoramento "Live" dos logs.

---

## 🛠 Pré-requisitos

*   **Docker** e **Docker Compose** instalados no servidor.
*   Um servidor **MySQL** ou **MariaDB** acessível pela rede (pode estar na mesma máquina ou externo).
*   Equipamentos MikroTik (RouterOS) com comunicação de rede para o servidor que hospedará a aplicação.

---

## 🚀 Guia de Implantação (Deployment)

### Passo 1: Configuração do Banco de Dados
1. Acesse o seu servidor MySQL.
2. Execute o conteúdo do arquivo `init_auth.sql` para criar o banco de dados `SyslogDB`, a tabela de logs `SystemEvents` e a tabela de usuários `Users`.
3. Para criar o seu primeiro usuário administrador de acesso ao painel, você pode acessar via navegador o arquivo `setup_auth.php` (quando o painel estiver no ar) ou inserir manualmente o usuário e a senha em texto plano (conforme configurado) na tabela `Users`.

### Passo 2: Configuração de Credenciais
Você precisa conectar a aplicação ao banco de dados recém-criado.

1. **Frontend (PHP):** Abra o arquivo `public/db.php` e altere as credenciais de `$host`, `$dbname`, `$user` e `$pass` para as configurações do seu MySQL.
2. **Backend (Python):** Abra o arquivo `main.py` e altere o dicionário de configuração `db_config` com as credenciais do seu MySQL.

### Passo 3: Iniciando os Contêineres
O projeto já conta com orquestração Docker, o que significa que o ambiente web PHP/Nginx e o ambiente Python serão iniciados simultaneamente.

No terminal, dentro da pasta raiz do projeto, execute:
```bash
docker-compose up -d
```
*Isso fará o download das imagens (se necessário), instalará as dependências do Python e colocará o sistema no ar.*

---

## 📡 Configuração no Roteador MikroTik

Para que os logs comecem a chegar no painel, você deve configurar o seu(s) MikroTik(s) para enviar o Syslog remotamente para o seu servidor.

Acesse o terminal do MikroTik e execute os seguintes comandos (substituindo `IP_DO_SERVIDOR` pelo IP da máquina onde o Docker está rodando):

```routeros
# Define o servidor remoto que vai receber os logs
/system logging action set 3 remote=IP_DO_SERVIDOR remote-port=514

# Define quais tópicos de logs enviar para a ação remota (Ex: todos do tópico "info")
/system logging add action=remote topics=info
```

---

## 💻 Como Acessar e Usar o Sistema

Abra o seu navegador e acesse:
**`http://IP_DO_SERVIDOR:5700`**

*   **Login:** Insira as credenciais do usuário cadastrado na tabela `Users`.
*   **Tempo Real (Live):** Na Página 1, o sistema atualizará automaticamente novos logs de acordo com o intervalo selecionado (1s, 3s, 5s ou 10s).
*   **Busca:** Digite no campo de pesquisa para filtrar logs instantaneamente por IP, Mensagem ou Prefixo.
*   **Cores de Criticidade:**
    *   🔴 `error`, `erro` -> Vermelho
    *   🟠 `critical` -> Laranja
    *   🟡 `warning`, `aviso` -> Amarelo
    *   🟢 `info` -> Verde
*   **Histórico:** Avance pelas páginas de paginação para consultar eventos passados. Ao sair da Página 1, o monitoramento em tempo real é pausado automaticamente para que você consiga ler com calma.
*   **Mobile:** O sistema é totalmente responsivo, suportando gestos de deslize na tabela quando acessado por smartphones ou tablets.

---

## 🛠 Manutenção

**Parar os serviços:**
```bash
docker-compose down
```

**Ver logs de erro do receptor Python:**
```bash
docker logs syslog-server
```

**Ver logs de acesso/erro do Dashboard Web:**
```bash
docker logs web-dashboard
```
*(Nota: O nome do contêiner web pode variar ligeiramente dependendo da pasta local).*
