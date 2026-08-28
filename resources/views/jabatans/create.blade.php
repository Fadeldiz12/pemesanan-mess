@extends('layouts.app', ['title' => 'Tambah Jabatan'])
@section('content')<div class="card"><div class="card-body p-4"><form method="post" action="{{ route('jabatans.store') }}">@include('jabatans.form')</form></div></div>@endsection
