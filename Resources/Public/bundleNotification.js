(function () {
    var script = document.currentScript;
    var now = Date.now();
    script.previousSibling.onload = function () {
        log('Bundled generated in ' + ((Date.now() - now) / 1000) + ' seconds', 'ok');
    };

    script.previousSibling.onerror = function () {
        log('Bundling error, please check the logs', 'error');
    };

    var log = function (message, severity) {
        switch (severity) {
            case 'warning': {
                console.log('%cWarning%c' + message, 'background: rgb(255, 238, 186); color: #856404; font-weight: bold; padding: 3px', 'background: rgba(255, 238, 186, .5); color: #856404; padding: 3px 5px');
                break;
            }
            case 'ok': {
                console.log('%cOK%c' + message, 'background: rgb(195, 230, 203); color: #155724; font-weight: bold; padding: 3px', 'background: rgba(195, 230, 203, .5); color: #155724; padding: 3px 5px');
                break;
            }
            case 'error': {
                console.log('%cError%c' + message, 'background: rgb(245, 198, 203); color: #721c24; font-weight: bold; padding: 3px', 'background: rgba(245, 198, 203, .5); color: #721c24; padding: 3px 5px');
            }
        }
    };

    log('A new react bundle is being generated for identifier "' + script.dataset.identifier + '"...' , 'warning');
})();

