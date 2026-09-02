document.addEventListener("DOMContentLoaded", function () {

    const botoesTema = document.querySelectorAll(
        "[data-theme-option]"
    );

    const body = document.body;

    // Tema salvo anteriormente
    const temaSalvo = localStorage.getItem("mathrun-tema");

    // Se não existir tema salvo, usa o claro
    const temaInicial = temaSalvo || "light";

    aplicarTema(temaInicial);


    // Quando clicar em um dos três botões
    botoesTema.forEach(function (botao) {

        botao.addEventListener("click", function () {

            const tema = botao.getAttribute(
                "data-theme-option"
            );

            aplicarTema(tema);

            // Salva a escolha
            localStorage.setItem(
                "mathrun-tema",
                tema
            );
        });

    });


    function aplicarTema(tema) {

        // Remove todos os temas anteriores
        body.classList.remove(
            "dark",
            "pink",
            "tema-escuro",
            "tema-rosa"
        );

        body.removeAttribute("data-theme");


        // Aplica o tema escolhido
        if (tema === "dark") {

            body.classList.add("dark");

            body.setAttribute(
                "data-theme",
                "dark"
            );

        }

        else if (tema === "pink") {

            body.classList.add("pink");

            body.setAttribute(
                "data-theme",
                "pink"
            );

        }

        else {

            body.setAttribute(
                "data-theme",
                "light"
            );

        }


        // Atualiza o botão ativo
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

});