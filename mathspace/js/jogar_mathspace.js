
(() => {
    "use strict";

    const cfg = window.MATHSPACE_GAME;

    if (!cfg) {
        console.error("Configuração do MathSpace não encontrada.");
        return;
    }

    // =========================================================
    // ELEMENTOS DA PÁGINA
    // =========================================================

    const $ = (selector) => document.querySelector(selector);

    const ui = {
        score: $("#score"),
        lives: $("#lives"),
        shieldFill: $("#shield-fill"),
        shieldValue: $("#shield-value"),
        phaseName: $("#phase-name"),
        phaseDescription: $("#phase-description"),

        questionPanel: $("#question-panel"),
        questionText: $("#question-text"),
        questionOptions: $("#answer-list"), questionTimer: $("#timer-value"),
        timerProgress: $("#timer-progress"),
        questionFeedback: $("#question-feedback"),

        pauseOverlay: $("#pause-overlay"),
        resultOverlay: $("#result-overlay"),
        resultTitle: $("#result-title"),
        resultMessage: $("#result-message"),
        resultScore: $("#result-score"),

        startButton: $("#start-button"),
        pauseButton: $("#pause-button"),
        resumeButton: $("#resume-button"),
        restartButton: $("#restart-button"),
        backButton: $("#back-button")
    };

    // =========================================================
    // ESTADO DO JOGO
    // =========================================================

    const state = {
        game: null,
        scene: null,

        question: null,
        questionIndex: 0,
        totalQuestions: 6,

        score: 0,
        lives: 3,
        correct: 0,
        shield: 100,

        started: false,
        paused: false,
        finished: false,
        locked: false,

        timer: null,
        timeLeft: 20,

        requestInProgress: false,
        damageInProgress: false,
        pendingFinish: false,

        // Evita que uma sequência de projéteis tire várias vidas
        // antes que o jogador consiga reagir.
        invulnerableUntil: 0
    };

    const QUESTION_TIME = 20;
    const MAX_SHIELD = 100;
    const SHIELD_DAMAGE = 20;
    const MAX_LIVES = 3;

    // =========================================================
    // COMUNICAÇÃO COM O PHP
    // =========================================================

    async function api(action, extra = {}) {
        const response = await fetch(cfg.api, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            credentials: "same-origin",
            body: JSON.stringify({
                action,
                acao: action,

                fase_id: cfg.faseId,
                faseId: cfg.faseId,

                csrf: cfg.csrf || "",

                ...extra
            })
        });

        let data;

        try {
            data = await response.json();
        } catch {
            throw new Error("O servidor retornou uma resposta inválida.");
        }

        if (!response.ok || data.sucesso === false || data.success === false) {
            throw new Error(
                data.mensagem ||
                data.message ||
                "Não foi possível completar essa ação."
            );
        }

        return data;
    }

    // =========================================================
    // UTILITÁRIOS
    // =========================================================

    function setText(element, value) {
        if (element) {
            element.textContent = value;
        }
    }

    function getQuestion(data) {
        return data.questao || data.question || data.pergunta || null;
    }

    function getAlternatives(question) {
        if (!question) return [];

        return (
            question.alternativas ||
            question.alternatives ||
            question.opcoes ||
            []
        );
    }

    function getQuestionId(question) {
        return question?.id ??
            question?.questao_id ??
            question?.question_id ??
            null;
    }

    function getAlternativeId(alternative) {
        return alternative?.id ??
            alternative?.alternativa_id ??
            null;
    }

    function showFeedback(message, type = "info") {
        if (!ui.questionFeedback) return;

        ui.questionFeedback.textContent = message;
        ui.questionFeedback.className = `question-feedback ${type}`;
        ui.questionFeedback.hidden = false;
    }

    function clearFeedback() {
        if (!ui.questionFeedback) return;

        ui.questionFeedback.textContent = "";
        ui.questionFeedback.className = "question-feedback";
        ui.questionFeedback.hidden = true;
    }

    function setOptionsDisabled(disabled) {
        if (!ui.questionOptions) return;

        ui.questionOptions
            .querySelectorAll("button")
            .forEach((button) => {
                button.disabled = disabled;
            });
    }

    function renderScore() {
        setText(ui.score, state.score);
    }

    function renderLives() {
        if (!ui.lives) return;

        // Também funciona caso o HUD use corações individuais.
        const hearts = ui.lives.querySelectorAll("[data-heart]");

        if (hearts.length) {
            hearts.forEach((heart, index) => {
                heart.classList.toggle("lost", index >= state.lives);
                heart.classList.toggle("active", index < state.lives);
            });
        } else {
            ui.lives.textContent = "♥ ".repeat(state.lives) +
                "♡ ".repeat(MAX_LIVES - state.lives);
        }
    }

    function renderShield() {
        const shield = Math.max(0, Math.min(MAX_SHIELD, state.shield));

        if (ui.shieldFill) {
            ui.shieldFill.style.width = `${shield}%`;
            ui.shieldFill.setAttribute("aria-valuenow", String(shield));

            ui.shieldFill.classList.toggle("shield-warning", shield <= 40);
            ui.shieldFill.classList.toggle("shield-critical", shield <= 20);
        }

        setText(ui.shieldValue, `${shield}%`);
    }

    function renderHUD() {
        renderScore();
        renderLives();
        renderShield();
    }

    function syncServerState(data) {
        if (Number.isFinite(Number(data.pontuacao))) {
            state.score = Number(data.pontuacao);
        } else if (Number.isFinite(Number(data.score))) {
            state.score = Number(data.score);
        }

        if (Number.isFinite(Number(data.vidas))) {
            state.lives = Number(data.vidas);
        } else if (Number.isFinite(Number(data.lives))) {
            state.lives = Number(data.lives);
        }

        if (Number.isFinite(Number(data.escudo))) {
            state.shield = Number(data.escudo);
        } else if (Number.isFinite(Number(data.shield))) {
            state.shield = Number(data.shield);
        } else if (Number.isFinite(Number(data.integridade))) {
            state.shield = Number(data.integridade);
        }

        if (Number.isFinite(Number(data.acertos))) {
            state.correct = Number(data.acertos);
        } else if (Number.isFinite(Number(data.correctCount))) {
            state.correct = Number(data.correctCount);
        }

        renderHUD();
    }

    function setOverlayVisible(element, visible) {
        if (!element) return;

        element.hidden = !visible;
        element.classList.toggle("visible", visible);
    }

    function enterFullscreen() {
        const element = document.documentElement;

        if (!document.fullscreenElement && element.requestFullscreen) {
            element.requestFullscreen().catch(() => {
                // Alguns navegadores bloqueiam tela cheia.
                // O jogo continua funcionando sem ela.
            });
        }
    }

    // =========================================================
    // TEMPORIZADOR DA PERGUNTA
    // =========================================================

    function stopQuestionTimer() {
        if (state.timer !== null) {
            clearInterval(state.timer);
            state.timer = null;
        }
    }

    function renderTimer() {
        setText(ui.questionTimer, `${state.timeLeft}s`);

        if (ui.timerProgress) {
            const percentage = (state.timeLeft / QUESTION_TIME) * 100;

            ui.timerProgress.style.width = `${percentage}%`;
            ui.timerProgress.classList.toggle(
                "timer-warning",
                state.timeLeft <= 10
            );
            ui.timerProgress.classList.toggle(
                "timer-critical",
                state.timeLeft <= 5
            );
        }
    }

    function startQuestionTimer() {
        stopQuestionTimer();

        state.timeLeft = QUESTION_TIME;
        renderTimer();

        state.timer = setInterval(() => {
            if (
                state.finished ||
                state.paused ||
                !state.locked ||
                state.requestInProgress
            ) {
                return;
            }

            state.timeLeft -= 1;
            renderTimer();

            if (state.timeLeft <= 0) {
                stopQuestionTimer();
                handleTimeout();
            }
        }, 1000);
    }

    // =========================================================
    // CONTROLE DO PAINEL DE PERGUNTAS
    // =========================================================

    function showQuestion() {
        if (
            state.finished ||
            state.paused ||
            !state.question ||
            state.locked
        ) {
            return;
        }

        state.locked = true;

        if (state.scene) {
            state.scene.scene.pause();
        }

        clearFeedback();

        const questionText =
            state.question.pergunta ||
            state.question.texto ||
            state.question.enunciado ||
            "Resolva o desafio para continuar.";

        setText(ui.questionText, questionText);

        if (ui.questionOptions) {
            ui.questionOptions.replaceChildren();

            const alternatives = getAlternatives(state.question);

            alternatives.forEach((alternative, index) => {
                const button = document.createElement("button");

                button.type = "button";
                button.className = "answer-option";
                button.textContent =
                    alternative.texto ??
                    alternative.text ??
                    alternative.descricao ??
                    `Alternativa ${index + 1}`;

                button.addEventListener("click", () => {
                    submitAnswer(alternative, button);
                });

                ui.questionOptions.appendChild(button);
            });
        }

        setOverlayVisible(ui.questionPanel, true);
        startQuestionTimer();
    }

    function closeQuestion() {
        stopQuestionTimer();
        setOverlayVisible(ui.questionPanel, false);

        state.locked = false;
        state.requestInProgress = false;
        state.timeLeft = QUESTION_TIME;

        clearFeedback();
    }

    async function loadNextQuestion() {
        try {
            const data = await api("question");

            const question = getQuestion(data);

            if (!question) {
                if (data.finished || data.concluida) {
                    await finishRun();
                    return;
                }

                throw new Error("O servidor não encontrou a próxima pergunta.");
            }

            state.question = question;

            const index = data.indice ?? data.questionIndex;

            if (Number.isFinite(Number(index))) {
                state.questionIndex = Number(index);
            } else {
                state.questionIndex += 1;
            }

            // A pergunta só aparece quando o laser atingir outro alien.
            closeQuestion();

            if (state.scene && !state.paused && !state.finished) {
                state.scene.scene.resume();
            }
        } catch (error) {
            console.error(error);
            showFeedback(
                "Não foi possível carregar o próximo desafio. Tente novamente.",
                "error"
            );

            // Não deixa a cena travada indefinidamente.
            closeQuestion();

            if (state.scene && !state.paused && !state.finished) {
                state.scene.scene.resume();
            }
        }
    }

    // =========================================================
    // RESPOSTA DO JOGADOR
    // =========================================================

    async function submitAnswer(alternative, button) {
        if (
            state.requestInProgress ||
            state.finished ||
            !state.locked
        ) {
            return;
        }

        const questionId = getQuestionId(state.question);
        const alternativeId = getAlternativeId(alternative);

        if (!questionId || !alternativeId) {
            showFeedback(
                "Esta alternativa não possui um identificador válido.",
                "error"
            );
            return;
        }

        state.requestInProgress = true;
        stopQuestionTimer();
        setOptionsDisabled(true);

        if (button) {
            button.classList.add("selected");
        }

        try {
            const data = await api("answer", {
                questao_id: questionId,
                question_id: questionId,
                alternativa_id: alternativeId,
                alternative_id: alternativeId
            });

            const isCorrect = Boolean(
                data.correta ??
                data.correct ??
                data.acertou
            );

            syncServerState(data);

            if (isCorrect) {
                showFeedback(
                    data.explicacao ||
                    data.explanation ||
                    "Resposta correta! O alien foi derrotado.",
                    "correct"
                );

                if (button) {
                    button.classList.add("correct");
                }

                if (state.scene) {
                    state.scene.destroyAlienTarget();
                }
            } else {
                showFeedback(
                    data.explicacao ||
                    data.explanation ||
                    "Resposta incorreta! O alien conseguiu atacar.",
                    "wrong"
                );

                if (button) {
                    button.classList.add("incorrect");
                }

                if (state.scene) {
                    state.scene.showAlienAttack();
                }
            }

            const ended = Boolean(
                data.finished ||
                data.fim ||
                data.concluida ||
                state.lives <= 0
            );

            state.pendingFinish = ended;

            window.setTimeout(async () => {
                if (state.finished) return;

                if (state.pendingFinish) {
                    await finishRun();
                    return;
                }

                await loadNextQuestion();
            }, 1000);
        } catch (error) {
            console.error(error);

            showFeedback(
                error.message ||
                "Não foi possível registrar sua resposta. Tente novamente.",
                "error"
            );

            // Permite tentar novamente sem perder a pergunta.
            state.requestInProgress = false;
            setOptionsDisabled(false);
            startQuestionTimer();
        }
    }

    // =========================================================
    // TEMPO ESGOTADO
    // =========================================================

    async function handleTimeout() {
        if (
            state.finished ||
            state.requestInProgress ||
            !state.locked
        ) {
            return;
        }

        state.requestInProgress = true;
        stopQuestionTimer();
        setOptionsDisabled(true);

        const questionId = getQuestionId(state.question);

        try {
            const data = await api("timeout", {
                questao_id: questionId,
                question_id: questionId
            });

            syncServerState(data);

            showFeedback(
                "Tempo esgotado! O alien atacou e você perdeu uma vida.",
                "wrong"
            );

            if (state.scene) {
                state.scene.showAlienAttack();
            }

            state.pendingFinish = Boolean(
                data.finished ||
                data.fim ||
                state.lives <= 0
            );

            window.setTimeout(async () => {
                if (state.finished) return;

                if (state.pendingFinish) {
                    await finishRun();
                    return;
                }

                await loadNextQuestion();
            }, 1000);
        } catch (error) {
            console.error(error);

            showFeedback(
                error.message ||
                "Não foi possível registrar o tempo esgotado.",
                "error"
            );

            // Não deixa os botões permanentemente desativados.
            state.requestInProgress = false;
            setOptionsDisabled(false);
            startQuestionTimer();
        }
    }

    // =========================================================
    // DANO AO ESCUDO
    // =========================================================

    async function handleShipHit() {
        const now = Date.now();

        if (
            state.finished ||
            state.paused ||
            state.locked ||
            state.damageInProgress ||
            now < state.invulnerableUntil
        ) {
            return;
        }

        state.damageInProgress = true;
        state.invulnerableUntil = now + 900;

        // O dano visual é imediato. O servidor confirma o estado real.
        state.shield = Math.max(0, state.shield - SHIELD_DAMAGE);
        renderShield();

        if (state.scene) {
            state.scene.flashPlayer();
        }

        try {
            const data = await api("damage", {
                dano: SHIELD_DAMAGE,
                damage: SHIELD_DAMAGE,
                integridade: state.shield,
                shield: state.shield
            });

            syncServerState(data);

            if (state.lives <= 0 || data.finished || data.fim) {
                await finishRun();
            }
        } catch (error) {
            console.error(error);

            // Se o servidor falhar, tenta sincronizar o HUD na próxima ação.
            showFeedback(
                "Falha ao registrar o dano. Verifique sua conexão.",
                "error"
            );
        } finally {
            state.damageInProgress = false;
        }
    }

    // =========================================================
    // PAUSAR E RETOMAR
    // =========================================================

    function pauseGame() {
        if (!state.started || state.finished || state.paused) return;

        state.paused = true;

        if (state.scene) {
            state.scene.scene.pause();
        }

        if (state.locked) {
            stopQuestionTimer();
        }

        setOverlayVisible(ui.pauseOverlay, true);
    }

    function resumeGame() {
        if (!state.paused || state.finished) return;

        state.paused = false;
        setOverlayVisible(ui.pauseOverlay, false);

        if (state.locked) {
            startQuestionTimer();
        } else if (state.scene) {
            state.scene.scene.resume();
        }
    }

    // =========================================================
    // FINALIZAÇÃO DA MISSÃO
    // =========================================================

    async function finishRun() {
        if (state.finished) return;

        state.finished = true;
        state.paused = false;

        stopQuestionTimer();

        if (state.scene) {
            state.scene.scene.pause();
        }

        setOverlayVisible(ui.pauseOverlay, false);
        setOverlayVisible(ui.questionPanel, false);

        let result = null;

        try {
            result = await api("finish", {
                pontuacao: state.score,
                score: state.score,
                acertos: state.correct,
                correct: state.correct,
                vidas: state.lives,
                lives: state.lives
            });

            syncServerState(result);
        } catch (error) {
            console.error("Erro ao finalizar missão:", error);
        }

        const success = Boolean(
            result?.sucesso_missao ??
            result?.success ??
            result?.vitoria ??
            (state.lives > 0 && state.questionIndex >= state.totalQuestions)
        );

        setText(
            ui.resultTitle,
            success ? "Missão concluída!" : "Missão encerrada"
        );

        setText(
            ui.resultMessage,
            success
                ? (
                    result?.mensagem ||
                    "Você concluiu os desafios desta fase."
                )
                : (
                    result?.mensagem ||
                    "Sua nave não conseguiu concluir a missão. Recupere suas forças e tente novamente."
                )
        );

        setText(ui.resultScore, state.score);

        setOverlayVisible(ui.resultOverlay, true);

        if (document.fullscreenElement && document.exitFullscreen) {
            document.exitFullscreen().catch(() => { });
        }
    }

    // =========================================================
    // CENA PHASER
    // =========================================================

    class SpaceScene extends Phaser.Scene {
        constructor() {
            super("SpaceScene");

            this.player = null;
            this.stars = [];
            this.playerLasers = [];
            this.enemyBullets = [];
            this.aliens = [];

            this.lastLaserTime = 0;
            this.lastAlienTime = 0;
            this.lastEnemyShotTime = 0;

            this.pointerX = null;
            this.playerHit = false;
        }

        create() {
            state.scene = this;

            const width = this.scale.width;
            const height = this.scale.height;

            this.cameras.main.setBackgroundColor("#050817");

            this.createStars(width, height);
            this.createPlayer(width, height);

            this.input.on("pointermove", (pointer) => {
                this.pointerX = pointer.x;
            });

            this.input.on("pointerdown", (pointer) => {
                this.pointerX = pointer.x;
            });

            this.scale.on("resize", (size) => {
                if (this.player) {
                    this.player.y = size.height - 85;
                    this.player.x = Phaser.Math.Clamp(
                        this.player.x,
                        35,
                        size.width - 35
                    );
                }
            });
        }

        createStars(width, height) {
            for (let i = 0; i < 90; i++) {
                const star = this.add.circle(
                    Phaser.Math.Between(0, width),
                    Phaser.Math.Between(0, height),
                    Phaser.Math.Between(1, 2),
                    0xffffff,
                    Phaser.Math.FloatBetween(0.2, 0.8)
                );

                star.speed = Phaser.Math.FloatBetween(0.3, 1.7);
                this.stars.push(star);
            }
        }

        createPlayer(width, height) {
            this.player = this.add.container(width / 2, height - 85);

            const body = this.add.polygon(
                0,
                0,
                [
                    0, -28,
                    21, 20,
                    0, 12,
                    -21, 20
                ],
                0x9c8cff
            );

            const cockpit = this.add.ellipse(
                0,
                -5,
                10,
                17,
                0x9de7ff
            );

            const leftWing = this.add.triangle(
                -17,
                12,
                0, 0,
                -12, 15,
                6, 12,
                0x5e67db
            );

            const rightWing = this.add.triangle(
                17,
                12,
                0, 0,
                12, 15,
                -6, 12,
                0x5e67db
            );

            const engine = this.add.ellipse(
                0,
                22,
                10,
                15,
                0x64dcff,
                0.85
            );

            this.player.add([
                body,
                leftWing,
                rightWing,
                cockpit,
                engine
            ]);

            this.player.setDepth(10);
        }

        update(time, delta) {
            if (
                state.finished ||
                state.paused ||
                state.locked ||
                !this.player
            ) {
                return;
            }

            this.updateStars(delta);
            this.updatePlayer(delta);
            this.updateLasers(delta);
            this.updateAliens(time);
            this.updateEnemyBullets();
        }

        updateStars(delta) {
            this.stars.forEach((star) => {
                star.y += star.speed * (delta / 16);

                if (star.y > this.scale.height) {
                    star.y = 0;
                    star.x = Phaser.Math.Between(0, this.scale.width);
                }
            });
        }

        updatePlayer(delta) {
            if (this.pointerX === null) return;

            const targetX = Phaser.Math.Clamp(
                this.pointerX,
                35,
                this.scale.width - 35
            );

            this.player.x = Phaser.Math.Linear(
                this.player.x,
                targetX,
                Math.min(1, delta / 75)
            );
        }

        updateLasers(delta) {
            const time = this.time.now;

            if (time - this.lastLaserTime >= 240) {
                this.lastLaserTime = time;
                this.fireLaser();
            }

            this.playerLasers = this.playerLasers.filter((laser) => {
                if (!laser.active) return false;

                laser.y -= 10;

                if (laser.y < -20) {
                    laser.destroy();
                    return false;
                }

                for (const alien of this.aliens) {
                    if (!alien.active) continue;

                    const distance = Phaser.Math.Distance.Between(
                        laser.x,
                        laser.y,
                        alien.x,
                        alien.y
                    );

                    if (distance < 28) {
                        laser.destroy();
                        this.onLaserHitAlien(alien);
                        return false;
                    }
                }

                return true;
            });
        }

        fireLaser() {
            if (!this.player || state.locked || state.finished) return;

            [-10, 10].forEach((offset) => {
                const laser = this.add.rectangle(
                    this.player.x + offset,
                    this.player.y - 25,
                    4,
                    18,
                    0x6ef7ff
                );

                laser.setDepth(8);
                this.playerLasers.push(laser);
            });
        }

        updateAliens(time) {
            if (time - this.lastAlienTime >= 1900 && this.aliens.length < 3) {
                this.lastAlienTime = time;
                this.spawnAlien();
            }

            this.aliens.forEach((alien) => {
                if (!alien.active) return;

                alien.y += alien.speed;
                alien.x += Math.sin(time / 450 + alien.phase) * 0.8;

                if (alien.y > this.scale.height + 35) {
                    alien.destroy();
                }
            });

            this.aliens = this.aliens.filter((alien) => alien.active);

            if (
                this.aliens.length > 0 &&
                time - this.lastEnemyShotTime >= 1450
            ) {
                this.lastEnemyShotTime = time;
                this.fireEnemyBullet();
            }
        }

        spawnAlien() {
            const x = Phaser.Math.Between(45, this.scale.width - 45);
            const alien = this.add.container(x, -35);

            const body = this.add.ellipse(
                0,
                0,
                35,
                25,
                0xff658e
            );

            const eyeLeft = this.add.circle(-8, -2, 4, 0xffffff);
            const eyeRight = this.add.circle(8, -2, 4, 0xffffff);
            const pupilLeft = this.add.circle(-8, -2, 2, 0x20223c);
            const pupilRight = this.add.circle(8, -2, 2, 0x20223c);

            alien.add([
                body,
                eyeLeft,
                eyeRight,
                pupilLeft,
                pupilRight
            ]);

            alien.speed = Phaser.Math.FloatBetween(0.35, 0.8);
            alien.phase = Phaser.Math.FloatBetween(0, Math.PI * 2);
            alien.setDepth(5);

            this.aliens.push(alien);
        }

        fireEnemyBullet() {
            if (!this.player || state.locked) return;

            const target = this.aliens.find((alien) => alien.active);
            if (!target) return;

            const bullet = this.add.circle(
                target.x,
                target.y + 15,
                6,
                0xff9b65
            );

            bullet.setDepth(7);

            const angle = Phaser.Math.Angle.Between(
                target.x,
                target.y,
                this.player.x,
                this.player.y
            );

            bullet.vx = Math.cos(angle) * 3.2;
            bullet.vy = Math.max(3.5, Math.sin(angle) * 3.2);

            this.enemyBullets.push(bullet);
        }

        updateEnemyBullets() {
            this.enemyBullets = this.enemyBullets.filter((bullet) => {
                if (!bullet.active) return false;

                bullet.x += bullet.vx;
                bullet.y += bullet.vy;

                if (
                    bullet.y > this.scale.height + 25 ||
                    bullet.x < -25 ||
                    bullet.x > this.scale.width + 25
                ) {
                    bullet.destroy();
                    return false;
                }

                const distance = Phaser.Math.Distance.Between(
                    bullet.x,
                    bullet.y,
                    this.player.x,
                    this.player.y
                );

                if (distance < 28) {
                    // Remove o projétil mesmo durante a invulnerabilidade.
                    // Assim ele não fica parado dentro da nave, esperando
                    // para causar dano assim que o bloqueio terminar.
                    bullet.destroy();

                    if (
                        Date.now() >= state.invulnerableUntil &&
                        !state.damageInProgress
                    ) {
                        handleShipHit();
                    }

                    return false;
                }

                return true;
            });
        }

        onLaserHitAlien(alien) {
            if (state.locked || state.finished) return;

            state.targetAlien = alien;

            // Impede que os lasers atinjam vários aliens enquanto a pergunta abre.
            this.playerLasers.forEach((laser) => laser.destroy());
            this.playerLasers = [];

            showQuestion();
        }

        destroyAlienTarget() {
            const alien = state.targetAlien;

            if (alien && alien.active) {
                this.tweens.add({
                    targets: alien,
                    scale: 1.7,
                    alpha: 0,
                    duration: 220,
                    onComplete: () => alien.destroy()
                });
            }

            state.targetAlien = null;
            this.aliens = this.aliens.filter((item) => item.active);
        }

        showAlienAttack() {
            const alien = state.targetAlien;

            if (!alien || !alien.active || !this.player) return;

            this.tweens.add({
                targets: alien,
                x: this.player.x,
                y: this.player.y - 45,
                duration: 450,
                ease: "Sine.easeIn",
                onComplete: () => {
                    if (alien.active) {
                        alien.destroy();
                    }

                    state.targetAlien = null;
                    this.aliens = this.aliens.filter((item) => item.active);
                }
            });

            this.flashPlayer();
        }

        flashPlayer() {
            if (!this.player) return;

            this.tweens.add({
                targets: this.player,
                alpha: 0.25,
                yoyo: true,
                repeat: 3,
                duration: 80,
                onComplete: () => {
                    if (this.player) {
                        this.player.alpha = 1;
                    }
                }
            });
        }
    }

    // =========================================================
    // INICIAR O JOGO
    // =========================================================

    async function begin() {
        if (state.started || state.finished) return;

        if (ui.startButton) {
            ui.startButton.disabled = true;
        }

        try {
            const data = await api("start");
            const question = getQuestion(data);

            if (!question) {
                throw new Error(
                    "A fase não possui uma pergunta inicial disponível."
                );
            }

            state.question = question;
            state.started = true;
            state.score = Number(data.pontuacao ?? data.score ?? 0);
            state.lives = Number(data.vidas ?? data.lives ?? MAX_LIVES);
            state.shield = Number(
                data.escudo ?? data.shield ?? MAX_SHIELD
            );
            state.correct = Number(data.acertos ?? data.correctCount ?? 0);
            state.questionIndex = Number(data.indice ?? data.questionIndex ?? 0);

            renderHUD();

            const gameElement = $("#space-canvas");

            if (!gameElement) {
                throw new Error(
                    "O elemento #space-canvas não foi encontrado no HTML."
                );
            }

            state.game = new Phaser.Game({
                type: Phaser.AUTO,
                parent: gameElement.id,
                backgroundColor: "#050817",
                scale: {
                    mode: Phaser.Scale.RESIZE,
                    autoCenter: Phaser.Scale.CENTER_BOTH,
                    width: "100%",
                    height: "100%"
                },
                scene: [SpaceScene]
            });

            if (ui.phaseName) {
                ui.phaseName.textContent =
                    cfg.nome || "MathSpace";
            }

            if (ui.phaseDescription) {
                ui.phaseDescription.textContent =
                    cfg.descricao || "Prepare-se para a missão espacial.";
            }

            enterFullscreen();

            if (ui.startButton) {
                ui.startButton.hidden = true;
            }
        } catch (error) {
            console.error(error);

            window.alert(
                error.message ||
                "Não foi possível iniciar a missão."
            );

            if (ui.startButton) {
                ui.startButton.disabled = false;
            }
        }
    }

    // =========================================================
    // EVENTOS DOS BOTÕES
    // =========================================================

    if (ui.startButton) {
        ui.startButton.addEventListener("click", begin);
    }

    if (ui.pauseButton) {
        ui.pauseButton.addEventListener("click", pauseGame);
    }

    if (ui.resumeButton) {
        ui.resumeButton.addEventListener("click", resumeGame);
    }

    if (ui.restartButton) {
        ui.restartButton.addEventListener("click", () => {
            window.location.reload();
        });
    }

    if (ui.backButton) {
        ui.backButton.addEventListener("click", () => {
            window.location.href = cfg.mapa || "mathspace.php";
        });
    }

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && state.started && !state.finished) {
            if (state.paused) {
                resumeGame();
            } else {
                pauseGame();
            }
        }
    });

    // =========================================================
    // ESTADO INICIAL
    // =========================================================

    renderHUD();

    if (ui.questionPanel) {
        ui.questionPanel.hidden = true;
    }

    if (ui.pauseOverlay) {
        ui.pauseOverlay.hidden = true;
    }

    if (ui.resultOverlay) {
        ui.resultOverlay.hidden = true;
    }
})();
