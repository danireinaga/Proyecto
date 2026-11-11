<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo | Azúcar y Yo</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('estilos/base.css') }}">
    <link rel="stylesheet" href="{{ asset('estilos/catalogo.css') }}">
</head>

<body>

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
                        <li class="nav-item"><a class="nav-link" href="{{ url('/') }}">Inicio</a></li>
                        <li class="nav-item"><a class="nav-link active" href="{{ route('catalogo') }}">Catálogo</a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ url('/') }}#nosotros">Nosotros</a></li>
                    </ul>
                </div>

                <div class="d-flex align-items-center gap-2 justify-content-center mt-3 mt-lg-0">
                    <a href="{{ route('login') ?? '/login' }}" class="btn-icon-admin" title="Panel Admin">
                        <i class="bi bi-person fs-5"></i>
                    </a>

                    <!-- Enlace directo al Offcanvas -->
                    <a href="#cartOffcanvas" class="btn-cart" title="Carrito" data-bs-toggle="offcanvas" role="button">
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

    <section class="container catalog-header">
        <h1 class="catalog-title">Nuestro Catálogo</h1>
        <p class="catalog-subtitle">Explora nuestra colección de postres elaborados diariamente de forma 100% artesanal.
        </p>

        <form action="{{ route('catalogo') }}" method="GET" class="row justify-content-center mb-4">
            <div class="col-md-7 col-lg-5">
                <div class="input-group search-box">
                    <span class="input-group-text bg-transparent border-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" name="q" value="{{ $busqueda ?? '' }}" class="form-control search-input"
                        placeholder="Buscar postres, tortas, cupcakes...">
                    @if (isset($categoriaId) && $categoriaId && $categoriaId !== 'todos')
                        <input type="hidden" name="categoria" value="{{ $categoriaId }}">
                    @endif
                </div>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-center gap-2 my-3">
            <a href="{{ route('catalogo', array_merge(request()->except('categoria'), ['categoria' => 'todos'])) }}"
                class="btn category-filter-btn {{ !isset($categoriaId) || !$categoriaId || $categoriaId === 'todos' ? 'active' : '' }}">Todos</a>
            @if (isset($categorias))
                @foreach ($categorias as $cat)
                    <a href="{{ route('catalogo', array_merge(request()->except('categoria'), ['categoria' => $cat->id])) }}"
                        class="btn category-filter-btn {{ isset($categoriaId) && $categoriaId == $cat->id ? 'active' : '' }}">{{ $cat->nombre }}</a>
                @endforeach
            @endif
        </div>
    </section>

    <section class="container my-4 catalog-grid">
        <div class="row g-4">
            @forelse($productos as $producto)
                <div class="col-lg-4 col-md-6">
                    <div class="product-card">
                        <button class="btn-fav" title="Favorito"><i class="bi bi-heart"></i></button>
                        <div class="product-img-wrapper">
                            <!-- Carga dinámica segura -->
                            <img src="{{ $producto->imagen ? asset($producto->imagen) : asset('img/default.jpg') }}"
                                alt="{{ $producto->nombre }}" class="product-img">
                        </div>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-light text-dark fw-normal">
                                    {{ $producto->categoria->nombre ?? 'Postre Artesanal' }}
                                </span>
                            </div>
                            <h5 class="product-title">{{ $producto->nombre }}</h5>
                            <p class="product-desc">{{ $producto->descripcion ?? 'Sin descripción disponible...' }}</p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="product-price">Bs. {{ number_format($producto->precio, 2) }}</span>
                            <!-- Botón Añadir arreglado -->
                            <button type="button" class="btn btn-add-cart btn-add-to-cart"
                                data-id="{{ $producto->id }}" data-nombre="{{ $producto->nombre }}"
                                data-precio="{{ $producto->precio }}"
                                data-imagen="{{ $producto->imagen ? asset($producto->imagen) : asset('img/default.jpg') }}">
                                <i class="bi bi-cart-plus"></i> Añadir
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No se encontraron productos disponibles en este catálogo.</p>
                </div>
            @endforelse
        </div>
    </section>

    <footer class="footer-custom">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <a href="{{ url('/') }}" class="footer-brand footer-brand-image" aria-label="Azúcar y Yo, inicio">
                    <img src="{{ asset('img/logo_reposteria_transparente.png') }}" alt="Azúcar y Yo">
                </a>
            </div>
        </div>
    </footer>

    <!-- Añade la clase 'cart-offcanvas' aquí -->
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

            <!-- Añade la clase 'cart-footer' aquí -->
            <div class="border-top pt-3 bg-white p-3 rounded-top-3 shadow-sm mt-auto cart-footer">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fs-5 fw-semibold text-secondary">Total:</span>
                    <span id="cart-total-price" class="fs-4 fw-bold" style="color: #6a397b;">Bs. 0.00</span>
                </div>
                <div class="d-grid gap-2">
                    <!-- Añade la clase 'btn-whatsapp' aquí -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/carrito.js') }}"></script>
</body>

</html>
