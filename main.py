import socket
import mysql.connector
from mysql.connector import errorcode
import logging
import re
import sys
import threading
import queue
import time
from datetime import datetime

# ==========================================
# CONFIGURAÇÃO DO SISTEMA
# ==========================================
class Config:
    # Banco de Dados
    DB_HOST = '127.0.0.1'
    DB_USER = 'rsyslog'
    DB_PASS = 'rsyslog'
    DB_NAME = 'Syslog'
    
    # Rede
    BIND_IP = '0.0.0.0'
    BIND_PORT = 514
    
    # Filtros (Vazio = permite todos)
    # Ex: ALLOWED_IPS = ['172.16.0.1', '172.16.0.148']
    ALLOWED_IPS = [] 
    
    # Performance
    QUEUE_SIZE = 10000
    WORKER_THREADS = 2

# Configuração de Logging
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.StreamHandler(sys.stdout),
        logging.FileHandler('debug_server.log', encoding='utf-8')
    ]
)
logger = logging.getLogger("SyslogServer")

# ==========================================
# GESTÃO DE BANCO DE DADOS
# ==========================================
class DatabaseHandler:
    def __init__(self):
        self.connection = None
        self.cursor = None
        self._connect()

    def _connect(self):
        try:
            logger.info(f"🔌 Conectando ao MySQL em {Config.DB_HOST}...")
            self.connection = mysql.connector.connect(
                host=Config.DB_HOST,
                user=Config.DB_USER,
                password=Config.DB_PASS,
                database=Config.DB_NAME,
                autocommit=True
            )
            self.cursor = self.connection.cursor()
            logger.info("✅ Conexão MySQL estabelecida com sucesso.")
        except mysql.connector.Error as err:
            logger.error(f"❌ Erro ao conectar ao MySQL: {err}")
            self.connection = None

    def ensure_connection(self):
        if self.connection is None or not self.connection.is_connected():
            self._connect()
        return self.connection is not None

    def save_log(self, raw_payload, from_host):
        if not self.ensure_connection():
            return False

        try:
            # Parsing simplificado para extração do Prefixo
            # MikroTik costuma enviar: <PRI>Data Hora Hostname Tópicos Prefix: Mensagem
            prefix = self.extract_prefix(raw_payload)
            
            # Limpa a mensagem removendo o cabeçalho syslog padrão se existir
            clean_msg = re.sub(r'^<\d+>.*?\s\S+\s', '', raw_payload).strip()
            
            sql = """INSERT INTO SystemEvents 
                     (Message, FromHost, ReceivedAt, LogPrefix, RawPayload) 
                     VALUES (%s, %s, %s, %s, %s)"""
            
            values = (clean_msg, from_host, datetime.now(), prefix, raw_payload)
            
            self.cursor.execute(sql, values)
            return True
        except mysql.connector.Error as err:
            logger.error(f"⚠️ Erro ao inserir log no banco: {err}")
            if err.errno == errorcode.CR_SERVER_GONE_ERROR:
                self.connection = None # Força reconexão na próxima tentativa
            return False

    def extract_prefix(self, msg):
        """
        Extrai o prefixo de logs MikroTik. 
        Ex: 'firewall,info ICMP_LOG input: ...' -> ICMP_LOG
        """
        # Padrão: Palavra em maiúsculo seguida de 'input:', 'forward:', etc.
        match = re.search(r'([A-Z0-9_-]+)\s(input|forward|output|account)', msg)
        if match:
            return match.group(1)
        
        # Padrão: Prefixos manuais seguidos de dois pontos fora do timestamp
        # Evita pegar o hostname ou data
        parts = msg.split()
        for i, part in enumerate(parts):
            if i > 3 and part.endswith(':'):
                potential = part[:-1]
                if potential.isupper():
                    return potential
        return None

# ==========================================
# PROCESSADOR DE MENSAGENS (WORKER)
# ==========================================
class LogProcessor(threading.Thread):
    def __init__(self, log_queue):
        super().__init__(daemon=True)
        self.queue = log_queue
        self.db = DatabaseHandler()

    def run(self):
        logger.info(f"👷 Worker {self.name} iniciado.")
        while True:
            try:
                data, addr = self.queue.get(timeout=1)
                from_host = addr[0]
                
                try:
                    raw_msg = data.decode('utf-8', errors='ignore').strip()
                    logger.debug(f"📩 [{from_host}] {raw_msg}")
                    
                    if self.db.save_log(raw_msg, from_host):
                        logger.info(f"💾 Log salvo | Host: {from_host}")
                    else:
                        logger.warning(f"❌ Falha ao salvar log de {from_host}")
                        
                except Exception as e:
                    logger.error(f"⚠️ Erro ao processar mensagem: {e}")
                
                self.queue.task_done()
            except queue.Empty:
                continue

# ==========================================
# SERVIDOR UDP (RECEIVER)
# ==========================================
def start_syslog_server():
    log_queue = queue.Queue(maxsize=Config.QUEUE_SIZE)
    
    # Inicia Workers
    for i in range(Config.WORKER_THREADS):
        worker = LogProcessor(log_queue)
        worker.name = f"Worker-{i+1}"
        worker.start()

    # Cria Socket UDP
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.bind((Config.BIND_IP, Config.BIND_PORT))
        logger.info(f"🚀 Servidor Syslog Online | {Config.BIND_IP}:{Config.BIND_PORT}")
    except PermissionError:
        logger.critical("🚫 Erro: Permissão negada para porta 514. Use sudo!")
        return
    except Exception as e:
        logger.critical(f"💥 Erro fatal ao abrir socket: {e}")
        return

    while True:
        try:
            data, addr = sock.recvfrom(8192) # Buffer maior para logs extensos
            sender_ip = addr[0]
            
            # LOG DE DIAGNÓSTICO BRUTO
            logger.debug(f"🔍 SOCKET: Recebido {len(data)} bytes de {sender_ip}")
            
            # Filtro de IP
            if Config.ALLOWED_IPS and sender_ip not in Config.ALLOWED_IPS:
                logger.debug(f"🚫 Ignorado: {sender_ip} não está na lista permitida.")
                continue

            try:
                log_queue.put_nowait((data, addr))
            except queue.Full:
                logger.warning("🔥 Fila cheia! Descartando log.")
                
        except KeyboardInterrupt:
            logger.info("🛑 Servidor sendo finalizado...")
            break
        except Exception as e:
            logger.error(f"⚠️ Erro no loop de recepção: {e}")

if __name__ == "__main__":
    start_syslog_server()
