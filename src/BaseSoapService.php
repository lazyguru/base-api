<?php namespace Constant\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

abstract class BaseSoapService
{
    protected $wsdl        = null;
    private   $_soapClient = null;
    private   $_options    = [];

    // Used for testing with a proxy (CharlesProxy) enabled
    private $_debug = false;

    protected $_headers = [];
    protected $uri = '';
    protected $username = '';
    protected $password = '';

    /**
     * @var LoggerInterface $output
     */
    protected $output;

    /**
     * @param LoggerInterface $log
     * @param $username
     * @param $password
     * @param array $options
     */
    public abstract function __construct(LoggerInterface $log, $username, $password, $options = []);

    protected function processRequest($method, $params)
    {
        if (empty($this->wsdl)) {
            throw new \Exception('Must specifiy WSDL in child class before calling processRequest.  Did you remember to implement the __construct method?'
            );
        }
        if (empty($this->uri)) {
            throw new \Exception('Must specifiy uri in child class before calling processRequest.  Did you remember to implement the __construct method?'
            );
        }

        $this->_options['trace'] = 1;
        $this->_options['exceptions'] = true;
        $this->_options['cache_wsdl'] = WSDL_CACHE_BOTH;

        $this->_headers = array(
            new \SoapHeader(
                $this->uri,
                'Login',
                array(
                    'Username' => $this->username,
                    'Password' => $this->password
                )
            )
        );

        if (substr($this->wsdl, 0, 4) != 'http') {
            $this->_soapClient = new \SoapClient(
                dirname(__DIR__) . '/wsdl/' . $this->wsdl,
                $this->_options
            );
        } else {
            $this->_soapClient = new \SoapClient($this->wsdl, $this->_options);
        }

        try {
            $outputHeader = '';
            $resp = $this->_soapClient->__soapCall(
                $method,
                $params,
                null,
                $this->_headers,
                $outputHeader
            );

            return json_decode($resp);
        } catch (\Exception $e) {
            $this->output->error($e->getRequest());
            if ($e->hasResponse()) {
                $this->output->error($e->getResponse());
            }
        }
    }
} 
