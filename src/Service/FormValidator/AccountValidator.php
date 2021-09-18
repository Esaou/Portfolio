<?php

declare(strict_types=1);

namespace App\Service\FormValidator;

use App\Service\AbstractValidator;

class AccountValidator extends AbstractValidator
{
    private $session;

    public function __construct($session)
    {

        parent::__construct($session);
        $this->session = $session;

    }

    public function accountValidator(array $data):bool{

        $error = false;

        if (!$this->validate($data)){
            $error = true;
        }

        if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[-+!*$@%_])([-+!*$@%_\w]{8,100})$/',$data['password'])){
            $this->session->addFlashes('danger','Votre mot de passe doit contenir au moins 1 chiffre, une lettre minuscule, majuscule, un caractère spécial et 8 caractères minimum !');
            $error = true;
        }

        if ($error === true){
            return false;
        }

        return true;
    }

}