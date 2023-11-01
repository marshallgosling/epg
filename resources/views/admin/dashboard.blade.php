<style>
    .title {
        font-size: 30px;
        color: #636b6f;
        font-family: 'Raleway', sans-serif;
        font-weight: 100;
        display: block;
        text-align: center;
        margin: 30px 0 30px 0px;
    }

    .links {
        text-align: left;
        margin-bottom: 20px;
    }

    .links > a {
        padding: 0 25px;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: .1rem;
    }

    .links > span {
        color: #636b6f;
        padding: 0 25px;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: .1rem;
    }
</style>

<div class="title">
    {{ $title }}
</div>
<div class="links">
    <span>快速导航链接：</span>
    @foreach($links as $name => $url)
    <a href="{{$url}}">{{$name}}</a>
    @endforeach
</div>