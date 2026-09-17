document.addEventListener('DOMContentLoaded', () => {
    let carrito = JSON.parse(localStorage.getItem('carritoKion')) || [];
    const cartCountElement = document.getElementById('cart-count');
    const toastContainer = document.getElementById('toast-container');
    const botonesAgregar = document.querySelectorAll('.btn-agregar');
    const cartIcon = document.querySelector('.topbar__carrito');
    const cartPanel = document.getElementById('cart-panel');
    const cartOverlay = document.getElementById('cart-overlay');
    const closeCartBtn = document.getElementById('close-cart');
    const cartItemsContainer = document.getElementById('cart-items');
    const cartTotalElement = document.getElementById('cart-total');

    function actualizarBurbuja() {
        const totalArticulos = carrito.reduce((total, prod) => total + prod.cantidad, 0);
        if (cartCountElement) {
            cartCountElement.textContent = totalArticulos;
        }
    }

    function guardarCarrito() {
        localStorage.setItem('carritoKion', JSON.stringify(carrito));
        actualizarBurbuja();
        renderizarCarrito();
    }

    function renderizarCarrito() {
        if (!cartItemsContainer) return;
        cartItemsContainer.innerHTML = '';
        let total = 0;

        if (carrito.length === 0) {
            cartItemsContainer.innerHTML = '<p style="color: #968F85; text-align: center; margin-top: 20px;">Tu carrito está vacío.</p>';
            cartTotalElement.textContent = '$0.00 MXN';
            return;
        }

        carrito.forEach((prod, index) => {
            const subtotal = prod.precio * prod.cantidad;
            total += subtotal;

            const item = document.createElement('div');
            item.classList.add('cart-item');
            item.innerHTML = `
                <div class="cart-item__info">
                    <span class="cart-item__nombre">${prod.nombre}</span>
                    <span class="cart-item__cantidad">Cantidad: ${prod.cantidad}</span>
                    <span class="cart-item__precio">$${prod.precio.toFixed(2)} MXN</span>
                </div>
                <button class="cart-item__eliminar" data-index="${index}">X</button>
            `;
            cartItemsContainer.appendChild(item);
        });

        cartTotalElement.textContent = `$${total.toFixed(2)} MXN`;

        document.querySelectorAll('.cart-item__eliminar').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = this.getAttribute('data-index');
                carrito.splice(idx, 1);
                guardarCarrito();
            });
        });
    }

    function showToast(mensaje) {
        const toast = document.createElement('div');
        toast.classList.add('toast');
        toast.textContent = mensaje;
        toastContainer.appendChild(toast);
        
        setTimeout(() => toast.classList.add('toast--visible'), 10);
        setTimeout(() => {
            toast.classList.remove('toast--visible');
            setTimeout(() => toast.remove(), 400); 
        }, 3000);
    }

    botonesAgregar.forEach(boton => {
        boton.addEventListener('click', function() {
            if (this.disabled) return;

            const idProd = this.getAttribute('data-id');
            const nombreProd = this.getAttribute('data-nombre');
            const precioProd = parseFloat(this.getAttribute('data-precio'));
            
            const index = carrito.findIndex(p => p.id === idProd);

            if (index !== -1) {
                carrito[index].cantidad += 1;
            } else {
                carrito.push({
                    id: idProd,
                    nombre: nombreProd,
                    precio: precioProd,
                    cantidad: 1
                });
            }

            guardarCarrito();
            
            cartCountElement.style.transform = 'scale(1.5)';
            setTimeout(() => cartCountElement.style.transform = 'scale(1)', 200);

            const spanTexto = this.querySelector('.btn-agregar__texto');
            const textoOriginal = spanTexto.textContent;
            this.classList.add('agregado');
            spanTexto.textContent = '¡Guardado!';
            
            setTimeout(() => {
                this.classList.remove('agregado');
                spanTexto.textContent = textoOriginal;
            }, 2000);

            showToast(`🛒 Guardado: ${nombreProd}`);
        });
    });

    cartIcon.addEventListener('click', () => {
        renderizarCarrito();
        cartPanel.classList.add('cart-panel--active');
        cartOverlay.classList.add('cart-overlay--active');
    });

    closeCartBtn.addEventListener('click', () => {
        cartPanel.classList.remove('cart-panel--active');
        cartOverlay.classList.remove('cart-overlay--active');
    });

    cartOverlay.addEventListener('click', () => {
        cartPanel.classList.remove('cart-panel--active');
        cartOverlay.classList.remove('cart-overlay--active');
    });

    actualizarBurbuja();
});