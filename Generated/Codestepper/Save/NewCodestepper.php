<?php

namespace CodeSteppers\Generated\Codestepper\Save;

use JsonSerializable;

class NewCodestepper implements JsonSerializable
{
    private $slug;
private $subscriberId;
private $guestId;
private $title;
private $createdAt;


    
public function __construct($slug, $subscriberId, $guestId, $title, $createdAt)
{
        $this->slug = $slug;
$this->subscriberId = $subscriberId;
$this->guestId = $guestId;
$this->title = $title;
$this->createdAt = $createdAt;

}
    
    public function getSlug(): ?string
    {
        return $this->slug;
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
    public function getCreatedAt(): ?int
    {
        return $this->createdAt;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'slug' => $this->slug,
 'subscriberId' => $this->subscriberId,
 'guestId' => $this->guestId,
 'title' => $this->title,
 'createdAt' => $this->createdAt,

        ];
    }
}
