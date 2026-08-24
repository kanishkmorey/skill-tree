<?php

namespace App\Enums;

enum KnowledgeStatus: string
{
    case NotStarted = 'not-started';
    case Learning = 'learning';
    case Familiar = 'familiar';
    case Proficient = 'proficient';
    case Mastered = 'mastered';
}
