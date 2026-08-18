<?php

declare(strict_types=1);

namespace RichnessAgency\RichWhatsApp\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RichnessAgency\RichWhatsApp\Contracts\BridgeClient;
use RichnessAgency\RichWhatsApp\Exceptions\BridgeAuthenticationException;
use RichnessAgency\RichWhatsApp\Exceptions\BridgeUnavailableException;
use RichnessAgency\RichWhatsApp\Exceptions\ConfigurationException;
use RichnessAgency\RichWhatsApp\Exceptions\MessageSendException;
use RichnessAgency\RichWhatsApp\Exceptions\WhatsAppDisconnectedException;

/**
 * Single isolated HTTP client for the Node.js WhatsApp Bridge.
 *
 * This is the ONLY place Http:: is used. Every BridgeClient method performs
 * exactly one request and returns the parsed bridge body. Failures are
 * translated into package exceptions; the bridge token is never logged and
 * only ever travels in the Authorization header.
 */
class WhatsAppBridgeClient implements BridgeClient
{
    protected array $timeouts;

    public function __construct(array $timeouts = [])
    {
        $this->timeouts = [
            'timeout' => (int) ($timeouts['timeout'] ?? config('rich-whatsapp.http_timeout', 10)),
            'connect' => (int) ($timeouts['connect'] ?? config('rich-whatsapp.connect_timeout', 3)),
        ];
    }

    // ------------------------------------------------------------ endpoints

    public function health(): array
    {
        return $this->request('GET', '/api/v1/health');
    }

    public function healthDetails(): array
    {
        return $this->request('GET', '/api/v1/health/details');
    }

    public function startSession(): array
    {
        return $this->request('POST', '/api/v1/session/start');
    }

    public function sessionStatus(): array
    {
        return $this->request('GET', '/api/v1/session/status');
    }

    public function qr(): array
    {
        return $this->request('GET', '/api/v1/session/qr');
    }

    public function reconnect(): array
    {
        return $this->request('POST', '/api/v1/session/reconnect');
    }

    public function logout(): array
    {
        return $this->request('POST', '/api/v1/session/logout');
    }

    public function sendText(string $requestId, string $phone, string $message): array
    {
        return $this->request('POST', '/api/v1/messages/text', [
            'json' => [
                'request_id' => $requestId,
                'to' => $phone,
                'message' => $message,
            ],
        ]);
    }

    public function sendMedia(
        string $requestId,
        string $phone,
        string $path,
        string $filename,
        string $mime,
        ?string $caption = null
    ): array {
        $fields = [
            'request_id' => $requestId,
            'to' => $phone,
            'type' => $this->typeForMime($mime),
            'filename' => $filename,
        ];

        if ($caption !== null && $caption !== '') {
            $fields['caption'] = $caption;
        }

        $stream = null;

        try {
            $stream = fopen($path, 'rb');

            if ($stream === false) {
                throw new MessageSendException("Unable to open media file for reading: {$path}", 0);
            }

            return $this->request('POST', '/api/v1/messages/media', [
                'fields' => $fields,
                'file' => ['media', $stream, $filename, $mime],
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function messageStatus(string $requestId): array
    {
        return $this->request('GET', '/api/v1/messages/'.rawurlencode($requestId));
    }

    public function checkContact(string $phone): array
    {
        return $this->request('POST', '/api/v1/contacts/check', [
            'json' => ['phone' => $phone],
        ]);
    }

    public function listContacts(?string $query = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->request('GET', '/api/v1/contacts'.$this->pageQuery($query, $limit, $offset));
    }

    public function listChats(?string $query = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->request('GET', '/api/v1/chats'.$this->pageQuery($query, $limit, $offset));
    }

    public function chatMessages(string $jid, ?int $limit = null, ?string $before = null): array
    {
        $params = [];
        if ($limit !== null && $limit > 0) {
            $params['limit'] = $limit;
        }
        if ($before !== null && $before !== '') {
            $params['before'] = $before;
        }

        $query = $params === [] ? '' : '?'.http_build_query($params);

        return $this->request('GET', '/api/v1/chats/'.rawurlencode($jid).'/messages'.$query);
    }

    public function chatMedia(string $jid, string $messageId): array
    {
        return $this->requestBinary(
            '/api/v1/chats/'.rawurlencode($jid).'/messages/'.rawurlencode($messageId).'/media'
        );
    }

    public function chatPicture(string $jid): array
    {
        return $this->requestBinary('/api/v1/chats/'.rawurlencode($jid).'/picture');
    }

    protected function pageQuery(?string $query, ?int $limit, ?int $offset): string
    {
        $params = [];
        if ($query !== null && trim($query) !== '') {
            $params['query'] = $query;
        }
        if ($limit !== null && $limit > 0) {
            $params['limit'] = $limit;
        }
        if ($offset !== null && $offset > 0) {
            $params['offset'] = $offset;
        }

        return $params === [] ? '' : '?'.http_build_query($params);
    }

    // ------------------------------------------------------------ transport

    /**
     * Perform a single authenticated request against the bridge.
     *
     * @param  array{json?: array<string, mixed>, fields?: array<string, mixed>, file?: array{0: string, 1: resource, 2: string, 3: string}}  $options
     */
    protected function request(string $method, string $path, array $options = []): array
    {
        $baseUrl = config('rich-whatsapp.bridge_url', '');

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw ConfigurationException::missing('RICH_WHATSAPP_BRIDGE_URL');
        }

        $token = (string) config('rich-whatsapp.bridge_token', '');

        if ($token === '') {
            throw ConfigurationException::missing('RICH_WHATSAPP_BRIDGE_TOKEN');
        }

        $url = rtrim($baseUrl, '/').$path;

        $http = Http::withToken($token)
            ->acceptJson()
            ->timeout($this->timeouts['timeout'])
            ->connectTimeout($this->timeouts['connect']);

        if (isset($options['file'])) {
            [$name, $stream, $filename, $mime] = $options['file'];
            $http = $http->attach($name, $stream, $filename, ['Content-Type' => $mime]);
        }

        try {
            if ($method === 'GET') {
                $response = $http->get($url);
            } elseif (isset($options['json'])) {
                $response = $http->asJson()->post($url, $options['json']);
            } else {
                $response = $http->asMultipart()->post($url, $options['fields'] ?? []);
            }
        } catch (ConnectionException $e) {
            // Connection-level failure: bridge offline or unreachable.
            throw BridgeUnavailableException::forRequest($path, $e->getMessage());
        } catch (RequestException $e) {
            $status = $e->response === null ? 0 : $e->response->status();

            return $this->handleHttpFailure($path, $status, $this->bodyOf($e));
        }

        $body = $this->decode($response->body());

        if (! $response->successful()) {
            return $this->handleHttpFailure($path, $response->status(), $body);
        }

        // The bridge may answer HTTP 2xx with success:false for domain errors.
        if (is_array($body) && isset($body['success']) && $body['success'] === false) {
            return $this->handleDomainFailure($path, $body);
        }

        return $body;
    }

    /**
     * Performs a single authenticated non-JSON (binary) GET against the bridge
     * and returns the raw body plus response metadata. Failure mapping matches
     * `request()` so callers see the exact same exception types.
     *
     * @return array{body: string, content_type: string|null, filename: string|null}
     */
    protected function requestBinary(string $path): array
    {
        $baseUrl = config('rich-whatsapp.bridge_url', '');

        if (! is_string($baseUrl) || $baseUrl === '') {
            throw ConfigurationException::missing('RICH_WHATSAPP_BRIDGE_URL');
        }

        $token = (string) config('rich-whatsapp.bridge_token', '');

        if ($token === '') {
            throw ConfigurationException::missing('RICH_WHATSAPP_BRIDGE_TOKEN');
        }

        $url = rtrim($baseUrl, '/').$path;

        try {
            $response = Http::withToken($token)
                ->timeout($this->timeouts['timeout'])
                ->connectTimeout($this->timeouts['connect'])
                ->get($url);
        } catch (ConnectionException $e) {
            throw BridgeUnavailableException::forRequest($path, $e->getMessage());
        } catch (RequestException $e) {
            $status = $e->response === null ? 0 : $e->response->status();

            return $this->handleHttpFailure($path, $status, $this->bodyOf($e));
        }

        if ($response->status() !== 200) {
            return $this->handleHttpFailure($path, $response->status(), $this->decode($response->body()));
        }

        $disposition = $response->header('Content-Disposition');

        return [
            'body' => $response->body(),
            'content_type' => $response->header('Content-Type'),
            'filename' => $this->dispositionFilename($disposition),
        ];
    }

    protected function dispositionFilename(?string $disposition): ?string
    {
        if ($disposition === null) {
            return null;
        }

        if (preg_match('/filename="?([^";]+)"?/i', $disposition, $m)) {
            return $m[1];
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    protected function bodyOf(RequestException $e): ?array
    {
        if ($e->response === null) {
            return null;
        }

        $body = json_decode($e->response->body(), true);

        return is_array($body) ? $body : null;
    }

    /** @return array<string, mixed> */
    protected function decode(string $raw): array
    {
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>|null  $body
     *
     * @throws BridgeUnavailableException|BridgeAuthenticationException|MessageSendException|WhatsAppDisconnectedException
     */
    protected function handleHttpFailure(string $path, int $status, ?array $body): never
    {
        if ($status === 401 || $status === 403) {
            throw BridgeAuthenticationException::create($path, $status);
        }

        if ($status >= 500) {
            $detail = $body['error']['message'] ?? null;

            throw BridgeUnavailableException::forRequest($path, is_string($detail) ? $detail : null);
        }

        $code = $body['error']['code'] ?? null;

        if ($code === 'WHATSAPP_NOT_CONNECTED') {
            throw WhatsAppDisconnectedException::create();
        }

        $message = $body['error']['message'] ?? 'The bridge rejected the request.';

        throw (new MessageSendException(sprintf('%s (HTTP %d).', $message, $status)))
            ->withBridgePayload($body ?? []);
    }

    /**
     * @param  array<string, mixed>  $body
     *
     * @throws MessageSendException|WhatsAppDisconnectedException|BridgeAuthenticationException
     */
    protected function handleDomainFailure(string $path, array $body): never
    {
        $code = $body['error']['code'] ?? null;
        $message = $body['error']['message'] ?? 'The bridge rejected the request.';

        if ($code === 'UNAUTHORIZED' || $code === 'RATE_LIMITED') {
            // RATE_LIMITED is transient; report as authentication to avoid leaky retries is wrong —
            // map to a send failure instead.
            if ($code === 'UNAUTHORIZED') {
                throw BridgeAuthenticationException::create($path, 401);
            }

            throw (new MessageSendException((string) $message))->withBridgePayload($body);
        }

        if ($code === 'WHATSAPP_NOT_CONNECTED') {
            throw WhatsAppDisconnectedException::create();
        }

        throw (new MessageSendException((string) $message))->withBridgePayload($body);
    }

    protected function typeForMime(string $mime): string
    {
        return match (true) {
            str_starts_with(strtolower($mime), 'image/') => 'image',
            str_starts_with(strtolower($mime), 'video/') => 'video',
            str_starts_with(strtolower($mime), 'audio/') => 'audio',
            default => 'document',
        };
    }
}