@extends('adminlte::page')

@section('title', 'Menú Digital')

@section('content_header')
    <div class="menu-admin-heading">
        <div>
            <span class="menu-eyebrow">EXPERIENCIA EN TIENDA</span>
            <h1>Menú Digital</h1>
            <p>Selecciona qué productos aparecen en el menú y en Caja. El menú es informativo: no registra pedidos ni modifica existencias.</p>
        </div>
        <div class="menu-heading-actions">
            <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#menuSelectionModal">
                <i class="fas fa-list-alt mr-1"></i> Añadir menú
            </button>
            <a href="{{ $publicMenuUrl }}" target="_blank" rel="noopener" class="btn btn-menu-preview">
                <i class="fas fa-external-link-alt mr-1"></i> Ver pantalla del cliente
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid px-0">
        <section class="menu-qr-card" id="menu-qr-print">
            <div class="menu-qr-copy">
                <span class="menu-eyebrow">ACCESO PARA CLIENTES</span>
                <h2>Comparte tu menú con un QR</h2>
                <p>Los clientes pueden escanear este código con la cámara de su teléfono para abrir el menú digital.</p>
                <a class="menu-qr-url" href="{{ $customerMenuUrl }}" target="_blank" rel="noopener">{{ $customerMenuUrl }}</a>
                <button class="btn btn-menu-preview menu-qr-print-button" type="button" onclick="window.print()">
                    <i class="fas fa-print mr-1"></i> Imprimir QR
                </button>
            </div>
            <div class="menu-qr-code" id="menu-qr-code" data-menu-url="{{ $customerMenuUrl }}"
                role="img" aria-label="Código QR para abrir el menú digital">
                <span class="text-muted">Generando código QR...</span>
            </div>
        </section>

        @include('admin.productos._selection-modal', [
            'modalId' => 'menuSelectionModal',
            'field' => 'en_menu_digital',
            'title' => 'Productos visibles en el Menú Digital',
            'description' => 'Busca productos y marca los que quieres mostrar. Los que ya están visibles aparecen seleccionados.',
            'selectionRoute' => route('admin.menu-digital.selection'),
            'todosProductos' => $todosProductos,
        ])

        @forelse ($productos as $categoryId => $productsInCategory)
            @php($category = $productsInCategory->first()->category)
            <details class="menu-category-block">
                <summary class="menu-category-heading">
                    <div>
                        <span class="menu-eyebrow">CATEGORÍA</span>
                        <h2>{{ $category?->nombre ?? 'Sin categoría' }}</h2>
                    </div>
                    <div class="menu-category-meta">
                        <span class="badge badge-light">{{ $productsInCategory->count() }} productos</span>
                        <i class="fas fa-chevron-down menu-category-chevron" aria-hidden="true"></i>
                    </div>
                </summary>
                <div class="table-responsive">
                    <table class="table menu-product-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Precio</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($productsInCategory as $producto)
                                <tr>
                                    <td>
                                        <div class="menu-product-cell">
                                            <div class="menu-product-thumb">
                                                @if ($producto->imagen)
                                                    <img src="{{ asset($producto->imagen) }}" alt="" loading="lazy">
                                                @else
                                                    <i class="fas fa-cookie-bite"></i>
                                                @endif
                                            </div>
                                            <div class="min-width-0">
                                                <strong class="d-block text-truncate">{{ $producto->nombre }}</strong>
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($producto->descripcion ?: 'Producto artesanal', 68) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="font-weight-bold">Bs. {{ number_format((float) $producto->precio, 2, ',', '.') }}</td>
                                    <td class="text-right text-nowrap">
                                        <form action="{{ route('admin.menu-digital.reorder', $producto) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="direccion" value="arriba">
                                            <button class="btn btn-sm btn-outline-secondary" title="Mover arriba" aria-label="Mover {{ $producto->nombre }} arriba"><i class="fas fa-arrow-up"></i></button>
                                        </form>
                                        <form action="{{ route('admin.menu-digital.reorder', $producto) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="direccion" value="abajo">
                                            <button class="btn btn-sm btn-outline-secondary" title="Mover abajo" aria-label="Mover {{ $producto->nombre }} abajo"><i class="fas fa-arrow-down"></i></button>
                                        </form>
                                        @can('admin.productos.ver')
                                            <a href="{{ route('admin.productos.edit', $producto) }}" class="btn btn-sm btn-outline-primary" title="Editar precio, foto y producto"><i class="fas fa-pen"></i><span class="d-none d-lg-inline ml-1">Editar</span></a>
                                        @endcan
                                        <form action="{{ route('admin.menu-digital.toggle', $producto) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm {{ $producto->en_menu_digital ? 'btn-outline-danger' : 'btn-menu-toggle' }}">
                                                <i class="fas {{ $producto->en_menu_digital ? 'fa-eye-slash' : 'fa-eye' }} mr-1"></i>
                                                {{ $producto->en_menu_digital ? 'Ocultar' : 'Mostrar' }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        @empty
            <div class="menu-empty-state">
                <span><i class="fas fa-cookie-bite"></i></span>
                <h2>Aún no hay productos en el menú</h2>
                <p>Usa “Añadir menú” para seleccionar productos ya registrados en el sistema.</p>
            </div>
        @endforelse
    </div>
@stop

@push('css')
    <style>
        .menu-admin-heading { display:flex; align-items:center; justify-content:space-between; gap:1.25rem; margin-bottom:1.2rem; }
        .menu-admin-heading h1 { margin:0; font-weight:700; color:#302332; }
        .menu-admin-heading p { margin:.35rem 0 0; color:#77717a; }
        .menu-eyebrow { display:block; color:#9b6e91; font-size:.68rem; font-weight:800; letter-spacing:.14em; margin-bottom:.3rem; }
        .menu-heading-actions { display:flex; justify-content:flex-end; gap:.55rem; flex-wrap:wrap; margin-left:auto; }
        .btn-menu-preview,.btn-menu-toggle { background:#713e67; border-color:#713e67; color:#fff; }
        .btn-menu-preview:hover,.btn-menu-toggle:hover { background:#5d3155; border-color:#5d3155; color:#fff; }
        .menu-qr-card { display:flex; align-items:center; justify-content:space-between; gap:2rem; padding:1.4rem; margin:0 0 1.4rem; background:#fff; border:1px solid #f0edf0; border-radius:14px; box-shadow:0 5px 18px rgba(52,34,50,.05); }
        .menu-qr-copy { min-width:0; }
        .menu-qr-copy h2 { margin:0; color:#332b35; font-size:1.2rem; font-weight:700; }
        .menu-qr-copy p { margin:.4rem 0; color:#817985; }
        .menu-qr-url { display:block; overflow-wrap:anywhere; color:#713e67; font-size:.85rem; }
        .menu-qr-print-button { margin-top:.8rem; }
        .menu-qr-code { display:grid; place-items:center; flex:0 0 180px; width:180px; height:180px; padding:10px; background:#fff; border:1px solid #f0edf0; border-radius:12px; }
        .menu-qr-code img,.menu-qr-code canvas { display:block; max-width:100%; }
        .menu-category-block { overflow:hidden; margin-bottom:1.25rem; background:#fff; border:1px solid #f0edf0; border-radius:14px; box-shadow:0 5px 18px rgba(52,34,50,.04); }
        .menu-category-heading { display:flex; align-items:center; justify-content:space-between; padding:1rem 1.2rem; border-bottom:1px solid #f0edf0; cursor:pointer; list-style:none; }
        .menu-category-heading::-webkit-details-marker { display:none; }
        .menu-category-meta { display:flex; align-items:center; gap:.75rem; }
        .menu-category-chevron { color:#817985; transition:transform .2s ease; }
        .menu-category-block:not([open]) .menu-category-heading { border-bottom:0; }
        .menu-category-block:not([open]) .menu-category-chevron { transform:rotate(-90deg); }
        .menu-category-heading h2 { margin:0; color:#332b35; font-size:1.1rem; font-weight:700; }
        .menu-product-table { margin:0; min-width:850px; }
        .menu-product-table thead th { padding:.8rem 1rem; color:#817985; background:#fcfafc; border-top:0; border-bottom:1px solid #f0edf0; font-size:.74rem; text-transform:uppercase; letter-spacing:.06em; }
        .menu-product-table td { padding:.8rem 1rem; vertical-align:middle; border-color:#f3f0f3; }
        .menu-product-cell { display:flex; align-items:center; gap:.8rem; min-width:260px; }
        .menu-product-thumb { display:grid; place-items:center; flex:0 0 48px; width:48px; height:48px; overflow:hidden; border-radius:12px; color:#8b5c80; background:linear-gradient(135deg,#f8e8ec,#f3e8f6); }
        .menu-product-thumb img { width:100%; height:100%; object-fit:cover; }
        .menu-empty-state { padding:4rem 1rem; text-align:center; background:#fff; border-radius:14px; }
        .menu-empty-state>span { display:grid; place-items:center; width:72px; height:72px; margin:0 auto 1rem; color:#713e67; background:#f1e8f0; border-radius:22px; font-size:1.8rem; }
        .menu-empty-state h2 { color:#332b35; font-size:1.3rem; font-weight:700; } .menu-empty-state p { color:#817985; }
        .min-width-0 { min-width:0; }
        @media(max-width:767.98px) { .menu-admin-heading { align-items:flex-start; flex-direction:column; } .menu-heading-actions { justify-content:flex-start; margin-left:0; } .menu-qr-card { align-items:flex-start; flex-direction:column; } }
        @media print {
            body * { visibility:hidden !important; }
            #menu-qr-print,#menu-qr-print * { visibility:visible !important; }
            #menu-qr-print { position:absolute; inset:0 auto auto 0; width:100%; display:flex; flex-direction:column; align-items:center; text-align:center; border:0; box-shadow:none; }
            #menu-qr-print .menu-qr-code { flex-basis:260px; width:260px; height:260px; margin-top:1rem; }
            #menu-qr-print .menu-qr-print-button { display:none; }
        }
    </style>
@endpush

@push('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        (() => {
            const container = document.getElementById('menu-qr-code');
            if (!container) return;
            if (typeof QRCode === 'undefined') {
                container.textContent = 'No se pudo cargar el generador del código QR. Verifica tu conexión e inténtalo de nuevo.';
                console.error('No se pudo cargar QRCode.js para el menú digital.');
                return;
            }

            const menuUrl = container.dataset.menuUrl;
            const customerLink = document.querySelector('.menu-qr-url');
            if (customerLink) {
                customerLink.href = menuUrl;
                customerLink.textContent = menuUrl;
            }

            container.replaceChildren();
            try {
                new QRCode(container, {
                    text: menuUrl,
                    width: 160,
                    height: 160,
                    correctLevel: QRCode.CorrectLevel.H,
                });
            } catch (error) {
                container.textContent = 'No se pudo generar el código QR.';
                console.error('No se pudo generar el código QR del menú digital.', error);
            }
        })();
    </script>
@endpush
