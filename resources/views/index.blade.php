<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Azúcar y Yo - Repostería Artesanal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('estilos/base.css') }}">
    <link rel="stylesheet" href="{{ asset('estilos/index.css') }}">
    <style>
        .brand-logo-image img {
            max-width: min(31vw, 155px);
            height: 62px;
        }

        @media (max-width: 575.98px) {
            .brand-logo-image img {
                max-width: 115px;
                height: 50px;
            }
        }

        .top-section .product-img-wrapper {
            height: 160px;
        }

        .top-section .product-img {
            width: 100%;
            height: 100%;
            max-height: none;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <canvas id="webgl-canvas"></canvas>

    <header class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container">
            <a class="brand-logo brand-logo-image me-lg-4" href="{{ url('/') }}" aria-label="Azúcar y Yo, inicio">
                <img src="{{ asset('img/logo_reposteria_transparente.png') }}" alt="Azúcar y Yo">
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <div class="mx-auto my-2 my-lg-0">
                    <ul class="navbar-nav nav-pill-container flex-row gap-1 justify-content-center">
                        <li class="nav-item"><a class="nav-link active" href="#">Inicio</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('catalogo') }}">Catálogo</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('menu') }}">Menú Digital</a></li>
                        <li class="nav-item"><a class="nav-link" href="#nosotros">Nosotros</a></li>
                        <li class="nav-item"><a class="nav-link" href="#resenas">Reseñas</a></li>
                        <li class="nav-item"><a class="nav-link" href="#destacados">Ofertas</a></li>
                    </ul>
                </div>

                <div class="d-flex align-items-center gap-2 justify-content-center mt-3 mt-lg-0">
                    <a href="{{ route('login') ?? '/login' }}" class="btn-icon-admin"
                        title="Iniciar Sesión / Panel Admin">
                        <i class="bi bi-person fs-5"></i>
                    </a>

                    <!-- Botón del carrito activando el Offcanvas -->
                    <a href="#cartOffcanvas" class="btn-cart" title="Carrito de Compras" data-bs-toggle="offcanvas"
                        role="button">
                        <i class="bi bi-bag fs-6"></i>
                        <span class="cart-badge">0</span>
                    </a>

                    <a href="#cartOffcanvas" class="btn btn-pedido ms-1" data-bs-toggle="offcanvas" role="button">
                        Haz tu Pedido
                    </a>
                </div>
            </div>
        </div>
    </header>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <h1 class="hero-title">Dulzura en <br>cada bocado</h1>
                    <p class="hero-description">
                        Disfruta de nuestros postres artesanales, hechos con pasión y los mejores ingredientes. Una
                        experiencia para los sentidos.
                    </p>
                    <a href="{{ route('catalogo') }}" class="btn btn-hero">Explorar</a>
                </div>
                <div class="col-lg-6">
                    <div class="hero-3d-wrapper">
                        <div class="hero-3d-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="destacados" class="container top-section my-5">
        <div class="top-watermark">TOP</div>
        <div class="row g-4 justify-content-center position-relative" style="z-index: 1;">

            @forelse($destacados as $producto)
                <div class="col-md-4 col-sm-8">
                    <div class="product-card h-100 d-flex flex-column justify-content-between">
                        <div class="product-img-wrapper">
                            <img src="{{ $producto->imagen ? asset($producto->imagen) : asset('img/default.jpg') }}"
                                alt="{{ $producto->nombre }}" class="product-img">
                        </div>
                        <div>
                            <div class="rating-stars">
                                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                    class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                    class="bi bi-star-fill"></i>
                            </div>
                            <h5 class="product-title">{{ $producto->nombre }}</h5>
                            <p class="product-desc">
                                {{ $producto->descripcion ?? 'Delicioso postre artesanal preparado con los mejores ingredientes.' }}
                            </p>
                            <p class="text-primary fw-bold mb-2">Bs. {{ number_format($producto->precio, 2) }}</p>
                        </div>
                        <div class="product-actions">
                            <button class="btn-card-action" title="Guardar en Favoritos"><i
                                    class="bi bi-heart"></i></button>
                            <button class="btn-card-action btn-add-to-cart" title="Añadir al Carrito"
                                data-id="{{ $producto->id }}" data-nombre="{{ $producto->nombre }}"
                                data-precio="{{ $producto->precio }}"
                                data-imagen="{{ $producto->imagen ? asset($producto->imagen) : asset('img/default.jpg') }}">
                                <i class="bi bi-cart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Pronto tendremos productos destacados disponibles.</p>
                </div>
            @endforelse

        </div>
        <div class="text-center mt-5 position-relative" style="z-index: 1;">
            <a href="{{ route('catalogo') }}" class="btn btn-ver-catalogo">Ver Catálogo</a>
        </div>
    </section>

    <!-- SECCIÓN CARACTERÍSTICAS / NOSOTROS -->
    <section id="nosotros" class="container features-section my-5">
        <h2 class="features-title">AZÚCAR Y YO es...</h2>
        <div class="row align-items-center">
            <div class="col-lg-4 col-md-6 order-1">
                <div class="feature-item">
                    <span class="feature-number">1</span>
                    <h4 class="feature-heading">Calidad Artesanal.</h4>
                    <p class="feature-text">
                        Nuestros productos son elaborados a mano, respetando las tradiciones.
                    </p>
                </div>
                <div class="feature-item mt-lg-5">
                    <span class="feature-number">3</span>
                    <h4 class="feature-heading">Atención Personalizada.</h4>
                    <p class="feature-text">
                        Nos esforzamos por ofrecerte un servicio excepcional, adaptándonos a tus gustos.
                    </p>
                </div>
            </div>
            <div class="col-lg-4 my-4 my-lg-0 order-3 order-lg-2">
                <div class="features-3d-wrapper"></div>
            </div>
            <div class="col-lg-4 col-md-6 order-2 order-lg-3">
                <div class="feature-item">
                    <span class="feature-number">2</span>
                    <h4 class="feature-heading">Ingredientes Selectos.</h4>
                    <p class="feature-text">
                        Utilizamos solo los mejores ingredientes: harina de alta calidad, chocolate real.
                    </p>
                </div>
                <div class="feature-item mt-lg-5">
                    <span class="feature-number">4</span>
                    <h4 class="feature-heading">Entregas Puntuales.</h4>
                    <p class="feature-text">
                        Garantizamos que tus pedidos lleguen frescos y puntuales, listos para disfrutar.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN CUESTIONARIO / BANNER -->
    <section id="quiz-banner" class="container my-5">
        <div class="quiz-banner">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <h3 class="quiz-title">¡DESCUBRE TU DULCE IDEAL!</h3>
                    <p class="quiz-text">
                        Haz nuestro cuestionario para encontrar el postre que mejor se adapta a ti.
                    </p>
                    <a href="#" class="btn btn-quiz">Empezar Prueba</a>
                </div>
                <div class="col-lg-5 text-center text-lg-end">
                    <div class="p-3 d-inline-block text-center placeholder-3d">
                        <i class="bi bi-magic fs-2 placeholder-3d-icon"></i>
                        <p class="m-0 mt-1 small fw-semibold placeholder-3d-text">[ Área reservada para Elemento 3D / Detalle ]</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN RESEÑAS / TESTIMONIOS -->
    <section id="resenas" class="container reviews-section my-5">
        <h2 class="features-title">Lo que dicen nuestros clientes</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4 col-sm-8">
                <div class="review-card">
                    <div>
                        <i class="bi bi-quote quote-icon"></i>
                        <div class="rating-stars mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i>
                        </div>
                        <p class="review-text">
                            "El mousse de chocolate belga superó todas mis expectativas. La textura es increíble y el
                            nivel de dulce es perfecto. ¡Definitivamente volveré a pedir!"
                        </p>
                    </div>
                    <div class="review-user">
                        <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&auto=format&fit=crop&q=80"
                            alt="Cliente" class="review-avatar">
                        <div>
                            <h6 class="review-name">Mariana Torres</h6>
                            <p class="review-date">Cliente Verificada</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-8">
                <div class="review-card">
                    <div>
                        <i class="bi bi-quote quote-icon"></i>
                        <div class="rating-stars mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i>
                        </div>
                        <p class="review-text">
                            "Pedí una mesa de postres para mi evento y la atención personalizada fue impecable. Todo
                            llegó a tiempo y la presentación fue hermosa."
                        </p>
                    </div>
                    <div class="review-user">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80"
                            alt="Cliente" class="review-avatar">
                        <div>
                            <h6 class="review-name">Carlos Mendoza</h6>
                            <p class="review-date">Cliente Verificado</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-8">
                <div class="review-card">
                    <div>
                        <i class="bi bi-quote quote-icon"></i>
                        <div class="rating-stars mb-2">
                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i
                                class="bi bi-star-fill"></i>
                        </div>
                        <p class="review-text">
                            "La copa de mousse de frutos rojos es deliciosa. Se nota la frescura de los ingredientes.
                            Totalmente recomendado para cualquier ocasión."
                        </p>
                    </div>
                    <div class="review-user">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80"
                            alt="Cliente" class="review-avatar">
                        <div>
                            <h6 class="review-name">Sofía Ramírez</h6>
                            <p class="review-date">Cliente Verificada</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer-custom" style="background-color: #e2b5f0ff; padding: 25px 0; border-top: 1px solid #c7b5d6;">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <a href="#" class="footer-brand font-weight-bold" style="color: #4a3b5c; font-size: 1.2rem;">AZÚCAR Y YO</a>
                
                <!-- Ubicación -->
                <div class="mb-1 text-secondary">
                    <i class="fas fa-map-marker-alt text-danger"></i> <strong>Ubicación:</strong> Sacaba - Huayllani
                </div>

                <!-- Teléfono -->
                <div class="mb-1 text-secondary">
                    <i class="fas fa-phone-alt text-success"></i> <strong>Teléfono:</strong> 65318803
                </div>

                <!-- Horarios de Atención -->
                <div class="mb-1 text-secondary">
                    <i class="fas fa-clock text-primary"></i> <strong>Horarios de atención:</strong> 09:00 am a 18:30 pm
                </div>
            </div> 
        </div>
        
        <div class="text-center mt-3 border-top pt-2" style="border-color: #c7b5d6 !important; font-size: 0.9rem; color: #555;">
            <div class="float-right d-none d-sm-inline mr-3">
                <b>Versión</b> 1.0
            </div>
            <strong>Copyright &copy; {{ date('Y') }} <a href="#" style="color: #5a4572;">Azúcar y Yo</a>.</strong> Todos los derechos reservados.
        </div>
    </footer>
    

    <!-- Offcanvas del Carrito de Compras -->
    <div class="offcanvas offcanvas-end cart-offcanvas" tabindex="-1" id="cartOffcanvas"
        aria-labelledby="cartOffcanvasLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="cartOffcanvasLabel"><i class="bi bi-cart3"></i> Mi Carrito</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-0" style="background-color: #f8f9fa;">
            <div id="cart-items-container" class="flex-grow-1 overflow-auto p-3">
                <!-- Los productos se inyectan aquí -->
            </div>

            <div class="border-top pt-3 bg-white p-3 rounded-top-3 shadow-sm mt-auto cart-footer">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fs-5 fw-semibold text-secondary">Total:</span>
                    <span id="cart-total-price" class="fs-4 fw-bold" style="color: #6a397b;">Bs. 0.00</span>
                </div>
                <div class="d-grid gap-2">
                    <button id="btn-send-whatsapp" class="btn btn-success fw-semibold py-2 btn-whatsapp">
                        <i class="bi bi-whatsapp"></i> Enviar Pedido
                    </button>
                    <button id="btn-clear-cart" class="btn btn-outline-danger btn-sm mt-1">
                        Vaciar Carrito
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <script src="{{ asset('js/script.js') }}"></script>
    <script src="{{ asset('js/carrito.js') }}"></script>
</body>

</html>