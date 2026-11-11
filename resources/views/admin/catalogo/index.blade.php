@extends('adminlte::page')

@section('title', 'Catálogo')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-7">
                <span class="catalog-admin-eyebrow">VITRINA DE PRODUCTOS</span>
                <h1 class="m-0 font-weight-bold text-dark">Catálogo</h1>
                <p class="text-muted text-sm mb-0">Selecciona qué productos pueden verse en el catálogo público.</p>
            </div>
            <div class="col-sm-5 text-end d-flex justify-content-end flex-wrap catalog-admin-actions">
                <button type="button" class="btn btn-outline-secondary shadow-sm rounded-pill px-4 mr-2"
                    data-toggle="modal" data-target="#catalogSelectionModal">
                    <i class="fas fa-th-large mr-1"></i> Seleccionar productos
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid px-0">
        @include('admin.productos._selection-modal', [
            'modalId' => 'catalogSelectionModal',
            'field' => 'estado',
            'title' => 'Productos visibles en el catálogo',
            'description' => 'Busca productos y marca los que quieres publicar. Los que ya están visibles aparecen seleccionados.',
            'selectionRoute' => route('admin.catalogo.selection'),
            'todosProductos' => $todosProductos,
        ])

        @if ($productos->isNotEmpty())
            <div class="catalog-products-heading">
                <div>
                    <h2>Productos actualmente en el catálogo</h2>
                    <p>Estos son los productos que los clientes pueden ver.</p>
                </div>
                <span class="catalog-products-count">{{ $productos->count() }} {{ \Illuminate\Support\Str::plural('producto', $productos->count()) }}</span>
            </div>

            <div class="row">
                @foreach ($productos as $producto)
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-4">
                        <article class="catalog-product-card">
                            <div class="catalog-product-image">
                                <img src="{{ $producto->imagen ? asset($producto->imagen) : asset('img/default.jpg') }}"
                                    alt="{{ $producto->nombre }}" loading="lazy">
                                <span class="catalog-visible-badge"><i class="fas fa-eye mr-1"></i> Visible</span>
                                @if ($producto->destacado)
                                    <span class="catalog-featured-badge"><i class="fas fa-star mr-1"></i> Destacado</span>
                                @endif
                            </div>
                            <div class="catalog-product-body">
                                <span class="catalog-product-category">{{ $producto->category?->nombre ?? 'Sin categoría' }}</span>
                                <h3>{{ $producto->nombre }}</h3>
                                <p class="catalog-product-description">
                                    {{ $producto->descripcion ?: 'Sin descripción disponible.' }}
                                </p>
                                <div class="catalog-product-footer">
                                    <strong>Bs. {{ number_format((float) $producto->precio, 2, ',', '.') }}</strong>
                                    <a href="{{ route('admin.productos.edit', $producto) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-pen mr-1"></i> Editar
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @else
            <div class="catalog-empty-state">
                <span><i class="fas fa-store"></i></span>
                <h2>Aún no hay productos publicados</h2>
                <p>Selecciona los productos que quieras mostrar en el catálogo.</p>
                @if ($todosProductos->isNotEmpty())
                    <button type="button" class="btn btn-catalog-primary" data-toggle="modal" data-target="#catalogSelectionModal">
                        Seleccionar productos
                    </button>
                @else
                    <a href="{{ route('admin.productos.create') }}" class="btn btn-catalog-primary">Crear primer producto</a>
                @endif
            </div>
        @endif
    </div>
@endsection

@push('css')
    <style>
        .catalog-admin-eyebrow { display:block; margin-bottom:.25rem; color:#9b6e91; font-size:.68rem; font-weight:800; letter-spacing:.14em; }
        .catalog-admin-actions { gap:.45rem; }
        .catalog-products-heading { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin:.6rem 0 1.15rem; }
        .catalog-products-heading h2 { margin:0; color:#302332; font-size:1.2rem; font-weight:750; }
        .catalog-products-heading p { margin:.25rem 0 0; color:#817985; }
        .catalog-products-count { padding:.42rem .8rem; color:#713e67; background:#f1e8f0; border-radius:99px; font-size:.8rem; font-weight:800; white-space:nowrap; }
        .catalog-product-card { height:100%; overflow:hidden; background:#fff; border:1px solid #eee8ee; border-radius:17px; box-shadow:0 6px 22px rgba(52,34,50,.06); transition:transform .2s ease,box-shadow .2s ease; }
        .catalog-product-card:hover { transform:translateY(-3px); box-shadow:0 12px 28px rgba(52,34,50,.12); }
        .catalog-product-image { position:relative; height:190px; overflow:hidden; background:linear-gradient(135deg,#f8e8ec,#f3e8f6); }
        .catalog-product-image img { width:100%; height:100%; object-fit:cover; transition:transform .35s ease; }
        .catalog-product-card:hover .catalog-product-image img { transform:scale(1.04); }
        .catalog-visible-badge,.catalog-featured-badge { position:absolute; top:.75rem; display:inline-flex; align-items:center; padding:.38rem .62rem; border-radius:99px; font-size:.7rem; font-weight:800; box-shadow:0 3px 12px rgba(0,0,0,.12); }
        .catalog-visible-badge { left:.75rem; color:#266a47; background:rgba(239,250,243,.95); }
        .catalog-featured-badge { right:.75rem; color:#fff; background:rgba(113,62,103,.94); }
        .catalog-product-body { display:flex; min-height:205px; flex-direction:column; padding:1rem 1.05rem; }
        .catalog-product-category { color:#9b6e91; font-size:.7rem; font-weight:800; letter-spacing:.09em; text-transform:uppercase; }
        .catalog-product-body h3 { overflow:hidden; margin:.28rem 0 .45rem; color:#302332; font-size:1.05rem; font-weight:750; text-overflow:ellipsis; white-space:nowrap; }
        .catalog-product-description { display:-webkit-box; overflow:hidden; flex:1; margin:0 0 .8rem; color:#817985; font-size:.86rem; -webkit-box-orient:vertical; -webkit-line-clamp:2; }
        .catalog-product-footer { display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding-top:.7rem; border-top:1px solid #f0edf0; }
        .catalog-product-footer>strong { color:#713e67; font-size:1.08rem; }
        .catalog-empty-state { padding:4rem 1rem; text-align:center; background:#fff; border:1px solid #eee8ee; border-radius:18px; }
        .catalog-empty-state>span { display:grid; width:70px; height:70px; place-items:center; margin:0 auto 1rem; color:#713e67; background:#f1e8f0; border-radius:22px; font-size:1.7rem; }
        .catalog-empty-state h2 { color:#302332; font-size:1.25rem; font-weight:750; }
        .catalog-empty-state p { color:#817985; }
        .btn-catalog-primary { color:#fff; background:#713e67; border-color:#713e67; }
        .btn-catalog-primary:hover { color:#fff; background:#5d3155; border-color:#5d3155; }
        @media(max-width:767.98px) { .catalog-admin-actions { justify-content:flex-start!important; margin-top:.8rem; } .catalog-products-heading { align-items:flex-start; flex-direction:column; } }
    </style>
@endpush