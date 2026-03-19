<?php

declare(strict_types=1);

namespace AzureOss\Storage\Queue\Requests;

/**
 * @internal
 */
final class QueueMessageRequestBody
{
    public function __construct(
        public readonly string $messageText,
    ) {}

    public function toXml(): \SimpleXMLElement
    {
        $xml = new \SimpleXMLElement('<QueueMessage></QueueMessage>');
        $xml->addChild('MessageText', $this->messageText);

        return $xml;
    }
}
