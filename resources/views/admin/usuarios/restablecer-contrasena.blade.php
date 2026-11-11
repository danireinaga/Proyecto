@extends('adminlte::page')

@section('title', 'Restablecer contraseña')

@section('content_header')
    <h1>Restablecer contraseña</h1>
@stop

@section('content')
    <div class="card card-outline card-warning">
        <div class="card-header">
            <h3 class="card-title">Cuenta de {{ $usuario->name }}</h3>
        </div>
        <form method="POST" action="{{ route('admin.usuarios.reset-password', $usuario) }}">
            @csrf
            @method('PATCH')
            <div class="card-body">
                <div class="alert alert-info">
                    Esta acción solo cambia la contraseña. El correo y el historial del usuario se conservarán.
                </div>
                <div class="form-group">
                    <label for="current_password">Tu contraseña de administrador</label>
                    <input id="current_password" type="password" name="current_password"
                        class="form-control @error('current_password') is-invalid @enderror"
                        autocomplete="current-password" required>
                    @error('current_password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group">
                    <label for="password">Nueva contraseña</label>
                    <input id="password" type="password" name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="new-password" required>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group mb-0">
                    <label for="password_confirmation">Confirmar nueva contraseña</label>
                    <input id="password_confirmation" type="password" name="password_confirmation"
                        class="form-control" autocomplete="new-password" required>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-key mr-1"></i> Restablecer contraseña
                </button>
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
@stop
