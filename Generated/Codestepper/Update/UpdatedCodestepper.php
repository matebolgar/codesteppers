<?php

namespace CodeSteppers\Generated\Codestepper\Update;

use JsonSerializable;

class UpdatedCodestepper implements JsonSerializable
{
    private $slug;
private $title;


    
public function __construct($slug, $title)
{
        $this->slug = $slug;
$this->title = $title;

}
    
    public function getSlug(): ?string
    {
        return $this->slug;
    }
    public function getTitle(): ?string
    {
        return $this->title;
    }
    
    
    public function jsonSerialize()
    {
        return [
            'slug' => $this->slug,
 'title' => $this->title,

        ];
    }
}
