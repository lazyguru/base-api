<?php namespace Constant\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

abstract class BaseSoapService extends BaseService
{
    protected $wsdl        = null;
    private   $_soapClient = null;
    private   $_auth       = [];
    private   $_options    = [];

    // Used for testing with a proxy (CharlesProxy) enabled
    private $_debug = false;

    /**
     * @param LoggerInterface $log
     * @param $username
     * @param $password
     * @param array $options
     */
    public abstract function __construct(LoggerInterface $log, $username, $password, $options = []);
    // $this->_options['proxy_host'] = '';
    // $this->_options['proxy_port'] = '';

    protected function processRequest($method, $params)
    {
        if (empty($this->wsdl)) {
            throw Exception('Must specifiy WSDL in child class before calling parent::__construct.  Did you remember to override the __construct method?'
            );
        }
        if (empty($this->uri)) {
            throw Exception('Must specifiy endpoint in child class before calling parent::__construct.  Did you remember to override the __construct method?'
            );
        }

        $this->_options['trace'] = 1;
        $this->_options['exceptions'] = true;
        $this->_options['cache_wsdl'] = WSDL_CACHE_BOTH;

        $this->_headers = array(
            new SoapHeader(
                $this->uri,
                'Login',
                array(
                    'Username' => $this->username,
                    'Password' => $this->password
                )
            )
        );

        if (substr($this->wsdl, 0, 4) != 'http') {
            $this->_soapClient = new SoapClient(
                dirname(__DIR__) . '/wsdl/' . $this->wsdl,
                $this->_options
            );
        } else {
            $this->_soapClient = new SoapClient($this->wsdl, $this->_options);
        }

        try {
            $resp = $this->_soapClient->__soapCall(
                $method,
                $params,
                null,
                $this->_headers,
                ''
            );

            return json_decode($resp);
        } catch (BadResponseException $e) {
            $this->output->error($e->getRequest());
            if ($e->hasResponse()) {
                $this->output->error($e->getResponse());
            }
        }
    }
} 
