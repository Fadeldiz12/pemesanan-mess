@extends('layouts.app', ['title' => 'Tambah Subbagian'])
@section('content')<div class="card"><div class="card-body p-4"><form method="post" action="{{ route('sub-departments.store') }}">@include('sub_departments.form')</form></div></div>@endsection
