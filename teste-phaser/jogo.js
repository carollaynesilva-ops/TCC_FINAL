
const config = {
    type: Phaser.AUTO,

    width: 1000,
    height: 600,

    parent: "jogo",

    backgroundColor: "#080b24",

    scene: {
        create: criarJogo,
        update: atualizarJogo
    }
};

let nave;
let teclas;
let mensagem;
let velocidade = 4;

const jogo = new Phaser.Game(config);


// =====================================
// CRIAR O UNIVERSO
// =====================================

function criarJogo() {
    const cena = this;

    // Título dentro do jogo
    cena.add.text(
        25,
        20,
        "SETOR DE EXPLORACAO",
        {
            fontSize: "20px",
            color: "#d9c5ff",
            fontStyle: "bold"
        }
    );

    // Criar estrelas
    for (let i = 0; i < 160; i++) {
        const x = Phaser.Math.Between(0, 1000);
        const y = Phaser.Math.Between(0, 600);
        const tamanho = Math.random() * 2 + 1;

        const estrela = cena.add.circle(
            x,
            y,
            tamanho,
            0xffffff
        );

        estrela.setAlpha(
            Phaser.Math.FloatBetween(0.2, 1)
        );

        // Fazer as estrelas piscarem
        cena.tweens.add({
            targets: estrela,
            alpha: 0.15,
            duration: Phaser.Math.Between(500, 2000),
            yoyo: true,
            repeat: -1
        });
    }

    // Criar planetas
    criarPlaneta(
        cena, 220, 230,
        0xa78bfa,
        "GEOMETRIA"
    );

    criarPlaneta(
        cena, 500, 360,
        0x45d9ff,
        "EQUACOES"
    );

    criarPlaneta(
        cena, 780, 220,
        0xffad70,
        "PROPORCAO"
    );

    // Criar nave
    nave = cena.add.triangle(
        500, 150,
        0, -22,
        17, 18,
        -17, 18,
        0xffffff
    );

    nave.setStrokeStyle(3, 0x6ee7ff);

    // Brilho da nave
    const brilhoNave = cena.add.circle(
        500, 150, 25, 0x42dfff, 0.15
    );

    brilhoNave.setDepth(-1);

    // Guardar referência do brilho
    nave.brilho = brilhoNave;

    // Controles
    teclas = cena.input.keyboard.createCursorKeys();

    // Mensagem na parte inferior
    mensagem = cena.add.text(
        500,
        555,
        "Explore o universo!",
        {
            fontSize: "19px",
            color: "#ffffff",
            backgroundColor: "#24204b",
            padding: {
                x: 14,
                y: 9
            }
        }
    );

    mensagem.setOrigin(0.5);
}


// =====================================
// CRIAR PLANETAS
// =====================================

function criarPlaneta(cena, x, y, cor, nome) {

    // Aura do planeta
    const aura = cena.add.circle(
        x, y, 48, cor, 0.15
    );

    // Corpo do planeta
    const planeta = cena.add.circle(
        x, y, 30, cor
    );

    planeta.setStrokeStyle(3, 0xffffff, 0.7);

    planeta.setInteractive({
        useHandCursor: true
    });

    // Nome abaixo do planeta
    cena.add.text(
        x,
        y + 53,
        nome,
        {
            fontSize: "15px",
            color: "#ffffff",
            fontStyle: "bold"
        }
    ).setOrigin(0.5);

    // Animação contínua
    cena.tweens.add({
        targets: [planeta, aura],
        scale: 1.15,
        duration: 1200,
        yoyo: true,
        repeat: -1
    });

    // Efeito ao passar o mouse
    planeta.on("pointerover", () => {
        planeta.setStrokeStyle(4, 0xffe7a3);
        mensagem.setText("Planeta: " + nome);
    });

    planeta.on("pointerout", () => {
        planeta.setStrokeStyle(3, 0xffffff, 0.7);
    });

    // Efeito ao clicar
    planeta.on("pointerdown", () => {
        mensagem.setText(
            "Missao selecionada: " + nome
        );

        cena.cameras.main.flash(
            180,
            100,
            80,
            255,
            false
        );
    });
}


// =====================================
// MOVIMENTAR A NAVE
// =====================================

function atualizarJogo() {

    if (!nave || !teclas) {
        return;
    }

    if (teclas.left.isDown) {
        nave.x -= velocidade;
    }

    if (teclas.right.isDown) {
        nave.x += velocidade;
    }

    if (teclas.up.isDown) {
        nave.y -= velocidade;
    }

    if (teclas.down.isDown) {
        nave.y += velocidade;
    }

    // Manter a nave dentro da tela
    nave.x = Phaser.Math.Clamp(
        nave.x, 25, 975
    );

    nave.y = Phaser.Math.Clamp(
        nave.y, 80, 570
    );

    // O brilho acompanha a nave
    nave.brilho.setPosition(
        nave.x, nave.y
    );
}
