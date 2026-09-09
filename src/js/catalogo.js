/**
 * ==========================================================================
 * KION · Catálogo Felino — script.js
 * --------------------------------------------------------------------------
 * Interacción del botón "Agregar al carrito". Por ahora solo confirma la
 * acción visualmente y lleva un contador en memoria; cuando conectes el
 * módulo de ventas real, escucha el evento personalizado "kion:add-to-cart"
 * (ver el final del archivo) en lugar de leer el DOM.
 * ========================================================================== */

(function () {
    'use strict';

    let contadorCarrito = 0;

    document.addEventListener('DOMContentLoaded', function () {
        const botones = document.querySelectorAll('.btn-agregar');
        botones.forEach(function (boton) {
            boton.addEventListener('click', manejarClicAgregar);
        });
    });

    function manejarClicAgregar(evento) {
        const boton = evento.currentTarget;

        if (boton.disabled) {
            return;
        }

        const producto = {
            id: boton.dataset.id,
            nombre: boton.dataset.nombre,
            precio: parseFloat(boton.dataset.precio),
        };

        contadorCarrito += 1;
        actualizarBadgeCarrito();
        mostrarToast(producto.nombre + ' se agregó al carrito');

        // Evento personalizado: el futuro carrito de ventas puede
        // suscribirse a esto sin tocar este archivo.
        document.dispatchEvent(
            new CustomEvent('kion:add-to-cart', { detail: producto, bubbles: true })
        );

        confirmarEnBoton(boton);
    }

    /**
     * Cambia el texto del botón para confirmar la acción y lo vuelve a
     * dejar disponible después de un momento, para poder añadir el mismo
     * producto más de una vez (útil en un punto de venta).
     */
    function confirmarEnBoton(boton) {
        const textoOriginal = boton.textContent;

        boton.textContent = '✓ Agregado';
        boton.classList.add('agregado');
        boton.disabled = true;

        window.setTimeout(function () {
            boton.textContent = textoOriginal;
            boton.classList.remove('agregado');
            boton.disabled = false;
        }, 1400);
    }

    function actualizarBadgeCarrito() {
        const badge = document.getElementById('cart-count');
        if (badge) {
            badge.textContent = String(contadorCarrito);
        }
    }

    function mostrarToast(mensaje) {
        const contenedor = document.getElementById('toast-container');
        if (!contenedor) {
            return;
        }

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = mensaje;
        contenedor.appendChild(toast);

        // Forzar un frame antes de añadir la clase visible, para que la
        // transición de CSS se ejecute.
        requestAnimationFrame(function () {
            toast.classList.add('toast--visible');
        });

        window.setTimeout(function () {
            toast.classList.remove('toast--visible');
            window.setTimeout(function () {
                toast.remove();
            }, 250);
        }, 2200);
    }
})();