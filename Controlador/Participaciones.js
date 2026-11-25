// Consultas 11 y 14: uso exclusivo del administrador

const formularioCantidad = document.getElementById("FormCantidad");
const formularioParticipantes = document.getElementById("FormParticipantes");

const mensajeCantidad = document.getElementById("mensajeCantidad");
const mensajeParticipantes = document.getElementById("mensajeParticipantes");

const resultadoCantidad = document.getElementById("resultadoCantidad");
const resultadoParticipantes = document.getElementById("resultadoParticipantes");

const listaParticipantes = document.getElementById("listaParticipantes");


async function solicitarApi(filtros) {

    const respuesta = await fetch(
        "../Modelo/GestionarParticipaciones.php?" + filtros,
        { cache: "no-store" }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok || !resultado.success) {

        throw new Error(
            resultado.mensaje || "Error en la solicitud"
        );
    }

    return resultado;
}


function mostrarMensaje(elemento, texto, correcto) {

    elemento.textContent = texto;

    elemento.className = correcto
        ? "text-success mt-3"
        : "text-danger mt-3";
}


// Consulta 11: cantidad de actividades por asociado

formularioCantidad.addEventListener("submit", async (evento) => {

    evento.preventDefault();

    const documento = document.getElementById("documentoConsulta").value.trim();

    resultadoCantidad.classList.add("d-none");

    mensajeCantidad.textContent = "";

    try {

        const resultado = await solicitarApi(
            "filtro=cantidad&documento=" + encodeURIComponent(documento)
        );

        const asociado = resultado.asociado;

        document.getElementById("nombreConsulta").textContent =
            asociado.nombres + " " + asociado.apellidos;

        document.getElementById("documentoResultado").textContent =
            asociado.documento;

        document.getElementById("cantidadActividades").textContent =
            asociado.cantidad;

        resultadoCantidad.classList.remove("d-none");

    } catch (error) {

        mostrarMensaje(mensajeCantidad, error.message, false);
    }
});


// Consulta 14: participantes por actividad

formularioParticipantes.addEventListener("submit", async (evento) => {

    evento.preventDefault();

    const nombre = document.getElementById("nombreActividadConsulta").value.trim();

    mensajeParticipantes.textContent = "";

    resultadoParticipantes.classList.add("d-none");

    listaParticipantes.replaceChildren();

    try {

        const resultado = await solicitarApi(
            "filtro=participantes&nombre=" + encodeURIComponent(nombre)
        );

        const participantes = resultado.participantes;

        if (participantes.length === 0) {

            mostrarMensaje(
                mensajeParticipantes,
                "No se encontraron actividades con ese nombre.",
                false
            );

            return;
        }

        participantes.forEach((participante) => {

            const fila = document.createElement("tr");

            const actividad = document.createElement("td");

            actividad.textContent = participante.nombreActividad;

            const documento = document.createElement("td");

            documento.textContent = participante.documento ?? "-";

            const nombreAsociado = document.createElement("td");

            nombreAsociado.textContent =
                participante.documento === null
                    ? "Sin participantes"
                    : participante.nombres + " " + participante.apellidos;

            fila.append(
                actividad,
                documento,
                nombreAsociado
            );

            listaParticipantes.append(fila);
        });

        resultadoParticipantes.classList.remove("d-none");

    } catch (error) {

        mostrarMensaje(
            mensajeParticipantes,
            error.message,
            false
        );
    }
});