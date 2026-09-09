document.addEventListener("DOMContentLoaded", function () {

    let questaoAtual = 0;
    let pontuacao = 0;
    let acertos = 0;
    let erros = 0;
    let dicasUsadas = 0;

    let respostaSelecionada = false;

    const questionText = document.getElementById("questionText");
    const questionSubject = document.getElementById("questionSubject");
    const questionPoints = document.getElementById("questionPoints");

    const alternativesContainer =
        document.getElementById("alternativesContainer");

    const feedback =
        document.getElementById("feedback");

    const feedbackIcon =
        document.getElementById("feedbackIcon");

    const feedbackTitle =
        document.getElementById("feedbackTitle");

    const feedbackText =
        document.getElementById("feedbackText");

    const explanation =
        document.getElementById("explanation");

    const nextButton =
        document.getElementById("nextButton");

    const nextButtonText =
        document.getElementById("nextButtonText");

    const score =
        document.getElementById("score");

    const scoreTop =
        document.getElementById("scoreTop");

    const questionNumber =
        document.getElementById("questionNumber");

    const totalQuestions =
        document.getElementById("totalQuestions");

    const progressFill =
        document.getElementById("progressFill");

    const progressPercent =
        document.getElementById("progressPercent");

    const hintsContainer =
        document.getElementById("hintsContainer");


    /* =====================================================
       TEMA
       ===================================================== */

    const botoesTema =
        document.querySelectorAll("[data-theme-option]");

    const body = document.body;

    const temaSalvo =
        localStorage.getItem("mathrun-tema");

    const temaInicial =
        temaSalvo || "light";

    aplicarTema(temaInicial);


    botoesTema.forEach(function (botao) {

        botao.addEventListener("click", function () {

            const tema =
                botao.getAttribute("data-theme-option");

            aplicarTema(tema);

            localStorage.setItem(
                "mathrun-tema",
                tema
            );
        });

    });


    function aplicarTema(tema) {

        body.classList.remove(
            "dark",
            "pink",
            "tema-escuro",
            "tema-rosa"
        );

        body.removeAttribute("data-theme");

        if (tema === "dark") {

            body.classList.add("dark");

            body.setAttribute(
                "data-theme",
                "dark"
            );

        } else if (tema === "pink") {

            body.classList.add("pink");

            body.setAttribute(
                "data-theme",
                "pink"
            );

        } else {

            body.setAttribute(
                "data-theme",
                "light"
            );
        }

        botoesTema.forEach(function (botao) {

            const temaBotao =
                botao.getAttribute("data-theme-option");

            botao.classList.toggle(
                "active",
                temaBotao === tema
            );
        });
    }


    /* =====================================================
       CARREGAR QUESTÃO
       ===================================================== */

    function carregarQuestao() {

        const questao =
            questoes[questaoAtual];

        respostaSelecionada = false;

        questionNumber.textContent =
            questaoAtual + 1;

        totalQuestions.textContent =
            questoes.length;

        questionText.textContent =
            questao.pergunta;

        questionSubject.textContent =
            questao.materia || "Matemática";

        questionPoints.textContent =
            questao.pontuacao;

        feedback.hidden = true;

        feedback.className =
            "feedback";

        nextButton.hidden = true;

        alternativesContainer.innerHTML = "";

        hintsContainer.innerHTML = "";

        atualizarProgresso();

        criarAlternativas(questao);

        criarDicas(questao);
    }


    /* =====================================================
       PROGRESSO
       ===================================================== */

    function atualizarProgresso() {

        const porcentagem =
            ((questaoAtual) / questoes.length) * 100;

        progressFill.style.width =
            porcentagem + "%";

        progressPercent.textContent =
            Math.round(porcentagem) + "%";
    }


    /* =====================================================
       ALTERNATIVAS
       ===================================================== */

    function criarAlternativas(questao) {

        const letras =
            ["A", "B", "C", "D"];

        questao.alternativas.forEach(
            function (alternativa, index) {

                const button =
                    document.createElement("button");

                button.type = "button";

                button.className =
                    "alternative-button";

                const letra =
                    document.createElement("span");

                letra.className =
                    "alternative-letter";

                letra.textContent =
                    letras[index] || "?";

                const texto =
                    document.createElement("span");

                texto.textContent =
                    alternativa.texto;

                button.appendChild(letra);

                button.appendChild(texto);

                button.addEventListener(
                    "click",
                    function () {

                        verificarResposta(
                            alternativa,
                            button
                        );

                    }
                );

                alternativesContainer.appendChild(
                    button
                );
            }
        );
    }


    /* =====================================================
       VERIFICAR RESPOSTA
       ===================================================== */

    function verificarResposta(
        alternativaSelecionada,
        botaoSelecionado
    ) {

        if (respostaSelecionada) {
            return;
        }

        respostaSelecionada = true;

        const questao =
            questoes[questaoAtual];

        const botoes =
            document.querySelectorAll(
                ".alternative-button"
            );

        botoes.forEach(function (botao) {

            botao.disabled = true;

        });


        if (
            Boolean(
                Number(alternativaSelecionada.correta)
            )
        ) {

            botaoSelecionado.classList.add(
                "correct"
            );

            acertos++;

            pontuacao +=
                Number(questao.pontuacao);

            feedbackIcon.textContent = "✓";

            feedbackTitle.textContent =
                "Resposta correta!";

            feedbackText.textContent =
                "Você acertou e ganhou " +
                questao.pontuacao +
                " pontos.";

            feedback.classList.add(
                "correct-feedback"
            );

        } else {

            botaoSelecionado.classList.add(
                "wrong"
            );

            erros++;

            feedbackIcon.textContent = "✕";

            feedbackTitle.textContent =
                "Não foi dessa vez!";

            feedbackText.textContent =
                "A resposta correta está destacada.";

            feedback.classList.add(
                "wrong-feedback"
            );


            botoes.forEach(function (botao, index) {

                const alternativa =
                    questao.alternativas[index];

                if (
                    alternativa &&
                    Boolean(
                        Number(alternativa.correta)
                    )
                ) {

                    botao.classList.add(
                        "correct"
                    );
                }
            });
        }


        explanation.textContent =
            questao.explicacao;

        feedback.hidden = false;

        score.textContent =
            pontuacao;

        scoreTop.textContent =
            pontuacao;


        if (
            questaoAtual <
            questoes.length - 1
        ) {

            nextButtonText.textContent =
                "Próxima questão";

        } else {

            nextButtonText.textContent =
                "Finalizar fase";
        }

        nextButton.hidden = false;

        salvarResposta(
            questao,
            alternativaSelecionada
        );
    }


    /* =====================================================
       DICAS
       ===================================================== */

    function criarDicas(questao) {

        if (!questao.dicas || questao.dicas.length === 0) {

            const vazio =
                document.createElement("p");

            vazio.className =
                "hint-text";

            vazio.textContent =
                "Nenhuma dica disponível para esta questão.";

            hintsContainer.appendChild(vazio);

            return;
        }


        questao.dicas.forEach(function (dica) {

            const button =
                document.createElement("button");

            button.type = "button";

            button.className =
                "hint-button";

            const texto =
                document.createElement("span");

            texto.textContent =
                "💡 Dica " + dica.ordem;

            const custo =
                document.createElement("span");

            if (Number(dica.custo_xp) > 0) {

                custo.textContent =
                    "-" + dica.custo_xp + " XP";

            } else {

                custo.textContent =
                    "Grátis";
            }

            button.appendChild(texto);

            button.appendChild(custo);


            button.addEventListener(
                "click",
                function () {

                    mostrarDica(
                        dica,
                        button
                    );

                }
            );

            hintsContainer.appendChild(
                button
            );
        });
    }


    function mostrarDica(dica, button) {

        if (button.disabled) {
            return;
        }

        const custo =
            Number(dica.custo_xp);

        const xpAtual =
            Number(usuarioXpAtual());

        if (
            custo > 0 &&
            xpAtual < custo
        ) {

            alert(
                "Você não possui XP suficiente para usar esta dica."
            );

            return;
        }

        if (custo > 0) {

            descontarXP(custo);
        }

        dicasUsadas++;

        const dicaTexto =
            document.createElement("div");

        dicaTexto.className =
            "hint-text";

        dicaTexto.textContent =
            dica.texto;

        button.disabled = true;

        button.parentNode.insertBefore(
            dicaTexto,
            button.nextSibling
        );
    }


    function usuarioXpAtual() {

        return 999999;
    }


    function descontarXP(custo) {

        /*
         * O desconto definitivo do XP pode ser
         * tratado no PHP posteriormente.
         *
         * Neste primeiro momento o custo da dica
         * fica registrado na partida.
         */
    }


    /* =====================================================
       SALVAR RESPOSTA
       ===================================================== */

    function salvarResposta(
        questao,
        alternativa
    ) {

        /*
         * As respostas são enviadas ao PHP
         * somente quando a partida é finalizada.
         *
         * Elas ficam armazenadas no navegador
         * durante a partida.
         */
    }


    /* =====================================================
       PRÓXIMA QUESTÃO
       ===================================================== */

    nextButton.addEventListener(
        "click",
        function () {

            if (
                questaoAtual <
                questoes.length - 1
            ) {

                questaoAtual++;

                carregarQuestao();

                window.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });

            } else {

                finalizarFase();
            }
        }
    );


    /* =====================================================
       FINALIZAR
       ===================================================== */

    function finalizarFase() {

        const porcentagem =
            Math.round(
                (acertos / questoes.length) * 100
            );

        let titulo =
            "Fase concluída!";

        let icone =
            "🏆";

        if (porcentagem === 100) {

            titulo =
                "Perfeito!";

            icone =
                "👑";

        } else if (porcentagem >= 70) {

            titulo =
                "Mandou muito bem!";

            icone =
                "🏆";

        } else if (porcentagem >= 50) {

            titulo =
                "Bom trabalho!";

            icone =
                "⭐";

        } else {

            titulo =
                "Fase concluída!";

            icone =
                "🍳";
        }


        const dados =
            new FormData();

        dados.append(
            "usuario_id",
            usuarioId
        );

        dados.append(
            "fase_id",
            faseId
        );

        dados.append(
            "pontuacao",
            pontuacao
        );

        dados.append(
            "acertos",
            acertos
        );

        dados.append(
            "erros",
            erros
        );

        dados.append(
            "dicas_usadas",
            dicasUsadas
        );


        fetch(
            "finalizar_mathchef.php",
            {
                method: "POST",
                body: dados
            }
        )
        .then(function (response) {

            return response.json();

        })
        .then(function (resultado) {

            mostrarResultado(
                titulo,
                icone,
                resultado
            );

        })
        .catch(function () {

            mostrarResultado(
                titulo,
                icone,
                {
                    sucesso: false
                }
            );
        });
    }


    /* =====================================================
       RESULTADO
       ===================================================== */

    function mostrarResultado(
        titulo,
        icone,
        resultado
    ) {

        document.querySelector(
            ".game-container"
        ).innerHTML = `

            <section class="result-card">

                <div class="result-icon">
                    ${icone}
                </div>

                <h1>
                    ${titulo}
                </h1>

                <p>
                    Você terminou a fase
                    <strong>${faseNome()}</strong>.
                </p>

                <div class="result-score">

                    <span>
                        Pontuação
                    </span>

                    <strong>
                        ${pontuacao}
                    </strong>

                </div>

                <div class="result-stats">

                    <div class="result-stat">
                        <strong>
                            ${acertos}
                        </strong>

                        <span>
                            Acertos
                        </span>
                    </div>

                    <div class="result-stat">
                        <strong>
                            ${erros}
                        </strong>

                        <span>
                            Erros
                        </span>
                    </div>

                    <div class="result-stat">
                        <strong>
                            ${Math.round(
                                (acertos / questoes.length) * 100
                            )}%
                        </strong>

                        <span>
                            Aproveitamento
                        </span>
                    </div>

                </div>

                <div class="result-buttons">

                    <a
                        href="mathchef.php"
                        class="secondary-result-button">
                        ← Voltar para fases
                    </a>

                    <a
                        href="jogar_mathchef.php?fase=${faseId}"
                        class="primary-result-button">
                        Jogar novamente
                    </a>

                </div>

            </section>
        `;
    }


    function faseNome() {

        const titulo =
            document.querySelector(
                ".phase-info h1"
            );

        return titulo
            ? titulo.textContent.trim()
            : "esta fase";
    }


    /* =====================================================
       COMEÇAR
       ===================================================== */

    carregarQuestao();

});