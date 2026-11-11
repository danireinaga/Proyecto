@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <h1>Editar Usuario: {{ $usuario->name }}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.usuarios.update', $usuario) }}" method="POST">
                @csrf
                @method('PUT')
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="nombres">Nombre(s)</label>
                        <input type="text" name="nombres" id="nombres"
                            class="form-control @error('nombres') is-invalid @enderror"
                            value="{{ old('nombres', $usuario->nombres) }}" maxlength="80" required>
                        @error('nombres')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label for="apellido_paterno">Primer apellido</label>
                        <input type="text" name="apellido_paterno" id="apellido_paterno"
                            class="form-control @error('apellido_paterno') is-invalid @enderror"
                            value="{{ old('apellido_paterno', $usuario->apellido_paterno) }}" maxlength="80" required>
                        @error('apellido_paterno')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label for="apellido_materno">Segundo apellido <span class="text-muted">(opcional)</span></label>
                        <input type="text" name="apellido_materno" id="apellido_materno"
                            class="form-control @error('apellido_materno') is-invalid @enderror"
                            value="{{ old('apellido_materno', $usuario->apellido_materno) }}" maxlength="80">
                        @error('apellido_materno')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico</label>
                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $usuario->email) }}" maxlength="255" required>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="celular">Número de celular</label>
                        <input type="tel" name="celular" id="celular"
                            class="form-control @error('celular') is-invalid @enderror"
                            value="{{ old('celular', $usuario->celular) }}" maxlength="25" required>
                        @error('celular')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="carnet_identidad">Carnet de identidad</label>
                        <input type="text" name="carnet_identidad" id="carnet_identidad"
                            class="form-control @error('carnet_identidad') is-invalid @enderror"
                            value="{{ old('carnet_identidad', $usuario->carnet_identidad) }}" maxlength="30" required>
                        @error('carnet_identidad')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Rol Asignado</label>
                    <select name="role" class="form-control @error('role') is-invalid @enderror" required>
                        <option value="">Seleccione un rol...</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role', $usuario->getRoleNames()->first()) === $role->name)>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <p class="text-muted">
                    Para cambiar la contraseña de este empleado, usa la opción de restablecimiento desde la lista de personal.
                </p>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Actualizar Usuario
                    </button>
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
@stop