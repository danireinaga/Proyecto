@extends('adminlte::page')

@php
    $esModuloPersonalizado = request()->routeIs('admin.pedidos.personalizados.*');
    $rutaListado = $esModuloPersonalizado
        ? route('admin.pedidos.personalizados.index')
        : route('admin.pedidos.index');
    $rutaGuardar = $esModuloPersonalizado
        ? route('admin.pedidos.personalizados.store')
        : route('admin.pedidos.store');
@endphp

@section('title', 'Nuevo pedido personalizado')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-white rounded shadow-sm border-left border-primary"
        style="border-left-color: #552f4dff !important; border-width: 4px !important;">
        <h4 class="font-weight-bold text-dark m-0 d-flex align-items-center">
            <span class="fa-stack fa-sm mr-2" style="color: #6c3461;">
                <i class="fas fa-circle fa-stack-2x" style="opacity: 0.15;"></i>
                <i class="fas fa-plus-circle fa-stack-1x"></i>
            </span>
            NUEVO PEDIDO PERSONALIZADO
        </h4>
        <a href="{{ $rutaListado }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver al listado
        </a>
    </div>
@stop

@section('content')
    <div class="alert alert-light border-left mb-3" style="border-left:4px solid #6c3461 !important;">
        Registra aquí el diseño acordado, la fecha de entrega y el precio del encargo.
    </div>
    <div class="mb-3">
        <span class="badge badge-pill badge-light border text-dark">
            <i class="fas fa-user-circle mr-1" style="color: #6c3461;"></i>
            Se registrará a nombre de <strong>{{ auth()->user()->name }}</strong>
        </span>
    </div>

    @include('admin.pedidos._form', [
        'accionRuta' => $rutaGuardar,
        'metodo' => 'POST',
    ])
@stop
