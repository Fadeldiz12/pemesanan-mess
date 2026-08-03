@extends('layouts.app', ['title' => 'Edit Subbagian'])
@section('content')<div class="card"><div class="card-body p-4"><form method="post" action="{{ route('sub-departments.update',$subDepartment) }}">@method('put')@include('sub_departments.form')</form></div></div>@endsection
