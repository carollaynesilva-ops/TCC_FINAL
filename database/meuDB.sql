

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


USE b17_42774059_tcc;

-- =========================================================
-- MATHCHEF
-- CONFIGURAÇÃO DE XP POR FASE
-- =========================================================

/*
Cada questão vale 20 XP.

São necessários 60 XP para desbloquear a próxima fase.

O XP conquistado fica salvo individualmente no progresso
do usuário em cada fase.
*/


-- =========================================================
-- 1. ADICIONAR XP CONQUISTADO POR FASE
-- =========================================================

ALTER TABLE progresso_usuario
ADD COLUMN xp_conquistado INT NOT NULL DEFAULT 0
AFTER melhor_pontuacao;


-- =========================================================
-- 2. GARANTIR QUE O XP EXISTENTE NÃO FIQUE NULL
-- =========================================================

UPDATE progresso_usuario
SET xp_conquistado = 0
WHERE xp_conquistado IS NULL;


-- =========================================================
-- 3. CRIAR CONFIGURAÇÃO DE XP DO MATHCHEF
-- =========================================================

/*
Esta tabela centraliza as regras do jogo.

Assim não precisamos espalhar os valores
20 e 60 pelo código inteiro.
*/

CREATE TABLE IF NOT EXISTS configuracao_mathchef (

    id INT AUTO_INCREMENT PRIMARY KEY,

    xp_por_questao INT NOT NULL DEFAULT 20,

    xp_para_proxima_fase INT NOT NULL DEFAULT 60

);


-- =========================================================
-- 4. INSERIR CONFIGURAÇÃO PADRÃO
-- =========================================================

INSERT INTO configuracao_mathchef (
    xp_por_questao,
    xp_para_proxima_fase
)

SELECT
    20,
    60

WHERE NOT EXISTS (
    SELECT 1
    FROM configuracao_mathchef
);


-- =========================================================
-- 5. VERIFICAÇÃO
-- =========================================================

SELECT
    id,
    xp_por_questao,
    xp_para_proxima_fase
FROM configuracao_mathchef;


SELECT
    id,
    usuario_id,
    fase_id,
    concluida,
    xp_conquistado,
    melhor_pontuacao,
    tentativas
FROM progresso_usuario
ORDER BY usuario_id, fase_id;


-- MathSpace: cadastro completo de fases e perguntas do 6º ao 9º ano
-- Execute com o banco b17_42774059_tcc já selecionado no phpMyAdmin.
-- Não contém USE, DROP ou DELETE. Pode ser executado novamente sem duplicar fases/perguntas/alternativas/dicas.
SET NAMES utf8mb4;

INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 6, 'Missão Lua', 'Identifique formas, ângulos e perímetros.', 'facil', 1
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 6 AND numero = 1);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 6, 'Missão Marte', 'Resolva operações, frações e medidas.', 'medio', 2
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 6 AND numero = 2);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 6, 'Campo de Asteroides', 'Descubra padrões e resolva problemas.', 'medio', 3
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 6 AND numero = 3);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 6, 'Estação Espacial', 'Calcule áreas e interprete informações simples.', 'dificil', 4
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 6 AND numero = 4);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 7, 'Missão Lua', 'Explore números inteiros e ângulos.', 'facil', 1
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 7 AND numero = 1);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 7, 'Missão Marte', 'Use proporções, porcentagens e números racionais.', 'medio', 2
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 7 AND numero = 2);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 7, 'Campo de Asteroides', 'Resolva equações simples e padrões numéricos.', 'medio', 3
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 7 AND numero = 3);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 7, 'Estação Espacial', 'Analise dados e calcule áreas.', 'dificil', 4
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 7 AND numero = 4);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 8, 'Missão Lua', 'Explore potências, raízes e geometria.', 'facil', 1
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 8 AND numero = 1);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 8, 'Missão Marte', 'Resolva problemas de proporção, descontos e medidas.', 'medio', 2
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 8 AND numero = 2);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 8, 'Campo de Asteroides', 'Resolva expressões algébricas e produtos notáveis.', 'medio', 3
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 8 AND numero = 3);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 8, 'Estação Espacial', 'Use sistemas, volume e análise de dados.', 'dificil', 4
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 8 AND numero = 4);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 9, 'Missão Lua', 'Revise números reais, potências e relações geométricas.', 'facil', 1
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 9 AND numero = 1);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 9, 'Missão Marte', 'Trabalhe com porcentagem, razão e funções.', 'medio', 2
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 9 AND numero = 2);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 9, 'Campo de Asteroides', 'Resolva equações do primeiro grau e padrões.', 'medio', 3
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 9 AND numero = 3);
INSERT INTO fases (jogo_id, serie, nome, descricao, nivel_dificuldade, numero)
SELECT 2, 9, 'Estação Espacial', 'Aplique fórmulas de geometria e probabilidade.', 'dificil', 4
WHERE NOT EXISTS (SELECT 1 FROM fases WHERE jogo_id = 2 AND serie = 9 AND numero = 4);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quantos lados tem um triângulo?', '3', 'Um triângulo possui três lados.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quantos lados tem um triângulo?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '2', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos lados tem um triângulo?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '2');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos lados tem um triângulo?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos lados tem um triângulo?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos lados tem um triângulo?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Conte os segmentos que formam a figura.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos lados tem um triângulo?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quantos graus mede um ângulo reto?', '90°', 'O ângulo reto mede 90 graus.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quantos graus mede um ângulo reto?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '45°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos graus mede um ângulo reto?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '45°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '60°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos graus mede um ângulo reto?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '60°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '90°', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos graus mede um ângulo reto?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '90°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '180°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos graus mede um ângulo reto?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '180°');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'É o ângulo que parece o canto de um quadrado.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Quantos graus mede um ângulo reto?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um retângulo mede 5 cm por 3 cm. Qual é o perímetro?', '16 cm', 'O perímetro é 5 + 3 + 5 + 3 = 16 cm.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um retângulo mede 5 cm por 3 cm. Qual é o perímetro?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um retângulo mede 5 cm por 3 cm. Qual é o perímetro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '15 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um retângulo mede 5 cm por 3 cm. Qual é o perímetro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '15 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '16 cm', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um retângulo mede 5 cm por 3 cm. Qual é o perímetro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '16 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '18 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um retângulo mede 5 cm por 3 cm. Qual é o perímetro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '18 cm');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some todos os lados.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um retângulo mede 5 cm por 3 cm. Qual é o perímetro?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um quadrado tem lado de 4 cm. Qual é seu perímetro?', '16 cm', 'Quatro lados de 4 cm totalizam 16 cm.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um quadrado tem lado de 4 cm. Qual é seu perímetro?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado de 4 cm. Qual é seu perímetro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado de 4 cm. Qual é seu perímetro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '16 cm', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado de 4 cm. Qual é seu perímetro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '16 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado de 4 cm. Qual é seu perímetro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20 cm');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique a medida do lado por quatro.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado de 4 cm. Qual é seu perímetro?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual objeto tem formato parecido com uma esfera?', 'Bola', 'Uma bola lembra uma esfera, um sólido tridimensional.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual objeto tem formato parecido com uma esfera?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'Livro', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Qual objeto tem formato parecido com uma esfera?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'Livro');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'Bola', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Qual objeto tem formato parecido com uma esfera?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'Bola');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'Folha', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Qual objeto tem formato parecido com uma esfera?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'Folha');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'Régua', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Qual objeto tem formato parecido com uma esfera?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'Régua');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Pense em um objeto redondo em todas as direções.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 1 AND q.pergunta = 'Qual objeto tem formato parecido com uma esfera?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 125 + 37?', '162', '125 + 37 = 162.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 125 + 37?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '152', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 125 + 37?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '152');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '162', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 125 + 37?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '162');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '172', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 125 + 37?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '172');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '182', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 125 + 37?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '182');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some primeiro as dezenas e unidades.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 125 + 37?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 240 - 85?', '155', '240 - 85 = 155.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 240 - 85?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '145', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 240 - 85?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '145');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '150', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 240 - 85?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '150');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '155', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 240 - 85?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '155');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '165', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 240 - 85?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '165');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Subtraia 80 e depois mais 5.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quanto é 240 - 85?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Há 6 caixas com 4 peças em cada uma. Quantas peças há?', '24', '6 × 4 = 24 peças.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Há 6 caixas com 4 peças em cada uma. Quantas peças há?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Há 6 caixas com 4 peças em cada uma. Quantas peças há?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Há 6 caixas com 4 peças em cada uma. Quantas peças há?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '24', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Há 6 caixas com 4 peças em cada uma. Quantas peças há?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '24');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '28', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Há 6 caixas com 4 peças em cada uma. Quantas peças há?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '28');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique caixas pela quantidade em cada caixa.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Há 6 caixas com 4 peças em cada uma. Quantas peças há?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a metade de 18?', '9', '18 dividido por 2 é 9.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a metade de 18?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Qual é a metade de 18?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Qual é a metade de 18?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Qual é a metade de 18?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Qual é a metade de 18?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida 18 em duas partes iguais.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Qual é a metade de 18?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quantos mililitros existem em 1,5 litro?', '1500 ml', 'Cada litro corresponde a 1000 ml; 1,5 L = 1500 ml.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quantos mililitros existem em 1,5 litro?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '150 ml', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quantos mililitros existem em 1,5 litro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '150 ml');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '500 ml', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quantos mililitros existem em 1,5 litro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '500 ml');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1000 ml', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quantos mililitros existem em 1,5 litro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1000 ml');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1500 ml', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quantos mililitros existem em 1,5 litro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1500 ml');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Converta litros em mililitros multiplicando por 1000.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 2 AND q.pergunta = 'Quantos mililitros existem em 1,5 litro?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é o próximo número: 2, 4, 6, 8, ...?', '10', 'A sequência aumenta de 2 em 2.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é o próximo número: 2, 4, 6, 8, ...?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 2, 4, 6, 8, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 2, 4, 6, 8, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '11', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 2, 4, 6, 8, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '11');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 2, 4, 6, 8, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some 2 ao último número.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 2, 4, 6, 8, ...?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é o próximo número: 5, 10, 15, ...?', '20', 'A sequência aumenta de 5 em 5.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é o próximo número: 5, 10, 15, ...?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '18', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 5, 10, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '18');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 5, 10, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '25', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 5, 10, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '25');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '30', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 5, 10, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '30');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Observe a diferença entre os termos.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo número: 5, 10, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'A sequência dobra a cada etapa: 3, 6, 12, ... Qual é o próximo número?', '24', '12 × 2 = 24.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'A sequência dobra a cada etapa: 3, 6, 12, ... Qual é o próximo número?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '18', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'A sequência dobra a cada etapa: 3, 6, 12, ... Qual é o próximo número?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '18');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'A sequência dobra a cada etapa: 3, 6, 12, ... Qual é o próximo número?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '24', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'A sequência dobra a cada etapa: 3, 6, 12, ... Qual é o próximo número?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '24');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '36', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'A sequência dobra a cada etapa: 3, 6, 12, ... Qual é o próximo número?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '36');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique o último número por 2.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'A sequência dobra a cada etapa: 3, 6, 12, ... Qual é o próximo número?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Há 4 robôs com 3 baterias cada. Quantas baterias são necessárias?', '12', '4 × 3 = 12 baterias.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Há 4 robôs com 3 baterias cada. Quantas baterias são necessárias?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Há 4 robôs com 3 baterias cada. Quantas baterias são necessárias?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Há 4 robôs com 3 baterias cada. Quantas baterias são necessárias?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Há 4 robôs com 3 baterias cada. Quantas baterias são necessárias?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '16', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Há 4 robôs com 3 baterias cada. Quantas baterias são necessárias?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '16');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique robôs pela quantidade de baterias.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = 'Há 4 robôs com 3 baterias cada. Quantas baterias são necessárias?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, '36 astronautas serão divididos igualmente em 4 naves. Quantos vão em cada nave?', '9', '36 ÷ 4 = 9 astronautas por nave.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = '36 astronautas serão divididos igualmente em 4 naves. Quantos vão em cada nave?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = '36 astronautas serão divididos igualmente em 4 naves. Quantos vão em cada nave?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = '36 astronautas serão divididos igualmente em 4 naves. Quantos vão em cada nave?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = '36 astronautas serão divididos igualmente em 4 naves. Quantos vão em cada nave?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = '36 astronautas serão divididos igualmente em 4 naves. Quantos vão em cada nave?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida o total de astronautas pelo número de naves.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 3 AND q.pergunta = '36 astronautas serão divididos igualmente em 4 naves. Quantos vão em cada nave?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a área de um retângulo de 8 cm por 3 cm?', '24 cm²', 'Área do retângulo = base × altura = 8 × 3 = 24 cm².', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a área de um retângulo de 8 cm por 3 cm?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '11 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um retângulo de 8 cm por 3 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '11 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '16 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um retângulo de 8 cm por 3 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '16 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '24 cm²', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um retângulo de 8 cm por 3 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '24 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '32 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um retângulo de 8 cm por 3 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '32 cm²');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique comprimento e largura.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um retângulo de 8 cm por 3 cm?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um tapete mede 7 m por 5 m. Qual é sua área?', '35 m²', '7 × 5 = 35 m².', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um tapete mede 7 m por 5 m. Qual é sua área?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12 m²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Um tapete mede 7 m por 5 m. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12 m²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '24 m²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Um tapete mede 7 m por 5 m. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '24 m²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '30 m²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Um tapete mede 7 m por 5 m. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '30 m²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '35 m²', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Um tapete mede 7 m por 5 m. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '35 m²');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Área é medida em unidades quadradas.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Um tapete mede 7 m por 5 m. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Uma equipe coletou 3, 5 e 2 amostras em três locais. Quantas coletou ao todo?', '10', '3 + 5 + 2 = 10 amostras.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Uma equipe coletou 3, 5 e 2 amostras em três locais. Quantas coletou ao todo?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma equipe coletou 3, 5 e 2 amostras em três locais. Quantas coletou ao todo?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma equipe coletou 3, 5 e 2 amostras em três locais. Quantas coletou ao todo?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma equipe coletou 3, 5 e 2 amostras em três locais. Quantas coletou ao todo?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma equipe coletou 3, 5 e 2 amostras em três locais. Quantas coletou ao todo?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some as três quantidades.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma equipe coletou 3, 5 e 2 amostras em três locais. Quantas coletou ao todo?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Em uma caixa há 3 cartões vermelhos e 1 azul. Qual fração representa a chance de tirar o azul?', '1/4', 'Há 1 cartão azul entre 4 cartões ao todo.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Em uma caixa há 3 cartões vermelhos e 1 azul. Qual fração representa a chance de tirar o azul?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/3', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Em uma caixa há 3 cartões vermelhos e 1 azul. Qual fração representa a chance de tirar o azul?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/3');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/4', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Em uma caixa há 3 cartões vermelhos e 1 azul. Qual fração representa a chance de tirar o azul?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3/4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Em uma caixa há 3 cartões vermelhos e 1 azul. Qual fração representa a chance de tirar o azul?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3/4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4/1', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Em uma caixa há 3 cartões vermelhos e 1 azul. Qual fração representa a chance de tirar o azul?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4/1');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Compare os casos azuis com o total.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Em uma caixa há 3 cartões vermelhos e 1 azul. Qual fração representa a chance de tirar o azul?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Uma roleta tem 4 partes iguais, sendo 1 verde. Qual é a chance de cair no verde?', '1/4', 'Uma das quatro partes é verde.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Uma roleta tem 4 partes iguais, sendo 1 verde. Qual é a chance de cair no verde?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/2', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma roleta tem 4 partes iguais, sendo 1 verde. Qual é a chance de cair no verde?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/2');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/3', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma roleta tem 4 partes iguais, sendo 1 verde. Qual é a chance de cair no verde?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/3');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/4', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma roleta tem 4 partes iguais, sendo 1 verde. Qual é a chance de cair no verde?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3/4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma roleta tem 4 partes iguais, sendo 1 verde. Qual é a chance de cair no verde?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3/4');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Casos favoráveis divididos pelo total.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 6 AND f.numero = 4 AND q.pergunta = 'Uma roleta tem 4 partes iguais, sendo 1 verde. Qual é a chance de cair no verde?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é -3 + 8?', '5', 'Partindo de -3 e avançando 8 unidades, chegamos a 5.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é -3 + 8?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '-11', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é -3 + 8?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '-11');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '-5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é -3 + 8?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '-5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é -3 + 8?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '11', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é -3 + 8?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '11');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Na reta numérica, avance oito casas para a direita.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é -3 + 8?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é o valor de |-7|?', '7', 'O módulo representa a distância até zero, sempre não negativa.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é o valor de |-7|?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '-7', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o valor de |-7|?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '-7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '0', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o valor de |-7|?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '0');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o valor de |-7|?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '14', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o valor de |-7|?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '14');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Conte a distância entre -7 e zero.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o valor de |-7|?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Dois ângulos de um triângulo medem 50° e 60°. Quanto mede o terceiro?', '70°', 'A soma dos ângulos internos é 180°; 180 - 50 - 60 = 70°.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Dois ângulos de um triângulo medem 50° e 60°. Quanto mede o terceiro?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '60°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Dois ângulos de um triângulo medem 50° e 60°. Quanto mede o terceiro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '60°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '70°', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Dois ângulos de um triângulo medem 50° e 60°. Quanto mede o terceiro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '70°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '80°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Dois ângulos de um triângulo medem 50° e 60°. Quanto mede o terceiro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '80°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '90°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Dois ângulos de um triângulo medem 50° e 60°. Quanto mede o terceiro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '90°');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'A soma dos três ângulos é 180°.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Dois ângulos de um triângulo medem 50° e 60°. Quanto mede o terceiro?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é o resultado de 3²?', '9', '3² = 3 × 3 = 9.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é o resultado de 3²?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o resultado de 3²?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o resultado de 3²?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o resultado de 3²?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o resultado de 3²?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'O expoente 2 indica multiplicar a base por ela mesma.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Qual é o resultado de 3²?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 2/5 de 20?', '8', '20 ÷ 5 × 2 = 8.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 2/5 de 20?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é 2/5 de 20?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é 2/5 de 20?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é 2/5 de 20?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é 2/5 de 20?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida 20 pelo denominador e multiplique pelo numerador.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 1 AND q.pergunta = 'Quanto é 2/5 de 20?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 25% de 80?', '20', '25% corresponde a um quarto; 80 ÷ 4 = 20.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 25% de 80?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é 25% de 80?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '15', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é 25% de 80?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '15');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é 25% de 80?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '25', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é 25% de 80?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '25');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Calcule a quarta parte de 80.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é 25% de 80?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Uma receita usa 3 copos para 6 pessoas. Quantos copos para 12 pessoas?', '6', 'O número de pessoas dobrou, então os copos também: 3 × 2 = 6.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Uma receita usa 3 copos para 6 pessoas. Quantos copos para 12 pessoas?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Uma receita usa 3 copos para 6 pessoas. Quantos copos para 12 pessoas?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Uma receita usa 3 copos para 6 pessoas. Quantos copos para 12 pessoas?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Uma receita usa 3 copos para 6 pessoas. Quantos copos para 12 pessoas?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Uma receita usa 3 copos para 6 pessoas. Quantos copos para 12 pessoas?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Aumente as duas quantidades na mesma proporção.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Uma receita usa 3 copos para 6 pessoas. Quantos copos para 12 pessoas?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual fração é equivalente a 3/4?', '6/8', 'Multiplicando numerador e denominador por 2, obtemos 6/8.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual fração é equivalente a 3/4?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4/6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Qual fração é equivalente a 3/4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4/6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6/8', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Qual fração é equivalente a 3/4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6/8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5/8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Qual fração é equivalente a 3/4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5/8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3/8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Qual fração é equivalente a 3/4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3/8');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique os dois termos pelo mesmo número.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Qual fração é equivalente a 3/4?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é -12 + 5?', '-7', 'Somar 5 a -12 resulta em -7.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é -12 + 5?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '-17', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é -12 + 5?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '-17');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '-7', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é -12 + 5?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '-7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é -12 + 5?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '17', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é -12 + 5?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '17');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Avance cinco unidades a partir de -12.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Quanto é -12 + 5?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um produto de R$ 50 tem desconto de 10%. Qual é o desconto?', 'R$ 5', '10% de 50 é 5 reais.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um produto de R$ 50 tem desconto de 10%. Qual é o desconto?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 2', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Um produto de R$ 50 tem desconto de 10%. Qual é o desconto?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 2');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 5', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Um produto de R$ 50 tem desconto de 10%. Qual é o desconto?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 10', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Um produto de R$ 50 tem desconto de 10%. Qual é o desconto?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 10');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 15', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Um produto de R$ 50 tem desconto de 10%. Qual é o desconto?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 15');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, '10% é um décimo do valor.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 2 AND q.pergunta = 'Um produto de R$ 50 tem desconto de 10%. Qual é o desconto?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Se x + 7 = 15, quanto vale x?', '8', 'x = 15 - 7 = 8.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Se x + 7 = 15, quanto vale x?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se x + 7 = 15, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se x + 7 = 15, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se x + 7 = 15, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se x + 7 = 15, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Subtraia 7 dos dois lados.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se x + 7 = 15, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é o próximo termo: 4, 8, 12, 16, ...?', '20', 'A sequência aumenta de 4 em 4.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é o próximo termo: 4, 8, 12, 16, ...?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '18', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 4, 8, 12, 16, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '18');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 4, 8, 12, 16, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '22', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 4, 8, 12, 16, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '22');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '24', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 4, 8, 12, 16, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '24');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some 4 ao último termo.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 4, 8, 12, 16, ...?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Se 3x = 21, quanto vale x?', '7', 'Divida os dois lados por 3: x = 7.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Se 3x = 21, quanto vale x?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se 3x = 21, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se 3x = 21, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se 3x = 21, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se 3x = 21, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Use a operação inversa da multiplicação.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Se 3x = 21, quanto vale x?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 2 × (5 + 3)?', '16', 'Primeiro 5 + 3 = 8; depois 2 × 8 = 16.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 2 × (5 + 3)?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '13', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Quanto é 2 × (5 + 3)?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '13');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '15', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Quanto é 2 × (5 + 3)?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '15');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '16', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Quanto é 2 × (5 + 3)?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '16');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '18', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Quanto é 2 × (5 + 3)?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '18');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Resolva primeiro o que está entre parênteses.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Quanto é 2 × (5 + 3)?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um mapa usa escala 1 cm para 5 km. A distância no mapa é 4 cm. Qual é a distância real?', '20 km', '4 × 5 = 20 km.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um mapa usa escala 1 cm para 5 km. A distância no mapa é 4 cm. Qual é a distância real?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9 km', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Um mapa usa escala 1 cm para 5 km. A distância no mapa é 4 cm. Qual é a distância real?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9 km');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '15 km', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Um mapa usa escala 1 cm para 5 km. A distância no mapa é 4 cm. Qual é a distância real?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '15 km');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20 km', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Um mapa usa escala 1 cm para 5 km. A distância no mapa é 4 cm. Qual é a distância real?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20 km');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '25 km', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Um mapa usa escala 1 cm para 5 km. A distância no mapa é 4 cm. Qual é a distância real?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '25 km');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique a distância do mapa pelo valor da escala.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 3 AND q.pergunta = 'Um mapa usa escala 1 cm para 5 km. A distância no mapa é 4 cm. Qual é a distância real?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a área de um triângulo de base 10 cm e altura 6 cm?', '30 cm²', 'Área = base × altura ÷ 2 = 10 × 6 ÷ 2 = 30 cm².', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a área de um triângulo de base 10 cm e altura 6 cm?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '16 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um triângulo de base 10 cm e altura 6 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '16 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '30 cm²', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um triângulo de base 10 cm e altura 6 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '30 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '60 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um triângulo de base 10 cm e altura 6 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '60 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '80 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um triângulo de base 10 cm e altura 6 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '80 cm²');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique base e altura e divida por dois.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um triângulo de base 10 cm e altura 6 cm?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a média de 6, 8 e 10?', '8', '(6 + 8 + 10) ÷ 3 = 8.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a média de 6, 8 e 10?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 6, 8 e 10?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 6, 8 e 10?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 6, 8 e 10?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 6, 8 e 10?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some os valores e divida pela quantidade.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 6, 8 e 10?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Uma sacola tem 2 bolas verdes e 6 amarelas. Qual a probabilidade de tirar uma verde?', '1/4', 'São 2 verdes em 8 bolas: 2/8 = 1/4.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Uma sacola tem 2 bolas verdes e 6 amarelas. Qual a probabilidade de tirar uma verde?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/2', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Uma sacola tem 2 bolas verdes e 6 amarelas. Qual a probabilidade de tirar uma verde?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/2');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/3', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Uma sacola tem 2 bolas verdes e 6 amarelas. Qual a probabilidade de tirar uma verde?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/3');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/4', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Uma sacola tem 2 bolas verdes e 6 amarelas. Qual a probabilidade de tirar uma verde?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3/4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Uma sacola tem 2 bolas verdes e 6 amarelas. Qual a probabilidade de tirar uma verde?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3/4');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Simplifique 2/8.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Uma sacola tem 2 bolas verdes e 6 amarelas. Qual a probabilidade de tirar uma verde?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um retângulo tem área de 48 cm² e largura 6 cm. Qual é o comprimento?', '8 cm', '48 ÷ 6 = 8 cm.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um retângulo tem área de 48 cm² e largura 6 cm. Qual é o comprimento?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Um retângulo tem área de 48 cm² e largura 6 cm. Qual é o comprimento?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Um retângulo tem área de 48 cm² e largura 6 cm. Qual é o comprimento?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8 cm', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Um retângulo tem área de 48 cm² e largura 6 cm. Qual é o comprimento?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Um retângulo tem área de 48 cm² e largura 6 cm. Qual é o comprimento?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9 cm');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida a área pela largura.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Um retângulo tem área de 48 cm² e largura 6 cm. Qual é o comprimento?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é o suplemento de um ângulo de 125°?', '55°', 'Ângulos suplementares somam 180°; 180 - 125 = 55°.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é o suplemento de um ângulo de 125°?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '45°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é o suplemento de um ângulo de 125°?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '45°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '55°', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é o suplemento de um ângulo de 125°?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '55°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '65°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é o suplemento de um ângulo de 125°?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '65°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '75°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é o suplemento de um ângulo de 125°?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '75°');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Subtraia 125° de 180°.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 7 AND f.numero = 4 AND q.pergunta = 'Qual é o suplemento de um ângulo de 125°?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 2⁴?', '16', '2⁴ = 2 × 2 × 2 × 2 = 16.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 2⁴?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 2⁴?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 2⁴?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '16', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 2⁴?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '16');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '24', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 2⁴?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '24');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique quatro fatores iguais a 2.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 2⁴?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a raiz quadrada de 81?', '9', '9 × 9 = 81.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a raiz quadrada de 81?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual é a raiz quadrada de 81?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual é a raiz quadrada de 81?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual é a raiz quadrada de 81?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual é a raiz quadrada de 81?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Procure o número que multiplicado por ele mesmo dá 81.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual é a raiz quadrada de 81?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 3² + 4?', '13', '3² = 9; 9 + 4 = 13.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 3² + 4?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '11', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 3² + 4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '11');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 3² + 4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '13', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 3² + 4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '13');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '15', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 3² + 4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '15');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Calcule a potência antes da soma.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Quanto é 3² + 4?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um quadrado tem lado 6 cm. Qual é sua área?', '36 cm²', 'Área = lado × lado = 6 × 6 = 36 cm².', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um quadrado tem lado 6 cm. Qual é sua área?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado 6 cm. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '24 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado 6 cm. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '24 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '30 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado 6 cm. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '30 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '36 cm²', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado 6 cm. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '36 cm²');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Eleve a medida do lado ao quadrado.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Um quadrado tem lado 6 cm. Qual é sua área?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual número é irracional?', '√2', '√2 não pode ser escrito como uma fração exata de inteiros.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual número é irracional?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '0,5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual número é irracional?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '0,5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3/4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual número é irracional?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3/4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '√2', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual número é irracional?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '√2');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual número é irracional?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Sua representação decimal é infinita e não periódica.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 1 AND q.pergunta = 'Qual número é irracional?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 15% de 200?', '30', '0,15 × 200 = 30.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 15% de 200?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '15', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Quanto é 15% de 200?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '15');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Quanto é 15% de 200?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '30', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Quanto é 15% de 200?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '30');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '35', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Quanto é 15% de 200?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '35');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, '10% é 20 e 5% é 10; some os dois.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Quanto é 15% de 200?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Se 4 cadernos custam R$ 28, quanto custam 6 pelo mesmo preço unitário?', 'R$ 42', 'Cada caderno custa R$ 7; 6 × 7 = 42.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Se 4 cadernos custam R$ 28, quanto custam 6 pelo mesmo preço unitário?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 35', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Se 4 cadernos custam R$ 28, quanto custam 6 pelo mesmo preço unitário?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 35');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 40', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Se 4 cadernos custam R$ 28, quanto custam 6 pelo mesmo preço unitário?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 40');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 42', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Se 4 cadernos custam R$ 28, quanto custam 6 pelo mesmo preço unitário?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 42');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 48', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Se 4 cadernos custam R$ 28, quanto custam 6 pelo mesmo preço unitário?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 48');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Encontre primeiro o preço de um caderno.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Se 4 cadernos custam R$ 28, quanto custam 6 pelo mesmo preço unitário?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a representação decimal de 3/8?', '0,375', '3 ÷ 8 = 0,375.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a representação decimal de 3/8?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '0,25', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Qual é a representação decimal de 3/8?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '0,25');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '0,375', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Qual é a representação decimal de 3/8?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '0,375');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '0,5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Qual é a representação decimal de 3/8?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '0,5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '0,75', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Qual é a representação decimal de 3/8?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '0,75');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida o numerador pelo denominador.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Qual é a representação decimal de 3/8?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um valor de 120 aumentou 10%. Qual é o novo valor?', '132', '10% de 120 é 12; 120 + 12 = 132.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um valor de 120 aumentou 10%. Qual é o novo valor?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '122', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um valor de 120 aumentou 10%. Qual é o novo valor?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '122');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '128', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um valor de 120 aumentou 10%. Qual é o novo valor?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '128');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '132', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um valor de 120 aumentou 10%. Qual é o novo valor?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '132');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '140', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um valor de 120 aumentou 10%. Qual é o novo valor?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '140');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Calcule o aumento e some ao valor inicial.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um valor de 120 aumentou 10%. Qual é o novo valor?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um carro percorre 180 km em 3 horas, à velocidade constante. Qual é a velocidade média?', '60 km/h', 'Velocidade média = distância ÷ tempo = 180 ÷ 3 = 60 km/h.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um carro percorre 180 km em 3 horas, à velocidade constante. Qual é a velocidade média?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '45 km/h', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um carro percorre 180 km em 3 horas, à velocidade constante. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '45 km/h');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '50 km/h', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um carro percorre 180 km em 3 horas, à velocidade constante. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '50 km/h');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '60 km/h', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um carro percorre 180 km em 3 horas, à velocidade constante. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '60 km/h');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '90 km/h', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um carro percorre 180 km em 3 horas, à velocidade constante. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '90 km/h');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida quilômetros por horas.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 2 AND q.pergunta = 'Um carro percorre 180 km em 3 horas, à velocidade constante. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Simplifique 3x + 2x.', '5x', 'Termos semelhantes somam seus coeficientes: 3 + 2 = 5.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Simplifique 3x + 2x.');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Simplifique 3x + 2x.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5x', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Simplifique 3x + 2x.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5x');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6x', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Simplifique 3x + 2x.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6x');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'x²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Simplifique 3x + 2x.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'x²');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some apenas os números que acompanham x.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Simplifique 3x + 2x.'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Se x = 4, quanto vale 2x + 3?', '11', '2 × 4 + 3 = 11.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Se x = 4, quanto vale 2x + 3?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Se x = 4, quanto vale 2x + 3?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Se x = 4, quanto vale 2x + 3?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '11', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Se x = 4, quanto vale 2x + 3?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '11');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '14', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Se x = 4, quanto vale 2x + 3?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '14');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Substitua x por 4.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Se x = 4, quanto vale 2x + 3?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a fatoração de x² - 9?', '(x - 3)(x + 3)', 'É uma diferença de quadrados: x² - 3².', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a fatoração de x² - 9?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '(x-9)(x+1)', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Qual é a fatoração de x² - 9?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '(x-9)(x+1)');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '(x-3)(x+3)', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Qual é a fatoração de x² - 9?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '(x-3)(x+3)');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '(x-3)²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Qual é a fatoração de x² - 9?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '(x-3)²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'x(x-9)', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Qual é a fatoração de x² - 9?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'x(x-9)');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Use a forma a² - b² = (a-b)(a+b).', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Qual é a fatoração de x² - 9?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Desenvolva (x + 2)².', 'x² + 4x + 4', '(x + 2)² = x² + 4x + 4.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Desenvolva (x + 2)².');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'x² + 4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Desenvolva (x + 2)².'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'x² + 4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'x² + 2x + 4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Desenvolva (x + 2)².'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'x² + 2x + 4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'x² + 4x + 4', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Desenvolva (x + 2)².'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'x² + 4x + 4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'x² + 2', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Desenvolva (x + 2)².'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'x² + 2');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Use (a+b)² = a² + 2ab + b².', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Desenvolva (x + 2)².'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Resolva 5x - 7 = 18.', '5', '5x = 25, então x = 5.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Resolva 5x - 7 = 18.');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Resolva 5x - 7 = 18.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Resolva 5x - 7 = 18.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Resolva 5x - 7 = 18.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Resolva 5x - 7 = 18.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some 7 aos dois lados e depois divida por 5.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 3 AND q.pergunta = 'Resolva 5x - 7 = 18.'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é o volume de um cubo de aresta 3 cm?', '27 cm³', 'Volume = 3 × 3 × 3 = 27 cm³.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é o volume de um cubo de aresta 3 cm?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9 cm³', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cubo de aresta 3 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9 cm³');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '18 cm³', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cubo de aresta 3 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '18 cm³');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '27 cm³', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cubo de aresta 3 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '27 cm³');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '36 cm³', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cubo de aresta 3 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '36 cm³');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique a aresta por ela mesma três vezes.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cubo de aresta 3 cm?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Resolva o sistema x + y = 10 e x - y = 2. Qual é x?', '6', 'Somando as equações, 2x = 12; portanto x = 6.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Resolva o sistema x + y = 10 e x - y = 2. Qual é x?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Resolva o sistema x + y = 10 e x - y = 2. Qual é x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Resolva o sistema x + y = 10 e x - y = 2. Qual é x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Resolva o sistema x + y = 10 e x - y = 2. Qual é x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Resolva o sistema x + y = 10 e x - y = 2. Qual é x?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some as duas equações para eliminar y.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Resolva o sistema x + y = 10 e x - y = 2. Qual é x?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a área de um círculo de raio 2 cm? Use π ≈ 3,14.', '12,56 cm²', 'A = πr² = 3,14 × 4 = 12,56 cm².', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a área de um círculo de raio 2 cm? Use π ≈ 3,14.');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6,28 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um círculo de raio 2 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6,28 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9,42 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um círculo de raio 2 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9,42 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12,56 cm²', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um círculo de raio 2 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12,56 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '25,12 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um círculo de raio 2 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '25,12 cm²');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Eleve o raio ao quadrado e multiplique por π.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um círculo de raio 2 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a média de 12, 15, 18 e 15?', '15', 'A soma é 60 e 60 ÷ 4 = 15.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a média de 12, 15, 18 e 15?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '14', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 12, 15, 18 e 15?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '14');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '15', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 12, 15, 18 e 15?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '15');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '16', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 12, 15, 18 e 15?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '16');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '18', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 12, 15, 18 e 15?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '18');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some os quatro dados e divida por quatro.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Qual é a média de 12, 15, 18 e 15?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Uma caixa mede 4 cm × 3 cm × 5 cm. Qual é seu volume?', '60 cm³', 'Volume = 4 × 3 × 5 = 60 cm³.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Uma caixa mede 4 cm × 3 cm × 5 cm. Qual é seu volume?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12 cm³', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Uma caixa mede 4 cm × 3 cm × 5 cm. Qual é seu volume?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12 cm³');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20 cm³', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Uma caixa mede 4 cm × 3 cm × 5 cm. Qual é seu volume?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20 cm³');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '45 cm³', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Uma caixa mede 4 cm × 3 cm × 5 cm. Qual é seu volume?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '45 cm³');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '60 cm³', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Uma caixa mede 4 cm × 3 cm × 5 cm. Qual é seu volume?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '60 cm³');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique comprimento, largura e altura.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 8 AND f.numero = 4 AND q.pergunta = 'Uma caixa mede 4 cm × 3 cm × 5 cm. Qual é seu volume?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é √144?', '12', '12 × 12 = 144.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é √144?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é √144?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '11', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é √144?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '11');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é √144?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '14', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é √144?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '14');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Procure o número cujo quadrado é 144.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é √144?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 2³ × 2²?', '32', 'Potências de mesma base: 2³ × 2² = 2⁵ = 32.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 2³ × 2²?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '16', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Quanto é 2³ × 2²?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '16');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '24', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Quanto é 2³ × 2²?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '24');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '32', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Quanto é 2³ × 2²?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '32');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '64', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Quanto é 2³ × 2²?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '64');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some os expoentes quando as bases são iguais e multiplicadas.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Quanto é 2³ × 2²?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um triângulo tem ângulos 35° e 65°. Qual é o terceiro?', '80°', '180 - 35 - 65 = 80°.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um triângulo tem ângulos 35° e 65°. Qual é o terceiro?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '70°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Um triângulo tem ângulos 35° e 65°. Qual é o terceiro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '70°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '75°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Um triângulo tem ângulos 35° e 65°. Qual é o terceiro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '75°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '80°', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Um triângulo tem ângulos 35° e 65°. Qual é o terceiro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '80°');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '90°', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Um triângulo tem ângulos 35° e 65°. Qual é o terceiro?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '90°');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'A soma dos ângulos internos é 180°.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Um triângulo tem ângulos 35° e 65°. Qual é o terceiro?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Dois triângulos semelhantes têm razão de lados 2:3. Um lado do menor mede 8 cm. Quanto mede o correspondente no maior?', '12 cm', 'O fator de ampliação é 3/2; 8 × 3/2 = 12.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Dois triângulos semelhantes têm razão de lados 2:3. Um lado do menor mede 8 cm. Quanto mede o correspondente no maior?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Dois triângulos semelhantes têm razão de lados 2:3. Um lado do menor mede 8 cm. Quanto mede o correspondente no maior?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '11 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Dois triângulos semelhantes têm razão de lados 2:3. Um lado do menor mede 8 cm. Quanto mede o correspondente no maior?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '11 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12 cm', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Dois triângulos semelhantes têm razão de lados 2:3. Um lado do menor mede 8 cm. Quanto mede o correspondente no maior?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12 cm');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '14 cm', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Dois triângulos semelhantes têm razão de lados 2:3. Um lado do menor mede 8 cm. Quanto mede o correspondente no maior?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '14 cm');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique 8 pela razão 3/2.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Dois triângulos semelhantes têm razão de lados 2:3. Um lado do menor mede 8 cm. Quanto mede o correspondente no maior?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a distância entre -4 e 6 na reta numérica?', '10', 'A distância é |6 - (-4)| = 10.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a distância entre -4 e 6 na reta numérica?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '2', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é a distância entre -4 e 6 na reta numérica?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '2');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é a distância entre -4 e 6 na reta numérica?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é a distância entre -4 e 6 na reta numérica?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é a distância entre -4 e 6 na reta numérica?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Calcule a diferença entre os valores.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 1 AND q.pergunta = 'Qual é a distância entre -4 e 6 na reta numérica?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Quanto é 20% de 350?', '70', '0,20 × 350 = 70.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Quanto é 20% de 350?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '35', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Quanto é 20% de 350?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '35');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '50', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Quanto é 20% de 350?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '50');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '70', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Quanto é 20% de 350?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '70');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '80', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Quanto é 20% de 350?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '80');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, '10% é 35; dobre para obter 20%.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Quanto é 20% de 350?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Se y = 2x + 1, quanto vale y quando x = 4?', '9', 'y = 2 × 4 + 1 = 9.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Se y = 2x + 1, quanto vale y quando x = 4?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se y = 2x + 1, quanto vale y quando x = 4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se y = 2x + 1, quanto vale y quando x = 4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se y = 2x + 1, quanto vale y quando x = 4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se y = 2x + 1, quanto vale y quando x = 4?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Substitua x por 4 na expressão.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se y = 2x + 1, quanto vale y quando x = 4?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Uma camisa de R$ 80 recebeu desconto de 25%. Qual é o preço final?', 'R$ 60', '25% de 80 é 20; 80 - 20 = 60.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Uma camisa de R$ 80 recebeu desconto de 25%. Qual é o preço final?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 40', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma camisa de R$ 80 recebeu desconto de 25%. Qual é o preço final?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 40');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 55', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma camisa de R$ 80 recebeu desconto de 25%. Qual é o preço final?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 55');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 60', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma camisa de R$ 80 recebeu desconto de 25%. Qual é o preço final?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 60');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, 'R$ 65', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma camisa de R$ 80 recebeu desconto de 25%. Qual é o preço final?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = 'R$ 65');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Calcule um quarto de 80 e subtraia.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma camisa de R$ 80 recebeu desconto de 25%. Qual é o preço final?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Uma distância de 150 km é percorrida em 2,5 horas. Qual é a velocidade média?', '60 km/h', '150 ÷ 2,5 = 60 km/h.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Uma distância de 150 km é percorrida em 2,5 horas. Qual é a velocidade média?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '50 km/h', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma distância de 150 km é percorrida em 2,5 horas. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '50 km/h');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '55 km/h', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma distância de 150 km é percorrida em 2,5 horas. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '55 km/h');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '60 km/h', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma distância de 150 km é percorrida em 2,5 horas. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '60 km/h');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '75 km/h', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma distância de 150 km é percorrida em 2,5 horas. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '75 km/h');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida a distância pelo tempo.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Uma distância de 150 km é percorrida em 2,5 horas. Qual é a velocidade média?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Se 5 máquinas produzem 100 peças no mesmo período, quantas peças 8 máquinas produzem na mesma proporção?', '160', 'Cada máquina produz 20 peças; 8 × 20 = 160.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Se 5 máquinas produzem 100 peças no mesmo período, quantas peças 8 máquinas produzem na mesma proporção?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '120', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se 5 máquinas produzem 100 peças no mesmo período, quantas peças 8 máquinas produzem na mesma proporção?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '120');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '140', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se 5 máquinas produzem 100 peças no mesmo período, quantas peças 8 máquinas produzem na mesma proporção?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '140');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '160', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se 5 máquinas produzem 100 peças no mesmo período, quantas peças 8 máquinas produzem na mesma proporção?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '160');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '180', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se 5 máquinas produzem 100 peças no mesmo período, quantas peças 8 máquinas produzem na mesma proporção?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '180');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Encontre a produção por máquina.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 2 AND q.pergunta = 'Se 5 máquinas produzem 100 peças no mesmo período, quantas peças 8 máquinas produzem na mesma proporção?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Resolva 3x + 5 = 20.', '5', '3x = 15, então x = 5.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Resolva 3x + 5 = 20.');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 3x + 5 = 20.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 3x + 5 = 20.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 3x + 5 = 20.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 3x + 5 = 20.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Subtraia 5 e divida por 3.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 3x + 5 = 20.'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é o próximo termo: 3, 7, 11, 15, ...?', '19', 'A sequência aumenta de 4 em 4.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é o próximo termo: 3, 7, 11, 15, ...?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '17', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 3, 7, 11, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '17');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '18', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 3, 7, 11, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '18');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '19', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 3, 7, 11, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '19');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 3, 7, 11, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some 4 ao último termo.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Qual é o próximo termo: 3, 7, 11, 15, ...?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Resolva 2(x - 3) = 10.', '8', 'x - 3 = 5, então x = 8.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Resolva 2(x - 3) = 10.');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 2(x - 3) = 10.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 2(x - 3) = 10.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 2(x - 3) = 10.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '10', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 2(x - 3) = 10.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '10');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida por 2 e depois some 3.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Resolva 2(x - 3) = 10.'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'As raízes de x² - 9 = 0 são?', '-3 e 3', 'x² = 9, então x pode ser -3 ou 3.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'As raízes de x² - 9 = 0 são?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3 apenas', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'As raízes de x² - 9 = 0 são?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3 apenas');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '-3 apenas', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'As raízes de x² - 9 = 0 são?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '-3 apenas');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '-3 e 3', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'As raízes de x² - 9 = 0 são?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '-3 e 3');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '0 e 9', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'As raízes de x² - 9 = 0 são?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '0 e 9');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Pense nos dois números cujo quadrado é 9.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'As raízes de x² - 9 = 0 são?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Um número somado ao seu dobro resulta em 27. Qual é o número?', '9', 'x + 2x = 27; 3x = 27; x = 9.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Um número somado ao seu dobro resulta em 27. Qual é o número?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Um número somado ao seu dobro resulta em 27. Qual é o número?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '8', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Um número somado ao seu dobro resulta em 27. Qual é o número?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '8');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '9', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Um número somado ao seu dobro resulta em 27. Qual é o número?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '9');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '12', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Um número somado ao seu dobro resulta em 27. Qual é o número?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '12');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Monte a equação x + 2x = 27.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 3 AND q.pergunta = 'Um número somado ao seu dobro resulta em 27. Qual é o número?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é o volume de um cilindro de raio 3 cm e altura 4 cm? Use π ≈ 3,14.', '113,04 cm³', 'V = πr²h = 3,14 × 9 × 4 = 113,04 cm³.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é o volume de um cilindro de raio 3 cm e altura 4 cm? Use π ≈ 3,14.');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '37,68 cm³', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cilindro de raio 3 cm e altura 4 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '37,68 cm³');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '75,36 cm³', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cilindro de raio 3 cm e altura 4 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '75,36 cm³');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '113,04 cm³', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cilindro de raio 3 cm e altura 4 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '113,04 cm³');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '150,72 cm³', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cilindro de raio 3 cm e altura 4 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '150,72 cm³');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Multiplique π pelo raio ao quadrado e pela altura.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é o volume de um cilindro de raio 3 cm e altura 4 cm? Use π ≈ 3,14.'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a distância entre os pontos (0,0) e (3,4)?', '5', 'Pelo teorema de Pitágoras, √(3² + 4²) = √25 = 5.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a distância entre os pontos (0,0) e (3,4)?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '4', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a distância entre os pontos (0,0) e (3,4)?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '4');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '5', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a distância entre os pontos (0,0) e (3,4)?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '6', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a distância entre os pontos (0,0) e (3,4)?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '6');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '7', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a distância entre os pontos (0,0) e (3,4)?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '7');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Use a distância como hipotenusa de um triângulo retângulo.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a distância entre os pontos (0,0) e (3,4)?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Em um grupo de 20 estudantes, 8 preferem robótica. Qual porcentagem prefere robótica?', '40%', '8 ÷ 20 = 0,4 = 40%.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Em um grupo de 20 estudantes, 8 preferem robótica. Qual porcentagem prefere robótica?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '20%', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Em um grupo de 20 estudantes, 8 preferem robótica. Qual porcentagem prefere robótica?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '20%');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '30%', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Em um grupo de 20 estudantes, 8 preferem robótica. Qual porcentagem prefere robótica?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '30%');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '40%', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Em um grupo de 20 estudantes, 8 preferem robótica. Qual porcentagem prefere robótica?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '40%');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '50%', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Em um grupo de 20 estudantes, 8 preferem robótica. Qual porcentagem prefere robótica?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '50%');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida 8 por 20 e transforme em porcentagem.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Em um grupo de 20 estudantes, 8 preferem robótica. Qual porcentagem prefere robótica?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Uma caixa tem 3 cartões azuis e 2 vermelhos. Qual é a probabilidade de tirar um vermelho?', '2/5', 'Há 2 cartões vermelhos em 5 cartões no total.', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Uma caixa tem 3 cartões azuis e 2 vermelhos. Qual é a probabilidade de tirar um vermelho?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Uma caixa tem 3 cartões azuis e 2 vermelhos. Qual é a probabilidade de tirar um vermelho?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '2/5', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Uma caixa tem 3 cartões azuis e 2 vermelhos. Qual é a probabilidade de tirar um vermelho?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '2/5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '3/5', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Uma caixa tem 3 cartões azuis e 2 vermelhos. Qual é a probabilidade de tirar um vermelho?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '3/5');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '1/2', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Uma caixa tem 3 cartões azuis e 2 vermelhos. Qual é a probabilidade de tirar um vermelho?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '1/2');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Divida a quantidade de cartões vermelhos pelo total.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Uma caixa tem 3 cartões azuis e 2 vermelhos. Qual é a probabilidade de tirar um vermelho?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

INSERT INTO questoes (fase_id, pergunta, resposta_correta, explicacao, pontuacao)
SELECT f.id, 'Qual é a área de um trapézio com bases 8 cm e 12 cm e altura 5 cm?', '50 cm²', 'A = (B+b)h/2 = (12+8)×5/2 = 50 cm².', 100 FROM fases f
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4
AND NOT EXISTS (SELECT 1 FROM questoes q WHERE q.fase_id = f.id AND q.pergunta = 'Qual é a área de um trapézio com bases 8 cm e 12 cm e altura 5 cm?');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '40 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um trapézio com bases 8 cm e 12 cm e altura 5 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '40 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '45 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um trapézio com bases 8 cm e 12 cm e altura 5 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '45 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '50 cm²', TRUE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um trapézio com bases 8 cm e 12 cm e altura 5 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '50 cm²');
INSERT INTO alternativas (questao_id, texto, correta)
SELECT q.id, '60 cm²', FALSE FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um trapézio com bases 8 cm e 12 cm e altura 5 cm?'
AND NOT EXISTS (SELECT 1 FROM alternativas a WHERE a.questao_id = q.id AND a.texto = '60 cm²');
INSERT INTO dicas (questao_id, ordem, texto, custo_xp)
SELECT q.id, 1, 'Some as bases, multiplique pela altura e divida por dois.', 0 FROM questoes q JOIN fases f ON f.id = q.fase_id
WHERE f.jogo_id = 2 AND f.serie = 9 AND f.numero = 4 AND q.pergunta = 'Qual é a área de um trapézio com bases 8 cm e 12 cm e altura 5 cm?'
AND NOT EXISTS (SELECT 1 FROM dicas d WHERE d.questao_id = q.id AND d.ordem = 1);

-- Conferência: deve haver 4 fases e 20 questões por série.
SELECT serie, COUNT(*) AS fases FROM fases WHERE jogo_id = 2 AND serie BETWEEN 6 AND 9 GROUP BY serie ORDER BY serie;
SELECT f.serie, f.numero, f.nome, COUNT(q.id) AS questoes
FROM fases f LEFT JOIN questoes q ON q.fase_id = f.id
WHERE f.jogo_id = 2 AND f.serie BETWEEN 6 AND 9
GROUP BY f.serie, f.numero, f.nome ORDER BY f.serie, f.numero;
SELECT f.serie, COUNT(q.id) AS total_questoes, SUM((SELECT COUNT(*) FROM alternativas a WHERE a.questao_id = q.id)) AS total_alternativas
FROM fases f JOIN questoes q ON q.fase_id = f.id
WHERE f.jogo_id = 2 AND f.serie BETWEEN 6 AND 9 GROUP BY f.serie ORDER BY f.serie;
