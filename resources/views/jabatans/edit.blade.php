@extends('layouts.app', ['title' => 'Edit Jabatan'])
@section('content')<div class="card"><div class="card-body p-4"><form method="post" action="{{ route('jabatans.update',$jabatan) }}">@method('put')@include('jabatans.form')</form></div></div>@endsection
