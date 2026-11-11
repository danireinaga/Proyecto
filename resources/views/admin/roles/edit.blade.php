@extends('adminlte::page')

@section('title', 'Editar Rol')

@section('content_header')
    <h1>Editar Rol: {{ $role->name }}</h1>
@stop

@section('content')
    <div class="card">
        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Nombre del Rol</label>
                    <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                </div>

                <div class="form-group">
                    <label><strong>Permisos / Módulos Permitidos</strong></label>
                    <div class="row border p-3 rounded bg-light">
                        @foreach($permissions as $permission)
                            <div class="col-md-4 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" 
                                           class="custom-control-input" id="permiso_{{ $permission->id }}"
                                           {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
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
                <button type="submit" class="btn btn-success">Actualizar Rol</button>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@stop