<?php

namespace CodeSteppers\Generated\Codestepper;

use JsonSerializable;

class Codestepper implements JsonSerializable
{
    private $id;
private $slug;
private $subscriberId;
private $guestId;
private $title;
private $createdAt;


    
public function __construct($id, $slug, $subscriberId, $guestId, $title, $createdAt)
{
        $this->id = $id;
$this->slug = $slug;
$this->subscriberId = $subscriberId;
$this->guestId = $guestId;
$this->title = $title;
$this->createdAt = $createdAt;

}
    
    public function getId(): ?int
    {
        return $this->id;
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
            'id' => $this->id,
 'slug' => $this->slug,
 'subscriberId' => $this->subscriberId,
 'guestId' => $this->guestId,
 'title' => $this->title,
 'createdAt' => $this->createdAt,

        ];
    }
}
