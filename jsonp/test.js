function myJsonp(url, data, fn) {
    var name = 'dfasdfa'
    var param = ''
    for (var k in data) {
        let value = data[k] !== undefined ? data[k] : ''
        param += `&${value}=${encodeURIComponent(value)}`
    }
    param.substr(1)
    url +=  (url.indexOf('?') < 0 ? '?' : '&') + param
    url += '&callback=' + name
    window[name] = function (json) {
        window[name] = undefined

        fn(json)
    }

    var script = document.createElement('script')
    script.src = url
    var target = document.getElementsByTagName('head')[0]
    target.appendChild(script)
}