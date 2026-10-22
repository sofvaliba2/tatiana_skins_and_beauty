<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <?= logo(true) ?>
                <p style="margin-top:1rem;max-width:300px;font-size:.92rem">
                    Acompañándote en cada paso del proceso para mejorar la calidad de tu piel, con una guía honesta, personalizada y profesional.
                </p>
            </div>
            <div>
                <h4>Explora</h4>
                <ul>
                    <li><a href="<?= ruta('servicios') ?>">Servicios</a></li>
                    <li><a href="<?= ruta('catalogo') ?>">Productos</a></li>
                    <li><a href="<?= ruta('reserva_nueva') ?>">Reservar cita</a></li>
                    <li><a href="<?= ruta('mis_reservas') ?>">Mis reservas</a></li>
                </ul>
            </div>
            <div>
                <h4>Contacto</h4>
                <ul>
                    <li>La Paz, Bolivia</li>
                    <li>+591 700 00000</li>
                    <li>hola@tatiana.bo</li>
                </ul>
            </div>
            <div>
                <h4>Horario</h4>
                <ul>
                    <li>Lun – Vie: 09:00 – 19:00</li>
                    <li>Sábado: 09:00 – 14:00</li>
                    <li>Domingo: cerrado</li>
                </ul>
            </div>
        </div>
        <div class="copy">© <?= date('Y') ?> Tatiana Skin and Beauty · Tu piel, nuestra prioridad</div>
    </div>
</footer>
<script src="<?= BASE_URL ?>/js/app.js"></script>
</body>
</html>
