const usuarios = [
    { usuario: 'admin@gmail.com', clave: '123', rol: 'admin', nombre: 'Admin MR' },
    { usuario: 'pepito@gmail.com', clave: '123', rol: 'asociados', nombre: 'Juan Perez' }
];

document.getElementById('FormInicioSesion').addEventListener('submit', function (e) {
    e.preventDefault(); //No recarga la pagina

    const usuarioInput = document.getElementById('correo').value;
    const claveInput = document.getElementById('contraseña').value;

    const usuarioEncontrado = usuarios.find(
        u => u.usuario === usuarioInput && u.clave === claveInput
    );

    if (usuarioEncontrado) {
        localStorage.setItem('logueado', 'true');
        localStorage.setItem('rol', usuarioEncontrado.rol);
        localStorage.setItem('nombre', usuarioEncontrado.nombre);
        window.location.href = 'dashboard.html';
    } else {
        alert('Usuario o contraseña incorrectos');
    }
});