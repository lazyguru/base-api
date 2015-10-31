<?php namespace Constant\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Subscriber\Log\LogSubscriber;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

abstract class BaseService
{

    const POST = 'post';
    const GET = 'get';
    const PUT = 'put';

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

    protected function processRequest($data, $method = self::POST)
    {
        $this->_headers['Content-Type'] = 'application/json';
        try {
            $client = new Client();

            // create a log channel
            $log = new Logger(get_class($this));
            $log->pushHandler(new StreamHandler('service_call.log', Logger::DEBUG));

            $subscriber = new LogSubscriber($log);
            $client->getEmitter()->attach($subscriber);

            $response = $client->$method($this->uri, [
                'auth' => [$this->username, $this->password],
                'headers' => $this->_headers,
                'body' => $data
            ]);

            return json_decode($response->getBody());
        } catch (BadResponseException $e) {
            $this->output->error($e->getRequest());
            if ($e->hasResponse()) {
                $this->output->error($e->getResponse());
            }
        }
    }
} 
