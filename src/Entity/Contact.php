<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;


class Contact
{
    /**
     * Undocumented variable
     *
     * @var string|null
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=100 )
     */
    private $firstName;



    
    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Email()
     * 
     */
    private $email;


    /**
     * Undocumented variable
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=256 )
     * 
     * 
     */
    private $subject;

    /**
     * Undocumented variable
     *
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min=5)
     * 
     * 
     */
    private $content;


    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }
    

    /**
     * Get undocumented variable
     *
     * @return  string
     */ 
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $content  Undocumented variable
     *
     * @return  self
     */ 
    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string
     */ 
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set undocumented variable
     *
     * @param  string  $subject  Undocumented variable
     *
     * @return  self
     */ 
    public function setSubject(string $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  string|null
     */ 
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set undocumented variable
     *
     * @param  string|null  $firstName  Undocumented variable
     *
     * @return  self
     */ 
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

   
}
