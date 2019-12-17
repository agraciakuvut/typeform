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
     * @param string $url
     * @return string
     * @throws \Exception
     */
    public function getIdFromUrl(string $url): string
    {
        $urlParts = parse_url($url);

        if ($urlParts['scheme'] != 'https') {
            $urlParts['scheme'] = 'https';
        }

        if (substr($urlParts['host'], -12) != 'typeform.com') {
            throw new \Exception('Url typeform invalid');
        }

        if (substr($urlParts['path'], 0, 4) != '/to/') {
            throw new \Exception('Url typeform invalid');
        }

        $formId = substr($urlParts['path'], 4);

        if (empty($formId)) {
            throw new \Exception('Url typeform invalid');
        }

        return $formId;
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
     * @param $parameters - Can be [page_size, since, until, after, before, included_response_ids, completed, sort, query, fields]
     * @return array
     */
    public function getResponses(
        $formId,
        $parameters = []
    ) {
        $q = [];

        if (!empty($parameters['page_size'])) {
            $q[] = "page_size=" . $parameters['page_size'];
        }

        if (!empty($parameters['since'])) {
            $q[] = "since=" . $parameters['since'];
        }

        if (!empty($parameters['until'])) {
            $q[] = "until=" . $parameters['until'];
        }

        if (!empty($parameters['after'])) {
            $q[] = "after=" . $parameters['after'];
        }

        if (!empty($parameters['before'])) {
            $q[] = "before=" . $parameters['before'];
        }

        if (!empty($parameters['included_response_ids'])) {
            $q[] = "included_response_ids=" . $parameters['included_response_ids'];
        }

        if (!empty($parameters['completed'])) {
            $q[] = "completed=" . $parameters['completed'];
        }

        if (!empty($parameters['sort'])) {
            $q[] = "sort=" . $parameters['sort'];
        }

        if (!empty($parameters['query'])) {
            $q[] = "query=" . $parameters['query'];
        }

        if (!empty($parameters['fields'])) {
            $q[] = "fields=" . $parameters['fields'];
        }

        $query = '';

        if (!empty($q)) {
            array_walk($q, function ($v) {
                return urldecode($v);
            });
            $query = "?" . implode("&", $q);
        }

        $response = $this->http->get("/forms/" . $formId . "/responses$query");
        return json_decode($response->getBody());
        //$responses = [];
        //if (isset($body->items)) {
        //    foreach ($body->items as $item) {
        //        $responses[] = new FormResponse($item);
        //    }
        //}
        //return $responses;
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

    /**
     * @param string $fomrId
     * @param array $reponseTokens
     * @throws \Exception
     */
    public function deleteResponses(string $fomrId, array $reponseTokens)
    {
        if (sizeof($reponseTokens) > 1000) {
            throw new \Exception('Limit for deleting responses (1000) exceded');
        }

        $this->http->delete(
            "/forms/" . $fomrId . "/responses?included_tokens=" . urlencode(implode(',', $reponseTokens))
        );
    }
}
