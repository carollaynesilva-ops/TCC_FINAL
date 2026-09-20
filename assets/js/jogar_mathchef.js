document.addEventListener("DOMContentLoaded", function () {

    ```
/* =====================================================
   VARIÁVEIS DA PARTIDA
   ===================================================== */

let questaoAtual = 0;

let pontuacao = 0;
let acertos = 0;
let erros = 0;
let dicasUsadas = 0;

let respostaSelecionada = false;
let finalizandoFase = false;

/*
 * Guarda todas as respostas dadas durante a partida.
 *
 * O PHP irá conferir novamente essas informações
 * usando os dados do banco.
 */
const respostasPartida = [];

/*
 * Guarda o momento em que a questão foi exibida.
 *
 * É utilizado para calcular o tempo de resposta.
 */
let inicioQuestao = Date.now();

/*
 * XP disponível durante a partida.

 * IMPORTANTE:
 * O JavaScript usa esse valor somente para atualizar
 * a interface.
 *
 * O valor oficial é controlado pelo PHP.
 */
let xpDisponivel =
    typeof usuarioXp !== "undefined"
        ? Number(usuarioXp)
        : 0;


/* =====================================================
   ELEMENTOS DA PÁGINA
   ===================================================== */

const questionText =
    document.getElementById("questionText");

const questionSubject =
    document.getElementById("questionSubject");

const questionPoints =
    document.getElementById("questionPoints");

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
   VERIFICAR DADOS RECEBIDOS DO PHP
   ===================================================== */

/*
 * O PHP precisa fornecer o array de questões.
 *
 * Caso ele não exista ou não seja um array,
 * evitamos que o JavaScript quebre completamente.
 */
if (
    typeof questoes === "undefined" ||
    !Array.isArray(questoes)
) {
    console.error(
        "As questões não foram carregadas corretamente."
    );

    return;
}

/*
 * Não existe partida sem questões.
 */
if (questoes.length === 0) {
    console.error(
        "Nenhuma questão foi encontrada para esta fase."
    );

    return;
}


/* =====================================================
   XP NA INTERFACE
   ===================================================== */

/*
 * Procura elementos que possam mostrar o XP.
 *
 * Se algum deles existir na página, ele será atualizado.
 */
const xpElements = [
    document.getElementById("xp"),
    document.getElementById("xpValue"),
    document.getElementById("userXp"),
    document.getElementById("xpTop")
].filter(Boolean);


function atualizarXPInterface() {

    xpElements.forEach(function (elemento) {

        elemento.textContent = xpDisponivel;

    });

}


/* =====================================================
   PONTUAÇÃO NA INTERFACE
   ===================================================== */

function atualizarPontuacaoInterface() {

    if (score) {
        score.textContent = pontuacao;
    }

    if (scoreTop) {
        scoreTop.textContent = pontuacao;
    }

}


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

const temasPermitidos = [
    "light",
    "dark",
    "pink"
];

let temaInicial =
    temasPermitidos.includes(temaSalvo)
        ? temaSalvo
        : "light";


aplicarTema(temaInicial);


botoesTema.forEach(function (botao) {

    botao.addEventListener(
        "click",
        function () {

            const tema =
                botao.getAttribute(
                    "data-theme-option"
                );

            if (
                !temasPermitidos.includes(tema)
            ) {
                return;
            }

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


    /*
     * Atualizar número da questão.
     */
    if (questionNumber) {

        questionNumber.textContent =
            questaoAtual + 1;

    }


    if (totalQuestions) {

        totalQuestions.textContent =
            questoes.length;

    }


    /*
     * Atualizar pergunta.
     */
    if (questionText) {

        questionText.textContent =
            questao.pergunta || "";

    }


    /*
     * Atualizar matéria.
     */
    if (questionSubject) {

        questionSubject.textContent =
            questao.materia ||
            "Matemática";

    }


    /*
     * Atualizar pontuação da questão.
     */
    if (questionPoints) {

        questionPoints.textContent =
            Number(questao.pontuacao || 0);

    }


    /*
     * Esconder feedback.
     */
    if (feedback) {

        feedback.hidden = true;

        feedback.className =
            "feedback";

    }


    /*
     * Esconder botão de próxima questão.
     */
    if (nextButton) {

        nextButton.hidden = true;

        nextButton.disabled = false;

    }


    /*
     * Limpar alternativas e dicas anteriores.
     */
    if (alternativesContainer) {

        alternativesContainer.innerHTML = "";

    }


    if (hintsContainer) {

        hintsContainer.innerHTML = "";

    }


    /*
     * Criar alternativas e dicas.
     */
    criarAlternativas(questao);

    criarDicas(questao);


    /*
     * Atualizar barra de progresso.
     */
    atualizarProgresso();

}


/* =====================================================
   PROGRESSO
   ===================================================== */

function atualizarProgresso() {

    if (!progressFill ||
        !progressPercent) {

        return;

    }


    /*
     * Enquanto a questão está sendo respondida,
     * mostramos o progresso das questões anteriores.
     *
     * Exemplo:
     *
     * questão 1 de 5 → 0%
     * questão 2 de 5 → 20%
     * questão 3 de 5 → 40%
     */
    const porcentagem =
        questoes.length > 0
            ? (questaoAtual / questoes.length) * 100
            : 0;


    progressFill.style.width =
        porcentagem + "%";


    progressPercent.textContent =
        Math.round(porcentagem) + "%";

}


/* =====================================================
   ALTERNATIVAS
   ===================================================== */

function criarAlternativas(questao) {

    if (!alternativesContainer) {
        return;
    }


    const letras =
        ["A", "B", "C", "D"];


    /*
     * Garante que exista um array.
     */
    const alternativas =
        Array.isArray(questao.alternativas)
            ? questao.alternativas
            : [];


    alternativas.forEach(
        function (alternativa, index) {

            const button =
                document.createElement("button");

            button.type = "button";

            button.className =
                "alternative-button";


            /*
             * Letra da alternativa.
             */
            const letra =
                document.createElement("span");

            letra.className =
                "alternative-letter";

            letra.textContent =
                letras[index] || "?";


            /*
             * Texto da alternativa.
             */
            const texto =
                document.createElement("span");

            texto.className =
                "alternative-text";

            texto.textContent =
                alternativa.texto || "";


            button.appendChild(letra);

            button.appendChild(texto);


            /*
             * Clique da alternativa.
             */
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

    /*
     * Impede selecionar duas alternativas
     * para a mesma questão.
     */
    if (respostaSelecionada) {
        return;
    }


    /*
     * Validação básica.
     */
    if (!alternativaSelecionada) {
        return;
    }


    respostaSelecionada = true;


    const questao =
        questoes[questaoAtual];


    if (!questao) {
        return;
    }


    /*
     * Todas as alternativas ficam desabilitadas.
     */
    const botoes =
        document.querySelectorAll(
            ".alternative-button"
        );


    botoes.forEach(function (botao) {

        botao.disabled = true;

    });


    /*
     * O JS usa a informação recebida do banco
     * somente para apresentar o feedback imediato.
     *
     * A validação oficial será feita pelo PHP.
     */
    const correta =
        Number(
            alternativaSelecionada.correta
        ) === 1;


    if (correta) {

        botaoSelecionado.classList.add(
            "correct"
        );


        acertos++;


        /*
         * Pontuação visual da partida.
         */
        pontuacao +=
            Number(questao.pontuacao || 0);


        feedbackIcon.textContent =
            "✓";


        feedbackTitle.textContent =
            "Resposta correta!";


        feedbackText.textContent =
            "Você acertou e ganhou " +
            Number(questao.pontuacao || 0) +
            " pontos.";


        feedback.classList.add(
            "correct-feedback"
        );


    } else {

        botaoSelecionado.classList.add(
            "wrong"
        );


        erros++;


        feedbackIcon.textContent =
            "✕";


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
                    Number(
                        alternativa.correta
                    ) === 1
                ) {

                    botao.classList.add(
                        "correct"
                    );

                }

            }
        );

    }


    /*
     * Mostrar explicação.
     */
    explanation.textContent =
        questao.explicacao || "";


    feedback.hidden = false;


    /*
     * Atualizar pontuação.
     */
    atualizarPontuacaoInterface();


    /*
     * Calcular tempo de resposta.
     */
    const tempoResposta =
        Math.max(
            0,
            Math.round(
                (Date.now() - inicioQuestao) /
                1000
            )
        );


    /*
     * Guardar resposta para enviar ao PHP.
     *
     * Não enviamos "correta" como dado confiável.
     *
     * O PHP irá consultar a alternativa no banco.
     */
    respostasPartida.push({

        questao_id:
            Number(questao.id),

        alternativa_id:
            Number(
                alternativaSelecionada.id
            ),

        resposta:
            alternativaSelecionada.texto || "",

        tempo_resposta:
            tempoResposta,

        usou_dica:
            Boolean(
                questao.dicaUsada
            )

    });


    /*
     * Alterar texto do botão.
     */
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
     * Toda questão começa sem dica utilizada.
     */
    questao.dicaUsada = false;


    if (!hintsContainer) {
        return;
    }


    if (
        !questao.dicas ||
        !Array.isArray(questao.dicas) ||
        questao.dicas.length === 0
    ) {

        const vazio =
            document.createElement("p");

        vazio.className =
            "hint-text";

        vazio.textContent =
            "Nenhuma dica disponível para esta questão.";


        hintsContainer.appendChild(
            vazio
        );

        return;

    }


    questao.dicas.forEach(
        function (dica) {

            const button =
                document.createElement("button");

            button.type = "button";

            button.className =
                "hint-button";


            /*
             * Texto da dica.
             */
            const texto =
                document.createElement("span");

            texto.textContent =
                "💡 Dica " +
                Number(dica.ordem || 1);


            /*
             * Custo da dica.
             */
            const custo =
                document.createElement("span");


            const custoXP =
                Number(dica.custo_xp || 0);


            if (custoXP > 0) {

                custo.textContent =
                    "-" +
                    custoXP +
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

    /*
     * Impede clicar novamente.
     */
    if (
        button.disabled ||
        questao.dicaUsada
    ) {
        return;
    }


    const custo =
        Number(dica.custo_xp || 0);


    /*
     * Verificação local apenas para melhorar
     * a experiência do usuário.
     *
     * O PHP continua sendo responsável
     * pela validação verdadeira.
     */
    if (
        custo > 0 &&
        xpDisponivel < custo
    ) {

        mostrarMensagemXP();

        return;

    }


    /*
     * Desabilitar imediatamente.
     */
    button.disabled = true;

    button.classList.add("loading");

    button.setAttribute(
        "aria-busy",
        "true"
    );


    /*
     * Dados enviados para o PHP.
     */
    const dados =
        new FormData();


    dados.append(
        "questao_id",
        questao.id
    );


    dados.append(
        "fase_id",
        faseId
    );


    /*
     * Solicitar a dica ao servidor.
     */
    fetch(
        "usar_dica_mathchef.php",
        {
            method: "POST",
            body: dados
        }
    )

        .then(function (response) {

            return response
                .json()
                .then(function (resultado) {

                    if (!response.ok) {

                        throw new Error(
                            resultado.mensagem ||
                            "Não foi possível usar a dica."
                        );

                    }

                    return resultado;

                });

        })

        .then(function (resultado) {

            /*
             * O servidor recusou a utilização.
             */
            if (!resultado.sucesso) {

                throw new Error(
                    resultado.mensagem ||
                    "Não foi possível liberar a dica."
                );

            }


            /*
             * Atualizar XP usando o valor
             * enviado pelo servidor.
             */
            if (
                typeof resultado.xp_atual !==
                "undefined"
            ) {

                xpDisponivel =
                    Number(
                        resultado.xp_atual
                    );

            } else {

                /*
                 * Fallback visual.
                 */
                xpDisponivel -= custo;

            }


            /*
             * Nunca mostrar XP negativo.
             */
            if (xpDisponivel < 0) {

                xpDisponivel = 0;

            }


            atualizarXPInterface();


            /*
             * Registrar que uma dica foi utilizada.
             */
            dicasUsadas++;

            questao.dicaUsada = true;


            /*
             * Criar o texto da dica.
             */
            const dicaTexto =
                document.createElement("div");

            dicaTexto.className =
                "hint-text";


            /*
             * Preferir o texto retornado pelo servidor.
             */
            if (
                resultado.dica &&
                resultado.dica.texto
            ) {

                dicaTexto.textContent =
                    resultado.dica.texto;

            } else {

                dicaTexto.textContent =
                    dica.texto || "";

            }


            /*
             * Remover estado de carregamento.
             */
            button.classList.remove(
                "loading"
            );

            button.removeAttribute(
                "aria-busy"
            );


            /*
             * Mostrar a dica.
             */
            button.parentNode.insertBefore(
                dicaTexto,
                button.nextSibling
            );


            /*
             * Manter o botão desabilitado.
             */
            button.disabled = true;


            /*
             * Como só pode existir uma dica por questão,
             * desabilitamos as demais.
             */
            const outrosBotoes =
                hintsContainer.querySelectorAll(
                    ".hint-button"
                );


            outrosBotoes.forEach(
                function (outroBotao) {

                    outroBotao.disabled =
                        true;

                }
            );

        })

        .catch(function (erro) {

            console.error(
                "Erro ao usar dica:",
                erro
            );


            /*
             * Permitir tentar novamente.
             */
            button.disabled = false;

            button.classList.remove(
                "loading"
            );

            button.removeAttribute(
                "aria-busy"
            );


            alert(
                erro.message ||
                "Não foi possível usar a dica."
            );

        });

}


/* =====================================================
   MENSAGEM DE XP INSUFICIENTE
   ===================================================== */

function mostrarMensagemXP() {

    alert(
        "Você não possui XP suficiente para usar esta dica."
    );

}


/* =====================================================
   PRÓXIMA QUESTÃO
   ===================================================== */

if (nextButton) {

    nextButton.addEventListener(
        "click",
        function () {

            /*
             * Impede cliques enquanto uma finalização
             * já está acontecendo.
             */
            if (finalizandoFase) {
                return;
            }


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

}


/* =====================================================
   FINALIZAR FASE
   ===================================================== */

function finalizarFase() {

    /*
     * Evita enviar a mesma partida duas vezes.
     */
    if (finalizandoFase) {
        return;
    }


    finalizandoFase = true;


    /*
     * Criar dados da requisição.
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
     * Desabilitar botão enquanto salva.
     */
    if (nextButton) {

        nextButton.disabled = true;

        nextButton.textContent =
            "Salvando resultado...";

    }


    /*
     * Enviar resultado para o PHP.
     */
    fetch(
        "finalizar_mathchef.php",
        {
            method: "POST",
            body: dados
        }
    )

        .then(function (response) {

            return response
                .json()
                .then(function (resultado) {

                    if (!response.ok) {

                        throw new Error(
                            resultado.mensagem ||
                            "Erro ao finalizar a fase."
                        );

                    }

                    return resultado;

                });

        })

        .then(function (resultado) {

            /*
             * Verificar se o servidor confirmou
             * a finalização.
             */
            if (!resultado.sucesso) {

                throw new Error(
                    resultado.mensagem ||
                    "Não foi possível salvar a partida."
                );

            }


            /*
             * Usar os valores oficiais enviados pelo PHP.
             */
            pontuacao =
                Number(
                    resultado.pontuacao || 0
                );


            acertos =
                Number(
                    resultado.acertos || 0
                );


            erros =
                Number(
                    resultado.erros || 0
                );


            dicasUsadas =
                Number(
                    resultado.dicas_usadas || 0
                );


            /*
             * Atualizar XP caso o PHP envie
             * o valor oficial.
             */
            if (
                typeof resultado.xp_atual !==
                "undefined"
            ) {

                xpDisponivel =
                    Number(
                        resultado.xp_atual
                    );

                atualizarXPInterface();

            }


            atualizarPontuacaoInterface();


            /*
             * Mostrar resultado.
             */
            mostrarResultado(
                resultado
            );

        })

        .catch(function (erro) {

            console.error(
                "Erro ao finalizar MathChef:",
                erro
            );


            /*
             * Permitir uma nova tentativa.
             */
            finalizandoFase = false;


            if (nextButton) {

                nextButton.disabled = false;

                nextButton.textContent =
                    "Tentar novamente";

            }


            alert(
                erro.message ||
                "Não foi possível salvar sua partida."
            );

        });

}


/* =====================================================
   RESULTADO FINAL
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
     * Localizar container principal.
     */
    const container =
        document.querySelector(
            ".game-container"
        );


    if (!container) {
        return;
    }


    /*
     * Limpar conteúdo da partida.
     */
    container.innerHTML = "";


    /* =================================================
       CARD DE RESULTADO
       ================================================= */

    const resultCard =
        document.createElement("section");

    resultCard.className =
        "result-card";


    /*
     * Ícone.
     */
    const resultIcon =
        document.createElement("div");

    resultIcon.className =
        "result-icon";

    resultIcon.textContent =
        icone;


    /*
     * Título.
     */
    const resultTitle =
        document.createElement("h1");

    resultTitle.textContent =
        titulo;


    /*
     * Descrição.
     */
    const resultDescription =
        document.createElement("p");


    const nomeFase =
        resultado.fase &&
        resultado.fase.nome
            ? resultado.fase.nome
            : "selecionada";


    resultDescription.textContent =
        "Você terminou a fase " +
        nomeFase +
        ".";


    /* =================================================
       PONTUAÇÃO
       ================================================= */

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


    /* =================================================
       ESTATÍSTICAS
       ================================================= */

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


    criarEstatistica(
        resultStats,
        dicasUsadas,
        "Dicas usadas"
    );


    /*
     * XP atual.
     */
    criarEstatistica(
        resultStats,
        xpDisponivel + " XP",
        "XP atual"
    );


    /*
     * XP ganho, caso o PHP envie.
     */
    if (
        typeof resultado.xp_ganho !==
        "undefined"
    ) {

        criarEstatistica(
            resultStats,
            "+" +
            Number(
                resultado.xp_ganho || 0
            ) +
            " XP",
            "XP ganho"
        );

    }


    /* =================================================
       BOTÕES
       ================================================= */

    const resultButtons =
        document.createElement("div");

    resultButtons.className =
        "result-buttons";


    /*
     * Voltar para fases.
     */
    const voltar =
        document.createElement("a");

    voltar.href =
        "mathchef.php";

    voltar.className =
        "secondary-result-button";

    voltar.textContent =
        "← Voltar para fases";


    /*
     * Jogar novamente.
     */
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


    /* =================================================
       MONTAR CARD
       ================================================= */

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


    /*
     * Voltar para o topo.
     */
    window.scrollTo({

        top: 0,

        behavior: "smooth"

    });

}


/* =====================================================
   CRIAR ESTATÍSTICA
   ===================================================== */

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
   INICIAR PARTIDA
   ===================================================== */

atualizarXPInterface();

atualizarPontuacaoInterface();

carregarQuestao();
```

});
