<?php

declare(strict_types=1);

interface IncomingSmsHandler
{
    public function process(string $message, string $source_adress, string $dest_adress, subscription $subscription, string $transaction_id): void;
}
