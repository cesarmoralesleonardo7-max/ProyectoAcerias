const listaAsociados = document.getElementById("listaAsociados");
let esAdministrador = false;
let asociados = [];

const formularioFiltro = document.getElementById("FormFiltro");
const formularioEdicion = document.getElementById("FormEditarAsociado");

const filtro = document.getElementById("filtro");
const grupoGenero = document.getElementById("grupoGenero");
const grupoCiudad = document.getElementById("grupoCiudad");
const contenedorEdicion = document.getElementById("contenedorEdicion");


async function solicitarApi(metodo, datos) {

    const opciones = {
        method: metodo,
        headers: { 'Content-Type': 'application/json' },
    }

    if (datos) {
        opciones.body = JSON.stringify(datos);
    }

    let url = "../Modelo/gestionarAsociados.php";

    if (metodo === "GET") {

        let valor = "";

        if (filtro.value === "genero") {
            valor = document.getElementById("filtroGenero").value;
        }

        if (filtro.value === "ciudad") {
            valor = document.getElementById("filtroCiudad").value;
        }

        url = url + "?filtro=" + filtro.value + "&valor=" + valor;
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


async function cargarAsociados() {

    try {
        const resultado = await solicitarApi("GET");

        esAdministrador = resultado.rol === "admin";

        asociados = resultado.asociados;

        mostrarAsociados();

    } catch (error) {
        alert("Error al cargar los asociados: " + error.message);
    }
}


function mostrarAsociados() {

    listaAsociados.replaceChildren();

    if (asociados.length === 0) {

        const fila = document.createElement("tr");
        const celda = document.createElement("td");

        celda.colSpan = 10;
        celda.className = "text-center py-4 text-muted";
        celda.textContent = "No hay asociados disponibles.";

        fila.append(celda);
        listaAsociados.append(fila);

        return;
    }

    asociados.forEach((asociado) => {

        const fila = document.createElement("tr");

        fila.append(
            crearCelda(asociado.documento),
            crearCelda(asociado.nombres),
            crearCelda(asociado.apellidos),
            crearCelda(asociado.fechaNacimiento),
            crearCelda(asociado.genero),
            crearCelda(asociado.direccionResidencia),
            crearCelda(asociado.ciudad),
            crearCelda(asociado.telefono),
            crearCelda(asociado.email)
        );

        if (esAdministrador) {

            const acciones = document.createElement("td");
            acciones.className = "text-nowrap";

            const botonEditar = document.createElement("button");

            botonEditar.type = "button";
            botonEditar.className = "btn btn-sm btn-outline-primary me-2";
            botonEditar.innerHTML = '<i class="bi bi-pencil-square"></i>';

            botonEditar.addEventListener("click", () =>
                abrirEdicion(asociado)
            );

            const botonEliminar = document.createElement("button");

            botonEliminar.type = "button";
            botonEliminar.className = "btn btn-sm btn-outline-danger";
            botonEliminar.innerHTML = '<i class="bi bi-trash"></i>';

            botonEliminar.addEventListener("click", () =>
                eliminarAsociado(asociado)
            );

            acciones.append(botonEditar, botonEliminar);
            fila.append(acciones);
        }

        listaAsociados.append(fila);
    });
}


function crearCelda(texto) {

    const celda = document.createElement("td");
    celda.textContent = texto;

    return celda;
}


function mostrarFiltros() {

    grupoGenero.classList.add("d-none");
    grupoCiudad.classList.add("d-none");

    if (filtro.value === "genero") {
        grupoGenero.classList.remove("d-none");
    }

    if (filtro.value === "ciudad") {
        grupoCiudad.classList.remove("d-none");
    }
}


filtro.addEventListener("change", mostrarFiltros);


formularioFiltro.addEventListener("submit", (evento) => {

    evento.preventDefault();

    cargarAsociados();
});


document.getElementById("btnLimpiar").addEventListener("click", () => {

    filtro.value = "todos";

    mostrarFiltros();
    cargarAsociados();
});


function abrirEdicion(asociado) {

    contenedorEdicion.classList.remove("d-none");

    document.getElementById("documento").value = asociado.documento;
    document.getElementById("nombres").value = asociado.nombres;
    document.getElementById("apellidos").value = asociado.apellidos;
    document.getElementById("fechaNacimiento").value = asociado.fechaNacimiento;
    document.getElementById("genero").value = asociado.genero;
    document.getElementById("direccionResidencia").value = asociado.direccionResidencia;
    document.getElementById("ciudad").value = asociado.ciudad;
    document.getElementById("telefono").value = asociado.telefono;
    document.getElementById("email").value = asociado.email;
}


formularioEdicion.addEventListener("submit", async (evento) => {

    evento.preventDefault();

    const datos = {

        documento: document.getElementById("documento").value,
        nombres: document.getElementById("nombres").value.trim(),
        apellidos: document.getElementById("apellidos").value.trim(),
        fechaNacimiento: document.getElementById("fechaNacimiento").value,
        genero: document.getElementById("genero").value,
        direccionResidencia: document.getElementById("direccionResidencia").value.trim(),
        ciudad: document.getElementById("ciudad").value,
        telefono: document.getElementById("telefono").value.trim(),
        email: document.getElementById("email").value.trim()
    }

    try {

        await solicitarApi("PUT", datos);

        alert("Asociado actualizado correctamente");

        contenedorEdicion.classList.add("d-none");

        cargarAsociados();

    } catch (error) {
        alert(error.message);
    }
});


document.getElementById("btnCancelar").addEventListener("click", () => {

    contenedorEdicion.classList.add("d-none");
});


async function eliminarAsociado(asociado) {

    const confirmar = window.confirm(
        `¿Desea eliminar al asociado "${asociado.nombres} ${asociado.apellidos}"?`
    );

    if (!confirmar) {
        return;
    }

    try {

        await solicitarApi("DELETE", {
            documento: asociado.documento
        });

        contenedorEdicion.classList.add("d-none");

        await cargarAsociados();

        alert("Asociado eliminado correctamente");

    } catch (error) {
        alert(error.message);
    }
}


if (listaAsociados) {
    cargarAsociados();
}