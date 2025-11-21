<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Guest;
use App\Models\TelegramUser;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Найти или создать пользователя по телефону
     */
    public function findOrCreateByPhone(string $phone, array $additionalData = []): Guest
    {
        $phone = $this->normalizePhone($phone);

        $guest = Guest::where('phone', $phone)->first();

        if (!$guest) {
            $guest = Guest::create([
                'phone' => $phone,
                'name' => $additionalData['name'] ?? null,
                'email' => $additionalData['email'] ?? null,
                'metadata' => $additionalData['metadata'] ?? [],
            ]);

            Log::info('Created new guest by phone', [
                'guest_id' => $guest->id,
                'phone' => $phone,
            ]);
        } else {
            // Обновляем данные, если они предоставлены
            $updateData = [];
            if (isset($additionalData['name']) && !$guest->name) {
                $updateData['name'] = $additionalData['name'];
            }
            if (isset($additionalData['email']) && !$guest->email) {
                $updateData['email'] = $additionalData['email'];
            }
            if (!empty($updateData)) {
                $guest->update($updateData);
            }
        }

        return $guest;
    }

    /**
     * Найти или создать Telegram пользователя
     */
    public function findOrCreateTelegramUser(
        int $telegramId,
        Guest $guest,
        array $telegramData = []
    ): TelegramUser {
        $telegramUser = TelegramUser::findByTelegramId($telegramId);

        if (!$telegramUser) {
            $telegramUser = TelegramUser::create([
                'guest_id' => $guest->id,
                'telegram_id' => $telegramId,
                'username' => $telegramData['username'] ?? null,
                'first_name' => $telegramData['first_name'] ?? null,
                'last_name' => $telegramData['last_name'] ?? null,
                'phone' => $telegramData['phone'] ?? null,
                'metadata' => $telegramData['metadata'] ?? [],
            ]);

            Log::info('Created new Telegram user', [
                'telegram_user_id' => $telegramUser->id,
                'telegram_id' => $telegramId,
                'guest_id' => $guest->id,
            ]);
        } else {
            // Обновляем данные Telegram пользователя
            $updateData = [];
            if (isset($telegramData['username']) && $telegramData['username'] !== $telegramUser->username) {
                $updateData['username'] = $telegramData['username'];
            }
            if (isset($telegramData['first_name']) && $telegramData['first_name'] !== $telegramUser->first_name) {
                $updateData['first_name'] = $telegramData['first_name'];
            }
            if (isset($telegramData['last_name']) && $telegramData['last_name'] !== $telegramUser->last_name) {
                $updateData['last_name'] = $telegramData['last_name'];
            }
            if (isset($telegramData['phone']) && $telegramData['phone'] !== $telegramUser->phone) {
                $updateData['phone'] = $telegramData['phone'];
            }
            if (!empty($updateData)) {
                $telegramUser->update($updateData);
            }

            // Обновляем связь с Guest, если она изменилась
            if ($telegramUser->guest_id !== $guest->id) {
                $telegramUser->update(['guest_id' => $guest->id]);
            }
        }

        return $telegramUser;
    }

    /**
     * Обработать контакт Telegram и связать с пользователем
     */
    public function processTelegramContact(int $telegramId, array $contactData): TelegramUser
    {
        $phone = $this->normalizePhone($contactData['phone_number'] ?? '');

        if (!$phone) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        // Найти или создать Guest по телефону
        $guest = $this->findOrCreateByPhone($phone, [
            'name' => trim(($contactData['first_name'] ?? '') . ' ' . ($contactData['last_name'] ?? '')),
        ]);

        // Найти или создать TelegramUser
        $telegramUser = $this->findOrCreateTelegramUser($telegramId, $guest, [
            'username' => $contactData['username'] ?? null,
            'first_name' => $contactData['first_name'] ?? null,
            'last_name' => $contactData['last_name'] ?? null,
            'phone' => $phone,
        ]);

        return $telegramUser;
    }

    /**
     * Нормализовать номер телефона
     */
    private function normalizePhone(string $phone): string
    {
        // Удаляем все нецифровые символы, кроме +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Если номер начинается с +, оставляем как есть
        // Если начинается с 8, заменяем на +7
        if (str_starts_with($phone, '8') && strlen($phone) === 11) {
            $phone = '+7' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+') && strlen($phone) === 10) {
            $phone = '+7' . $phone;
        } elseif (!str_starts_with($phone, '+') && strlen($phone) === 11 && str_starts_with($phone, '7')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }
}

