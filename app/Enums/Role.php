<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Receptionist = 'receptionist';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
