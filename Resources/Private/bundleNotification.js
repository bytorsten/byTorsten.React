(function () {
    var legacy = JSON.parse('%LEGACY%');

    var scriptUrl = '%SCRIPT_URL%';
    var identifier = '%IDENTIFIER%';

    var log = function (message, style, error) {
        console[error ? 'error' : 'log']('%c' + message, error ? 'background: red; color: white; padding: 2px' : 'background: #00d8ff; color: white; padding: 2px');
    };

    log('React bundle is being generated for identifier "' + identifier + '"...');

    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.async = true;
    script.src = scriptUrl;

    if (!legacy) {
        script.type = 'module';
    }


    var start = Date.now();
    script.onload = function () {
        var seconds = (Date.now() - start) / 1000;
        log('...done in ' + seconds + ' seconds');
    };

    script.onerror = function () {
        log('React bundle failed to generate, please refresh the page and check your error logs', true);
    };

    document.body.appendChild(script);
}());

