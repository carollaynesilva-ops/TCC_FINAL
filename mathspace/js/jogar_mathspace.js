(() => {
    'use strict';
    const cfg = window.MATHSPACE_GAME;
    if (!cfg) return;
    const $ = id => document.getElementById(id);
    const ui = { shell: $('game-shell'), canvas: $('space-canvas'), integrity: $('integrity-fill'), integrityLabel: $('integrity-label'), intro: $('mission-intro'), start: $('start-button'), question: $('question-panel'), step: $('question-step'), type: $('question-type'), progress: $('question-progress-fill'), text: $('question-text'), answers: $('answer-list'), feedback: $('answer-feedback'), next: $('continue-button'), score: $('score-value'), lives: $('lives'), pause: $('pause-button'), pauseOverlay: $('pause-overlay'), resume: $('resume-button'), exit: $('exit-button'), pauseExit: $('pause-exit-button'), result: $('result-overlay'), resultSymbol: $('result-symbol'), resultEyebrow: $('result-eyebrow'), resultTitle: $('result-title'), resultMessage: $('result-message'), resultScore: $('result-score'), resultCorrect: $('result-correct'), resultXp: $('result-xp'), retry: $('retry-button'), alert: $('mission-alert'), tip: $('flight-tip') };
    const state = { phase: null, question: null, index: 0, total: 6, score: 0, lives: 3, integrity: 100, correct: 0, locked: false, paused: false, started: false, finished: false, game: null, scene: null, pendingFinish: false };
    const api = async (action, extra = {}) => { const response = await fetch(cfg.api, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: JSON.stringify({ action, csrf: cfg.csrf, fase: cfg.faseId, ...extra }) }); const data = await response.json().catch(() => ({ ok: false, message: 'O servidor retornou uma resposta inválida.' })); if (!response.ok || !data.ok) throw new Error(data.message || 'Não foi possível concluir esta ação.'); return data; };
    function toast(message) { ui.alert.textContent = message; ui.alert.classList.add('visible'); clearTimeout(toast.timer); toast.timer = setTimeout(() => ui.alert.classList.remove('visible'), 2600) }
    function renderLives() { [...ui.lives.children].forEach((heart, i) => heart.classList.toggle('is-lost', i >= state.lives)); ui.lives.setAttribute('aria-label', `${state.lives} vidas restantes`) }
    function renderScore() { ui.score.textContent = String(state.score).padStart(4, '0'); renderLives(); if (ui.integrity) ui.integrity.style.width = `${state.integrity}%`; if (ui.integrityLabel) ui.integrityLabel.textContent = `${state.integrity}%`; }
    function enterFullscreen() { if (!document.fullscreenElement && ui.shell.requestFullscreen) ui.shell.requestFullscreen().catch(() => { }); }
    function startScene() {
        if (typeof Phaser === 'undefined') { toast('O motor do jogo não carregou. Verifique sua conexão e recarregue a página.'); return; } if (state.game) return;
        class SpaceScene extends Phaser.Scene {
            constructor() { super('space') }
            create() {
                const w = this.scale.width, h = this.scale.height;
                this.stars = []; this.bullets = []; this.aliens = []; this.enemyBullets = [];
                this.lastShot = 0; this.spawnAt = 0; this.enemyShotAt = 0; this.pointerTarget = w / 2;
                for (let i = 0; i < 105; i++) {
                    const star = this.add.circle(Math.random() * w, Math.random() * h, Math.random() * 1.6 + .35, 0xffffff, Math.random() * .7 + .15);
                    star.speed = Math.random() * 32 + 12; this.stars.push(star);
                }
                this.ship = this.add.container(w / 2, h - 74);
                const glow = this.add.circle(0, 8, 24, 0x5c76ff, .13);
                const flame = this.add.triangle(0, 28, 0, 0, -6, 18, 6, 18, 0xff9bff, .9);
                const wingL = this.add.triangle(-12, 10, 0, -8, -12, 11, 0, 8, 0x7774ff, 1);
                const wingR = this.add.triangle(12, 10, 0, -8, 12, 11, 0, 8, 0x7774ff, 1);
                const hull = this.add.triangle(0, 0, 0, -26, -18, 17, 0, 10, 0x8cefff, 1);
                hull.setStrokeStyle(2, 0xffffff, .95);
                const cockpit = this.add.ellipse(0, -4, 7, 13, 0xffffff, .9);
                this.ship.add([glow, flame, wingL, wingR, hull, cockpit]);
                this.input.on('pointermove', p => { this.pointerTarget = p.x });
                this.input.on('pointerdown', p => { this.pointerTarget = p.x });
                this.scale.on('resize', size => { this.pointerTarget = Phaser.Math.Clamp(this.ship.x, 28, size.width - 28); this.ship.y = size.height - 74; });
            }
            update(time, delta) {
                if (state.paused || state.finished || !state.started) return;
                const w = this.scale.width, h = this.scale.height;
                this.stars.forEach(s => { s.y += s.speed * delta / 1000; if (s.y > h) { s.y = -2; s.x = Math.random() * w; } });
                const target = Phaser.Math.Clamp(this.pointerTarget, 25, w - 25);
                this.ship.x = Phaser.Math.Linear(this.ship.x, target, Math.min(1, delta / 70));
                this.ship.y = h - 74;
                // Disparo automático do jogador.
                if (time - this.lastShot > 235) { this.lastShot = time; this.fire(this.ship.x - 8, this.ship.y - 20); this.fire(this.ship.x + 8, this.ship.y - 20); }
                // Aliens aparecem em intervalos regulares.
                if (time - this.spawnAt > 1050) { this.spawnAt = time; this.spawnAlien(); }
                // Os rivais atiram em intervalos independentes.
                if (time - this.enemyShotAt > 1250 && this.aliens.length) {
                    this.enemyShotAt = time;
                    const rival = this.aliens[Phaser.Math.Between(0, this.aliens.length - 1)];
                    this.fireEnemy(rival.x, rival.y + 12);
                }
                this.bullets = this.bullets.filter(b => {
                    b.y -= delta * .78;
                    if (b.y < -24) { b.destroy(); return false; }
                    return true;
                });
                this.aliens = this.aliens.filter(a => {
                    a.y += a.speed * delta / 1000;
                    a.x += Math.sin(time / 380 + a.wobble) * delta * .035;
                    if (a.y > h + 35) { a.destroy(); return false; }
                    return true;
                });
                this.enemyBullets = this.enemyBullets.filter(b => {
                    b.y += b.speed * delta / 1000;
                    if (b.y > h + 20) { b.destroy(); return false; }
                    // Colisão real entre projétil rival e nave.
                    if (Math.abs(b.x - this.ship.x) < 22 && Math.abs(b.y - this.ship.y) < 28) {
                        b.destroy(); this.damageShip(); return false;
                    }
                    return true;
                });
                // Colisão entre lasers do jogador e aliens.
                for (let i = this.aliens.length - 1; i >= 0; i--) {
                    const alien = this.aliens[i]; let destroyed = false;
                    for (let j = this.bullets.length - 1; j >= 0; j--) {
                        const laser = this.bullets[j];
                        if (Phaser.Math.Distance.Between(alien.x, alien.y, laser.x, laser.y) < alien.radius + 7) {
                            laser.destroy(); this.bullets.splice(j, 1); alien.hp--;
                            alien.setAlpha(.45); this.time.delayedCall(70, () => { if (alien.active) alien.setAlpha(1); });
                            if (alien.hp <= 0) {
                                // Na etapa 3, este ponto abrirá a questão ligada ao alien atingido.
                                alien.destroy(); this.aliens.splice(i, 1); destroyed = true;
                                this.events.emit('alien-defeated', { x: alien.x, y: alien.y });
                                break;
                            }
                        }
                    }
                    if (destroyed) continue;
                }
            }
            fire(x, y) {
                const beam = this.add.rectangle(x, y, 3, 18, 0x75f7ff, 1); beam.setStrokeStyle(1, 0xffffff, .8);
                beam.glow = this.add.circle(x, y, 5, 0x54dfff, .16);
                const originalDestroy = beam.destroy.bind(beam);
                beam.destroy = () => { if (beam.glow) beam.glow.destroy(); originalDestroy(); };
                this.bullets.push(beam);
            }
            fireEnemy(x, y) {
                const shot = this.add.ellipse(x, y, 10, 16, 0xff527d, 1).setStrokeStyle(2, 0xffb4d0, .9);
                shot.speed = Phaser.Math.Between(190, 245); shot.setDepth(2); this.enemyBullets.push(shot);
            }
            spawnAlien() {
                const x = Phaser.Math.Between(30, this.scale.width - 30);
                const radius = Phaser.Math.Between(13, 19);
                const alien = this.add.container(x, -30);
                const aura = this.add.circle(0, 0, radius + 7, 0xff4fd8, .13);
                const body = this.add.ellipse(0, 0, radius * 1.8, radius * 1.45, 0x8c5bff, 1).setStrokeStyle(2, 0xe0caff, 1);
                const eyeL = this.add.ellipse(-5, -2, 4, 7, 0x8cffff, 1);
                const eyeR = this.add.ellipse(5, -2, 4, 7, 0x8cffff, 1);
                const feelerL = this.add.line(-6, -radius / 2, 0, 0, -5, -8, 0xc99bff, 1.5);
                const feelerR = this.add.line(6, -radius / 2, 0, 0, 5, -8, 0xc99bff, 1.5);
                alien.add([aura, body, eyeL, eyeR, feelerL, feelerR]);
                alien.radius = radius + 3; alien.speed = Phaser.Math.Between(38, 68); alien.hp = 1; alien.wobble = Math.random() * 6;
                this.aliens.push(alien);
            }
            damageShip() {
                if (state.finished || state.paused) return;
                state.integrity = Math.max(0, state.integrity - 20); renderScore();
                this.ship.setAlpha(.35); this.time.delayedCall(180, () => { if (this.ship && this.ship.active) this.ship.setAlpha(1); });
                if (state.integrity === 0) {
                    state.lives = Math.max(0, state.lives - 1); state.integrity = 100; renderScore();
                    toast(state.lives ? `Projétil rival! Você perdeu uma vida. Restam ${state.lives}.` : 'Sua nave ficou sem vidas!');
                    if (state.lives === 0) { state.finished = true; this.physics?.pause?.(); toast('Fim de jogo. Vamos integrar a tela de resultado na etapa 4.'); }
                } else {
                    toast(`Projétil rival atingiu a nave: integridade em ${state.integrity}%.`);
                }
            }
        }
        state.game = new Phaser.Game({ type: Phaser.AUTO, parent: ui.canvas, width: '100%', height: '100%', backgroundColor: '#080b20', transparent: true, render: { antialias: true, roundPixels: true }, scale: { mode: Phaser.Scale.RESIZE, autoCenter: Phaser.Scale.CENTER_BOTH }, input: { activePointers: 2, touch: { capture: true } }, scene: [SpaceScene] });
    }
    function showQuestion(q, index) { state.question = q; state.index = index; state.locked = false; ui.question.hidden = false; ui.step.textContent = index === 5 ? 'DESAFIO ESPECIAL · 6/6' : `QUESTÃO ${index + 1}/6`; ui.type.textContent = index === 5 ? 'CHEFE DE SETOR' : 'TRANSMISSÃO'; ui.progress.style.width = `${((index + 1) / 6) * 100}%`; ui.text.textContent = q.pergunta; ui.answers.innerHTML = ''; ui.feedback.textContent = ''; ui.feedback.className = 'answer-feedback'; ui.next.hidden = true; q.alternativas.forEach((a, i) => { const b = document.createElement('button'); b.type = 'button'; b.className = 'answer-button'; b.textContent = `${String.fromCharCode(65 + i)}. ${a.texto}`; b.addEventListener('click', () => answer(a.id, b)); ui.answers.appendChild(b) }); }
    async function answer(alternativeId, button) { if (state.locked || !state.question) return; state.locked = true; const buttons = [...ui.answers.querySelectorAll('button')]; buttons.forEach(b => b.disabled = true); try { const data = await api('answer', { questao: state.question.id, alternativa: alternativeId }); state.score = data.score; state.lives = data.lives; state.correct = data.correctCount; renderScore(); button.classList.add(data.correct ? 'correct' : 'incorrect'); ui.feedback.classList.add(data.correct ? 'good' : 'bad'); ui.feedback.textContent = (data.correct ? 'ACERTOU! ' : 'NÃO FOI DESSA VEZ. ') + (data.explanation || ''); if (!data.correct) toast(state.lives ? `A nave perdeu energia. Restam ${state.lives} vidas.` : 'Energia esgotada!'); ui.next.textContent = data.finished ? 'VER RESULTADO →' : 'PRÓXIMA QUESTÃO →'; ui.next.hidden = false; ui.next.focus({ preventScroll: true }); state.pendingFinish = data.finished; } catch (e) { state.locked = false; buttons.forEach(b => b.disabled = false); toast(e.message) } }
    async function nextQuestion() { ui.next.disabled = true; try { if (state.pendingFinish) { await finishRun(); return } const data = await api('question'); showQuestion(data.question, data.index) } catch (e) { toast(e.message) } finally { ui.next.disabled = false } }
    async function finishRun() { state.finished = true; try { const data = await api('finish'), r = data.result; ui.question.hidden = true; ui.result.hidden = false; ui.resultSymbol.textContent = r.success ? '✦' : '↻'; ui.resultEyebrow.textContent = r.success ? 'MISSÃO CONCLUÍDA' : 'MISSÃO INTERROMPIDA'; ui.resultTitle.textContent = r.success ? 'Setor explorado!' : 'A nave precisa de reparos'; ui.resultMessage.textContent = r.success ? 'Você concluiu os seis desafios. Seu progresso foi registrado.' : 'Suas vidas acabaram antes de concluir a missão. Você pode tentar novamente.'; ui.resultScore.textContent = String(r.score); ui.resultCorrect.textContent = `${r.correct}/6`; ui.resultXp.textContent = String(r.xp); ui.retry.hidden = false; ui.retry.textContent = 'TENTAR NOVAMENTE'; } catch (e) { state.finished = false; toast(e.message) } }
    async function begin() { ui.start.disabled = true; try { const data = await api('start'); state.phase = data.phase; state.score = 0; state.lives = 3; state.integrity = 100; state.correct = 0; state.index = 0; state.started = true; state.finished = false; state.paused = false; state.pendingFinish = false; renderScore(); ui.intro.hidden = true; startScene(); enterFullscreen(); showQuestion(data.question, 0); ui.tip.style.opacity = '1'; setTimeout(() => { if (ui.tip) ui.tip.style.opacity = '.65' }, 5000) } catch (e) { toast(e.message); ui.start.disabled = false } }
    function pause() { if (!state.started || state.finished) return; state.paused = true; ui.pauseOverlay.hidden = false; if (state.game) state.game.scene.pause('space') }
    function resume() { state.paused = false; ui.pauseOverlay.hidden = true; if (state.game) state.game.scene.resume('space') }
    function exitToMap() { if (document.fullscreenElement) document.exitFullscreen().catch(() => { }); window.location.href = cfg.mapa || 'mathspace.php' }
    ui.start.addEventListener('click', begin); ui.next.addEventListener('click', nextQuestion); ui.pause.addEventListener('click', pause); ui.resume.addEventListener('click', resume); ui.exit.addEventListener('click', pause); ui.pauseExit.addEventListener('click', exitToMap); ui.retry.addEventListener('click', () => window.location.reload()); document.addEventListener('pointermove', e => { if (state.scene && state.started && !state.finished && !state.paused) { const rect = ui.canvas.getBoundingClientRect(); state.scene.pointerTarget = e.clientX - rect.left } }); document.addEventListener('pointerdown', e => { if (state.scene && state.started && !state.finished && !state.paused) { const rect = ui.canvas.getBoundingClientRect(); state.scene.pointerTarget = e.clientX - rect.left } }); document.addEventListener('keydown', e => { if (e.key === 'Escape' && state.started && !state.finished) { if (state.paused) resume(); else pause() } }); document.addEventListener('fullscreenchange', () => { if (state.game) state.game.scale.refresh() });
})();
