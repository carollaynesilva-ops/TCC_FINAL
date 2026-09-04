

USE b17_42774059_tcc;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('aluno', 'admin') DEFAULT 'aluno',
    nivel INT DEFAULT 1,
    xp INT DEFAULT 0,
    pontuacao_total INT DEFAULT 0,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


CREATE TABLE jogos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    imagem VARCHAR(255),
    ativo BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB;

INSERT INTO jogos (nome, descricao, imagem)
VALUES
(
    'MathChef',
    'Aprenda matemática através de receitas e desafios de frações.',
    'mathchef.png'
),
(
    'MathSpace',
    'Explore o espaço resolvendo desafios matemáticos.',
    'mathspace.png'
);

CREATE TABLE fases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jogo_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    nivel_dificuldade ENUM('facil', 'medio', 'dificil') NOT NULL,
    numero INT NOT NULL,

    FOREIGN KEY (jogo_id)
        REFERENCES jogos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE questoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fase_id INT NOT NULL,
    pergunta TEXT NOT NULL,
    resposta_correta VARCHAR(255) NOT NULL,
    explicacao TEXT NOT NULL,
    pontuacao INT DEFAULT 100,

    FOREIGN KEY (fase_id)
        REFERENCES fases(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE alternativas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    questao_id INT NOT NULL,
    texto VARCHAR(255) NOT NULL,
    correta BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (questao_id)
        REFERENCES questoes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE dicas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    questao_id INT NOT NULL,
    ordem INT NOT NULL,
    texto TEXT NOT NULL,
    custo_xp INT DEFAULT 0,

    FOREIGN KEY (questao_id)
        REFERENCES questoes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE medalhas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    imagem VARCHAR(255),
    criterio TEXT
) ENGINE=InnoDB;


CREATE TABLE usuario_medalhas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    medalha_id INT NOT NULL,
    data_conquista DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (medalha_id)
        REFERENCES medalhas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE (usuario_id, medalha_id)
) ENGINE=InnoDB;

CREATE TABLE progresso_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fase_id INT NOT NULL,
    concluida BOOLEAN DEFAULT FALSE,
    pontuacao INT DEFAULT 0,
    tentativas INT DEFAULT 0,
    melhor_pontuacao INT DEFAULT 0,
    data_conclusao DATETIME NULL,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (fase_id)
        REFERENCES fases(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE (usuario_id, fase_id)
) ENGINE=InnoDB;


CREATE TABLE historico_partidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    jogo_id INT NOT NULL,
    fase_id INT NOT NULL,
    pontuacao INT DEFAULT 0,
    acertos INT DEFAULT 0,
    erros INT DEFAULT 0,
    dicas_usadas INT DEFAULT 0,
    data_partida DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (jogo_id)
        REFERENCES jogos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (fase_id)
        REFERENCES fases(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE respostas_usuario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    questao_id INT NOT NULL,
    partida_id INT NOT NULL,
    resposta VARCHAR(255) NOT NULL,
    correta BOOLEAN NOT NULL,
    tempo_resposta INT,
    usou_dica BOOLEAN DEFAULT FALSE,
    data_resposta DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (questao_id)
        REFERENCES questoes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    FOREIGN KEY (partida_id)
        REFERENCES historico_partidas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO fases
(jogo_id, nome, descricao, nivel_dificuldade, numero)
VALUES
(
    1,
    'Cozinha Básica',
    'Aprenda a identificar e calcular frações simples usando ingredientes.',
    'facil',
    1
),
(
    1,
    'Ingredientes',
    'Calcule quantidades fracionadas de diferentes ingredientes.',
    'medio',
    2
),
(
    1,
    'Aumentando a Receita',
    'Ajuste as quantidades dos ingredientes para preparar receitas maiores.',
    'medio',
    3
),
(
    1,
    'Problemas na Cozinha',
    'Resolva situações envolvendo frações e quantidades que faltam ou sobraram.',
    'dificil',
    4
);

INSERT INTO fases
(jogo_id, nome, descricao, nivel_dificuldade, numero)
VALUES
(
    2,
    'Missão Lua',
    'Resolva desafios envolvendo área e perímetro.',
    'facil',
    1
),
(
    2,
    'Missão Marte',
    'Resolva problemas de razão e proporção.',
    'medio',
    2
),
(
    2,
    'Campo de Asteroides',
    'Resolva expressões numéricas para avançar pelo espaço.',
    'medio',
    3
),
(
    2,
    'Estação Espacial',
    'Resolva equações de primeiro grau para desbloquear a estação.',
    'dificil',
    4
);

INSERT INTO medalhas
(nome, descricao, imagem, criterio)
VALUES
(
    'Primeira Receita',
    'Complete sua primeira receita no MathChef.',
    'primeira_receita.png',
    'Completar a primeira fase do MathChef.'
),
(
    'Chef das Frações',
    'Demonstre domínio nas receitas envolvendo frações.',
    'chef_fracoes.png',
    'Concluir todas as fases do MathChef.'
),
(
    'Sem Derramar',
    'Acerte 5 questões consecutivas sem errar.',
    'sem_derrubar.png',
    'Acertar 5 questões consecutivas.'
),
(
    'Mestre da Cozinha',
    'Complete uma receita sem utilizar nenhuma dica.',
    'mestre_cozinha.png',
    'Concluir uma fase sem utilizar dicas.'
),
(
    'Explorador Espacial',
    'Complete sua primeira missão no MathSpace.',
    'explorador.png',
    'Completar a primeira fase do MathSpace.'
),
(
    'Mestre da Geometria',
    'Demonstre domínio dos desafios de geometria.',
    'mestre_geometria.png',
    'Concluir a missão relacionada à geometria.'
);

ALTER TABLE usuarios
ADD COLUMN foto VARCHAR(255) NULL AFTER senha;

ALTER TABLE usuarios
ADD COLUMN serie INT NULL AFTER tipo,
ADD COLUMN turma CHAR(1) NULL AFTER serie;

ALTER TABLE fases
ADD COLUMN serie INT NOT NULL AFTER jogo_id;