@php
    $esEdicion = $pedido->exists;
    $fechaPedido = $pedido->fecha_pedido?->format('Y-m-d') ?? today()->toDateString();
    $responsable = $pedido->user?->name ?? auth()->user()->name;
    $horaEntrega = old('hora_entrega');
    if (blank($horaEntrega)) {
        $horaEntrega = $pedido->hora_entrega
            ? \Illuminate\Support\Carbon::parse($pedido->hora_entrega)->format('H:i')
            : '10:00';
    }
@endphp

<style>
    .ped-form-card { border-top: 3px solid #6c3461; }
    .ped-btn {
        background: linear-gradient(135deg, #6c3461 0%, #512648 100%);
        color: #fff;
        border: none;
        box-shadow: 0 4px 10px rgba(108, 52, 97, 0.25);
    }
    .ped-btn:hover { background: linear-gradient(135deg, #512648 0%, #3a1d34 100%); color: #fff; }
</style>

<form action="{{ $accionRuta }}" method="POST" id="pedido-form" enctype="multipart/form-data">
    @csrf
    @if ($metodo !== 'POST')
        @method($metodo)
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle mr-1"></i> Revisa los siguientes datos:</h6>
            <ul class="mb-0 pl-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    @endif

    <div class="alert alert-light border-left mb-3" style="border-left:4px solid #6c3461 !important;">
        Registra aquí los requisitos del cliente y el monto acordado. Este encargo no depende de productos del catálogo
        ni descuenta stock.
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card ped-form-card mb-3">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-user mr-1" style="color:#6c3461;"></i>
                        Datos del cliente</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="cliente_nombre" class="small font-weight-bold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="cliente_nombre" id="cliente_nombre" required maxlength="120"
                                class="form-control form-control-sm"
                                value="{{ old('cliente_nombre', $pedido->cliente_nombre) }}" placeholder="Ej. Daniela">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="cliente_apellido" class="small font-weight-bold">Apellido</label>
                            <input type="text" name="cliente_apellido" id="cliente_apellido" maxlength="120"
                                class="form-control form-control-sm"
                                value="{{ old('cliente_apellido', $pedido->cliente_apellido) }}" placeholder="Ej. Ramírez">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="cliente_telefono" class="small font-weight-bold">Teléfono <span class="text-danger">*</span></label>
                            <input type="text" name="cliente_telefono" id="cliente_telefono" required maxlength="40"
                                class="form-control form-control-sm"
                                value="{{ old('cliente_telefono', $pedido->cliente_telefono) }}" placeholder="Ej. 70123456">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="cliente_nit" class="small font-weight-bold">NIT / CI</label>
                            <input type="text" name="cliente_nit" id="cliente_nit" maxlength="40"
                                class="form-control form-control-sm"
                                value="{{ old('cliente_nit', $pedido->cliente_nit) }}" placeholder="Ej. 1234567 LP">
                        </div>
                        <div class="form-group col-12 mb-0">
                            <label for="cliente_direccion" class="small font-weight-bold">Dirección de entrega</label>
                            <input type="text" name="cliente_direccion" id="cliente_direccion" maxlength="255"
                                class="form-control form-control-sm"
                                value="{{ old('cliente_direccion', $pedido->cliente_direccion) }}"
                                placeholder="Ej. Av. Santa Cruz 458, zona Sur">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card ped-form-card mb-3">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-birthday-cake mr-1" style="color:#6c3461;"></i>
                        Personalización del encargo</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="tipo_encargo" class="small font-weight-bold">Tipo de encargo</label>
                            <input type="text" name="tipo_encargo" id="tipo_encargo" maxlength="120"
                                class="form-control form-control-sm"
                                value="{{ old('tipo_encargo', $pedido->tipo_encargo) }}"
                                placeholder="Ej. torta, cupcakes, mesa dulce">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ocasion" class="small font-weight-bold">Ocasión</label>
                            <select name="ocasion" id="ocasion" class="form-control form-control-sm">
                                <option value="">Seleccionar (opcional)</option>
                                @foreach (['Cumpleaños', 'Boda', 'Aniversario', 'Bautizo', 'Graduación', 'Baby shower', 'Otro'] as $ocasion)
                                    <option value="{{ $ocasion }}" @selected(old('ocasion', $pedido->ocasion) === $ocasion)>
                                        {{ $ocasion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="porciones" class="small font-weight-bold">Porciones / personas</label>
                            <input type="number" name="porciones" id="porciones" min="1" max="1000"
                                class="form-control form-control-sm"
                                value="{{ old('porciones', $pedido->porciones) }}" placeholder="Ej. 20">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="sabor" class="small font-weight-bold">Sabor de la masa</label>
                            <input type="text" name="sabor" id="sabor" maxlength="120"
                                class="form-control form-control-sm"
                                value="{{ old('sabor', $pedido->sabor) }}" placeholder="Ej. vainilla y chocolate">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="relleno" class="small font-weight-bold">Relleno</label>
                            <input type="text" name="relleno" id="relleno" maxlength="120"
                                class="form-control form-control-sm"
                                value="{{ old('relleno', $pedido->relleno) }}" placeholder="Ej. frutilla con crema">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="cobertura" class="small font-weight-bold">Cobertura</label>
                            <input type="text" name="cobertura" id="cobertura" maxlength="120"
                                class="form-control form-control-sm"
                                value="{{ old('cobertura', $pedido->cobertura) }}" placeholder="Ej. crema chantilly">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="colores" class="small font-weight-bold">Colores</label>
                            <input type="text" name="colores" id="colores" maxlength="255"
                                class="form-control form-control-sm"
                                value="{{ old('colores', $pedido->colores) }}" placeholder="Ej. verde y dorado">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="texto_torta" class="small font-weight-bold">Texto o dedicatoria</label>
                            <input type="text" name="texto_torta" id="texto_torta" maxlength="255"
                                class="form-control form-control-sm"
                                value="{{ old('texto_torta', $pedido->texto_torta) }}" placeholder="Ej. ¡Feliz cumpleaños!">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="alergias" class="small font-weight-bold">Alergias o restricciones</label>
                            <input type="text" name="alergias" id="alergias" maxlength="1000"
                                class="form-control form-control-sm"
                                value="{{ old('alergias', $pedido->alergias) }}" placeholder="Ej. sin nueces">
                        </div>
                        <div class="form-group col-12">
                            <label for="decoracion" class="small font-weight-bold">Diseño y decoración</label>
                            <textarea name="decoracion" id="decoracion" rows="3" maxlength="2000"
                                class="form-control form-control-sm"
                                placeholder="Describe la forma, temática, acabado u otros detalles solicitados">{{ old('decoracion', $pedido->decoracion) }}</textarea>
                        </div>
                        <div class="form-group col-12">
                            <label for="detalles" class="small font-weight-bold">Otros detalles del encargo</label>
                            <textarea name="detalles" id="detalles" rows="3" maxlength="2000"
                                class="form-control form-control-sm"
                                placeholder="Cualquier requisito adicional acordado con el cliente">{{ old('detalles', $pedido->detalles) }}</textarea>
                        </div>
                        <div class="form-group col-12 mb-0">
                            <label for="imagen_referencia" class="small font-weight-bold">Imagen de referencia (opcional)</label>
                            <input type="file" name="imagen_referencia" id="imagen_referencia"
                                accept="image/jpeg,image/png,image/webp" class="form-control-file d-block">
                            <small class="form-text text-muted">JPG, PNG o WebP; máximo 5 MB.</small>
                            @if ($pedido->imagen_referencia)
                                <div class="mt-2">
                                    <a href="{{ route('admin.pedidos.imagen-referencia', $pedido) }}"
                                        target="_blank" rel="noopener">Ver imagen guardada</a>
                                    <div class="form-check mt-1">
                                        <input type="checkbox" name="quitar_imagen_referencia" id="quitar_imagen_referencia"
                                            value="1" class="form-check-input">
                                        <label for="quitar_imagen_referencia" class="form-check-label small">
                                            Quitar imagen guardada
                                        </label>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card ped-form-card mb-3">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-calendar-alt mr-1" style="color:#6c3461;"></i>
                        Entrega y responsable</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="small font-weight-bold">Fecha del pedido</label>
                        <div class="form-control form-control-sm bg-light">{{ \Illuminate\Support\Carbon::parse($fechaPedido)->format('d/m/Y') }}</div>
                    </div>
                    <div class="form-group">
                        <label for="fecha_entrega" class="small font-weight-bold">Fecha de entrega <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_entrega" id="fecha_entrega" required min="{{ $fechaPedido }}"
                            class="form-control form-control-sm"
                            value="{{ old('fecha_entrega', $pedido->fecha_entrega?->format('Y-m-d') ?? today()->addDay()->toDateString()) }}">
                    </div>
                    <div class="form-group">
                        <label for="hora_entrega" class="small font-weight-bold">Hora de entrega</label>
                        <input type="time" name="hora_entrega" id="hora_entrega"
                            class="form-control form-control-sm" value="{{ $horaEntrega }}">
                    </div>
                    <div class="form-group">
                        <label for="estado" class="small font-weight-bold">Estado <span class="text-danger">*</span></label>
                        <select name="estado" id="estado" required class="form-control form-control-sm">
                            @foreach ($estados as $clave => $label)
                                <option value="{{ $clave }}"
                                    @selected(old('estado', $pedido->estado ?? \App\Models\Pedido::ESTADO_PENDIENTE) === $clave)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small font-weight-bold">Responsable</label>
                        <div class="form-control form-control-sm bg-light" aria-readonly="true">{{ $responsable }}</div>
                        <small class="form-text text-muted">
                            {{ $esEdicion ? 'El responsable original se conserva.' : 'Se asignará automáticamente a quien registra el pedido.' }}
                        </small>
                    </div>
                </div>
            </div>

            <div class="card ped-form-card mb-3">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-money-bill-wave mr-1" style="color:#6c3461;"></i>
                        Precio y pago</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="total_estimado" class="small font-weight-bold">Monto acordado (Bs.) <span class="text-danger">*</span></label>
                        <input type="number" name="total_estimado" id="total_estimado" required min="0" step="0.01"
                            class="form-control"
                            value="{{ old('total_estimado', $pedido->total_estimado) }}" placeholder="Ej. 250.00">
                        <small class="form-text text-muted">Ingresa el precio acordado; no se calcula desde el catálogo.</small>
                    </div>
                    <div class="form-group mb-0">
                        <label for="abono" class="small font-weight-bold">Abono (Bs.)</label>
                        <input type="number" name="abono" id="abono" step="0.01" min="0"
                            class="form-control form-control-sm" value="{{ old('abono', $pedido->abono ?? 0) }}">
                    </div>
                </div>
            </div>

            <div class="card ped-form-card mb-3">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold"><i class="fas fa-sticky-note mr-1" style="color:#6c3461;"></i>
                        Notas internas</h3>
                </div>
                <div class="card-body">
                    <textarea name="notas" id="notas" rows="4" maxlength="2000" class="form-control form-control-sm"
                        placeholder="Información interna para el equipo">{{ old('notas', $pedido->notas) }}</textarea>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn ped-btn btn-block font-weight-bold">
                        <i class="fas fa-save mr-1"></i> {{ $esEdicion ? 'Guardar cambios' : 'Registrar pedido' }}
                    </button>
                    <a href="{{ $esEdicion ? route('admin.pedidos.show', $pedido) : (request()->routeIs('admin.pedidos.personalizados.*') ? route('admin.pedidos.personalizados.index') : route('admin.pedidos.index')) }}"
                        class="btn btn-outline-secondary btn-block btn-sm mt-1">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
