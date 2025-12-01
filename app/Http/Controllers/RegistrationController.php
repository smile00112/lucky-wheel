<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\WelcomeMail;
use App\Models\Company;
use App\Models\User;
use App\Services\MailConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    public function register(Request $request)
    {
        $isLocal = app()->environment('local');
        
        $rules = [
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];

        if (!$isLocal) {
            $rules['captcha_token'] = 'required|string';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Проверка Яндекс капчи (пропускаем в локальном окружении)
        if (!$isLocal) {
            $captchaValid = $this->verifyYandexCaptcha($request->captcha_token);
            if (!$captchaValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка проверки капчи. Пожалуйста, попробуйте еще раз.',
                ], 422);
            }
        }

        try {
            $company = Company::create([
                'name' => $request->company_name,
            ]);

            $user = User::create([
                'name' => $request->company_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_OWNER,
                'company_id' => $company->id,
            ]);

            // Отправка письма приветствия с настройками из .env
            $mailConfigService = app(MailConfigService::class);
            $mailConfigService->resetToEnvConfig();
            
            Mail::to($user->email)->send(new WelcomeMail($user));

            return response()->json([
                'success' => true,
                'message' => 'Регистрация успешно завершена! Проверьте вашу почту для дальнейших инструкций.',
            ]);
        } catch (\Exception $e) {
            Log::error('Registration error', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.',
            ], 500);
        }
    }

    protected function verifyYandexCaptcha(string $token): bool
    {
        $secretKey = config('services.yandex.captcha_secret');
        
        if (!$secretKey) {
            Log::warning('Yandex captcha secret key not configured');
            return false;
        }

        try {
            $response = Http::asForm()->post('https://smartcaptcha.yandexcloud.net/validate', [
                'secret' => $secretKey,
                'token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['status']) && $data['status'] === 'ok';
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Yandex captcha verification error', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

