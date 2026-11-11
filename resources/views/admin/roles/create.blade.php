@extends('adminlte::page')

@section('title', 'Crear Rol')

@section('content_header')
    <h1>Crear Nuevo Rol</h1>
@stop

@section('content')
    <div class="card">
        <form action="{{ route('admin.roles.store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nombre del Rol</label>
                    <input type="text" name="name" class="form-control" required placeholder="Ej. Cajero, Almacenero...">
                </div>

                <div class="form-group">
                    <label><strong>Permisos / Módulos Disponibles</strong></label>
                    <div class="row border p-3 rounded bg-light">
                        @foreach($permissions as $permission)
                            <div class="col-md-4 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                                           class="custom-control-input" id="permiso_{{ $permission->id }}">
                                    <label class="custom-control-label" for="permiso_{{ $permission->id }}">
                                        {{ ucfirst(str_replace(['admin.', 'cajero.', '.'], ['', '', ' '], $permission->name)) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-success">Guardar Rol</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@stop