
const LARGURA = 1000;
const ALTURA = 650;

let nave;
let teclas;
let mensagem;
let painelMissao;
let textoMissao;
let planetaSelecionado = null;
let estrelasMovimento = [];

const config = {
    type: Phaser.AUTO,
    parent: "jogo",
    width: LARGURA,
    height: ALTURA,
    backgroundColor: "#050718",

    scale: {
        mode: Phaser.Scale.FIT,
        autoCenter: Phaser.Scale.CENTER_BOTH
    },

    scene: {
        create: criarJogo,
        update: atualizarJogo
    }
};

new Phaser.Game(config);


// ==========================================
// INICIALIZAÇÃO
// ==========================================

function criarJogo() {
    const cena = this;

    criarTexturaNebulosa(cena);
    criarTexturasPlanetas(cena);

    // Fundo da galáxia
    cena.add.image(500, 325, "nebulosa")
    .setDisplaySize(LARGURA, ALTURA)
    .setDepth(-10);
    // Camadas de estrelas: sensação de profundidade
    criarCampoEstelar(cena, 100, 1, 0.3);
    criarCampoEstelar(cena, 75, 2, 0.65);
    criarCampoEstelar(cena, 35, 3, 1);

    // Objetos no espaço
    criarPlaneta(cena, {
        x: 230,
        y: 250,
        escala: 1.05,
        textura: "planeta-roxo",
        nome: "Nebulosa de Cristal",
        descricao: "Missão: geometria e formas espaciais",
        cor: 0xc8a4ff,
        anel: true
    });

    criarPlaneta(cena, {
        x: 530,
        y: 410,
        escala: 0.8,
        textura: "planeta-azul",
        nome: "Mundo Oceânico",
        descricao: "Missão: equações e energia",
        cor: 0x65e5ff,
        anel: false
    });

    criarPlaneta(cena, {
        x: 790,
        y: 205,
        escala: 0.95,
        textura: "planeta-laranja",
        nome: "Planeta Solaris",
        descricao: "Missão: proporção e cálculos",
        cor: 0xffbd80,
        anel: true
    });

    criarAsteroides(cena);
    criarNave(cena);
    criarInterface(cena);
    criarEstrelaCadente(cena);

    // Estrelas cadentes aparecem periodicamente
    cena.time.addEvent({
        delay: 2400,
        callback: () => criarEstrelaCadente(cena),
        loop: true
    });
}


// ==========================================
// CRIAR O FUNDO COM NEBULOSAS
// ==========================================

function criarTexturaNebulosa(cena) {
    const largura = 1000;
    const altura = 650;

    const textura = cena.textures.createCanvas(
        "nebulosa",
        largura,
        altura
    );

    const ctx = textura.getContext();

    ctx.fillStyle = "#050718";
    ctx.fillRect(0, 0, largura, altura);

    // Gradientes coloridos que lembram nuvens de gás
    const nuvens = [
        { x: 190, y: 180, r: 280, cor: "105, 42, 190" },
        { x: 720, y: 170, r: 240, cor: "22, 80, 180" },
        { x: 490, y: 470, r: 280, cor: "145, 35, 130" },
        { x: 900, y: 500, r: 200, cor: "20, 125, 155" }
    ];

    nuvens.forEach(nuvem => {
        const gradiente = ctx.createRadialGradient(
            nuvem.x,
            nuvem.y,
            5,
            nuvem.x,
            nuvem.y,
            nuvem.r
        );

        gradiente.addColorStop(
            0,
            `rgba(${nuvem.cor}, 0.42)`
        );

        gradiente.addColorStop(
            0.45,
            `rgba(${nuvem.cor}, 0.17)`
        );

        gradiente.addColorStop(
            1,
            `rgba(${nuvem.cor}, 0)`
        );

        ctx.fillStyle = gradiente;

        ctx.fillRect(0, 0, largura, altura);
    });

    textura.refresh();
}


// ==========================================
// CRIAR TEXTURAS PROCEDURAIS DOS PLANETAS
// ==========================================

function criarTexturasPlanetas(cena) {
    criarTexturaPlaneta(cena, "planeta-roxo", "#bd86ff", "#6336b5");
    criarTexturaPlaneta(cena, "planeta-azul", "#64e7ff", "#1266a6");
    criarTexturaPlaneta(cena, "planeta-laranja", "#ffc17e", "#b64d39");
}

function criarTexturaPlaneta(cena, chave, corClara, corEscura) {
    const tamanho = 180;

    const textura = cena.textures.createCanvas(
        chave,
        tamanho,
        tamanho
    );

    const ctx = textura.getContext();
    const centro = tamanho / 2;
    const raio = 76;

    ctx.clearRect(0, 0, tamanho, tamanho);

    // Limitar os detalhes ao formato esférico
    ctx.save();
    ctx.beginPath();
    ctx.arc(centro, centro, raio, 0, Math.PI * 2);
    ctx.clip();

    // Cor e iluminação da superfície
    const base = ctx.createRadialGradient(
        65, 55, 5,
        centro, centro, 115
    );

    base.addColorStop(0, corClara);
    base.addColorStop(0.55, corEscura);
    base.addColorStop(1, "#090b20");

    ctx.fillStyle = base;
    ctx.fillRect(0, 0, tamanho, tamanho);

    // Faixas atmosféricas
    for (let i = 0; i < 12; i++) {
        const y = 25 + i * 12;

        ctx.beginPath();
        ctx.ellipse(
            centro,
            y,
            70 + Math.sin(i) * 9,
            3 + Math.random() * 5,
            Math.sin(i) * 0.12,
            0,
            Math.PI * 2
        );

        ctx.fillStyle = i % 2 === 0
            ? "rgba(255,255,255,0.10)"
            : "rgba(0,0,30,0.12)";

        ctx.fill();
    }

    // Crateras e manchas na superfície
    for (let i = 0; i < 17; i++) {
        const angulo = Math.random() * Math.PI * 2;
        const distancia = Math.random() * 60;
        const x = centro + Math.cos(angulo) * distancia;
        const y = centro + Math.sin(angulo) * distancia;
        const r = 2 + Math.random() * 8;

        ctx.beginPath();
        ctx.arc(x, y, r, 0, Math.PI * 2);

        ctx.fillStyle = "rgba(10, 10, 35, 0.15)";
        ctx.fill();

        ctx.strokeStyle = "rgba(255, 255, 255, 0.10)";
        ctx.lineWidth = 1;
        ctx.stroke();
    }

    ctx.restore();

    // Sombra global: a parte direita fica mais escura
    const sombra = ctx.createLinearGradient(
        35, 0, 155, 0
    );

    sombra.addColorStop(0, "rgba(0,0,0,0)");
    sombra.addColorStop(0.45, "rgba(0,0,0,0.04)");
    sombra.addColorStop(1, "rgba(0,0,0,0.70)");

    ctx.beginPath();
    ctx.arc(centro, centro, raio, 0, Math.PI * 2);
    ctx.fillStyle = sombra;
    ctx.fill();

    // Contorno luminoso
    ctx.beginPath();
    ctx.arc(centro, centro, raio, 0, Math.PI * 2);
    ctx.strokeStyle = "rgba(220,235,255,0.45)";
    ctx.lineWidth = 2;
    ctx.stroke();

    textura.refresh();
}


// ==========================================
// CAMPO ESTELAR
// ==========================================

function criarCampoEstelar(cena, quantidade, tamanho, alpha) {
    for (let i = 0; i < quantidade; i++) {
        const estrela = cena.add.circle(
            Phaser.Math.Between(0, LARGURA),
            Phaser.Math.Between(0, ALTURA),
            Math.random() * tamanho + 0.4,
            0xffffff,
            alpha
        );

        

        // Brilho pulsante
        cena.tweens.add({
            targets: estrela,
            alpha: Math.max(0.1, alpha * 0.25),
            duration: Phaser.Math.Between(700, 2600),
            yoyo: true,
            repeat: -1,
            delay: Math.random() * 1800
        });

        // Pequeno movimento vertical
        estrelasMovimento.push({
            objeto: estrela,
            velocidade: Math.random() * 0.12 + 0.02
        });
    }
}


// ==========================================
// PLANETAS COM ANÉIS E BRILHO
// ==========================================

function criarPlaneta(cena, dados) {
    const grupo = cena.add.container(dados.x, dados.y);

    // Aura externa
    const aura = cena.add.circle(
        0, 0, 100, dados.cor, 0.07
    );

    const auraInterna = cena.add.circle(
        0, 0, 76, dados.cor, 0.09
    );

    grupo.add([aura, auraInterna]);

    // Anéis atrás do planeta
    if (dados.anel) {
        const aneis = cena.add.graphics();

        aneis.lineStyle(3, dados.cor, 0.75);
        aneis.strokeEllipse(0, 0, 200, 58);

        aneis.lineStyle(7, dados.cor, 0.16);
        aneis.strokeEllipse(0, 0, 214, 68);

        grupo.add(aneis);
    }

    // Esfera com textura
    const esfera = cena.add.image(
        0, 0, dados.textura
    );

    esfera.setDisplaySize(
        150 * dados.escala,
        150 * dados.escala
    );

    grupo.add(esfera);

    // Nome do planeta
    const nome = cena.add.text(
        0, 100 * dados.escala,
        dados.nome,
        {
            fontSize: "15px",
            color: "#ffffff",
            fontStyle: "bold",
            stroke: "#080718",
            strokeThickness: 4
        }
    ).setOrigin(0.5);

    grupo.add(nome);

    // Área clicável invisível
    const clique = cena.add.circle(
        0, 0, 85 * dados.escala, 0xffffff, 0
    );

    clique.setInteractive({ useHandCursor: true });
    grupo.add(clique);

    grupo.setPosition(dados.x, dados.y);

    // Pulsação da aura
    cena.tweens.add({
        targets: [aura, auraInterna],
        scale: 1.25,
        alpha: 0.03,
        duration: 1800,
        yoyo: true,
        repeat: -1
    });

    // Rotação sutil do planeta
    cena.tweens.add({
        targets: esfera,
        angle: 360,
        duration: 50000,
        repeat: -1
    });

    // Flutuação do planeta inteiro
    cena.tweens.add({
        targets: grupo,
        y: dados.y - 9,
        duration: 2300 + Math.random() * 1000,
        yoyo: true,
        repeat: -1,
        ease: "Sine.inOut"
    });

    clique.on("pointerover", () => {
        planetaSelecionado = dados;

        mensagem.setText("✦ " + dados.nome);
        aura.setAlpha(0.28);

        cena.tweens.add({
            targets: esfera,
            scale: 1.08,
            duration: 180,
            yoyo: true
        });
    });

    clique.on("pointerout", () => {
        aura.setAlpha(0.07);
    });

    clique.on("pointerdown", () => {
        abrirMissao(cena, dados);
    });
}


// ==========================================
// PAINEL DE MISSÃO
// ==========================================

function criarInterface(cena) {
    cena.add.text(
        22, 20,
        "MATHSPACE",
        {
            fontSize: "25px",
            color: "#e5d8ff",
            fontStyle: "bold",
            stroke: "#2c195d",
            strokeThickness: 3
        }
    );

    cena.add.text(
        24, 52,
        "SISTEMA DE EXPLORAÇÃO",
        {
            fontSize: "11px",
            color: "#8caddf",
            letterSpacing: 2
        }
    );

    // Painel inferior
    painelMissao = cena.add.rectangle(
        500, 585, 550, 78,
        0x11152f, 0.92
    );

    painelMissao.setStrokeStyle(1, 0x8e7cff, 0.8);

    textoMissao = cena.add.text(
        500, 585,
        "Escolha um planeta para descobrir sua missão.",
        {
            fontSize: "16px",
            color: "#ffffff",
            align: "center",
            wordWrap: { width: 490 }
        }
    ).setOrigin(0.5);

    mensagem = cena.add.text(
        500, 100,
        "Explore a galáxia",
        {
            fontSize: "16px",
            color: "#d8caff",
            backgroundColor: "#17152e",
            padding: { x: 12, y: 8 }
        }
    ).setOrigin(0.5);
}

function abrirMissao(cena, dados) {
    planetaSelecionado = dados;

    textoMissao.setText(
        dados.nome + "\n" + dados.descricao
    );

    cena.cameras.main.shake(100, 0.0015);

    // Efeito de destaque
    cena.tweens.add({
        targets: painelMissao,
        alpha: 0.5,
        duration: 100,
        yoyo: true
    });
}


// ==========================================
// NAVE
// ==========================================

function criarNave(cena) {
    nave = cena.add.container(500, 130);

    // Brilho atrás da nave
    const brilho = cena.add.circle(
        0, 0, 24, 0x55ddff, 0.2
    );

    // Corpo da nave
    const corpo = cena.add.triangle(
        0, 0,
        0, -24,
        17, 19,
        -17, 19,
        0xe9f6ff
    );

    corpo.setStrokeStyle(2, 0x7deaff);

    // Propulsor
    const motor = cena.add.triangle(
        0, 22,
        -6, -1,
        6, -1,
        0, 14,
        0x8d68ff
    );

    nave.add([brilho, corpo, motor]);

    nave.brilho = brilho;
    nave.motor = motor;

    teclas = cena.input.keyboard.createCursorKeys();

    // Chama animada do motor
    cena.tweens.add({
        targets: motor,
        scaleY: 1.7,
        alpha: 0.3,
        duration: 150,
        yoyo: true,
        repeat: -1
    });
}


// ==========================================
// ASTEROIDES
// ==========================================

function criarAsteroides(cena) {
    for (let i = 0; i < 12; i++) {
        const x = Phaser.Math.Between(20, 980);
        const y = Phaser.Math.Between(80, 560);
        const tamanho = Phaser.Math.Between(3, 8);

        const asteroide = cena.add.circle(
            x, y, tamanho,
            0x9999b8, 0.65
        );

        asteroide.setStrokeStyle(1, 0xffffff, 0.25);

        cena.tweens.add({
            targets: asteroide,
            x: x + Phaser.Math.Between(-35, 35),
            y: y + Phaser.Math.Between(-25, 25),
            angle: 180,
            duration: Phaser.Math.Between(3000, 8000),
            yoyo: true,
            repeat: -1,
            ease: "Sine.inOut"
        });
    }
}


// ==========================================
// ESTRELAS CADENTES
// ==========================================

function criarEstrelaCadente(cena) {
    const x = Phaser.Math.Between(100, 950);
    const y = Phaser.Math.Between(50, 450);

    const risco = cena.add.graphics();

    risco.lineStyle(2, 0xc4f4ff, 0.9);
    risco.beginPath();
    risco.moveTo(0, 0);
    risco.lineTo(-65, -25);
    risco.strokePath();

    risco.setPosition(x, y);

    cena.tweens.add({
        targets: risco,
        x: x + 180,
        y: y + 80,
        alpha: 0,
        duration: 750,
        onComplete: () => risco.destroy()
    });
}


// ==========================================
// ATUALIZAR A CENA
// ==========================================

function atualizarJogo() {
    if (!nave || !teclas) return;

    const velocidade = 3.5;

    if (teclas.left.isDown) nave.x -= velocidade;
    if (teclas.right.isDown) nave.x += velocidade;
    if (teclas.up.isDown) nave.y -= velocidade;
    if (teclas.down.isDown) nave.y += velocidade;

    nave.x = Phaser.Math.Clamp(nave.x, 25, 975);
    nave.y = Phaser.Math.Clamp(nave.y, 80, 540);

    // Estrelas da camada próxima movem-se discretamente
    estrelasMovimento.forEach(item => {
        item.objeto.y += item.velocidade;

        if (item.objeto.y > ALTURA) {
            item.objeto.y = 0;
        }
    });
}
