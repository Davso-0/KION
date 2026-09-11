document.addEventListener('DOMContentLoaded', () => {
    let carritoCount = 0;
    const cartCountElement = document.getElementById('cart-count');
    const toastContainer = document.getElementById('toast-container');
    const botonesAgregar = document.querySelectorAll('.btn-agregar');

    // Función para mostrar la notificación (Toast)
    function showToast(mensaje) {
        const toast = document.createElement('div');
        toast.classList.add('toast');
        toast.textContent = mensaje;
        
        toastContainer.appendChild(toast);

        // Animar entrada
        setTimeout(() => toast.classList.add('toast--visible'), 10);

        // Quitar después de 3 segundos
        setTimeout(() => {
            toast.classList.remove('toast--visible');
            setTimeout(() => toast.remove(), 400); // Espera a que termine la animación
        }, 3000);
    }

    // Darle vida a cada botón
    botonesAgregar.forEach(boton => {
        boton.addEventListener('click', function() {
            // Si está deshabilitado por falta de stock, no hacemos nada
            if (this.disabled) return;

            const nombreProducto = this.getAttribute('data-nombre');
            
            // Sumar al contador del carrito
            carritoCount++;
            cartCountElement.textContent = carritoCount;
            
            // Pequeña animación en el contador
            cartCountElement.style.transform = 'scale(1.5)';
            setTimeout(() => cartCountElement.style.transform = 'scale(1)', 200);

            // Cambiar estado del botón temporalmente
            const spanTexto = this.querySelector('.btn-agregar__texto');
            const textoOriginal = spanTexto.textContent;
            
            this.classList.add('agregado');
            spanTexto.textContent = '¡Agregado!';
            
            // Regresar el botón a la normalidad después de 2 segundos
            setTimeout(() => {
                this.classList.remove('agregado');
                spanTexto.textContent = textoOriginal;
            }, 2000);

            // Mostrar la alerta
            showToast(`🛒 Se agregó: ${nombreProducto}`);
        });
    });
});