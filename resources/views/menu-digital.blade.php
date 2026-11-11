<!DOCTYPE html>

<html class="h-full" lang="es">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="tv_menu_commercial" name="shell-type" />
    <title>Azúcar y Yo - Haute Pâtisserie &amp; Café de Especialidad</title>
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&amp;family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600;1,700&amp;family=Playfair+Display:ital,wght@0,600;0,700;0,800;1,600&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <style>
        @layer base {

            html,
            body {
                min-height: 100%;
                overflow-x: hidden;
                overflow-y: auto;
                margin: 0;
                padding: 0;
            }

            body {
                -webkit-font-smoothing: antialiased;
                text-rendering: optimizeLegibility;
            }
        }

        ::-webkit-scrollbar {
            display: none;
        }

        .gold-glow {
            box-shadow: 0 0 35px -5px rgba(229, 169, 85, 0.22);
        }

        .plum-glow {
            box-shadow: 0 16px 40px -10px rgba(74, 21, 54, 0.65);
        }

        #menu-showcase {
            flex: none;
            overflow: visible;
        }

        .menu-digital-grid {
            height: calc(100vh - 200px);
            min-height: 600px;
        }

        #additional-menu-groups {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.5rem;
        }

        .additional-menu-card {
            height: calc((100vh - 232px) / 3);
            min-height: 145px;
        }

        .menu-digital-hero-details > div:first-child > div:first-child,
        .menu-digital-hero-details > div:first-child > div:first-child h2,
        .menu-digital-supporting [data-menu-card] > div:nth-child(2) {
            min-width: 0;
        }

        #hero-name,
        .menu-digital-supporting [data-product-field="name"] {
            overflow-wrap: anywhere;
        }

        #hero-price {
            font-size: clamp(2rem, 4vw, 3.75rem);
            overflow-wrap: anywhere;
        }

        #hero-description {
            display: block;
            max-width: 100%;
            white-space: normal;
            overflow-wrap: anywhere;
            line-height: 1.45;
        }

        .menu-digital-hero-details > div:last-child {
            align-items: flex-start;
            flex-wrap: wrap;
            gap: .6rem;
        }

        .menu-digital-hero-details > div:last-child > div:first-child {
            min-width: 0;
            flex: 1 1 100%;
            align-items: stretch;
            flex-direction: column;
        }

        .menu-digital-supporting [data-menu-card] > div:nth-child(2) > div:first-child {
            min-width: 0;
        }

        .menu-digital-supporting [data-product-field="name"] {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .menu-digital-supporting [data-product-field="description"] {
            display: -webkit-box;
            overflow: hidden;
            white-space: normal;
            overflow-wrap: anywhere;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
            line-height: 1.4;
        }

        .menu-digital-supporting [data-menu-card] > div:nth-child(2) > div:last-child {
            flex-wrap: wrap;
            gap: .5rem;
        }

        .menu-digital-supporting [data-product-field="price"] {
            font-size: clamp(1.25rem, 2.4vw, 2.25rem);
            overflow-wrap: anywhere;
        }

        .menu-digital-supporting [data-menu-card] > div:nth-child(2) > div:last-child > div {
            max-width: 100%;
            padding-left: .75rem;
            padding-right: .75rem;
        }
    </style>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "plum-950": "#210918",
                        "plum-900": "#340E26",
                        "plum-800": "#4A1536",
                        "plum-700": "#6A2B50",
                        "plum-600": "#8A2D65",
                        "gold-rose": "#E5A955",
                        "gold-accent": "#D4AF37",
                        "pistachio": "#2D7A5B",
                        "pistachio-light": "#3F9E78",
                        "cream": "#FBF7F4",
                        "cream-muted": "#EADCD5",
                        "card-dark": "rgba(42, 13, 33, 0.72)",
                        "card-border": "rgba(229, 169, 85, 0.24)"
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        serif: ['"Cormorant Garamond"', '"Playfair Display"', 'serif'],
                    }
                }
            }
        };
    </script>
    <style>
        @media (max-width: 767.98px) {
            html,
            body.menu-digital-page {
                height: auto !important;
                min-height: 100%;
                overflow-x: hidden !important;
                overflow-y: auto !important;
            }

            body.menu-digital-page {
                width: 100%;
                min-height: 100vh;
            }

            .menu-digital-header-content {
                height: auto !important;
                min-height: 0;
                padding: .8rem !important;
                align-items: stretch;
                flex-direction: column;
                gap: .75rem;
            }

            .menu-digital-brand-row {
                flex-wrap: wrap;
                gap: .65rem;
            }

            .menu-digital-brand-row > .h-8 {
                display: none;
            }

            #scene-pills {
                max-width: 100%;
                overflow-x: auto;
                justify-content: flex-start;
                border-radius: 14px;
            }

            .menu-digital-header-controls {
                justify-content: space-between;
                flex-wrap: wrap;
                gap: .5rem;
            }

            #fullscreen-toggle-btn {
                padding: .6rem .75rem;
                font-size: .65rem;
            }

            #menu-showcase {
                flex: none;
                padding: .75rem;
                overflow: visible !important;
            }

            .menu-digital-grid {
                display: flex;
                height: auto !important;
                min-height: 0;
                flex-direction: column;
                gap: .85rem;
            }

            #additional-menu-groups {
                grid-template-columns: minmax(0, 1fr);
                gap: .75rem;
            }

            .additional-menu-card {
                height: auto;
            }

            .menu-digital-hero {
                min-height: 0;
                padding: .85rem;
                gap: .85rem;
            }

            .menu-digital-hero-photo {
                height: clamp(210px, 62vw, 340px) !important;
                flex: none;
            }

            .menu-digital-hero-details {
                flex: none;
                gap: .8rem;
            }

            .menu-digital-hero-details > div:first-child {
                align-items: flex-start;
                flex-wrap: wrap;
                gap: .65rem;
            }

            .menu-digital-hero-details > div:first-child > div:first-child {
                flex: 1 1 100%;
            }

            #hero-name {
                font-size: 1.65rem;
            }

            #hero-price {
                font-size: clamp(1.75rem, 9vw, 2.5rem);
            }

            .menu-digital-hero-details > div:last-child > div:first-child {
                flex: 1 1 100%;
            }

            .menu-digital-supporting [data-menu-card] > div:first-child {
                width: 32%;
                min-height: 130px;
            }

            .menu-digital-supporting [data-menu-card] > div:nth-child(2) {
                flex: 1;
            }

            .menu-digital-supporting [data-product-field="price"] {
                font-size: clamp(1.2rem, 6vw, 1.75rem);
            }

            .menu-digital-supporting {
                display: flex;
                height: auto !important;
                flex-direction: column;
                gap: .75rem;
            }

            .menu-digital-supporting [data-menu-card] {
                min-height: 145px;
                padding: .7rem;
            }

            .menu-digital-footer {
                align-items: flex-start;
                flex-wrap: wrap;
                gap: .7rem;
                padding: .8rem;
            }

            .menu-digital-footer > div {
                flex-wrap: wrap;
                gap: .5rem;
            }

            .menu-footer-divider {
                display: none;
            }
        }

        @media (max-width: 420px) {
            .menu-digital-brand-row img {
                width: 3.5rem;
            }

            .menu-digital-brand-row h1 {
                font-size: 1.45rem;
            }

            .menu-digital-header-controls > div {
                padding: .4rem .6rem;
            }

            .menu-digital-supporting [data-menu-card] {
                gap: .55rem;
            }
        }
    </style>
</head>

<body class="menu-digital-page text-cream font-sans select-none min-h-screen w-full flex flex-col relative"
    style="background: radial-gradient(circle at 75% 20%, #581C40 0%, #350F27 45%, #1C0614 100%);">
    <!-- Ambient Light Accents for Haute Luxury feel -->
    <div
        class="absolute top-[-10%] left-[-5%] w-[45vw] h-[45vw] rounded-full bg-plum-700/25 blur-[120px] pointer-events-none">
    </div>
    <div
        class="absolute bottom-[-10%] right-[10%] w-[40vw] h-[40vw] rounded-full bg-gold-rose/15 blur-[140px] pointer-events-none">
    </div>
    <!-- TOP HEADER / BOUTIQUE LUXURY BAR (16:9 Optimized) -->
    <header
        class="menu-digital-header w-full bg-plum-950/80 backdrop-blur-xl border-b border-gold-rose/20 relative z-30 flex-shrink-0 shadow-2xl">
        <div class="menu-digital-header-content h-[74px] px-8 flex items-center justify-between">
            <!-- Brand & Luxury Heritage Title -->
            <div class="menu-digital-brand-row flex items-center gap-6">
                <div class="flex items-center gap-3.5">
                    <img src="{{ asset('img/logo_reposteria_transparente.png') }}" alt="Azúcar y Yo"
                        class="h-14 w-20 object-contain">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-3">
                            <h1
                                class="font-serif text-3xl font-bold tracking-tight text-white leading-none drop-shadow-md">
                                Azúcar y Yo
                            </h1>
                            <span
                                class="inline-flex items-center gap-1.5 text-[11px] font-extrabold px-2.5 py-0.5 rounded-full bg-gold-rose/15 border border-gold-rose/30 text-gold-rose uppercase tracking-widest">
                                <span class="w-1.5 h-1.5 rounded-full bg-gold-rose animate-pulse"></span> Boutique
                            </span>
                        </div>
                        <span class="text-[12px] font-medium tracking-[0.2em] text-cream/70 uppercase mt-1">Repostería
                        </span>
                    </div>
                </div>
                <div class="h-8 w-px bg-gold-rose/25 mx-1"></div>
                <!-- Estado dinámico del menú digital -->
                <div
                    class="flex items-center gap-2.5 bg-pistachio/25 border border-pistachio-light/40 px-3.5 py-1.5 rounded-full backdrop-blur-md">
                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pistachio-light opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-pistachio-light"></span>
                    </span>
                    <span class="text-[12px] font-bold text-white tracking-wide flex items-center gap-1.5">
                        <span id="menu-status">Menú disponible</span>
                        <span class="text-white/40">•</span>
                        <span class="text-emerald-200" id="available-products-count">{{ $productos->count() }}
                            productos</span>
                    </span>
                </div>
            </div>
            <!-- Center Category Navigation -->
            <nav class="flex items-center gap-2 bg-black/40 p-1.5 rounded-full border border-gold-rose/20 backdrop-blur-md"
                id="scene-pills">
            </nav>
            <!-- Right Controls: Live Clock + Prominent Fullscreen Button -->
            <div class="menu-digital-header-controls flex items-center gap-4">
                <!-- Live Clock -->
                <div
                    class="flex items-center gap-2.5 bg-plum-900/80 border border-gold-rose/30 px-4 py-1.5 rounded-2xl backdrop-blur-md shadow-inner">
                    <span class="material-symbols-outlined text-gold-rose text-[20px]">schedule</span>
                    <span class="font-mono text-lg font-bold tracking-widest text-white" id="digital-clock">--:--</span>
                </div>
                <!-- Prominent Fullscreen Button -->
                <button
                    class="flex items-center gap-2 bg-gradient-to-r from-gold-rose via-amber-400 to-gold-accent text-plum-950 hover:brightness-110 px-4 py-2 rounded-2xl font-black text-xs uppercase tracking-wider shadow-lg transition-transform duration-200 hover:scale-105 active:scale-95 border border-amber-200/50"
                    id="fullscreen-toggle-btn" title="Ver Pantalla Completa (F11)">
                    <span class="material-symbols-outlined text-[20px]" id="fs-icon">fullscreen</span>
                    <span class="tracking-wider" id="fs-text">Modo Pantalla Completa</span>
                </button>
            </div>
        </div>
    </header>
    <!-- MAIN 16:9 COMMERCIAL VIEWPORT -->
    @php
        $initialMenuItems = collect($menuSlides[0]['products'] ?? [])
            ->take(4)
            ->values();
        $heroProduct = $initialMenuItems->get(0);
        $supportingProductOne = $initialMenuItems->get(1);
        $supportingProductTwo = $initialMenuItems->get(2);
        $supportingProductThree = $initialMenuItems->get(3);
    @endphp
    <main id="menu-showcase"
        class="w-full p-6 pb-4 flex flex-col gap-6 overflow-visible relative z-10 {{ empty($menuSlides) ? 'hidden' : '' }}">
        <div class="menu-digital-grid grid grid-cols-12 gap-6 h-full items-stretch">
            <!-- Featured product -->
            <div
                class="menu-digital-hero col-span-12 lg:col-span-6 bg-card-dark rounded-3xl p-5 backdrop-blur-xl border border-gold-rose/30 flex flex-col justify-between relative overflow-hidden group plum-glow transition-all duration-500 hover:border-gold-rose/60">
                <!-- Large Photo Container (High-impact visual) -->
                <div
                    class="menu-digital-hero-photo relative w-full h-[65%] rounded-2xl overflow-hidden bg-plum-950 border border-white/10 shadow-inner">
                    <img id="hero-image" data-hero-field="image" alt="{{ $heroProduct['name'] ?? '' }}"
                        class="w-full h-full object-contain transform transition-transform duration-1000 group-hover:scale-105"
                        src="{{ $heroProduct['image'] ?? asset('img/default.jpg') }}" />
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-plum-950 via-plum-950/20 to-transparent opacity-90">
                    </div>
                    <!-- Badges -->
                    <div class="absolute top-4 left-4 flex items-center gap-2.5">
                        <span
                            class="px-4 py-1.5 rounded-full bg-gradient-to-r from-plum-700 to-plum-600 text-cream font-extrabold text-xs uppercase tracking-wider border border-pink-400/30 shadow-lg flex items-center gap-1.5">
                            <span
                                class="material-symbols-outlined text-[16px] text-gold-rose">workspace_premium</span><span
                                id="hero-featured-label" data-hero-field="featured-label">{{ !empty($heroProduct['featured']) ? 'Selección destacada' : $heroProduct['category'] ?? 'Especialidades' }}</span>
                        </span>
                        <span id="hero-category-badge" data-hero-field="category"
                            class="px-3.5 py-1.5 rounded-full bg-black/60 backdrop-blur-md text-gold-rose border border-gold-rose/40 font-bold text-xs uppercase tracking-wider">
                            {{ $heroProduct['category'] ?? '' }}
                        </span>
                    </div>
                    <!-- Micro photo bottom label -->
                    <div class="absolute bottom-3 left-4 right-4 flex items-center justify-between text-cream/90">
                        <span id="hero-photo-category" data-hero-field="category"
                            class="text-xs font-bold tracking-[0.18em] uppercase text-gold-rose/90">{{ $heroProduct['category'] ?? '' }}</span>
                    </div>
                </div>
                <!-- Product Details & Giant 4-Meter Readability Price -->
                <div class="menu-digital-hero-details mt-4 flex-1 flex flex-col justify-between">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <span id="hero-category" data-hero-field="category"
                                class="text-[11px] font-black uppercase tracking-[0.25em] text-gold-rose">{{ $heroProduct['category'] ?? '' }}</span>
                            <h2
                                class="font-serif text-3xl xl:text-4xl font-bold text-white tracking-tight leading-tight mt-1">
                                <span id="hero-name" data-hero-field="name">{{ $heroProduct['name'] ?? '' }}</span>
                            </h2>
                        </div>
                        <!-- Giant Price Box -->
                        <div
                            class="flex flex-col items-end px-5 py-2.5 rounded-2xl bg-gradient-to-br from-plum-800 to-plum-950 border border-gold-rose/40 shadow-xl gold-glow">
                            <span
                                class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-gold-rose/90">Precio
                                Final</span>
                            <div class="flex items-baseline gap-1">
                                <span class="text-2xl font-bold font-serif text-gold-rose">Bs.</span>
                                <span id="hero-price" data-hero-field="price"
                                    class="font-serif text-5xl xl:text-6xl font-extrabold text-white tracking-tight leading-none drop-shadow">{{ isset($heroProduct['price']) ? number_format((float) $heroProduct['price'], 2, ',', '.') : '' }}</span>
                            </div>
                        </div>
                    </div>
                    <!-- Micro Quick Tags for Instant Visual Decision -->
                    <div class="flex items-center justify-between pt-3 border-t border-gold-rose/20 mt-2">
                        <div class="flex items-center gap-2">
                            <span id="hero-description" data-hero-field="description"
                                class="px-3 py-1 rounded-xl text-xs font-bold bg-plum-900/90 text-pink-200 border border-plum-700/60">
                                {{ $heroProduct['description'] ?? '' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- RIGHT COLUMN: 3 PROMINENT REFINED PHOTO CARDS -->
            <div class="menu-digital-supporting col-span-12 lg:col-span-6 grid grid-rows-3 gap-4 h-full">
                <!-- Product card 1 -->
                <div data-menu-card="1"
                    class="bg-card-dark rounded-3xl p-3.5 backdrop-blur-xl border border-card-border flex items-center gap-4 relative overflow-hidden group hover:border-gold-rose/60 hover:bg-plum-900/60 transition-all duration-300 shadow-xl {{ $supportingProductOne ? '' : 'hidden' }}">
                    <div
                        class="w-[40%] h-full rounded-2xl overflow-hidden relative flex-shrink-0 bg-plum-950 border border-white/10">
                        <img data-product-field="image" alt="{{ $supportingProductOne['name'] ?? '' }}"
                            class="w-full h-full object-contain transform transition-transform duration-700 group-hover:scale-105"
                            src="{{ $supportingProductOne['image'] ?? asset('img/default.jpg') }}" />
                        <span data-product-field="category"
                            class="absolute top-2.5 left-2.5 bg-gradient-to-r from-plum-700 to-plum-600 text-gold-rose border border-gold-rose/40 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider shadow">{{ $supportingProductOne['category'] ?? '' }}</span>
                    </div>
                    <div class="flex-1 flex flex-col justify-between py-1 h-full">
                        <div>
                            <span data-product-field="category"
                                class="text-[10px] font-bold text-gold-rose uppercase tracking-[0.2em]">{{ $supportingProductOne['category'] ?? '' }}</span>
                            <h3 data-product-field="name"
                                class="font-serif text-2xl xl:text-[27px] font-bold text-white leading-tight mt-0.5">
                                {{ $supportingProductOne['name'] ?? '' }}</h3>
                            <p data-product-field="description"
                                class="text-xs font-medium text-cream/75 mt-1 line-clamp-1">
                                {{ $supportingProductOne['description'] ?? '' }}</p>
                        </div>
                        <div class="flex items-center justify-end pt-2 border-t border-gold-rose/15">
                            <div
                                class="flex items-baseline gap-1 px-4 py-1.5 rounded-2xl bg-gradient-to-r from-plum-800 to-plum-950 border border-gold-rose/40 shadow-md">
                                <span class="text-sm font-bold text-gold-rose font-serif">Bs.</span>
                                <span data-product-field="price"
                                    class="font-serif text-3xl xl:text-4xl font-extrabold text-white leading-none">{{ isset($supportingProductOne['price']) ? number_format((float) $supportingProductOne['price'], 2, ',', '.') : '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Product card 2 -->
                <div data-menu-card="2"
                    class="bg-card-dark rounded-3xl p-3.5 backdrop-blur-xl border border-card-border flex items-center gap-4 relative overflow-hidden group hover:border-gold-rose/60 hover:bg-plum-900/60 transition-all duration-300 shadow-xl {{ $supportingProductTwo ? '' : 'hidden' }}">
                    <div
                        class="w-[40%] h-full rounded-2xl overflow-hidden relative flex-shrink-0 bg-plum-950 border border-white/10">
                        <img data-product-field="image" alt="{{ $supportingProductTwo['name'] ?? '' }}"
                            class="w-full h-full object-contain transform transition-transform duration-700 group-hover:scale-105"
                            src="{{ $supportingProductTwo['image'] ?? asset('img/default.jpg') }}" />
                        <span data-product-field="category"
                            class="absolute top-2.5 left-2.5 bg-gradient-to-r from-gold-rose/90 to-gold-accent text-plum-950 text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider shadow">{{ $supportingProductTwo['category'] ?? '' }}</span>
                    </div>
                    <div class="flex-1 flex flex-col justify-between py-1 h-full">
                        <div>
                            <span data-product-field="category"
                                class="text-[10px] font-bold text-gold-rose uppercase tracking-[0.2em]">{{ $supportingProductTwo['category'] ?? '' }}</span>
                            <h3 data-product-field="name"
                                class="font-serif text-2xl xl:text-[27px] font-bold text-white leading-tight mt-0.5">
                                {{ $supportingProductTwo['name'] ?? '' }}</h3>
                            <p data-product-field="description"
                                class="text-xs font-medium text-cream/75 mt-1 line-clamp-1">
                                {{ $supportingProductTwo['description'] ?? '' }}</p>
                        </div>
                        <div class="flex items-center justify-end pt-2 border-t border-gold-rose/15">
                            <div
                                class="flex items-baseline gap-1 px-4 py-1.5 rounded-2xl bg-gradient-to-r from-plum-800 to-plum-950 border border-gold-rose/40 shadow-md">
                                <span class="text-sm font-bold text-gold-rose font-serif">Bs.</span>
                                <span data-product-field="price"
                                    class="font-serif text-3xl xl:text-4xl font-extrabold text-white leading-none">{{ isset($supportingProductTwo['price']) ? number_format((float) $supportingProductTwo['price'], 2, ',', '.') : '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Product card 3 -->
                <div data-menu-card="3"
                    class="bg-card-dark rounded-3xl p-3.5 backdrop-blur-xl border border-card-border flex items-center gap-4 relative overflow-hidden group hover:border-gold-rose/60 hover:bg-plum-900/60 transition-all duration-300 shadow-xl {{ $supportingProductThree ? '' : 'hidden' }}">
                    <div
                        class="w-[40%] h-full rounded-2xl overflow-hidden relative flex-shrink-0 bg-plum-950 border border-white/10">
                        <img data-product-field="image" alt="{{ $supportingProductThree['name'] ?? '' }}"
                            class="w-full h-full object-contain transform transition-transform duration-700 group-hover:scale-105"
                            src="{{ $supportingProductThree['image'] ?? asset('img/default.jpg') }}" />
                        <span data-product-field="category"
                            class="absolute top-2.5 left-2.5 bg-gradient-to-r from-amber-600 to-amber-700 text-white text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider shadow">{{ $supportingProductThree['category'] ?? '' }}</span>
                    </div>
                    <div class="flex-1 flex flex-col justify-between py-1 h-full">
                        <div>
                            <span data-product-field="category"
                                class="text-[10px] font-bold text-gold-rose uppercase tracking-[0.2em]">{{ $supportingProductThree['category'] ?? '' }}</span>
                            <h3 data-product-field="name"
                                class="font-serif text-2xl xl:text-[27px] font-bold text-white leading-tight mt-0.5">
                                {{ $supportingProductThree['name'] ?? '' }}</h3>
                            <p data-product-field="description"
                                class="text-xs font-medium text-cream/75 mt-1 line-clamp-1">
                                {{ $supportingProductThree['description'] ?? '' }}</p>
                        </div>
                        <div class="flex items-center justify-end pt-2 border-t border-gold-rose/15">
                            <div
                                class="flex items-baseline gap-1 px-4 py-1.5 rounded-2xl bg-gradient-to-r from-plum-800 to-plum-950 border border-gold-rose/40 shadow-md">
                                <span class="text-sm font-bold text-gold-rose font-serif">Bs.</span>
                                <span data-product-field="price"
                                    class="font-serif text-3xl xl:text-4xl font-extrabold text-white leading-none">{{ isset($supportingProductThree['price']) ? number_format((float) $supportingProductThree['price'], 2, ',', '.') : '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="additional-menu-groups"></div>
    </main>
    <div id="empty-menu-message"
        class="{{ empty($menuSlides) ? 'flex' : 'hidden' }} flex-1 items-center justify-center px-8 text-center text-cream/80">
        <p class="rounded-3xl border border-gold-rose/30 bg-plum-950/70 px-10 py-8 text-xl font-semibold">
            Aún no hay productos disponibles en el menú digital.
        </p>
    </div>
    <!-- LIVE FOOTER TICKER / STATUS BAR (Vanguardista & Cálido) -->
    <footer
        class="menu-digital-footer w-full bg-plum-950/90 backdrop-blur-xl border-t border-gold-rose/25 px-8 py-3 flex items-center justify-between text-xs z-20 flex-shrink-0 shadow-2xl">
        <!-- Información del menú -->
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2 font-bold text-cream">
                <span class="material-symbols-outlined text-[18px] text-gold-rose">coffee</span>
                <span>Precios y disponibilidad <strong class="text-gold-rose font-black">actualizados
                        automáticamente</strong></span>
            </div>
            <div class="menu-footer-divider h-3.5 w-px bg-gold-rose/30"></div>
            <div class="flex items-center gap-2 text-cream/80 font-semibold">
                <span class="material-symbols-outlined text-[18px] text-pistachio-light">timer</span>
                <span>Productos publicados en el menú digital</span>
            </div>
            <div class="menu-footer-divider h-3.5 w-px bg-gold-rose/30"></div>
            <div class="flex items-center gap-2 text-cream/80">
                <span class="material-symbols-outlined text-[18px] text-pink-300">bakery_dining</span>
                <span>Productos seleccionados para el menú</span>
            </div>
        </div>
        <!-- Right Boutique Identity & Ticket -->
        <div class="flex items-center gap-6">
            <div
                class="flex items-center gap-2 bg-white/5 border border-gold-rose/30 px-3.5 py-1 rounded-xl backdrop-blur-sm">
                <span class="font-semibold text-cream/70">Catálogo de</span>
                <span class="font-mono font-black text-gold-rose text-sm">Azúcar y Yo</span>
            </div>
            <span class="font-extrabold text-gold-rose/80 uppercase tracking-[0.2em] text-[11px]">Azúcar y Yo •
                Boutique de Autor</span>
        </div>
    </footer>
    <!-- INTERACTIVE MENU, FULLSCREEN & TIME SCRIPT -->
    <script>
        (() => {
            const menuDataUrl = @json(route('menu.datos'));
            const fallbackImage = @json(asset('img/default.jpg'));
            const clock = document.getElementById('digital-clock');
            const fullscreenButton = document.getElementById('fullscreen-toggle-btn');
            const pills = document.getElementById('scene-pills');
            const showcase = document.getElementById('menu-showcase');
            const emptyMessage = document.getElementById('empty-menu-message');
            const additionalGroups = document.getElementById('additional-menu-groups');
            const firstGrid = showcase.querySelector('.menu-digital-grid');
            let slides = @json($menuSlides);
            let activeSlide = 0;
            let refreshInProgress = false;

            const updateClock = () => {
                if (clock) {
                    clock.textContent = new Intl.DateTimeFormat('es-BO', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: false
                    }).format(new Date());
                }
            };
            updateClock();
            window.setInterval(updateClock, 1000);

            const formatPrice = (price) => new Intl.NumberFormat('es-BO', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(Number(price) || 0);

            const setText = (id, value) => {
                const element = document.getElementById(id);
                if (element) element.textContent = value ?? '';
            };

            const setImage = (image, product) => {
                if (!image) return;
                image.src = product.image || fallbackImage;
                image.alt = product.name || '';
            };

            const updateCard = (card, product) => {
                card.classList.toggle('hidden', !product);
                if (!product) return;

                card.querySelectorAll('[data-product-field]').forEach((element) => {
                    const field = element.dataset.productField;
                    if (field === 'image') {
                        setImage(element, product);
                    } else if (field === 'price') {
                        element.textContent = formatPrice(product.price);
                    } else {
                        element.textContent = product[field] ?? '';
                    }
                });
            };

            const updateHero = (grid, product) => {
                grid.querySelectorAll('[data-hero-field]').forEach((element) => {
                    const field = element.dataset.heroField;
                    if (field === 'image') {
                        setImage(element, product || {});
                    } else if (field === 'price') {
                        element.textContent = product ? formatPrice(product.price) : '';
                    } else if (field === 'featured-label') {
                        element.textContent = product?.featured
                            ? 'Selección destacada'
                            : (product?.category || 'Especialidades');
                    } else {
                        element.textContent = product?.[field] ?? '';
                    }
                });
            };

            const renderGroup = (grid, products) => {
                updateHero(grid, products[0]);
                [1, 2, 3].forEach((index) => {
                    const card = grid.querySelector(`[data-menu-card="${index}"]`);
                    if (card) updateCard(card, products[index]);
                });
            };

            const updateNavigation = () => {
                pills.replaceChildren();
                slides.forEach((slide, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className =
                        'scene-pill px-5 py-2 rounded-full font-bold text-xs tracking-wider uppercase transition-all duration-300 flex items-center gap-2';
                    if (index === activeSlide) {
                        button.classList.add('bg-gradient-to-r', 'from-gold-rose', 'to-gold-accent',
                            'text-plum-950', 'shadow-lg');
                    } else {
                        button.classList.add('text-cream/80', 'hover:text-white', 'hover:bg-white/10');
                    }
                    const icon = document.createElement('span');
                    icon.className = 'material-symbols-outlined text-[16px]';
                    icon.textContent = slide.icon || 'bakery_dining';
                    button.append(icon, document.createTextNode(slide.label || 'Productos'));
                    button.addEventListener('click', () => {
                        activeSlide = index;
                        render();
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    });
                    pills.append(button);
                });
                pills.classList.toggle('hidden', slides.length < 2);
            };

            const render = () => {
                const productIds = new Set(slides.flatMap((slide) => slide.products.map((product) => product.id)));
                const availableProducts = productIds.size;
                setText('available-products-count', `${availableProducts} productos`);
                setText('menu-status', availableProducts ? 'Menú disponible' : 'Menú sin productos');
                if (showcase) showcase.classList.toggle('hidden', availableProducts === 0);
                if (emptyMessage) {
                    emptyMessage.classList.toggle('hidden', availableProducts > 0);
                    emptyMessage.classList.toggle('flex', availableProducts === 0);
                }
                if (availableProducts === 0) {
                    pills.replaceChildren();
                    return;
                }

                activeSlide = Math.min(activeSlide, slides.length - 1);
                const slide = slides[activeSlide];
                const groups = [];
                for (let index = 0; index < slide.products.length; index += 4) {
                    groups.push(slide.products.slice(index, index + 4));
                }

                renderGroup(firstGrid, groups[0] || []);
                additionalGroups.replaceChildren();
                const cardTemplate = firstGrid.querySelector('[data-menu-card="1"]');
                slide.products.slice(4).forEach((product) => {
                    const card = cardTemplate.cloneNode(true);
                    card.classList.add('additional-menu-card');
                    card.querySelectorAll('[id]').forEach((element) => element.removeAttribute('id'));
                    updateCard(card, product);
                    additionalGroups.append(card);
                });
                updateNavigation();
            };

            const refreshMenu = async () => {
                if (refreshInProgress) return;
                refreshInProgress = true;
                try {
                    const response = await fetch(menuDataUrl, {
                        headers: {
                            Accept: 'application/json'
                        },
                        cache: 'no-store'
                    });
                    if (!response.ok) throw new Error(`Menu feed returned HTTP ${response.status}`);
                    const data = await response.json();
                    if (!Array.isArray(data.slides)) throw new Error('Menu feed response has no slides array');
                    const currentSlideKey = slides[activeSlide]?.key;
                    slides = data.slides.filter((slide) => Array.isArray(slide.products) && slide.products.length > 0);
                    const updatedSlideIndex = slides.findIndex((slide) => slide.key === currentSlideKey);
                    activeSlide = updatedSlideIndex >= 0 ? updatedSlideIndex : 0;
                    render();
                } catch (error) {
                    console.error('No se pudo actualizar el menú digital.', error);
                } finally {
                    refreshInProgress = false;
                }
            };

            const fullscreenIcon = document.getElementById('fs-icon');
            const fullscreenText = document.getElementById('fs-text');
            const toggleFullscreen = async () => {
                try {
                    if (!document.fullscreenElement) {
                        await document.documentElement.requestFullscreen();
                    } else {
                        await document.exitFullscreen();
                    }
                } catch (error) {
                    console.error('No se pudo cambiar el modo de pantalla completa.', error);
                }
            };
            const updateFullscreenButton = () => {
                const fullscreen = Boolean(document.fullscreenElement);
                if (fullscreenIcon) fullscreenIcon.textContent = fullscreen ? 'fullscreen_exit' : 'fullscreen';
                if (fullscreenText) fullscreenText.textContent = fullscreen ? 'Salir Pantalla' : 'Modo Pantalla Completa';
            };

            if (fullscreenButton) fullscreenButton.addEventListener('click', toggleFullscreen);
            document.addEventListener('fullscreenchange', updateFullscreenButton);
            render();
            refreshMenu();
            window.setInterval(refreshMenu, 30000);
        })();
    </script>
</body>

</html>
