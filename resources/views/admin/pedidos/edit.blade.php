@extends('adminlte::page')

@section('title', 'Editar ' . $pedido->codigo)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-white rounded shadow-sm border-left border-primary"
        style="border-left-color: #6c3461 !important; border-width: 4px !important;">
        <h4 class="font-weight-bold text-dark m-0 d-flex align-items-center">
            <span class="fa-stack fa-sm mr-2" style="color: #6c3461;">
                <i class="fas fa-circle fa-stack-2x" style="opacity: 0.15;"></i>
                <i class="fas fa-edit fa-stack-1x"></i>
            </span>
            EDITAR PEDIDO
            <span class="badge ml-3 shadow-sm text-white"
                style="background-color: #6c3461; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.5px;">
                {{ $pedido->codigo }}
            </span>
            <span class="badge badge-{{ $pedido->estado_color }} ml-2">
                {{ $pedido->estado_label }}
            </span>
        </h4>
        <a href="{{ route('admin.pedidos.show', $pedido) }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-eye mr-1"></i> Ver detalle
        </a>
    </div>
@stop

@section('content')
    @include('admin.pedidos._form', [
        'accionRuta' => route('admin.pedidos.update', $pedido),
        'metodo' => 'PUT',
    ])
@stop
