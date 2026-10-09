import * as THREE from 'three';

document.addEventListener('DOMContentLoaded', () => {
    const mapViewport = document.getElementById('mapViewport');
    const destinations = [...document.querySelectorAll('[data-destination]')];
    const missionEmpty = document.getElementById('missionEmpty');
    const missionDetails = document.getElementById('missionDetails');
    const planetCanvas = document.getElementById('planetCanvas');
    const planetStage = document.getElementById('planetStage');
    const planetFallback = document.getElementById('planetStageFallback');
    const selectedType = document.getElementById('selectedType');
    const selectedName = document.getElementById('selectedName');
    const selectedDescription = document.getElementById('selectedDescription');
    const selectedDifficulty = document.getElementById('selectedDifficulty');
    const selectedPoints = document.getElementById('selectedPoints');
    const selectedStatus = document.getElementById('selectedStatus');
    const selectedStatusLine = document.querySelector('.mission-status-line');
    const selectedStatusDot = document.getElementById('selectedStatusDot');
    const stageCaption = document.getElementById('stageCaption');
    const launchButton = document.getElementById('launchButton');
    const launchText = document.getElementById('launchText');
    const launchCaption = document.getElementById('launchCaption');
    const cursorRocket = document.getElementById('cursorRocket');
    const toast = document.getElementById('missionToast');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let activeDestination = null;
    let toastTimer = null;
    let renderer = null;
    let scene = null;
    let camera = null;
    let planetMesh = null;
    let atmosphereMesh = null;
    let ringMesh = null;
    let animationFrame = null;
    let lastFrame = 0;
    let resizeObserver = null;

    const planetPresets = {
        moon: { color: 0x9cc5d8, secondary: 0x5e7897, roughness: .98, cratered: true, atmosphere: 0x8cecff, type: 'rocky' },
        mars: { color: 0xd77870, secondary: 0x7f344f, roughness: .96, cratered: true, atmosphere: 0xff9ebc, type: 'rocky' },
        asteroids: { color: 0x9a91bb, secondary: 0x51466f, roughness: 1, cratered: true, atmosphere: 0xa995ff, type: 'rocky' },
        station: { color: 0x6ed7db, secondary: 0x3b5d9a, roughness: .68, cratered: false, atmosphere: 0x83f4e6, type: 'gas' },
        unknown: { color: 0x8c8ee8, secondary: 0x3c397e, roughness: .9, cratered: true, atmosphere: 0xc2a2ff, type: 'rocky' }
    };

    function makeSurfaceTexture(preset, seed = 1) {
        const canvas = document.createElement('canvas');
        canvas.width = 512;
        canvas.height = 256;
        const ctx = canvas.getContext('2d');
        const base = new THREE.Color(preset.color);
        const secondary = new THREE.Color(preset.secondary);
        const gradient = ctx.createLinearGradient(0, 0, 512, 256);
        gradient.addColorStop(0, `#${base.getHexString()}`);
        gradient.addColorStop(.5, `#${secondary.getHexString()}`);
        gradient.addColorStop(1, `#${base.clone().multiplyScalar(.5).getHexString()}`);
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, 512, 256);

        let s = seed * 9301 + 49297;
        const random = () => { s = (s * 233280 + 49297) % 233280; return s / 233280; };
        for (let i = 0; i < 720; i++) {
            const x = random() * 512;
            const y = random() * 256;
            const radius = preset.cratered ? 1 + random() * 10 : 2 + random() * 22;
            const shade = random() > .5 ? 1 : -1;
            const alpha = .035 + random() * .16;
            const color = shade > 0 ? '#f0f5ff' : '#071026';
            const g = ctx.createRadialGradient(x - radius * .2, y - radius * .2, 0, x, y, radius);
            g.addColorStop(0, `${color}${Math.round(alpha * 255).toString(16).padStart(2, '0')}`);
            g.addColorStop(1, `${color}00`);
            ctx.fillStyle = g;
            ctx.beginPath();
            ctx.arc(x, y, radius, 0, Math.PI * 2);
            ctx.fill();
            if (preset.cratered && i % 3 === 0) {
                ctx.strokeStyle = `rgba(230,240,255,${alpha * .7})`;
                ctx.lineWidth = .7;
                ctx.beginPath();
                ctx.arc(x, y, radius * .65, 0, Math.PI * 2);
                ctx.stroke();
            }
        }
        if (preset.type === 'gas') {
            for (let i = 0; i < 22; i++) {
                const y = random() * 256;
                ctx.fillStyle = `rgba(230,245,255,${.025 + random() * .08})`;
                ctx.fillRect(0, y, 512, 2 + random() * 12);
            }
        }
        const texture = new THREE.CanvasTexture(canvas);
        texture.colorSpace = THREE.SRGBColorSpace;
        texture.anisotropy = 4;
        return texture;
    }

    function makeBumpTexture(seed = 2) {
        const canvas = document.createElement('canvas');
        canvas.width = 256;
        canvas.height = 128;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#777777';
        ctx.fillRect(0, 0, 256, 128);
        let s = seed * 134775813;
        const random = () => { s = (s * 1664525 + 1013904223) >>> 0; return s / 4294967296; };
        for (let i = 0; i < 900; i++) {
            const x = random() * 256, y = random() * 128, r = 1 + random() * 6;
            const shade = Math.floor(70 + random() * 150);
            ctx.fillStyle = `rgb(${shade},${shade},${shade})`;
            ctx.beginPath(); ctx.arc(x, y, r, 0, Math.PI * 2); ctx.fill();
        }
        const texture = new THREE.CanvasTexture(canvas);
        texture.wrapS = THREE.RepeatWrapping;
        texture.colorSpace = THREE.NoColorSpace;
        return texture;
    }

    function disposePlanet() {
        if (!scene) return;
        if (planetMesh) {
            scene.remove(planetMesh);
            planetMesh.geometry.dispose();
            planetMesh.material.map?.dispose();
            planetMesh.material.bumpMap?.dispose();
            planetMesh.material.dispose();
            planetMesh = null;
        }
        if (atmosphereMesh) {
            scene.remove(atmosphereMesh);
            atmosphereMesh.geometry.dispose();
            atmosphereMesh.material.dispose();
            atmosphereMesh = null;
        }
        if (ringMesh) {
            scene.remove(ringMesh);
            ringMesh.geometry.dispose();
            ringMesh.material.dispose();
            ringMesh = null;
        }
    }

    function createPlanet(planetClass, hexColor, seed) {
        if (!renderer || !scene) return;
        disposePlanet();
        const preset = { ...(planetPresets[planetClass] || planetPresets.unknown) };
        const custom = new THREE.Color(hexColor || '#8cecff');
        preset.atmosphere = custom.getHex();
        const geometry = new THREE.SphereGeometry(1, 72, 56);
        const material = new THREE.MeshStandardMaterial({
            map: makeSurfaceTexture(preset, seed),
            bumpMap: makeBumpTexture(seed),
            bumpScale: preset.cratered ? .055 : .018,
            roughness: preset.roughness,
            metalness: .02
        });
        planetMesh = new THREE.Mesh(geometry, material);
        planetMesh.rotation.z = .22;
        scene.add(planetMesh);

        const atmosphereMaterial = new THREE.ShaderMaterial({
            uniforms: { glowColor: { value: new THREE.Color(preset.atmosphere) } },
            vertexShader: `
        varying vec3 vNormal;
        varying vec3 vViewPosition;
        void main() {
          vec4 mvPosition = modelViewMatrix * vec4(position, 1.0);
          vNormal = normalize(normalMatrix * normal);
          vViewPosition = -mvPosition.xyz;
          gl_Position = projectionMatrix * mvPosition;
        }
      `,
            fragmentShader: `
        uniform vec3 glowColor;
        varying vec3 vNormal;
        varying vec3 vViewPosition;
        void main() {
          float intensity = pow(0.72 - dot(normalize(vNormal), normalize(vViewPosition)), 2.2);
          gl_FragColor = vec4(glowColor, clamp(intensity * 1.35, 0.0, 0.72));
        }
      `,
            blending: THREE.AdditiveBlending,
            side: THREE.BackSide,
            transparent: true,
            depthWrite: false
        });
        atmosphereMesh = new THREE.Mesh(new THREE.SphereGeometry(1.12, 64, 48), atmosphereMaterial);
        scene.add(atmosphereMesh);

        if (planetClass === 'station') {
            ringMesh = new THREE.Mesh(
                new THREE.RingGeometry(1.25, 1.68, 96),
                new THREE.MeshBasicMaterial({ color: 0x9bdfff, side: THREE.DoubleSide, transparent: true, opacity: .62 })
            );
            ringMesh.rotation.x = Math.PI / 2.5;
            ringMesh.rotation.y = .22;
            scene.add(ringMesh);
        }
        if (planetFallback) planetFallback.style.opacity = '0';
    }

    function initRenderer() {
        if (!planetCanvas || !planetStage || !window.WebGLRenderingContext) return;
        try {
            renderer = new THREE.WebGLRenderer({ canvas: planetCanvas, alpha: true, antialias: true, powerPreference: 'low-power' });
            renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.6));
            renderer.outputColorSpace = THREE.SRGBColorSpace;
            renderer.toneMapping = THREE.ACESFilmicToneMapping;
            renderer.toneMappingExposure = 1.12;
            scene = new THREE.Scene();
            camera = new THREE.PerspectiveCamera(34, 1, .1, 100);
            camera.position.set(0, 0, 4.4);
            scene.add(new THREE.AmbientLight(0x8aa9ff, 1.25));
            const keyLight = new THREE.DirectionalLight(0xffffff, 3.2);
            keyLight.position.set(-3, 2.5, 4);
            scene.add(keyLight);
            const pinkRim = new THREE.PointLight(0xff8ce6, 5, 8);
            pinkRim.position.set(2.5, .4, -1.5);
            scene.add(pinkRim);
            const blueRim = new THREE.PointLight(0x63cfff, 4, 8);
            blueRim.position.set(-2, -1.5, -2);
            scene.add(blueRim);
            const starGeometry = new THREE.BufferGeometry();
            const starCount = 650;
            const starPositions = new Float32Array(starCount * 3);
            for (let i = 0; i < starPositions.length; i++) starPositions[i] = (Math.random() - .5) * 14;
            starGeometry.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
            const stars = new THREE.Points(starGeometry, new THREE.PointsMaterial({ color: 0xcdeaff, size: .018, transparent: true, opacity: .6 }));
            scene.add(stars);
            const resize = () => {
                if (!renderer || !planetStage) return;
                const width = Math.max(1, planetStage.clientWidth);
                const height = Math.max(1, planetStage.clientHeight);
                renderer.setSize(width, height, false);
                camera.aspect = width / height;
                camera.updateProjectionMatrix();
            };
            resizeObserver = new ResizeObserver(resize);
            resizeObserver.observe(planetStage);
            resize();
            const render = (time) => {
                animationFrame = requestAnimationFrame(render);
                if (reduceMotion && time - lastFrame < 100) return;
                lastFrame = time;
                if (planetMesh) planetMesh.rotation.y += reduceMotion ? 0 : .0025;
                if (atmosphereMesh && planetMesh) atmosphereMesh.rotation.copy(planetMesh.rotation);
                if (ringMesh) ringMesh.rotation.z += reduceMotion ? 0 : .0012;
                renderer.render(scene, camera);
            };
            animationFrame = requestAnimationFrame(render);
        } catch (error) {
            console.warn('Three.js não pôde iniciar; usando planeta CSS como alternativa.', error);
            renderer = null;
            if (planetCanvas) planetCanvas.style.display = 'none';
            if (planetFallback) planetFallback.style.opacity = '1';
        }
    }

    function showToast(message) {
        if (!toast) return;
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 2600);
    }

    function selectDestination(destination) {
        activeDestination = destination;
        destinations.forEach((item) => {
            const selected = item === destination;
            item.classList.toggle('selected', selected);
            item.setAttribute('aria-pressed', String(selected));
        });

        const isCompleted = destination.dataset.completed === '1';
        const isUnlocked = destination.dataset.unlocked === '1';
        const planetClass = destination.dataset.planetClass || 'unknown';
        const color = destination.dataset.color || '#8cecff';
        const phaseName = destination.dataset.name || destination.dataset.planet || 'Setor desconhecido';

        missionEmpty.hidden = true;
        missionDetails.hidden = false;
        missionDetails.style.setProperty('--selected-planet-color', color);
        planetStage?.style.setProperty('--selected-planet-color', color);
        selectedType.textContent = destination.dataset.type || 'SETOR DE EXPLORAÇÃO';
        selectedName.textContent = phaseName;
        selectedDescription.textContent = destination.dataset.description || 'Explore esta região e conclua os desafios matemáticos para avançar.';
        selectedDifficulty.textContent = destination.dataset.difficulty || 'Não informada';
        selectedPoints.textContent = `${Number(destination.dataset.points || 0).toLocaleString('pt-BR')} PTS`;
        stageCaption.textContent = `DESTINO ${String(destination.dataset.number || '00').padStart(2, '0')} / HOLOGRAMA ATIVO`;

        selectedStatusLine.classList.toggle('is-locked', !isUnlocked && !isCompleted);
        selectedStatusLine.classList.toggle('is-completed', isCompleted);
        selectedStatusDot.style.background = isCompleted ? '#7df5c6' : (isUnlocked ? '#72ffe0' : '#a1a8c0');
        selectedStatusDot.style.boxShadow = `0 0 8px ${isCompleted ? '#7df5c6' : (isUnlocked ? '#72ffe0' : '#a1a8c0')}`;

        launchButton.classList.toggle('is-disabled', !isUnlocked);
        if (isCompleted) {
            selectedStatus.textContent = 'MISSÃO CONCLUÍDA';
            launchText.textContent = 'Jogar novamente';
            launchCaption.textContent = 'Revisite o setor e tente superar sua pontuação.';
            launchButton.href = destination.dataset.url || '#';
            launchButton.setAttribute('aria-disabled', 'false');
        } else if (isUnlocked) {
            selectedStatus.textContent = 'MISSÃO DISPONÍVEL';
            launchText.textContent = 'Iniciar missão';
            launchCaption.textContent = 'Propulsores prontos. Aguardando confirmação.';
            launchButton.href = destination.dataset.url || '#';
            launchButton.setAttribute('aria-disabled', 'false');
        } else {
            selectedStatus.textContent = 'ACESSO RESTRITO';
            launchText.textContent = 'Destino bloqueado';
            launchCaption.textContent = 'Conclua a missão anterior para liberar esta região.';
            launchButton.href = '#';
            launchButton.setAttribute('aria-disabled', 'true');
        }

        if (renderer) createPlanet(planetClass, color, Number(destination.dataset.number || 1));
    }

    destinations.forEach((destination) => destination.addEventListener('click', () => selectDestination(destination)));

    launchButton?.addEventListener('click', (event) => {
        if (launchButton.getAttribute('aria-disabled') === 'true') {
            event.preventDefault();
            showToast('MISSÃO BLOQUEADA // Conclua o setor anterior para continuar.');
        }
    });

    if (mapViewport && cursorRocket && window.matchMedia('(pointer: fine)').matches) {
        mapViewport.addEventListener('pointerenter', () => {
            cursorRocket.classList.add('visible');
            mapViewport.classList.add('rocket-cursor');
        });
        mapViewport.addEventListener('pointerleave', () => {
            cursorRocket.classList.remove('visible');
            mapViewport.classList.remove('rocket-cursor');
        });
        mapViewport.addEventListener('pointermove', (event) => {
            cursorRocket.style.left = `${event.clientX}px`;
            cursorRocket.style.top = `${event.clientY}px`;
            if (event.movementX || event.movementY) {
                const angle = Math.atan2(event.movementY, event.movementX) * 180 / Math.PI + 45;
                cursorRocket.style.transform = `translate(-50%, -50%) rotate(${angle}deg)`;
            }
        });
    }

    document.querySelectorAll('[data-progress]').forEach((bar) => {
        const progress = Number(bar.dataset.progress) || 0;
        requestAnimationFrame(() => { bar.style.width = `${Math.min(100, Math.max(0, progress))}%`; });
    });

    initRenderer();
    if (destinations.length) {
        const firstUnlocked = destinations.find((item) => item.dataset.unlocked === '1');
        selectDestination(firstUnlocked || destinations[0]);
    }

    window.addEventListener('beforeunload', () => {
        if (animationFrame) cancelAnimationFrame(animationFrame);
        if (resizeObserver) resizeObserver.disconnect();
        disposePlanet();
        renderer?.dispose();
    });
});
