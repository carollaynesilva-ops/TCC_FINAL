(() => {
    "use strict";

    const config = window.MATHSPACE_CONFIG || {};
    const fases = Array.isArray(config.fases) ? config.fases : [];

    const viewport = document.getElementById("universe-viewport");
    const gameContainer = document.getElementById("universe-game");
    const loading = document.getElementById("universe-loading");

    const missionName = document.getElementById("mission-name");
    const missionDescription = document.getElementById("mission-description");
    const missionKicker = document.getElementById("mission-kicker");
    const missionStatus = document.getElementById("mission-status");
    const missionIcon = document.getElementById("selected-planet-icon");
    const launchButton = document.getElementById("launch-mission");

    const themeCurrent = document.getElementById("theme-current");
    const starCount = document.getElementById("star-count");
    const zoomLevel = document.getElementById("zoom-level");

    const root = document.documentElement;

    let game = null;
    let sceneRef = null;
    let selectedPhase = null;
    let effectsEnabled = true;
    let cameraZoom = 1;
    let resizeObserver = null;

    const planetObjects = [];
    const stars = [];

    const palette = {
        available: 0x7b9cff,
        completed: 0x69e7ba,
        locked: 0x59627a,
        cyan: 0x6ce6ff,
        violet: 0x9b7cff,
        white: 0xeaf1ff
    };

    const themeNames = {
        light: "TEMA CLARO",
        dark: "TEMA ESCURO",
        pink: "TEMA ROSA"
    };

    const planetColors = [
        { main: 0x5d83df, light: 0x9ebaff, dark: 0x24356e },
        { main: 0x9c6be6, light: 0xd1a8ff, dark: 0x43266d },
        { main: 0x3dabb8, light: 0x8cf0ef, dark: 0x174b69 },
        { main: 0xd38c6c, light: 0xffc49e, dark: 0x743d58 },
        { main: 0x7885d7, light: 0xc0caff, dark: 0x34356d },
        { main: 0x3f9e8b, light: 0x92e8cb, dark: 0x1d4e57 }
    ];

    const layoutPositions = [
        { x: 0.14, y: 0.57, size: 43 },
        { x: 0.37, y: 0.31, size: 35 },
        { x: 0.61, y: 0.62, size: 49 },
        { x: 0.84, y: 0.34, size: 39 },
        { x: 0.78, y: 0.76, size: 30 },
        { x: 0.35, y: 0.78, size: 32 },
        { x: 0.13, y: 0.23, size: 27 },
        { x: 0.54, y: 0.17, size: 25 }
    ];

    function random(min, max) {
        return Math.random() * (max - min) + min;
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function formatDifficulty(value) {
        const difficulty = String(value || "").toLowerCase();

        const labels = {
            facil: "INICIAL",
            fácil: "INICIAL",
            medio: "INTERMEDIÁRIO",
            média: "INTERMEDIÁRIO",
            dificil: "AVANÇADO",
            difícil: "AVANÇADO"
        };

        return labels[difficulty] || "MISSÃO";
    }

    function getPhaseStatus(phase) {
        if (phase.concluida) return "completed";
        if (phase.desbloqueada) return "available";
        return "locked";
    }

    function statusLabel(status) {
        if (status === "completed") return "CONCLUÍDA";
        if (status === "available") return "DISPONÍVEL";
        return "BLOQUEADA";
    }

    function setTheme(theme) {
        if (!Object.prototype.hasOwnProperty.call(themeNames, theme)) {
            theme = "dark";
        }

        root.dataset.theme = theme;

        document.querySelectorAll("[data-theme-option]").forEach(button => {
            const active = button.dataset.themeOption === theme;
            button.setAttribute("aria-pressed", String(active));
        });

        if (themeCurrent) {
            themeCurrent.textContent = themeNames[theme];
        }

        try {
            localStorage.setItem("mathspace-theme", theme);
        } catch (_) {
            // O tema continua funcionando mesmo se o navegador bloquear o armazenamento.
        }
    }

    function initializeTheme() {
        let savedTheme = "dark";

        try {
            savedTheme = localStorage.getItem("mathspace-theme") || "dark";
        } catch (_) {
            // Usa o tema padrão.
        }

        setTheme(savedTheme);
    }

    document.querySelectorAll("[data-theme-option]").forEach(button => {
        button.addEventListener("click", () => {
            setTheme(button.dataset.themeOption);
        });
    });

    initializeTheme();

    function updateMissionPanel(phase) {
        selectedPhase = phase;

        if (!phase) {
            missionName.textContent = "Nenhum planeta selecionado";
            missionDescription.textContent = "Selecione um planeta no holomapa.";
            missionKicker.textContent = "DESTINO SELECIONADO";
            missionStatus.textContent = "AGUARDANDO";
            missionStatus.className = "mission-status";
            missionIcon.textContent = "✦";

            launchButton.href = "#";
            launchButton.classList.add("is-disabled");
            launchButton.setAttribute("aria-disabled", "true");
            launchButton.removeAttribute("aria-label");
            return;
        }

        const status = getPhaseStatus(phase);
        const numero = Number(phase.numero || 0);

        missionKicker.textContent =
            `MISSÃO ${String(numero).padStart(2, "0")} · ${formatDifficulty(phase.nivel_dificuldade)}`;

        missionName.textContent = phase.nome || `Planeta ${numero}`;
        missionDescription.textContent =
            phase.descricao || "Explore este destino e conclua o desafio.";

        missionStatus.textContent = statusLabel(status);
        missionStatus.className = `mission-status is-${status}`;

        missionIcon.textContent = phase.concluida ? "✓" :
            phase.desbloqueada ? "✦" : "⌑";

        if (phase.desbloqueada) {
            launchButton.href = `jogar_mathspace.php?fase=${encodeURIComponent(phase.id)}`;
            launchButton.classList.remove("is-disabled");
            launchButton.setAttribute("aria-disabled", "false");
            launchButton.setAttribute("aria-label", `Iniciar missão ${phase.nome}`);
        } else {
            launchButton.href = "#";
            launchButton.classList.add("is-disabled");
            launchButton.setAttribute("aria-disabled", "true");
            launchButton.setAttribute("aria-label", "Missão bloqueada");
        }

        if (sceneRef) {
            sceneRef.highlightSelectedPlanet(phase.id);
        }
    }

    function createGame() {
        if (typeof Phaser === "undefined") {
            if (loading) {
                loading.classList.remove("is-hidden");
                loading.innerHTML = `
                    <span style="font-size:24px">⌁</span>
                    <span>O SISTEMA DE NAVEGAÇÃO NÃO FOI CARREGADO</span>
                    <span style="font-size:10px;letter-spacing:1px">
                        Verifique sua conexão e recarregue a página.
                    </span>
                `;
            }
            return;
        }

        class GalaxyScene extends Phaser.Scene {
            constructor() {
                super("GalaxyScene");

                this.starsGraphics = null;
                this.planetLayer = null;
                this.routeLayer = null;
                this.backgroundLayer = null;
                this.effectsLayer = null;
                this.starSprites = [];
                this.planetNodes = [];
                this.decorativeObjects = [];
                this.selectedNode = null;
                this.dragStart = null;
                this.worldWidth = 1200;
                this.worldHeight = 700;
                this.hasBuilt = false;
                this.effectsOn = true;
            }

            create() {
                sceneRef = this;

                this.backgroundLayer = this.add.container(0, 0);
                this.routeLayer = this.add.graphics();
                this.planetLayer = this.add.container(0, 0);
                this.effectsLayer = this.add.container(0, 0);

                this.cameras.main.setBackgroundColor("#070b1a");
                this.cameras.main.setBounds(
                    0,
                    0,
                    this.scale.width,
                    this.scale.height
                );

                this.createNebulas();
                this.createStarfield();
                this.createGrid();
                this.createDistantObjects();
                this.createPlanets();
                this.drawRoutes();

                this.input.on("pointerdown", pointer => {
                    this.dragStart = {
                        x: pointer.x,
                        y: pointer.y,
                        cameraX: this.cameras.main.scrollX,
                        cameraY: this.cameras.main.scrollY,
                        moved: false
                    };
                });

                this.input.on("pointermove", pointer => {
                    if (!this.dragStart || !pointer.isDown) return;

                    const dx = pointer.x - this.dragStart.x;
                    const dy = pointer.y - this.dragStart.y;

                    if (Math.abs(dx) > 5 || Math.abs(dy) > 5) {
                        this.dragStart.moved = true;
                    }

                    if (this.dragStart.moved) {
                        const camera = this.cameras.main;

                        camera.scrollX = this.dragStart.cameraX - dx;
                        camera.scrollY = this.dragStart.cameraY - dy;
                    }
                });

                this.input.on("pointerup", () => {
                    this.dragStart = null;
                });

                this.input.on("wheel", (pointer, objects, dx, dy) => {
                    const delta = dy > 0 ? -0.05 : 0.05;
                    this.setZoom(cameraZoom + delta);
                });

                this.scale.on("resize", size => {
                    this.handleResize(size.width, size.height);
                });

                this.hasBuilt = true;

                if (loading) {
                    loading.classList.add("is-hidden");
                }

                if (starCount) {
                    starCount.textContent = `ESTRELAS: ${this.starSprites.length}`;
                }

                this.events.once("shutdown", () => {
                    sceneRef = null;
                });
            }

            update(time, delta) {
                if (!this.effectsOn) return;

                const seconds = delta / 1000;

                this.starSprites.forEach(star => {
                    if (!star.twinkle) return;

                    star.phase += seconds * star.speed;
                    star.object.setAlpha(
                        star.baseAlpha +
                        Math.sin(star.phase) * star.twinkle
                    );
                });

                this.decorativeObjects.forEach(item => {
                    if (item.kind === "satellite") {
                        item.angle += seconds * item.speed;
                        item.object.x = item.centerX +
                            Math.cos(item.angle) * item.radiusX;
                        item.object.y = item.centerY +
                            Math.sin(item.angle) * item.radiusY;
                    }

                    if (item.kind === "pulse") {
                        item.phase += seconds * 1.5;
                        item.object.setAlpha(
                            0.12 + (Math.sin(item.phase) + 1) * 0.11
                        );
                        item.object.setScale(
                            1 + (Math.sin(item.phase) + 1) * 0.12
                        );
                    }
                });
            }

            createNebulas() {
                const width = this.scale.width;
                const height = this.scale.height;

                const nebulaGraphics = this.add.graphics();
                nebulaGraphics.setBlendMode(Phaser.BlendModes.ADD);

                const blobs = [
                    { x: width * 0.20, y: height * 0.65, color: 0x1e327c, r: 150 },
                    { x: width * 0.73, y: height * 0.28, color: 0x542b7d, r: 160 },
                    { x: width * 0.54, y: height * 0.86, color: 0x0d647b, r: 130 },
                    { x: width * 0.43, y: height * 0.45, color: 0x1c2258, r: 180 }
                ];

                blobs.forEach(blob => {
                    for (let i = 8; i >= 1; i--) {
                        nebulaGraphics.fillStyle(
                            blob.color,
                            0.012 + (9 - i) * 0.002
                        );
                        nebulaGraphics.fillCircle(
                            blob.x,
                            blob.y,
                            blob.r * i / 4
                        );
                    }
                });

                this.backgroundLayer.add(nebulaGraphics);
            }

            createStarfield() {
                const width = this.scale.width;
                const height = this.scale.height;
                const graphics = this.add.graphics();

                const total = Math.max(110, Number(config.totalEstrelas) || 180);

                for (let i = 0; i < total; i++) {
                    const x = random(0, width);
                    const y = random(0, height);
                    const radius = random(0.4, 1.6);
                    const alpha = random(0.25, 0.85);

                    graphics.fillStyle(
                        Math.random() > 0.9 ? 0x9defff : 0xffffff,
                        alpha
                    );
                    graphics.fillCircle(x, y, radius);

                    if (i % 15 === 0) {
                        const cross = this.add.graphics();
                        cross.lineStyle(1, 0x8ba8e8, 0.25);
                        cross.lineBetween(x - 4, y, x + 4, y);
                        cross.lineBetween(x, y - 4, x, y + 4);
                        this.backgroundLayer.add(cross);
                    }

                    if (i % 3 === 0) {
                        const star = this.add.circle(
                            x,
                            y,
                            radius,
                            0xffffff,
                            alpha
                        );

                        this.backgroundLayer.add(star);

                        this.starSprites.push({
                            object: star,
                            baseAlpha: alpha,
                            twinkle: random(0.08, 0.35),
                            speed: random(0.5, 2.2),
                            phase: random(0, Math.PI * 2)
                        });
                    }
                }

                this.backgroundLayer.add(graphics);
            }

            createGrid() {
                const width = this.scale.width;
                const height = this.scale.height;
                const grid = this.add.graphics();

                grid.lineStyle(1, 0x6989d4, 0.07);

                const spacing = 55;

                for (let x = 0; x < width; x += spacing) {
                    grid.lineBetween(x, 0, x, height);
                }

                for (let y = 0; y < height; y += spacing) {
                    grid.lineBetween(0, y, width, y);
                }

                this.backgroundLayer.add(grid);
            }

            createDistantObjects() {
                const width = this.scale.width;
                const height = this.scale.height;

                // Planeta distante decorativo.
                const distant = this.add.container(width * 0.91, height * 0.82);
                const halo = this.add.circle(0, 0, 56, 0x9d7cff, 0.035);
                const planet = this.add.circle(0, 0, 25, 0x2b426f, 0.75);
                const light = this.add.circle(-8, -8, 10, 0x93b6ff, 0.15);

                const ring = this.add.ellipse(0, 0, 82, 24, 0x9dbaff, 0);
                ring.setStrokeStyle(2, 0x91b2ff, 0.25);
                ring.setAngle(-20);

                distant.add([halo, ring, planet, light]);
                this.backgroundLayer.add(distant);

                // Lua pequena orbitando um planeta decorativo.
                const moon = this.add.circle(
                    width * 0.91 + 46,
                    height * 0.82,
                    4,
                    0xd7e3ff,
                    0.8
                );

                this.backgroundLayer.add(moon);

                this.decorativeObjects.push({
                    kind: "satellite",
                    object: moon,
                    centerX: width * 0.91,
                    centerY: height * 0.82,
                    radiusX: 46,
                    radiusY: 19,
                    angle: 0,
                    speed: 0.3
                });

                // Uma pequena nave de reconhecimento.
                const shipX = width * 0.09;
                const shipY = height * 0.39;
                const ship = this.add.graphics();

                ship.fillStyle(0xb6caff, 0.75);
                ship.fillTriangle(
                    shipX, shipY - 6,
                    shipX + 9, shipY + 4,
                    shipX - 9, shipY + 4
                );

                ship.lineStyle(1, 0x6ce6ff, 0.35);
                ship.lineBetween(shipX - 14, shipY + 7, shipX + 14, shipY + 7);

                this.backgroundLayer.add(ship);

                const pulse = this.add.circle(
                    shipX,
                    shipY,
                    14,
                    0x6ce6ff,
                    0.2
                );

                this.backgroundLayer.add(pulse);
                this.decorativeObjects.push({
                    kind: "pulse",
                    object: pulse,
                    phase: random(0, 4)
                });
            }

            createPlanets() {
                const width = this.scale.width;
                const height = this.scale.height;

                this.planetNodes = [];

                const count = fases.length;

                if (count === 0) {
                    this.createDecorativePlanet(
                        width * 0.5,
                        height * 0.53,
                        47,
                        planetColors[0]
                    );

                    this.createDecorativePlanet(
                        width * 0.25,
                        height * 0.28,
                        24,
                        planetColors[2]
                    );

                    this.createDecorativePlanet(
                        width * 0.77,
                        height * 0.68,
                        31,
                        planetColors[1]
                    );

                    return;
                }

                fases.forEach((phase, index) => {
                    const position = layoutPositions[index % layoutPositions.length];
                    const x = width * position.x;
                    const y = height * position.y;
                    const size = position.size;
                    const status = getPhaseStatus(phase);
                    const colors = planetColors[index % planetColors.length];

                    const node = this.createPlanetNode(
                        phase,
                        index,
                        x,
                        y,
                        size,
                        colors,
                        status
                    );

                    this.planetNodes.push(node);
                    planetObjects.push(node);
                });
            }

            createDecorativePlanet(x, y, size, colors) {
                const graphics = this.add.graphics();

                graphics.fillStyle(colors.dark, 0.3);
                graphics.fillCircle(x, y, size + 18);

                graphics.lineStyle(1, colors.light, 0.22);
                graphics.strokeCircle(x, y, size + 13);

                graphics.fillStyle(colors.main, 0.9);
                graphics.fillCircle(x, y, size);

                graphics.fillStyle(colors.light, 0.18);
                graphics.fillCircle(x - size * 0.25, y - size * 0.3, size * 0.5);

                graphics.lineStyle(2, colors.light, 0.3);
                graphics.strokeCircle(x, y, size);

                this.backgroundLayer.add(graphics);
            }

            createPlanetNode(phase, index, x, y, size, colors, status) {
                const container = this.add.container(x, y);
                const graphics = this.add.graphics();

                const active = status === "available";
                const completed = status === "completed";

                const glowColor = completed
                    ? palette.completed
                    : active
                        ? palette.available
                        : palette.locked;

                // Halo externo.
                graphics.fillStyle(glowColor, active ? 0.035 : 0.018);
                graphics.fillCircle(0, 0, size * 1.95);

                graphics.lineStyle(
                    1,
                    glowColor,
                    active ? 0.24 : 0.12
                );
                graphics.strokeCircle(0, 0, size * 1.48);

                // Órbita inclinada.
                graphics.lineStyle(
                    1,
                    colors.light,
                    active ? 0.55 : 0.22
                );
                graphics.strokeEllipse(
                    0,
                    0,
                    size * 2.15,
                    size * 0.62
                );

                // Sombra e esfera.
                graphics.fillStyle(0x02040c, 0.65);
                graphics.fillCircle(3, 5, size);

                graphics.fillStyle(colors.dark, 1);
                graphics.fillCircle(0, 0, size);

                graphics.fillStyle(colors.main, status === "locked" ? 0.45 : 0.95);
                graphics.fillCircle(-size * 0.12, -size * 0.12, size * 0.88);

                // Luz na borda superior.
                graphics.fillStyle(colors.light, status === "locked" ? 0.14 : 0.3);
                graphics.fillCircle(-size * 0.28, -size * 0.3, size * 0.52);

                // Sombra inferior para dar volume.
                graphics.fillStyle(0x030713, status === "locked" ? 0.5 : 0.32);
                graphics.fillEllipse(
                    size * 0.17,
                    size * 0.42,
                    size * 1.2,
                    size * 0.68
                );

                // Continentes e faixas estilizadas.
                graphics.fillStyle(colors.light, 0.25);
                graphics.fillEllipse(
                    -size * 0.18,
                    size * 0.1,
                    size * 0.55,
                    size * 0.23
                );

                graphics.fillStyle(colors.dark, 0.35);
                graphics.fillEllipse(
                    size * 0.28,
                    -size * 0.2,
                    size * 0.45,
                    size * 0.19
                );

                graphics.lineStyle(
                    1.5,
                    colors.light,
                    status === "locked" ? 0.22 : 0.55
                );
                graphics.strokeCircle(0, 0, size);

                // Marcador de status.
                const markerColor = completed
                    ? palette.completed
                    : active
                        ? palette.cyan
                        : 0x77819a;

                graphics.fillStyle(markerColor, 0.95);
                graphics.fillCircle(size * 0.73, -size * 0.72, 5);

                if (active) {
                    graphics.lineStyle(1, palette.cyan, 0.75);
                    graphics.strokeCircle(size * 0.73, -size * 0.72, 8);
                }

                // Número da missão.
                const numberText = this.add.text(
                    0,
                    0,
                    completed ? "✓" : status === "locked" ? "⌑" : String(phase.numero),
                    {
                        fontFamily: "Barlow Condensed, sans-serif",
                        fontSize: `${Math.max(15, size * 0.68)}px`,
                        fontStyle: "700",
                        color: "#ffffff",
                        align: "center",
                        stroke: "#10152b",
                        strokeThickness: 3
                    }
                );

                numberText.setOrigin(0.5);

                // Nome do planeta.
                const planetLabel = this.add.text(
                    0,
                    size + 16,
                    phase.nome || `Planeta ${phase.numero}`,
                    {
                        fontFamily: "Barlow Condensed, sans-serif",
                        fontSize: "13px",
                        fontStyle: "600",
                        color: status === "locked" ? "#8a94ad" : "#edf2ff",
                        align: "center",
                        stroke: "#080b1a",
                        strokeThickness: 4,
                        wordWrap: { width: 150 },
                        lineSpacing: -2
                    }
                );

                planetLabel.setOrigin(0.5, 0);
                planetLabel.setDepth(5);

                const subLabel = this.add.text(
                    0,
                    size + 33,
                    `DESTINO ${String(phase.numero).padStart(2, "0")}`,
                    {
                        fontFamily: "Inter, sans-serif",
                        fontSize: "8px",
                        color: status === "locked" ? "#67718b" :
                            completed ? "#69e7ba" : "#7f9bdf",
                        align: "center",
                        letterSpacing: 1.2
                    }
                );

                subLabel.setOrigin(0.5, 0);

                container.add([
                    graphics,
                    numberText,
                    planetLabel,
                    subLabel
                ]);

                container.setSize(size * 2.8, size * 2.9);
                container.setInteractive(
                    new Phaser.Geom.Rectangle(
                        -size * 1.4,
                        -size * 1.4,
                        size * 2.8,
                        size * 2.9
                    ),
                    Phaser.Geom.Rectangle.Contains
                );

                container.setDepth(10 + index);

                container.on("pointerover", () => {
                    if (this.input.activePointer.isDown) return;

                    this.game.canvas.style.cursor = "pointer";

                    if (this.effectsOn) {
                        this.tweens.add({
                            targets: container,
                            scale: 1.06,
                            duration: 180,
                            ease: "Sine.easeOut"
                        });
                    }
                });

                container.on("pointerout", () => {
                    this.game.canvas.style.cursor = "grab";

                    this.tweens.add({
                        targets: container,
                        scale: container === this.selectedNode ? 1.04 : 1,
                        duration: 180,
                        ease: "Sine.easeOut"
                    });
                });

                container.on("pointerup", pointer => {
                    if (this.dragStart && this.dragStart.moved) return;
                    if (pointer && pointer.button !== 0) return;

                    updateMissionPanel(phase);

                    if (this.effectsOn) {
                        this.cameras.main.shake(100, 0.0015);
                    }

                    this.centerOnPlanet(container, false);
                });

                // Pequeno satélite em órbita nos planetas selecionáveis.
                if (active || completed) {
                    const satellite = this.add.circle(
                        x + size * 1.13,
                        y,
                        2.5,
                        completed ? palette.completed : palette.cyan,
                        0.95
                    );

                    this.planetLayer.add(satellite);

                    this.decorativeObjects.push({
                        kind: "satellite",
                        object: satellite,
                        centerX: x,
                        centerY: y,
                        radiusX: size * 1.13,
                        radiusY: size * 0.35,
                        angle: random(0, Math.PI * 2),
                        speed: active ? 0.7 : 0.35
                    });
                }

                this.planetLayer.add(container);

                if (active && !selectedPhase) {
                    selectedPhase = phase;
                }

                return {
                    phase,
                    container,
                    graphics,
                    label: planetLabel,
                    size,
                    x,
                    y,
                    colors,
                    status
                };
            }

            drawRoutes() {
                this.routeLayer.clear();

                if (this.planetNodes.length < 2) return;

                this.routeLayer.lineStyle(1.5, 0x7295ec, 0.18);

                for (let i = 0; i < this.planetNodes.length - 1; i++) {
                    const a = this.planetNodes[i];
                    const b = this.planetNodes[i + 1];

                    const curve = new Phaser.Curves.QuadraticBezier(
                        new Phaser.Math.Vector2(a.x, a.y),
                        new Phaser.Math.Vector2(
                            (a.x + b.x) / 2 + random(-25, 25),
                            (a.y + b.y) / 2 + random(-25, 25)
                        ),
                        new Phaser.Math.Vector2(b.x, b.y)
                    );

                    const points = curve.getPoints(30);

                    for (let j = 0; j < points.length - 1; j++) {
                        this.routeLayer.lineBetween(
                            points[j].x,
                            points[j].y,
                            points[j + 1].x,
                            points[j + 1].y
                        );
                    }

                    const midpoint = curve.getPoint(0.5);
                    this.routeLayer.fillStyle(0x7b9cff, 0.45);
                    this.routeLayer.fillCircle(
                        midpoint.x,
                        midpoint.y,
                        2
                    );
                }
            }

            highlightSelectedPlanet(id) {
                const selected = this.planetNodes.find(
                    node => Number(node.phase.id) === Number(id)
                );

                if (!selected) return;

                if (this.selectedNode && this.selectedNode !== selected.container) {
                    this.tweens.add({
                        targets: this.selectedNode,
                        scale: 1,
                        duration: 180
                    });
                }

                this.selectedNode = selected.container;

                this.tweens.add({
                    targets: selected.container,
                    scale: 1.04,
                    duration: 220,
                    ease: "Sine.easeOut"
                });
            }

            centerOnPlanet(container, animate = true) {
                const camera = this.cameras.main;
                const targetX = container.x - camera.width / 2;
                const targetY = container.y - camera.height / 2;

                if (!animate || !this.effectsOn) {
                    camera.scrollX = Math.max(0, targetX);
                    camera.scrollY = Math.max(0, targetY);
                    return;
                }

                this.tweens.add({
                    targets: camera,
                    scrollX: Math.max(0, targetX),
                    scrollY: Math.max(0, targetY),
                    duration: 450,
                    ease: "Cubic.easeOut"
                });
            }

            centerUniverse() {
                const camera = this.cameras.main;

                camera.setZoom(1);
                camera.centerOn(
                    this.scale.width / 2,
                    this.scale.height / 2
                );

                camera.scrollX = 0;
                camera.scrollY = 0;

                cameraZoom = 1;

                if (zoomLevel) {
                    zoomLevel.textContent = "ZOOM 100%";
                }
            }

            setZoom(value) {
                cameraZoom = clamp(value, 0.75, 1.3);
                this.cameras.main.setZoom(cameraZoom);

                if (zoomLevel) {
                    zoomLevel.textContent = `ZOOM ${Math.round(cameraZoom * 100)}%`;
                }
            }

            toggleEffects(enabled) {
                this.effectsOn = enabled;

                if (!enabled) {
                    this.tweens.killAll();
                }
            }

            handleResize(width, height) {
                if (!this.hasBuilt) return;

                this.cameras.main.setBounds(0, 0, width, height);

                // Recalcula a posição dos destinos mantendo a disposição do mapa.
                this.planetNodes.forEach((node, index) => {
                    const position = layoutPositions[index % layoutPositions.length];

                    node.x = width * position.x;
                    node.y = height * position.y;

                    node.container.setPosition(node.x, node.y);
                });

                this.drawRoutes();
            }
        }

        game = new Phaser.Game({
            type: Phaser.AUTO,
            parent: "universe-game",
            backgroundColor: "#070b1a",
            transparent: true,
            antialias: true,
            render: {
                pixelArt: false,
                roundPixels: true,
                powerPreference: "high-performance"
            },
            scale: {
                mode: Phaser.Scale.RESIZE,
                autoCenter: Phaser.Scale.CENTER_BOTH,
                width: viewport.clientWidth,
                height: viewport.clientHeight
            },
            input: {
                activePointers: 2
            },
            scene: [GalaxyScene]
        });
    }

    document.getElementById("center-map")?.addEventListener("click", () => {
        if (sceneRef) {
            sceneRef.centerUniverse();
        }
    });

    document.getElementById("toggle-effects")?.addEventListener("click", event => {
        effectsEnabled = !effectsEnabled;

        const button = event.currentTarget;
        button.setAttribute("aria-pressed", String(effectsEnabled));
        button.classList.toggle("effects-off", !effectsEnabled);

        if (sceneRef) {
            sceneRef.toggleEffects(effectsEnabled);
        }

        root.classList.toggle("reduce-motion", !effectsEnabled);
    });

    document.getElementById("toggle-fullscreen")?.addEventListener("click", async () => {
        try {
            if (!document.fullscreenElement) {
                await document.documentElement.requestFullscreen();
            } else {
                await document.exitFullscreen();
            }
        } catch (error) {
            console.warn("Não foi possível alternar para tela cheia.", error);
        }
    });

    launchButton?.addEventListener("click", event => {
        if (!selectedPhase || !selectedPhase.desbloqueada) {
            event.preventDefault();
        }
    });

    window.addEventListener("beforeunload", () => {
        if (game) {
            game.destroy(true);
        }

        if (resizeObserver) {
            resizeObserver.disconnect();
        }
    });

    createGame();
})();