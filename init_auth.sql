-- --------------------------------------------------------
-- Script de Inicialização Completo do MikroTik Syslog
-- --------------------------------------------------------

-- 1. Criação do Banco de Dados
CREATE DATABASE IF NOT EXISTS Syslog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE Syslog;

-- 2. Criação da Tabela de Logs (SystemEvents)
-- Esta tabela armazena todos os logs recebidos pelo servidor Python.
CREATE TABLE IF NOT EXISTS SystemEvents (
    ID int(10) unsigned NOT NULL AUTO_INCREMENT,
    ReceivedAt datetime DEFAULT current_timestamp(),
    DeviceReportedTime datetime DEFAULT NULL,
    FromHost varchar(60) DEFAULT NULL,
    Facility varchar(20) DEFAULT NULL,
    Priority varchar(20) DEFAULT NULL,
    SysLogTag varchar(60) DEFAULT NULL,
    LogPrefix varchar(100) DEFAULT NULL,
    Message text DEFAULT NULL,
    RawPayload text DEFAULT NULL,
    PRIMARY KEY (ID),
    KEY (FromHost),
    KEY (LogPrefix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Criação da Tabela de Usuários (Users)
-- Tabela para gerenciar o acesso ao painel web.
CREATE TABLE IF NOT EXISTS Users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Inserção do Usuário Padrão
-- Cria o usuário admin com a senha 'admin' (em texto simples) apenas se a tabela estiver vazia
INSERT IGNORE INTO Users (username, password) 
VALUES ('admin', 'admin');
