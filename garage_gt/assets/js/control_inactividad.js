/**
 * assets/js/control_inactividad.js
 * RNF-04: Cierre automático de sesión por inactividad
 * Timeout: 30 minutos (1.800.000 ms)
 */

(function () {
    'use strict';

    const TIMEOUT_MS  = 30 * 60 * 1000; // 30 minutos
    const AVISO_MS    =  5 * 60 * 1000; //  5 minutos antes aviso
    const BASE_URL    = document.querySelector('meta[name="base-url"]')?.content || '';

    let timerLogout;
    let timerAviso;
    let modalAviso = null;

    function resetTimers() {
        clearTimeout(timerLogout);
        clearTimeout(timerAviso);

        // Aviso 5 minutos antes del cierre
        timerAviso = setTimeout(mostrarAviso, TIMEOUT_MS - AVISO_MS);

        // Cierre de sesión
        timerLogout = setTimeout(cerrarSesion, TIMEOUT_MS);
    }

    function mostrarAviso() {
        if (!modalAviso) {
            const div = document.createElement('div');
            div.id = 'modal-inactividad';
            div.innerHTML = `
                <div style="position:fixed;top:0;left:0;width:100%;height:100%;
                     background:rgba(0,0,0,.55);z-index:9999;
                     display:flex;align-items:center;justify-content:center;">
                  <div style="background:#fff;border-radius:12px;padding:32px;
                       max-width:380px;text-align:center;box-shadow:0 8px 32px rgba(0,0,0,.25);">
                    <i class="bi bi-clock-history" style="font-size:2.5rem;color:#e94560;"></i>
                    <h5 style="margin:14px 0 8px;font-weight:700;">Sesión por expirar</h5>
                    <p style="color:#636e72;font-size:14px;margin-bottom:20px;">
                      Tu sesión cerrará en <strong>5 minutos</strong> por inactividad.
                    </p>
                    <button id="btn-continuar-sesion"
                      style="background:#e94560;color:#fff;border:none;
                             padding:10px 28px;border-radius:8px;font-weight:600;cursor:pointer;">
                      Continuar sesión
                    </button>
                  </div>
                </div>`;
            document.body.appendChild(div);
            modalAviso = div;

            document.getElementById('btn-continuar-sesion').addEventListener('click', function () {
                ocultarAviso();
                resetTimers();
            });
        } else {
            modalAviso.style.display = 'flex';
        }
    }

    function ocultarAviso() {
        if (modalAviso) modalAviso.style.display = 'none';
    }

    function cerrarSesion() {
        window.location.href = BASE_URL + '/controllers/logout.php?timeout=1';
    }

    // Eventos que reinician el contador
    const eventos = ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'];
    eventos.forEach(ev => document.addEventListener(ev, resetTimers, { passive: true }));

    // Iniciar
    resetTimers();
})();
