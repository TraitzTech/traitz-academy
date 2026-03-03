<?php

namespace App\Notifications;

use App\Helpers\SettingHelper;
use App\Models\AiForgeOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AiForgeSwagOrderReceipt extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private AiForgeOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = SettingHelper::get('site_name', config('app.name'));

        $message = (new MailMessage)
            ->subject("AI Forge Swag Order Receipt - {$this->order->receipt_number}")
            ->greeting("Hello {$this->order->first_name},")
            ->line('Thank you for your AI Forge swag order! Here are your order details:')
            ->line('')
            ->line("**Order Number:** {$this->order->order_number}")
            ->line("**Receipt:** {$this->order->receipt_number}")
            ->line("**Date:** {$this->order->paid_at?->format('F j, Y g:i A')}")
            ->line('');

        foreach ($this->order->items as $item) {
            $itemName = $item->swag?->name ?? 'Swag Item';
            $variation = $item->variation ? " ({$item->variation})" : '';
            $message->line("- {$itemName}{$variation} × {$item->quantity} = ".number_format((float) $item->total_price, 0, '.', ',')." {$this->order->currency}");
        }

        $message->line('')
            ->line('**Subtotal:** '.number_format((float) $this->order->subtotal, 0, '.', ',')." {$this->order->currency}")
            ->when((float) $this->order->surcharge_amount > 0, function (MailMessage $m) {
                return $m->line("**Transaction Fee ({$this->order->surcharge_percentage}%):** ".number_format((float) $this->order->surcharge_amount, 0, '.', ',')." {$this->order->currency}");
            })
            ->line('**Total Paid:** '.number_format((float) $this->order->total_amount, 0, '.', ',')." {$this->order->currency}")
            ->line('')
            ->action('View Order', url("/ai-forge/orders/{$this->order->id}/confirmation"))
            ->line('Thank you for supporting AI Forge!')
            ->salutation("Best regards,\n{$siteName}");

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total_amount' => $this->order->total_amount,
        ];
    }
}
