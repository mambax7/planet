<?php

/*
   IXR - The Inutio XML-RPC Library - (c) Incutio Ltd 2002
   Version 1.62WP - Simon Willison, 11th July 2003 (htmlentities -> htmlspecialchars)
           ^^^^^^ (We've made some changes)
   Site:   http://scripts.incutio.com/xmlrpc/
   Manual: http://scripts.incutio.com/xmlrpc/manual.php
   Made available under the BSD License: http://www.opensource.org/licenses/bsd-license.php
*/

/**
 * Class IXR_Value
 */
class IXR_Value
{
    public $data;
    public $type;

    /**
     * IXR_Value constructor.
     * @param      $data
     * @param bool $type
     */
    public function __construct($data, $type = false)
    {
        $this->data = $data;
        if (!$type) {
            $type = $this->calculateType();
        }
        $this->type = $type;
        if ('struct' === $type) {
            /* Turn all the values in the array in to new IXR_Value objects */
            foreach ($this->data as $key => $value) {
                $this->data[$key] = new IXR_Value($value);
            }
        }
        if ('array' === $type) {
            for ($i = 0, $j = count($this->data); $i < $j; ++$i) {
                $this->data[$i] = new IXR_Value($this->data[$i]);
            }
        }
    }

    /**
     * @return string
     */
    public function calculateType()
    {
        if (true === $this->data || false === $this->data) {
            return 'boolean';
        }
        if (is_int($this->data)) {
            return 'int';
        }
        if (is_float($this->data)) {
            return 'double';
        }
        // Deal with IXR object types base64 and date
        if (is_object($this->data) && is_a($this->data, 'IXR_Date')) {
            return 'date';
        }
        if (is_object($this->data) && is_a($this->data, 'IXR_Base64')) {
            return 'base64';
        }
        // If it is a normal PHP object convert it in to a struct
        if (is_object($this->data)) {
            $this->data = get_object_vars($this->data);

            return 'struct';
        }
        if (!is_array($this->data)) {
            return 'string';
        }
        /* We have an array - is it an array or a struct ? */
        if ($this->isStruct($this->data)) {
            return 'struct';
        } else {
            return 'array';
        }
    }

    /**
     * @return bool|string
     */
    public function getXml()
    {
        /* Return XML for this value */
        switch ($this->type) {
            case 'boolean':
                return '<boolean>' . ($this->data ? '1' : '0') . '</boolean>';
                break;
            case 'int':
                return '<int>' . $this->data . '</int>';
                break;
            case 'double':
                return '<double>' . $this->data . '</double>';
                break;
            case 'string':
                return '<string>' . htmlspecialchars($this->data) . '</string>';
                break;
            case 'array':
                $return = '<array><data>' . "\n";
                foreach ($this->data as $item) {
                    $return .= '  <value>' . $item->getXml() . "</value>\n";
                }
                $return .= '</data></array>';

                return $return;
                break;
            case 'struct':
                $return = '<struct>' . "\n";
                foreach ($this->data as $name => $value) {
                    $return .= "  <member><name>$name</name><value>";
                    $return .= $value->getXml() . "</value></member>\n";
                }
                $return .= '</struct>';

                return $return;
                break;
            case 'date':
            case 'base64':
                return $this->data->getXml();
                break;
        }

        return false;
    }

    /**
     * @param $array
     * @return bool
     */
    public function isStruct($array)
    {
        /* Nasty function to check if an array is a struct or not */
        $expected = 0;
        foreach ($array as $key => $value) {
            if ((string)$key != (string)$expected) {
                return true;
            }
            ++$expected;
        }

        return false;
    }
}

/**
 * Class IXR_Message
 */
class IXR_Message
{
    public $message;
    public $messageType;  // methodCall / methodResponse / fault
    public $faultCode;
    public $faultString;
    public $methodName;
    public $params;
    // Current variable stacks
    public $_arraystructs      = [];   // The stack used to keep track of the current array/struct
    public $_arraystructstypes = []; // Stack keeping track of if things are structs or array
    public $_currentStructName = [];  // A stack as well
    public $_param;
    public $_value;
    public $_currentTag;
    public $_currentTagContents;
    // The XML parser
    public $_parser;

    /**
     * IXR_Message constructor.
     * @param $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @return bool
     */
    public function parse()
    {
        // first remove the XML declaration
        $this->message = preg_replace('/<\?xml(.*)?\?' . '>/', '', $this->message);
        if ('' == trim($this->message)) {
            return false;
        }
        $this->_parser = xml_parser_create();
        // Set XML parser to take the case of tags in to account
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
        // Set XML parser callback functions
        xml_set_object($this->_parser, $this);
        xml_set_elementHandler($this->_parser, 'tag_open', 'tag_close');
        xml_set_character_dataHandler($this->_parser, 'cdata');
        if (!xml_parse($this->_parser, $this->message)) {
            /* die(sprintf('XML error: %s at line %d',
                xml_error_string(xml_get_error_code($this->_parser)),
                xml_get_current_line_number($this->_parser))); */

            return false;
        }
        xml_parser_free($this->_parser);
        // Grab the error messages, if any
        if ('fault' === $this->messageType) {
            $this->faultCode   = $this->params[0]['faultCode'];
            $this->faultString = $this->params[0]['faultString'];
        }

        return true;
    }

    /**
     * @param $parser
     * @param $tag
     * @param $attr
     */
    public function tag_open($parser, $tag, $attr)
    {
        $this->currentTag = $tag;
        switch ($tag) {
            case 'methodCall':
            case 'methodResponse':
            case 'fault':
                $this->messageType = $tag;
                break;
            /* Deal with stacks of arrays and structs */
            case 'data':    // data is to all intents and puposes more interesting than array
                $this->_arraystructstypes[] = 'array';
                $this->_arraystructs[]      = [];
                break;
            case 'struct':
                $this->_arraystructstypes[] = 'struct';
                $this->_arraystructs[]      = [];
                break;
        }
    }

    /**
     * @param $parser
     * @param $cdata
     */
    public function cdata($parser, $cdata)
    {
        $this->_currentTagContents .= $cdata;
    }

    /**
     * @param $parser
     * @param $tag
     */
    public function tag_close($parser, $tag)
    {
        $valueFlag = false;
        switch ($tag) {
            case 'int':
            case 'i4':
                $value                     = (int)trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;
                break;
            case 'double':
                $value                     = (double)trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;
                break;
            case 'string':
                $value                     = (string)trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;
                break;
            case 'dateTime.iso8601':
                $value = new IXR_Date(trim($this->_currentTagContents));
                // $value = $iso->getTimestamp();
                $this->_currentTagContents = '';
                $valueFlag                 = true;
                break;
            case 'value':
                // "If no type is indicated, the type is string."
                if ('' != trim($this->_currentTagContents)) {
                    $value                     = (string)$this->_currentTagContents;
                    $this->_currentTagContents = '';
                    $valueFlag                 = true;
                }
                break;
            case 'boolean':
                $value                     = (boolean)trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                $valueFlag                 = true;
                break;
            case 'base64':
                $value                     = base64_decode(trim($this->_currentTagContents));
                $this->_currentTagContents = '';
                $valueFlag                 = true;
                break;
            /* Deal with stacks of arrays and structs */
            case 'data':
            case 'struct':
                $value = array_pop($this->_arraystructs);
                array_pop($this->_arraystructstypes);
                $valueFlag = true;
                break;
            case 'member':
                array_pop($this->_currentStructName);
                break;
            case 'name':
                $this->_currentStructName[] = trim($this->_currentTagContents);
                $this->_currentTagContents  = '';
                break;
            case 'methodName':
                $this->methodName          = trim($this->_currentTagContents);
                $this->_currentTagContents = '';
                break;
        }
        if ($valueFlag) {
            /*
            if (!is_array($value) && !is_object($value)) {
                $value = trim($value);
            }
            */
            if (count($this->_arraystructs) > 0) {
                // Add value to struct or array
                if ('struct' === $this->_arraystructstypes[count($this->_arraystructstypes) - 1]) {
                    // Add to struct
                    $this->_arraystructs[count($this->_arraystructs) - 1][$this->_currentStructName[count($this->_currentStructName) - 1]] = $value;
                } else {
                    // Add to array
                    $this->_arraystructs[count($this->_arraystructs) - 1][] = $value;
                }
            } else {
                // Just add as a paramater
                $this->params[] = $value;
            }
        }
    }
}

/**
 * Class IXR_Server
 */
class IXR_Server
{
    public $data;
    public $callbacks = [];
    public $message;
    public $capabilities;

    /**
     * IXR_Server constructor.
     * @param bool $callbacks
     * @param bool $data
     */
    public function __construct($callbacks = false, $data = false)
    {
        $this->setCapabilities();
        if ($callbacks) {
            $this->callbacks = $callbacks;
        }
        $this->setCallbacks();
        $this->serve($data);
    }

    /**
     * @param bool $data
     */
    public function serve($data = false)
    {
        if (!$data) {
            $http_raw_post_data = file_get_contents('php://input');
            if (!$http_raw_post_data) {
                die('XML-RPC server accepts POST requests only.');
            }
            $data = $http_raw_post_data;
        }
        $this->message = new IXR_Message($data);
        if (!$this->message->parse()) {
            $this->error(-32700, 'parse error. not well formed');
        }
        if ('methodCall' !== $this->message->messageType) {
            $this->error(-32600, 'server error. invalid xml-rpc. not conforming to spec. Request must be a methodCall');
        }
        $result = $this->call($this->message->methodName, $this->message->params);
        // Is the result an error?
        if (is_a($result, 'IXR_Error')) {
            $this->error($result);
        }
        // Encode the result
        $r         = new IXR_Value($result);
        $resultxml = $r->getXml();
        // Create the XML
        $xml = <<<EOD
<methodResponse>
  <params>
    <param>
      <value>
        $resultxml
      </value>
    </param>
  </params>
</methodResponse>

EOD;
        // Send it
        $this->output($xml);
    }

    /**
     * @param $methodname
     * @param $args
     * @return IXR_Error|mixed
     */
    public function call($methodname, $args)
    {
        if (!$this->hasMethod($methodname)) {
            return new IXR_Error(-32601, 'server error. requested method ' . $methodname . ' does not exist.');
        }
        $method = $this->callbacks[$methodname];
        // Perform the callback and send the response
        if (1 == count($args)) {
            // If only one paramater just send that instead of the whole array
            $args = $args[0];
        }
        // Are we dealing with a function or a method?
        if ('this:' === substr($method, 0, 5)) {
            // It's a class method - check it exists
            $method = substr($method, 5);
            if (!method_exists($this, $method)) {
                return new IXR_Error(-32601, 'server error. requested class method "' . $method . '" does not exist.');
            }
            // Call the method
            $result = $this->$method($args);
        } else {
            // It's a function - does it exist?
            if (is_array($method)) {
                if (!method_exists($method[0], $method[1])) {
                    return new IXR_Error(-32601, 'server error. requested object method "' . $method[1] . '" does not exist.');
                }
            } elseif (!function_exists($method)) {
                return new IXR_Error(-32601, 'server error. requested function "' . $method . '" does not exist.');
            }
            // Call the function
            $result = call_user_func($method, $args);
        }

        return $result;
    }

    /**
     * @param      $error
     * @param bool $message
     */
    public function error($error, $message = false)
    {
        // Accepts either an error object or an error code and message
        if ($message && !is_object($error)) {
            $error = new IXR_Error($error, $message);
        }
        $this->output($error->getXml());
    }

    /**
     * @param $xml
     */
    public function output($xml)
    {
        $xml    = '<?xml version="1.0"?>' . "\n" . $xml;
        $length = strlen($xml);
        header('Connection: close');
        header('Content-Length: ' . $length);
        header('Content-Type: text/xml');
        header('Date: ' . date('r'));
        echo $xml;
        exit;
    }

    /**
     * @param $method
     * @return bool
     */
    public function hasMethod($method)
    {
        return in_array($method, array_keys($this->callbacks));
    }

    public function setCapabilities()
    {
        // Initialises capabilities array
        $this->capabilities = [
            'xmlrpc'           => [
                'specUrl'     => 'http://www.xmlrpc.com/spec',
                'specVersion' => 1
            ],
            'faults_interop'   => [
                'specUrl'     => 'http://xmlrpc-epi.sourceforge.net/specs/rfc.fault_codes.php',
                'specVersion' => 20010516
            ],
            'system.multicall' => [
                'specUrl'     => 'http://www.xmlrpc.com/discuss/msgReader$1208',
                'specVersion' => 1
            ]
        ];
    }

    /**
     * @param $args
     * @return mixed
     */
    public function getCapabilities($args)
    {
        return $this->capabilities;
    }

    public function setCallbacks()
    {
        $this->callbacks['system.getCapabilities'] = 'this:getCapabilities';
        $this->callbacks['system.listMethods']     = 'this:listMethods';
        $this->callbacks['system.multicall']       = 'this:multiCall';
    }

    /**
     * @param $args
     * @return array
     */
    public function listMethods($args)
    {
        // Returns a list of methods - uses array_reverse to ensure user defined
        // methods are listed before server defined methods
        return array_reverse(array_keys($this->callbacks));
    }

    /**
     * @param $methodcalls
     * @return array
     */
    public function multiCall($methodcalls)
    {
        // See http://www.xmlrpc.com/discuss/msgReader$1208
        $return = [];
        foreach ($methodcalls as $call) {
            $method = $call['methodName'];
            $params = $call['params'];
            if ('system.multicall' === $method) {
                $result = new IXR_Error(-32600, 'Recursive calls to system.multicall are forbidden');
            } else {
                $result = $this->call($method, $params);
            }
            if (is_a($result, 'IXR_Error')) {
                $return[] = [
                    'faultCode'   => $result->code,
                    'faultString' => $result->message
                ];
            } else {
                $return[] = [$result];
            }
        }

        return $return;
    }
}

/**
 * Class IXR_Request
 */
class IXR_Request
{
    public $method;
    public $args;
    public $xml;

    /**
     * IXR_Request constructor.
     * @param $method
     * @param $args
     */
    public function __construct($method, $args)
    {
        $this->method = $method;
        $this->args   = $args;
        $this->xml    = <<<EOD
<?xml version="1.0"?>
<methodCall>
<methodName>{$this->method}</methodName>
<params>

EOD;
        foreach ($this->args as $arg) {
            $this->xml .= '<param><value>';
            $v         = new IXR_Value($arg);
            $this->xml .= $v->getXml();
            $this->xml .= "</value></param>\n";
        }
        $this->xml .= '</params></methodCall>';
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return strlen($this->xml);
    }

    /**
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }
}

/**
 * Class IXR_Client
 */
class IXR_Client
{
    public $server;
    public $port;
    public $path;
    public $useragent;
    public $response;
    public $timeout;
    public $vendor  = '';
    public $message = false;
    public $debug   = false;
    // Storage place for an error message
    public $error = false;

    /**
     * IXR_Client constructor.
     * @param        $server
     * @param bool   $path
     * @param int    $port
     * @param int    $timeout
     * @param string $vendor
     */
    public function __construct($server, $path = false, $port = 80, $timeout = 30, $vendor = '')
    {
        if (!$path) {
            // Assume we have been given a URL instead
            $bits         = parse_url($server);
            $this->server = $bits['host'];
            $this->port   = isset($bits['port']) ? $bits['port'] : 80;
            $this->path   = isset($bits['path']) ? $bits['path'] : '/';
            // Make absolutely sure we have a path
            if (!$this->path) {
                $this->path = '/';
            }
        } else {
            $this->server  = $server;
            $this->path    = $path;
            $this->port    = $port;
            $this->timeout = $timeout;
        }
        $this->useragent = 'The Incutio XML-RPC PHP Library';
    }

    /**
     * @return bool
     */
    public function query()
    {
        $args    = func_get_args();
        $method  = array_shift($args);
        $request = new IXR_Request($method, $args);
        $length  = $request->getLength();
        $xml     = $request->getXml();
        $r       = "\r\n";
        $request = "POST {$this->path} HTTP/1.0$r";
        $request .= "Host: {$this->server}$r";
        $request .= "Content-Type: text/xml$r";
        $request .= "User-Agent: {$this->useragent}$r";
        $request .= "Content-length: {$length}$r$r";
        $request .= $xml;
        // Now send the request
        if ($this->debug) {
            echo '<pre>' . htmlspecialchars($request) . "\n</pre>\n\n";
        }
        $fp = @fsockopen($this->server, $this->port, $errno, $errstr, $this->timeout);
        if (!$fp) {
            $this->error = new IXR_Error(-32300, 'transport error - could not open socket');

            return false;
        }
        fwrite($fp, $request);
        $contents       = '';
        $gotFirstLine   = false;
        $gettingHeaders = true;
        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            if (!$gotFirstLine) {
                // Check line for '200'
                if (false === strpos($line, '200')) {
                    $this->error = new IXR_Error(-32300, 'transport error - HTTP status code was not 200');

                    return false;
                }
                $gotFirstLine = true;
            }
            if ('' == trim($line)) {
                $gettingHeaders = false;
            }
            if (!$gettingHeaders) {
                $contents .= trim($line) . "\n";
            }
        }
        if ($this->debug) {
            echo '<pre>' . htmlspecialchars($contents) . "\n</pre>\n\n";
        }
        // Now parse what we've got back
        $this->message = new IXR_Message($contents);
        if (!$this->message->parse()) {
            // XML error
            $this->error = new IXR_Error(-32700, 'parse error. not well formed');

            return false;
        }
        // Is the message a fault?
        if ('fault' === $this->message->messageType) {
            $this->error = new IXR_Error($this->message->faultCode, $this->message->faultString);

            return false;
        }

        // Message must be OK
        return true;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        // methodResponses can only have one param - return that
        return $this->message->params[0];
    }

    /**
     * @return bool
     */
    public function isError()
    {
        return is_object($this->error);
    }

    /**
     * @return mixed
     */
    public function getErrorCode()
    {
        return $this->error->code;
    }

    /**
     * @return mixed
     */
    public function getErrorMessage()
    {
        return $this->error->message;
    }
}

/**
 * Class IXR_Error
 */
class IXR_Error
{
    public $code;
    public $message;

    /**
     * IXR_Error constructor.
     * @param $code
     * @param $message
     */
    public function __construct($code, $message)
    {
        $this->code    = $code;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getXml()
    {
        $xml = <<<EOD
<methodResponse>
  <fault>
    <value>
      <struct>
        <member>
          <name>faultCode</name>
          <value><int>{$this->code}</int></value>
        </member>
        <member>
          <name>faultString</name>
          <value><string>{$this->message}</string></value>
        </member>
      </struct>
    </value>
  </fault>
</methodResponse>

EOD;

        return $xml;
    }
}

/**
 * Class IXR_Date
 */
class IXR_Date
{
    public $year;
    public $month;
    public $day;
    public $hour;
    public $minute;
    public $second;
    public $timezone;

    /**
     * IXR_Date constructor.
     * @param $time
     */
    public function __construct($time)
    {
        // $time can be a PHP timestamp or an ISO one
        if (is_numeric($time)) {
            $this->parseTimestamp($time);
        } else {
            $this->parseIso($time);
        }
    }

    /**
     * @param $timestamp
     */
    public function parseTimestamp($timestamp)
    {
        $this->year   = date('Y', $timestamp);
        $this->month  = date('Y', $timestamp);
        $this->day    = date('Y', $timestamp);
        $this->hour   = date('H', $timestamp);
        $this->minute = date('i', $timestamp);
        $this->second = date('s', $timestamp);
    }

    /**
     * @param $iso
     */
    public function parseIso($iso)
    {
        $this->year     = substr($iso, 0, 4);
        $this->month    = substr($iso, 4, 2);
        $this->day      = substr($iso, 6, 2);
        $this->hour     = substr($iso, 9, 2);
        $this->minute   = substr($iso, 12, 2);
        $this->second   = substr($iso, 15, 2);
        $this->timezone = substr($iso, 17);
    }

    /**
     * @return string
     */
    public function getIso()
    {
        return $this->year . $this->month . $this->day . 'T' . $this->hour . ':' . $this->minute . ':' . $this->second . $this->timezone;
    }

    /**
     * @return string
     */
    public function getXml()
    {
        return '<dateTime.iso8601>' . $this->getIso() . '</dateTime.iso8601>';
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return mktime($this->hour, $this->minute, $this->second, $this->month, $this->day, $this->year);
    }
}

/**
 * Class IXR_Base64
 */
class IXR_Base64
{
    public $data;

    /**
     * IXR_Base64 constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getXml()
    {
        return '<base64>' . base64_encode($this->data) . '</base64>';
    }
}

/**
 * Class IXR_IntrospectionServer
 */
class IXR_IntrospectionServer extends IXR_Server
{
    public $signatures;
    public $help;

    /**
     * IXR_IntrospectionServer constructor.
     */
    public function __construct()
    {
        $this->setCallbacks();
        $this->setCapabilities();
        $this->capabilities['introspection'] = [
            'specUrl'     => 'http://xmlrpc.usefulinc.com/doc/reserved.html',
            'specVersion' => 1
        ];
        $this->addCallback('system.methodSignature', 'this:methodSignature', ['array', 'string'], 'Returns an array describing the return type and required parameters of a method');
        $this->addCallback('system.getCapabilities', 'this:getCapabilities', ['struct'], 'Returns a struct describing the XML-RPC specifications supported by this server');
        $this->addCallback('system.listMethods', 'this:listMethods', ['array'], 'Returns an array of available methods on this server');
        $this->addCallback('system.methodHelp', 'this:methodHelp', ['string', 'string'], 'Returns a documentation string for the specified method');
    }

    /**
     * @param $method
     * @param $callback
     * @param $args
     * @param $help
     */
    public function addCallback($method, $callback, $args, $help)
    {
        $this->callbacks[$method]  = $callback;
        $this->signatures[$method] = $args;
        $this->help[$method]       = $help;
    }

    /**
     * @param $methodname
     * @param $args
     * @return IXR_Error|mixed
     */
    public function call($methodname, $args)
    {
        // Make sure it's in an array
        if ($args && !is_array($args)) {
            $args = [$args];
        }
        // Over-rides default call method, adds signature check
        if (!$this->hasMethod($methodname)) {
            return new IXR_Error(-32601, 'server error. requested method "' . $this->message->methodName . '" not specified.');
        }
        $method     = $this->callbacks[$methodname];
        $signature  = $this->signatures[$methodname];
        $returnType = array_shift($signature);
        // Check the number of arguments
        if (count($args) != count($signature)) {
            // print 'Num of args: '.count($args).' Num in signature: '.count($signature);
            return new IXR_Error(-32602, 'server error. wrong number of method parameters');
        }
        // Check the argument types
        $ok         = true;
        $argsbackup = $args;
        for ($i = 0, $j = count($args); $i < $j; ++$i) {
            $arg  = array_shift($args);
            $type = array_shift($signature);
            switch ($type) {
                case 'int':
                case 'i4':
                    if (is_array($arg) || !is_int($arg)) {
                        $ok = false;
                    }
                    break;
                case 'base64':
                case 'string':
                    if (!is_string($arg)) {
                        $ok = false;
                    }
                    break;
                case 'boolean':
                    if (false !== $arg && true !== $arg) {
                        $ok = false;
                    }
                    break;
                case 'float':
                case 'double':
                    if (!is_float($arg)) {
                        $ok = false;
                    }
                    break;
                case 'date':
                case 'dateTime.iso8601':
                    if (!is_a($arg, 'IXR_Date')) {
                        $ok = false;
                    }
                    break;
            }
            if (!$ok) {
                return new IXR_Error(-32602, 'server error. invalid method parameters');
            }
        }

        // It passed the test - run the "real" method call
        return parent::call($methodname, $argsbackup);
    }

    /**
     * @param $method
     * @return array|IXR_Error
     */
    public function methodSignature($method)
    {
        if (!$this->hasMethod($method)) {
            return new IXR_Error(-32601, 'server error. requested method "' . $method . '" not specified.');
        }
        // We should be returning an array of types
        $types  = $this->signatures[$method];
        $return = [];
        foreach ($types as $type) {
            switch ($type) {
                case 'string':
                    $return[] = 'string';
                    break;
                case 'int':
                case 'i4':
                    $return[] = 42;
                    break;
                case 'double':
                    $return[] = 3.1415;
                    break;
                case 'dateTime.iso8601':
                    $return[] = new IXR_Date(time());
                    break;
                case 'boolean':
                    $return[] = true;
                    break;
                case 'base64':
                    $return[] = new IXR_Base64('base64');
                    break;
                case 'array':
                    $return[] = ['array'];
                    break;
                case 'struct':
                    $return[] = ['struct' => 'struct'];
                    break;
            }
        }

        return $return;
    }

    /**
     * @param $method
     * @return mixed
     */
    public function methodHelp($method)
    {
        return $this->help[$method];
    }
}

/**
 * Class IXR_ClientMulticall
 */
class IXR_ClientMulticall extends IXR_Client
{
    public $calls = [];

    /**
     * IXR_ClientMulticall constructor.
     * @param      $server
     * @param bool $path
     * @param int  $port
     */
    public function __construct($server, $path = false, $port = 80)
    {
        parent::IXR_Client($server, $path, $port);
        $this->useragent = 'The Incutio XML-RPC PHP Library (multicall client)';
    }

    public function addCall()
    {
        $args          = func_get_args();
        $methodName    = array_shift($args);
        $struct        = [
            'methodName' => $methodName,
            'params'     => $args
        ];
        $this->calls[] = $struct;
    }

    /**
     * @return bool
     */
    public function query()
    {
        // Prepare multicall, then call the parent::query() method
        return parent::query('system.multicall', $this->calls);
    }
}
