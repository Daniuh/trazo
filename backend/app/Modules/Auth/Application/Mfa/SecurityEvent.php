<?php

declare(strict_types=1);

// Cambios de seguridad que se avisan por correo. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Mfa;

enum SecurityEvent: string
{
    case MfaEnabled = 'mfa_enabled';
    case MfaDisabled = 'mfa_disabled';
    case RecoveryCodeUsed = 'recovery_code_used';
    case RecoveryCodesRegenerated = 'recovery_codes_regenerated';
    case PasswordChanged = 'password_changed';
}
