@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        {{ __('Bienvenido a Dulce Reposteria "Azucar & Yo"') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection