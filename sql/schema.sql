-- Banco de Dados para Controle Financeiro
-- SQLite

CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    senha_hash TEXT NOT NULL,
    salario REAL DEFAULT 0,
    salario_data TEXT,
    totp_secret TEXT,
    totp_ativo INTEGER DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categorias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    tipo TEXT NOT NULL CHECK (tipo IN ('despesa', 'cartao', 'fixa')),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dividas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_categoria INTEGER,
    credor TEXT NOT NULL,
    valor_total REAL NOT NULL,
    saldo_restante REAL NOT NULL,
    valor_parcela REAL NOT NULL,
    num_parcelas INTEGER NOT NULL,
    parcelas_pagas INTEGER DEFAULT 0,
    data_vencimento DATE NOT NULL,
    status TEXT NOT NULL DEFAULT 'pendente' CHECK (status IN ('pendente', 'paga', 'atrasada')),
    fixa INTEGER DEFAULT 0,
    observacao TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS pagamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_divida INTEGER NOT NULL,
    valor REAL NOT NULL,
    data_pagamento DATE NOT NULL,
    observacao TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_divida) REFERENCES dividas(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cartoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    limite REAL NOT NULL,
    dia_fechamento INTEGER NOT NULL,
    dia_vencimento INTEGER NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS compras_cartao (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_cartao INTEGER NOT NULL,
    descricao TEXT NOT NULL,
    valor REAL NOT NULL,
    num_parcelas INTEGER DEFAULT 1,
    data_compra DATE NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cartao) REFERENCES cartoes(id) ON DELETE CASCADE
);

-- Inserir categorias padrao
INSERT OR IGNORE INTO categorias (id, nome, tipo) VALUES
(1, 'Alimentacao', 'despesa'),
(2, 'Transporte', 'despesa'),
(3, 'Moradia', 'despesa'),
(4, 'Saude', 'despesa'),
(5, 'Educacao', 'despesa'),
(6, 'Lazer', 'despesa'),
(7, 'Assinaturas', 'fixa'),
(8, 'Academia', 'fixa'),
(9, 'Seguros', 'fixa'),
(10, 'Cartao de Credito', 'cartao');

-- O usuario administrador e criado pelo setup.php (primeiro acesso)
