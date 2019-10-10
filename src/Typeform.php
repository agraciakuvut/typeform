<?php

namespace WATR;

use GuzzleHttp\Client;
use WATR\Models\Form;
use WATR\Models\FormResponse;
use WATR\Models\Webhook;
use WATR\Models\WebhookResponse;

/**
 * Base Package wrapper for Typeform API
 */
class Typeform
{
    /**
     * @var  Client
     */
    protected $http;

    /**
     * @var  string Typeform API key
     */
    protected $apiKey;

    /**
     * @var string Typeform base URI
     */
    protected $baseUri = 'https://api.typeform.com/';

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->http = new Client([
            'base_uri' => $this->baseUri,
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->apiKey,
            ]
        ]);
    }

    /**
     * Get form information
     * @param $formId
     * @return Form
     */
    public function getForm($formId)
    {
        $response = $this->http->get("/forms/" . $formId);
        $body = json_decode($response->getBody());
        return new Form($body);
    }

    /**
     * Get form responses
     * @param $formId
     * @return array
     */
    public function getResponses($formId)
    {
        $response = $this->http->get("/forms/" . $formId . "/responses");
        $body = json_decode($response->getBody());
        $responses = [];
        if (isset($body->items)) {
            foreach ($body->items as $item) {
                $responses[] = new FormResponse($item);
            }
        }
        return $responses;
    }

    /**
     * Register webhook for form
     * @param string $formId
     * @param string $url
     * @param string $tag
     * @return Webhook
     * @throws \Exception
     */
    public function registerWebhook(string $formId, string $url, string $tag = "response")
    {
        $response = $this->http->put(
            "/forms/" . $formId . "/webhooks/" . $tag,
            [
                'json' => [
                    'url'     => $url,
                    'enabled' => true,
                ]
            ]
        );
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode > 300) {
            throw new \Exception('Failed to unregister webhook, form_id: ' . $formId . ', tag: ' . $tag);
        }
        return new Webhook(json_decode($response->getBody()));
    }


    public function addHiddenFields(Form $form, $fields)
    {
        $form->addHiddenFields($fields);

        $this->http->put(
            "/forms/" . $form->id,
            [
                'json' => (array)$form->getRaw()
            ]
        );
    }

    /**
     * Parse incoming webhook
     * @param $json
     * @return WebhookResponse
     */
    public static function parseWebhook($json)
    {
        return new WebhookResponse($json);
    }


    /**
     * Get form information
     * @param $formId
     * @return Webhook[]
     */
    public function getWebhooks($formId)
    {
        $response = $this->http->get("/forms/" . $formId . "/webhooks");
        $body = json_decode($response->getBody());
        $responses = [];
        if (isset($body->items)) {
            foreach ($body->items as $item) {
                $responses[] = new Webhook($item);
            }
        }
        return $responses;
    }


    /**
     * @param string $formId
     * @param string $tag
     * @return bool
     * @throws \Exception
     */
    public function unRegisterWebhook(string $formId, string $tag = "response")
    {
        $response = $this->http->delete("/forms/" . $formId . "/webhooks/" . $tag);
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode > 300) {
            throw new \Exception('Failed to unregister webhook, form_id: ' . $formId . ', tag: ' . $tag);
        }
        return true;
    }

    /**
     * @param Form $form
     * @param array $fields
     */
    public function removeHiddenFields(Form $form, $fields)
    {
        $form->removeHiddenFields($fields);

        $this->http->put(
            "/forms/" . $form->id,
            [
                'json' => (array)$form->getRaw()
            ]
        );
    }
}
