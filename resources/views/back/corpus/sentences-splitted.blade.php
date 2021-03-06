@extends('front.template-iframe')

@section('main')

@if(isset($sentences_splitted))
	{!! Form::open(['url' => 'corpus/parse', 'method' => 'post', 'role' => 'form', 'target'=>'parse']) !!}
	    <div class="col-md-10 col-md-offset-1">
			<textarea style="width:100%;height:500px;" name="text">{!! $sentences_splitted !!}</textarea>
	    </div>
	<div class="clearfix"></div>
	<a href="{{ url('corpus/file?file=').$parser->output_file }}" class="btn btn-info">Get raw output file</a>
	<a data-toggle="collapse" data-target="#command" class="btn btn-info">Show command</a>
	<div id="command" class="collapse">{{ $parser->command }}</div><br/>

	<div class="col-md-10">
	Sentid prefix : <input type="text" value="{{ $url }}" name="url" />
	{!! Form::submit('Parse', null,['class' => 'btn btn-success']) !!}
	</div>
	{!! Form::close() !!}
<iframe name="parse" style="width:100%;" frameborder="0" scrolling="no" onload="resizeIframe(this)" />	
@endif

@stop

