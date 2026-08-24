<?php

namespace Tests\Feature;

use App\Channels\WhatsAppChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * O canal do WhatsApp saía calado quando as credenciais faltavam: bastava
 * alguém mexer no `.env` e apagar o token para as notificações pararem sem que
 * ninguém desse por isso — nem no log, nem na fila, nem no portal.
 */
class WhatsAppChannelTest extends TestCase
{
    use RefreshDatabase;

    private const TELEFONE = '+55 11 98888-7777';

    public function test_it_logs_a_warning_when_the_credentials_are_missing(): void
    {
        Http::fake();
        Log::spy();

        config(['services.whatsapp.phone_id' => null, 'services.whatsapp.token' => null]);

        $this->send();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $mensagem, array $contexto): bool {
                $this->assertStringContainsString('WhatsApp', $mensagem);
                $this->assertSame([
                    'services.whatsapp.phone_id',
                    'services.whatsapp.token',
                ], $contexto['faltam']);

                return true;
            });

        Http::assertNothingSent();
    }

    public function test_the_warning_names_only_the_setting_that_is_missing(): void
    {
        Http::fake();
        Log::spy();

        config(['services.whatsapp.phone_id' => '1234567890', 'services.whatsapp.token' => null]);

        $this->send();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(function (string $mensagem, array $contexto): bool {
                $this->assertSame(['services.whatsapp.token'], $contexto['faltam']);

                // Nem o identificador do remetente nem o número do
                // destinatário podem sair em claro para o log.
                $serializado = json_encode($contexto);
                $this->assertStringNotContainsString('1234567890', $serializado);
                $this->assertStringNotContainsString('98888', $serializado);
                $this->assertStringNotContainsString('5511988887777', $serializado);

                return true;
            });
    }

    public function test_it_logs_nothing_when_the_credentials_are_configured(): void
    {
        Http::fake();
        Log::spy();

        config(['services.whatsapp.phone_id' => '1234567890', 'services.whatsapp.token' => 'segredo']);

        $this->send();

        Log::shouldNotHaveReceived('warning');
        Http::assertSentCount(1);
    }

    private function send(): void
    {
        $notifiable = User::factory()->create(['phone' => self::TELEFONE]);

        $notification = new class extends Notification
        {
            public function toWhatsApp(mixed $notifiable): string
            {
                return 'Há uma novidade na obra.';
            }
        };

        (new WhatsAppChannel)->send($notifiable, $notification);
    }
}
