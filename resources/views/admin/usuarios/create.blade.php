@extends('adminlte::page')

@section('title', 'Crear Empleado')

@section('content_header')
    <h1>Crear nuevo usuario</h1>
@stop

@section('content')
    <div class="card card-primary">
        <form action="{{ route('admin.usuarios.store') }}" method="POST">
            @csrf
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <p class="mb-1">Revisa los datos ingresados:</p>
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
                            value="{{ old('nombres') }}" maxlength="80" autocomplete="given-name" required>
                        @error('nombres')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label for="apellido_paterno">Primer apellido</label>
                        <input type="text" name="apellido_paterno" id="apellido_paterno"
                            class="form-control @error('apellido_paterno') is-invalid @enderror"
                            value="{{ old('apellido_paterno') }}" maxlength="80" autocomplete="family-name" required>
                        @error('apellido_paterno')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label for="apellido_materno">Segundo apellido <span class="text-muted">(opcional)</span></label>
                        <input type="text" name="apellido_materno" id="apellido_materno"
                            class="form-control @error('apellido_materno') is-invalid @enderror"
                            value="{{ old('apellido_materno') }}" maxlength="80">
                        @error('apellido_materno')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Correo Electrónico (Para su acceso)</label>
                    <input type="email" name="email" id="email"
                        class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}"
                        placeholder="empleado@azucaryyo.com" maxlength="255" autocomplete="email" required>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="celular">Número de celular</label>
                        <input type="tel" name="celular" id="celular"
                            class="form-control @error('celular') is-invalid @enderror"
                            value="{{ old('celular') }}" maxlength="25" autocomplete="tel" required>
                        @error('celular')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group col-md-6">
                        <label for="carnet_identidad">Carnet de identidad</label>
                        <input type="text" name="carnet_identidad" id="carnet_identidad"
                            class="form-control @error('carnet_identidad') is-invalid @enderror"
                            value="{{ old('carnet_identidad') }}" maxlength="30" required>
                        @error('carnet_identidad')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña Temporal</label>
                    <input type="password" name="password" id="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="new-password" required>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="role">Rol Asignado</label>
                    <select name="role" id="role"
                        class="form-control @error('role') is-invalid @enderror"
                        @required($roles->isNotEmpty()) @disabled($roles->isEmpty())>
                        <option value="">Selecciona un rol</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role') === $role->name)>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    @if ($roles->isEmpty())
                        <small class="form-text text-danger">
                            No hay roles de personal activos. Crea y activa un rol en el módulo Roles antes de registrar empleados.
                        </small>
                    @endif
                </div>
            </div>
            <div class="card-footer">
                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary" @disabled($roles->isEmpty())>
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
@stop
