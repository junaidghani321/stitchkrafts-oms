<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

/**
 * Base for every order-module notification.
 *
 * Email only. In-app notifications were removed, so `database` is no longer a
 * channel and nothing is written to the notifications table.
 *
 * Mail is sent INLINE, on the sync connection — see viaConnections(). That is
 * a deliberate trade: no queue worker has to be running for mail to go out,
 * at the cost of the request waiting on SMTP. See the note there.
 */
abstract class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Mail runs on the sync connection: the send happens during the request,
     * so nothing depends on `php artisan queue:work` being up.
     *
     * The cost is real and worth knowing. Each recipient costs an SMTP
     * round-trip that the user waits on, and notifying several staff at once
     * can trip a provider's per-second rate limit. Delivery is best-effort —
     * OrderNotifier::send() catches and reports failures rather than failing
     * the business action — so a rejected message is logged, not retried.
     *
     * Putting mail back on a worker is a one-line change: return
     * config('queue.default') here instead of 'sync'.
     */
    public function viaConnections(): array
    {
        return ['mail' => 'sync'];
    }

    /**
     * Mail is the only channel, and only when the recipient's Settings → Email
     * switch for this kind of message is on. An empty array means this person
     * has opted out and nothing is sent.
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User && $notifiable->prefers($this->preferenceKey($notifiable))) {
            return ['mail'];
        }

        return [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hello '.($notifiable->name ?? 'there').',')
            ->line($this->text());

        if ($url = $this->urlFor($notifiable)) {
            $mail->action($this->actionLabel(), $url);
        }

        return $mail
            ->line('You are receiving this because of your email settings.')
            ->salutation('— '.config('app.name', 'OMS'));
    }

    /**
     * Which Settings → Email switch gates this message, for this recipient.
     *
     * Mail is the only channel now, so returning null — or naming a key this
     * role does not offer — means the message is not sent at all. Takes the
     * notifiable because the same class can go to more than one audience:
     * OrderPlaced is `order_placed` for the customer and `new_order_alert`
     * for staff.
     */
    abstract protected function preferenceKey(object $notifiable): ?string;

    /**
     * The order link, built for the recipient's own role — admin and customer
     * reach the same order by different routes.
     */
    protected function urlFor(object $notifiable): ?string
    {
        $orderId = $this->orderId();

        if ($orderId === null || ! $notifiable instanceof User) {
            return null;
        }

        $route = $notifiable->type.'.orders.show';

        return Route::has($route) ? route($route, $orderId) : null;
    }

    protected function subject(): string
    {
        return config('app.name', 'OMS').' — '.$this->text();
    }

    protected function actionLabel(): string
    {
        return 'View Order';
    }

    abstract protected function text(): string;

    abstract protected function orderId(): ?int;
}
