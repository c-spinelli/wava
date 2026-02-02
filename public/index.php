<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya está logueado, no tiene sentido ver la landing
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Wava – Tu wellness, bajo control</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand">
                <div class="brand-mark">≋</div>
                <div class="brand-name">Wava</div>
            </div>

            <div class="topbar-actions">
                <a class="btn btn-ghost" href="login.php">Iniciar sesión</a>
                <a class="btn btn-primary" href="register.php">Empezar ahora</a>
            </div>
        </div>
    </header>

    <main>

        <!-- HERO FULL WIDTH -->
        <section class="hero">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <div class="pill">Tu wellness, bajo control</div>

                    <h1 class="hero-title">
                        Transforma tus<br>
                        <span class="accent">hábitos diarios</span>
                    </h1>

                    <p class="hero-subtitle">
                        Registrá agua, proteína, ejercicio y sueño. Visualizá tu progreso y alcanzá tus objetivos de bienestar con Wava.
                    </p>

                    <div class="hero-cta">
                        <a class="btn btn-primary" href="register.php">Empezar ahora</a>
                        <a class="btn btn-ghost" href="#features">Ver demo</a>
                    </div>

                    <!-- STATS como en la landing ejemplo (con separadores) -->
                    <div class="hero-stats">
                        <div class="stat">
                            <div class="stat-big">10K+</div>
                            <div class="stat-small">Usuarios activos</div>
                        </div>

                        <div class="stat-sep"></div>

                        <div class="stat">
                            <div class="stat-big">95%</div>
                            <div class="stat-small">Satisfacción</div>
                        </div>

                        <div class="stat-sep"></div>

                        <div class="stat">
                            <div class="stat-big">4.9/5</div>
                            <div class="stat-small">Rating</div>
                        </div>
                    </div>
                </div>

                <div class="hero-visual">
                    <img class="hero-image" src="../assets/img/dashboard.jpeg" alt="Vista previa del dashboard de Wava">
                </div>


            </div>
        </section>

        <!-- FEATURES -->
        <section class="section" id="features">
            <div class="section-inner">
                <div class="section-head center">
                    <h2 class="h2">Todo lo que necesitás para tu bienestar</h2>
                    <p class="p">Herramientas simples para registrar y ver tu progreso diario.</p>
                </div>

                <div class="feature-grid">
                    <article class="feature">
                        <div class="feature-icon">💧</div>
                        <h3>Hidratación</h3>
                        <p>Registrá tu consumo de agua y alcanzá tu objetivo diario.</p>
                    </article>

                    <article class="feature">
                        <div class="feature-icon">🥗</div>
                        <h3>Nutrición</h3>
                        <p>Controlá tu ingesta de proteína con un número claro y editable.</p>
                    </article>

                    <article class="feature">
                        <div class="feature-icon">🏋️‍♀️</div>
                        <h3>Ejercicio</h3>
                        <p>Sumá múltiples entrenos por día y mirá el progreso total.</p>
                    </article>

                    <article class="feature">
                        <div class="feature-icon">🌙</div>
                        <h3>Descanso</h3>
                        <p>Cargá sueño y energía para ver patrones con el tiempo.</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- TESTIMONIOS -->
        <section class="section section-muted" id="testimonios">
            <div class="section-inner">
                <div class="section-head center">
                    <h2 class="h2">Lo que dicen nuestros usuarios</h2>
                    <p class="p">Miles de personas ya están transformando sus hábitos con Wava.</p>
                </div>

                <div class="reviews-grid">
                    <article class="review">
                        <div class="stars">★★★★★</div>
                        <p class="quote">
                            “Wava me ayudó a ordenar mis hábitos. Ver el progreso en tarjetas me motiva a ser constante.”
                        </p>
                        <div class="reviewer">
                            <img class="review-avatar" src="../assets/img/review3.jpg" alt="Foto de María González">

                            <div>
                                <div class="name">María González</div>
                                <div class="role">Instructora de Yoga</div>
                            </div>
                        </div>
                    </article>

                    <article class="review">
                        <div class="stars">★★★★★</div>
                        <p class="quote">
                            “La interfaz es simple y rápida. En un minuto ya tengo el día cargado y el total de ejercicio.”
                        </p>
                        <div class="reviewer">
                            <img class="review-avatar" src="../assets/img/review2.jpg" alt="Foto de Carlos Ruiz">
                            <div>
                                <div class="name">Carlos Ruiz</div>
                                <div class="role">Atleta</div>
                            </div>
                        </div>
                    </article>

                    <article class="review">
                        <div class="stars">★★★★★</div>
                        <p class="quote">
                            “Perfecta para registrar agua y proteína. Me encanta poder editar el día sin complicarme.”
                        </p>
                        <div class="reviewer">
                            <img class="review-avatar" src="../assets/img/review3.jpg" alt="Foto de Laura Martínez">
                            <div>
                                <div class="name">Laura Martínez</div>
                                <div class="role">Nutricionista</div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- CTA GRANDE -->
        <section class="section section-muted" id="cta">
            <div class="section-inner">
                <div class="cta">
                    <div class="cta-content">
                        <h2>Comienza tu transformación hoy</h2>
                        <p>Unite y registrá tu primer día en menos de 1 minuto.</p>
                    </div>

                    <a class="btn btn-primary cta-btn" href="register.php">
                        Crear cuenta <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </section>

        </section>

    </main>

    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <span class="footer-mark">≋</span>
                        <span class="footer-name">Wava</span>
                    </div>
                    <p class="footer-tagline">Tu compañero de bienestar diario</p>
                </div>

                <div class="footer-col">
                    <h4>Producto</h4>
                    <a href="#features">Características</a>
                    <a href="#precios">Precios</a>
                    <a href="#faq">FAQ</a>
                </div>

                <div class="footer-col">
                    <h4>Empresa</h4>
                    <a href="#about">Sobre nosotros</a>
                    <a href="#blog">Blog</a>
                    <a href="#contacto">Contacto</a>
                </div>

                <div class="footer-col">
                    <h4>Legal</h4>
                    <a href="#privacidad">Privacidad</a>
                    <a href="#terminos">Términos</a>
                    <a href="#cookies">Cookies</a>
                </div>
            </div>



            <div class="footer-bottom">
                © 2026 Wava. Todos los derechos reservados.
            </div>
        </div>
    </footer>


</body>

</html>