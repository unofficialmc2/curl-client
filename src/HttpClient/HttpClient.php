<?php
declare(strict_types=1);

namespace HttpClient;

use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class ApiHttpClient
 * @package Api\Utilities
 */
class HttpClient implements HttpClientInterface
{

    /** @var array<string, mixed> Tableaux de paramètre pour les different curl */
    protected array $curlsParam = [];
    /** @var resource Resource qui est donner par curl_multi_init() */
    protected $curlMulHand;
    /** @var LoggerInterface|null */
    protected ?LoggerInterface $logger;
    /** @var array<string,resource> liste des curl init */
    protected array $curls = [];
    /** @var array<string,HttpResponse> tableaux de résultat des curls */
    protected array $curlResult;
    /** @var bool indique si le process est fini */
    protected bool $endOfProcess = false;
    /** @var bool flag pour savoir si on suit la redirection */
    private bool $followLocation = false;
    /** @var int durée du time out */
    private int $timeout = 30;

    /**
     * ApiHttpClient constructor.
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(?LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function addParamRequest(string $url, array $headers = [], string $methode = HttpMethod::GET, string $data = ''): string
    {
        $clef = $this->getClef(8);
        $param = ["url" => $url, "headers" => $headers, "methode" => $methode, "data" => $data];
        $this->curlsParam[$clef] = $param;
        return $clef;
    }

    /**
     * @param int $length
     * @return string
     */
    private function getClef(int $length): string
    {
        /** @noinspection CryptographicallySecureRandomnessInspection */
        $data = openssl_random_pseudo_bytes($length, $strong);
        if (false === $strong || false === $data) {
            throw new RuntimeException("Un problème est survenu lors d'une génération cryptographique.");
        }
        return substr(bin2hex($data), $length);
    }

    /**
     * @inheritDoc
     */
    public function execAll(): void
    {
        $this->endOfProcess = false;
        $active = null;
        $this->initAll();
        curl_multi_exec($this->curlMulHand, $active);
    }

    /**
     * @return bool
     */
    private function initAll(): bool
    {
        $this->curlMulHand = curl_multi_init();
        foreach ($this->curlsParam as $clef => $curlparam) {
            $curl = $this->initNewCurl($curlparam);
            if (!$curl) {
                $this->log('error', "Problème dans l'initialisation d'un curl", ["curlparam" => $curlparam]);
                return false;
            }
            $this->curls[$clef] = $curl;
            curl_multi_add_handle($this->curlMulHand, $curl);
        }
        return true;
    }

    /**
     * @param array<string,mixed> $curlparam
     * @return false|resource
     */
    private function initNewCurl(array $curlparam)
    {
        $curl = curl_init($curlparam["url"]);
        if ($curl === false) {
            return false;
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headerAdapter($curlparam["headers"]));
        $this->setCurlMethode($curlparam["methode"], $curl, $curlparam["data"]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $this->followLocation);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);

        return $curl;
    }

    /**
     * adapter les headers pour CUrl
     * @param array<string,mixed> $headers
     * @return array<int,string>
     */
    private function headerAdapter(array $headers): array
    {
        $curlHeaders = [];
        foreach ($headers as $key => $header) {
            $curlHeaders[] = sprintf("%s: %s", $key, (string)$header);
        }
        return $curlHeaders;
    }

    /**
     * @param string $methode
     * @param resource $curl
     * @param string $data
     */
    private function setCurlMethode(string $methode, $curl, string $data): void
    {
        switch ($methode) :
            case HttpMethod::GET:
                break;
            case HttpMethod::POST:
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case HttpMethod::DELETE:
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $methode);
                break;
        endswitch;
    }

    /**
     * @param-stan 'error'|'debug' $type
     * @param string $type
     * @param string $message
     * @param array<string|int,string> $context
     * @return void
     */
    private function log(string $type, string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            switch ($type) {
                case 'error':
                    $this->logger->error($message, $context);
                    break;
                case 'debug':
                    $this->logger->debug($message, $context);
                    break;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getResult(string $clef): HttpResponse
    {
        if (!$this->endOfProcess) {
            $this->waitResult();
        }
        if (!array_key_exists($clef, $this->curlResult)) {
            $this->log('error', "La clef curl n'éxiste pas", ["clef Curl" => $clef]);
            throw new RuntimeException("La clef curl n'éxiste pas");
        }
        return $this->curlResult[$clef];
    }

    /**
     * @inheritDoc
     */
    public function waitResult(): void
    {
        $active = null;
        do {
            $status = curl_multi_exec($this->curlMulHand, $active);
            if ($active) {
                curl_multi_select($this->curlMulHand);
            }
        } while ($active && $status === CURLM_OK);
        $result = [];
        //Verifier les erreurs
        if ($status !== CURLM_OK) {
            $this->log('error', "Error Curl", ["error" => curl_multi_strerror($status)]);
            throw new RuntimeException("Une erreur a eu lieu avec le serveur");
        }
        foreach ($this->curls as $clef => $curl) {
            /** @var string|null $response */
            $response = curl_multi_getcontent($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            $status = curl_errno($curl);
            $result[$clef] = $this->makeResponse($status, $response, $code, $error);
            curl_multi_remove_handle($this->curlMulHand, $curl);
        }
        $this->curlResult = $result;
        $this->endOfProcess = true;
        $this->closeMultiCurl();
    }

    /**
     * @param int $status
     * @param null|bool|string $response
     * @param mixed $code
     * @param string $error
     * @return \HttpClient\HttpResponse
     */
    private function makeResponse(int $status, $response, $code, string $error): HttpResponse
    {
        if ($status !== CURLE_OK) {
            $this->log('error', "Error Curl", ["error" => curl_strerror($status)]);
            switch ($status) {
                case CURLE_OPERATION_TIMEOUTED:
                    return new HttpResponse(408, [], 'TIMEOUT');
                default:
                    throw new RuntimeException("Une erreur a eu lieu avec le serveur");
            }
        }
        if (!is_string($response)) {
            $this->log('error', "Curl error", ["error" => $error]);
            return new HttpResponse(false, [], $error);
        }
        $splitedRep = preg_split("/\r\n\r\n|\n\n/", $response);
        $headers = $this->headerParse($splitedRep[0] ?? '');
        return new HttpResponse($code, $headers, $splitedRep[1] ?? '');
    }

    /**
     * @param string $rawHeaders
     * @return array<string, string>
     */
    private function headerParse(string $rawHeaders): array
    {
        if (empty($rawHeaders)) {
            return [];
        }
        $headers = [];
        $key = '';
        foreach (explode("\n", $rawHeaders) as $i => $h) {
            $h = explode(':', $h, 2);
            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } elseif (is_array($headers[$h[0]])) {
                    $headers[$h[0]] = array_merge($headers[$h[0]], [trim($h[1])]);
                } else {
                    $headers[$h[0]] = array_merge([$headers[$h[0]]], [trim($h[1])]);
                }
                $key = strtolower($h[0]);
            } else {
                if ($h[0][0] === "\t") {
                    $headers[$key] .= "\r\n\t" . trim($h[0]);
                } elseif (!$key) {
                    $headers[0] = trim($h[0]);
                }
            }
        }
        return $headers;
    }

    /**
     *
     */
    private function closeMultiCurl(): void
    {
        if (is_resource($this->curlMulHand)) {
            curl_multi_close($this->curlMulHand);
        }
    }

    /**
     * @inheritDoc
     */
    public function curlUnique(string $url, array $headers = [], string $methode = HttpMethod::GET, string $data = ''): HttpResponse
    {
        $curl = $this->initNewCurl([
            "url" => $url,
            "headers" => $headers,
            "methode" => $methode,
            "data" => $data,
            "post" => $methode === HttpMethod::POST
        ]);
        if (!$curl) {
            throw new RuntimeException("Erreur de curl ");
        }
// Vérifie les erreurs et affiche le message d'erreur
        $curlResult = curl_exec($curl);
        $status = curl_errno($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        $response = $this->makeResponse($status, $curlResult, $code, $error);
        curl_close($curl);
        return $response;
    }

    /**
     *
     */
    public function followRedirect(): void
    {
        $this->followLocation = true;
    }

    /**
     * @inheritDoc
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }
}
