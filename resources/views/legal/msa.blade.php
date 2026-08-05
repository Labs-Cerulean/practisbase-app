@extends('layouts.legal')

@section('page_title', 'Master Service Agreement')

@section('content')
    <h1>PractisBase Master Service Agreement</h1>
    <p class="legal-meta">Terms of Service · Effective date: 5 August 2026 · Revision R02 · Cerulean Labs Limited</p>

    @include('legal.partials.msa-body')
@endsection
