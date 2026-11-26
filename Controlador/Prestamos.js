const listaPrestamos = document.getElementById("listaPrestamos");
let esAdministrador = false;
let prestamos = [];

const formularioPrestamo = document.getElementById("FormPrestamo");
const formularioFiltro = document.getElementById("FormFiltro");
const formularioEdicion = document.getElementById("FormEditarPrestamo");

const filtro = document.getElementById("filtro");
const contenedorEdicion = document.getElementById("contenedorEdicion");
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

    let url = "../Modelo/gestionarPrestamos.php";

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


async function buscarAsociado() {

    const documento = document.getElementById("documento").value.trim();
    const mensaje = document.getElementById("mensaje");

    limpiarAsociado();

    if (documento === "") {

        mensaje.textContent = "Ingresa el documento del asociado";
        mensaje.className = "text-danger mt-3";

        return;
    }

    try {

        const resultado = await solicitarApi(
            "GET",
            null,
            "filtro=buscarAsociado&documento=" + encodeURIComponent(documento)
        );

        if (document.getElementById("documento").value.trim() !== documento) {
            return;
        }

        const asociado = resultado.asociado;

        document.getElementById("idAsociado").value = asociado.idAsociado;

        document.getElementById("nombreAsociado").value =
            asociado.nombres + " " + asociado.apellidos;

        document.getElementById("btnRegistrar").disabled = false;

        mensaje.textContent = "Asociado encontrado";
        mensaje.className = "text-success mt-3";

    } catch (error) {

        mensaje.textContent = error.message;
        mensaje.className = "text-danger mt-3";
    }
}


function limpiarAsociado() {

    document.getElementById("idAsociado").value = "";
    document.getElementById("nombreAsociado").value = "";
    document.getElementById("btnRegistrar").disabled = true;
}


if (formularioPrestamo) {

    document.getElementById("btnBuscar").addEventListener("click", buscarAsociado);

    document.getElementById("documento").addEventListener("input", limpiarAsociado);

    formularioPrestamo.addEventListener("reset", () => {

        limpiarAsociado();

        document.getElementById("mensaje").textContent = "";
    });

    formularioPrestamo.addEventListener("submit", async (evento) => {

        evento.preventDefault();

        const mensaje = document.getElementById("mensaje");
        const botonRegistrar = document.getElementById("btnRegistrar");

        const datos = {

            idAsociado: document.getElementById("idAsociado").value,
            valorPrestamo: document.getElementById("valorPrestamo").value,
            tasaInteres: document.getElementById("tasaInteres").value,
            numeroCuotas: document.getElementById("numeroCuotas").value,
            fechaPrestamo: document.getElementById("fechaPrestamo").value
        }

        if (datos.idAsociado === "") {

            mensaje.textContent = "Primero debes buscar un asociado";
            mensaje.className = "text-danger mt-3";

            return;
        }

        botonRegistrar.disabled = true;

        try {

            const resultado = await solicitarApi("POST", datos);

            formularioPrestamo.reset();

            limpiarAsociado();

            mensaje.textContent = resultado.mensaje;
            mensaje.className = "text-success mt-3";

        } catch (error) {

            mensaje.textContent = error.message;
            mensaje.className = "text-danger mt-3";

            if (document.getElementById("idAsociado").value !== "") {
                botonRegistrar.disabled = false;
            }
        }
    });
}


async function cargarPrestamos() {

    try {

        const tipoFiltro = filtro.value;

        let filtros = "filtro=" + tipoFiltro;

        if (tipoFiltro === "fechas") {

            const fechaInicio = document.getElementById("fechaInicio").value;
            const fechaFin = document.getElementById("fechaFin").value;

            filtros = filtros + "&fechaInicio=" + fechaInicio +
                "&fechaFin=" + fechaFin;
        }

        if (tipoFiltro === "documento") {

            const documento = document.getElementById("filtroDocumento").value.trim();

            filtros = filtros + "&documento=" + encodeURIComponent(documento);
        }

        const resultado = await solicitarApi("GET", null, filtros);

        esAdministrador = resultado.rol === "admin";

        contenedorEdicion.classList.add("d-none");

        if (tipoFiltro === "cantidad") {

            contenedorTabla.classList.add("d-none");
            contenedorCantidad.classList.remove("d-none");

            document.getElementById("cantidadPrestamos").textContent =
                resultado.cantidad;

        } else {

            contenedorCantidad.classList.add("d-none");
            contenedorTabla.classList.remove("d-none");

            prestamos = resultado.prestamos;

            mostrarPrestamos();
        }

    } catch (error) {

        alert("Error al cargar los préstamos: " + error.message);
    }
}


function mostrarPrestamos() {

    listaPrestamos.replaceChildren();

    if (prestamos.length === 0) {

        const fila = document.createElement("tr");
        const celda = document.createElement("td");

        celda.colSpan = 9;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay préstamos disponibles.";

        fila.append(celda);
        listaPrestamos.append(fila);

        return;
    }

    prestamos.forEach((prestamo) => {

        const fila = document.createElement("tr");

        fila.append(
            crearCelda(prestamo.idPrestamo),
            crearCelda(prestamo.documento),
            crearCelda(prestamo.nombres),
            crearCelda(prestamo.apellidos),
            crearCelda(prestamo.fechaPrestamo),
            crearCelda(prestamo.valorPrestamo),
            crearCelda(prestamo.tasaInteres),
            crearCelda(prestamo.numeroCuotas)
        );

        const acciones = document.createElement("td");

        if (esAdministrador) {

            acciones.className = "text-nowrap";

            const botonEditar = document.createElement("button");

            botonEditar.type = "button";
            botonEditar.className = "btn btn-sm btn-outline-primary me-2";
            botonEditar.innerHTML = '<i class="bi bi-pencil-square"></i>';

            botonEditar.addEventListener("click", () =>
                abrirEdicion(prestamo)
            );

            const botonEliminar = document.createElement("button");

            botonEliminar.type = "button";
            botonEliminar.className = "btn btn-sm btn-outline-danger";
            botonEliminar.innerHTML = '<i class="bi bi-trash"></i>';

            botonEliminar.addEventListener("click", () =>
                eliminarPrestamo(prestamo)
            );

            acciones.append(botonEditar, botonEliminar);
        }

        fila.append(acciones);
        listaPrestamos.append(fila);
    });
}


function crearCelda(texto) {

    const celda = document.createElement("td");

    celda.textContent = texto;

    return celda;
}


function mostrarFiltros() {

    document.getElementById("grupoFechaInicio").classList.add("d-none");
    document.getElementById("grupoFechaFin").classList.add("d-none");
    document.getElementById("grupoDocumento").classList.add("d-none");

    if (filtro.value === "fechas") {

        document.getElementById("grupoFechaInicio").classList.remove("d-none");
        document.getElementById("grupoFechaFin").classList.remove("d-none");
    }

    if (filtro.value === "documento") {

        document.getElementById("grupoDocumento").classList.remove("d-none");
    }
}


function abrirEdicion(prestamo) {

    contenedorEdicion.classList.remove("d-none");

    document.getElementById("idPrestamo").value = prestamo.idPrestamo;

    document.getElementById("nombreAsociado").value =
        prestamo.nombres + " " + prestamo.apellidos;

    document.getElementById("fechaPrestamo").value = prestamo.fechaPrestamo;
    document.getElementById("valorPrestamo").value = prestamo.valorPrestamo;
    document.getElementById("tasaInteres").value = prestamo.tasaInteres;
    document.getElementById("numeroCuotas").value = prestamo.numeroCuotas;
}


async function eliminarPrestamo(prestamo) {

    const confirmar = window.confirm(
        "¿Desea eliminar el préstamo " + prestamo.idPrestamo + "?"
    );

    if (!confirmar) {
        return;
    }

    try {

        await solicitarApi("DELETE", {
            idPrestamo: prestamo.idPrestamo
        });

        contenedorEdicion.classList.add("d-none");

        await cargarPrestamos();

        alert("Préstamo eliminado correctamente");

    } catch (error) {

        alert(error.message);
    }
}


if (listaPrestamos) {

    filtro.addEventListener("change", mostrarFiltros);

    formularioFiltro.addEventListener("submit", (evento) => {

        evento.preventDefault();

        cargarPrestamos();
    });

    document.getElementById("btnLimpiar").addEventListener("click", () => {

        formularioFiltro.reset();

        mostrarFiltros();

        cargarPrestamos();
    });

    document.getElementById("btnCancelar").addEventListener("click", () => {

        contenedorEdicion.classList.add("d-none");
    });

    formularioEdicion.addEventListener("submit", async (evento) => {

        evento.preventDefault();

        const datos = {

            idPrestamo: document.getElementById("idPrestamo").value,
            fechaPrestamo: document.getElementById("fechaPrestamo").value,
            valorPrestamo: document.getElementById("valorPrestamo").value,
            tasaInteres: document.getElementById("tasaInteres").value,
            numeroCuotas: document.getElementById("numeroCuotas").value
        }

        try {

            await solicitarApi("PUT", datos);

            alert("Préstamo actualizado correctamente");

            contenedorEdicion.classList.add("d-none");

            cargarPrestamos();

        } catch (error) {

            alert(error.message);
        }
    });

    cargarPrestamos();
}