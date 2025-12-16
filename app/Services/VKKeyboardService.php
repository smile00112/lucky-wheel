<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformIntegration;
use App\Models\VKUser;
use Illuminate\Support\Facades\Log;
use App\Services\VKConnector;

class VKKeyboardService
{
    private VKTextService $textService;

    public function __construct(VKTextService $textService)
    {
        $this->textService = $textService;
    }

    public function getKeyboardForUser(?int $vkId, ?PlatformIntegration $integration = null, ?string $wheelSlug = null, ?int $guestId = null): array
    {
        //$hasPhone = $vkId ? $this->hasPhoneNumber($vkId) : false;

        $buttons = [];
        $vkUser = VKUser::findByVkId($vkId);

//        if (!$hasPhone) {
//            $sendPhoneText = $this->textService->get($integration, 'button_send_phone', '📱 Отправить номер');
//            $buttons[] = [
//                [
//                    'action' => [
//                        'type' => 'text',
//                        'label' => $sendPhoneText,
//                    ],
//                    'color' => 'primary',
//                ],
//            ];
//        } else
        {
            $spinText = $this->textService->get($integration, 'button_spin', '🎡 Крутить колесо');
            $historyText = $this->textService->get($integration, 'button_history', '📜 История призов');

            $connector = new VKConnector();
            $webAppUrl = $connector->buildLaunchUrl($integration, '', ['guest_id' => $vkUser->guest->id]);

            $miniapp_id_index = array_find_key((array)$integration->settings,  fn($item) => $item['key'] === 'app_id');
            $appId = !empty($integration->settings[$miniapp_id_index]['value']) ? $integration->settings[$miniapp_id_index]['value'] : null;

            if ($appId) {
                $buttons[] = [
                    [
                        'action' => [
                            'type' => 'open_app',
                            'label' => $spinText,
                            'app_id' => (int)$appId,
                            'hash' => $webAppUrl,
                        ],
                    ],
                ];

            }

//            $buttons[] = [
//                [
//                    'action' => [
//                        'type' => 'text',
//                        'label' => $spinText,
//                    ],
//                    'color' => 'positive',
//                ],
//            ];

            $buttons[] = [
                [
                    'action' => [
                        'type' => 'text',
                        'label' => $historyText,
                    ],
                    'color' => 'secondary',
                ],
            ];

            // Добавляем кнопку для Mini App, если есть wheelSlug
            if ($wheelSlug && $guestId && $integration) {
                $baseUrl = config('app.url');
                $webAppUrl = $baseUrl . '/vk/app?wheel=' . $wheelSlug . '&guest_id=' . $guestId;
                $appId = $integration->settings['app_id'] ?? null;

                if ($appId) {
                    $buttons[] = [
                        [
                            'action' => [
                                'type' => 'open_app',
                                'label' => '🎡 Открыть колесо',
                                'app_id' => (int)$appId,
                                'hash' => $webAppUrl,
                            ],
                            'color' => 'positive',
                        ],
                    ];
                }
            }
        }

        return [
            'one_time' => false,
            'buttons' => $buttons,
        ];
    }

    public function hasPhoneNumber(int $vkId): bool
    {
        $vkUser = VKUser::findByVkId($vkId);
        return $vkUser && !empty($vkUser->phone);
    }
}

