@extends('adminlte::page')

@section('adminlte_css_pre')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('title', 'Caja - Menú Digital')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-white rounded shadow-sm border-left border-primary"
        style="border-left-color: #6c3461 !important; border-width: 4px !important;">
        <h4 class="font-weight-bold text-dark m-0 d-flex align-items-center">
            <span class="fa-stack fa-sm mr-2 text-purple" style="color: #6c3461;">
                <i class="fas fa-circle fa-stack-2x" style="opacity: 0.15;"></i>
                <i class="fas fa-cash-register fa-stack-1x"></i>
            </span>
            AZÚCAR Y YO
            <span class="badge ml-3 shadow-sm text-white"
                style="background-color: #6c3461; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.5px;">Mi
                Caja</span>
        </h4>
        @can('admin.pedidos.ver')
            <a href="{{ route('admin.pedidos.personalizados.index') }}"
                class="btn btn-sm font-weight-bold shadow-sm px-3 py-2 text-white"
                style="background: linear-gradient(135deg, #6c3461 0%, #4a2848 100%); border-radius: 20px; transition: all 0.3s;">
                <i class="fas fa-birthday-cake mr-1"></i> Pedidos personalizados
            </a>
        @endcan
       
    </div>
@endsection

@section('content')
    <style>
        /* Estilos personalizados y paleta temática lila / repostería */
        .pos-wrapper {
            background: linear-gradient(135deg, #f8f4f7 0%, #f0e6ec 100%);
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 0.5rem 1rem rgba(108, 52, 97, 0.08);
        }

        .btn-brand-primary {
            background: linear-gradient(135deg, #6c3461 0%, #512648 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 10px rgba(108, 52, 97, 0.25);
            transition: all 0.2s ease-in-out;
        }

        .btn-brand-primary:hover {
            background: linear-gradient(135deg, #512648 0%, #3a1d34 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(108, 52, 97, 0.35);
        }

        .category-pill {
            background-color: #ffffff;
            color: #512648;
            border: 1px solid #e2cdd9;
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .category-pill.active,
        .category-pill:hover {
            background: linear-gradient(135deg, #6c3461 0%, #512648 100%);
            color: white;
            border-color: #6c3461;
            box-shadow: 0 4px 10px rgba(108, 52, 97, 0.2);
            transform: translateY(-1px);
        }

        .product-card {
            border: none;
            border-radius: 12px;
            transition: all 0.25s ease;
            box-shadow: 0 3px 10px rgba(108, 52, 97, 0.05);
            background: white;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(108, 52, 97, 0.12);
        }

        .ticket-card-panel {
            background: white;
            border-radius: 12px;
            border-top: 4px solid #6c3461;
            box-shadow: 0 4px 15px rgba(108, 52, 97, 0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 75vh;
        }

        .cart-item-row {
            background-color: #fcf9fb;
            border: 1px solid #f2e6ee !important;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .cart-item-row:hover {
            background-color: #f7eff4;
        }

        /* Scrollbars estilizados estéticos */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1ebd-e;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #d4b8cc;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #b08aa3;
        }
    </style>

    <div class="container-fluid p-2 pos-wrapper">
        <div class="row">
            <!-- Panel Izquierdo: Catálogo de Productos y Filtros -->
            <div class="col-lg-8 pr-lg-3 mb-3 mb-lg-0">
                <!-- Barra de Filtros por Categoría con diseño atractivo -->
                <div class="card border-0 shadow-sm mb-3"
                    style="border-radius: 12px; background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(5px);">
                    <div class="card-body py-3 px-3">
                        <div class="d-flex flex-wrap align-items-center" style="gap: 8px;" id="category-filters">
                            <button class="category-pill active" data-filter="all">
                                <i class="fas fa-th-large mr-1"></i> Todos
                            </button>
                            @foreach ($categorias as $cat)
                                <button class="category-pill" data-filter="{{ $cat->id }}">
                                    <i class="fas fa-utensils mr-1"></i> {{ $cat->nombre }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Grilla de Productos Dinámica -->
                <div class="row" id="product-grid" style="max-height: 65vh; overflow-y: auto; padding-right: 4px;">
                    @forelse($productos as $producto)
                        <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 mb-3 product-item"
                            data-category="{{ $producto->category_id }}">
                            <div class="card product-card h-100">
                                <div class="position-relative"
                                    style="height: 130px; overflow: hidden; background: #f4eaed;">
                                    @if ($producto->imagen)
                                        <img src="{{ asset($producto->imagen) }}" class="w-100 h-100"
                                            alt="{{ $producto->nombre }}"
                                            style="object-fit: cover; transition: transform 0.3s;">
                                    @else
                                        <div
                                            class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                            <i class="fas fa-cookie-bite fa-2x" style="color: #d4b8cc;"></i>
                                        </div>
                                    @endif
                                    <span class="badge position-absolute shadow-sm"
                                        style="top: 8px; right: 8px; background-color: rgba(108, 52, 97, 0.85); color: white; font-size: 0.7rem;">
                                        Bs. {{ number_format($producto->precio, 2) }}
                                    </span>
                                </div>
                                <div class="card-body p-2.5 d-flex flex-column justify-content-between">
                                    <div>
                                        <h6 class="font-weight-bold text-dark mb-1"
                                            style="font-size: 0.9rem; line-height: 1.3;">{{ $producto->nombre }}</h6>
                                        <p class="text-muted mb-2" style="font-size: 0.72rem; line-height: 1.2;">
                                            {{ Str::limit($producto->descripcion, 35) }}</p>
                                    </div>
                                    <div class="mt-1">
                                        <button
                                            class="btn btn-sm btn-brand-primary btn-block py-1 font-weight-bold add-to-cart"
                                            data-id="{{ $producto->id }}" data-name="{{ $producto->nombre }}"
                                            data-price="{{ $producto->precio }}"
                                            style="border-radius: 6px; font-size: 0.8rem;">
                                            <i class="fas fa-plus mr-1"></i> Agregar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5">
                            <div class="p-4 bg-white rounded shadow-sm d-inline-block">
                                <i class="fas fa-cookie-bite fa-3x mb-3" style="color: #d4b8cc;"></i>
                                <h6 class="text-dark font-weight-bold">No hay productos disponibles</h6>
                                <p class="text-muted small mb-0">Asegúrate de registrar artículos en el catálogo general.
                                </p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Panel Derecho: Nota de Venta / Caja Profesional -->
            <div class="col-lg-4">
                <div class="ticket-card-panel p-3">
                    <div>
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="font-weight-bold m-0 text-dark">
                                <i class="fas fa-receipt mr-1 text-purple" style="color: #6c3461;"></i> Nota de Venta / Caja
                            </h6>
                            <span class="badge badge-pill badge-light border text-success font-weight-bold px-2 py-1">
                                <i class="fas fa-circle fa-xs mr-1 text-success"></i> Venta Directa
                            </span>
                        </div>
                        <div class="form-row mb-1">
                            <div class="form-group col-7 mb-2">
                                <label for="cliente_nombre" class="small font-weight-bold text-muted mb-1">Nombre del
                                    cliente *</label>
                                <input type="text" id="cliente_nombre" class="form-control form-control-sm"
                                    placeholder="Ej: Juan" style="border-radius: 8px;" autocomplete="off">
                            </div>
                            <div class="form-group col-5 mb-2">
                                <label for="cliente_apellido" class="small font-weight-bold text-muted mb-1">Apellido</label>
                                <input type="text" id="cliente_apellido" class="form-control form-control-sm"
                                    placeholder="Ej: Pérez" style="border-radius: 8px;" autocomplete="off">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="cliente_telefono" class="small font-weight-bold text-muted mb-1">Teléfono
                                (opcional)</label>
                            <input type="text" id="cliente_telefono" class="form-control form-control-sm"
                                placeholder="Ej: 70012345" style="border-radius: 8px;" autocomplete="off">
                        </div>

                        <!-- Lista de Carrito / Items Agregados -->
                        <div id="cart-items" style="max-height: 42vh; overflow-y: auto;" class="mb-2 pr-1">
                            <div class="text-center text-muted py-5" id="empty-cart-msg">
                                <div class="mb-2">
                                    <i class="fas fa-shopping-basket fa-3x" style="color: #ebd7e4;"></i>
                                </div>
                                <p class="small font-weight-medium text-muted">Toque un postre o bebida del catálogo para
                                    comenzar la venta</p>
                            </div>
                        </div>
                    </div>

                    <!-- Totales y Botones de Acción -->
                    <div class="pt-2">
                        <div class="p-3 mb-3 rounded" style="background-color: #f7eff4; border: 1px dashed #d4b8cc;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-secondary" style="font-size: 0.95rem;">Total a
                                    Pagar:</span>
                                <span class="font-weight-bold text-dark h4 m-0" id="cart-total"
                                    style="color: #512648 !important;">Bs. 0.00</span>
                            </div>
                        </div>

                        <button class="btn btn-brand-primary btn-block py-2 mb-2 font-weight-bold shadow-sm" id="btn-cobrar"
                            disabled style="border-radius: 8px;">
                            <i class="fas fa-print mr-2"></i> Cobrar e Imprimir Ficha
                        </button>
                        <button class="btn btn-outline-secondary btn-block btn-sm py-2 font-weight-medium" id="btn-cancelar"
                            style="border-radius: 8px; border-color: #dcdcdc;">
                            <i class="fas fa-trash-alt mr-1 text-danger"></i> Cancelar Venta Actual
                        </button>

                        {{-- Aviso de venta registrada con acceso a la ficha --}}
                        <div id="venta-ok" class="mt-2 d-none">
                            <div class="p-2 rounded" style="background: #eaf7ee; border: 1px solid #b7e0c4;">
                                <div class="d-flex align-items-center small font-weight-bold text-success mb-1">
                                    <i class="fas fa-check-circle mr-1"></i> Venta registrada
                                </div>
                                <div class="small text-dark">
                                    Ficha <span id="venta-ok-ficha" class="font-weight-bold"></span>
                                    &middot; Total Bs. <span id="venta-ok-total" class="font-weight-bold"></span>
                                </div>
                                <div class="small text-dark" id="venta-ok-vuelto-wrap">
                                    Vuelto Bs. <span id="venta-ok-vuelto" class="font-weight-bold"></span>
                                </div>
                                <a id="venta-ok-link" href="#" target="_blank" rel="noopener"
                                    class="btn btn-sm btn-block btn-outline-success mt-2 font-weight-bold">
                                    <i class="fas fa-print mr-1"></i> Ver / reimprimir ficha
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Observaciones/Notas del Producto -->
    <div class="modal fade" id="observacionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header py-2 text-white"
                    style="background: linear-gradient(135deg, #6c3461 0%, #512648 100%);">
                    <h6 class="modal-title font-weight-bold"><i class="fas fa-pen mr-1"></i> Personalizar Producto</h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body bg-light">
                    <p class="small font-weight-bold text-dark mb-2" id="modal-product-name">Producto</p>
                    <div class="form-group mb-0">
                        <label class="small font-weight-bold text-muted">Nota / Observación:</label>
                        <textarea class="form-control form-control-sm" id="product-note" rows="3"
                            placeholder="Ej. Sin crema, extra frutilla..." style="border-radius: 8px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-white">
                    <button type="button" class="btn btn-light btn-sm px-3" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-brand-primary btn-sm px-3" id="save-note-btn"
                        style="border-radius: 6px;">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cobro: forma de pago, efectivo recibido, descuento y propina -->
    <div class="modal fade" id="cobroModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header py-2 text-white"
                    style="background: linear-gradient(135deg, #6c3461 0%, #512648 100%);">
                    <h6 class="modal-title font-weight-bold"><i class="fas fa-cash-register mr-1"></i> Cobrar venta</h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body bg-light">
                    <div class="d-flex justify-content-between align-items-center p-2 mb-3 rounded"
                        style="background-color: #f7eff4; border: 1px dashed #d4b8cc;">
                        <span class="small font-weight-bold text-secondary">Total a pagar:</span>
                        <span class="font-weight-bold m-0" id="cobro-total" style="color: #512648; font-size: 1.3rem;">
                            Bs. 0.00</span>
                    </div>

                    <div class="form-group">
                        <label for="cobro-pago" class="small font-weight-bold text-dark">Forma de pago:</label>
                        <select id="cobro-pago" class="form-control form-control-sm" style="border-radius: 8px;">
                            @foreach ($formasPagoLabels as $clave => $etiqueta)
                                <option value="{{ $clave }}">{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="cobro-descuento" class="small font-weight-bold text-dark">Descuento (Bs.):</label>
                            <input type="number" min="0" step="0.50" id="cobro-descuento"
                                class="form-control form-control-sm" value="0" style="border-radius: 8px;">
                        </div>
                        <div class="form-group col-6">
                            <label for="cobro-propina" class="small font-weight-bold text-dark">Propina (Bs.):</label>
                            <input type="number" min="0" step="0.50" id="cobro-propina"
                                class="form-control form-control-sm" value="0" style="border-radius: 8px;">
                        </div>
                    </div>

                    <div class="form-group mb-0" id="cobro-efectivo-box">
                        <label for="cobro-recibido" class="small font-weight-bold text-dark">Efectivo recibido (Bs.):</label>
                        <input type="number" min="0" step="0.50" id="cobro-recibido"
                            class="form-control form-control-sm" style="border-radius: 8px;">
                        <div class="d-flex justify-content-between small mt-1">
                            <span class="text-muted">Vuelto a entregar:</span>
                            <span class="font-weight-bold text-success" id="cobro-vuelto">Bs. 0.00</span>
                        </div>
                    </div>

                    <div class="alert alert-danger py-2 px-3 mb-0 mt-3 d-none" id="cobro-error">
                        <small id="cobro-error-texto"></small>
                    </div>
                </div>
                <div class="modal-footer py-2 bg-white">
                    <button type="button" class="btn btn-light btn-sm px-3" data-dismiss="modal">Volver</button>
                    <button type="button" class="btn btn-brand-primary btn-sm px-3" id="btn-confirmar-cobro"
                        style="border-radius: 6px;">
                        <i class="fas fa-print mr-1"></i> Cobrar e Imprimir Ficha
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script interactivo de Caja -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let cart = [];
            let currentProductTemp = null;

            // Filtrado por categorías dinámico
            const filterBtns = document.querySelectorAll('.category-pill');
            const productItems = document.querySelectorAll('.product-item');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    let catId = this.getAttribute('data-filter');

                    productItems.forEach(item => {
                        if (catId === 'all' || item.getAttribute('data-category') ===
                            catId) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });

            // Agregar del catálogo con modal de notas
            document.querySelectorAll('.add-to-cart').forEach(btn => {
                btn.addEventListener('click', function() {
                    currentProductTemp = {
                        id: this.getAttribute('data-id'),
                        nombre: this.getAttribute('data-name'), // Clave corregida a 'nombre'
                        precio: parseFloat(this.getAttribute(
                            'data-price')), // Clave corregida a 'precio'
                        cantidad: 1, // Clave corregida a 'cantidad'
                        note: ''
                    };
                    document.getElementById('modal-product-name').innerText = currentProductTemp
                        .nombre + ' - Bs. ' + currentProductTemp.precio.toFixed(2);
                    document.getElementById('product-note').value = '';
                    $('#observacionModal').modal('show');
                });
            });

            document.getElementById('save-note-btn').addEventListener('click', function() {
                if (currentProductTemp) {
                    currentProductTemp.note = document.getElementById('product-note').value.trim();
                    let existing = cart.find(item => item.id === currentProductTemp.id && item.note ===
                        currentProductTemp.note && !item.is_custom);
                    if (existing) {
                        existing.cantidad += 1; // Actualizado a 'cantidad'
                    } else {
                        cart.push({
                            ...currentProductTemp
                        });
                    }
                    renderCart();
                    $('#observacionModal').modal('hide');
                }
            });

            // Renderizar ticket / carrito
            function renderCart() {
                const container = document.getElementById('cart-items');
                const totalEl = document.getElementById('cart-total');
                const cobrarBtn = document.getElementById('btn-cobrar');

                if (cart.length === 0) {
                    container.innerHTML = `
                    <div class="text-center text-muted py-5" id="empty-cart-msg">
                        <div class="mb-2">
                            <i class="fas fa-shopping-basket fa-3x" style="color: #ebd7e4;"></i>
                        </div>
                        <p class="small font-weight-medium text-muted">Toque un postre o bebida del catálogo para comenzar la venta</p>
                    </div>`;
                    totalEl.innerText = 'Bs. 0.00';
                    cobrarBtn.disabled = true;
                    document.getElementById('venta-ok').classList.add('d-none');
                    return;
                }

                let html = '';
                let total = 0;

                cart.forEach((item, index) => {
                    let subtotal = item.precio * item.cantidad;
                    total += subtotal;

                    html += `
                    <div class="card mb-2 p-2 cart-item-row shadow-none">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="font-weight-bold small text-dark">${item.nombre}</span>
                                <div class="text-muted" style="font-size: 0.72rem;">Bs. ${item.precio.toFixed(2)} x ${item.cantidad}</div>
                                ${item.note ? '<div class="text-danger font-italic small" style="font-size: 0.7rem;"><i class="fas fa-comment-alt mr-1"></i> ' + item.note + '</div>' : ''}
                            </div>
                            <div class="text-right">
                                <span class="font-weight-bold text-dark small">Bs. ${subtotal.toFixed(2)}</span>
                                <div class="mt-1 d-flex align-items-center justify-content-end" style="gap: 3px;">
                                    <button class="btn btn-xs btn-outline-secondary py-0 px-1 change-qty" data-index="${index}" data-delta="-1" style="font-size: 0.7rem;">-</button>
                                    <span class="small mx-1 font-weight-bold">${item.cantidad}</span>
                                    <button class="btn btn-xs btn-outline-secondary py-0 px-1 change-qty" data-index="${index}" data-delta="1" style="font-size: 0.7rem;">+</button>
                                    <button class="btn btn-xs text-danger py-0 px-1 remove-item ml-1" data-index="${index}"><i class="fas fa-trash"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                });

                container.innerHTML = html;
                totalEl.innerText = 'Bs. ' + total.toFixed(2);
                cobrarBtn.disabled = false;

                document.querySelectorAll('.change-qty').forEach(btn => {
                    btn.addEventListener('click', function() {
                        let idx = parseInt(this.getAttribute('data-index'));
                        let delta = parseInt(this.getAttribute('data-delta'));
                        cart[idx].cantidad += delta;
                        if (cart[idx].cantidad <= 0) {
                            cart.splice(idx, 1);
                        }
                        renderCart();
                    });
                });

                document.querySelectorAll('.remove-item').forEach(btn => {
                    btn.addEventListener('click', function() {
                        let idx = parseInt(this.getAttribute('data-index'));
                        cart.splice(idx, 1);
                        renderCart();
                    });
                });
            }

            document.getElementById('btn-cancelar').addEventListener('click', function() {
                window.appConfirm({
                    title: 'Cancelar venta',
                    message: '¿Desea cancelar la venta actual?',
                    confirmText: 'Cancelar venta',
                    destructive: true
                }).then(function(confirmed) {
                    if (!confirmed) return;
                    cart = [];
                    renderCart();
                    document.getElementById('venta-ok').classList.add('d-none');
                });
            });

            /* -----------------------------------------------------------------
             | Cobro: total, forma de pago y efectivo recibido
             |----------------------------------------------------------------*/

            const inpNombre = document.getElementById('cliente_nombre');
            const inpApellido = document.getElementById('cliente_apellido');
            const inpTelefono = document.getElementById('cliente_telefono');
            const inpPago = document.getElementById('cobro-pago');
            const inpRecibido = document.getElementById('cobro-recibido');
            const inpDescuento = document.getElementById('cobro-descuento');
            const inpPropina = document.getElementById('cobro-propina');
            const boxEfectivo = document.getElementById('cobro-efectivo-box');
            const outCobroTotal = document.getElementById('cobro-total');
            const outCobroVuelto = document.getElementById('cobro-vuelto');
            const boxError = document.getElementById('cobro-error');
            const txtError = document.getElementById('cobro-error-texto');
            const btnConfirmar = document.getElementById('btn-confirmar-cobro');

            const num = (el) => {
                let n = parseFloat(el.value);
                return isNaN(n) || n < 0 ? 0 : n;
            };

            const totalCarrito = () => cart.reduce(
                (acc, it) => acc + (Number(it.precio) || 0) * (Number(it.cantidad) || 0), 0);

            /** Total final del modal: subtotal - descuento + propina (nunca negativo). */
            function totalACobrar() {
                let total = totalCarrito() - Math.min(num(inpDescuento), totalCarrito()) + num(inpPropina);
                return Math.max(0, Math.round(total * 100) / 100);
            }

            function refrescarCobro() {
                let total = totalACobrar();
                let efectivo = inpPago.value === 'efectivo';
                let recibido = num(inpRecibido);
                let faltaEfectivo = efectivo && recibido < total;

                boxEfectivo.classList.toggle('d-none', !efectivo);
                outCobroTotal.textContent = 'Bs. ' + total.toFixed(2);

                let vuelto = efectivo ? Math.max(0, Math.round((recibido - total) * 100) / 100) : 0;
                outCobroVuelto.textContent = 'Bs. ' + vuelto.toFixed(2);
                outCobroVuelto.classList.toggle('text-danger', faltaEfectivo);
                outCobroVuelto.classList.toggle('text-success', !faltaEfectivo);

                btnConfirmar.disabled = faltaEfectivo;
            }

            function mostrarErrorCobro(mensaje) {
                txtError.textContent = mensaje;
                boxError.classList.remove('d-none');
            }

            [inpPago, inpRecibido, inpDescuento, inpPropina].forEach(el => {
                el.addEventListener('input', refrescarCobro);
                el.addEventListener('change', refrescarCobro);
            });

            // Al abrir el modal se precarga el total como efectivo recibido.
            document.getElementById('btn-cobrar').addEventListener('click', function() {
                if (cart.length === 0) return;

                if (!inpNombre.value.trim()) {
                    inpNombre.focus();
                    inpNombre.classList.add('is-invalid');
                    return;
                }
                inpNombre.classList.remove('is-invalid');

                inpDescuento.value = 0;
                inpPropina.value = 0;
                inpRecibido.value = totalCarrito().toFixed(2);
                boxError.classList.add('d-none');
                refrescarCobro();
                $('#cobroModal').modal('show');
            });

            // Enviar la venta. La ventana de la ficha se abre en el mismo clic
            // del usuario: abrirla tras el fetch la bloquearía el navegador.
            btnConfirmar.addEventListener('click', function() {
                if (cart.length === 0) {
                    $('#cobroModal').modal('hide');
                    return;
                }

                let total = totalACobrar();
                let efectivo = inpPago.value === 'efectivo';
                let recibido = efectivo ? num(inpRecibido) : 0;

                if (efectivo && recibido < total) {
                    mostrarErrorCobro('El efectivo recibido es menor al total a pagar (Bs. ' +
                        total.toFixed(2) + ').');
                    return;
                }

                btnConfirmar.disabled = true;
                boxError.classList.add('d-none');

                let popup = window.open('', 'fichaAzucarYyo',
                    'width=480,height=700,menubar=no,toolbar=no,location=no,scrollbars=yes');
                if (popup) {
                    popup.document.write(
                        '<p style="font-family:system-ui,sans-serif;padding:24px;text-align:center;">Generando ficha...</p>'
                    );
                }

                fetch('{{ route('admin.caja.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content') || '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            cliente_nombre: inpNombre.value.trim(),
                            cliente_apellido: inpApellido.value.trim() || null,
                            cliente_telefono: inpTelefono.value.trim() || null,
                            items: cart,
                            pago_tipo: inpPago.value,
                            pago_recibido: recibido,
                            descuento: num(inpDescuento),
                            propina: num(inpPropina)
                        })
                    })
                    .then(res => res.text().then(texto => {
                        let body;
                        try {
                            body = JSON.parse(texto);
                        } catch (e) {
                            body = {
                                success: false,
                                message: res.status === 419
                                    ? 'La sesion expiro. Recarga la pagina e intenta de nuevo.'
                                    : 'Respuesta inesperada del servidor (' + res.status + ').'
                            };
                        }
                        return { ok: res.ok, body: body };
                    }))
                    .then(({ ok, body }) => {
                        if (!ok) {
                            // Los errores de validación llegan en body.errors
                            let mensaje = body.message;
                            if (body.errors) {
                                mensaje = Object.values(body.errors).flat().join(' ');
                            }
                            if (popup) popup.close();
                            mostrarErrorCobro(mensaje || 'No se pudo registrar la venta.');
                            return;
                        }

                        if (!body.success) {
                            if (popup) popup.close();
                            mostrarErrorCobro(body.message || 'No se pudo registrar la venta.');
                            return;
                        }

                        // Mostrar la ficha en la ventana abierta; desde allí se puede imprimir.
                        let urlFicha = body.ficha_url;
                        if (popup) {
                            popup.location = urlFicha;
                        } else {
                            window.open(urlFicha, 'fichaAzucarYyo');
                        }

                        document.getElementById('venta-ok-ficha').textContent = body.numero_ficha;
                        document.getElementById('venta-ok-total').textContent = Number(body
                            .total_final).toFixed(2);
                        let vuelto = Number(body.pago_vuelto || 0);
                        document.getElementById('venta-ok-vuelto').textContent = vuelto.toFixed(2);
                        document.getElementById('venta-ok-vuelto-wrap').classList.toggle('d-none',
                            vuelto <= 0);
                        document.getElementById('venta-ok-link').href = urlFicha;

                        cart = [];
                        inpNombre.value = '';
                        inpApellido.value = '';
                        inpTelefono.value = '';
                        renderCart();
                        $('#cobroModal').modal('hide');
                        document.getElementById('venta-ok').classList.remove('d-none');
                    })
                    .catch(err => {
                        console.error(err);
                        if (popup) popup.close();
                        mostrarErrorCobro(
                            'Ocurrio un error al procesar la venta. Revisa la consola del servidor.'
                        );
                    })
                    .finally(() => {
                        btnConfirmar.disabled = false;
                    });
            });
        });
    </script>
@endsection
