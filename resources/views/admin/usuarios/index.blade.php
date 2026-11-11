@extends('adminlte::page')

@section('title', 'Gestión de Empleados')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestión de personal</h1>
        <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
        </a>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo Electrónico</th>
                        <th>Celular</th>
                        <th>Carnet</th>
                        <th>Rol Asignado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->celular ?: '—' }}</td>
                            <td>{{ $user->carnet_identidad ?: '—' }}</td>
                            <td>
                                <span class="badge badge-info uppercase">
                                    {{ $user->getRoleNames()->implode(', ') }}
                                </span>
                            </td>
                            <td>
                                @if ($user->esAdministrador())
                                    <span class="text-muted">Cuenta protegida</span>
                                @else
                                    <a href="{{ route('admin.usuarios.reset-password.edit', $user) }}"
                                        class="btn btn-sm btn-outline-warning" title="Restablecer contraseña">
                                        <i class="fas fa-key"></i>
                                    </a>
                                    <a href="{{ route('admin.usuarios.edit', $user) }}" class="btn btn-sm btn-warning"
                                        title="Editar usuario" aria-label="Editar usuario">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.usuarios.destroy', $user) }}" method="POST"
                                        class="d-inline"
                                        data-confirm-title="Eliminar usuario"
                                        data-confirm-message="Se eliminará el acceso de este usuario, pero se conservará su historial de ventas y pedidos. ¿Deseas continuar?"
                                        data-confirm-accept="Eliminar usuario"
                                        data-confirm-destructive="true">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar usuario">
                                            <i class="fas fa-user-slash mr-1"></i> Eliminar usuario
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($users->isEmpty())
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No hay otros usuarios registrados para mostrar.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@stop