<table {!! $attributes !!}>
    <thead>
    <tr>
        @foreach($headers as $header)
            <th>{!! $header !!}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
    <tr class="{{$row['style']}}">
        @foreach($row['item'] as $item)
        <td>{!! $item !!}</td>
        @endforeach
    </tr>
    @endforeach
    </tbody>
</table>