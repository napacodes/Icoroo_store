@extends('back.master')

@section('title', __('File manager'))

@section('content')
<div class="row main" id="filemanager">
	<iframe src="{{ route('show_file_manager') }}" frameborder="0"></iframe>
</div>
@endsection