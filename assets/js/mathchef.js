(() => {
    'use strict';
    const config = window.MATHCHEF_CONFIG || { fases: [], serie: 0 };
    const phases = Array.isArray(config.fases) ? config.fases : [];
    const recipes = [
        { emoji: '🍫🎂', title: 'Bolo de chocolate', note: 'Misture frações e medidas para preparar um bolo fofinho.', math: 'Frações, operações e medidas de ingredientes.' },
        { emoji: '🍫', title: 'Brownie de chocolate', note: 'Acerte as proporções para deixar o brownie no ponto.', math: 'Proporções, números decimais e quantidades.' },
        { emoji: '🧁', title: 'Cupcakes de morango', note: 'Prepare porções na medida certa e decore cada cupcake.', math: 'Multiplicação, divisão e divisão em porções.' },
        { emoji: '🍰', title: 'Bolo de festa', note: 'Uma receita especial com desafios que exigem atenção.', math: 'Problemas matemáticos com várias etapas.' },
        { emoji: '🍪', title: 'Cookies crocantes', note: 'Encontre as quantidades certas para uma fornada perfeita.', math: 'Operações e comparação de quantidades.' },
        { emoji: '🥧', title: 'Torta da casa', note: 'Combine os ingredientes sem perder nenhuma medida.', math: 'Frações, medidas e raciocínio lógico.' },
        { emoji: '🍮', title: 'Pudim caramelizado', note: 'Equilibre as quantidades para acertar a sobremesa.', math: 'Proporções e cálculos com medidas.' },
        { emoji: '🍓🍰', title: 'Cheesecake de morango', note: 'O desafio final do caderno: capricho e precisão.', math: 'Desafios matemáticos combinados.' }
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
        document.getElementById('recipe-emoji').textContent = recipe.emoji;
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
            const g = this.add.graphics();
            g.fillGradientStyle(0xf8dfb9, 0xf8dfb9, 0xe8bd8b, 0xe8bd8b, 1); g.fillRect(0, 0, w, h);
            // Wall panels and wallpaper pattern
            g.fillStyle(0xf4d9b1, 1); g.fillRect(0, 0, w, h * .57);
            g.lineStyle(2, 0xd5ae82, .55); for (let x = 0; x < w; x += Math.max(70, w * .09)) { g.lineBetween(x, 0, x, h * .56); }
            for (let y = 45; y < h * .55; y += 55) { g.lineStyle(1, 0xd5ae82, .23); g.lineBetween(0, y, w, y); }
            // Backsplash tiles
            g.fillStyle(0xffefcf, 1); g.fillRect(0, h * .39, w, h * .18);
            g.lineStyle(2, 0xd9b78c, .65); for (let x = 0; x < w; x += 54)g.lineBetween(x, h * .39, x, h * .57); for (let y = h * .39; y < h * .57; y += 34)g.lineBetween(0, y, w, y);
            // Cabinets
            const cabY = h * .07, cabH = h * .29, cabW = Math.max(95, w * .15);
            for (let x = -8; x < w; x += cabW + 8) { g.fillStyle(0x9d6746, 1); g.fillRoundedRect(x, cabY, cabW, cabH, 5); g.fillStyle(0xc38c5f, 1); g.fillRoundedRect(x + 5, cabY + 5, cabW - 10, cabH - 10, 4); g.lineStyle(3, 0x875234, .8); g.strokeRoundedRect(x + 8, cabY + 8, cabW - 16, cabH - 16, 3); g.fillStyle(0x6d432e, 1); g.fillRoundedRect(x + cabW * .48, cabY + cabH * .48, 7, 15, 3); }
            // hanging rail and utensils
            g.fillStyle(0x76533b, 1); g.fillRoundedRect(w * .19, h * .36, w * .62, 5, 2);
            const utensilXs = [.25, .34, .43, .58, .67, .76]; utensilXs.forEach((v, i) => { const x = w * v, y = h * .365; g.lineStyle(3, 0x8a6345, 1); g.lineBetween(x, y, x, y + 22 + (i % 2) * 7); g.lineStyle(4, 0xb6a18a, 1); if (i % 3 === 0) g.strokeEllipse(x, y + 28, 12, 18); else if (i % 3 === 1) g.strokeRoundedRect(x - 5, y + 20, 10, 19, 3); else g.strokeEllipse(x, y + 28, 7, 19); });
            // window and sunlight
            const wx = w * .43, wy = h * .07, ww = w * .18, wh = h * .25; g.fillStyle(0x8b5c3b, 1); g.fillRoundedRect(wx - 7, wy - 7, ww + 14, wh + 14, 5); g.fillStyle(0xa9d2d5, 1); g.fillRect(wx, wy, ww, wh); g.fillStyle(0xd7efdf, .8); g.fillTriangle(wx, wy, wx + ww, wy, wx + ww, wy + wh); g.lineStyle(5, 0xf5dfb9, 1); g.lineBetween(wx + ww / 2, wy, wx + ww / 2, wy + wh); g.lineBetween(wx, wy + wh / 2, wx + ww, wy + wh / 2);
            // oven on left
            const ox = w * .025, oy = h * .38, ow = w * .14, oh = h * .25; g.fillStyle(0x7c4d35, 1); g.fillRoundedRect(ox, oy, ow, oh, 7); g.fillStyle(0xe7c49b, 1); g.fillRoundedRect(ox + 7, oy + 7, ow - 14, oh - 14, 4); g.fillStyle(0x4c3226, 1); g.fillRoundedRect(ox + 13, oy + 35, ow - 26, oh - 53, 4); g.lineStyle(3, 0x9b7558, 1); g.strokeRoundedRect(ox + 18, oy + 41, ow - 36, oh - 66, 3); g.fillStyle(0x6b3c27, 1); g.fillRoundedRect(ox + ow * .28, oy + oh - 11, ow * .44, 4, 2);
            // warm ambient lights
            g.fillStyle(0xfff0b9, .38); g.fillCircle(w * .16, h * .16, Math.min(w, h) * .1); g.fillCircle(w * .83, h * .18, Math.min(w, h) * .11);
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
                const emoji = locked ? '🔒' : completed ? '✓' : recipe.emoji;
                const art = this.add.text(pageX + 8, y, emoji, { fontFamily: 'Arial', fontSize: locked ? '17px' : (completed ? '19px' : '24px'), color: '#68442f' }).setOrigin(0, y ? 0.5 : .5).setDepth(5); art.setData('phaseIndex', i); if (!locked) art.setInteractive({ useHandCursor: true }); this.items.push(art);
                const textX = pageX + pageW * .31;
                const title = phase.numero + '. ' + recipe.title;
                const label = this.add.text(textX, y - 8, title, { fontFamily: 'Georgia,serif', fontSize: `${Math.max(9, Math.min(13, w * .012))}px`, fontStyle: 'bold', color: locked ? '#a18c75' : '#70432d', wordWrap: { width: pageW * .65 }, lineSpacing: 0 }).setOrigin(0, .5).setDepth(5); label.setData('phaseIndex', i); if (!locked) label.setInteractive({ useHandCursor: true }); this.items.push(label);
                const status = locked ? 'BLOQUEADA' : completed ? 'CONCLUÍDA' : difficulty(phase.dificuldade).toUpperCase();
                const sub = this.add.text(textX, y + Math.min(15, pageH * .24), status, { fontFamily: 'Arial', fontSize: '7px', fontStyle: 'bold', color: locked ? '#a18c75' : completed ? '#62865a' : '#a15e3e', letterSpacing: 1 }).setOrigin(0, .5).setDepth(5); sub.setData('phaseIndex', i); if (!locked) sub.setInteractive({ useHandCursor: true }); this.items.push(sub);
            });
            // spine and decorative flourishes
            g.lineStyle(2, 0xb57d54, .9); g.lineBetween(bx + bw / 2, by + 15, bx + bw / 2, by + bh - 15); this.add.text(bx + bw / 2, by + bh - 27, '✦  MATHCHEF  ✦', { fontFamily: 'Georgia,serif', fontSize: '9px', color: '#9a6748', letterSpacing: 2 }).setOrigin(.5).setDepth(5);
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
            const flour = this.add.text(w * .2, h * .73, '🥚', { fontSize: '25px' }).setOrigin(.5).setDepth(4); const berry = this.add.text(w * .8, h * .73, '🍓', { fontSize: '25px' }).setOrigin(.5).setDepth(4);
            this.add.text(w * .045, h * .86, 'FARINHA', { fontFamily: 'Arial', fontSize: '7px', fontStyle: 'bold', color: '#f9e6c8', letterSpacing: 1 }).setDepth(4);
            this.add.text(w * .91, h * .86, 'MORANGOS', { fontFamily: 'Arial', fontSize: '7px', fontStyle: 'bold', color: '#f9e6c8', letterSpacing: 1 }).setOrigin(.5).setDepth(4);
            this.add.text(w * .5, h * .97, '✦  ESCOLHA UMA RECEITA NO LIVRO  ✦', { fontFamily: 'Arial', fontSize: `${Math.max(8, Math.min(11, w * .009))}px`, fontStyle: 'bold', color: '#fff0d3', letterSpacing: 2 }).setOrigin(.5).setDepth(4);
        }
        hitPage(x, y) { if (!this.bookBounds) return -1; const b = this.bookBounds; if (x < b.x || x > b.x + b.w || y < b.y || y > b.y + b.h) return -1; const localX = x - b.x; const side = localX < b.w / 2 ? 0 : 1; const localY = (y - (b.y + b.h * .35)) / (b.h * .52); const count = phases.length; const leftCount = Math.ceil(count / 2); const n = side === 0 ? leftCount : count - leftCount; if (n <= 0) return -1; const local = Math.floor(localY * n); const idx = side === 0 ? local : leftCount + local; return idx >= 0 && idx < count ? idx : -1; }
    }
    const game = new Phaser.Game({ type: Phaser.AUTO, parent: 'chef-game', backgroundColor: '#e8c49a', transparent: false, scale: { mode: Phaser.Scale.RESIZE, width: '100%', height: '100%', autoCenter: Phaser.Scale.CENTER_BOTH }, render: { antialias: true, pixelArt: false, roundPixels: true }, scene: [KitchenScene] });
    window.addEventListener('beforeunload', () => game.destroy(true));
})();
