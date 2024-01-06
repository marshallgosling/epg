<div class="row">
@foreach($channels as $channel => $links)
<div class="col-md-4">
    <div class="list-group">
        <a href="#" class="list-group-item list-group-item-info">
            {{$channel}}
        </a>
        @foreach($links as $name => $url)
        <a href="{{$url}}" class="list-group-item">{!!$name!!}</a>
        @endforeach
    </div>
</div>
@endforeach
</div>