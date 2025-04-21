<?php

namespace Database\Factories;

use App\Models\NotificationSettings;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationSettingsFactory extends Factory
{
  protected $model = NotificationSettings::class;

  public function definition(): array
  {
    return [
      'user_id' => User::factory(),
      'email_notifications' => true,
      'database_notifications' => true,
      'notification_thresholds' => [30, 14, 7, 3, 1],
    ];
  }

  public function emailOnly(): self
  {
    return $this->state([
      'database_notifications' => false,
    ]);
  }

  public function databaseOnly(): self
  {
    return $this->state([
      'email_notifications' => false,
    ]);
  }

  public function customThresholds(array $thresholds): self
  {
    return $this->state([
      'notification_thresholds' => $thresholds,
    ]);
  }
}
