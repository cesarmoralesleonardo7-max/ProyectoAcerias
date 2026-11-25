const listaPagos = document.getElementById("listaPagos");
const formularioFiltro = document.getElementById("FormFiltro");
const filtro = document.getElementById("filtro");
const contenedorTotal = document.getElementById("contenedorTotal");
const contenedorTabla = document.getElementById("contenedorTabla");
const mensajePagos = document.getElementById("mensajePagos");

let pagos = [];


async function solicitarApi(filtros) {

    const respuesta = await fetch(
        "../Modelo/GestionarPagos.php?" + filtros,
        { cache: "no-store" }
    );

    const resultado = await respuesta.json();

    if (respuesta.status === 401) {
        window.location.href = "Login.html";
        return null;
    }

    if (!respuesta.ok || !resultado.success) {
        throw new Error(resultado.mensaje || "Error en la consulta");
    }

    return resultado;
}


// Formatear dinero

function formatoDinero(valor) {

    return Number(valor).toLocaleString("es-CO", {
        style: "currency",
        currency: "COP"
    });
}


// Consultar pagos

async function cargarPagos() {

    try {

        mensajePagos.textContent = "";

        const tipoFiltro = filtro.value;

        let filtros = "filtro=" + tipoFiltro;

        if (tipoFiltro === "total") {

            const documento = document.getElementById("filtroDocumento").value.trim();

            if (documento === "") {
                throw new Error("Ingresa el documento del asociado");
            }

            filtros += "&documento=" + encodeURIComponent(documento);
        }

        if (tipoFiltro === "prestamo") {

            const idPrestamo = document.getElementById("filtroPrestamo").value;

            if (idPrestamo === "" || Number(idPrestamo) < 1) {
                throw new Error("Ingresa un código de préstamo válido");
            }

            filtros += "&idPrestamo=" + encodeURIComponent(idPrestamo);
        }

        const resultado = await solicitarApi(filtros);

        if (!resultado) {
            return;
        }

        if (tipoFiltro === "total") {

            contenedorTabla.classList.add("d-none");
            contenedorTotal.classList.remove("d-none");

            document.getElementById("nombreTotal").textContent =
                resultado.total.nombres + " " + resultado.total.apellidos;

            document.getElementById("documentoTotal").textContent =
                resultado.total.documento;

            document.getElementById("totalPagado").textContent =
                formatoDinero(resultado.total.totalPagado);

        } else {

            contenedorTotal.classList.add("d-none");
            contenedorTabla.classList.remove("d-none");

            pagos = resultado.pagos;

            mostrarPagos();
        }

    } catch (error) {

        mensajePagos.textContent = error.message;
        mensajePagos.className = "alert alert-danger mb-3";
    }
}


// Mostrar historial de pagos

function mostrarPagos() {

    listaPagos.replaceChildren();

    if (pagos.length === 0) {

        const fila = document.createElement("tr");
        const celda = document.createElement("td");

        celda.colSpan = 7;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay pagos disponibles.";

        fila.append(celda);
        listaPagos.append(fila);

        return;
    }

    pagos.forEach((pago) => {

        const fila = document.createElement("tr");

        fila.append(
            crearCelda(pago.idPago),
            crearCelda(pago.idPrestamo),
            crearCelda(pago.documento),
            crearCelda(pago.nombres),
            crearCelda(pago.apellidos),
            crearCelda(pago.fechaPago),
            crearCelda(formatoDinero(pago.valorCuota))
        );

        listaPagos.append(fila);
    });
}


// Crear celdas de la tabla

function crearCelda(texto) {

    const celda = document.createElement("td");

    celda.textContent = texto ?? "-";

    return celda;
}


// Mostrar filtros

function mostrarFiltros() {

    document.getElementById("grupoDocumento").classList.add("d-none");
    document.getElementById("grupoPrestamo").classList.add("d-none");

    if (filtro.value === "total") {

        document.getElementById("grupoDocumento").classList.remove("d-none");
    }

    if (filtro.value === "prestamo") {

        document.getElementById("grupoPrestamo").classList.remove("d-none");
    }
}


// Configurar eventos

if (listaPagos) {

    filtro.addEventListener("change", mostrarFiltros);

    formularioFiltro.addEventListener("submit", (evento) => {

        evento.preventDefault();

        cargarPagos();
    });

    document.getElementById("btnLimpiar").addEventListener("click", () => {

        formularioFiltro.reset();

        mostrarFiltros();

        cargarPagos();
    });

    cargarPagos();
}