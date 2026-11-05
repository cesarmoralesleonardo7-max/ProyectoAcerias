const formulario = document.getElementById("FormRegistro");

 formulario.addEventListener("submit", async function (event) {

    event.preventDefault(); /*evita q se recargue la pagina al enviar el formulario*/

    const botonGuardar = formulario.querySelector('button[type="submit"]');

    //Lee los campos del formularios y los convierte en un objeto 
    const asociado = Object.fromEntries(new FormData(formulario).entries());
    botonGuardar.disabled = true; // Desahabilitar el boton mientras se procesa la solicitud 

    try{
        const respuesta = await fetch(formulario.action, {method: 'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(asociado)});
        
        // pasar la respuesta JSON a un objeto de JavaScript
        const resultado = await respuesta.json();
        document.getElementById("mensajeRegistro").textContent = "Guardando...";

        document.getElementById("mensajeRegistro").dataset.tipo=''; 

        document.getElementById("mensajeRegistro").textContent = resultado.mensaje;

        if (respuesta.ok && resultado.success) {
            formulario.reset(); //Limpiar el formulario si la solicitud fue exitosa
        }
    }catch(error){
        console.error("Error al enviar el formulario:",error);
        document.getElementById("mensajeRegistro").textContent = 
        "Ocurrió un error al enviar el formulario. Por favor,inténtelo de nuevo más tarde.";
    } finally {
        botonGuardar.disabled = false; // Habilitar el botón nuevamente después de procesar la solicitud
    }


});