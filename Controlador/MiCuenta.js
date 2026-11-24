async function cargarMiCuenta() {

    try {

        const respuesta = await fetch(
            "../Modelo/consultarMiCuenta.php",
            { cache: "no-store" }
        );

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.success) {
            throw new Error(resultado.mensaje || "Error al cargar la información");
        }

        // Mostrar información de Mi Perfil

        if (document.getElementById("perfilNombre")) {
            mostrarPerfil(resultado.asociado);
        }

        // Mostrar información de Mis Préstamos

        if (document.getElementById("tablaPrestamos")) {
            mostrarPrestamos(resultado.prestamos);
        }

        // Mostrar información de Mis Pagos

        if (document.getElementById("tablaPagos")) {
            mostrarPagos(resultado.pagos);
        }

        // Mostrar información de Mis Actividades

        if (document.getElementById("tablaActividades")) {
            mostrarActividades(resultado.actividades);
        }

    } catch (error) {

        const mensaje = document.getElementById("mensaje");

        mensaje.textContent = error.message;
        mensaje.className = "alert alert-danger mb-3";
    }
}


// Mostrar datos personales

function mostrarPerfil(asociado) {

    document.getElementById("perfilNombre").textContent =
        asociado.nombres + " " + asociado.apellidos;

    document.getElementById("perfilDocumento").textContent =
        asociado.documento ?? "-";

    document.getElementById("perfilNacimiento").textContent =
        asociado.fechaNacimiento ?? "-";

    document.getElementById("perfilGenero").textContent =
        asociado.genero ?? "-";

    document.getElementById("perfilDireccion").textContent =
        asociado.direccionResidencia ?? "-";

    document.getElementById("perfilCiudad").textContent =
        asociado.ciudad ?? "-";

    document.getElementById("perfilTelefono").textContent =
        asociado.telefono ?? "-";

    document.getElementById("perfilEmail").textContent =
        asociado.email ?? "-";
}


// Formatear valores en pesos colombianos

function formatoDinero(valor) {

    if (valor === null || valor === undefined) {
        return "-";
    }

    return Number(valor).toLocaleString("es-CO", {
        style: "currency",
        currency: "COP"
    });
}


// Mostrar los préstamos del asociado

function mostrarPrestamos(prestamos) {

    mostrarTabla(
        "tablaPrestamos",
        prestamos,
        5,
        (prestamo) => [
            prestamo.idPrestamo,
            prestamo.fechaPrestamo,
            formatoDinero(prestamo.valorPrestamo),
            prestamo.tasaInteres,
            prestamo.numeroCuotas
        ]
    );
}


// Mostrar los pagos del asociado

function mostrarPagos(pagos) {

    mostrarTabla(
        "tablaPagos",
        pagos,
        4,
        (pago) => [
            pago.idPago,
            pago.idPrestamo,
            pago.fechaPago,
            formatoDinero(pago.valorCuota)
        ]
    );
}



// Mostrar todas las actividades e inscribir al asociado

function mostrarActividades(actividades) {

    const tabla = document.getElementById("tablaActividades");

    tabla.replaceChildren();

    const hoy = new Intl.DateTimeFormat("sv-SE", {
        timeZone: "America/Bogota",
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    }).format(new Date());

    if (actividades.length === 0) {

        const fila = document.createElement("tr");
        const celda = document.createElement("td");

        celda.colSpan = 5;
        celda.className = "text-center text-muted py-4";
        celda.textContent = "No hay actividades disponibles.";

        fila.append(celda);
        tabla.append(fila);

        return;
    }

    actividades.forEach((actividad) => {

        const fila = document.createElement("tr");

        const inscrito = Number(actividad.participa) === 1;
        const finalizada = actividad.fechaEvento < hoy;

        const valores = [
            actividad.nombreActividad,
            actividad.fechaEvento,
            actividad.municipio,
            inscrito ? "Inscrito" : "Sin inscripción"
        ];

        valores.forEach((valor) => {

            const celda = document.createElement("td");

            celda.textContent = valor;

            fila.append(celda);
        });

        const accion = document.createElement("td");

        const boton = document.createElement("button");

        boton.type = "button";
        boton.className = "btn btn-sm btn-club";

        if (inscrito) {

            boton.textContent = "Inscrito";
            boton.disabled = true;

        } else if (finalizada) {

            boton.textContent = "Finalizada";
            boton.disabled = true;

        } else {

            boton.textContent = "Participar";

            boton.addEventListener("click", () => {

                inscribirmeEnActividad(actividad.idActividad, boton);

            });
        }

        accion.append(boton);
        fila.append(accion);
        tabla.append(fila);
    });
}


// Registrar la inscripción mediante fetch

async function inscribirmeEnActividad(idActividad, boton) {

    const mensaje = document.getElementById("mensaje");

    if (!window.confirm("¿Deseas inscribirte en esta actividad?")) {
        return;
    }

    boton.disabled = true;

    try {

        // Obtener el token de seguridad de la sesión

        const respuestaToken = await fetch(
            "../Modelo/GestionarParticipaciones.php?filtro=token",
            { cache: "no-store" }
        );

        const seguridad = await respuestaToken.json();

        if (!respuestaToken.ok || !seguridad.success) {

            throw new Error(
                seguridad.mensaje || "No se pudo preparar la inscripción"
            );
        }

        // Enviar la actividad a PHP

        const respuesta = await fetch(
            "../Modelo/GestionarParticipaciones.php",
            {
                method: "POST",

                headers: {
                    "Content-Type": "application/json"
                },

                body: JSON.stringify({
                    idActividad: idActividad,
                    token: seguridad.token
                })
            }
        );

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.success) {

            throw new Error(
                resultado.mensaje || "No se pudo registrar la inscripción"
            );
        }

        mensaje.textContent = resultado.mensaje;
        mensaje.className = "alert alert-success mb-3";

        // Volver a consultar para mostrar el estado Inscrito

        await cargarMiCuenta();

    } catch (error) {

        mensaje.textContent = error.message;
        mensaje.className = "alert alert-danger mb-3";

        boton.disabled = false;
    }
}




// Crear las filas de las tablas

function mostrarTabla(idTabla, registros, cantidadColumnas, obtenerDatos) {

    const tabla = document.getElementById(idTabla);

    tabla.replaceChildren();

    if (registros.length === 0) {

        const fila = document.createElement("tr");
        const celda = document.createElement("td");

        celda.colSpan = cantidadColumnas;
        celda.className = "text-center text-muted py-4";
        celda.textContent = "No hay registros disponibles.";

        fila.append(celda);
        tabla.append(fila);

        return;
    }

    registros.forEach((registro) => {

        const fila = document.createElement("tr");

        const datos = obtenerDatos(registro);

        datos.forEach((dato) => {

            const celda = document.createElement("td");

            celda.textContent = dato ?? "-";

            fila.append(celda);
        });

        tabla.append(fila);
    });
}


// Cargar los datos al abrir la página

cargarMiCuenta();