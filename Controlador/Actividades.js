const listaActividades = document.getElementById("listaActividades");
let esAdministrador = false;
let actividades = [];
let accionesConfiguradas = false;

const formularioActividad = document.getElementById("FormActividad");
const formularioFiltro = document.getElementById("FormFiltro");

const filtro = document.getElementById("filtro");
const contenedorCantidad = document.getElementById("contenedorCantidad");
const contenedorTabla = document.getElementById("contenedorTabla");


async function solicitarApi(metodo, datos, filtros) {

    const opciones = {
        method: metodo,
        headers: { 'Content-Type': 'application/json' },
    }

    if (datos) {
        opciones.body = JSON.stringify(datos);
    }

    let url = "../Modelo/gestionarActividades.php";

    if (filtros) {
        url = url + "?" + filtros;
    }

    const respuesta = await fetch(url, opciones);
    const resultado = await respuesta.json();

    if (respuesta.status === 401) {
        window.location.href = "Login.html";
    }

    if (!respuesta.ok || !resultado.success) {
        throw new Error(resultado.mensaje || "Error en la solicitud");
    }

    return resultado;
}


async function cargarActividades() {

    try {

        const tipoFiltro = filtro.value;

        let filtros = "filtro=" + tipoFiltro;

        if (tipoFiltro === "cantidad") {

            const anio = document.getElementById("anio").value;

            filtros = filtros + "&anio=" + anio;
        }

        if (tipoFiltro === "municipio") {

            const municipio = document.getElementById("filtroMunicipio").value;

            filtros = filtros + "&municipio=" + encodeURIComponent(municipio);
        }

        const resultado = await solicitarApi("GET", null, filtros);

        esAdministrador = resultado.rol === "admin";

        configurarAcciones();

        if (tipoFiltro === "cantidad") {

            contenedorTabla.classList.add("d-none");
            contenedorCantidad.classList.remove("d-none");

            document.getElementById("cantidadActividades").textContent =
                resultado.cantidad;

        } else {

            contenedorCantidad.classList.add("d-none");
            contenedorTabla.classList.remove("d-none");

            actividades = resultado.actividades;

            mostrarActividades();
        }

    } catch (error) {

        alert("Error al cargar las actividades: " + error.message);
    }
}


function mostrarActividades() {

    listaActividades.replaceChildren();

    if (actividades.length === 0) {

        const fila = document.createElement("tr");
        const celda = document.createElement("td");

        celda.colSpan = esAdministrador ? 5 : 4;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay actividades disponibles.";

        fila.append(celda);
        listaActividades.append(fila);

        return;
    }

    actividades.forEach((actividad) => {

        const fila = document.createElement("tr");

        fila.append(
            crearCelda(actividad.nombreActividad),
            crearCelda(actividad.fechaEvento),
            crearCelda(actividad.municipio),
            crearCelda(
                actividad.totalRecaudado === null ? "Pendiente"
                    : Number(actividad.totalRecaudado).toLocaleString("es-CO", {
                        style: "currency",
                        currency: "COP",
                    })
            )
        );

        if (esAdministrador) {

            const acciones = document.createElement("td");

            acciones.className = "text-nowrap";

            const botonEditar = document.createElement("button");

            botonEditar.type = "button";
            botonEditar.className = "btn btn-sm btn-outline-primary me-2";
            botonEditar.innerHTML = '<i class="bi bi-pencil-square"></i>';

            botonEditar.addEventListener("click", () =>
                abrirEdicion(actividad)
            );

            const botonEliminar = document.createElement("button");

            botonEliminar.type = "button";
            botonEliminar.className = "btn btn-sm btn-outline-danger";
            botonEliminar.innerHTML = '<i class="bi bi-trash"></i>';

            botonEliminar.addEventListener("click", () =>
                eliminarActividad(actividad)
            );

            acciones.append(botonEditar, botonEliminar);
            fila.append(acciones);
        }

        listaActividades.append(fila);
    });
}


function crearCelda(texto) {

    const celda = document.createElement("td");

    celda.textContent = texto;

    return celda;
}


function mostrarFiltros() {

    document.getElementById("grupoAnio").classList.add("d-none");
    document.getElementById("grupoMunicipio").classList.add("d-none");

    if (filtro.value === "cantidad") {

        document.getElementById("grupoAnio").classList.remove("d-none");
    }

    if (filtro.value === "municipio") {

        document.getElementById("grupoMunicipio").classList.remove("d-none");
    }
}


async function eliminarActividad(actividad) {

    const confirmar = window.confirm(
        `¿Desea eliminar la actividad "${actividad.nombreActividad}"?`
    );

    if (!confirmar) {
        return;
    }

    try {

        await solicitarApi("DELETE", {
            idActividad: actividad.idActividad
        });

        await cargarActividades();

        alert("Actividad eliminada correctamente");

    } catch (error) {

        alert(error.message);
    }
}


function configurarAcciones() {

    if (accionesConfiguradas) {
        return;
    }

    const botonNueva = document.getElementById("btnNuevaActividad");
    const encabezadoAcciones = document.getElementById("encabezadoAcciones");

    if (esAdministrador) {

        if (botonNueva) {
            botonNueva.classList.remove("d-none");
        }

        if (encabezadoAcciones) {
            encabezadoAcciones.classList.remove("d-none");
        }

        accionesConfiguradas = true;
    }
}


function abrirEdicion(actividad) {

    sessionStorage.setItem("actividadEditar", JSON.stringify(actividad));

    window.location.href = "RegistroActividades.html";
}


if (formularioActividad) {

    const actividad = JSON.parse(
        sessionStorage.getItem("actividadEditar")
    );

    sessionStorage.removeItem("actividadEditar");

    if (actividad) {

        document.getElementById("idActividad").value = actividad.idActividad;
        document.getElementById("nombreActividad").value = actividad.nombreActividad;
        document.getElementById("fechaEvento").value = actividad.fechaEvento;
        document.getElementById("municipio").value = actividad.municipio;
        document.getElementById("totalRecaudado").value = actividad.totalRecaudado ?? "";

        document.getElementById("grupoRecaudo").classList.remove("d-none");
    }

    formularioActividad.addEventListener("submit", async (evento) => {

        evento.preventDefault();

        const idActividad = document.getElementById("idActividad").value;

        const datos = {

            nombreActividad: document.getElementById("nombreActividad").value.trim(),
            fechaEvento: document.getElementById("fechaEvento").value,
            municipio: document.getElementById("municipio").value.trim()
        }

        let metodo = "POST";

        if (idActividad) {

            metodo = "PUT";

            datos.idActividad = Number(idActividad);

            const total = document.getElementById("totalRecaudado").value;

            datos.totalRecaudado = total === "" ? null : Number(total);
        }

        try {

            const resultado = await solicitarApi(metodo, datos);

            alert(resultado.mensaje);

            window.location.href = "ListarActividades.html";

        } catch (error) {

            alert(error.message);
        }
    });
}


if (listaActividades) {

    filtro.addEventListener("change", mostrarFiltros);

    formularioFiltro.addEventListener("submit", (evento) => {

        evento.preventDefault();

        cargarActividades();
    });

    document.getElementById("btnLimpiar").addEventListener("click", () => {

        formularioFiltro.reset();

        mostrarFiltros();

        cargarActividades();
    });

    cargarActividades();
}