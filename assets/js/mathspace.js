
/* =========================================================
   MATHSPACE 2.0 — INTERAÇÃO DO HOLOGRAMA
   Adicione ao FINAL de mathspace.js, fora do callback
   DOMContentLoaded existente.
   ========================================================= */

document.addEventListener("DOMContentLoaded", () => {
    const map = document.getElementById("mapViewport");
    const panel = document.querySelector(".right-console");
    const details = document.getElementById("missionDetails");
    const emptyState = document.getElementById("missionEmpty");
    const destinations = [...document.querySelectorAll("[data-destination]")];
    const launchButton = document.getElementById("launchButton");

    if (!map || !panel || !details || !destinations.length) {
        return;
    }

    /*
     * Cria controles de holograma sem alterar o HTML PHP.
     */
    const holoControls = document.createElement("div");
    holoControls.className = "holo-controls";
    holoControls.innerHTML = `
        <span class="holo-coordinates">NAVEGAÇÃO HOLOGRÁFICA</span>
        <button type="button" class="holo-close" aria-label="Fechar holograma">
            ×
        </button>
    `;

    panel.prepend(holoControls);

    const closeButton = holoControls.querySelector(".holo-close");

    const style = document.createElement("style");
    style.textContent = `
        .holo-controls {
            position: relative;
            z-index: 20;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 10px;
        }

        .holo-coordinates {
            color: #8cecff;
            font: 500 8px Orbitron, sans-serif;
            letter-spacing: .12em;
        }

        .holo-close {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border: 1px solid #8cecff66;
            color: #dffaff;
            background: #08152bd9;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            transition: background .2s, box-shadow .2s;
        }

        .holo-close:hover {
            background: #1c3760;
            box-shadow: 0 0 16px #8cecff33;
        }

        .right-console.holo-hidden .mission-details,
        .right-console.holo-hidden .mission-empty-state {
            display: none !important;
        }

        .right-console.holo-hidden {
            min-height: 0;
            border-color: #8cecff40;
        }

        .right-console.holo-hidden .console-heading {
            margin-bottom: 0;
        }

        @media (max-width: 1000px) {
            .holo-controls {
                margin-bottom: 12px;
            }
        }
    `;
    document.head.appendChild(style);

    /*
     * A seleção é controlada pelo script principal.
     * Aqui apenas melhoramos a apresentação e o fechamento.
     */
    function openHologram(destination) {
        panel.classList.remove("holo-hidden");

        if (emptyState) {
            emptyState.hidden = true;
        }

        details.hidden = false;

        destinations.forEach((item) => {
            const selected = item === destination;
            item.classList.toggle("selected", selected);
            item.setAttribute("aria-pressed", String(selected));
        });

        panel.dataset.selectedDestination = destination.dataset.id || "";

        if (window.matchMedia("(max-width: 1000px)").matches) {
            panel.scrollIntoView({
                behavior: "smooth",
                block: "nearest"
            });
        }
    }

    function closeHologram() {
        panel.classList.add("holo-hidden");

        destinations.forEach((item) => {
            item.classList.remove("selected");
            item.setAttribute("aria-pressed", "false");
        });

        panel.dataset.selectedDestination = "";
    }

    destinations.forEach((destination) => {
        destination.addEventListener("click", () => {
            openHologram(destination);
        });
    });

    closeButton.addEventListener("click", closeHologram);

    /*
     * Segurança de navegação: fase bloqueada não deve abrir.
     */
    launchButton?.addEventListener("click", (event) => {
        if (launchButton.getAttribute("aria-disabled") === "true") {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    /*
     * Escape fecha a projeção, como em interfaces de jogos.
     */
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            closeHologram();
        }
    });

    /*
     * Estado inicial: o holograma começa fechado.
     * A pessoa escolhe um destino no mapa.
     */
    panel.classList.add("holo-hidden");

    if (emptyState) {
        emptyState.hidden = true;
    }

    details.hidden = true;
});