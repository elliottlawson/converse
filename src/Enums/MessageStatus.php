<?php

namespace ElliottLawson\Converse\Enums;

enum MessageStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Error = 'error';
}
