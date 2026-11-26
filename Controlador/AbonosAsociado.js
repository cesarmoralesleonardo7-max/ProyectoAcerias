const formularioAbono = document.getElementById("FormAbono");
const seleccionarPrestamo = document.getElementById("prestamoAbono");
const mensajeAbono = document.getElementById("mensajeAbono");
const botonAbono = document.getElementById("btnAbonar");

let tokenAbono = "";


async function cargarFormularioAbono() {

    try {

        const [respuestaCuenta, respuestaToken] = await Promise.all([
            fetch("../Modelo/consultarMiCuenta.php", { cache: "no-store" }),
            fetch("../Modelo/RegistrarAbonoAsociado.php", { cache: "no-store" })
        ]);

        const cuenta = await respuestaCuenta.json();
        const seguridad = await respuestaToken.json();

        if (!respuestaCuenta.ok || !cuenta.success) {

            throw new Error(
                cuenta.mensaje || "No se pudieron consultar los préstamos"
            );
        }

        if (!respuestaToken.ok || !seguridad.success) {

            throw new Error(
                seguridad.mensaje || "No se pudo preparar el formulario"
            );
        }

        tokenAbono = seguridad.token;

        seleccionarPrestamo.replaceChildren();

        const primeraOpcion = document.createElement("option");

        primeraOpcion.value = "";
        primeraOpcion.textContent = "Selecciona un préstamo";

        seleccionarPrestamo.append(primeraOpcion);

        cuenta.prestamos.forEach((prestamo) => {

            const opcion = document.createElement("option");

            opcion.value = prestamo.idPrestamo;

            opcion.textContent = "Préstamo " + prestamo.idPrestamo;

            seleccionarPrestamo.append(opcion);
        });

        botonAbono.disabled = cuenta.prestamos.length === 0;

        if (cuenta.prestamos.length === 0) {

            mensajeAbono.textContent =
                "No tienes préstamos registrados para realizar pagos.";

            mensajeAbono.className = "text-muted mt-3";
        }

    } catch (error) {

        botonAbono.disabled = true;

        mensajeAbono.textContent = error.message;
        mensajeAbono.className = "text-danger mt-3";
    }
}


if (formularioAbono) {

    formularioAbono.addEventListener("submit", async (evento) => {

        evento.preventDefault();

        const idPrestamo = seleccionarPrestamo.value;

        const valorCuota = document.getElementById("valorAbono").value;

        if (!idPrestamo || Number(valorCuota) <= 0) {

            mensajeAbono.textContent =
                "Selecciona un préstamo y escribe un valor válido.";

            mensajeAbono.className = "text-danger mt-3";

            return;
        }

        botonAbono.disabled = true;

        try {

            const respuesta = await fetch(
                "../Modelo/RegistrarAbonoAsociado.php",
                {
                    method: "POST",

                    headers: {
                        "Content-Type": "application/json"
                    },

                    body: JSON.stringify({
                        idPrestamo: Number(idPrestamo),
                        valorCuota: valorCuota,
                        token: tokenAbono
                    })
                }
            );

            const resultado = await respuesta.json();

            if (!respuesta.ok || !resultado.success) {

                throw new Error(
                    resultado.mensaje || "No se pudo registrar el pago"
                );
            }

            formularioAbono.reset();

            mensajeAbono.textContent = resultado.mensaje;
            mensajeAbono.className = "text-success mt-3";

            // Actualizar el historial sin recargar la página

            if (typeof cargarMiCuenta === "function") {

                await cargarMiCuenta();
            }

        } catch (error) {

            mensajeAbono.textContent = error.message;
            mensajeAbono.className = "text-danger mt-3";

        } finally {

            botonAbono.disabled = false;
        }
    });

    cargarFormularioAbono();
}