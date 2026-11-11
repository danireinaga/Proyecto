@extends('adminlte::page')

@section('title', 'Configuración de la cuenta')

@section('content_header')
    <h1>Configuración de la cuenta</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Correo de acceso</h3>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>{{ $user->email }}</strong></p>
                    <p class="text-muted mb-0">El correo de acceso no se puede cambiar desde esta cuenta.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Cambiar contraseña</h3>
                    </div>
                    <form method="POST" action="{{ route('admin.configuracion.password') }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <p class="text-muted">Al guardar, se cerrará tu sesión. Ingresa nuevamente con tu nueva contraseña.</p>
                            <div class="form-group">
                                <label for="current_password">Contraseña actual</label>
                                <input id="current_password" type="password" name="current_password"
                                    class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                    autocomplete="current-password" required>
                                @error('current_password', 'updatePassword')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="password">Nueva contraseña</label>
                                <input id="password" type="password" name="password"
                                    class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                    autocomplete="new-password" required>
                                @error('password', 'updatePassword')
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
                            <button class="btn btn-warning" type="submit">
                                <i class="fas fa-key mr-1"></i> Cambiar contraseña y cerrar sesión
                            </button>
                        </div>
                    </form>
                </div>
        </div>
    </div>
@stop
