<?php

namespace App\Providers;

use App\Models\Certificate;
use App\Models\User;
use App\Observers\CertificateObserver;
use App\Observers\Tac\RegistrationCommunityObserver;
use App\Observers\Tac\UserCommunityObserver;
use App\Services\Tac\RegistrationMemberMapper;
use App\Support\Payments\Contracts\PaymentGateway;
use App\Support\Payments\MesombPaymentGateway;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, MesombPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Certificate::observe(CertificateObserver::class);

        $this->registerCommunityAutoJoin();
    }

    /**
     * Anyone who registers for any Traitz Academy program, event, course or
     * internship is automatically included in the Traitz Academy Community.
     */
    protected function registerCommunityAutoJoin(): void
    {
        foreach (RegistrationMemberMapper::OBSERVED_MODELS as $model) {
            $model::observe(RegistrationCommunityObserver::class);
        }

        User::observe(UserCommunityObserver::class);
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
