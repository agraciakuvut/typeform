<?php

namespace WATR\Models;

/**
 * Webhook Model
 */
class Webhook
{
    /**
     * @var string identifier of the webhoook
     */
    public $id;

    /**
     * @var string identifier of the form
     */
    public $form_id;

    /**
     * @var string tag of the webhook
     */
    public $tag;

    /**
     * @var string url of the webhook
     */
    public $url;


    /**
     * @var boolean if the webhook is enabled
     */
    public $enabled;

    /**
     * @var boolean if the webhook verfies the ssl certificate
     */
    public $verify_ssl;

    /**
     * @var string webhook creation date
     */
    public $created_at;

    /**
     * @var string webhook updated date
     */
    public $updated_at;

    /**
     * constructor
     * @param $json
     */
    public function __construct($json)
    {
        $this->id = $json->id;
        $this->form_id = $json->form_id;
        $this->tag = $json->tag;
        $this->url = $json->url;
        $this->enabled = $json->enabled;
        $this->verify_ssl = $json->verify_ssl;
        $this->created_at = $json->created_at;
        $this->updated_at = $json->updated_at;
    }
}
