<pre id="json_config_{{ $id }}" style="white-space: pre-wrap;background: #000000;color: #00fa4a;padding: 10px;border-radius: 0;">
{!! $config !!}
</pre>
<script type="text/javascript">
    (function() {
        var element = document.getElementById("json_config_{{ $id }}");
        var obj = JSON.parse(element.innerText);
        element.innerHTML = JSON.stringify(obj, undefined, 2);
    })();
</script>
