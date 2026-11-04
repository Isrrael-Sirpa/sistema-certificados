@extends('layouts.app')

@section('page-title', 'Editar Beneficiario')
@section('page-subtitle', 'Actualización de identidad y pertenencia institucional')

@section('content')
    @include('beneficiaries.form', ['beneficiary' => $beneficiary, 'units' => $units])
@endsection
