<?php

namespace Tests\Unit;

use App\Http\Controllers\Auth\WebauthnAuthenticateController;
use LaravelWebauthn\Http\Controllers\AuthenticateController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebauthnAuthenticateControllerBindingTest extends TestCase
{
    #[Test]
    public function package_authenticate_controller_resolves_to_application_implementation(): void
    {
        $this->assertInstanceOf(
            WebauthnAuthenticateController::class,
            app(AuthenticateController::class)
        );
    }
}
