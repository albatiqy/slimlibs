<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs\ApiClient;

use Psr\Container\ContainerInterface;
use Albatiqy\Slimlibs\Result\AbstractResult;
use Albatiqy\Slimlibs\Result\ResultException;

class ApiCall {

    private $token;
    private $base_url;
    private $password;
    private $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function setBaseUrl($baseUrl) {
        $this->base_url = $base_url . '/api/v0';
        return $this;
    }

    public function post($endPoint, $data) {
        $data_string = \json_encode($data);
        $ch = \curl_init($this->base_url . $endPoint);
        \curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, "POST");
        \curl_setopt($ch, \CURLOPT_POSTFIELDS, $data_string);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . \strlen($data_string),
            'Accept: application/json',
        ]);
        $result = \curl_exec($ch);
        if (\curl_errno($ch)) {
            $error_msg = \curl_error($ch);
            throw new \Exception($error_msg);
        }
        $httpcode = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        \curl_close($ch);
        $result = \json_decode($result);
        if ($result === null && \json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Exception('Malformed JSON Response: '.\json_last_error_msg());
        }
        if (\property_exists($result, 'resType')) {
            switch ($result->resType) {
            case AbstractResult::RES_TYPES[AbstractResult::T_TABLE]:
            case AbstractResult::RES_TYPES[AbstractResult::T_DATA]:
                return $result->data;
            default:
                return true;
            }
        }
        if (\property_exists($result, 'errType')) {
            throw new ApiException($result->message, $result->errType);
        }
        throw new RemoteException($result);
    }
}