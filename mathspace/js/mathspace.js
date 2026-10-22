
/* =========================================================
   MATHSPACE | MAPA INTERATIVO COM PHASER 3
   Depende de window.MATHSPACE_CONFIG definido pelo PHP.
   ========================================================= */

(() => {
    "use strict";

    const config = window.MATHSPACE_CONFIG || {};
    const fases = Array.isArray(config.fases) ? config.fases : [];

    const viewport = document.getElementById("universe-viewport");
    const gameContainer = document.getElementById("universe-game");
    const loading = document.getElementById("universe-loading");

    if (!viewport || !gameContainer) {
        console.error("MathSpace: não encontrei o contêiner do mapa.");
        return;
    }

    if (typeof Phaser === "undefined") {
        showLoadingError("Não foi possível carregar o motor do jogo. Verifique sua conexão.");
        return;
    }

    if (fases.length === 0) {
        showLoadingError("Nenhuma fase foi encontrada para sua série.");
    }

    const el = (id) => document.getElementById(id);

    const ui = {
        missionName: el("mission-name"),
        missionDescription: el("mission-description"),
        missionKicker: el("mission-kicker"),
        missionStatus: el("mission-status"),
        selectedPlanetIcon: el("selected-planet-icon"),
        launchButton: el("launch-mission"),
        themeCurrent: el("theme-current"),
        starCount: el("star-count"),
        zoomLevel: el("zoom-level"),
        centerMap: el("center-map"),
        toggleEffects: el("toggle-effects"),
        toggleFullscreen: el("toggle-fullscreen"),
        progressFill: document.querySelector(".progress-fill"),
        progressText: document.querySelector("[data-progress-text]"),
        alert: el("system-alert")
    };

    const state = {
        selectedIndex: 0,
        selectedPhase: null,
        scene: null,
        game: null,
        effectsEnabled: true,
        dragging: false,
        alertTimer: null,
        currentTheme: getSavedTheme()
    };

    const planetPalettes = [
        {
            base: "#7268e9",
            light: "#d4caff",
            dark: "#26245e",
            accent: "#9e91ff",
            type: "rocky"
        },
        {
            base: "#e5a45d",
            light: "#ffe5a4",
            dark: "#7d3b42",
            accent: "#ffb776",
            type: "striped"
        },
        {
            base: "#42b9c9",
            light: "#b5ffff",
            dark: "#174a78",
            accent: "#72f0ee",
            type: "rocky"
        },
        {
            base: "#d96698",
            light: "#ffc4e0",
            dark: "#692d70",
            accent: "#ff9bc8",
            type: "ringed"
        },
        {
            base: "#79a9e9",
            light: "#d4e8ff",
            dark: "#263d82",
            accent: "#9ecaff",
            type: "striped"
        },
        {
            base: "#b68ae9",
            light: "#f0d8ff",
            dark: "#4d337d",
            accent: "#d4a9ff",
            type: "ringed"
        }
    ];

    const planetObjects = [];
    let resizeObserver = null;

    /* -----------------------------------------------------
       UTILITÁRIOS
       ----------------------------------------------------- */

    function normalizePhase(phase, index) {
        const number = Number(phase.numero ?? index + 1);

        return {
            id: Number(phase.id),
            nome: String(phase.nome || `Setor ${number}`),
            descricao: String(
                phase.descricao || "Explore este setor e resolva os desafios matemáticos."
            ),
            dificuldade: String(phase.nivel_dificuldade || "facil"),
            numero: number,
            concluida: isTrue(phase.concluida),
            desbloqueada: isTrue(phase.desbloqueada),
            pontuacao: Number(phase.melhor_pontuacao || 0),
            xp: Number(phase.xp_conquistado || 0)
        };
    }

    function isTrue(value) {
        return value === true || value === 1 || value === "1";
    }

    const normalizedPhases = fases
        .map(normalizePhase)
        .sort((a, b) => a.numero - b.numero);

    function getSavedTheme() {
        try {
            const saved = localStorage.getItem("mathspace-theme");

            if (["light", "dark", "pink"].includes(saved)) {
                return saved;
            }
        } catch (error) {
            // O jogo continua mesmo se o navegador bloquear armazenamento.
        }

        const current = document.documentElement.dataset.theme;

        return ["light", "dark", "pink"].includes(current)
            ? current
            : "dark";
    }

    function showLoadingError(message) {
        if (loading) {
            loading.classList.remove("is-hidden");
            loading.innerHTML = `
                <div class="loading-orbit"></div>
                <span>${escapeHTML(message)}</span>
            `;
        }

        console.error("MathSpace:", message);
    }

    function escapeHTML(value) {
        return String(value).replace(/[&<>"']/g, (character) => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        })[character]);
    }

    function notify(message) {
        if (!ui.alert) {
            console.info(message);
            return;
        }

        ui.alert.textContent = message;
        ui.alert.classList.add("is-visible");

        clearTimeout(state.alertTimer);

        state.alertTimer = setTimeout(() => {
            ui.alert.classList.remove("is-visible");
        }, 2800);
    }

    function hideLoading() {
        if (loading) {
            loading.classList.add("is-hidden");
        }
    }

    /* -----------------------------------------------------
       INTERFACE DAS MISSÕES
       ----------------------------------------------------- */

    function selectPhase(index) {
        if (!normalizedPhases.length) return;

        const phase = normalizedPhases[index];

        if (!phase) return;

        state.selectedIndex = index;
        state.selectedPhase = phase;

        if (ui.missionName) {
            ui.missionName.textContent = phase.nome;
        }

        if (ui.missionDescription) {
            ui.missionDescription.textContent = phase.descricao;
        }

        if (ui.missionKicker) {
            ui.missionKicker.textContent =
                `SETOR ${String(phase.numero).padStart(2, "0")} · ${phase.dificuldade.toUpperCase()}`;
        }

        if (ui.selectedPlanetIcon) {
            const palette = planetPalettes[index % planetPalettes.length];

            ui.selectedPlanetIcon.style.setProperty("--planet-color", palette.base);
            ui.selectedPlanetIcon.textContent = phase.concluida ? "✦" : "◉";
            ui.selectedPlanetIcon.setAttribute(
                "aria-label",
                `Planeta da fase ${phase.nome}`
            );
        }

        if (ui.missionStatus) {
            ui.missionStatus.classList.remove("is-locked", "is-complete");

            if (!phase.desbloqueada) {
                ui.missionStatus.textContent = "🔒 Setor bloqueado";
                ui.missionStatus.classList.add("is-locked");
            } else if (phase.concluida) {
                ui.missionStatus.textContent =
                    `★ Concluído · ${phase.pontuacao} pontos`;
                ui.missionStatus.classList.add("is-complete");
            } else {
                ui.missionStatus.textContent = "● Setor disponível";
            }
        }


        if (ui.launchButton) {
            const faseDisponivel =
                phase.desbloqueada === true && Number.isInteger(phase.id) && phase.id > 0;

            // Atualiza o endereço da fase selecionada.
            ui.launchButton.href = faseDisponivel
                ? `jogar_mathspace.php?fase=${encodeURIComponent(phase.id)}`
                : "#";

            // Atualiza o estado visual e acessível do botão.
            ui.launchButton.classList.toggle(
                "is-disabled",
                !faseDisponivel
            );

            ui.launchButton.setAttribute(
                "aria-disabled",
                String(!faseDisponivel)
            );

            ui.launchButton.setAttribute(
                "aria-label",
                faseDisponivel
                    ? `Iniciar fase ${phase.nome}`
                    : `Fase bloqueada: ${phase.nome}`
            );

            ui.launchButton.innerHTML = faseDisponivel
                ? '<span>INICIAR MISSÃO</span><span class="launch-arrow">↗</span>'
                : '<span>FASE BLOQUEADA</span><span class="launch-arrow">🔒</span>';
        }


        planetObjects.forEach((planet, planetIndex) => {
            const selected = planetIndex === index;

            if (planet.ringGlow) {
                planet.ringGlow.setAlpha(selected ? 0.85 : 0.28);
            }

            if (planet.selectionRing) {
                planet.selectionRing.setVisible(selected);
            }

            if (planet.label) {
                planet.label.setColor(
                    selected ? "#ffffff" : "#aab5dc"
                );
            }

            if (planet.marker) {
                planet.marker.setAlpha(selected ? 1 : 0.72);
            }
        });

        if (ui.starCount) {
            ui.starCount.textContent = String(
                normalizedPhases.filter((item) => item.concluida).length
            );
        }

        updateProgress();
    }

    function updateProgress() {
        const total = normalizedPhases.length;
        const completed = normalizedPhases.filter(
            (phase) => phase.concluida
        ).length;

        const percent = total > 0
            ? Math.round((completed / total) * 100)
            : 0;

        if (ui.progressFill) {
            ui.progressFill.style.width = `${percent}%`;
        }

        if (ui.progressText) {
            ui.progressText.textContent = `${completed}/${total} setores`;
        }

        const progressValue = document.querySelector(".progress-copy strong");

        if (progressValue) {
            progressValue.textContent = `${percent}%`;
        }
    }

    /* -----------------------------------------------------
       TEXTURAS PROCEDURAIS DOS PLANETAS
       ----------------------------------------------------- */

    function createPlanetTexture(scene, key, palette, seed) {
        const size = 256;

        if (scene.textures.exists(key)) {
            return;
        }

        const texture = scene.textures.createCanvas(key, size, size);
        const context = texture.getContext();

        const center = size / 2;
        const radius = 104;

        context.clearRect(0, 0, size, size);

        // Halo exterior suave.
        const halo = context.createRadialGradient(
            center, center, radius * 0.72,
            center, center, radius * 1.4
        );

        halo.addColorStop(0, `${palette.accent}38`);
        halo.addColorStop(0.65, `${palette.accent}14`);
        halo.addColorStop(1, `${palette.accent}00`);

        context.fillStyle = halo;
        context.fillRect(0, 0, size, size);

        // Corpo com iluminação superior esquerda.
        const body = context.createRadialGradient(
            center - 35, center - 42, 8,
            center + 14, center + 15, radius * 1.15
        );

        body.addColorStop(0, palette.light);
        body.addColorStop(0.27, palette.base);
        body.addColorStop(0.72, palette.dark);
        body.addColorStop(1, "#070918");

        context.beginPath();
        context.arc(center, center, radius, 0, Math.PI * 2);
        context.fillStyle = body;
        context.fill();

        // Recorte de textura no interior do planeta.
        context.save();
        context.beginPath();
        context.arc(center, center, radius - 1, 0, Math.PI * 2);
        context.clip();

        let seedValue = seed * 92821 + 17;

        function random() {
            seedValue = (seedValue * 9301 + 49297) % 233280;
            return seedValue / 233280;
        }

        if (palette.type === "striped") {
            for (let i = 0; i < 13; i++) {
                const y = 35 + i * 15 + random() * 8;
                const bandHeight = 4 + random() * 12;

                context.globalAlpha = 0.08 + random() * 0.16;
                context.fillStyle = i % 2 === 0
                    ? palette.light
                    : palette.dark;

                context.beginPath();
                context.ellipse(
                    center,
                    y + 30,
                    radius * 1.05,
                    bandHeight,
                    (random() - 0.5) * 0.12,
                    0,
                    Math.PI * 2
                );
                context.fill();
            }
        } else {
            // Superfície rochosa com crateras discretas.
            for (let i = 0; i < 38; i++) {
                const angle = random() * Math.PI * 2;
                const distance = Math.sqrt(random()) * radius * 0.92;
                const x = center + Math.cos(angle) * distance;
                const y = center + Math.sin(angle) * distance;
                const craterRadius = 2 + random() * 9;

                context.globalAlpha = 0.08 + random() * 0.16;
                context.fillStyle = random() > 0.5
                    ? palette.light
                    : "#080a22";

                context.beginPath();
                context.arc(x, y, craterRadius, 0, Math.PI * 2);
                context.fill();

                context.globalAlpha = 0.1;
                context.strokeStyle = palette.light;
                context.lineWidth = 1;
                context.stroke();
            }

            // Faixas de terreno suaves.
            for (let i = 0; i < 5; i++) {
                context.globalAlpha = 0.07;
                context.strokeStyle = palette.light;
                context.lineWidth = 5 + random() * 12;

                context.beginPath();
                context.ellipse(
                    center + (random() - 0.5) * 45,
                    center + (random() - 0.5) * 70,
                    40 + random() * 65,
                    8 + random() * 20,
                    random() * Math.PI,
                    0,
                    Math.PI * 2
                );
                context.stroke();
            }
        }

        context.globalAlpha = 1;
        context.restore();

        // Sombra na borda para dar volume.
        const shade = context.createRadialGradient(
            center - 42, center - 48, radius * 0.2,
            center, center, radius * 1.04
        );

        shade.addColorStop(0, "rgba(255,255,255,0)");
        shade.addColorStop(0.55, "rgba(5,7,22,0)");
        shade.addColorStop(0.82, "rgba(5,7,22,0.2)");
        shade.addColorStop(1, "rgba(2,4,15,0.86)");

        context.beginPath();
        context.arc(center, center, radius, 0, Math.PI * 2);
        context.fillStyle = shade;
        context.fill();

        // Reflexo de luz.
        const shine = context.createRadialGradient(
            center - 45, center - 48, 1,
            center - 36, center - 40, 56
        );

        shine.addColorStop(0, "rgba(255,255,255,0.46)");
        shine.addColorStop(0.25, "rgba(255,255,255,0.13)");
        shine.addColorStop(1, "rgba(255,255,255,0)");

        context.fillStyle = shine;
        context.beginPath();
        context.arc(center, center, radius, 0, Math.PI * 2);
        context.fill();

        // Contorno fino.
        context.strokeStyle = `${palette.light}60`;
        context.lineWidth = 1.4;
        context.beginPath();
        context.arc(center, center, radius - 0.7, 0, Math.PI * 2);
        context.stroke();

        texture.refresh();
    }

    /* -----------------------------------------------------
       CENA PHASER
       ----------------------------------------------------- */

    class UniverseScene extends Phaser.Scene {
        constructor() {
            super({ key: "UniverseScene" });

            this.mapWidth = 1500;
            this.mapHeight = 1100;
            this.dragging = false;
            this.lastPointer = null;
            this.starParticles = [];
            this.orbitGraphics = null;
            this.planetsLayer = null;
            this.effectsOn = true;
        }

        create() {
            state.scene = this;

            this.cameras.main.setBounds(
                0, 0, this.mapWidth, this.mapHeight
            );

            this.drawNebula();
            this.drawStarfield();
            this.drawOrbits();
            this.createPlanets();
            this.setupCameraControls();
            this.setupWheelZoom();

            this.cameras.main.setZoom(0.82);
            this.centerOnFirstAvailable();

            hideLoading();
            selectPhase(
                Math.max(
                    0,
                    normalizedPhases.findIndex((phase) => phase.desbloqueada)
                )
            );

            if (ui.zoomLevel) {
                ui.zoomLevel.textContent = `${Math.round(
                    this.cameras.main.zoom * 100
                )}%`;
            }

            this.scale.on("resize", () => {
                this.cameras.main.setBounds(
                    0, 0, this.mapWidth, this.mapHeight
                );
            });
        }

        drawNebula() {
            const graphics = this.add.graphics();

            const clouds = [
                { x: 230, y: 230, r: 210, color: 0x5a3ca5, alpha: 0.07 },
                { x: 960, y: 360, r: 260, color: 0x246ca6, alpha: 0.07 },
                { x: 720, y: 840, r: 230, color: 0x733e9a, alpha: 0.06 },
                { x: 1270, y: 760, r: 200, color: 0x286f95, alpha: 0.05 }
            ];

            clouds.forEach((cloud) => {
                for (let i = 8; i >= 1; i--) {
                    graphics.fillStyle(
                        cloud.color,
                        cloud.alpha / (i * 0.6)
                    );

                    graphics.fillCircle(
                        cloud.x,
                        cloud.y,
                        cloud.r * (i / 4)
                    );
                }
            });

            graphics.setDepth(-20);
        }

        drawStarfield() {
            let seed = 7519;

            const random = () => {
                seed = (seed * 16807) % 2147483647;
                return (seed - 1) / 2147483646;
            };

            const stars = this.add.graphics();
            stars.setDepth(-10);

            for (let i = 0; i < 520; i++) {
                const x = random() * this.mapWidth;
                const y = random() * this.mapHeight;
                const radius = random() > 0.96 ? 1.8 : 0.6 + random() * 0.7;
                const alpha = 0.15 + random() * 0.72;

                stars.fillStyle(
                    random() > 0.88 ? 0xaec7ff : 0xffffff,
                    alpha
                );

                stars.fillCircle(x, y, radius);

                if (radius > 1.6) {
                    const glow = this.add.circle(
                        x, y, radius * 3.2, 0xa5bfff, 0.08
                    );

                    glow.setDepth(-9);
                    this.starParticles.push(glow);
                }
            }

            // Pequenas estrelas brilhantes.
            for (let i = 0; i < 24; i++) {
                const x = random() * this.mapWidth;
                const y = random() * this.mapHeight;

                const star = this.add.star(
                    x, y, 4, 1, 3,
                    0xcedbff,
                    0.55
                );

                star.setDepth(-8);
                star.rotation = random() * Math.PI;
                this.starParticles.push(star);
            }
        }

        drawOrbits() {
            this.orbitGraphics = this.add.graphics();
            this.orbitGraphics.setDepth(-2);

            this.orbitGraphics.lineStyle(
                1,
                0x8195d9,
                0.17
            );

            const centerX = this.mapWidth / 2;
            const centerY = this.mapHeight / 2;

            [170, 285, 400, 515].forEach((radius, index) => {
                this.orbitGraphics.strokeEllipse(
                    centerX,
                    centerY,
                    radius * 2,
                    radius * (1.35 + index * 0.04)
                );
            });

            // Linhas cartográficas suaves.
            this.orbitGraphics.lineStyle(1, 0x6c80bd, 0.06);

            for (let x = 100; x < this.mapWidth; x += 100) {
                this.orbitGraphics.lineBetween(
                    x, 60, x, this.mapHeight - 60
                );
            }

            for (let y = 100; y < this.mapHeight; y += 100) {
                this.orbitGraphics.lineBetween(
                    60, y, this.mapWidth - 60, y
                );
            }
        }

        createPlanets() {
            this.planetsLayer = this.add.container(0, 0);
            this.planetsLayer.setDepth(2);

            if (!normalizedPhases.length) {
                const emptyText = this.add.text(
                    this.mapWidth / 2,
                    this.mapHeight / 2,
                    "NENHUM SETOR DISPONÍVEL",
                    {
                        fontFamily: "Arial",
                        fontSize: "18px",
                        color: "#c8d1ff",
                        fontStyle: "bold",
                        letterSpacing: 3
                    }
                ).setOrigin(0.5);

                this.planetsLayer.add(emptyText);
                return;
            }

            const positions = this.getPlanetPositions(
                normalizedPhases.length
            );

            normalizedPhases.forEach((phase, index) => {
                const palette = planetPalettes[index % planetPalettes.length];
                const position = positions[index];
                const planetSize = index === 0 ? 78 : 65;

                const planet = this.createPlanet(
                    phase,
                    index,
                    position.x,
                    position.y,
                    planetSize,
                    palette
                );

                planetObjects[index] = planet;
            });

            this.drawRoute(positions);
        }

        getPlanetPositions(count) {
            const centerX = this.mapWidth / 2;
            const centerY = this.mapHeight / 2;

            const positions = [];
            const startAngle = -Math.PI / 2;

            for (let i = 0; i < count; i++) {
                let x;
                let y;

                if (count === 1) {
                    x = centerX;
                    y = centerY;
                } else {
                    const angle = startAngle + (i / count) * Math.PI * 2;
                    const ring = Math.floor(i / 8);
                    const radius = Math.min(
                        170 + ring * 125 + (i % 3) * 12,
                        470
                    );

                    x = centerX + Math.cos(angle) * radius * 1.2;
                    y = centerY + Math.sin(angle) * radius * 0.77;
                }

                positions.push({ x, y });
            }

            // Evita que as fases fiquem coladas umas nas outras.
            positions.forEach((point, index) => {
                if (index === 0) return;

                const previous = positions[index - 1];
                const dx = point.x - previous.x;
                const dy = point.y - previous.y;
                const distance = Math.sqrt(dx * dx + dy * dy);

                if (distance < 145) {
                    const angle = Math.atan2(dy, dx);

                    point.x = previous.x + Math.cos(angle) * 145;
                    point.y = previous.y + Math.sin(angle) * 145;
                }

                point.x = Phaser.Math.Clamp(point.x, 105, this.mapWidth - 105);
                point.y = Phaser.Math.Clamp(point.y, 105, this.mapHeight - 105);
            });

            return positions;
        }

        drawRoute(positions) {
            if (positions.length < 2) return;

            const route = this.add.graphics();
            route.setDepth(-1);

            route.lineStyle(2, 0x8196ff, 0.18);

            for (let i = 0; i < positions.length - 1; i++) {
                const from = positions[i];
                const to = positions[i + 1];

                route.lineBetween(from.x, from.y, to.x, to.y);

                // Pequenos pontos sobre o trajeto.
                for (let step = 1; step <= 3; step++) {
                    const t = step / 4;

                    route.fillStyle(0xa9b6ff, 0.35);

                    route.fillCircle(
                        Phaser.Math.Linear(from.x, to.x, t),
                        Phaser.Math.Linear(from.y, to.y, t),
                        1.8
                    );
                }
            }

            route.setDepth(0);
        }

        createPlanet(phase, index, x, y, size, palette) {
            const container = this.add.container(x, y);
            container.setSize(size * 2.6, size * 2.6);
            container.setDepth(3);

            const textureKey = `mathspace-planet-${index}`;

            createPlanetTexture(this, textureKey, palette, index + 1);

            // Brilho ao redor do planeta.
            const ringGlow = this.add.circle(
                0, 0, size * 0.78, Phaser.Display.Color.HexStringToColor(
                    palette.accent
                ).color, 0.1
            );

            ringGlow.setBlendMode(Phaser.BlendModes.ADD);

            // Órbita externa fina.
            const orbit = this.add.ellipse(
                0, 0, size * 2.8, size * 1.08
            );

            orbit.setStrokeStyle(1, 0x9eafff, 0.3);
            orbit.setFillStyle(0x000000, 0);

            // Planeta.
            const sprite = this.add.image(0, 0, textureKey);
            sprite.setDisplaySize(size * 2, size * 2);

            // Anéis de Saturno para alguns planetas.
            if (palette.type === "ringed") {
                const backRing = this.add.ellipse(
                    0, 0, size * 2.75, size * 0.72
                );

                backRing.setStrokeStyle(7, 0xe5b7ff, 0.48);
                backRing.setFillStyle(0x000000, 0);
                backRing.setDepth(-1);

                container.add(backRing);
                container.sendToBack(backRing);
            }

            // Anel de seleção.
            const selectionRing = this.add.ellipse(
                0, 0, size * 3.2, size * 1.35
            );

            selectionRing.setStrokeStyle(2, 0xffffff, 0.85);
            selectionRing.setFillStyle(0x000000, 0);
            selectionRing.setVisible(false);

            // Marcador de estado.
            const markerColor = !phase.desbloqueada
                ? 0x7c86a8
                : phase.concluida
                    ? 0xffd477
                    : 0x75efc2;

            const marker = this.add.circle(
                size * 0.63,
                -size * 0.61,
                7,
                markerColor,
                1
            );

            marker.setStrokeStyle(2, 0x080c1c, 1);

            const markerText = this.add.text(
                size * 0.63,
                -size * 0.61,
                phase.desbloqueada
                    ? phase.concluida ? "✓" : String(index + 1)
                    : "×",
                {
                    fontFamily: "Arial",
                    fontSize: "9px",
                    color: "#10152a",
                    fontStyle: "bold"
                }
            ).setOrigin(0.5);

            // Nome do planeta.
            const label = this.add.text(
                0,
                size * 1.15,
                phase.nome.toUpperCase(),
                {
                    fontFamily: "Arial",
                    fontSize: "11px",
                    color: "#aab5dc",
                    fontStyle: "bold",
                    align: "center",
                    letterSpacing: 1
                }
            ).setOrigin(0.5, 0);

            const numberLabel = this.add.text(
                0,
                size * 1.15 + 18,
                `SETOR ${String(phase.numero).padStart(2, "0")}`,
                {
                    fontFamily: "Consolas, monospace",
                    fontSize: "9px",
                    color: "#7483b1",
                    align: "center",
                    letterSpacing: 1
                }
            ).setOrigin(0.5, 0);

            container.add([
                ringGlow,
                orbit,
                sprite,
                selectionRing,
                marker,
                markerText,
                label,
                numberLabel
            ]);

            container.setInteractive(
                new Phaser.Geom.Circle(0, 0, size * 1.4),
                Phaser.Geom.Circle.Contains
            );

            container.on("pointerover", () => {
                this.game.canvas.style.cursor = "pointer";

                this.tweens.add({
                    targets: sprite,
                    scaleX: (size * 2.1) / 256,
                    scaleY: (size * 2.1) / 256,
                    duration: 180,
                    ease: "Sine.easeOut"
                });

                ringGlow.setAlpha(0.4);
            });

            container.on("pointerout", () => {
                this.game.canvas.style.cursor = "grab";

                this.tweens.add({
                    targets: sprite,
                    scaleX: (size * 2) / 256,
                    scaleY: (size * 2) / 256,
                    duration: 180,
                    ease: "Sine.easeOut"
                });

                ringGlow.setAlpha(
                    state.selectedIndex === index ? 0.85 : 0.28
                );
            });

            container.on("pointerup", (pointer) => {
                if (pointer.getDistance() > 8) return;

                selectPhase(index);
                this.focusPlanet(index);
            });

            // Animação lenta para o planeta não parecer uma figurinha colada.
            this.tweens.add({
                targets: container,
                y: y + 5,
                duration: 2300 + index * 170,
                yoyo: true,
                repeat: -1,
                ease: "Sine.easeInOut",
                delay: index * 120
            });

            // Pequeno movimento do brilho.
            this.tweens.add({
                targets: ringGlow,
                alpha: { from: 0.08, to: 0.2 },
                duration: 1700 + index * 120,
                yoyo: true,
                repeat: -1,
                ease: "Sine.easeInOut"
            });

            this.planetsLayer.add(container);

            return {
                container,
                sprite,
                ringGlow,
                selectionRing,
                label,
                marker,
                x,
                y
            };
        }

        setupCameraControls() {
            const camera = this.cameras.main;

            this.input.on("pointerdown", (pointer) => {
                if (pointer.button !== 0) return;

                this.dragging = true;
                this.lastPointer = {
                    x: pointer.x,
                    y: pointer.y
                };
            });

            this.input.on("pointermove", (pointer) => {
                if (!this.dragging || !pointer.isDown || !this.lastPointer) {
                    return;
                }

                const dx = pointer.x - this.lastPointer.x;
                const dy = pointer.y - this.lastPointer.y;

                camera.scrollX -= dx / camera.zoom;
                camera.scrollY -= dy / camera.zoom;

                this.lastPointer = {
                    x: pointer.x,
                    y: pointer.y
                };
            });

            this.input.on("pointerup", () => {
                this.dragging = false;
                this.lastPointer = null;
            });

            this.input.on("pointerupoutside", () => {
                this.dragging = false;
                this.lastPointer = null;
            });
        }

        setupWheelZoom() {
            this.input.on("wheel", (pointer, objects, deltaX, deltaY) => {
                const camera = this.cameras.main;

                camera.setZoom(
                    Phaser.Math.Clamp(
                        camera.zoom - deltaY * 0.001,
                        0.48,
                        1.45
                    )
                );

                if (ui.zoomLevel) {
                    ui.zoomLevel.textContent =
                        `${Math.round(camera.zoom * 100)}%`;
                }
            });
        }

        centerOnFirstAvailable() {
            const firstAvailable = normalizedPhases.findIndex(
                (phase) => phase.desbloqueada
            );

            const index = firstAvailable >= 0 ? firstAvailable : 0;

            this.focusPlanet(index, false);
        }

        focusPlanet(index, animate = true) {
            const planet = planetObjects[index];

            if (!planet) return;

            const camera = this.cameras.main;

            if (animate) {
                this.tweens.add({
                    targets: camera,
                    scrollX: planet.x - camera.width / (2 * camera.zoom),
                    scrollY: planet.y - camera.height / (2 * camera.zoom),
                    duration: 500,
                    ease: "Sine.easeInOut"
                });
            } else {
                camera.centerOn(planet.x, planet.y);
            }

            if (ui.zoomLevel) {
                ui.zoomLevel.textContent =
                    `${Math.round(camera.zoom * 100)}%`;
            }
        }

        toggleEffects() {
            this.effectsOn = !this.effectsOn;

            this.starParticles.forEach((particle) => {
                particle.setVisible(this.effectsOn);
            });

            this.tweens.getTweens().forEach((tween) => {
                if (tween.targets.some((target) =>
                    this.starParticles.includes(target)
                )) {
                    if (this.effectsOn) {
                        tween.resume();
                    } else {
                        tween.pause();
                    }
                }
            });
        }
    }

    /* -----------------------------------------------------
       CRIAÇÃO DO JOGO
       ----------------------------------------------------- */

    function createGame() {
        const width = Math.max(320, viewport.clientWidth);
        const height = Math.max(360, viewport.clientHeight);

        const game = new Phaser.Game({
            type: Phaser.CANVAS,
            parent: gameContainer,
            width,
            height,
            backgroundColor: "#060918",
            transparent: false,
            antialias: true,
            roundPixels: false,
            render: {
                pixelArt: false,
                antialias: true,
                transparent: false,
                powerPreference: "high-performance"
            },
            scale: {
                mode: Phaser.Scale.RESIZE,
                autoCenter: Phaser.Scale.CENTER_BOTH,
                width,
                height
            },
            input: {
                activePointers: 2
            },
            scene: [UniverseScene]
        });

        state.game = game;

        if ("ResizeObserver" in window) {
            resizeObserver = new ResizeObserver(() => {
                if (!state.game) return;

                state.game.scale.resize(
                    Math.max(320, viewport.clientWidth),
                    Math.max(360, viewport.clientHeight)
                );
            });

            resizeObserver.observe(viewport);
        }

        return game;
    }

    /* -----------------------------------------------------
       BOTÕES DA CABINE
       ----------------------------------------------------- */

    function setupThemeButtons() {
        const buttons = document.querySelectorAll("[data-theme-option]");

        function applyTheme(theme) {
            if (!["light", "dark", "pink"].includes(theme)) return;

            state.currentTheme = theme;
            document.documentElement.dataset.theme = theme;

            try {
                localStorage.setItem("mathspace-theme", theme);
            } catch (error) {
                // O tema continua funcionando durante esta visita.
            }

            buttons.forEach((button) => {
                const active = button.dataset.themeOption === theme;

                button.classList.toggle("active", active);
                button.setAttribute("aria-pressed", String(active));
            });

            const themeNames = {
                light: "Claro",
                dark: "Escuro",
                pink: "Rosa"
            };

            if (ui.themeCurrent) {
                ui.themeCurrent.textContent =
                    `Sistema visual: tema ${themeNames[theme]}.`;
            }
        }

        buttons.forEach((button) => {
            button.addEventListener("click", () => {
                applyTheme(button.dataset.themeOption);
            });
        });

        applyTheme(state.currentTheme);
    }

    function setupCommandButtons() {
        if (ui.centerMap) {
            ui.centerMap.addEventListener("click", () => {
                if (!state.scene) return;

                state.scene.centerOnFirstAvailable();
                notify("Mapa centralizado.");
            });
        }

        if (ui.toggleEffects) {
            ui.toggleEffects.addEventListener("click", () => {
                if (!state.scene) return;

                state.effectsEnabled = !state.effectsEnabled;
                state.scene.toggleEffects();

                ui.toggleEffects.setAttribute(
                    "aria-pressed",
                    String(state.effectsEnabled)
                );

                notify(
                    state.effectsEnabled
                        ? "Efeitos visuais ativados."
                        : "Efeitos visuais reduzidos."
                );
            });
        }

        if (ui.toggleFullscreen) {
            ui.toggleFullscreen.addEventListener("click", async () => {
                const target = document.querySelector(".space-game-page")
                    || document.documentElement;

                try {
                    if (!document.fullscreenElement) {
                        await target.requestFullscreen();
                    } else {
                        await document.exitFullscreen();
                    }
                } catch (error) {
                    notify("Não foi possível ativar a tela cheia neste navegador.");
                }
            });
        }

        document.addEventListener("fullscreenchange", () => {
            if (ui.toggleFullscreen) {
                ui.toggleFullscreen.setAttribute(
                    "aria-pressed",
                    String(Boolean(document.fullscreenElement))
                );
            }

            if (state.game) {
                state.game.scale.refresh();
            }
        });
    }

    function setupLaunchButton() {
        if (!ui.launchButton) return;

        ui.launchButton.addEventListener("click", () => {
            const phase = state.selectedPhase;

            if (!phase) {
                notify("Selecione uma fase no mapa.");
                return;
            }

            if (!phase.desbloqueada) {
                notify("Conclua a fase anterior para desbloquear este setor.");
                return;
            }

            if (!Number.isInteger(phase.id) || phase.id <= 0) {
                notify("O identificador desta fase é inválido.");
                return;
            }

            window.location.href =
                `jogar_mathspace.php?fase=${encodeURIComponent(phase.id)}`;
        });
    }

    /* -----------------------------------------------------
       INICIALIZAÇÃO
       ----------------------------------------------------- */

    function initialize() {
        setupThemeButtons();
        setupCommandButtons();
        setupLaunchButton();
        updateProgress();

        if (!normalizedPhases.length) return;

        createGame();
    }

    initialize();

    // Ajuda a liberar recursos se a página for descarregada.
    window.addEventListener("pagehide", () => {
        if (resizeObserver) {
            resizeObserver.disconnect();
            resizeObserver = null;
        }
    });

    if (ui.launchButton) {
        ui.launchButton.addEventListener("click", function (event) {
            const fase = state.selectedPhase;

            // Impede que o link "#" faça a página pular.
            if (
                !fase ||
                fase.desbloqueada !== true ||
                !Number.isInteger(fase.id) ||
                fase.id <= 0
            ) {
                event.preventDefault();
                return;
            }

            // Garante que o endereço corresponda ao planeta selecionado.
            this.href =
                `jogar_mathspace.php?fase=${encodeURIComponent(fase.id)}`;
        });
    }

})();


