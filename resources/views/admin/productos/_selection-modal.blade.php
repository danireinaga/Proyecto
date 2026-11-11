@php
    $selectionModalId = $modalId;
    $selectionField = $field;
    $selectionTitle = $title;
    $selectionDescription = $description;
@endphp

<form action="{{ $selectionRoute }}" method="POST">
    @csrf
    @method('PUT')
    <div class="modal fade product-selection-modal" id="{{ $selectionModalId }}" tabindex="-1" role="dialog"
        aria-labelledby="{{ $selectionModalId }}Title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <span class="product-selection-eyebrow">SELECCIÓN DE PRODUCTOS</span>
                        <h2 class="modal-title h4 mb-1" id="{{ $selectionModalId }}Title">{{ $selectionTitle }}</h2>
                        <p class="text-muted mb-0">{{ $selectionDescription }}</p>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if ($todosProductos->isNotEmpty())
                        <div class="product-selection-toolbar">
                            <label class="product-selection-search">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                <input type="search" class="form-control js-product-selection-search"
                                    placeholder="Buscar por producto o categoría..." autocomplete="off"
                                    aria-label="Buscar productos">
                            </label>
                            <div class="product-selection-bulk-actions">
                                <span class="product-selection-count"><strong class="js-selected-count">0</strong> seleccionados</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-toggle-visible">
                                    Seleccionar visibles
                                </button>
                            </div>
                        </div>

                        <div class="product-selection-grid">
                            @foreach ($todosProductos as $producto)
                                <label class="product-selection-card {{ $producto->{$selectionField} ? 'is-selected' : '' }}"
                                    data-product-card>
                                    <input type="checkbox" name="product_ids[]" value="{{ $producto->id }}"
                                        class="js-product-selection-checkbox"
                                        {{ $producto->{$selectionField} ? 'checked' : '' }}>
                                    <span class="product-selection-check" aria-hidden="true">
                                        <i class="fas fa-check"></i>
                                    </span>
                                    <span class="product-selection-image">
                                        <img src="{{ $producto->imagen ? asset($producto->imagen) : asset('img/default.jpg') }}"
                                            alt="" loading="lazy">
                                    </span>
                                    <span class="product-selection-details">
                                        <strong class="product-selection-name">{{ $producto->nombre }}</strong>
                                        <span class="product-selection-category">{{ $producto->category?->nombre ?? 'Sin categoría' }}</span>
                                        <span class="product-selection-price">Bs. {{ number_format((float) $producto->precio, 2, ',', '.') }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <p class="product-selection-no-results js-no-selection-results d-none">
                            No se encontraron productos para esa búsqueda.
                        </p>
                    @else
                        <div class="product-selection-empty">
                            <i class="fas fa-cookie-bite" aria-hidden="true"></i>
                            <strong>Aún no hay productos registrados</strong>
                            <span>Primero crea productos desde el módulo Productos.</span>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <span class="small text-muted mr-auto">Los productos desmarcados dejarán de mostrarse aquí. El stock no cambia.</span>
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-selection-save" {{ $todosProductos->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-save mr-1"></i> Guardar selección
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@push('css')
    <style>
        .product-selection-modal .modal-content { overflow:hidden; border:0; border-radius:20px; box-shadow:0 24px 70px rgba(37,20,40,.22); }
        .product-selection-modal .modal-header { align-items:flex-start; padding:1.35rem 1.5rem; border-bottom:1px solid #f0ebf0; }
        .product-selection-modal .modal-body { max-height:min(70vh,760px); overflow-y:auto; padding:1.2rem 1.5rem; background:#fbf9fb; }
        .product-selection-modal .modal-footer { flex-wrap:wrap; padding:1rem 1.5rem; border-top:1px solid #f0ebf0; }
        .product-selection-eyebrow { display:block; margin-bottom:.3rem; color:#9b6e91; font-size:.66rem; font-weight:800; letter-spacing:.14em; }
        .product-selection-toolbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.1rem; }
        .product-selection-search { display:flex; flex:1; align-items:center; gap:.65rem; max-width:540px; margin:0; padding:0 .9rem; color:#8d788b; background:#fff; border:1px solid #e8e0e8; border-radius:12px; }
        .product-selection-search .form-control { height:42px; padding-left:0; border:0; box-shadow:none; }
        .product-selection-bulk-actions { display:flex; align-items:center; justify-content:flex-end; gap:.8rem; }
        .product-selection-count { color:#716675; font-size:.85rem; white-space:nowrap; }
        .product-selection-count strong { color:#713e67; }
        .product-selection-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:1rem; }
        .product-selection-card { position:relative; display:flex; flex-direction:column; min-width:0; margin:0; overflow:hidden; cursor:pointer; background:#fff; border:1px solid #ebe5eb; border-radius:15px; box-shadow:0 5px 16px rgba(45,28,45,.045); transition:transform .18s ease,border-color .18s ease,box-shadow .18s ease; }
        .product-selection-card:hover { transform:translateY(-2px); border-color:#c79abb; box-shadow:0 10px 24px rgba(61,35,59,.11); }
        .product-selection-card.is-selected { border:2px solid #825276; box-shadow:0 0 0 3px rgba(130,82,118,.1); }
        .product-selection-card>input { position:absolute; z-index:2; top:12px; left:12px; width:19px; height:19px; margin:0; accent-color:#713e67; }
        .product-selection-check { position:absolute; z-index:1; top:10px; right:10px; display:grid; width:28px; height:28px; place-items:center; color:#fff; background:#713e67; border:2px solid #fff; border-radius:50%; opacity:0; transform:scale(.8); transition:opacity .15s ease,transform .15s ease; }
        .product-selection-card.is-selected .product-selection-check { opacity:1; transform:scale(1); }
        .product-selection-check i { font-size:.75rem; }
        .product-selection-image { display:block; height:150px; overflow:hidden; background:linear-gradient(135deg,#f7edf2,#eee8f5); }
        .product-selection-image img { width:100%; height:100%; object-fit:cover; transition:transform .3s ease; }
        .product-selection-card:hover .product-selection-image img { transform:scale(1.04); }
        .product-selection-details { display:flex; flex:1; flex-direction:column; align-items:flex-start; gap:.28rem; padding:.85rem .95rem 1rem; }
        .product-selection-name { width:100%; overflow:hidden; color:#332b35; font-size:.96rem; text-overflow:ellipsis; white-space:nowrap; }
        .product-selection-category { color:#877b89; font-size:.78rem; }
        .product-selection-price { margin-top:.3rem; color:#713e67; font-weight:800; }
        .product-selection-empty,.product-selection-no-results { display:flex; flex-direction:column; align-items:center; gap:.5rem; padding:3rem 1rem; color:#817985; text-align:center; }
        .product-selection-empty i { margin-bottom:.35rem; color:#9b6e91; font-size:2rem; }
        .product-selection-empty strong { color:#413543; }
        .btn-selection-save { color:#fff; background:#713e67; border-color:#713e67; }
        .btn-selection-save:hover { color:#fff; background:#5d3155; border-color:#5d3155; }
        @media(max-width:767.98px) { .product-selection-toolbar,.product-selection-bulk-actions { align-items:stretch; flex-direction:column; } .product-selection-search { max-width:none; } .product-selection-bulk-actions { align-items:flex-start; } .product-selection-grid { grid-template-columns:repeat(auto-fill,minmax(155px,1fr)); gap:.7rem; } .product-selection-image { height:120px; } .product-selection-modal .modal-footer>.small { width:100%; margin:0 0 .4rem; } }
    </style>
@endpush

@push('js')
    <script>
        (() => {
            document.querySelectorAll('.product-selection-modal').forEach((modal) => {
                const search = modal.querySelector('.js-product-selection-search');
                const cards = Array.from(modal.querySelectorAll('[data-product-card]'));
                const counter = modal.querySelector('.js-selected-count');
                const noResults = modal.querySelector('.js-no-selection-results');
                const toggleVisible = modal.querySelector('.js-toggle-visible');

                const update = () => {
                    const term = (search?.value || '').trim().toLocaleLowerCase();
                    let visibleCount = 0;
                    let selectedCount = 0;

                    cards.forEach((card) => {
                        const matches = card.textContent.toLocaleLowerCase().includes(term);
                        card.classList.toggle('d-none', !matches);
                        if (matches) visibleCount++;

                        const checkbox = card.querySelector('.js-product-selection-checkbox');
                        card.classList.toggle('is-selected', checkbox.checked);
                        if (checkbox.checked) selectedCount++;
                    });

                    if (counter) counter.textContent = selectedCount;
                    if (noResults) noResults.classList.toggle('d-none', visibleCount > 0);
                    if (toggleVisible) {
                        const visibleCards = cards.filter((card) => !card.classList.contains('d-none'));
                        const allVisibleSelected = visibleCards.length > 0
                            && visibleCards.every((card) => card.querySelector('.js-product-selection-checkbox').checked);
                        toggleVisible.textContent = allVisibleSelected ? 'Quitar selección visible' : 'Seleccionar visibles';
                    }
                };

                search?.addEventListener('input', update);
                modal.querySelectorAll('.js-product-selection-checkbox').forEach((checkbox) => {
                    checkbox.addEventListener('change', update);
                });
                toggleVisible?.addEventListener('click', () => {
                    const visibleCards = cards.filter((card) => !card.classList.contains('d-none'));
                    const allVisibleSelected = visibleCards.length > 0
                        && visibleCards.every((card) => card.querySelector('.js-product-selection-checkbox').checked);
                    visibleCards.forEach((card) => {
                        card.querySelector('.js-product-selection-checkbox').checked = !allVisibleSelected;
                    });
                    update();
                });

                update();
            });
        })();
    </script>
@endpush
