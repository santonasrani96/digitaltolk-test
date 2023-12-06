<?php
 
namespace Tests\Unit;
 
use PHPUnit\Framework\TestCase;
use App\Helpers\TeHelper;
use Carbon\Carbon;
 
class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_basic_test(): void
    {
        $this->assertTrue(true);
    }

    public function test_will_expired_at(): string
    {
        $mock_created_at = "1990-01-01 00:01";
        $mock_due_time = "1990-01-01 23:59";

        $created_at = Carbon::parse($mock_created_at);
        $due_time = Carbon::parse($mock_due_time);
        $difference = $due_time->diffInHours($created_at);

        if($difference <= 90)
            $expected_time = $due_time;
        elseif ($difference <= 24) {
            $expected_time = $created_at->addMinutes(90);
        } elseif ($difference > 24 && $difference <= 72) {
            $expected_time = $created_at->addHours(16);
        } else {
            $expected_time = $due_time->subHours(48);
        }

        $result = TeHelper::willExpireAt($due_time, $created_at);
        $this->assertEquals($expected_time->format('Y-m-d H:i:s'), $result);
    }
}