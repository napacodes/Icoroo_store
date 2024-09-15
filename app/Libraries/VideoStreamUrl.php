<?php

namespace App\Libraries;

class VideoStreamUrl
{
    private $url = "";
    private $sizeReq = 5 * 1000000;
    private $curlStream = NULL;
    private $followRedirects = true;
    private $start  = -1;
    private $end    = -1;
    private $size   = -1;
    public $currentLine = '';
    public $totalLineCount = 0;
    public $totalLength = 0;
    private $http_version = "HTTP/1.1"; 
 
    function __construct($URL) 
    {
        $this->http_version = $_SERVER['SERVER_PROTOCOL'] ?? $this->http_version;
        $this->url = $URL;
        $this->setSize();
    }
     
    public function curlCallback($curl, $data) 
    {

        $this->currentLine .= $data;
        $lines = explode("\n", $this->currentLine);
        $numLines = count($lines) - 1;
        $this->currentLine = $lines[$numLines];

        for ($i = 0; $i < $numLines; ++$i) {
            $this->processLine($lines[$i]);
            ++$this->totalLineCount;
            $this->totalLength += strlen($lines[$i]) + 1;
        }
        return strlen($data);

    }

    public function processLine($str) 
    {
        echo $str . "\n";
    }

    /**
     * Set proper header to serve the video content
     */
    private function setHeader()
    {
        ob_get_clean();
        header("Content-Type: video/mp4");
        header("Cache-Control: max-age=2592000, public");
        header("Expires: ".gmdate('D, d M Y H:i:s', time()+2592000) . ' GMT');
        $this->start = 0;

        $this->end = $this->size;
        header("Accept-Ranges: 0-" . ($this->size + 1));
        if (isset($_SERVER['HTTP_RANGE'])) {
            $ranges = explode("-", explode("=", $_SERVER['HTTP_RANGE'])[1]);
            $this->start = $ranges[0];

            if($ranges[1] === "")
            {
                $this->end = $ranges[0] + $this->sizeReq;
                if($this->end > $this->size) $this->end = $this->size;
            } 
            else 
            {
                $this->end = $ranges[1];
            }

            $length = $this->end - $this->start + 1;

            header("{$this->http_version} 206 Partial Content");
            header("Content-Length: ".$length);
            header("Content-Range: bytes $this->start-$this->end/" . ($this->size + 1));
        }
        else
        {
            header("Content-Length: ". ($this->end - $this->start + 1));
        }        
    }
    
    /**
     * close curretly opened stream
     */
    private function end()
    {
        curl_close($this->curlStream);
        exit;
    }
     
    /**
     * perform the streaming of calculated range
     */
    private function stream()
    {
        $this->curlStream = curl_init();

        curl_setopt($this->curlStream, CURLOPT_URL, $this->url);
        curl_setopt($this->curlStream, CURLOPT_WRITEFUNCTION, array($this, 'curlCallback'));
        curl_setopt($this->curlStream, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = "Pragma: no-cache";
        $headers[] = "Dnt: 1";
        $headers[] = "Accept-Encoding: identity;q=1, *;q=0";
        $headers[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.139 Safari/537.36";
        $headers[] = "Accept: */*";
        $headers[] = "Cache-Control: no-cache";
        $headers[] = "Connection: keep-alive";
        $headers[] = "Range: bytes={$this->start}-{$this->end}";

        curl_setopt($this->curlStream, CURLOPT_HTTPHEADER, $headers);
        curl_exec($this->curlStream);

        $this->processLine($this->currentLine);
    }
     
    /**
     * get the size of the external video
     */
    private function setSize()
    {
        $headers = get_headers($this->url, 1);

        if(isset($headers["Location"]) && $this->followRedirects)
        {
            $this->url = $headers["Location"];
            $this->setSize();
            return;
        }
        if(strpos($headers[0], '200 OK') === false)
        {            
            throw new \Exception("URL not valid, not a 200 reponse code");
        }
        if(!isset($headers["Content-Length"]))
        {
            throw new \Exception("URL not valid, could not find the video size"); 
        }

        $this->size = (int) $headers["Content-Length"];
    }

    /**
     * Start streaming video content
     */
    function start()
    {
        $this->setHeader();
        $this->stream();
        $this->end();
    }
}