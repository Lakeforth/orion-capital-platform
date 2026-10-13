<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case RIDER_ASSIGNED = 'rider_assigned';
    case RIDER_AT_PICKUP = 'rider_at_pickup';
    case ON_TRANSIT = 'on_transit';
    case CLOSE_TO_LOCATION = 'close_to_location';
    case AT_LOCATION = 'at_location';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::RIDER_ASSIGNED => 'Rider Assigned',
            self::RIDER_AT_PICKUP => 'Rider at Pickup',
            self::ON_TRANSIT => 'On Transit',
            self::CLOSE_TO_LOCATION => 'Close to Location',
            self::AT_LOCATION => 'At Location',
            self::DELIVERED => 'Delivered',
        };
    }

    public function getIndex(): int
    {
        return array_search($this, self::cases());
    }

    public static function all(): array
    {
        return array_map(fn($status) => $status->value, self::cases());
    }
}
