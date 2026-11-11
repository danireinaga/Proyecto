@extends('adminlte::page')

@section('title', 'Gestión de Roles')

@section('content_header')
    <h1>Gestión de Roles y Permisos</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">Crear Nuevo Rol</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Rol</th>
                        <th>Permisos Asignados</th>
                        <th>Usuarios</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td>{{ $role->id }}</td>
                            <td>{{ $role->name }}</td>
                            <td>
                                @foreach($role->permissions as $permission)
                                    <span class="badge badge-info">{{ ucfirst(str_replace(['admin.', '.'], ['Visualizar ', ' '], $permission->name)) }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ $role->assigned_users_count }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $role->is_active ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $role->is_active ? 'Activo' : 'Deshabilitado' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-warning">Editar</a>
                                <form action="{{ route('admin.roles.status', $role) }}" method="POST" style="display:inline-block;"
                                    data-confirm-title="{{ $role->is_active ? 'Deshabilitar rol' : 'Habilitar rol' }}"
                                    data-confirm-message="{{ $role->is_active
                                        ? 'Se impedirá asignar este rol a nuevos usuarios. No se puede deshabilitar mientras tenga usuarios asignados. ¿Continuar?'
                                        : 'El rol volverá a estar disponible para asignarlo. ¿Continuar?' }}"
                                    data-confirm-accept="{{ $role->is_active ? 'Deshabilitar' : 'Habilitar' }}"
                                    data-confirm-destructive="{{ $role->is_active ? 'true' : 'false' }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $role->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                        {{ $role->is_active ? 'Deshabilitar' : 'Habilitar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    @if($roles->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Aún no hay roles de personal. Los roles principales del sistema no se administran aquí.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@stop