<?php

namespace App\Http\Controllers\Auth;

use LaravelWebauthn\Actions\LoginUserRetrieval;
use LaravelWebauthn\Actions\PrepareAssertionData;
use LaravelWebauthn\Contracts\LoginViewResponse;
use LaravelWebauthn\Http\Controllers\AuthenticateController as BaseAuthenticateController;
use LaravelWebauthn\Http\Requests\WebauthnLoginAttemptRequest;
use LaravelWebauthn\Services\Webauthn;

class WebauthnAuthenticateController extends BaseAuthenticateController
{
    /**
     * For an authenticated session (second factor), the package passes null when user verification
     * is discouraged, so the challenge is cached under cacheKey(null) while validation uses
     * cacheKey(user) — abort(404, 'No public key credential found'). Scope to the session user when present.
     */
    public function create(WebauthnLoginAttemptRequest $request): LoginViewResponse
    {
        $user = $this->createPipeline($request)->then(function ($request) {
            if ($request->user() !== null) {
                return $request->user();
            }

            if (Webauthn::userless() || config('webauthn.user_verification') === 'discouraged') {
                return null;
            }

            return app(LoginUserRetrieval::class)($request);
        });

        $publicKey = app(PrepareAssertionData::class)($user);

        return app(LoginViewResponse::class)
            ->setPublicKey($request, $publicKey);
    }
}
