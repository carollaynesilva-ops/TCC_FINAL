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
        document.getElementById('recipe-emoji').remove();
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
        document.getElementById('start-recipe').href = `jogar_mathchef.php?fase=${encodeURIComponent(phase.id)}`;
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
        constructor() { super('KitchenScene'); this.items = []; this.decor = []; this.selectedIndex = -1; }
        preload() {
            this.load.image('kitchen-background', '../assets/img/cozinha.jpg');
            ['bolo', 'brownie', 'pudim', 'lasanha'].forEach(name => this.load.image('recipe-' + name, '../assets/img/' + name + '.jpg'));
        }
        create() {
            this.paintRoom(); this.paintCounter(); this.paintBook(); this.paintDecor();
            this.scale.on('resize', () => this.redraw());
            document.getElementById('game-loading').style.display = 'none';
            this.input.on('gameobjectdown', (pointer, obj) => { if (Number.isInteger(obj.getData('phaseIndex'))) openRecipe(obj.getData('phaseIndex')); });
            this.input.on('pointerdown', (pointer) => { if (pointer.y > this.scale.height * .38 && pointer.y < this.scale.height * .91) { const idx = this.hitPage(pointer.x, pointer.y); if (idx >= 0) openRecipe(idx); } });
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
                const veil = this.add.rectangle(w / 2, h / 2, w, h, 0x3d2416, .12).setDepth(-9); this.kitchenVeil = veil;
            } else {
                this.add.rectangle(w / 2, h / 2, w, h, 0xf0d7b5).setDepth(-10);
                this.add.text(w / 2, h * .2, 'Carregando cenário da cozinha…', { fontFamily: 'Georgia,serif', fontSize: '20px', color: '#70432d' }).setOrigin(.5).setDepth(-9);
            }
        }
        paintCounter() {
            const w = this.scale.width, h = this.scale.height, y = h * .61;
            const g = this.add.graphics(); g.fillStyle(0x70442d, 1); g.fillRect(0, y + 10, w, h - y); g.fillStyle(0xb87949, 1); g.fillRect(0, y, w, h * .075); g.fillStyle(0xd69b63, 1); g.fillRect(0, y + 5, w, h * .04); g.lineStyle(2, 0x8b5635, .55); for (let x = 0; x < w; x += 85) { g.lineBetween(x, y + 7, x + 45, y + 7); g.lineBetween(x + 30, y + 18, x + 70, y + 18); } g.fillStyle(0x593523, .22); g.fillRect(0, y + h * .075, w, 8);
        }
        paintBook() {
            const w = this.scale.width, h = this.scale.height;
            const bw = Math.min(w * .66, 850), bh = Math.min(h * .52, 355), bx = (w - bw) / 2, by = h * .405;
            // shadow and cover
            const g = this.add.graphics(); g.fillStyle(0x54301d, .25); g.fillRoundedRect(bx - 9, by + 10, bw + 18, bh + 6, 14); g.fillStyle(0x713f2a, 1); g.fillRoundedRect(bx - 7, by - 4, bw + 14, bh + 8, 13); g.fillStyle(0x9b593b, 1); g.fillRoundedRect(bx, by, bw, bh, 10);
            // pages
            const gap = 8, pw = (bw - gap) / 2; g.fillStyle(0xf8e9c9, 1); g.fillRoundedRect(bx + 8, by + 8, pw - 7, bh - 16, { tl: 5, tr: 1, bl: 5, br: 2 }); g.fillRoundedRect(bx + pw + gap, by + 8, pw - 16, bh - 16, { tl: 1, tr: 5, bl: 2, br: 5 }); g.fillStyle(0xe1c69e, .6); g.fillRect(bx + pw - 2, by + 11, 5, bh - 22); g.lineStyle(1, 0xd4b68a, .6); for (let i = 0; i < 7; i++) { g.lineBetween(bx + 18, by + bh - 18 - i * 3, bx + bw - 22, by + bh - 18 - i * 3); }
            this.add.text(w / 2, by + 18, 'O CADERNO DE RECEITAS', { fontFamily: 'Georgia,serif', fontSize: `${Math.max(13, Math.min(20, w * .019))}px`, fontStyle: 'bold', color: '#78452e', letterSpacing: 2 }).setOrigin(.5, 0).setDepth(3);
            this.add.text(bx + bw * .25, by + 45, 'RECEITAS DOCES', { fontFamily: 'Arial', fontSize: '9px', fontStyle: 'bold', color: '#a16b4b', letterSpacing: 2 }).setOrigin(.5).setDepth(3);
            this.add.text(bx + bw * .75, by + 45, 'ESCOLHA UMA PÁGINA', { fontFamily: 'Arial', fontSize: '9px', fontStyle: 'bold', color: '#a16b4b', letterSpacing: 1.5 }).setOrigin(.5).setDepth(3);
            const count = phases.length; const perPage = Math.ceil(count / 2); const leftX = bx + pw * .5 + 4, rightX = bx + pw + gap + pw * .5 - 3; const top = by + bh * .52;
            phases.forEach((phase, i) => {
                const side = i < perPage ? 0 : 1, local = i % perPage; const total = side === 0 ? perPage : count - perPage; const x = side === 0 ? leftX : rightX; const y = by + bh * .35 + (local + .5) * (bh * .52 / Math.max(total, 1));
                const recipe = recipes[i % recipes.length]; const locked = !phase.desbloqueada; const completed = !!phase.concluida;
                const pageW = pw * .78, pageH = Math.min(70, (bh * .48) / Math.max(total, 1) - 4); const pageX = x - pageW / 2, pageY = y - pageH / 2;
                const page = this.add.rectangle(x, y, pageW, pageH, completed ? 0xe2edcf : (locked ? 0xe7d8bd : 0xfff4dd), 1).setStrokeStyle(1, completed ? 0x7b9d69 : (locked ? 0xb9a48b : 0xc99967), 1).setInteractive({ useHandCursor: !locked }); page.setData('phaseIndex', i); page.setDepth(4); this.items.push(page);
                const imageKeys = ['bolo', 'brownie', 'pudim', 'lasanha'];
                const imageKey = imageKeys[i] ? 'recipe-' + imageKeys[i] : null;
                const thumbW = Math.min(58, pageW * .29);
                const thumbH = Math.max(26, Math.min(48, pageH - 8));
                const thumbX = pageX + 7 + thumbW / 2;
                const thumbY = y;
                const thumbFrame = this.add.rectangle(thumbX, thumbY, thumbW + 4, thumbH + 4, 0xd9c09b, 1).setStrokeStyle(1, 0xb78d61, .9).setDepth(5);
                thumbFrame.setData('phaseIndex', i);
                if (imageKey && this.textures.exists(imageKey)) {
                    const photo = this.add.image(thumbX, thumbY, imageKey).setDisplaySize(thumbW, thumbH).setDepth(6);
                    const scale = Math.max(thumbW / photo.width, thumbH / photo.height);
                    photo.setDisplaySize(photo.width * scale, photo.height * scale);
                    photo.setCrop(0, 0, photo.width, photo.height);
                    photo.setData('phaseIndex', i);
                    if (!locked) photo.setInteractive({ useHandCursor: true });
                    this.items.push(photo);
                } else {
                    this.add.rectangle(thumbX, thumbY, thumbW, thumbH, 0xf2e3c8, 1).setDepth(6);
                    this.add.text(thumbX, thumbY, 'SEM FOTO', { fontFamily: 'Arial', fontSize: '7px', color: '#8a6a4b' }).setOrigin(.5).setDepth(7);
                }
                if (!locked) thumbFrame.setInteractive({ useHandCursor: true });
                this.items.push(thumbFrame);
                if (locked) {
                    this.add.text(thumbX, thumbY + thumbH / 2 - 5, 'BLOQUEADA', { fontFamily: 'Arial', fontSize: '6px', fontStyle: 'bold', color: '#fff', backgroundColor: '#70503c', padding: { x: 2, y: 2 } }).setOrigin(.5).setDepth(8);
                } else if (completed) {
                    this.add.text(thumbX + thumbW / 2 - 2, thumbY - thumbH / 2 + 2, 'OK', { fontFamily: 'Arial', fontSize: '7px', fontStyle: 'bold', color: '#fff', backgroundColor: '#66845a', padding: { x: 2, y: 1 } }).setOrigin(1, 0).setDepth(8);
                }
                const textX = pageX + thumbW + 14;
                const title = phase.numero + '. ' + recipe.title;
                const label = this.add.text(textX, y - 8, title, { fontFamily: 'Georgia,serif', fontSize: `${Math.max(9, Math.min(13, w * .012))}px`, fontStyle: 'bold', color: locked ? '#a18c75' : '#70432d', wordWrap: { width: pageW * .65 }, lineSpacing: 0 }).setOrigin(0, .5).setDepth(5); label.setData('phaseIndex', i); if (!locked) label.setInteractive({ useHandCursor: true }); this.items.push(label);
                const status = locked ? 'BLOQUEADA' : completed ? 'CONCLUÍDA' : difficulty(phase.dificuldade).toUpperCase();
                const sub = this.add.text(textX, y + Math.min(15, pageH * .24), status, { fontFamily: 'Arial', fontSize: '7px', fontStyle: 'bold', color: locked ? '#a18c75' : completed ? '#62865a' : '#a15e3e', letterSpacing: 1 }).setOrigin(0, .5).setDepth(5); sub.setData('phaseIndex', i); if (!locked) sub.setInteractive({ useHandCursor: true }); this.items.push(sub);
            });
            // spine and decorative flourishes
            g.lineStyle(2, 0xb57d54, .9); g.lineBetween(bx + bw / 2, by + 15, bx + bw / 2, by + bh - 15); this.add.text(bx + bw / 2, by + bh - 27, 'MATHCHEF', { fontFamily: 'Georgia,serif', fontSize: '9px', color: '#9a6748', letterSpacing: 2 }).setOrigin(.5).setDepth(5);
            this.bookBounds = { x: bx, y: by, w: bw, h: bh, perPage };
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
