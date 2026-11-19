
const listaActividades = document.getElementById("listaActividades"); //su valor permance unico
let esAdministrador = false; //su valor puede cambiar
let actividades = [];
let accionesConfiguradas = false;
const formularioActividad = document.getElementById("FormActividad");


async function solicitarApi(metodo, datos) {
    const opciones = {
        method: metodo,
        headers: { 'Content-Type': 'application/json' },
    }

    if (datos) {
        opciones.body = JSON.stringify(datos);
    }

    const respuesta = await fetch("../Modelo/gestionarActividades.php", opciones);
    const resultado = await respuesta.json();

    if (respuesta.status === 401) {
        window.location.href = "login.html";
    }

    if (!respuesta.ok || !resultado.success) {
        throw new Error(resultado.mensaje || "Error en la solicitud");
    }

    return resultado;
}


async function cargarActividades() {
    try {
        const resultado = await solicitarApi("GET");
        esAdministrador = resultado.rol === "admin";

        configurarAcciones();

        actividades = resultado.actividades;
        mostrarActividades();

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
                        style: "currency", currency: "COP",
                    }),
            ),
        );

        if (esAdministrador) {
            const acciones = document.createElement("td");
            acciones.className = "text-nowrap";

            const botonEditar = document.createElement("button");
            botonEditar.type = "button";
            botonEditar.className = "btn btn-sm btn-outline-primary me-2";
            botonEditar.innerHTML = '<i class="bi bi-pencil-square"></i>';
            botonEditar.addEventListener("click", () => abrirEdicion(actividad));

            const botonEliminar = document.createElement("button");
            botonEliminar.type = "button";
            botonEliminar.className = "btn btn-sm btn-outline-danger me-2";
            botonEliminar.innerHTML = '<i class="bi bi-trash"></i>';
            botonEliminar.addEventListener("click", () =>
                eliminarActividad(actividad),
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


async function eliminarActividad(actividad) {
    const confirmar = window.confirm(
        `¿Desea eliminar la actividad "${actividad.nombreActividad}"?`,
    );

    if (!confirmar) {
        return;
    }

    try {
        await solicitarApi("DELETE", { idActividad: actividad.idActividad });
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
        return;
    }
}


function abrirEdicion(actividad) {
    sessionStorage.setItem("actividadEditar", JSON.stringify(actividad));
    window.location.href = "RegistroActividades.html";
}


if (listaActividades) {
    cargarActividades();
}
