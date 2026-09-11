

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


ALTER TABLE questoes
ADD COLUMN materia VARCHAR(100) NOT NULL AFTER fase_id;

DELETE FROM fases;

INSERT INTO fases
(jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
VALUES

-- ==========================================
-- MATHCHEF - 6º ANO
-- ==========================================

(1, 6, 'Cozinha Básica',
 'Resolva desafios com números naturais, frações e operações fundamentais.',
 'facil', 1),

(1, 6, 'Ingredientes',
 'Trabalhe com frações, números decimais, porcentagens e unidades de medida.',
 'medio', 2),

(1, 6, 'Aumentando a Receita',
 'Resolva problemas envolvendo operações, medidas e proporções simples.',
 'medio', 3),

(1, 6, 'Problemas na Cozinha',
 'Resolva situações-problema envolvendo diferentes conceitos matemáticos.',
 'dificil', 4),


-- ==========================================
-- MATHCHEF - 7º ANO
-- ==========================================

(1, 7, 'Cozinha Básica',
 'Resolva desafios com números inteiros, números racionais e operações.',
 'facil', 1),

(1, 7, 'Ingredientes',
 'Trabalhe com razão, proporção, porcentagem e medidas.',
 'medio', 2),

(1, 7, 'Aumentando a Receita',
 'Resolva problemas envolvendo expressões algébricas e proporcionalidade.',
 'medio', 3),

(1, 7, 'Problemas na Cozinha',
 'Resolva desafios envolvendo equações, porcentagens, juros e proporcionalidade.',
 'dificil', 4),


-- ==========================================
-- MATHCHEF - 8º ANO
-- ==========================================

(1, 8, 'Cozinha Básica',
 'Resolva desafios com produtos notáveis, fatoração, números irracionais e notação científica.',
 'facil', 1),

(1, 8, 'Ingredientes',
 'Trabalhe com áreas, volumes, porcentagens e sistemas de equações.',
 'medio', 2),

(1, 8, 'Aumentando a Receita',
 'Resolva problemas envolvendo álgebra, geometria e proporcionalidade.',
 'medio', 3),

(1, 8, 'Problemas na Cozinha',
 'Resolva desafios envolvendo diferentes conceitos matemáticos do 8º ano.',
 'dificil', 4),


-- ==========================================
-- MATHCHEF - 9º ANO
-- ==========================================

(1, 9, 'Cozinha Básica',
 'Resolva desafios com potenciação, radiciação, números reais e álgebra.',
 'facil', 1),

(1, 9, 'Ingredientes',
 'Trabalhe com funções, proporcionalidade, medidas e porcentagens.',
 'medio', 2),

(1, 9, 'Aumentando a Receita',
 'Resolva problemas envolvendo equações, funções e grandezas.',
 'medio', 3),

(1, 9, 'Problemas na Cozinha',
 'Resolva desafios avançados envolvendo álgebra, medidas e estatística.',
 'dificil', 4),


-- ==========================================
-- MATHSPACE - 6º ANO
-- ==========================================

(2, 6, 'Missão Lua',
 'Explore a Lua resolvendo desafios com formas geométricas, ângulos e medidas.',
 'facil', 1),

(2, 6, 'Missão Marte',
 'Resolva desafios envolvendo números, medidas e operações.',
 'medio', 2),

(2, 6, 'Campo de Asteroides',
 'Atravesse o campo de asteroides resolvendo operações, padrões e problemas.',
 'medio', 3),

(2, 6, 'Estação Espacial',
 'Resolva desafios envolvendo geometria, medidas, estatística e probabilidade.',
 'dificil', 4),


-- ==========================================
-- MATHSPACE - 7º ANO
-- ==========================================

(2, 7, 'Missão Lua',
 'Explore a Lua resolvendo desafios com polígonos, ângulos e transformações geométricas.',
 'facil', 1),

(2, 7, 'Missão Marte',
 'Resolva desafios envolvendo proporcionalidade, área e volume.',
 'medio', 2),

(2, 7, 'Campo de Asteroides',
 'Atravesse o campo de asteroides utilizando álgebra e operações.',
 'medio', 3),

(2, 7, 'Estação Espacial',
 'Resolva desafios avançados envolvendo geometria, estatística e probabilidade.',
 'dificil', 4),


-- ==========================================
-- MATHSPACE - 8º ANO
-- ==========================================

(2, 8, 'Missão Lua',
 'Explore a Lua utilizando Teorema de Tales, semelhança e construções geométricas.',
 'facil', 1),

(2, 8, 'Missão Marte',
 'Resolva desafios envolvendo áreas, circunferências e volumes.',
 'medio', 2),

(2, 8, 'Campo de Asteroides',
 'Atravesse o campo utilizando álgebra, números irracionais e notação científica.',
 'medio', 3),

(2, 8, 'Estação Espacial',
 'Resolva desafios avançados envolvendo sistemas, geometria, estatística e probabilidade.',
 'dificil', 4),


-- ==========================================
-- MATHSPACE - 9º ANO
-- ==========================================

(2, 9, 'Missão Lua',
 'Explore a Lua utilizando Teorema de Pitágoras e trigonometria.',
 'facil', 1),

(2, 9, 'Missão Marte',
 'Resolva desafios envolvendo proporcionalidade, funções e medidas.',
 'medio', 2),

(2, 9, 'Campo de Asteroides',
 'Atravesse o campo utilizando equações, radiciação e números reais.',
 'medio', 3),

(2, 9, 'Estação Espacial',
 'Resolva desafios avançados envolvendo álgebra, trigonometria, volumes e estatística.',
 'dificil', 4);

 ALTER TABLE questoes
CONVERT TO CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

ALTER TABLE alternativas
CONVERT TO CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

SELECT
    COUNT(*) AS total_questoes
FROM questoes;

USE b17_42774059_tcc;

ALTER TABLE questoes
CONVERT TO CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

ALTER TABLE alternativas
CONVERT TO CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

ALTER TABLE dicas
CONVERT TO CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE b17_42774059_tcc;
SET NAMES utf8mb4;

-- Garante que as tabelas aceitem corretamente acentos e caracteres Unicode.
ALTER TABLE questoes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE alternativas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Regrava as 80 questões para remover os símbolos matemáticos que foram salvos como '?'.
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita precisa de 3 colheres de açúcar e depois mais 2 colheres. Quantas colheres serão usadas ao todo?', resposta_correta='5', explicacao='Somamos as duas quantidades: 3 + 2 = 5 colheres.', pontuacao=100 WHERE id=1;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita tinha 24 ovos e 8 foram usados. Quantos ovos sobraram?', resposta_correta='16', explicacao='Subtraímos os ovos usados: 24 - 8 = 16 ovos.', pontuacao=100 WHERE id=2;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita pede metade de uma xícara de leite. Qual fração representa essa quantidade?', resposta_correta='1/2', explicacao='Metade de um inteiro é representada pela fração 1/2.', pontuacao=100 WHERE id=3;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma pizza foi dividida em 8 partes iguais. Foram usadas 2 partes para uma receita. Qual fração simplificada representa as partes usadas?', resposta_correta='1/4', explicacao='A fração é 2/8. Dividindo numerador e denominador por 2, temos 1/4.', pontuacao=100 WHERE id=4;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita custa R$ 20,00 e você precisa calcular 10% desse valor. Quanto é 10% de R$ 20,00?', resposta_correta='R$ 2,00', explicacao='10% de 20 é igual a 20 dividido por 10, ou seja, 2.', pontuacao=100 WHERE id=5;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita usa 0,5 litro de leite. Qual fração representa 0,5 litro?', resposta_correta='1/2', explicacao='0,5 representa metade de 1 inteiro, portanto equivale a 1/2.', pontuacao=150 WHERE id=6;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Uma receita tinha 2 kg de farinha. Foram usados 750 g. Quantos gramas de farinha sobraram?', resposta_correta='1.250 g', explicacao='2 kg equivalem a 2.000 g. Então 2.000 - 750 = 1.250 g.', pontuacao=150 WHERE id=7;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita já possui 1/2 xícara de ingrediente, mas precisa chegar a 3/4 de xícara. Qual quantidade falta?', resposta_correta='1/4', explicacao='Calculamos 3/4 - 1/2. Como 1/2 = 2/4, temos 3/4 - 2/4 = 1/4.', pontuacao=150 WHERE id=8;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Uma receita precisa de 2 litros de água. Quantos mililitros são necessários?', resposta_correta='2.000 ml', explicacao='Cada litro possui 1.000 ml. Portanto, 2 x 1.000 = 2.000 ml.', pontuacao=150 WHERE id=9;
UPDATE questoes SET materia='Números e Operações', pergunta='Em uma receita há 8,5 xícaras de farinha. Qual é a parte decimal desse número?', resposta_correta='0,5', explicacao='Na escrita decimal 8,5, o número após a vírgula é 0,5.', pontuacao=150 WHERE id=10;
UPDATE questoes SET materia='Proporcionalidade', pergunta='Uma receita para 2 pessoas usa 300 g de farinha. Quantos gramas serão necessários para 4 pessoas?', resposta_correta='600 g', explicacao='Como 4 pessoas representam o dobro de 2, dobramos a quantidade: 300 x 2 = 600 g.', pontuacao=150 WHERE id=11;
UPDATE questoes SET materia='Proporcionalidade', pergunta='Um bolo usa 2 ovos. Quantos ovos serão necessários para preparar 5 bolos iguais?', resposta_correta='10 ovos', explicacao='Cada bolo usa 2 ovos. Então 2 x 5 = 10 ovos.', pontuacao=150 WHERE id=12;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Uma receita usa 250 ml de leite por porção. Quantos mililitros serão usados em 3 porções?', resposta_correta='750 ml', explicacao='Multiplicamos 250 x 3 = 750 ml.', pontuacao=150 WHERE id=13;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita rende 6 porções e cada porção usa 2 colheres de um ingrediente. Quantas colheres serão usadas ao todo?', resposta_correta='12 colheres', explicacao='Multiplicamos 6 porções por 2 colheres: 6 x 2 = 12.', pontuacao=150 WHERE id=14;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita custa R$ 30,00. Para preparar o dobro da quantidade, mantendo o mesmo custo proporcional, quanto será gasto?', resposta_correta='R$ 60,00', explicacao='Dobrar a receita significa dobrar o custo: 30 x 2 = 60.', pontuacao=150 WHERE id=15;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Você tem 3 kg de farinha. Usa 1 kg e depois 750 g. Quanto sobra?', resposta_correta='1,25 kg', explicacao='3 kg = 3.000 g. Depois de retirar 1.000 g e 750 g, sobram 1.250 g, ou 1,25 kg.', pontuacao=200 WHERE id=16;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita tinha 2/3 de xícara de um ingrediente e foram usados 1/3. Quanto restou?', resposta_correta='1/3', explicacao='Subtraindo as frações: 2/3 - 1/3 = 1/3.', pontuacao=200 WHERE id=17;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita usa 25% de 24 morangos. Quantos morangos correspondem a essa quantidade?', resposta_correta='6', explicacao='25% equivale a 1/4. Então 24 dividido por 4 = 6.', pontuacao=200 WHERE id=18;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma pizza foi dividida em 12 partes iguais. Foram usadas 4 partes e depois mais 3. Qual fração representa o que sobrou?', resposta_correta='5/12', explicacao='Foram usadas 7 partes. Como 12 - 7 = 5, sobraram 5/12.', pontuacao=200 WHERE id=19;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Você tem 2 litros de suco e enche 6 copos de 250 ml. Quanto suco sobra?', resposta_correta='500 ml', explicacao='6 x 250 = 1.500 ml. Como 2 litros = 2.000 ml, sobram 500 ml.', pontuacao=200 WHERE id=20;
UPDATE questoes SET materia='Números e Operações', pergunta='A temperatura da cozinha estava em -5 graus Celsius e aumentou 8 graus Celsius. Qual é a nova temperatura?', resposta_correta='3 graus Celsius', explicacao='Somamos -5 + 8 = 3 graus Celsius.', pontuacao=100 WHERE id=21;
UPDATE questoes SET materia='Números e Operações', pergunta='Um freezer estava a -8 graus Celsius e sua temperatura aumentou 12 graus Celsius. Qual é a nova temperatura?', resposta_correta='4 graus Celsius', explicacao='Calculamos -8 + 12 = 4 graus Celsius.', pontuacao=100 WHERE id=22;
UPDATE questoes SET materia='Números e Operações', pergunta='Uma receita usa 3/4 de xícara e outra parte acrescenta 1/4. Qual é o total?', resposta_correta='1', explicacao='3/4 + 1/4 = 4/4 = 1 inteiro.', pontuacao=100 WHERE id=23;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Uma receita usa 1,5 litro de leite. Quantos mililitros isso representa?', resposta_correta='1.500 ml', explicacao='1 litro = 1.000 ml. Então 1,5 litro = 1.500 ml.', pontuacao=100 WHERE id=24;
UPDATE questoes SET materia='Números e Operações', pergunta='Quanto representa 15% de 40 unidades de um ingrediente?', resposta_correta='6', explicacao='15% de 40 = 0,15 x 40 = 6.', pontuacao=100 WHERE id=25;
UPDATE questoes SET materia='Proporcionalidade', pergunta='Uma receita usa 3 ovos para fazer 2 bolos. Quantos ovos serão necessários para fazer 6 bolos iguais?', resposta_correta='9 ovos', explicacao='6 bolos são 3 vezes 2 bolos. Então 3 x 3 = 9 ovos.', pontuacao=150 WHERE id=26;
UPDATE questoes SET materia='Porcentagem', pergunta='Uma receita custa R$ 150,00. Quanto representa 20% desse valor?', resposta_correta='R$ 30,00', explicacao='20% de 150 = 0,20 x 150 = 30.', pontuacao=150 WHERE id=27;
UPDATE questoes SET materia='Proporcionalidade', pergunta='Uma receita usa 4 xícaras para 8 porções. Quantas xícaras serão necessárias para 16 porções?', resposta_correta='8 xícaras', explicacao='16 porções são o dobro de 8, então dobramos 4 para obter 8 xícaras.', pontuacao=150 WHERE id=28;
UPDATE questoes SET materia='Porcentagem', pergunta='Um ingrediente custa R$ 50,00 e sofreu aumento de 10%. Qual é o novo preço?', resposta_correta='R$ 55,00', explicacao='10% de 50 é 5. Somando ao preço original: 50 + 5 = 55.', pontuacao=150 WHERE id=29;
UPDATE questoes SET materia='Porcentagem', pergunta='Um ingrediente custa R$ 80,00 e recebeu 25% de desconto. Qual será o preço final?', resposta_correta='R$ 60,00', explicacao='25% de 80 = 20. Então 80 - 20 = 60.', pontuacao=150 WHERE id=30;
UPDATE questoes SET materia='Álgebra', pergunta='Uma receita usa x ovos por bolo. Para preparar 3 bolos iguais, qual expressão representa a quantidade total de ovos?', resposta_correta='3x', explicacao='Se cada bolo usa x ovos, três bolos usam x + x + x = 3x.', pontuacao=150 WHERE id=31;
UPDATE questoes SET materia='Álgebra', pergunta='Você precisa descobrir a quantidade x de ovos sabendo que x + 7 = 15. Qual é o valor de x?', resposta_correta='8', explicacao='Subtraindo 7 dos dois lados: x = 15 - 7 = 8.', pontuacao=150 WHERE id=32;
UPDATE questoes SET materia='Álgebra', pergunta='Uma receita usa o dobro da quantidade x de um ingrediente e isso resulta em 18. Qual é o valor de x?', resposta_correta='9', explicacao='Temos 2x = 18. Dividindo por 2, x = 9.', pontuacao=150 WHERE id=33;
UPDATE questoes SET materia='Proporcionalidade', pergunta='São usados 20 ovos para fazer 5 bolos. Quantos ovos serão necessários para fazer 8 bolos?', resposta_correta='32 ovos', explicacao='Cada bolo usa 20 dividido por 5 = 4 ovos. Para 8 bolos: 4 x 8 = 32.', pontuacao=150 WHERE id=34;
UPDATE questoes SET materia='Álgebra', pergunta='Se cada unidade de um ingrediente custa R$ 12,00 e você compra x unidades, qual expressão representa o custo total?', resposta_correta='12x', explicacao='O preço de cada unidade é 12 e são compradas x unidades, então o total é 12x.', pontuacao=150 WHERE id=35;
UPDATE questoes SET materia='Juros Simples', pergunta='Um ingrediente custa R$ 100,00 e sofre um acréscimo de 5%. Qual é o valor do acréscimo?', resposta_correta='R$ 5,00', explicacao='5% de 100 = 5. Portanto, o acréscimo é de R$ 5,00.', pontuacao=200 WHERE id=36;
UPDATE questoes SET materia='Álgebra', pergunta='Uma receita precisa de 1.000 g de farinha. Você já colocou 300 g e depois mais x gramas. Se x + 300 = 1.000, qual é o valor de x?', resposta_correta='700 g', explicacao='Subtraindo 300 de 1.000: x = 700 g.', pontuacao=200 WHERE id=37;
UPDATE questoes SET materia='Álgebra', pergunta='Uma receita é representada pela equação 3x - 5 = 16. Qual é o valor de x?', resposta_correta='7', explicacao='Somamos 5 aos dois lados: 3x = 21. Dividindo por 3, x = 7.', pontuacao=200 WHERE id=38;
UPDATE questoes SET materia='Números e Operações', pergunta='Você usa 2/3 de kg de um ingrediente em cada receita. Quantos quilogramas serão usados em 3 receitas?', resposta_correta='2 kg', explicacao='Multiplicamos 2/3 por 3 = 2 kg.', pontuacao=200 WHERE id=39;
UPDATE questoes SET materia='Porcentagem', pergunta='Uma receita possui 250 g de ingrediente e 40% será utilizado. Quantos gramas serão usados?', resposta_correta='100 g', explicacao='40% de 250 = 0,40 x 250 = 100 g.', pontuacao=200 WHERE id=40;
UPDATE questoes SET materia='Álgebra', pergunta='Ao desenvolver a expressão (x + 3) ao quadrado, qual é o resultado?', resposta_correta='x ao quadrado + 6x + 9', explicacao='Usamos o produto notável: (a+b) ao quadrado = a ao quadrado + 2ab + b ao quadrado. Assim, (x+3) ao quadrado = x ao quadrado + 6x + 9.', pontuacao=100 WHERE id=41;
UPDATE questoes SET materia='Álgebra', pergunta='Qual é a fatoração de x ao quadrado + 5x?', resposta_correta='x(x + 5)', explicacao='Colocamos x em evidência: x ao quadrado + 5x = x(x + 5).', pontuacao=100 WHERE id=42;
UPDATE questoes SET materia='Notação Científica', pergunta='Uma receita usa 3 vezes 10 ao quadrado gramas de um ingrediente. Quantos gramas isso representa?', resposta_correta='300 g', explicacao='10 ao quadrado = 100. Então 3 x 100 = 300 g.', pontuacao=100 WHERE id=43;
UPDATE questoes SET materia='Números Irracionais', pergunta='Qual destes números é irracional?', resposta_correta='raiz quadrada de 2', explicacao='A raiz quadrada de 2 não pode ser representada como uma fração de inteiros e possui representação decimal não periódica.', pontuacao=100 WHERE id=44;
UPDATE questoes SET materia='Sistemas Lineares', pergunta='Em um sistema x + y = 10 e x - y = 2, quais são os valores de x e y?', resposta_correta='x = 6 e y = 4', explicacao='Somando as equações, obtemos 2x = 12, então x = 6. Substituindo, y = 4.', pontuacao=100 WHERE id=45;
UPDATE questoes SET materia='Geometria', pergunta='Uma forma circular usada para uma receita tem raio de 5 cm. Usando pi = 3,14, qual é sua área?', resposta_correta='78,5 cm²', explicacao='Área do círculo = pi x raio ao quadrado = 3,14 x 25 = 78,5 cm².', pontuacao=150 WHERE id=46;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Uma forma cilíndrica tem raio 2 cm e altura 5 cm. Usando pi = 3,14, qual é seu volume?', resposta_correta='62,8 cm³', explicacao='Volume = pi x raio ao quadrado x altura = 3,14 x 4 x 5 = 62,8 cm³.', pontuacao=150 WHERE id=47;
UPDATE questoes SET materia='Porcentagem', pergunta='Um ingrediente custa R$ 80,00 e recebe 15% de desconto. Qual será o preço final?', resposta_correta='R$ 68,00', explicacao='15% de 80 = 12. Então 80 - 12 = 68.', pontuacao=150 WHERE id=48;
UPDATE questoes SET materia='Sistemas Lineares', pergunta='Resolva o sistema 2x + y = 10 e x + y = 7. Quais são x e y?', resposta_correta='x = 3 e y = 4', explicacao='Subtraindo a segunda equação da primeira: x = 3. Então 3 + y = 7, logo y = 4.', pontuacao=150 WHERE id=49;
UPDATE questoes SET materia='Porcentagem', pergunta='Uma receita precisa de 30% de 200 g de um ingrediente. Quantos gramas serão utilizados?', resposta_correta='60 g', explicacao='30% de 200 = 0,30 x 200 = 60 g.', pontuacao=150 WHERE id=50;
UPDATE questoes SET materia='Geometria', pergunta='Duas receitas usam medidas proporcionais na razão 2:3. Se uma medida é 8 cm na primeira receita, qual será a correspondente na segunda?', resposta_correta='12 cm', explicacao='Se 2 corresponde a 8, então 1 corresponde a 4. Como a outra medida é 3, temos 3 x 4 = 12 cm.', pontuacao=150 WHERE id=51;
UPDATE questoes SET materia='Geometria', pergunta='Uma forma circular tem raio de 4 cm. Usando pi = 3,14, qual é o comprimento da circunferência?', resposta_correta='25,12 cm', explicacao='Comprimento = 2 x pi x raio = 2 x 3,14 x 4 = 25,12 cm.', pontuacao=150 WHERE id=52;
UPDATE questoes SET materia='Potenciação', pergunta='Uma quantidade de ingrediente pode ser representada por 2 ao cubo vezes 2 ao quadrado. Qual é o resultado?', resposta_correta='32', explicacao='Na multiplicação de potências de mesma base, somamos os expoentes: 2 elevado a 5 = 32.', pontuacao=150 WHERE id=53;
UPDATE questoes SET materia='Notação Científica', pergunta='Como representar 0,00045 em notação científica?', resposta_correta='4,5 vezes 10 elevado a menos 4', explicacao='Movemos a vírgula quatro casas para a direita, ficando 4,5 vezes 10 elevado a menos 4.', pontuacao=150 WHERE id=54;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Um prisma possui área da base de 20 cm² e altura de 10 cm. Qual é seu volume?', resposta_correta='200 cm³', explicacao='Volume do prisma = área da base x altura = 20 x 10 = 200 cm³.', pontuacao=150 WHERE id=55;
UPDATE questoes SET materia='Álgebra', pergunta='Ao desenvolver uma receita, você encontra a equação x ao quadrado - 5x + 6 = 0. Quais são as soluções?', resposta_correta='2 e 3', explicacao='Fatorando: (x - 2)(x - 3) = 0. Portanto, x = 2 ou x = 3.', pontuacao=200 WHERE id=56;
UPDATE questoes SET materia='Geometria', pergunta='Uma forma circular tem raio de 5 cm. Usando pi = 3,14, qual é sua área?', resposta_correta='78,5 cm²', explicacao='Área = pi x raio ao quadrado = 3,14 x 25 = 78,5 cm².', pontuacao=200 WHERE id=57;
UPDATE questoes SET materia='Álgebra', pergunta='Uma quantidade é dada por 2x ao quadrado - 8 = 0. Quais são os valores de x?', resposta_correta='2 e -2', explicacao='2x ao quadrado = 8, então x ao quadrado = 4. Logo x = 2 ou x = -2.', pontuacao=200 WHERE id=58;
UPDATE questoes SET materia='Estatística', pergunta='As quantidades de ingredientes usadas foram 10, 15, 20, 25 e 30. Qual é a média?', resposta_correta='20', explicacao='Somamos os valores: 100. Dividindo por 5, temos média 20.', pontuacao=200 WHERE id=59;
UPDATE questoes SET materia='Probabilidade', pergunta='Uma caixa possui 4 ingredientes diferentes e apenas 1 é o ingrediente correto para uma receita. Qual é a probabilidade de escolher o correto ao acaso?', resposta_correta='1/4', explicacao='Há 1 resultado favorável entre 4 possibilidades, então a probabilidade é 1/4.', pontuacao=200 WHERE id=60;
UPDATE questoes SET materia='Equação do 2º Grau', pergunta='Uma receita gera a equação x ao quadrado - 5x + 6 = 0. Quais são as soluções?', resposta_correta='2 e 3', explicacao='Fatorando: (x - 2)(x - 3) = 0. Assim, x = 2 ou x = 3.', pontuacao=100 WHERE id=61;
UPDATE questoes SET materia='Função', pergunta='O custo de uma receita é C(x) = 5x + 10. Quanto custa preparar uma receita com x = 8 unidades?', resposta_correta='R$ 50,00', explicacao='Substituindo x por 8: C(8) = 5 x 8 + 10 = 50.', pontuacao=100 WHERE id=62;
UPDATE questoes SET materia='Radiciação', pergunta='Qual é a raiz quadrada de 144?', resposta_correta='12', explicacao='12 x 12 = 144, portanto a raiz quadrada de 144 é 12.', pontuacao=100 WHERE id=63;
UPDATE questoes SET materia='Proporcionalidade', pergunta='Uma receita usa 2 litros de um ingrediente para 8 porções. Quantos litros serão necessários para 20 porções?', resposta_correta='5 litros', explicacao='Cada porção usa 2 dividido por 8 = 0,25 litro. Para 20 porções: 20 x 0,25 = 5 litros.', pontuacao=100 WHERE id=64;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Uma forma esférica tem raio de 3 cm. Usando pi = 3,14, qual é aproximadamente o volume?', resposta_correta='113,04 cm³', explicacao='Volume da esfera = 4/3 x pi x raio ao cubo = 4/3 x 3,14 x 27 = 113,04 cm³.', pontuacao=100 WHERE id=65;
UPDATE questoes SET materia='Proporcionalidade', pergunta='Uma nave de entrega percorre 400 km usando 20 litros de combustível. Mantendo a mesma proporção, quantos litros serão necessários para 700 km?', resposta_correta='35 litros', explicacao='A proporção é 400 dividido por 20 = 20 km por litro. Então 700 dividido por 20 = 35 litros.', pontuacao=150 WHERE id=66;
UPDATE questoes SET materia='Equação do 2º Grau', pergunta='Resolva a equação 2x ao quadrado - 8 = 0.', resposta_correta='2 e -2', explicacao='2x ao quadrado = 8, então x ao quadrado = 4. Logo x = 2 ou x = -2.', pontuacao=150 WHERE id=67;
UPDATE questoes SET materia='Função', pergunta='A função de uma receita é f(x) = 2x + 3. Qual é o valor de f(5)?', resposta_correta='13', explicacao='Substituindo x = 5: f(5) = 2 x 5 + 3 = 13.', pontuacao=150 WHERE id=68;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Uma forma de pirâmide possui área da base de 30 cm² e altura de 9 cm. Qual é o volume?', resposta_correta='90 cm³', explicacao='Volume da pirâmide = área da base x altura dividido por 3 = 30 x 9 dividido por 3 = 90 cm³.', pontuacao=150 WHERE id=69;
UPDATE questoes SET materia='Estatística', pergunta='Os resultados foram 4, 6, 7, 7, 8, 10 e 12. Qual é a mediana?', resposta_correta='7', explicacao='Há 7 valores ordenados. O valor central é o quarto, que é 7.', pontuacao=150 WHERE id=70;
UPDATE questoes SET materia='Equação do 2º Grau', pergunta='Resolva x ao quadrado - 7x + 12 = 0.', resposta_correta='3 e 4', explicacao='Fatorando: (x - 3)(x - 4) = 0. Portanto, x = 3 ou x = 4.', pontuacao=150 WHERE id=71;
UPDATE questoes SET materia='Função Quadrática', pergunta='Dada a função f(x) = x ao quadrado - 4x + 3, qual é o valor de f(2)?', resposta_correta='-1', explicacao='f(2) = 2 ao quadrado - 4 x 2 + 3 = 4 - 8 + 3 = -1.', pontuacao=150 WHERE id=72;
UPDATE questoes SET materia='Radiciação', pergunta='Qual é a forma simplificada da raiz quadrada de 50?', resposta_correta='5 vezes raiz quadrada de 2', explicacao='Como 50 = 25 x 2, temos raiz quadrada de 50 = 5 vezes raiz quadrada de 2.', pontuacao=150 WHERE id=73;
UPDATE questoes SET materia='Geometria', pergunta='Um triângulo retângulo possui hipotenusa de 13 cm e um cateto de 5 cm. Qual é o outro cateto?', resposta_correta='12 cm', explicacao='Pelo Teorema de Pitágoras: 5 ao quadrado + x ao quadrado = 13 ao quadrado. Então x ao quadrado = 144 e x = 12.', pontuacao=150 WHERE id=74;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Uma forma esférica tem raio de 3 cm. Usando pi = 3,14, qual é aproximadamente seu volume?', resposta_correta='113,04 cm³', explicacao='Volume da esfera = 4/3 x pi x raio ao cubo = 4/3 x 3,14 x 27 = 113,04 cm³.', pontuacao=150 WHERE id=75;
UPDATE questoes SET materia='Equação do 2º Grau', pergunta='Resolva x ao quadrado - 6x + 8 = 0.', resposta_correta='2 e 4', explicacao='Pela fatoração: (x - 2)(x - 4) = 0. Assim, x = 2 ou x = 4.', pontuacao=200 WHERE id=76;
UPDATE questoes SET materia='Geometria', pergunta='Um triângulo retângulo possui catetos de 9 cm e 12 cm. Qual é a medida da hipotenusa?', resposta_correta='15 cm', explicacao='Pelo Teorema de Pitágoras: 9 ao quadrado + 12 ao quadrado = 81 + 144 = 225. A raiz quadrada de 225 é 15.', pontuacao=200 WHERE id=77;
UPDATE questoes SET materia='Função Quadrática', pergunta='Dada f(x) = x ao quadrado - 3x + 2, qual é f(4)?', resposta_correta='6', explicacao='f(4) = 4 ao quadrado - 3 x 4 + 2 = 16 - 12 + 2 = 6.', pontuacao=200 WHERE id=78;
UPDATE questoes SET materia='Grandezas e Medidas', pergunta='Uma esfera tem raio de 5 cm. Usando pi = 3,14, qual é aproximadamente seu volume?', resposta_correta='523,33 cm³', explicacao='Volume = 4/3 x pi x raio ao cubo = 4/3 x 3,14 x 125, aproximadamente 523,33 cm³.', pontuacao=200 WHERE id=79;
UPDATE questoes SET materia='Estatística', pergunta='Os valores são 12, 15, 15, 18, 20, 22 e 25. Qual é a mediana e a moda?', resposta_correta='Mediana 18 e moda 15', explicacao='A mediana é o valor central, 18. A moda é o valor que mais se repete, 15.', pontuacao=200 WHERE id=80;

-- Remove somente as alternativas atuais. As questões permanecem intactas.
DELETE FROM alternativas;

-- Reinsere as 320 alternativas com texto compatível.
INSERT INTO alternativas (questao_id, texto, correta) VALUES (1, '4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (1, '5', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (1, '6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (1, '7', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (2, '14', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (2, '16', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (2, '18', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (2, '20', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (3, '1/3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (3, '1/2', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (3, '2/3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (3, '3/4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (4, '1/8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (4, '1/4', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (4, '1/2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (4, '2/3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (5, 'R$ 1,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (5, 'R$ 2,00', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (5, 'R$ 5,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (5, 'R$ 10,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (6, '1/4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (6, '1/2', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (6, '2/3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (6, '3/4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (7, '750 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (7, '1.000 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (7, '1.250 g', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (7, '1.500 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (8, '1/8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (8, '1/4', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (8, '1/2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (8, '3/4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (9, '200 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (9, '1.000 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (9, '2.000 ml', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (9, '2.500 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (10, '0,05', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (10, '0,5', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (10, '5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (10, '8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (11, '300 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (11, '450 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (11, '600 g', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (11, '900 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (12, '5 ovos', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (12, '8 ovos', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (12, '10 ovos', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (12, '12 ovos', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (13, '500 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (13, '750 ml', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (13, '1.000 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (13, '1.250 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (14, '6 colheres', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (14, '8 colheres', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (14, '12 colheres', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (14, '14 colheres', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (15, 'R$ 30,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (15, 'R$ 45,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (15, 'R$ 60,00', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (15, 'R$ 90,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (16, '0,75 kg', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (16, '1 kg', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (16, '1,25 kg', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (16, '2,25 kg', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (17, '1/6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (17, '1/3', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (17, '1/2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (17, '2/3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (18, '4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (18, '6', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (18, '8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (18, '10', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (19, '4/12', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (19, '5/12', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (19, '7/12', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (19, '8/12', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (20, '250 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (20, '500 ml', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (20, '750 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (20, '1.000 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (21, '-13 graus Celsius', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (21, '3 graus Celsius', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (21, '8 graus Celsius', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (21, '-3 graus Celsius', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (22, '2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (22, '4', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (22, '8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (22, '20', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (23, '1/4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (23, '1/2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (23, '1', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (23, '3/2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (24, '1.000 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (24, '1.500 ml', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (24, '2.000 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (24, '2.500 ml', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (25, '4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (25, '6', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (25, '8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (25, '10', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (26, '6 ovos', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (26, '9 ovos', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (26, '12 ovos', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (26, '18 ovos', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (27, 'R$ 20,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (27, 'R$ 25,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (27, 'R$ 30,00', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (27, 'R$ 35,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (28, '4 xícaras', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (28, '6 xícaras', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (28, '8 xícaras', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (28, '12 xícaras', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (29, 'R$ 52,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (29, 'R$ 55,00', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (29, 'R$ 60,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (29, 'R$ 65,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (30, 'R$ 20,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (30, 'R$ 40,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (30, 'R$ 60,00', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (30, 'R$ 75,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (31, 'x', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (31, '2x', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (31, '3x', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (31, 'x + 3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (32, '6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (32, '7', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (32, '8', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (32, '9', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (33, '6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (33, '8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (33, '9', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (33, '12', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (34, '24 ovos', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (34, '28 ovos', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (34, '32 ovos', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (34, '40 ovos', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (35, '6x', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (35, '10x', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (35, '12x', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (35, 'x + 12', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (36, 'R$ 2,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (36, 'R$ 5,00', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (36, 'R$ 10,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (36, 'R$ 20,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (37, '500 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (37, '600 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (37, '700 g', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (37, '800 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (38, '5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (38, '6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (38, '7', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (38, '8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (39, '1 kg', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (39, '2 kg', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (39, '3 kg', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (39, '4 kg', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (40, '50 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (40, '75 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (40, '100 g', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (40, '125 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (41, 'x ao quadrado + 3x + 9', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (41, 'x ao quadrado + 6x + 9', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (41, 'x ao quadrado + 9x + 6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (41, 'x ao quadrado + 9', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (42, 'x + 5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (42, 'x(x + 5)', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (42, '5(x + 1)', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (42, '5x', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (43, '30 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (43, '300 g', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (43, '3.000 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (43, '30.000 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (44, 'raiz quadrada de 2', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (44, '2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (44, 'raiz quadrada de 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (44, '1/2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (45, 'x = 4 e y = 6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (45, 'x = 5 e y = 5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (45, 'x = 6 e y = 4', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (45, 'x = 8 e y = 2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (46, '31,4 cm²', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (46, '50 cm²', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (46, '78,5 cm²', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (46, '100 cm²', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (47, '31,4 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (47, '62,8 cm³', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (47, '78,5 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (47, '100 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (48, 'R$ 60,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (48, 'R$ 65,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (48, 'R$ 68,00', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (48, 'R$ 72,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (49, 'x = 2 e y = 5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (49, 'x = 3 e y = 4', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (49, 'x = 4 e y = 3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (49, 'x = 5 e y = 2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (50, '30 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (50, '40 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (50, '60 g', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (50, '80 g', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (51, '10 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (51, '12 cm', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (51, '14 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (51, '16 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (52, '12,56 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (52, '25,12 cm', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (52, '31,4 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (52, '50,24 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (53, '16', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (53, '24', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (53, '32', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (53, '64', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (54, '4,5 vezes 10 elevado a menos 2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (54, '4,5 vezes 10 elevado a menos 3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (54, '4,5 vezes 10 elevado a menos 4', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (54, '4,5 vezes 10 elevado a 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (55, '20 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (55, '100 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (55, '200 cm³', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (55, '400 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (56, '1 e 6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (56, '2 e 3', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (56, '3 e 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (56, '4 e 5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (57, '31,4 cm²', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (57, '50 cm²', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (57, '78,5 cm²', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (57, '100 cm²', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (58, '-4 e 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (58, '-2 e 2', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (58, '0 e 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (58, '2 e 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (59, '15', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (59, '18', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (59, '20', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (59, '25', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (60, '1/2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (60, '1/3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (60, '1/4', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (60, '1/5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (61, '1 e 6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (61, '2 e 3', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (61, '3 e 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (61, '4 e 5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (62, 'R$ 40,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (62, 'R$ 45,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (62, 'R$ 50,00', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (62, 'R$ 55,00', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (63, '10', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (63, '12', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (63, '14', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (63, '16', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (64, '4 litros', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (64, '5 litros', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (64, '6 litros', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (64, '8 litros', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (65, '94,2 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (65, '100 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (65, '113,04 cm³', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (65, '125,6 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (66, '25 litros', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (66, '30 litros', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (66, '35 litros', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (66, '40 litros', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (67, '-2 e 2', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (67, '0 e 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (67, '2 e 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (67, '-4 e 4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (68, '10', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (68, '12', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (68, '13', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (68, '15', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (69, '60 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (69, '90 cm³', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (69, '120 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (69, '270 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (70, '6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (70, '7', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (70, '8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (70, '10', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (71, '2 e 3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (71, '3 e 4', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (71, '4 e 5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (71, '5 e 6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (72, '-1', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (72, '0', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (72, '1', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (72, '3', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (73, '2 vezes raiz quadrada de 2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (73, '5 vezes raiz quadrada de 2', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (73, '10 vezes raiz quadrada de 2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (73, '25 vezes raiz quadrada de 2', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (74, '8 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (74, '10 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (74, '12 cm', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (74, '14 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (75, '94,2 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (75, '100 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (75, '113,04 cm³', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (75, '125,6 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (76, '1 e 8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (76, '2 e 4', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (76, '3 e 5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (76, '4 e 6', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (77, '12 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (77, '15 cm', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (77, '18 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (77, '21 cm', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (78, '4', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (78, '5', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (78, '6', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (78, '8', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (79, '314 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (79, '392,5 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (79, '523,33 cm³', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (79, '628 cm³', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (80, 'Mediana 15 e moda 18', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (80, 'Mediana 18 e moda 15', TRUE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (80, 'Mediana 20 e moda 15', FALSE);
INSERT INTO alternativas (questao_id, texto, correta) VALUES (80, 'Mediana 18 e moda 20', FALSE);

-- Conferências finais
SELECT COUNT(*) AS total_questoes FROM questoes;
SELECT COUNT(*) AS total_alternativas FROM alternativas;

SELECT f.serie, f.numero, f.nome, COUNT(q.id) AS quantidade_questoes
FROM fases f
LEFT JOIN questoes q ON q.fase_id = f.id
WHERE f.jogo_id = 1
GROUP BY f.id, f.serie, f.numero, f.nome
ORDER BY f.serie, f.numero;


USE b17_42774059_tcc;

SET NAMES utf8mb4;

INSERT INTO dicas (questao_id, ordem, texto, custo_xp) VALUES

-- ==========================================
-- MATHCHEF - 6º ANO
-- ==========================================

(1, 1, 'Pense em quantidades que estão sendo juntadas. Qual operação usamos quando queremos descobrir o total?', 50),

(2, 1, 'Se alguns ovos foram usados, pense em retirar essa quantidade do total que havia no início.', 50),

(3, 1, 'A palavra "metade" representa uma divisão do inteiro em duas partes iguais. Pense em quantas dessas partes você precisa.', 50),

(4, 1, 'Observe o número de partes que foram usadas e o total de partes da pizza. Depois tente simplificar a fração.', 50),

(5, 1, 'Para descobrir 10%, pense em dividir o valor total em 10 partes iguais.', 50),

(6, 1, 'Transforme o número decimal em uma fração com denominador 10 e depois simplifique, se possível.', 50),

(7, 1, 'Antes de fazer a subtração, transforme quilogramas em gramas para deixar as duas quantidades na mesma unidade.', 50),

(8, 1, 'Transforme 1/2 em uma fração com o mesmo denominador de 3/4. Depois veja quanto falta para chegar ao total.', 50),

(9, 1, 'Lembre-se de que 1 litro corresponde a 1.000 mililitros. Use essa relação para fazer a conversão.', 50),

(10, 1, 'Na parte decimal, observe os números que aparecem depois da vírgula.', 50),

(11, 1, 'Compare a quantidade de pessoas. Se o número de pessoas dobrou, o que acontece com a quantidade de farinha?', 50),

(12, 1, 'Se cada bolo precisa da mesma quantidade de ovos, multiplique a quantidade usada em um bolo pelo número de bolos.', 50),

(13, 1, 'A quantidade de leite é igual para cada porção. Multiplique a quantidade de uma porção pelo número de porções.', 50),

(14, 1, 'Se cada uma das 6 porções usa a mesma quantidade, você pode repetir essa quantidade 6 vezes ou fazer uma multiplicação.', 50),

(15, 1, 'Se a receita será feita em quantidade dobrada, o custo também deve ser multiplicado por 2.', 50),

(16, 1, 'Converta tudo para gramas antes de subtrair. Depois transforme o resultado de volta para quilogramas, se necessário.', 50),

(17, 1, 'Como as duas frações possuem o mesmo denominador, observe apenas a diferença entre os numeradores.', 50),

(18, 1, '25% representa uma parte de um total dividido em quatro partes iguais. Pense em dividir a quantidade por 4.', 50),

(19, 1, 'Primeiro descubra quantas partes foram usadas ao todo. Depois compare esse número com as 12 partes originais.', 50),

(20, 1, 'Converta os 2 litros para mililitros. Depois descubra quanto foi usado nos 6 copos.', 50),

-- ==========================================
-- MATHCHEF - 7º ANO
-- ==========================================

(21, 1, 'Comece pela temperatura inicial e pense no aumento como uma adição. Cuidado com o sinal negativo.', 50),

(22, 1, 'Quando uma temperatura negativa aumenta, você está caminhando em direção ao zero e depois aos números positivos.', 50),

(23, 1, 'As duas frações possuem o mesmo denominador. Some os numeradores e observe quantas partes do inteiro foram obtidas.', 50),

(24, 1, 'Use a relação entre litros e mililitros. Um litro corresponde a 1.000 mililitros.', 50),

(25, 1, 'Transforme 15% em uma fração ou número decimal e multiplique pela quantidade total.', 50),

(26, 1, 'Compare 6 bolos com 2 bolos. Descubra por quantas vezes a quantidade de bolos aumentou e faça o mesmo com os ovos.', 50),

(27, 1, 'Para calcular 20%, transforme a porcentagem em decimal e multiplique pelo preço original.', 50),

(28, 1, 'A quantidade de porções passou de 8 para 16. Descubra a relação entre esses dois números e aplique-a às xícaras.', 50),

(29, 1, 'Um aumento de 10% significa acrescentar ao preço original uma décima parte desse valor.', 50),

(30, 1, 'Calcule primeiro quanto representa 25% do preço. Depois retire esse valor do preço original.', 50),

(31, 1, 'Se cada bolo usa x ovos, pense em somar x três vezes: x + x + x.', 50),

(32, 1, 'Você precisa descobrir qual número somado a 7 resulta em 15. Faça a operação inversa da adição.', 50),

(33, 1, 'Se o dobro de um número é 18, pense na operação inversa da multiplicação por 2.', 50),

(34, 1, 'Descubra primeiro quantos ovos são usados em cada bolo. Depois multiplique essa quantidade por 8.', 50),

(35, 1, 'Se cada unidade custa 12 reais e existem x unidades, pense em uma multiplicação entre o preço de uma unidade e a quantidade de unidades.', 50),

(36, 1, 'Calcule 5% de 100. Uma porcentagem pode ser transformada em decimal antes da multiplicação.', 50),

(37, 1, 'Você sabe o total e já conhece uma parte dele. Use a subtração para descobrir a quantidade que falta.', 50),

(38, 1, 'Primeiro elimine o -5 fazendo a operação inversa. Depois descubra o valor de x dividindo pelo número que o acompanha.', 50),

(39, 1, 'Multiplicar uma fração por 3 significa multiplicar seu numerador por 3. Observe o que acontece com o denominador.', 50),

(40, 1, 'Transforme 40% em decimal e multiplique pela quantidade total de gramas.', 50),

-- ==========================================
-- MATHCHEF - 8º ANO
-- ==========================================

(41, 1, 'Use a fórmula do quadrado da soma: o primeiro termo ao quadrado, mais duas vezes o produto dos termos, mais o segundo termo ao quadrado.', 50),

(42, 1, 'Procure um fator que apareça nos dois termos da expressão. Coloque esse fator em evidência.', 50),

(43, 1, 'Lembre-se de que 10 elevado ao quadrado significa 10 multiplicado por ele mesmo.', 50),

(44, 1, 'Um número irracional não pode ser escrito como uma fração exata de dois números inteiros. Pense nas raízes quadradas que não resultam em números inteiros.', 50),

(45, 1, 'Some as duas equações para eliminar uma das incógnitas. Depois substitua o valor encontrado em uma das equações.', 50),

(46, 1, 'A área de um círculo é calculada multiplicando pi pelo quadrado do raio. Primeiro descubra o quadrado do raio.', 50),

(47, 1, 'Para calcular o volume de um cilindro, use pi vezes o raio ao quadrado vezes a altura.', 50),

(48, 1, 'Calcule primeiro quanto representa 15% de 80. Depois retire esse valor do preço original.', 50),

(49, 1, 'Compare as duas equações. Subtrair uma da outra pode eliminar uma das incógnitas e facilitar o cálculo.', 50),

(50, 1, 'Para encontrar 30% de uma quantidade, transforme 30% em 0,30 e multiplique pelo total.', 50),

(51, 1, 'Observe a razão 2:3. Se 2 partes correspondem a 8 cm, descubra primeiro quanto vale 1 parte.', 50),

(52, 1, 'O comprimento da circunferência é calculado por 2 vezes pi vezes o raio.', 50),

(53, 1, 'Quando multiplicamos potências de mesma base, podemos somar os expoentes antes de calcular o resultado.', 50),

(54, 1, 'Na notação científica, o primeiro número deve ficar entre 1 e 10. Conte quantas casas a vírgula precisa se mover.', 50),

(55, 1, 'O volume de um prisma é calculado multiplicando a área da base pela altura.', 50),

(56, 1, 'Procure dois números que multiplicados resultem em 6 e somados resultem em 5. Eles ajudam a fatorar a equação.', 50),

(57, 1, 'Use novamente a fórmula da área do círculo: pi vezes o raio ao quadrado.', 50),

(58, 1, 'Primeiro isole o termo que contém x ao quadrado. Depois pense em quais números possuem quadrado igual ao valor encontrado.', 50),

(59, 1, 'Para calcular a média, some todos os valores e divida pela quantidade de valores existentes.', 50),

(60, 1, 'Probabilidade é a quantidade de resultados favoráveis dividida pela quantidade total de possibilidades.', 50),

-- ==========================================
-- MATHCHEF - 9º ANO
-- ==========================================

(61, 1, 'Procure dois números que multiplicados resultem em 6 e somados resultem em 5. Eles permitem fatorar a equação.', 50),

(62, 1, 'Substitua o valor de x na expressão. Primeiro faça a multiplicação e depois a adição.', 50),

(63, 1, 'Pense em qual número multiplicado por ele mesmo resulta em 144.', 50),

(64, 1, 'Descubra quantos litros correspondem a cada porção. Depois multiplique essa quantidade pelo número de porções desejado.', 50),

(65, 1, 'A fórmula do volume da esfera é quatro terços vezes pi vezes o raio ao cubo. Primeiro calcule o cubo do raio.', 50),

(66, 1, 'Descubra quantos quilômetros a nave percorre com cada litro. Depois use essa mesma proporção para 700 km.', 50),

(67, 1, 'Isole x ao quadrado dividindo os dois lados por 2. Depois lembre que tanto um número positivo quanto seu oposto podem ter o mesmo quadrado.', 50),

(68, 1, 'Substitua 5 no lugar de x na função e siga a ordem das operações.', 50),

(69, 1, 'O volume de uma pirâmide é a área da base multiplicada pela altura e dividida por 3.', 50),

(70, 1, 'Coloque os valores em ordem. Como existe uma quantidade ímpar de números, a mediana será o valor que fica exatamente no centro.', 50),

(71, 1, 'Procure dois números que multiplicados resultem em 12 e somados resultem em 7. Eles ajudam a fatorar a equação.', 50),

(72, 1, 'Substitua x por 2 na função e resolva primeiro a potência, depois a multiplicação e por fim as demais operações.', 50),

(73, 1, 'Separe 50 em um produto que contenha um quadrado perfeito. Depois retire a raiz desse quadrado perfeito.', 50),

(74, 1, 'Use o Teorema de Pitágoras. Ele relaciona os dois catetos com a hipotenusa por meio dos quadrados dessas medidas.', 50),

(75, 1, 'Use a fórmula do volume da esfera e lembre que o raio precisa ser elevado ao cubo.', 50),

(76, 1, 'Procure dois números que multiplicados resultem em 8 e somados resultem em 6. Eles podem ajudar na fatoração.', 50),

(77, 1, 'Use o Teorema de Pitágoras: some os quadrados dos dois catetos e depois encontre a raiz quadrada do resultado.', 50),

(78, 1, 'Substitua 4 no lugar de x. Resolva primeiro o quadrado de 4 e depois continue com as outras operações.', 50),

(79, 1, 'Para encontrar o volume da esfera, use quatro terços vezes pi vezes o raio ao cubo.', 50),

(80, 1, 'Para encontrar a mediana, coloque os valores em ordem e observe o valor central. Para encontrar a moda, procure o número que mais se repete.', 50);