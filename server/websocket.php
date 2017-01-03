<?php
namespace server;

/**
 * 
 * @package server.websocket
 * @author mrlin <714480119@qq.com>
 */
class Websocket
{
	public $path = '';

	public function doHandshake($serv, $buffer)
    {
        $magicGUID = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        $headers   = array();
        $lines     = explode("\n", $buffer);
        foreach ($lines as $line)
        {
            if (strpos($line, ":") !== false)
            {
                $header                                = explode(":", $line, 2);
                $headers[strtolower(trim($header[0]))] = trim($header[1]);
            }
            elseif (stripos($line, "get ") !== false)
            {
                preg_match("/GET (.*) HTTP/i", $buffer, $reqResource);
                $headers['get'] = trim($reqResource[1]);
                $serv->path = $headers['get'];  // websocket only use Http::GET
                $this->logInfo("client request uri = [$serv->path]");
            }
        }
        
        if (!isset($headers['get']) || !$this->checkUri($headers['get']))
        {          
            $handshakeResponse = "HTTP/1.1 405 Method Not Allowed\r\n\r\n";
        }

        if (!isset($headers['host']) || !$this->checkHost($headers['host']))
        {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }

        if (!isset($headers['upgrade']) || strtolower($headers['upgrade']) != 'websocket')
        {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }

        if (!isset($headers['connection']) || strpos(strtolower($headers['connection']), 'upgrade') === FALSE)
        {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }

        if (!isset($headers['sec-websocket-key']))
        {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }

        if (!isset($headers['sec-websocket-version']) || strtolower($headers['sec-websocket-version']) != 13)
        {
            $handshakeResponse = "HTTP/1.1 426 Upgrade Required\r\nSec-WebSocketVersion: 13";
        }

        if (($this->headerOriginRequired && !isset($headers['origin'])) || ($this->headerOriginRequired && !$this->checkOrigin($headers['origin'])))
        {
            $handshakeResponse = "HTTP/1.1 403 Forbidden";
        }

        if (($this->headerSecWebSocketProtocolRequired && !isset($headers['sec-websocket-protocol'])) || ($this->headerSecWebSocketProtocolRequired && !$this->checkWebsocProtocol($headers['sec-websocket-protocol'])))
        {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }

        if (($this->headerSecWebSocketExtensionsRequired && !isset($headers['sec-websocket-extensions'])) || ($this->headerSecWebSocketExtensionsRequired && !$this->checkWebsocExtensions($headers['sec-websocket-extensions'])))
        {
            $handshakeResponse = "HTTP/1.1 400 Bad Request";
        }

        // 如果上面设置了$handshakeResponse说明握手失败，主动关闭
        if (isset($handshakeResponse))
        {
            socket_write($serv->socket, $handshakeResponse, strlen($handshakeResponse));
            $this->disconnect($serv->socket);
            return;
        }

        $serv->headers   = $headers;
        $serv->handshake = $buffer;
        
        $webSocketKeyHash = sha1($headers['sec-websocket-key'] . $magicGUID);
        
        $rawToken = "";
        for ($i = 0; $i < 20; $i++)
        {
            $rawToken .= chr(hexdec(substr($webSocketKeyHash, $i * 2, 2)));
        }
        $handshakeToken = base64_encode($rawToken) . "\r\n";
        
        $subProtocol = (isset($headers['sec-websocket-protocol'])) ? $this->processProtocol($headers['sec-websocket-protocol']) : "";
        $extensions  = (isset($headers['sec-websocket-extensions'])) ? $this->processExtensions($headers['sec-websocket-extensions']) : "";
        
        $handshakeResponse = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $handshakeToken$subProtocol$extensions\r\n";

        socket_write($serv->socket, $handshakeResponse, strlen($handshakeResponse));
        $this->connected($serv);
    }

    protected function checkHost($hostName)
    {
        return true; // Override and return false if the host is not one that you would expect.
    }
    
    protected function checkUri($uri)
    {
        return true; // Override and return false if the uri is not one that you would expect.
    }
    
    protected function checkOrigin($origin)
    {
        return true; // Override and return false if the origin is not one that you would expect.
    }
    
    protected function checkWebsocProtocol($protocol)
    {
        return true; // Override and return false if a protocol is not found that you would expect.
    }
    
    protected function checkWebsocExtensions($extensions)
    {
        return true; // Override and return false if an extension is not found that you would expect.
    }
    
    protected function processProtocol($protocol)
    {
        return ""; // return either "Sec-WebSocket-Protocol: SelectedProtocolFromClientList\r\n" or return an empty string.  

        // The carriage return/newline combo must appear at the end of a non-empty string, and must not
        // appear at the beginning of the string nor in an otherwise empty string, or it will be considered part of 
        // the response body, which will trigger an error in the client as it will not be formatted correctly.
    }

}