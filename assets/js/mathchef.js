(() => {
    'use strict';
    const config = window.MATHCHEF_CONFIG || { fases: [], serie: 0 };
    const phases = Array.isArray(config.fases) ? config.fases : [];
    const recipes = [
        { image: '../assets/img/bolo.jpg', title: 'Bolo de chocolate', note: 'Misture frações e medidas para preparar um bolo fofinho.', math: 'Frações, operações e medidas de ingredientes.' },
        { image: '../assets/img/brownie.jpg', title: 'Brownie', note: 'Acerte as proporções para deixar o brownie no ponto.', math: 'Proporções, números decimais e quantidades.' },
        { image: '../assets/img/pudim.jpg', title: 'Pudim', note: 'Prepare porções na medida certa e decore cada cupcake.', math: 'Multiplicação, divisão e divisão em porções.' },
        { image: '../assets/img/lasanha.jpg', title: 'Lasanha', note: 'Uma receita especial com desafios que exigem atenção.', math: 'Problemas matemáticos com várias etapas.' },
        { title: 'Cookies crocantes', note: 'Encontre as quantidades certas para uma fornada perfeita.', math: 'Operações e comparação de quantidades.' },
        { title: 'Torta da casa', note: 'Combine os ingredientes sem perder nenhuma medida.', math: 'Frações, medidas e raciocínio lógico.' },
        { title: 'Pudim caramelizado', note: 'Equilibre as quantidades para acertar a sobremesa.', math: 'Proporções e cálculos com medidas.' },
        { title: 'Cheesecake de morango', note: 'O desafio final do caderno: capricho e precisão.', math: 'Desafios matemáticos combinados.' }
    ];
    const modal = document.getElementById('recipe-modal');
    const card = modal.querySelector('.recipe-card');
    let selected = null;
    let scene = null;
    const safe = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const difficulty = d => ({ facil: 'Fácil', medio: 'Médio', dificil: 'Difícil' }[d] || 'Especial');
    function recipeFor(phase, index) {
        const base = recipes[index % recipes.length];
        // Preserve the actual database phase title/description. Recipe names are presentation only.
        return { ...base, title: base.title, phaseTitle: phase.nome, description: phase.descricao || base.note };
    }
    function openRecipe(index) {
        const phase = phases[index];
        if (!phase) return;
        selected = phase;
        const recipe = recipeFor(phase, index);
        const oldEmoji = document.getElementById('recipe-emoji');
        if (oldEmoji) oldEmoji.remove();
        const art = document.getElementById('recipe-art');
        art.style.backgroundImage = `linear-gradient(180deg, rgba(35,20,12,.02) 45%, rgba(35,20,12,.72)), url("${recipe.image || '../assets/img/bolo.jpg'}")`;
        art.style.backgroundSize = 'cover'; art.style.backgroundPosition = 'center';
        art.classList.add('has-recipe-photo');
        document.getElementById('recipe-number').textContent = `RECEITA ${String(phase.numero || index + 1).padStart(2, '0')}`;
        document.getElementById('recipe-status').textContent = phase.concluida ? 'CONCLUÍDA' : (phase.desbloqueada ? 'DISPONÍVEL' : 'BLOQUEADA');
        document.getElementById('recipe-title').textContent = recipe.title;
        document.getElementById('recipe-description').textContent = recipe.description;
        document.getElementById('recipe-difficulty').textContent = difficulty(phase.dificuldade);
        document.getElementById('recipe-xp').textContent = `${Number(phase.xp || 0)} XP`;
        document.getElementById('recipe-score').textContent = Number(phase.pontuacao || 0).toLocaleString('pt-BR');
        document.getElementById('recipe-math-description').textContent = recipe.math;
        // Resolve o destino a partir da pasta atual da página, evitando que o link
        // seja interpretado a partir de outra rota ou subpasta da hospedagem.
        const startUrl = new URL('./jogar_mathchef.php', window.location.href);
        startUrl.search = '';
        startUrl.searchParams.set('fase', String(phase.id));
        document.getElementById('start-recipe').href = startUrl.toString();
        document.getElementById('start-recipe').textContent = phase.concluida ? 'JOGAR NOVAMENTE' : 'COMEÇAR A COZINHAR';
        document.getElementById('locked-message').hidden = Boolean(phase.desbloqueada);
        card.classList.toggle('is-locked', !phase.desbloqueada);
        modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        document.querySelector('.modal-close').focus();
    }
    function closeModal() { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }
    modal.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', closeModal));
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal(); });

    if (typeof Phaser === 'undefined') {
        document.getElementById('game-loading').textContent = 'Não foi possível carregar o cenário. Verifique sua conexão e recarregue a página.';
        return;
    }
    class KitchenScene extends Phaser.Scene {
        constructor() { super('KitchenScene'); this.items = []; this.decor = []; this.selectedIndex = -1; this.pageIndex = 0; }
        preload() {
            this.load.image('kitchen-background', '../assets/img/cozinha.jpg');
            ['bolo', 'brownie', 'pudim', 'lasanha'].forEach(name => this.load.image('recipe-' + name, '../assets/img/' + name + '.jpg'));
        }
        create() {
            this.paintRoom(); this.paintCounter(); this.paintBook(); this.paintDecor();
            this.scale.on('resize', () => this.redraw());
            document.getElementById('game-loading').style.display = 'none';
            // Cada item da receita recebe seu próprio evento. Isso evita que o clique
            // seja interpretado como outra página quando o livro muda de tamanho.
            this.input.on('gameobjectdown', (pointer, obj) => {
                const direction = obj.getData('pageDirection');
                if (direction) {
                    const totalPages = Math.max(1, Math.ceil(phases.length / 2));
                    this.pageIndex = Phaser.Math.Clamp(this.pageIndex + direction, 0, totalPages - 1);
                    this.redraw();
                    return;
                }
                const index = obj.getData('phaseIndex');
                if (Number.isInteger(index)) openRecipe(index);
            });
            this.events.on('destroy', () => { });
        }
        redraw() { this.children.removeAll(true); this.items = []; this.decor = []; this.paintRoom(); this.paintCounter(); this.paintBook(); this.paintDecor(); }
        paintRoom() {
            const w = this.scale.width, h = this.scale.height;
            if (this.textures.exists('kitchen-background')) {
                const bg = this.add.image(w / 2, h / 2, 'kitchen-background').setDepth(-10);
                const scale = Math.max(w / bg.width, h / bg.height); bg.setDisplaySize(bg.width * scale, bg.height * scale);
                bg.setPosition(w / 2, h / 2); bg.setData('isKitchenBackground', true); this.kitchenBackground = bg;
                // A light, translucent veil keeps the game elements readable without damaging the source image.
                const veil = this.add.rectangle(w / 2, h / 2, w, h, 0x6e3348, .10).setDepth(-9); this.kitchenVeil = veil;
            } else {
                this.add.rectangle(w / 2, h / 2, w, h, 0xf0d7b5).setDepth(-10);
                this.add.text(w / 2, h * .2, 'Carregando cenário da cozinha…', { fontFamily: 'Georgia,serif', fontSize: '20px', color: '#70432d' }).setOrigin(.5).setDepth(-9);
            }
        }
        paintCounter() {
            const w = this.scale.width, h = this.scale.height, y = h * .61;
            const g = this.add.graphics(); g.fillStyle(0x704044, 1); g.fillRect(0, y + 10, w, h - y); g.fillStyle(0xb87970, 1); g.fillRect(0, y, w, h * .075); g.fillStyle(0xdca0a0, 1); g.fillRect(0, y + 5, w, h * .04); g.lineStyle(2, 0x8b5635, .55); for (let x = 0; x < w; x += 85) { g.lineBetween(x, y + 7, x + 45, y + 7); g.lineBetween(x + 30, y + 18, x + 70, y + 18); } g.fillStyle(0x593523, .22); g.fillRect(0, y + h * .075, w, 8);
        }
        paintBook() {
            const w = this.scale.width, h = this.scale.height;
            const bw = Math.min(w * .82, 1120), bh = Math.min(h * .62, 430);
            const bx = (w - bw) / 2, by = Math.max(h * .24, h * .34);
            const totalPages = Math.max(1, Math.ceil(phases.length / 2));
            this.pageIndex = Phaser.Math.Clamp(this.pageIndex, 0, totalPages - 1);

            // Capa, páginas e sombra para dar ao livro volume de objeto físico.
            const g = this.add.graphics();
            g.fillStyle(0x4d2635, .30); g.fillRoundedRect(bx - 12, by + 15, bw + 24, bh + 10, 20);
            g.fillStyle(0x8f3d55, 1); g.fillRoundedRect(bx - 10, by - 5, bw + 20, bh + 15, 18);
            g.fillStyle(0xc65e7e, 1); g.fillRoundedRect(bx - 3, by - 1, bw + 6, bh + 8, 15);
            const gap = 10, pw = (bw - gap) / 2;
            g.fillStyle(0xfff1d9, 1);
            g.fillRoundedRect(bx + 7, by + 8, pw - 8, bh - 16, { tl: 8, tr: 2, bl: 8, br: 3 });
            g.fillRoundedRect(bx + pw + gap, by + 8, pw - 15, bh - 16, { tl: 2, tr: 8, bl: 3, br: 8 });
            g.fillStyle(0xe7c8a7, .75); g.fillRect(bx + pw - 2, by + 12, 5, bh - 24);
            g.lineStyle(1, 0xd6a98e, .75);
            for (let i = 0; i < 8; i++) g.lineBetween(bx + 20, by + bh - 20 - i * 3, bx + bw - 25, by + bh - 20 - i * 3);

            this.add.text(w / 2, by + 16, '♡  NOSSO LIVRO DE RECEITAS  ♡', {
                fontFamily: 'Georgia,serif', fontSize: `${Math.max(14, Math.min(23, w * .021))}px`,
                fontStyle: 'bold', color: '#8f3d55', letterSpacing: 1.5
            }).setOrigin(.5, 0).setDepth(3);
            this.add.text(bx + pw * .5, by + 47, 'RECEITAS DOCES', { fontFamily: 'Arial', fontSize: `${Math.max(9, Math.min(12, w * .011))}px`, fontStyle: 'bold', color: '#b46a78', letterSpacing: 2 }).setOrigin(.5).setDepth(3);
            this.add.text(bx + pw + gap + pw * .5, by + 47, 'RECEITAS ESPECIAIS', { fontFamily: 'Arial', fontSize: `${Math.max(9, Math.min(12, w * .011))}px`, fontStyle: 'bold', color: '#b46a78', letterSpacing: 2 }).setOrigin(.5).setDepth(3);

            // Duas receitas por abertura, uma em cada página. As demais ficam nas próximas viradas.
            const first = this.pageIndex * 2;
            const cardW = Math.min(pw * .62, 260);
            const cardH = Math.min(bh * .64, 275);
            const positions = [
                { x: bx + pw * .50, y: by + bh * .56 },
                { x: bx + pw + gap + pw * .50, y: by + bh * .56 }
            ];
            const imageKeys = ['bolo', 'brownie', 'pudim', 'lasanha'];
            for (let slot = 0; slot < 2; slot++) {
                const i = first + slot;
                const phase = phases[i];
                if (!phase) continue;
                const pos = positions[slot];
                const recipe = recipes[i % recipes.length];
                const locked = !phase.desbloqueada, completed = !!phase.concluida;
                const cardX = pos.x, cardY = pos.y;
                const cardFill = completed ? 0xfff0d5 : locked ? 0xe4dfd9 : 0xfff8e9;
                const cardStroke = completed ? 0x86a66b : locked ? 0xb2aaa3 : 0xe99aab;
                const card = this.add.rectangle(cardX, cardY, cardW, cardH, cardFill, 1).setStrokeStyle(2, cardStroke, 1).setDepth(4).setInteractive({ useHandCursor: true });
                card.setData('phaseIndex', i);
                this.items.push(card);
                const imageKey = imageKeys[i] ? 'recipe-' + imageKeys[i] : null;
                const imageY = cardY - cardH * .19;
                const imageW = cardW * .76, imageH = cardH * .43;
                this.add.rectangle(cardX, imageY, imageW + 8, imageH + 8, locked ? 0xc5c0ba : 0xf4d4d9, 1).setStrokeStyle(1, locked ? 0xaaa39c : 0xe6a0ad, 1).setDepth(5);
                if (imageKey && this.textures.exists(imageKey)) {
                    const photo = this.add.image(cardX, imageY, imageKey).setDepth(6);
                    const scale = Math.max(imageW / photo.width, imageH / photo.height);
                    photo.setDisplaySize(photo.width * scale, photo.height * scale);
                    photo.setCrop(0, 0, photo.width, photo.height);
                    if (locked) photo.setTint(0x777777);
                    photo.setInteractive({ useHandCursor: true }).setData('phaseIndex', i);
                    this.items.push(photo);
                } else {
                    this.add.text(cardX, imageY, '♡', { fontSize: '28px', color: locked ? '#99918b' : '#d45e83' }).setOrigin(.5).setDepth(6);
                }
                const title = this.add.text(cardX, cardY + cardH * .13, recipe.title, {
                    fontFamily: 'Georgia,serif', fontSize: `${Math.max(10, Math.min(16, w * .014))}px`,
                    fontStyle: 'bold', color: locked ? '#918983' : '#71384b',
                    align: 'center', wordWrap: { width: cardW * .88 }
                }).setOrigin(.5).setDepth(7).setInteractive({ useHandCursor: true });
                title.setData('phaseIndex', i); this.items.push(title);
                const statusText = locked ? '🔒 BLOQUEADA' : completed ? '✓ CONCLUÍDA' : 'DISPONÍVEL';
                const status = this.add.text(cardX, cardY + cardH * .34, statusText, {
                    fontFamily: 'Arial', fontSize: `${Math.max(8, Math.min(11, w * .009))}px`, fontStyle: 'bold',
                    color: locked ? '#89827d' : completed ? '#66804d' : '#bd466b', align: 'center'
                }).setOrigin(.5).setDepth(7).setInteractive({ useHandCursor: true });
                status.setData('phaseIndex', i); this.items.push(status);
                if (completed) this.add.text(cardX + cardW * .36, cardY - cardH * .42, '★', { fontSize: '18px', color: '#dca13a' }).setOrigin(.5).setDepth(8);
            }

            // Lombada, número da página e setas interativas.
            g.lineStyle(3, 0xd9a3a4, .9); g.lineBetween(bx + bw / 2, by + 13, bx + bw / 2, by + bh - 14);
            this.add.text(bx + bw / 2, by + bh - 20, 'MATHCHEF ♡', { fontFamily: 'Georgia,serif', fontSize: '10px', color: '#b86a7e', letterSpacing: 1 }).setOrigin(.5).setDepth(5);
            const arrowY = by + bh * .57;
            const arrowXLeft = Math.max(24, bx - 25), arrowXRight = Math.min(w - 24, bx + bw + 25);
            const addArrow = (x, direction, enabled) => {
                const circle = this.add.circle(x, arrowY, Math.max(17, Math.min(27, w * .026)), enabled ? 0xd94f7b : 0xb7a6a5, 1).setStrokeStyle(2, 0xffe5e9, 1).setDepth(10);
                this.add.text(x, arrowY - 1, direction < 0 ? '‹' : '›', { fontFamily: 'Arial', fontSize: '34px', color: '#fff8f3', fontStyle: 'bold' }).setOrigin(.5).setDepth(11);
                if (enabled) { circle.setInteractive({ useHandCursor: true }); circle.setData('pageDirection', direction); this.items.push(circle); }
            };
            addArrow(arrowXLeft, -1, this.pageIndex > 0);
            addArrow(arrowXRight, 1, this.pageIndex < totalPages - 1);
            this.add.text(w / 2, Math.min(h - 14, by + bh + 18), `${this.pageIndex + 1} / ${totalPages}`, {
                fontFamily: 'Arial', fontSize: '13px', fontStyle: 'bold', color: '#fff5ef',
                backgroundColor: '#a94362', padding: { x: 12, y: 5 }
            }).setOrigin(.5).setDepth(10);
            this.bookBounds = { x: bx, y: by, w: bw, h: bh, totalPages };
        }
        paintDecor() {
            const w = this.scale.width, h = this.scale.height;
            // jars and ingredients along the counter edges
            const g = this.add.graphics();
            const jar = (x, y, scale, color) => { g.fillStyle(0x8b5a3c, 1); g.fillRoundedRect(x - 14 * scale, y - 25 * scale, 28 * scale, 42 * scale, 5); g.fillStyle(color, 1); g.fillRoundedRect(x - 11 * scale, y - 19 * scale, 22 * scale, 30 * scale, 4); g.fillStyle(0xb17a50, 1); g.fillRoundedRect(x - 16 * scale, y - 29 * scale, 32 * scale, 7 * scale, 2); };
            jar(w * .08, h * .69, .9, 0xf5e6c5); jar(w * .14, h * .7, .8, 0xf4d3a2); jar(w * .89, h * .69, .9, 0xe7c5a0); jar(w * .94, h * .7, .75, 0xc2d6a5);
            const bowl = (x, y, r, color) => { g.fillStyle(0x7a482f, 1); g.fillEllipse(x, y, r * 2, r * .7); g.fillStyle(color, 1); g.fillEllipse(x, y - 3, r * 1.8, r * .58); g.fillStyle(0xffe5bc, 1); g.fillEllipse(x, y - 5, r * 1.45, r * .38); };
            bowl(w * .2, h * .75, 22, 0xdca66a); bowl(w * .8, h * .75, 25, 0xd88b8a);
            // Sem emojis: os elementos decorativos são desenhados com formas do Phaser.
            this.add.text(w * .045, h * .86, 'FARINHA', { fontFamily: 'Arial', fontSize: '7px', fontStyle: 'bold', color: '#f9e6c8', letterSpacing: 1 }).setDepth(4);
            this.add.text(w * .91, h * .86, 'MORANGOS', { fontFamily: 'Arial', fontSize: '7px', fontStyle: 'bold', color: '#f9e6c8', letterSpacing: 1 }).setOrigin(.5).setDepth(4);
            this.add.text(w * .5, h * .97, 'ESCOLHA UMA RECEITA NO LIVRO', { fontFamily: 'Arial', fontSize: `${Math.max(8, Math.min(11, w * .009))}px`, fontStyle: 'bold', color: '#fff0d3', letterSpacing: 2 }).setOrigin(.5).setDepth(4);
        }
        hitPage(x, y) { if (!this.bookBounds) return -1; const b = this.bookBounds; if (x < b.x || x > b.x + b.w || y < b.y || y > b.y + b.h) return -1; const localX = x - b.x; const side = localX < b.w / 2 ? 0 : 1; const localY = (y - (b.y + b.h * .35)) / (b.h * .52); const count = phases.length; const leftCount = Math.ceil(count / 2); const n = side === 0 ? leftCount : count - leftCount; if (n <= 0) return -1; const local = Math.floor(localY * n); const idx = side === 0 ? local : leftCount + local; return idx >= 0 && idx < count ? idx : -1; }
    }
    const game = new Phaser.Game({ type: Phaser.AUTO, parent: 'chef-game', backgroundColor: '#e8c49a', transparent: false, scale: { mode: Phaser.Scale.RESIZE, width: '100%', height: '100%', autoCenter: Phaser.Scale.CENTER_BOTH }, render: { antialias: true, pixelArt: false, roundPixels: true }, scene: [KitchenScene] });
    window.addEventListener('beforeunload', () => game.destroy(true));
})();
