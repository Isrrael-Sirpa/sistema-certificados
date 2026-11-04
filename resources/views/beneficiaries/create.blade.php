@extends('layouts.app')

@section('page-title', 'Registrar Beneficiario')
@section('page-subtitle', 'Identificación y pertenencia al padrón institucional')

@section('content')
    @include('beneficiaries.form', ['beneficiary' => null, 'units' => $units])
@endsection
