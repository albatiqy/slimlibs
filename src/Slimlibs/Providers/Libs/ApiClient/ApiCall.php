<?php declare (strict_types = 1);
namespace Albatiqy\Slimlibs\Providers\Libs\ApiClient;

use Psr\Container\ContainerInterface;
use Albatiqy\Slimlibs\Result\AbstractResult;
use Albatiqy\Slimlibs\Result\ResultException;

class ApiCall {

    private $base_url = null;
    private $base_endpoint;
    private $container;
    private $bearer;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->base_endpoint = '/api/v0';
        $this->bearer = null;
    }

    public function setBaseUrl($baseUrl, $baseEndPoint = null) {
        if ($baseEndPoint!=null) {
            $this->base_endpoint = $baseEndPoint;
        }
        $this->base_url = $baseUrl . $this->base_endpoint;
        return $this;
    }

    public function setBearer($bearer) {
        $this->bearer = $bearer;
        return $this;
    }

    public function get($endPoint, $bearer = null) {
        return $this->apiCall($endPoint, '', [], $bearer);
    }

    public function post($endPoint, $data, $bearer = null) {
        return $this->apiCall($endPoint, 'POST', $data, $bearer);
    }

    public function put($endPoint, $data, $bearer = null) {
        return $this->apiCall($endPoint, 'PUT', $data, $bearer);
    }

    public function delete($endPoint, $data, $bearer = null) {
        return $this->apiCall($endPoint, 'DELETE', $data, $bearer);
    }

    private function apiCall($endPoint, $method='', $data = [], $bearer = null) {
        if ($this->base_url==null) {
            throw new \Exception('no base url api call');
        }
        if ($bearer==null) {
            $bearer = $this->bearer;
        }
        $data_string = '';
        $ch = \curl_init($this->base_url . $endPoint);
        $headers = [
            'Accept: application/json'
        ];
        if ($method!='') {
            $data_string = \json_encode((object)$data);
            $headers = \array_merge($headers, [
                'Content-Type: application/json',
                'Content-Length: ' . \strlen($data_string)
            ]);
            \curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, $method);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $data_string);
        }
        if ($bearer!=null) {
            $headers[] = 'Authorization: Bearer '.$bearer;
        }
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);
        $result = \curl_exec($ch);
        if (\curl_errno($ch)) {
            $error_msg = \curl_error($ch);
            throw new \Exception($error_msg);
        }
        $httpcode = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        \curl_close($ch);
        if ($httpcode==204) {
            return null;
        }
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
            throw new ApiException($result);
        }
        throw new RemoteException($result);
    }


    public function authClientVerify($client_id, $key) {
        return $this->apiCall('/client/authorize', 'POST', ['client_id' => $client_id], $key);
    }
}