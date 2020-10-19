<?php
namespace DigitaleWerte\FortiPHP;

/**
 * Class FgtPHP
 */
class FgtPHP
{
    private $unsafeConnection = false;

    /**
     * possible Values: API, USER
     * @var string
     */
    private $authenticationMode = "apikey";

    private $authApiKey = "";

    private $reqProtocol = "https";

    private $devAddress;

    private $devPort = 443;

    private $devicename = "";
    /**
     * FgtPHP constructor.
     * @param string $address Adress of the device you want to connecto to
     * @param int $port
     * @param string $method http or https (standard)
     */
    function __construct($address, $port = 443, $protocol = "https")
    {
        $this->devAddress = $address;

        if ($port >= 1 AND $port <= 65535) {
            $this->devPort = $port;
        } else {
            throw new InvalidArgumentException("Error: Port must be between 1 and 65535");

        }

        if ($protocol == "https" OR $protocol == "http") {
            $this->reqProtocol = $protocol;
        } else {
            throw new InvalidArgumentException("Error. Protocol must be http or https");


        }

    }

    /**
     * @param string $apikey
     */
    function setApikey($apikey) {
        if(preg_match('/[a-z0-9]/i', $apikey)) {
            $this->authApiKey = $apikey;
        } else {
            throw new InvalidArgumentException("Error: API key is not in a valid format.");
        }
    }

    /**
     * With this function you can change de
     * @param boolean $unsafe
     */
    function setUnsafeConnection($unsafe) {

        $this->unsafeConnection = $unsafe;
    }

    /**
     * @param string $vdom Name of the vdom you want to get the configuration from. Optional, by default you will get the entire configuration (global)
     * @return string Device Configuration
     *
     */
    function getConfiguration($vdom = "global") {

        $scope = "global";


        if ($vdom != "global") {
            $scope = "vdom";

        }

        $request['method'] = 'GET';
        $request['uri'] = "/api/v2/monitor/system/config/backup";
        $request['parameter'] = array( "scope" => $scope);

        return $this->doRequest($request);

    }

    function getAddresses() {


        $request['method'] = 'GET';
        $request['uri'] = "/api/v2/cmdb/firewall/address/";


        return $this->doRequest($request);

    }

    function getSystemTime() {


        $request['method'] = 'GET';
        $request['uri'] = "/api/v2/monitor/system/time";


        return $this->doRequest($request);

    }

    function getDeviceName() {

    }

    function getSystemFirmware() {

        $request['method'] = 'GET';
        $request['uri'] = "/api/v2/monitor/system/firmware";

        return $this->doRequest($request);

    }

    /**
     * @param string $source Source of the Installation File. Valid values are 'upload' and 'fortiguard'
     * @param bool $format_partition
     * @param string $file
     */
    function updateSystemFirmware($source, $filename = 'null', $format_partition = false ,$file = 'null') {
        $b64file = null;
        if ($source != 'upload' AND $source != 'fortiguard') {
            throw new Exception($source . "is not a valid value as source. Use \"upload\" or \"fortiguard\". ");
            return;
        }

        if ($source == 'upload') {
            $b64file = base64_encode($file);

        }

        $request['method'] = 'POST';
        $request['uri'] = "/api/v2/monitor/system/firmware/upgrade";
        $request['postdata'] = '{
            "source":"'.$source.'",
            "filename":"'.$filename.'",
            "format_partition":false,
            "file_content":"'.$b64file.'"
        }';

        return $this->doRequest($request);

    }


    /**
     * @param $reqParam Array with many fields. the required fields are "method" (GET,POST,UPDATE,DELTE), "uri" and optional 'paramater'
     */
    private function doRequest($reqParam) {
        $ch = curl_init();

        /**
         * The params we want to add to the URL
         */
        $getParams = '';

        // Our Array for the headers we have to send
        $headers = array();

        //the parameters we get from the caller...
        if (isset($reqParam['parameter'])) {
            foreach($reqParam['parameter'] as $key=>$value) {
                $getParams .= $key.'='.$value.'&';
            }
            $getParams = trim($getParams, '&');
        }

        $curlopturl = $this->reqProtocol . "://" . $this->devAddress . ":" . $this->devPort . $reqParam['uri'] . "?" . $getParams;



        curl_setopt($ch, CURLOPT_URL, $curlopturl);

        /**
         * Set the Request Type
         */
        switch ($reqParam['method']) {
            case "GET":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                break;
            case "POST":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                if(isset($reqParam['postdata'])) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $reqParam['postdata']);
                    $headers[] = 'Content-Type: application/json';
                    $headers[] = 'Content-Length:' . strlen($reqParam['postdata']);
                }

                break;
            case "PUT":
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqParam['data']));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                break;
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($reqParam['data']));
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        }

        /**
         * Set unsafe Options...
         */
        if ($this->unsafeConnection) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }


        /**
         * Filling Headers
         */


        if ($this->authenticationMode == "apikey") {
            $headers[] = 'Authorization: Bearer ' . $this->authApiKey;
        }

        $headers[] = 'User-Agent: DW Services FortGate API Wrapper';
        $headers[] = 'Cache-Control: no-cache';

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);



        $server_output = curl_exec ($ch);

        return  $server_output;

        curl_close ($ch);
    }

}