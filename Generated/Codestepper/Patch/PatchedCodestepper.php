<?php

namespace CodeSteppers\Generated\Codestepper\Patch;

use JsonSerializable;

class PatchedCodestepper implements JsonSerializable
{
    private $subscriberId;
private $guestId;
private $title;


    
public function __construct($subscriberId, $guestId, $title)
{
        $this->subscriberId = $subscriberId;
$this->guestId = $guestId;
$this->title = $title;

}
    
    public function getSubscriberId(): ?int
    {
        return $this->subscriberId;
    }
    public function getGuestId(): ?string
    {
        return $this->guestId;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'subscriberId' => $this->subscriberId,
 'guestId' => $this->guestId,
 'title' => $this->title,

        ];
    }
}
