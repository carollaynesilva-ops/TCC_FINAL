document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       VARIÁVEIS DA PARTIDA
       ===================================================== */

    let questaoAtual = 0;

    let pontuacao = 0;
    let acertos = 0;
    let erros = 0;
    let dicasUsadas = 0;

    let respostaSelecionada = false;

    /*
     * Guarda cada resposta dada pelo aluno.
     *
     * O PHP vai usar esses dados para conferir
     * novamente as respostas no banco.
     */
    const respostasPartida = [];

    /*
     * Guarda o momento em que a questão foi exibida.
     * Usado para calcular o tempo de resposta.
     */
    let inicioQuestao = Date.now();

    /*
     * XP disponível durante a partida.
     *
     * O valor inicial deve ser enviado pelo PHP.
     */
    let xpDisponivel =
        typeof usuarioXp !== "undefined"
            ? Number(usuarioXp)
            : 0;


    /* =====================================================
       ELEMENTOS
       ===================================================== */

    const questionText =
        document.getElementById("questionText");

    const questionSubject =
        document.getElementById("questionSubject");

    const questionPoints =
        document.getElementById("questionPoints");

    const alternativesContainer =
        document.getElementById(
            "alternativesContainer"
        );

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
        document.querySelectorAll(
            "[data-theme-option]"
        );

    const body = document.body;

    const temaSalvo =
        localStorage.getItem("mathrun-tema");

    const temaInicial =
        temaSalvo || "light";

    aplicarTema(temaInicial);


    botoesTema.forEach(function (botao) {

        botao.addEventListener(
            "click",
            function () {

                const tema =
                    botao.getAttribute(
                        "data-theme-option"
                    );

                aplicarTema(tema);

                localStorage.setItem(
                    "mathrun-tema",
                    tema
                );
            }
        );
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
                botao.getAttribute(
                    "data-theme-option"
                );

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

        if (!questao) {
            return;
        }

        respostaSelecionada = false;

        inicioQuestao = Date.now();

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

        /*
         * Mostra o progresso até a questão atual.
         *
         * Exemplo:
         *
         * questão 1 de 5 → 0%
         * questão 2 de 5 → 20%
         * ...
         */

        const porcentagem =
            (questaoAtual / questoes.length) * 100;

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


        /*
         * Calculamos apenas para mostrar o feedback
         * imediatamente na tela.
         *
         * O resultado definitivo será recalculado
         * pelo PHP usando o banco de dados.
         */

        const correta =
            Boolean(
                Number(
                    alternativaSelecionada.correta
                )
            );


        if (correta) {

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


            /*
             * Destacar a alternativa correta.
             */

            botoes.forEach(
                function (botao, index) {

                    const alternativa =
                        questao.alternativas[index];

                    if (
                        alternativa &&
                        Boolean(
                            Number(
                                alternativa.correta
                            )
                        )
                    ) {

                        botao.classList.add(
                            "correct"
                        );
                    }
                }
            );
        }


        explanation.textContent =
            questao.explicacao;

        feedback.hidden = false;

        score.textContent =
            pontuacao;

        scoreTop.textContent =
            pontuacao;


        /*
         * Tempo gasto nesta questão.
         */

        const tempoResposta =
            Math.round(
                (Date.now() - inicioQuestao) / 1000
            );


        /*
         * Guardar resposta para enviar ao PHP.
         *
         * IMPORTANTE:
         * Não enviamos "correta" como informação
         * confiável.
         *
         * O PHP vai conferir isso novamente.
         */

        respostasPartida.push({

            questao_id:
                Number(questao.id),

            alternativa_id:
                Number(alternativaSelecionada.id),

            resposta:
                alternativaSelecionada.texto,

            tempo_resposta:
                tempoResposta,

            usou_dica:
                Boolean(
                    questao.dicaUsada
                )

        });


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
    }


    /* =====================================================
       DICAS
       ===================================================== */

    function criarDicas(questao) {

        /*
         * Marca que nenhuma dica foi usada
         * nesta questão.
         */

        questao.dicaUsada = false;


        if (
            !questao.dicas ||
            questao.dicas.length === 0
        ) {

            const vazio =
                document.createElement("p");

            vazio.className =
                "hint-text";

            vazio.textContent =
                "Nenhuma dica disponível para esta questão.";

            hintsContainer.appendChild(vazio);

            return;
        }


        questao.dicas.forEach(
            function (dica) {

                const button =
                    document.createElement("button");

                button.type = "button";

                button.className =
                    "hint-button";

                const texto =
                    document.createElement("span");

                texto.textContent =
                    "💡 Dica " +
                    dica.ordem;

                const custo =
                    document.createElement("span");


                if (
                    Number(dica.custo_xp) > 0
                ) {

                    custo.textContent =
                        "-" +
                        dica.custo_xp +
                        " XP";

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
                            button,
                            questao
                        );
                    }
                );


                hintsContainer.appendChild(
                    button
                );
            }
        );
    }


    /* =====================================================
       MOSTRAR DICA
       ===================================================== */

    function mostrarDica(
        dica,
        button,
        questao
    ) {

        if (button.disabled) {
            return;
        }

        const custo =
            Number(dica.custo_xp);


        /*
         * Verificação visual/local.
         *
         * O PHP também deverá validar o XP
         * definitivamente.
         */

        if (
            custo > 0 &&
            xpDisponivel < custo
        ) {

            mostrarMensagemXP();

            return;
        }


        /*
         * Descontar temporariamente o XP.
         */

        if (custo > 0) {

            xpDisponivel -= custo;

            if (xpDisponivel < 0) {
                xpDisponivel = 0;
            }
        }


        dicasUsadas++;

        questao.dicaUsada = true;


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


    function mostrarMensagemXP() {

        alert(
            "Você não possui XP suficiente para usar esta dica."
        );
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
       FINALIZAR FASE
       ===================================================== */

    function finalizarFase() {

        /*
         * Não usamos mais a pontuação enviada
         * pelo navegador como fonte confiável.
         *
         * Enviamos as respostas individuais.
         */

        const dados =
            new FormData();


        dados.append(
            "fase_id",
            faseId
        );


        dados.append(
            "respostas",
            JSON.stringify(
                respostasPartida
            )
        );


        /*
         * Estes valores servem apenas para
         * compatibilidade com o PHP atual.
         *
         * O PHP definitivo deve recalcular
         * todos eles a partir das respostas.
         */

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


        /*
         * Impedir múltiplos envios.
         */

        nextButton.disabled = true;

        nextButton.textContent =
            "Salvando resultado...";


        fetch(
            "finalizar_mathchef.php",
            {
                method: "POST",
                body: dados
            }
        )
        .then(function (response) {

            if (!response.ok) {
                throw new Error(
                    "Erro ao finalizar a fase."
                );
            }

            return response.json();

        })
        .then(function (resultado) {

            if (!resultado.sucesso) {

                throw new Error(
                    resultado.mensagem ||
                    "Não foi possível salvar a partida."
                );
            }


            /*
             * Usamos o resultado calculado pelo PHP.
             */

            if (
                typeof resultado.pontuacao !==
                "undefined"
            ) {

                pontuacao =
                    Number(
                        resultado.pontuacao
                    );
            }

            if (
                typeof resultado.acertos !==
                "undefined"
            ) {

                acertos =
                    Number(
                        resultado.acertos
                    );
            }

            if (
                typeof resultado.erros !==
                "undefined"
            ) {

                erros =
                    Number(
                        resultado.erros
                    );
            }


            mostrarResultado(
                resultado
            );

        })
        .catch(function (erro) {

            console.error(erro);

            nextButton.disabled = false;

            nextButton.textContent =
                "Tentar novamente";

            alert(
                "Não foi possível salvar sua partida. " +
                "Verifique sua conexão e tente novamente."
            );
        });
    }


    /* =====================================================
       RESULTADO
       ===================================================== */

    function mostrarResultado(resultado) {

        const aproveitamento =
            questoes.length > 0
                ? Math.round(
                    (acertos / questoes.length) *
                    100
                )
                : 0;


        let titulo =
            "Fase concluída!";

        let icone =
            "🏆";


        if (aproveitamento === 100) {

            titulo =
                "Perfeito!";

            icone =
                "👑";

        } else if (aproveitamento >= 70) {

            titulo =
                "Mandou muito bem!";

            icone =
                "🏆";

        } else if (aproveitamento >= 50) {

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


        /*
         * Criar a tela sem colocar conteúdo
         * do banco diretamente dentro de HTML.
         */

        const container =
            document.querySelector(
                ".game-container"
            );

        container.innerHTML = "";


        const resultCard =
            document.createElement("section");

        resultCard.className =
            "result-card";


        const resultIcon =
            document.createElement("div");

        resultIcon.className =
            "result-icon";

        resultIcon.textContent =
            icone;


        const resultTitle =
            document.createElement("h1");

        resultTitle.textContent =
            titulo;


        const resultDescription =
            document.createElement("p");

        resultDescription.textContent =
            "Você terminou a fase " +
            (
                resultado.fase &&
                resultado.fase.nome
                    ? resultado.fase.nome
                    : "selecionada"
            ) +
            ".";


        /*
         * Pontuação
         */

        const resultScore =
            document.createElement("div");

        resultScore.className =
            "result-score";


        const scoreLabel =
            document.createElement("span");

        scoreLabel.textContent =
            "Pontuação";


        const scoreValue =
            document.createElement("strong");

        scoreValue.textContent =
            pontuacao;


        resultScore.appendChild(
            scoreLabel
        );

        resultScore.appendChild(
            scoreValue
        );


        /*
         * Estatísticas
         */

        const resultStats =
            document.createElement("div");

        resultStats.className =
            "result-stats";


        criarEstatistica(
            resultStats,
            acertos,
            "Acertos"
        );

        criarEstatistica(
            resultStats,
            erros,
            "Erros"
        );

        criarEstatistica(
            resultStats,
            aproveitamento + "%",
            "Aproveitamento"
        );


        /*
         * Botões
         */

        const resultButtons =
            document.createElement("div");

        resultButtons.className =
            "result-buttons";


        const voltar =
            document.createElement("a");

        voltar.href =
            "mathchef.php";

        voltar.className =
            "secondary-result-button";

        voltar.textContent =
            "← Voltar para fases";


        const novamente =
            document.createElement("a");

        novamente.href =
            "jogar_mathchef.php?fase=" +
            encodeURIComponent(faseId);

        novamente.className =
            "primary-result-button";

        novamente.textContent =
            "Jogar novamente";


        resultButtons.appendChild(
            voltar
        );

        resultButtons.appendChild(
            novamente
        );


        /*
         * Montar resultado
         */

        resultCard.appendChild(
            resultIcon
        );

        resultCard.appendChild(
            resultTitle
        );

        resultCard.appendChild(
            resultDescription
        );

        resultCard.appendChild(
            resultScore
        );

        resultCard.appendChild(
            resultStats
        );

        resultCard.appendChild(
            resultButtons
        );

        container.appendChild(
            resultCard
        );


        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    }


    function criarEstatistica(
        container,
        valor,
        legenda
    ) {

        const stat =
            document.createElement("div");

        stat.className =
            "result-stat";


        const valorElemento =
            document.createElement("strong");

        valorElemento.textContent =
            valor;


        const legendaElemento =
            document.createElement("span");

        legendaElemento.textContent =
            legenda;


        stat.appendChild(
            valorElemento
        );

        stat.appendChild(
            legendaElemento
        );

        container.appendChild(
            stat
        );
    }


    /* =====================================================
       INICIAR
       ===================================================== */

    carregarQuestao();

});