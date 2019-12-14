<?php

namespace Dcat\Admin\Actions;

use Dcat\Admin\Admin;

trait ActionHandler
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @return Response
     */
    public function response()
    {
        if (is_null($this->response)) {
            $this->response = new Response();
        }

        return $this->response;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function parameters()
    {
        return [];
    }

    /**
     * Return the confirm message.
     *
     * @return string|void
     */
    protected function confirm()
    {
    }

    /**
     * @return mixed
     */
    public function getCalledClass()
    {
        return str_replace('\\', '_', get_called_class());
    }

    /**
     * @return string
     */
    public function getHandleRoute()
    {
        return admin_url('_handle_action_');
    }

    /**
     * @return void
     */
    protected function addHandlerScript()
    {
        $parameters = json_encode($this->parameters());

        $resolveScript = <<<JS
target.data('working', 1);
Object.assign(data, {$parameters});
{$this->actionScript()}
{$this->buildActionPromise()}
{$this->handleActionPromise()}
JS;

        $script = <<<JS
(function ($) {
    $('{$this->selector($this->selectorPrefix)}').off('{$this->event}').on('{$this->event}', function() {
        var data = $(this).data(),
            target = $(this);
        if (target.data('working')) {
            return;
        }
        {$this->confirmScript($resolveScript)}
    });
})(jQuery);
JS;

        Admin::script($script);
    }

    /**
     * @param string $resolveScript
     *
     * @return string
     */
    protected function confirmScript($resolveScript)
    {
        if (! $message = $this->confirm()) {
            return $resolveScript;
        }

        return <<<JS
LA.confirm('{$message}', function () {
    {$resolveScript}
});
JS;
    }

    /**
     * @return string
     */
    protected function actionScript()
    {
        return '';
    }

    /**
     * @return string
     */
    protected function buildActionPromise()
    {
        return <<<JS
var process = new Promise(function (resolve,reject) {
    
    Object.assign(data, {
        _token: LA.token,
        _action: '{$this->getCalledClass()}',
    });

    $.ajax({
        method: '{$this->getMethod()}',
        url: '{$this->getHandleRoute()}',
        data: data,
        success: function (data) {
            target.data('working', 0);
            resolve([data, target]);
        },
        error:function(request){
            target.data('working', 0);
            reject([request, target]);
        }
    });
});
JS;
    }

    /**
     * @return string
     */
    public function handleActionPromise()
    {
        Admin::script($this->buildDefaultPromiseCallbacks());

        return <<<'JS'
process.then(window.ACTION_RSOLVER).catch(window.ACTION_CATCHER);
JS;
    }

    /**
     * @return string
     */
    protected function buildDefaultPromiseCallbacks()
    {
        return <<<'JS'
window.ACTION_RSOLVER = function (data) {
    var response = data[0],
        target   = data[1];
        
    if (typeof response !== 'object') {
        return LA.error({type: 'error', title: 'Oops!'});
    }
    
    response = response.data;
    
    var then = function (then) {
        switch (then.action) {
            case 'refresh':
                LA.reload();
                break;
            case 'download':
                window.open(then.value, '_blank');
                break;
            case 'redirect':
                LA.reload(then.value);
                break;
            case 'location':
                window.location = then.value;
                break;
        }
    };
    
    if (typeof response.html === 'string') {
        target.html(response.html);
    }

    if (typeof response.message === 'string' && response.type) {
        LA[response.type](response.message);
    }
    
    if (response.then) {
      then(response.then);
    }
};

window.ACTION_CATCHER = function (data) {
    var request = data[0], target = data[1];
    
    if (request && typeof request.responseJSON === 'object') {
        LA.error(request.responseJSON.message)
    }
    console.error(request);
};
JS;
    }
}
