@extends('back.master')

@section('title', __('Log viewer'))

@section('content')
<div class="row main" id="logviewer">
	<iframe src="/admin/log_viewer" frameborder="0"></iframe>
</div>
@endsection