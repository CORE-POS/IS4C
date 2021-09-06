<?php

namespace Gohanman\Otto;

class Message
{
    private $json = array(
        '@type' => 'MessageCard',
        '@context' => 'http://schema.org/extensions',
        'markdown' => true,
    );

    public function body($body)
    {
        $this->json['text'] = $body;
    }

    public function title($subject)
    {
        $this->json['title'] = $subject;
    }

    public function toJSON()
    {
        return json_encode($this->json);
    }
}

