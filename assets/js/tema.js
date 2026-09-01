const temas = ["light", "dark", "pink"];

const temaSalvo =
    localStorage.getItem("mathrun-theme") || "light";

document.documentElement.setAttribute(
    "data-theme",
    temaSalvo
);


function atualizarBotoesTema() {

    const botoes =
        document.querySelectorAll("[data-theme-option]");

    botoes.forEach(botao => {

        const tema =
            botao.dataset.themeOption;

        botao.classList.toggle(
            "active",
            tema ===
            document.documentElement
                .getAttribute("data-theme")
        );

    });
}


function alterarTema(tema) {

    if (!temas.includes(tema)) {
        return;
    }

    document.documentElement.setAttribute(
        "data-theme",
        tema
    );

    localStorage.setItem(
        "mathrun-theme",
        tema
    );

    atualizarBotoesTema();
}


document.addEventListener("DOMContentLoaded", () => {

    atualizarBotoesTema();

    document
        .querySelectorAll("[data-theme-option]")
        .forEach(botao => {

            botao.addEventListener("click", () => {

                alterarTema(
                    botao.dataset.themeOption
                );

            });

        });

});