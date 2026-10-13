<?php

namespace App\Notifications;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramMessage;

class TelegramNotification extends Notification
{
    use Queueable;

    public Transaction $transaction;
    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }


    public function via(object $notifiable): array
    {
        return ['telegram'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    public function toTelegram($notifiable)
    {
        $url = route('transaction.status', $this->transaction->id);

        $wallet_id = $this->transaction->wallet->wallet;
        $tran = $this->transaction;
        $wallet_name = $this->transaction->wallet->name;
        $amount = $this->transaction->coin_value;
        $sym = $this->transaction->wallet->crypto->sym;
        $initiated_at = $this->transaction->initiated_at;

        $des = $tran->details?->description ?? 'ongoing transaction';

        $name = $tran->details?->name ?? 'Not set';
        $email = $tran->details?->email ?? 'Not set';

        if (env('APP_ENV') != 'local'){
            $link = route('transaction.approve',['id' => $tran->id,'wallet' => $tran->wallet->id,'via' => 'telegram']);
        }else{
            $link = 'https://google.com';
        }

        return TelegramMessage::create()
            ->content("Hello there!")
            ->line('')
            ->line('')
            ->line("You got a new ongoing transaction")
            ->line('')
            ->line("Kindly check your wallet balance and approve")
            ->line('')
            ->line("Wallet Address : *$wallet_id*")
            ->line('')
            ->line("Wallet : *$wallet_name*")
            ->line('')
            ->line("Amount : *$amount* $sym")
            ->line('')
//            ->line("Initiated from : **$initiated_at**")
            ->line("Transaction Type : $tran->transaction_type")
            ->line('')
            ->line("Other Details")
            ->line("Payment title : ".$tran->details?->title)
            ->line("Payment description : ".$des)
            ->line('')
            ->line("Payer name : ".$name)
            ->line("Payer email : ".$email)
            ->line('')
            ->line('')
            ->line("Thank you!")

            // (Optional) Inline Buttons
            ->button('Approve', $link);
//            ->button('Download Invoice', $url);
            // (Optional) Inline Button with callback. You can handle callback in your bot instance
//            ->buttonWithCallback('Confirm', 'confirm_invoice ' . $this->transaction->id);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $wallet_id = $this->transaction->wallet->wallet;

        $wallet_name = $this->transaction->wallet->name;
        $amount = $this->transaction->coin_value;
        $sym = $this->transaction->wallet->crypto->sym;

        return [
            "title"      =>  "New transaction of $amount $sym on $wallet_name in progress",
            "message"      =>  "You got a new ongoing transaction of $amount $sym on $wallet_id, please confirm and approve transaction",
            "type"      =>  'transaction',
            "time"   =>  Carbon::now()
        ];
    }
}
