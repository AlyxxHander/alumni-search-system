@extends('layouts.app')
@section('title', 'Tambah Alumni')

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('alumni.index') }}" class="text-blue-600 hover:text-blue-800 text-sm mb-4 inline-block">← Kembali</a>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('alumni.store') }}">
            @csrf
            @include('alumni._form')
            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 text-sm mt-4">
                Simpan
            </button>
        </form>
    </div>
</div>
@endsection
