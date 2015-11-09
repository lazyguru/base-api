<?php namespace Constant\Service;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

abstract class BaseSoapService
{
    protected $wsdl        = null;
    protected $_soapClient = null;
    protected $_options    = [];

    // Used for testing with a proxy (CharlesProxy) enabled
    protected $_debug = false;

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

    /**
     * Debug logging of input/output from API calls
     *
     * @param array $data     API input
     * @param mixed $response Response from API
     */
    public function _handleError($data, $response)
    {
        $this->output->debug('Class: ' . get_class($this));
        $this->output->debug('Request: ');
        $this->output->debug('**********');
        $this->output->debug(print_r($data, true));
        $this->output->debug('**********');
        $this->output->debug('Response: ');
        $this->output->debug('**********');
        $this->output->debug(print_r($response, true));
        $this->output->debug('**********');
    }

    /**
     * Make API call
     *
     * @param string $method SOAP method to call
     * @param array  $params Input for API call
     *
     * @return array Response body from API call
     */
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

            return $resp;
        } catch (\Exception $e) {
            $this->output->error(print_r($e, true));
        }
    }
}
