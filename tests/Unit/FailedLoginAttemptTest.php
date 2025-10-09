<?php

namespace Tests\Unit;

use App\Models\FailedLoginAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FailedLoginAttemptTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_log_failed_attempt()
    {
        FailedLoginAttempt::logAttempt('test@example.com', '192.168.1.1', 'Mozilla/5.0');

        $this->assertDatabaseHas('failed_login_attempts', [
            'email' => 'test@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
        ]);
    }

    #[Test]
    public function it_can_get_recent_attempts_count()
    {
        // Create 3 recent attempts
        for ($i = 0; $i < 3; $i++) {
            FailedLoginAttempt::create([
                'email' => 'test@example.com',
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
                'attempted_at' => now()->subMinutes(5),
            ]);
        }

        // Create 1 old attempt (should not be counted)
        FailedLoginAttempt::create([
            'email' => 'test@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'attempted_at' => now()->subHours(2),
        ]);

        $count = FailedLoginAttempt::getRecentAttemptsCount('test@example.com', '192.168.1.1');

        $this->assertEquals(3, $count);
    }

    #[Test]
    public function it_can_filter_by_email()
    {
        FailedLoginAttempt::create([
            'email' => 'user1@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'attempted_at' => now(),
        ]);

        FailedLoginAttempt::create([
            'email' => 'user2@example.com',
            'ip_address' => '192.168.1.2',
            'user_agent' => 'Mozilla/5.0',
            'attempted_at' => now(),
        ]);

        $attempts = FailedLoginAttempt::byEmail('user1@example.com')->get();

        $this->assertCount(1, $attempts);
        $this->assertEquals('user1@example.com', $attempts->first()->email);
    }

    #[Test]
    public function it_can_filter_by_ip()
    {
        FailedLoginAttempt::create([
            'email' => 'test1@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'attempted_at' => now(),
        ]);

        FailedLoginAttempt::create([
            'email' => 'test2@example.com',
            'ip_address' => '192.168.1.2',
            'user_agent' => 'Mozilla/5.0',
            'attempted_at' => now(),
        ]);

        $attempts = FailedLoginAttempt::byIp('192.168.1.1')->get();

        $this->assertCount(1, $attempts);
        $this->assertEquals('192.168.1.1', $attempts->first()->ip_address);
    }

    #[Test]
    public function it_can_get_recent_attempts_within_time_window()
    {
        // Create attempt within last 30 minutes
        FailedLoginAttempt::create([
            'email' => 'test@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'attempted_at' => now()->subMinutes(20),
        ]);

        // Create attempt outside time window
        FailedLoginAttempt::create([
            'email' => 'test@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'attempted_at' => now()->subMinutes(90),
        ]);

        $attempts = FailedLoginAttempt::recent(60)->get();

        $this->assertCount(1, $attempts);
    }

    #[Test]
    public function it_tracks_multiple_ips_for_same_email()
    {
        FailedLoginAttempt::logAttempt('test@example.com', '192.168.1.1', 'Mozilla/5.0');
        FailedLoginAttempt::logAttempt('test@example.com', '192.168.1.2', 'Chrome');
        FailedLoginAttempt::logAttempt('test@example.com', '192.168.1.3', 'Safari');

        $attempts = FailedLoginAttempt::byEmail('test@example.com')->get();

        $this->assertCount(3, $attempts);
        $this->assertCount(3, $attempts->pluck('ip_address')->unique());
    }

    #[Test]
    public function it_casts_attempted_at_to_datetime()
    {
        $attempt = FailedLoginAttempt::create([
            'email' => 'test@example.com',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'attempted_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $attempt->attempted_at);
    }
}
