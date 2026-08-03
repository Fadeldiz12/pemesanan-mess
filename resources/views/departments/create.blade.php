@extends('layouts.app', ['title' => 'Tambah Bagian'])
@section('content')<div class="card"><div class="card-body p-4"><form method="post" action="{{ route('departments.store') }}">@include('departments.form')</form></div></div>@endsection
